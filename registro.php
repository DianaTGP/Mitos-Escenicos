<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

if (mitos_esta_logueado()) { header('Location: ' . mitos_url('index.php')); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim((string) ($_POST['nombre'] ?? ''));
    $email    = trim((string) ($_POST['email'] ?? ''));
    $telefono = trim((string) ($_POST['telefono'] ?? ''));
    $password  = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password2'] ?? '');

    if ($nombre === '' || $email === '' || $password === '') {
        $error = 'Nombre, correo y contraseña son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $password2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $pdo  = mitos_pdo();
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Ya existe una cuenta con ese correo electrónico.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO usuarios (email, password_hash, nombre, telefono, rol) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$email, $hash, $nombre, $telefono === '' ? null : $telefono, 'usuario']);
            mitos_login($email, $password);
            header('Location: ' . mitos_url('index.php'));
            exit;
        }
    }
}

$pageTitle = 'Registro | Mitos Escénicos';
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

<main style="min-height:calc(100vh - 180px); display:flex; align-items:center; justify-content:center; padding:2rem 1rem;">
  <div style="position:fixed; inset:0; pointer-events:none; z-index:0;
              background:radial-gradient(ellipse 60% 50% at 50% 0%, rgba(149,12,19,0.15), transparent);"></div>

  <div style="position:relative; z-index:1; width:100%; max-width:460px;">
    <div class="surface-card" style="padding:2.5rem;">
      <div style="text-align:center; margin-bottom:2rem;">
        <?php if (file_exists(__DIR__ . '/media/logos/logo-vertical.png')): ?>
            <img src="<?php echo htmlspecialchars(mitos_url('media/logos/logo-vertical.png')); ?>" alt="Mitos Escénicos" style="max-height:80px; width:auto; margin-bottom:1rem;">
        <?php else: ?>
            <span class="material-symbols-outlined" style="font-size:2.5rem; color:var(--primary); display:block; margin-bottom:0.75rem;">person_add</span>
        <?php endif; ?>
        <h1 style="font-family:var(--font-theatrical); font-size:2rem; color:#fff; margin:0 0 0.4rem;">Únete al elenco</h1>
        <p style="color:var(--text-muted); font-size:0.9rem;">Crea tu cuenta para comprar boletos y mercancía</p>
      </div>

      <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="post" action="" style="display:flex; flex-direction:column; gap:1.1rem;">
        <div>
          <label for="nombre" style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); margin-bottom:0.4rem;">Nombre completo *</label>
          <input type="text" id="nombre" name="nombre" placeholder="Tu nombre" required
                 value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" autocomplete="name"/>
        </div>
        <div>
          <label for="email" style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); margin-bottom:0.4rem;">Correo electrónico *</label>
          <input type="email" id="email" name="email" placeholder="tu@correo.com" required
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" autocomplete="email"/>
        </div>
        <div>
          <label for="telefono" style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted); margin-bottom:0.4rem;">Teléfono (opcional)</label>
          <input type="tel" id="telefono" name="telefono" placeholder="55 1234 5678"
                 value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>" autocomplete="tel"/>
        </div>
        <div>
          <label for="password" style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); margin-bottom:0.4rem;">Contraseña * (mín. 8 caracteres)</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required minlength="8" autocomplete="new-password"/>
        </div>
        <div>
          <label for="password2" style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); margin-bottom:0.4rem;">Repetir contraseña *</label>
          <input type="password" id="password2" name="password2" placeholder="••••••••" required minlength="8" autocomplete="new-password"/>
        </div>
        <p style="font-size:0.75rem; color:var(--text-muted); line-height:1.5;">
          Al registrarte aceptas nuestros
          <a href="<?php echo htmlspecialchars(mitos_url('terminos-condiciones-privacidad.php')); ?>" style="color:var(--text-muted); text-decoration:underline;">Términos y Privacidad</a>.
        </p>
        <button type="submit" class="btn-primary" style="width:100%; padding:0.9rem; margin-top:0.25rem;">
          Crear cuenta
          <span class="material-symbols-outlined" style="font-size:1.1rem;">arrow_forward</span>
        </button>
      </form>

      <hr class="divider" style="margin:1.75rem 0;">
      <p style="text-align:center; color:var(--text-muted); font-size:0.88rem;">
        ¿Ya tienes cuenta?
        <a href="<?php echo htmlspecialchars(mitos_url('login.php')); ?>" style="color:var(--primary); font-weight:700;">Inicia sesión</a>
      </p>
      <p style="text-align:center; margin-top:0.75rem;">
        <a href="<?php echo htmlspecialchars(mitos_url('index.php')); ?>" style="color:var(--text-muted); font-size:0.82rem; display:inline-flex; align-items:center; gap:0.3rem;">
          <span class="material-symbols-outlined" style="font-size:0.9rem;">arrow_back</span> Volver al inicio
        </a>
      </p>
    </div>
  </div>
</main>

<?php mitos_footer(); ?>
</body>
</html>
