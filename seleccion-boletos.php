<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

$funcion_id = isset($_GET['funcion_id']) ? (int) $_GET['funcion_id'] : 0;
if ($funcion_id <= 0) {
    header('Location: ' . mitos_url('cartelera.php'));
    exit;
}

$pdo = mitos_pdo();
$stmt = $pdo->prepare('
  SELECT f.id, f.obra_id, f.fecha_hora, f.precio_base, f.aforo, o.titulo AS obra_titulo, o.venta_boletos_habilitada, l.nombre AS lugar_nombre, l.direccion AS lugar_direccion
  FROM funciones f
  JOIN obras o ON o.id = f.obra_id
  JOIN lugares l ON l.id = f.lugar_id
  WHERE f.id = ? AND f.fecha_hora > NOW()
');
$stmt->execute([$funcion_id]);
$funcion = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$funcion || (int) $funcion['venta_boletos_habilitada'] !== 1) {
    header('Location: ' . mitos_url('cartelera.php'));
    exit;
}

$added = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cantidad = (int) ($_POST['cantidad'] ?? 1);
    if ($cantidad < 1) {
        $error = 'Cantidad debe ser al menos 1.';
    } elseif ($cantidad > (int) $funcion['aforo']) {
        $error = 'Cantidad no puede superar el aforo.';
    } else {
        $_SESSION['carrito'][] = [
            'tipo' => 'boleto',
            'funcion_id' => $funcion_id,
            'cantidad' => $cantidad,
            'precio_unitario' => (float) $funcion['precio_base'],
            'nombre' => $funcion['obra_titulo'] . ' - ' . $funcion['lugar_nombre'] . ' ' . date('d/m/Y H:i', strtotime($funcion['fecha_hora'])),
            'fecha_hora' => $funcion['fecha_hora'],
            'lugar_nombre' => $funcion['lugar_nombre'],
        ];
        $added = true;
    }
}

$pageTitle = 'Comprar boletos | ' . $funcion['obra_titulo'];
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
  <p style="margin-bottom: 1rem;"><a href="<?php echo htmlspecialchars(mitos_url('obra.php?id=' . (int)$funcion['obra_id'])); ?>" style="color: var(--primary);">← Volver a la obra</a></p>
  <h1 style="font-size: 1.75rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($funcion['obra_titulo']); ?></h1>
  <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
    <span class="material-symbols-outlined" style="vertical-align: middle; font-size: 1rem;">event</span>
    <?php echo date('l d \d\e F Y - H:i', strtotime($funcion['fecha_hora'])); ?> · <?php echo htmlspecialchars($funcion['lugar_nombre']); ?>
  </p>

  <?php if ($added): ?>
    <div class="alert alert-success">
      Boletos añadidos al carrito. <a href="<?php echo htmlspecialchars(mitos_url('carrito.php')); ?>">Ver carrito</a> o <a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>">seguir comprando</a>.
    </div>
  <?php else: ?>
    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <div style="max-width: 400px; padding: 1.5rem; background: rgba(255,255,255,0.03); border: 1px solid var(--border-dark); border-radius: 0.75rem;">
      <p style="font-size: 1.5rem; font-weight: 700; color: var(--primary); margin-bottom: 1rem;">$<?php echo number_format((float)$funcion['precio_base'], 2); ?> <span style="font-size: 0.875rem; font-weight: 400; color: var(--text-muted);">por boleto</span></p>
      <form method="post" action="">
        <label for="cantidad" style="display: block; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.5rem;">Cantidad de boletos</label>
        <input type="number" id="cantidad" name="cantidad" value="<?php echo (int)($_POST['cantidad'] ?? 1); ?>" min="1" max="<?php echo (int)$funcion['aforo']; ?>" style="width: 6rem; margin-bottom: 1rem;"/>
        <button type="submit" class="btn-primary" style="width: 100%; padding: 0.75rem;">Añadir al carrito</button>
      </form>
    </div>
  <?php endif; ?>
</main>

<?php mitos_footer(); ?>
</body>
</html>
