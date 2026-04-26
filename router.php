<?php
/**
 * Router personalizado para Google App Engine (PHP 8+)
 * En PHP 8+, App Engine desvía todo el tráfico a un único archivo (entrypoint).
 * Este script captura la petición y carga el archivo .php original requerido, emulando a Apache/XAMPP.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Si la ruta es la raíz, cargamos index.php
if ($path === '/' || $path === '' || $path === '/index.php') {
    require __DIR__ . '/index.php';
    exit;
}

// Construimos la ruta absoluta basada en la petición
$absolutePath = __DIR__ . $path;

// Si existe el archivo exacto y es PHP, lo ejecutamos
if (file_exists($absolutePath) && is_file($absolutePath)) {
    if (pathinfo($absolutePath, PATHINFO_EXTENSION) === 'php') {
        require $absolutePath;
        exit;
    }
    // Servir archivos estáticos (CSS, JS, imágenes, fonts, etc.)
    $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
    $mimes = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'webp'  => 'image/webp',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'eot'   => 'application/vnd.ms-fontobject',
        'map'   => 'application/json',
    ];
    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
        readfile($absolutePath);
        exit;
    }
    // Cualquier otro archivo estático conocido
    return false;
}

// Algunas veces App Engine u otros prefieren URLs amigables sin ".php" (/login en vez de /login.php)
$phpFileFallback = $absolutePath . '.php';
if (file_exists($phpFileFallback) && is_file($phpFileFallback)) {
    require $phpFileFallback;
    exit;
}

// Si la URL realmente no existe en nuestros archivos PHP, devolvemos 404
http_response_code(404);
echo "404 Not Found - La ruta solicitada no se encontró en este servidor.";
exit;
