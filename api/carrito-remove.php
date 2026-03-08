<?php
/**
 * Quitar ítem del carrito por índice.
 * POST: index (índice en el array del carrito)
 */
require_once dirname(__DIR__) . '/php/init.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$index = (int) ($_POST['index'] ?? $_GET['index'] ?? -1);
if ($index < 0 || !isset($_SESSION['carrito'][$index])) {
    echo json_encode(['ok' => false, 'error' => 'Índice inválido']);
    exit;
}

array_splice($_SESSION['carrito'], $index, 1);
echo json_encode(['ok' => true, 'carrito_count' => array_sum(array_column($_SESSION['carrito'], 'cantidad'))]);
