<?php
/**
 * Actualizar cantidad de un ítem en el carrito.
 * POST: index, cantidad (para mercancia validar stock)
 */
require_once dirname(__DIR__) . '/php/init.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$index = (int) ($_POST['index'] ?? -1);
$cantidad = (int) ($_POST['cantidad'] ?? 0);
if ($index < 0 || !isset($_SESSION['carrito'][$index])) {
    echo json_encode(['ok' => false, 'error' => 'Índice inválido']);
    exit;
}
if ($cantidad < 1) {
    echo json_encode(['ok' => false, 'error' => 'Cantidad debe ser al menos 1']);
    exit;
}

$item = &$_SESSION['carrito'][$index];
if ($item['tipo'] === 'mercancia' && isset($item['mercancia_id'])) {
    $pdo = mitos_pdo();
    $stmt = $pdo->prepare('SELECT stock FROM mercaderia WHERE id = ?');
    $stmt->execute([$item['mercancia_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $cantidad = min($cantidad, (int) $row['stock']);
    }
}
$item['cantidad'] = $cantidad;
echo json_encode(['ok' => true, 'carrito_count' => array_sum(array_column($_SESSION['carrito'], 'cantidad'))]);
