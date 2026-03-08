<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

if (mitos_esta_logueado()) {
    header('Location: ' . (mitos_es_admin() ? mitos_url('admin/index.php') : mitos_url('index.php')));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if ($email === '' || $password === '') {
        $error = 'Por favor ingresa tu correo y contraseña.';
    } elseif (mitos_login($email, $password)) {
        $redirect = $_GET['redirect'] ?? '';
        if ($redirect !== '' && strpos($redirect, '//') === false) {
            header('Location: ' . mitos_url($redirect));
        } elseif (mitos_es_admin()) {
            header('Location: ' . mitos_url('admin/index.php'));
        } else {
            header('Location: ' . mitos_url('index.php'));
        }
        exit;
    } else {
        $error = 'Correo o contraseña incorrectos. Verifica tus datos e intenta de nuevo.';
    }
}

$pageTitle = 'Iniciar sesión | Mitos Escénicos';
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;900&family=Forum&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
</head>
<body>
<?php mitos_header($pageTitle, false); ?>

<main style="min-height: calc(100vh - 180px); display:flex; align-items:center; justify-content:center; padding:2rem 1rem;">
  <!-- Background glow -->
  <div style="position:fixed; inset:0; pointer-events:none; z-index:0;
              background: radial-gradient(ellipse 60% 50% at 50% 0%, rgba(149,12,19,0.18), transparent);"></div>

  <div style="position:relative; z-index:1; width:100%; max-width:420px;">

    <!-- Card -->
    <div class="surface-card" style="text-align:center; padding:2.5rem;">

      <div style="margin-bottom:2rem;">
        <span class="material-symbols-outlined" style="font-size:2.5rem; color:var(--primary); display:block; margin-bottom:0.75rem;">theater_comedy</span>
        <h1 style="font-family:var(--font-theatrical); font-size:2rem; color:#fff; margin:0 0 0.4rem;">Bienvenido</h1>
        <p style="color:var(--text-muted); font-size:0.9rem;">Ingresa a la magia del teatro</p>
      </div>

      <?php if ($error !== ''): ?>
        <div class="alert alert-error" style="text-align:left;"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="post" action="" style="display:flex; flex-direction:column; gap:1.25rem; text-align:left;">
        <div>
          <label for="email" style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); margin-bottom:0.4rem;">
            Correo electrónico
          </label>
          <input type="email" id="email" name="email" placeholder="tu@correo.com" required
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                 autocomplete="email"/>
        </div>
        <div>
          <label for="password" style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); margin-bottom:0.4rem;">
            Contraseña
          </label>
          <input type="password" id="password" name="password" placeholder="••••••••" required
                 autocomplete="current-password"/>
        </div>
        <button type="submit" class="btn-primary" style="width:100%; padding:0.9rem; margin-top:0.25rem;">
          Iniciar sesión
          <span class="material-symbols-outlined" style="font-size:1.1rem;">arrow_forward</span>
        </button>
      </form>

      <hr class="divider" style="margin:1.75rem 0;">

      <p style="color:var(--text-muted); font-size:0.88rem;">
        ¿No tienes cuenta?
        <a href="<?php echo htmlspecialchars(mitos_url('registro.php')); ?>" style="color:var(--primary); font-weight:700;">
          Regístrate aquí
        </a>
      </p>
      <p style="margin-top:0.75rem;">
        <a href="<?php echo htmlspecialchars(mitos_url('index.php')); ?>" style="color:var(--text-muted); font-size:0.82rem; display:inline-flex; align-items:center; gap:0.3rem;">
          <span class="material-symbols-outlined" style="font-size:0.9rem;">arrow_back</span>
          Volver al inicio
        </a>
      </p>

    </div>

    <!-- Términos pequeños -->
    <p style="text-align:center; font-size:0.72rem; color:var(--text-muted); margin-top:1.25rem; line-height:1.6;">
      Al iniciar sesión aceptas nuestros
      <a href="<?php echo htmlspecialchars(mitos_url('terminos-condiciones-privacidad.php')); ?>" style="color:var(--text-muted); text-decoration:underline;">Términos y Privacidad</a>.
    </p>
  </div>
</main>

<?php mitos_footer(); ?>
</body>
</html>
