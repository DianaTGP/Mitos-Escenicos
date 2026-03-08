<?php
/**
 * Procesar pago con Openpay: crear orden, cobrar, guardar ítems, opcionalmente guardar tarjeta.
 * POST: token_id (tarjeta nueva) o tarjeta_guardada_id (id de tarjetas_guardadas), total, guardar_tarjeta (opcional), nombre_factura, email_factura, device_session_id (opcional).
 */
require_once dirname(__DIR__) . '/php/init.php';
require_once dirname(__DIR__) . '/php/openpay-helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

mitos_requiere_login('pago.php');

$carrito = $_SESSION['carrito'] ?? [];
if (empty($carrito)) {
    echo json_encode(['ok' => false, 'error' => 'El carrito está vacío']);
    exit;
}

$subtotal = 0;
foreach ($carrito as $item) {
    $subtotal += (float) ($item['precio_unitario'] ?? 0) * (int) ($item['cantidad'] ?? 1);
}
$envio = 0;
foreach ($carrito as $item) {
    if (isset($item['tipo']) && $item['tipo'] === 'mercancia') {
        $envio = 12.50;
        break;
    }
}
$impuestos = $subtotal > 0 ? round($subtotal * 0.075, 2) : 0;
$total = round($subtotal + $envio + $impuestos, 2);

$postTotal = (float) ($_POST['total'] ?? 0);
if (abs($postTotal - $total) > 0.01) {
    echo json_encode(['ok' => false, 'error' => 'El total no coincide. Actualiza el carrito.']);
    exit;
}

$user = mitos_usuario_actual();
$pdo = mitos_pdo();

$tarjetaGuardadaId = (int) ($_POST['tarjeta_guardada_id'] ?? 0);
$tokenId = trim((string) ($_POST['token_id'] ?? ''));
$guardarTarjeta = isset($_POST['guardar_tarjeta']) && $_POST['guardar_tarjeta'] === '1';
$deviceSessionId = trim((string) ($_POST['device_session_id'] ?? ''));

$chargeResult = null;

if ($tarjetaGuardadaId > 0) {
    $stmt = $pdo->prepare('SELECT openpay_customer_id, openpay_card_id FROM tarjetas_guardadas WHERE id = ? AND usuario_id = ?');
    $stmt->execute([$tarjetaGuardadaId, $user['id']]);
    $tarjeta = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$tarjeta) {
        echo json_encode(['ok' => false, 'error' => 'Tarjeta no encontrada']);
        exit;
    }
    $chargeResult = openpay_charge_saved_card(
        $tarjeta['openpay_customer_id'],
        $tarjeta['openpay_card_id'],
        $total,
        'Mitos Escénicos - Orden'
    );
} elseif ($tokenId !== '' && OPENPAY_MERCHANT_ID && OPENPAY_PRIVATE_KEY) {
    $chargeResult = openpay_charge_token($tokenId, $total, 'Mitos Escénicos - Orden', $deviceSessionId);
} else {
    echo json_encode(['ok' => false, 'error' => 'Indica una tarjeta o token de pago']);
    exit;
}

if (!$chargeResult || isset($chargeResult['error'])) {
    $msg = isset($chargeResult['description']) ? $chargeResult['description'] : ($chargeResult['error'] ?? 'Error al procesar el pago');
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

$openpayChargeId = $chargeResult['id'] ?? '';
$openpayOrderId = $chargeResult['order_id'] ?? '';

$tipoOrden = 'mixto';
$soloBoletos = true;
$soloMercancia = true;
foreach ($carrito as $item) {
    if (($item['tipo'] ?? '') === 'boleto') {
        $soloMercancia = false;
    } else {
        $soloBoletos = false;
    }
}
if ($soloBoletos) {
    $tipoOrden = 'boletos';
} elseif ($soloMercancia) {
    $tipoOrden = 'tienda';
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('INSERT INTO ordenes (usuario_id, total, estado, tipo) VALUES (?, ?, ?, ?)');
    $stmt->execute([$user['id'], $total, 'pagado', $tipoOrden]);
    $ordenId = (int) $pdo->lastInsertId();

    $stmtItem = $pdo->prepare('INSERT INTO orden_items (orden_id, tipo_item, funcion_id, mercancia_id, cantidad, precio_unitario, detalles) VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($carrito as $item) {
        $tipo = $item['tipo'] ?? '';
        $funcionId = ($tipo === 'boleto' && isset($item['funcion_id'])) ? (int) $item['funcion_id'] : null;
        $mercanciaId = ($tipo === 'mercancia' && isset($item['mercancia_id'])) ? (int) $item['mercancia_id'] : null;
        $cantidad = (int) ($item['cantidad'] ?? 1);
        $precio = (float) ($item['precio_unitario'] ?? 0);
        $detalles = json_encode(['nombre' => $item['nombre'] ?? '']);
        $stmtItem->execute([$ordenId, $tipo, $funcionId, $mercanciaId, $cantidad, $precio, $detalles]);

        if ($tipo === 'mercancia' && $mercanciaId) {
            $pdo->prepare('UPDATE mercaderia SET stock = stock - ? WHERE id = ?')->execute([$cantidad, $mercanciaId]);
        }
    }

    $stmt = $pdo->prepare('INSERT INTO pagos (orden_id, monto, metodo, openpay_charge_id, openpay_order_id, estado) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$ordenId, $total, 'openpay', $openpayChargeId, $openpayOrderId, 'completado']);

    if ($guardarTarjeta && $tokenId !== '' && OPENPAY_MERCHANT_ID && OPENPAY_PRIVATE_KEY) {
        $stmt = $pdo->prepare('SELECT openpay_customer_id FROM tarjetas_guardadas WHERE usuario_id = ? LIMIT 1');
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $customerId = $row['openpay_customer_id'] ?? null;
        if (!$customerId) {
            $cust = openpay_create_customer($user['nombre'], $user['email']);
            if (!empty($cust['id'])) {
                $customerId = $cust['id'];
            }
        }
        if ($customerId) {
            $cardResult = openpay_save_card_for_customer($customerId, $tokenId);
            if (!empty($cardResult['id'])) {
                $ultimos4 = $cardResult['card_number'] ?? '';
                if (strlen($ultimos4) >= 4) {
                    $ultimos4 = substr($ultimos4, -4);
                }
                $marca = $cardResult['brand'] ?? null;
                $alias = $cardResult['holder_name'] ?? 'Tarjeta guardada';
                $stmt = $pdo->prepare('INSERT INTO tarjetas_guardadas (usuario_id, openpay_customer_id, openpay_card_id, ultimos_4, marca, alias) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$user['id'], $customerId, $cardResult['id'], $ultimos4, $marca, $alias]);
            }
        }
    }

    $_SESSION['carrito'] = [];
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['ok' => false, 'error' => 'Error al registrar la orden. Contacta soporte.']);
    exit;
}

echo json_encode(['ok' => true, 'orden_id' => $ordenId]);
