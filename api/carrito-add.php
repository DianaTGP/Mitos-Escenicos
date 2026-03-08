<?php
/**
 * Añadir ítem al carrito (sesión).
 * POST: tipo=boleto|mercancia, funcion_id (boleto), mercancia_id (mercancia), cantidad, precio
 * Para boleto: valida que la obra tenga venta_boletos_habilitada.
 */
require_once dirname(__DIR__) . '/php/init.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$tipo = trim((string) ($_POST['tipo'] ?? $_GET['tipo'] ?? ''));
$cantidad = (int) ($_POST['cantidad'] ?? $_GET['cantidad'] ?? 1);
if ($cantidad < 1) {
    $cantidad = 1;
}

if ($tipo === 'boleto') {
    $funcion_id = (int) ($_POST['funcion_id'] ?? $_GET['funcion_id'] ?? 0);
    $precio = (float) ($_POST['precio'] ?? $_GET['precio'] ?? 0);
    if ($funcion_id <= 0 || $precio <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Datos de función inválidos']);
        exit;
    }
    $pdo = mitos_pdo();
    $stmt = $pdo->prepare('SELECT f.id, f.precio_base, f.fecha_hora, f.aforo, o.titulo AS obra_titulo, o.venta_boletos_habilitada, l.nombre AS lugar_nombre FROM funciones f JOIN obras o ON o.id = f.obra_id JOIN lugares l ON l.id = f.lugar_id WHERE f.id = ?');
    $stmt->execute([$funcion_id]);
    $f = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$f) {
        echo json_encode(['ok' => false, 'error' => 'Función no encontrada']);
        exit;
    }
    if ((int) $f['venta_boletos_habilitada'] !== 1) {
        echo json_encode(['ok' => false, 'error' => 'La venta de boletos para esta obra está deshabilitada']);
        exit;
    }
    $nombre = $f['obra_titulo'] . ' - ' . $f['lugar_nombre'] . ' ' . date('d/m/Y H:i', strtotime($f['fecha_hora']));
    $precio = (float) $f['precio_base'];
    $item = [
        'tipo' => 'boleto',
        'funcion_id' => $funcion_id,
        'cantidad' => $cantidad,
        'precio_unitario' => $precio,
        'nombre' => $nombre,
        'fecha_hora' => $f['fecha_hora'],
        'lugar_nombre' => $f['lugar_nombre'],
    ];
} elseif ($tipo === 'mercancia') {
    $mercancia_id = (int) ($_POST['mercancia_id'] ?? $_GET['mercancia_id'] ?? 0);
    $precio = (float) ($_POST['precio'] ?? $_GET['precio'] ?? 0);
    if ($mercancia_id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Producto inválido']);
        exit;
    }
    $pdo = mitos_pdo();
    $stmt = $pdo->prepare('SELECT id, nombre, precio, stock FROM mercaderia WHERE id = ? AND activo = 1');
    $stmt->execute([$mercancia_id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$m || (int) $m['stock'] < 1) {
        echo json_encode(['ok' => false, 'error' => 'Producto no disponible']);
        exit;
    }
    $precio = (float) $m['precio'];
    $cantidad = min($cantidad, (int) $m['stock']);
    $item = [
        'tipo' => 'mercancia',
        'mercancia_id' => $mercancia_id,
        'cantidad' => $cantidad,
        'precio_unitario' => $precio,
        'nombre' => $m['nombre'],
    ];
} else {
    echo json_encode(['ok' => false, 'error' => 'Tipo de ítem inválido']);
    exit;
}

// Merge con ítem existente si mismo funcion_id o mercancia_id
$key = $tipo === 'boleto' ? 'funcion_id' : 'mercancia_id';
$id_val = $item[$key];
$found = false;
foreach ($_SESSION['carrito'] as $i => $existing) {
    if (isset($existing[$key]) && (int) $existing[$key] === (int) $id_val) {
        $_SESSION['carrito'][$i]['cantidad'] = (int) $existing['cantidad'] + $item['cantidad'];
        if ($tipo === 'mercancia') {
            $_SESSION['carrito'][$i]['cantidad'] = min($_SESSION['carrito'][$i]['cantidad'], (int) $m['stock']);
        }
        $found = true;
        break;
    }
}
if (!$found) {
    $_SESSION['carrito'][] = $item;
}

$redirect = empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest';
if ($redirect) {
    header('Location: ' . mitos_url('carrito.php'));
    exit;
}
echo json_encode(['ok' => true, 'carrito_count' => array_sum(array_column($_SESSION['carrito'], 'cantidad'))]);
