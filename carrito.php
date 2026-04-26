<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

$carrito = $_SESSION['carrito'] ?? [];
$subtotal = 0;
foreach ($carrito as $item) {
    $subtotal += (float) $item['precio_unitario'] * (int) $item['cantidad'];
}
$envio = 0;
$tieneMercancia = false;
foreach ($carrito as $item) {
    if (isset($item['tipo']) && $item['tipo'] === 'mercancia') {
        $tieneMercancia = true;
        break;
    }
}
if ($tieneMercancia && $subtotal > 0) {
    $envio = 12.50;
}
$impuestos = $subtotal > 0 ? round($subtotal * 0.075, 2) : 0;
$total = $subtotal + $envio + $impuestos;

$pageTitle = 'Carrito | Mitos Escénicos';
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
    .cart-item { display:flex; flex-wrap:wrap; gap:1rem; padding:1.5rem; background:rgba(255,255,255,0.03); border:1px solid var(--border-subtle); border-radius:0.75rem; margin-bottom:1rem; }
    .cart-item:hover { border-color:rgba(227,176,75,0.3); }
    .cart-item .thumb { width:120px; height:120px; border-radius:0.5rem; object-fit:cover; flex-shrink:0; }
    .cart-item .placeholder-thumb { width:120px; height:120px; border-radius:0.5rem; background:var(--surface-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .cart-item .qty-control { display:flex; align-items:center; gap:0.5rem; }
    .cart-item .qty-control input { width:3rem; text-align:center; background:var(--surface-dark); color:#fff; border:1px solid var(--border-dark); border-radius:0.25rem; height:2rem; }
  </style>
</head>
<body>
<?php mitos_header($pageTitle); ?>

<main class="container" style="padding-top:2rem; padding-bottom:3rem;">
  <!-- Breadcrumb -->
  <nav style="font-size:0.8rem; color:var(--text-muted); margin-bottom:1.5rem; display:flex; align-items:center; gap:0.4rem;">
    <a href="<?php echo htmlspecialchars(mitos_url('index.php')); ?>" style="color:var(--text-muted);">Inicio</a>
    <span class="material-symbols-outlined" style="font-size:0.85rem;">chevron_right</span>
    <span style="color:#fff;">Carrito</span>
  </nav>
  <div style="margin-bottom:2rem;">
    <h1 style="font-size:2rem; font-weight:800; color:#fff; margin:0 0 0.25rem;">Tu carrito</h1>
    <p style="color:var(--text-muted);">Revisa tus ítems antes de continuar al pago.</p>
  </div>

  <?php if (empty($carrito)): ?>
    <div style="text-align:center; padding:4rem 1rem;">
      <span class="material-symbols-outlined" style="font-size:3rem; color:var(--text-muted); display:block; margin-bottom:1rem;">shopping_bag</span>
      <p style="color:var(--text-muted); margin-bottom:1.5rem;">Tu carrito está vacío.</p>
      <div style="display:flex; justify-content:center; gap:1rem; flex-wrap:wrap;">
        <a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>" class="btn-primary">Ver cartelera</a>
        <a href="<?php echo htmlspecialchars(mitos_url('tienda.php')); ?>" class="btn-secondary">Ir a la tienda</a>
      </div>
    </div>
  <?php else: ?>
  <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
    <div style="flex: 1;">
      <?php foreach ($carrito as $idx => $item): ?>
        <?php
        $nombre = $item['nombre'] ?? 'Ítem';
        $precio = (float) ($item['precio_unitario'] ?? 0);
        $cantidad = (int) ($item['cantidad'] ?? 1);
        $lineTotal = $precio * $cantidad;
        $tipo = $item['tipo'] ?? '';
        ?>
        <div class="cart-item" data-index="<?php echo (int)$idx; ?>">
          <div class="placeholder-thumb">
            <span class="material-symbols-outlined" style="font-size: 2rem; color: var(--text-muted);"><?php echo $tipo === 'boleto' ? 'confirmation_number' : 'inventory_2'; ?></span>
          </div>
          <div style="flex: 1; min-width: 0;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem;">
              <div>
                <span style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--primary); font-weight: 700;"><?php echo $tipo === 'boleto' ? 'Entradas para función' : 'Merchandising'; ?></span>
                <h2 style="font-size: 1.125rem; font-weight: 700; color: #fff; margin: 0.25rem 0;"><?php echo htmlspecialchars($nombre); ?></h2>
              </div>
              <button type="button" class="cart-remove" data-index="<?php echo (int)$idx; ?>" style="background: none; border: none; color: rgba(255,255,255,0.4); cursor: pointer; padding: 0.25rem;" title="Quitar"><span class="material-symbols-outlined">delete</span></button>
            </div>
            <?php if (!empty($item['fecha_hora'])): ?>
              <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0.25rem 0;"><span class="material-symbols-outlined" style="font-size: 0.875rem; vertical-align: middle;">event</span> <?php echo date('d/m/Y H:i', strtotime($item['fecha_hora'])); ?></p>
            <?php endif; ?>
            <div style="margin-top: 0.75rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
              <?php if ($tipo === 'mercancia'): ?>
                <div class="qty-control">
                  <button type="button" class="cart-qty-minus" data-index="<?php echo (int)$idx; ?>" style="background: var(--border-dark); border: none; color: #fff; width: 2rem; height: 2rem; border-radius: 0.25rem; cursor: pointer;">−</button>
                  <input type="number" class="cart-qty-input" data-index="<?php echo (int)$idx; ?>" value="<?php echo $cantidad; ?>" min="1" style="width: 3rem;"/>
                  <button type="button" class="cart-qty-plus" data-index="<?php echo (int)$idx; ?>" style="background: var(--border-dark); border: none; color: #fff; width: 2rem; height: 2rem; border-radius: 0.25rem; cursor: pointer;">+</button>
                </div>
              <?php else: ?>
                <span style="font-size: 0.875rem; color: var(--text-muted);">Cantidad: <?php echo $cantidad; ?></span>
              <?php endif; ?>
              <span style="font-weight: 700; color: #fff;">$<?php echo number_format($lineTotal, 2); ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <aside style="max-width: 380px;">
      <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-dark); border-radius: 0.75rem; padding: 1.5rem; position: sticky; top: 1rem;">
        <h3 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin: 0 0 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-dark);">Resumen</h3>
        <div style="display: flex; justify-content: space-between; color: var(--text-muted); margin-bottom: 0.5rem;"><span>Subtotal</span><span>$<?php echo number_format($subtotal, 2); ?></span></div>
        <?php if ($envio > 0): ?>
          <div style="display: flex; justify-content: space-between; color: var(--text-muted); margin-bottom: 0.5rem;"><span>Envío</span><span>$<?php echo number_format($envio, 2); ?></span></div>
        <?php endif; ?>
        <div style="display: flex; justify-content: space-between; color: var(--text-muted); margin-bottom: 0.5rem;"><span>Impuestos</span><span>$<?php echo number_format($impuestos, 2); ?></span></div>
        <div style="padding-top: 1rem; margin-top: 1rem; border-top: 1px solid var(--border-dark); display: flex; justify-content: space-between; align-items: baseline;">
          <span style="font-size: 1.125rem; font-weight: 700; color: #fff;">Total</span>
          <span style="font-size: 1.5rem; font-weight: 800; color: var(--primary);">$<?php echo number_format($total, 2); ?></span>
        </div>
        <div style="margin-top: 1rem;">
          <?php if (mitos_esta_logueado()): ?>
            <a href="<?php echo htmlspecialchars(mitos_url('pago.php')); ?>" class="btn-primary" style="display: block; text-align: center; padding: 1rem;">Continuar al pago</a>
          <?php else: ?>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.75rem;">Debes iniciar sesión para pagar.</p>
            <a href="<?php echo htmlspecialchars(mitos_url('login.php?redirect=' . rawurlencode('carrito.php'))); ?>" class="btn-primary" style="display: block; text-align: center; padding: 1rem;">Iniciar sesión</a>
          <?php endif; ?>
          <a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>" class="btn-secondary" style="display: block; text-align: center; padding: 0.75rem; margin-top: 0.5rem;">Seguir explorando</a>
        </div>
      </div>
    </aside>
  </div>
  <?php endif; ?>
</main>

<?php mitos_footer(); ?>
<script src="<?php echo htmlspecialchars(mitos_url('js/carrito.js')); ?>" data-base-url="<?php echo htmlspecialchars(mitos_url('')); ?>"></script>
<script>
(function() {
  var base = (document.querySelector('script[data-base-url]') && document.querySelector('script[data-base-url]').getAttribute('data-base-url')) || '';
  var api = function(path) { return (base ? base.replace(/\/$/, '') + '/' : '') + path; };
  document.querySelectorAll('.cart-remove').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var idx = this.getAttribute('data-index');
      var form = new FormData();
      form.append('index', idx);
      fetch(api('api/carrito-remove.php'), { method: 'POST', body: form }).then(function(r) { return r.json(); }).then(function() { location.reload(); });
    });
  });
  document.querySelectorAll('.cart-qty-minus').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var idx = this.getAttribute('data-index');
      var inp = document.querySelector('.cart-qty-input[data-index="' + idx + '"]');
      var v = Math.max(1, (parseInt(inp.value, 10) || 1) - 1);
      inp.value = v;
      var form = new FormData();
      form.append('index', idx);
      form.append('cantidad', v);
      fetch(api('api/carrito-update.php'), { method: 'POST', body: form }).then(function(r) { return r.json(); }).then(function() { location.reload(); });
    });
  });
  document.querySelectorAll('.cart-qty-plus').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var idx = this.getAttribute('data-index');
      var inp = document.querySelector('.cart-qty-input[data-index="' + idx + '"]');
      var v = (parseInt(inp.value, 10) || 1) + 1;
      inp.value = v;
      var form = new FormData();
      form.append('index', idx);
      form.append('cantidad', v);
      fetch(api('api/carrito-update.php'), { method: 'POST', body: form }).then(function(r) { return r.json(); }).then(function() { location.reload(); });
    });
  });
  document.querySelectorAll('.cart-qty-input').forEach(function(inp) {
    inp.addEventListener('change', function() {
      var idx = this.getAttribute('data-index');
      var v = Math.max(1, parseInt(this.value, 10) || 1);
      this.value = v;
      var form = new FormData();
      form.append('index', idx);
      form.append('cantidad', v);
      fetch(api('api/carrito-update.php'), { method: 'POST', body: form }).then(function(r) { return r.json(); }).then(function() { location.reload(); });
    });
  });
})();
</script>
</body>
</html>
