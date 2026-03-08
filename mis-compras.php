<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

mitos_requiere_login('mis-compras.php');

$user = mitos_usuario_actual();
$pdo = mitos_pdo();
$stmt = $pdo->prepare('SELECT id, total, estado, tipo, created_at FROM ordenes WHERE usuario_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Mis compras | Mitos Escénicos';
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
  <style>
    .order-card { padding:1rem; border:1px solid var(--border-subtle); border-radius:0.75rem; margin-bottom:1rem; background:var(--surface-card); }
    .order-card:hover { border-color:var(--primary); }
  </style>
</head>
<body>
<?php mitos_header($pageTitle); ?>

<main class="container" style="padding-top: 2rem; padding-bottom: 3rem;">
  <h1 style="font-size: 1.75rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem;">Mis compras</h1>
  <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Historial de órdenes y boletos.</p>

  <?php if (empty($ordenes)): ?>
    <p style="color: var(--text-muted);">Aún no tienes compras.</p>
    <a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>" class="btn-primary" style="display: inline-block; margin-top: 1rem;">Ver cartelera</a>
  <?php else: ?>
    <?php foreach ($ordenes as $orden): ?>
      <a href="<?php echo htmlspecialchars(mitos_url('confirmacion.php?orden_id=' . (int)$orden['id'])); ?>" class="order-card" style="display: block; text-decoration: none; color: inherit;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
          <div>
            <strong style="color: #fff;">Orden #<?php echo (int)$orden['id']; ?></strong>
            <span style="color: var(--text-muted); font-size: 0.875rem; margin-left: 0.5rem;"><?php echo date('d/m/Y H:i', strtotime($orden['created_at'])); ?></span>
          </div>
          <div>
            <span style="font-weight: 700; color: var(--primary);">$<?php echo number_format((float)$orden['total'], 2); ?></span>
            <span style="font-size: 0.75rem; color: var(--text-muted); margin-left: 0.5rem;"><?php echo htmlspecialchars($orden['estado']); ?></span>
          </div>
        </div>
        <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0.25rem 0 0;"><?php echo htmlspecialchars($orden['tipo']); ?></p>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</main>

<?php mitos_footer(); ?>
</body>
</html>
