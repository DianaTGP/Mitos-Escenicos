<?php
/**
 * Inicialización común: config + auth.
 * Desde raíz: require_once __DIR__ . '/php/init.php';
 * Desde admin/: require_once __DIR__ . '/../php/init.php';
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once __DIR__ . '/auth.php';

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}
