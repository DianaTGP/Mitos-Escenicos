<?php
/**
 * Helpers de autenticación y sesión.
 */

declare(strict_types=1);

if (!defined('MITOS_APP')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

// Iniciar sesión de forma segura si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Obtiene el usuario actual desde la sesión (array con id, email, nombre, rol).
 */
function mitos_usuario_actual(): ?array
{
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }
    return [
        'id'     => (int) $_SESSION['usuario_id'],
        'email'  => (string) ($_SESSION['usuario_email'] ?? ''),
        'nombre' => (string) ($_SESSION['usuario_nombre'] ?? ''),
        'rol'    => (string) ($_SESSION['usuario_rol'] ?? 'usuario'),
    ];
}

/**
 * Comprueba si hay un usuario logueado.
 */
function mitos_esta_logueado(): bool
{
    return mitos_usuario_actual() !== null;
}

/**
 * Comprueba si el usuario actual es administrador.
 */
function mitos_es_admin(): bool
{
    $u = mitos_usuario_actual();
    return $u !== null && $u['rol'] === 'admin';
}

/**
 * Exige que haya un usuario logueado. Si no, redirige a login y termina.
 */
function mitos_requiere_login(string $redirectTo = ''): void
{
    if (mitos_esta_logueado()) {
        return;
    }
    $url = mitos_url('login.php');
    if ($redirectTo !== '') {
        $url .= '?redirect=' . rawurlencode($redirectTo);
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Exige que el usuario sea administrador. Si no, redirige a index y termina.
 */
function mitos_requiere_admin(): void
{
    if (!mitos_esta_logueado()) {
        header('Location: ' . mitos_url('login.php'));
        exit;
    }
    if (!mitos_es_admin()) {
        header('Location: ' . mitos_url('index.php'));
        exit;
    }
}

/**
 * Login: valida credenciales y establece sesión. Devuelve true si OK, false si fallo.
 */
function mitos_login(string $email, string $password): bool
{
    $pdo = mitos_pdo();
    $stmt = $pdo->prepare('SELECT TOP 1 id, email, nombre, password_hash, rol FROM usuarios WHERE email = ? AND activo = 1');
    $stmt->execute([trim($email)]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['usuario_id']    = (int) $row['id'];
    $_SESSION['usuario_email'] = $row['email'];
    $_SESSION['usuario_nombre'] = $row['nombre'];
    $_SESSION['usuario_rol']   = $row['rol'];
    return true;
}

/**
 * Cerrar sesión.
 */
function mitos_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}
