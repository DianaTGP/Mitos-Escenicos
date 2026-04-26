<?php
/**
 * Configuración de la aplicación Mitos Escénicos.
 * No subir credenciales reales a repositorio; usar .env o variables de entorno en producción.
 */

declare(strict_types = 1)
;

// Evitar acceso directo
if (!defined('MITOS_APP')) {
    define('MITOS_APP', true);
}

// Raíz del proyecto (directorio padre de config/)
$GLOBALS['MITOS_ROOT'] = dirname(__DIR__);

// Base URL (ajustar según instalación; en XAMPP si la app está en htdocs/mitos usar '/mitos')
$GLOBALS['MITOS_BASE_URL'] = getenv('MITOS_BASE_URL') ?: '';

// Base de datos SQL Server Express
define('DB_HOST', getenv('DB_HOST') ?: '.\SQLEXPRESS'); // Servidor local
define('DB_NAME', getenv('DB_NAME') ?: 'mitos_escenicos');
define('DB_USER', getenv('DB_USER') ?: ''); // Si se usa Auth de Windows se puede dejar vacío
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'UTF-8');

// Openpay (México) - usar modo sandbox en desarrollo
define('OPENPAY_MERCHANT_ID', getenv('OPENPAY_MERCHANT_ID') ?: '');
define('OPENPAY_PRIVATE_KEY', getenv('OPENPAY_PRIVATE_KEY') ?: '');
define('OPENPAY_PUBLIC_KEY', getenv('OPENPAY_PUBLIC_KEY') ?: '');
define('OPENPAY_SANDBOX', filter_var(getenv('OPENPAY_SANDBOX') ?: 'true', FILTER_VALIDATE_BOOLEAN));

// Conexión PDO (singleton)
function mitos_pdo(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        // Conexión para SQL Server usando pdo_sqlsrv
        // CharacterSet en UTF-8 es importante para T-SQL
        $dsn = 'sqlsrv:Server=' . DB_HOST . ';Database=' . DB_NAME;
        
        $opts = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        
        try {
            // Intentar conectar (si no hay pass, usará Windows Auth en SQLEXPRESS)
            if (empty(DB_USER)) {
                $pdo = new PDO($dsn, null, null, $opts);
            } else {
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
            }
        } catch (PDOException $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Helper ruta absoluta
function mitos_path(string $path): string
{
    $root = $GLOBALS['MITOS_ROOT'];
    $path = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    return $root . DIRECTORY_SEPARATOR . $path;
}

// Helper URL base
function mitos_url(string $path = ''): string
{
    $base = rtrim($GLOBALS['MITOS_BASE_URL'], '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base : $base . '/' . $path;
}
