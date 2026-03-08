<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

mitos_requiere_login('mis-boletos.php');

$user = mitos_usuario_actual();
$pdo = mitos_pdo();
$stmt = $pdo->prepare('
  SELECT oi.id, oi.orden_id, oi.cantidad, oi.precio_unitario, oi.detalles, o.created_at,
         f.fecha_hora, f.precio_base, o2.titulo AS obra_titulo, l.nombre AS lugar_nombre
  FROM orden_items oi
  JOIN ordenes o ON o.id = oi.orden_id
  LEFT JOIN funciones f ON f.id = oi.funcion_id
  LEFT JOIN obras o2 ON o2.id = f.obra_id
  LEFT JOIN lugares l ON l.id = f.lugar_id
  WHERE o.usuario_id = ? AND oi.tipo_item = ? AND o.estado = ?
  ORDER BY o.created_at DESC, f.fecha_hora ASC
');
$stmt->execute([$user['id'], 'boleto', 'pagado']);
$boletos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Mis boletos | Mitos Escénicos';
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
    .ticket-card { padding:1.25rem; border:1px solid var(--border-subtle); border-radius:0.75rem; margin-bottom:1rem; background:var(--surface-card); }
    .ticket-card:hover { border-color:var(--primary); }
  </style>
</head>
<body>
<?php mitos_header($pageTitle); ?>

<main class="container" style="padding-top: 2rem; padding-bottom: 3rem;">
  <h1 style="font-size: 1.75rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem;">Mis boletos</h1>
  <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Boletos comprados para funciones.</p>

  <?php if (empty($boletos)): ?>
    <p style="color: var(--text-muted);">No tienes boletos comprados.</p>
    <a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>" class="btn-primary" style="display: inline-block; margin-top: 1rem;">Ver cartelera</a>
  <?php else: ?>
    <?php foreach ($boletos as $b): ?>
      <div class="ticket-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem;">
          <div>
            <h2 style="font-size: 1.125rem; font-weight: 700; color: #fff; margin: 0 0 0.25rem;"><?php echo htmlspecialchars($b['obra_titulo'] ?? 'Función'); ?></h2>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0.25rem 0;"><span class="material-symbols-outlined" style="font-size: 0.875rem; vertical-align: middle;">event</span> <?php echo $b['fecha_hora'] ? date('l d/m/Y H:i', strtotime($b['fecha_hora'])) : '-'; ?></p>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0.25rem 0;"><span class="material-symbols-outlined" style="font-size: 0.875rem; vertical-align: middle;">place</span> <?php echo htmlspecialchars($b['lugar_nombre'] ?? '-'); ?></p>
          </div>
          <div style="text-align: right;">
            <p style="font-weight: 700; color: var(--primary); margin: 0;"><?php echo (int)$b['cantidad']; ?> boleto(s)</p>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0.25rem 0;">Orden #<?php echo (int)$b['orden_id']; ?></p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
  <p style="margin-top: 1rem;"><a href="<?php echo htmlspecialchars(mitos_url('mis-compras.php')); ?>" style="color: var(--primary);">Ver todas mis compras</a></p>
</main>

<?php mitos_footer(); ?>
</body>
</html>
