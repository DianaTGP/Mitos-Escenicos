<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . mitos_url('tienda.php'));
    exit;
}

$pdo = mitos_pdo();
$stmt = $pdo->prepare('SELECT id, nombre, descripcion, imagen_url, precio, stock FROM mercaderia WHERE id = ? AND activo = 1');
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) {
    header('Location: ' . mitos_url('tienda.php'));
    exit;
}

$stmtMedia = $pdo->prepare('SELECT id, tipo, ruta FROM mercaderia_media WHERE mercaderia_id = ? ORDER BY orden, id');
$stmtMedia->execute([$id]);
$galeria = $stmtMedia->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = $item['nombre'] . ' | Mitos Escénicos';
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700&amp;family=Forum&amp;display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
</head>
<body style="background-color: var(--background-dark); min-height: 100vh; display: flex; flex-direction: column;">
<?php mitos_header($pageTitle); ?>

<main class="container" style="padding-top: 2rem; padding-bottom: 3rem;">
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; max-width: 900px; align-items: start;">
    <div>
      <?php if (!empty($item['imagen_url'])): ?>
        <img src="<?php echo htmlspecialchars($item['imagen_url']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>" style="width: 100%; border-radius: 0.75rem;"/>
      <?php else: ?>
        <div style="aspect-ratio: 1; background: var(--border-dark); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center;"><span class="material-symbols-outlined" style="font-size: 4rem; color: var(--text-muted);">inventory_2</span></div>
      <?php endif; ?>
    </div>
    <div>
      <h1 style="font-size: 1.75rem; font-weight: 800; color: #fff; margin: 0 0 0.5rem;"><?php echo htmlspecialchars($item['nombre']); ?></h1>
      <p style="font-size: 1.25rem; font-weight: 700; color: var(--primary); margin-bottom: 1rem;">$<?php echo number_format((float)$item['precio'], 2); ?></p>
      <?php if (!empty($item['descripcion'])): ?>
        <p style="color: rgba(255,255,255,0.8); line-height: 1.6; margin-bottom: 1.5rem;"><?php echo nl2br(htmlspecialchars($item['descripcion'])); ?></p>
      <?php endif; ?>
      <?php if ((int)$item['stock'] > 0): ?>
        <form method="post" action="<?php echo htmlspecialchars(mitos_url('api/carrito-add.php')); ?>" style="display: flex; align-items: center; gap: 1rem;">
          <input type="hidden" name="tipo" value="mercancia"/>
          <input type="hidden" name="mercancia_id" value="<?php echo (int)$item['id']; ?>"/>
          <input type="hidden" name="precio" value="<?php echo htmlspecialchars($item['precio']); ?>"/>
          <label for="cantidad" style="color: var(--text-muted); font-size: 0.875rem;">Cantidad</label>
          <input type="number" id="cantidad" name="cantidad" value="1" min="1" max="<?php echo (int)$item['stock']; ?>" style="width: 5rem;"/>
          <button type="submit" class="btn-primary">Añadir al carrito</button>
        </form>
      <?php else: ?>
        <p style="color: var(--text-muted);">Producto agotado.</p>
      <?php endif; ?>
      <p style="margin-top: 1rem;"><a href="<?php echo htmlspecialchars(mitos_url('tienda.php')); ?>" style="color: var(--primary);">← Volver a la tienda</a></p>
    </div>
  </div>

  <?php if (!empty($galeria)): ?>
  <!-- Galería del producto -->
  <section style="margin-top:4rem; border-top:1px solid var(--border-subtle); padding-top:3rem;">
    <h2 style="font-size:1.4rem; font-weight:700; color:#fff; font-family:var(--font-theatrical); margin-bottom:2rem;">
      Galería del producto
    </h2>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px,1fr)); gap:1.5rem;">
      <?php foreach ($galeria as $m): ?>
        <div style="aspect-ratio:1; border-radius:0.75rem; overflow:hidden; background:var(--surface-dark); border:1px solid var(--border-subtle); position:relative; group;">
          <?php if ($m['tipo'] === 'video'): ?>
            <video src="<?php echo htmlspecialchars(mitos_url($m['ruta'])); ?>" 
                   style="width:100%; height:100%; object-fit:cover;" 
                   controls preload="metadata"></video>
          <?php else: ?>
            <img src="<?php echo htmlspecialchars(mitos_url($m['ruta'])); ?>" 
                 alt="Galería <?php echo htmlspecialchars($item['nombre']); ?>" 
                 style="width:100%; height:100%; object-fit:cover; transition:transform 0.5s;"
                 onmouseover="this.style.transform='scale(1.05)'"
                 onmouseout="this.style.transform='scale(1)'"/>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

</main>

<?php mitos_footer(); ?>
</body>
</html>
