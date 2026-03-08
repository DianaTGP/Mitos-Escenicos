<?php
/**
 * Configuración de la aplicación Mitos Escénicos.
 * No subir credenciales reales a repositorio; usar .env o variables de entorno en producción.
 */

declare(strict_types=1);

// Evitar acceso directo
if (!defined('MITOS_APP')) {
    define('MITOS_APP', true);
}

// Raíz del proyecto (directorio padre de config/)
$GLOBALS['MITOS_ROOT'] = dirname(__DIR__);

// Base URL (ajustar según instalación; en XAMPP si la app está en htdocs/mitos usar '/mitos')
$GLOBALS['MITOS_BASE_URL'] = getenv('MITOS_BASE_URL') ?: '/mitos';

// Base de datos MySQL (Cloud SQL)
define('DB_HOST', getenv('DB_HOST') ?: 'AQUI_LA_IP_PUBLICA_DE_CLOUD_SQL'); // IP Pública de la instancia
define('DB_NAME', getenv('DB_NAME') ?: 'mitos_escenicos');
define('DB_USER', getenv('DB_USER') ?: 'AQUI_TU_USUARIO'); // Usualmente 'root' o el usuario que creaste
define('DB_PASS', getenv('DB_PASS') ?: 'AQUI_TU_CONTRASEÑA');
define('DB_CHARSET', 'utf8mb4');

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
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES    => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
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
