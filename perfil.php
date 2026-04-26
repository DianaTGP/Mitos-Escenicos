<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

mitos_requiere_login('perfil.php');

$user = mitos_usuario_actual();
$pdo = mitos_pdo();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $telefono = trim((string) ($_POST['telefono'] ?? ''));
    $direccion = trim((string) ($_POST['direccion'] ?? ''));
    if ($nombre !== '') {
        // Manejar subida de imagen de perfil si existe
        $foto_perfil_url = null;
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['foto_perfil']['tmp_name'];
            $fileName = basename($_FILES['foto_perfil']['name']);
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $uniqueName = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
                $targetPath = __DIR__ . '/media/' . $uniqueName;
                if (!is_dir(__DIR__ . '/media')) {
                    mkdir(__DIR__ . '/media', 0755, true);
                }
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $foto_perfil_url = 'media/' . $uniqueName;
                }
            } else {
                $error = 'Formato de imagen no válido. Usa JPG, PNG o WEBP.';
            }
        }

        if ($foto_perfil_url) {
            $stmt = $pdo->prepare('UPDATE usuarios SET nombre = ?, telefono = ?, direccion = ?, foto_perfil = ? WHERE id = ?');
            $stmt->execute([$nombre, $telefono ?: null, $direccion ?: null, $foto_perfil_url, $user['id']]);
        } else {
            $stmt = $pdo->prepare('UPDATE usuarios SET nombre = ?, telefono = ?, direccion = ? WHERE id = ?');
            $stmt->execute([$nombre, $telefono ?: null, $direccion ?: null, $user['id']]);
        }
        $_SESSION['usuario_nombre'] = $nombre;
        $success = 'Perfil actualizado correctamente.';
    } elseif (!isset($_POST['cambiar_password'])) {
        $error = 'El nombre es obligatorio.';
    }
    $cambiarPass = trim((string) ($_POST['cambiar_password'] ?? ''));
    if ($cambiarPass === '1') {
        $passActual = (string) ($_POST['password_actual'] ?? '');
        $passNueva = (string) ($_POST['password_nueva'] ?? '');
        $passNueva2 = (string) ($_POST['password_nueva2'] ?? '');
        if (strlen($passNueva) < 8) {
            $error = ($error ? $error . ' ' : '') . 'La nueva contraseña debe tener al menos 8 caracteres.';
        } elseif ($passNueva !== $passNueva2) {
            $error = ($error ? $error . ' ' : '') . 'Las contraseñas nuevas no coinciden.';
        } else {
            $stmt = $pdo->prepare('SELECT password_hash FROM usuarios WHERE id = ?');
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && password_verify($passActual, $row['password_hash'])) {
                $hash = password_hash($passNueva, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')->execute([$hash, $user['id']]);
                $success = $success ? $success . ' Contraseña actualizada.' : 'Contraseña actualizada.';
            } else {
                $error = ($error ? $error . ' ' : '') . 'Contraseña actual incorrecta.';
            }
        }
    }
}

$stmt = $pdo->prepare('SELECT nombre, email, telefono, direccion, foto_perfil FROM usuarios WHERE id = ?');
$stmt->execute([$user['id']]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Mi perfil | Mitos Escénicos';
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
<?php mitos_header($pageTitle); ?>

<main class="container" style="padding-top:2rem; padding-bottom:3rem;">

  <!-- Breadcrumb -->
  <nav style="font-size:0.8rem; color:var(--text-muted); margin-bottom:1.5rem; display:flex; align-items:center; gap:0.4rem;">
    <a href="<?php echo htmlspecialchars(mitos_url('index.php')); ?>" style="color:var(--text-muted);">Inicio</a>
    <span class="material-symbols-outlined" style="font-size:0.85rem;">chevron_right</span>
    <span style="color:#fff;">Mi perfil</span>
  </nav>

  <!-- Quick links -->
  <div style="display:flex; flex-wrap:wrap; gap:0.75rem; margin-bottom:2rem;">
    <a href="<?php echo htmlspecialchars(mitos_url('mis-boletos.php')); ?>" class="btn-secondary btn-sm">
      <span class="material-symbols-outlined" style="font-size:1rem;">confirmation_number</span> Mis boletos
    </a>
    <a href="<?php echo htmlspecialchars(mitos_url('mis-compras.php')); ?>" class="btn-secondary btn-sm">
      <span class="material-symbols-outlined" style="font-size:1rem;">receipt_long</span> Mis compras
    </a>
    <a href="<?php echo htmlspecialchars(mitos_url('mis-talleres.php')); ?>" class="btn-secondary btn-sm">
      <span class="material-symbols-outlined" style="font-size:1rem;">school</span> Mis talleres
    </a>
    <a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>" class="btn-secondary btn-sm">
      <span class="material-symbols-outlined" style="font-size:1rem;">theaters</span> Cartelera
    </a>
    <a href="<?php echo htmlspecialchars(mitos_url('logout.php')); ?>" class="btn-secondary btn-sm" style="color:#f87171; border-color:rgba(239,68,68,0.3);">
      <span class="material-symbols-outlined" style="font-size:1rem;">logout</span> Cerrar sesión
    </a>
  </div>

  <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:2rem; align-items:start;">
  <div style="max-width:480px;">
  <!-- Avatar display estilo red social -->
  <div style="display:flex; align-items:center; gap:1.5rem; margin-bottom:1.5rem;">
      <div style="width:100px; height:100px; border-radius:50%; overflow:hidden; border:3px solid var(--gold); background:rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
          <?php if (!empty($datos['foto_perfil'])): ?>
              <img src="<?php echo htmlspecialchars($datos['foto_perfil']); ?>" alt="Tu foto de perfil" style="width:100%; height:100%; object-fit:cover;"/>
          <?php else: ?>
              <span class="material-symbols-outlined" style="font-size:3rem; color:var(--text-muted);">person</span>
          <?php endif; ?>
      </div>
      <div>
          <h1 style="font-size:1.75rem; font-weight:800; color:#fff; margin-bottom:0.25rem;">Mi perfil</h1>
          <p style="color:var(--text-muted); margin:0;">Bienvenido, <?php echo htmlspecialchars($datos['nombre'] ?? ''); ?>.</p>
      </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>

  <form method="post" action="" enctype="multipart/form-data" style="max-width: 480px; margin-top:2rem;">
    <div style="margin-bottom: 1rem;">
      <label style="display: block; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">Cambiar foto de perfil (JPG, PNG)</label>
      <input type="file" name="foto_perfil" accept="image/*" style="width:100%; padding:0.5rem; background:rgba(255,255,255,0.05); color:#fff; border-radius:0.4rem;"/>
    </div>
    <div style="margin-bottom: 1rem;">
      <label style="display: block; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">Nombre completo *</label>
      <input type="text" name="nombre" required value="<?php echo htmlspecialchars($datos['nombre'] ?? ''); ?>"/>
    </div>
    <div style="margin-bottom: 1rem;">
      <label style="display: block; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">Correo electrónico</label>
      <input type="email" value="<?php echo htmlspecialchars($datos['email'] ?? ''); ?>" disabled style="opacity: 0.7;"/>
      <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">No se puede cambiar el correo.</p>
    </div>
    <div style="margin-bottom: 1rem;">
      <label style="display: block; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">Teléfono</label>
      <input type="text" name="telefono" value="<?php echo htmlspecialchars($datos['telefono'] ?? ''); ?>"/>
    </div>
    <div style="margin-bottom: 1.5rem;">
      <label style="display: block; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">Dirección (para envíos)</label>
      <input type="text" name="direccion" value="<?php echo htmlspecialchars($datos['direccion'] ?? ''); ?>"/>
    </div>
    <button type="submit" class="btn-primary">Guardar cambios</button>
  </form>
  </div><!-- end left column -->

  <div style="max-width:480px;">
  <div class="surface-card">
    <h2 style="font-size: 1.125rem; font-weight: 700; color: #fff; margin-bottom: 0.75rem;">Cambiar contraseña</h2>
    <form method="post" action="">
      <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($datos['nombre'] ?? ''); ?>"/>
      <input type="hidden" name="telefono" value="<?php echo htmlspecialchars($datos['telefono'] ?? ''); ?>"/>
      <input type="hidden" name="direccion" value="<?php echo htmlspecialchars($datos['direccion'] ?? ''); ?>"/>
      <input type="hidden" name="cambiar_password" value="1"/>
      <div style="margin-bottom: 0.75rem;">
        <label style="display: block; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">Contraseña actual</label>
        <input type="password" name="password_actual" required/>
      </div>
      <div style="margin-bottom: 0.75rem;">
        <label style="display: block; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">Nueva contraseña (mín. 8 caracteres)</label>
        <input type="password" name="password_nueva" minlength="8"/>
      </div>
      <div style="margin-bottom: 1rem;">
        <label style="display: block; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">Repetir nueva contraseña</label>
        <input type="password" name="password_nueva2" minlength="8"/>
      </div>
      <button type="submit" class="btn-secondary">Cambiar contraseña</button>
    </form>
  </div></div>
  </div><!-- grid end -->

</main>

<?php mitos_footer(); ?>
</body>
</html>
