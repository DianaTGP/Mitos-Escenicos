<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

mitos_requiere_login('confirmacion.php');

$ordenId = isset($_GET['orden_id']) ? (int) $_GET['orden_id'] : 0;
if ($ordenId <= 0) {
    header('Location: ' . mitos_url('mis-compras.php'));
    exit;
}

$user = mitos_usuario_actual();
$pdo = mitos_pdo();
$stmt = $pdo->prepare('SELECT id, total, estado, tipo, created_at FROM ordenes WHERE id = ? AND usuario_id = ?');
$stmt->execute([$ordenId, $user['id']]);
$orden = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$orden) {
    header('Location: ' . mitos_url('mis-compras.php'));
    exit;
}

$stmt = $pdo->prepare('SELECT tipo_item, cantidad, precio_unitario, detalles, funcion_id, mercancia_id FROM orden_items WHERE orden_id = ? ORDER BY id');
$stmt->execute([$ordenId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Compra realizada | Mitos Escénicos';
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700&amp;family=Forum&amp;display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&amp;display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
</head>
<body style="background-color: var(--background-dark); min-height: 100vh; display: flex; flex-direction: column;">
<?php mitos_header($pageTitle); ?>

<main class="container" style="padding-top: 2rem; padding-bottom: 3rem;">
  <div class="alert alert-success" style="margin-bottom: 2rem;">
    <strong>¡Gracias por tu compra!</strong> Tu pago se ha procesado correctamente.
  </div>
  <h1 style="font-size: 1.5rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem;">Orden #<?php echo (int)$orden['id']; ?></h1>
  <p style="color: var(--text-muted); margin-bottom: 1.5rem;"><?php echo date('d/m/Y H:i', strtotime($orden['created_at'])); ?> · Total: <strong style="color: var(--primary);">$<?php echo number_format((float)$orden['total'], 2); ?></strong></p>

  <div style="max-width: 600px;">
    <h2 style="font-size: 1.125rem; font-weight: 700; color: #fff; margin-bottom: 0.75rem;">Detalle</h2>
    <ul style="list-style: none; padding: 0; margin: 0;">
      <?php foreach ($items as $item): ?>
        <?php
        $det = $item['detalles'] ? json_decode($item['detalles'], true) : [];
        $nombre = $det['nombre'] ?? ($item['tipo_item'] === 'boleto' ? 'Boleto' : 'Producto');
        $lineTotal = (float)$item['precio_unitario'] * (int)$item['cantidad'];
        ?>
        <li style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-dark);">
          <span style="color: rgba(255,255,255,0.9);"><?php echo (int)$item['cantidad']; ?> × <?php echo htmlspecialchars($nombre); ?></span>
          <span style="font-weight: 700;">$<?php echo number_format($lineTotal, 2); ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <p style="margin-top: 2rem;">
    <a href="<?php echo htmlspecialchars(mitos_url('mis-compras.php')); ?>" class="btn-primary">Ver mis compras</a>
    <a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>" class="btn-secondary" style="margin-left: 0.5rem;">Seguir comprando</a>
  </p>
</main>

<?php mitos_footer(); ?>
</body>
</html>
