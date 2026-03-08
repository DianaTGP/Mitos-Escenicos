<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

$pdo = mitos_pdo();
$items = $pdo->query(
    'SELECT id, nombre, descripcion, imagen_url, precio, stock FROM mercaderia WHERE activo = 1 ORDER BY nombre'
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Tienda | Mitos Escénicos';
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="Tienda oficial Mitos Escénicos. Mercancía y productos exclusivos de la compañía de teatro."/>
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
    <span style="color:#fff;">Tienda</span>
  </nav>

  <div style="margin-bottom:2rem;">
    <p class="section-label">Oficial</p>
    <h1 style="font-family:var(--font-theatrical); font-size:2.2rem; font-weight:700; color:#fff; margin:0 0 0.4rem;">Tienda Mitos Escénicos</h1>
    <p style="color:var(--text-muted); font-size:0.95rem;">Lleva un pedazo del teatro a casa con mercancía exclusiva.</p>
  </div>

  <?php if (!empty($items)): ?>
  <div class="grid-3">
    <?php foreach ($items as $item): ?>
      <div class="product-card" style="display:flex; flex-direction:column; position:relative;">
        <a href="<?php echo htmlspecialchars(mitos_url('producto.php?id=' . (int)$item['id'])); ?>"
           style="display:block; overflow:hidden; position:relative;">
          <?php if (!empty($item['imagen_url'])): ?>
            <img src="<?php echo htmlspecialchars($item['imagen_url']); ?>"
                 alt="<?php echo htmlspecialchars($item['nombre']); ?>" loading="lazy"
                 style="width:100%; aspect-ratio:4/3; object-fit:cover; transition:transform 0.3s;"/>
          <?php else: ?>
            <div style="aspect-ratio:4/3; background:var(--surface-dark); display:flex; align-items:center; justify-content:center;">
              <span class="material-symbols-outlined" style="font-size:3.5rem; color:var(--text-muted);">inventory_2</span>
            </div>
          <?php endif; ?>
          <?php if ((int)$item['stock'] === 0): ?>
            <div style="position:absolute; top:0.75rem; right:0.75rem;">
              <span class="badge badge-maroon">Agotado</span>
            </div>
          <?php endif; ?>
        </a>
        <div class="product-info" style="display:flex; flex-direction:column; flex:1;">
          <a href="<?php echo htmlspecialchars(mitos_url('producto.php?id=' . (int)$item['id'])); ?>"
             style="color:inherit; text-decoration:none;">
            <h2 style="font-size:1rem; font-weight:700; color:#fff; margin:0 0 0.2rem;"><?php echo htmlspecialchars($item['nombre']); ?></h2>
          </a>
          <p style="font-size:1.1rem; font-weight:800; color:var(--primary); margin:0 0 1rem;">
            $<?php echo number_format((float)$item['precio'], 2); ?>
          </p>
          <?php if ((int)$item['stock'] > 0): ?>
            <button type="button" class="btn-primary btn-sm add-to-cart-mercancia" style="width:100%; margin-top:auto;"
                    data-id="<?php echo (int)$item['id']; ?>"
                    data-nombre="<?php echo htmlspecialchars($item['nombre']); ?>"
                    data-precio="<?php echo htmlspecialchars($item['precio']); ?>">
              <span class="material-symbols-outlined" style="font-size:0.95rem;">add_shopping_cart</span>
              Añadir al carrito
            </button>
          <?php else: ?>
            <button disabled class="btn-secondary btn-sm" style="width:100%; margin-top:auto; opacity:0.5; cursor:not-allowed;">Sin stock</button>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <div style="text-align:center; padding:4rem 1rem;">
      <span class="material-symbols-outlined" style="font-size:3rem; color:var(--text-muted); display:block; margin-bottom:1rem;">shopping_bag</span>
      <p style="color:var(--text-muted);">No hay productos disponibles en este momento.</p>
      <a href="<?php echo htmlspecialchars(mitos_url('index.php')); ?>" class="btn-secondary" style="margin-top:1.25rem;">Volver al inicio</a>
    </div>
  <?php endif; ?>

</main>

<?php mitos_footer(); ?>
<script src="<?php echo htmlspecialchars(mitos_url('js/carrito.js')); ?>" data-base-url="<?php echo htmlspecialchars(mitos_url('')); ?>"></script>
<script>
document.querySelectorAll('.add-to-cart-mercancia').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var id     = parseInt(this.getAttribute('data-id'), 10);
    var nombre = this.getAttribute('data-nombre');
    var precio = parseFloat(this.getAttribute('data-precio'));
    if (typeof mitosCarrito !== 'undefined') {
      mitosCarrito.agregarMercancia(id, nombre, precio, 1);
      // Feedback visual
      var orig = this.innerHTML;
      this.innerHTML = '<span class="material-symbols-outlined" style="font-size:0.95rem;">check</span> Agregado';
      this.disabled = true;
      setTimeout(() => { this.innerHTML = orig; this.disabled = false; }, 1800);
    }
  });
});
</script>
</body>
</html>
