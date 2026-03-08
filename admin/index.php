<?php
require_once __DIR__ . '/../php/init.php';
require_once __DIR__ . '/../php/layout.php';
mitos_requiere_admin();

$user = mitos_usuario_actual();
$pdo  = mitos_pdo();

// Stats
try {
    $totalObras      = (int) $pdo->query('SELECT COUNT(*) FROM obras')->fetchColumn();
    $totalMercaderia = (int) $pdo->query('SELECT COUNT(*) FROM mercaderia WHERE activo = 1')->fetchColumn();
    $totalUsuarios   = (int) $pdo->query('SELECT COUNT(*) FROM usuarios WHERE activo = 1')->fetchColumn();
    $totalOrdenes    = (int) $pdo->query("SELECT COUNT(*) FROM ordenes WHERE estado = 'pagado'")->fetchColumn();
} catch (PDOException $e) {
    $totalObras = $totalMercaderia = $totalUsuarios = $totalOrdenes = 0;
}

$pageTitle = 'Panel de Administración | Mitos Escénicos';
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
<body style="background:var(--background-dark);">

<div class="admin-layout">
  <?php mitos_admin_sidebar('dashboard'); ?>

  <main class="admin-content" style="padding:2rem;">

    <!-- Título -->
    <div style="margin-bottom:2rem;">
      <h1 style="font-size:1.8rem; font-weight:800; color:#fff; margin:0 0 0.3rem;">Dashboard</h1>
      <p style="color:var(--text-muted); font-size:0.9rem;">Gestiona obras, funciones, lugares y mercancía.</p>
    </div>

    <!-- Stats -->
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:1rem; margin-bottom:2.5rem;">
      <div class="stat-card">
        <div class="stat-card-icon gold"><span class="material-symbols-outlined">theaters</span></div>
        <p class="label">Obras</p>
        <p class="value"><?php echo $totalObras; ?></p>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon primary"><span class="material-symbols-outlined">inventory_2</span></div>
        <p class="label">Productos activos</p>
        <p class="value"><?php echo $totalMercaderia; ?></p>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon maroon"><span class="material-symbols-outlined">group</span></div>
        <p class="label">Usuarios</p>
        <p class="value"><?php echo $totalUsuarios; ?></p>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon gold"><span class="material-symbols-outlined">payments</span></div>
        <p class="label">Órdenes pagadas</p>
        <p class="value"><?php echo $totalOrdenes; ?></p>
      </div>
    </div>

    <!-- Accesos rápidos -->
    <h2 style="font-size:1rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.1em; margin-bottom:1rem;">Accesos rápidos</h2>
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:1rem;">
      <?php
      $modules = [
          ['url'=>'admin/obras.php',      'icon'=>'theaters',     'title'=>'Obras',       'desc'=>'Agregar, editar y eliminar obras'],
          ['url'=>'admin/funciones.php',  'icon'=>'event',        'title'=>'Funciones',   'desc'=>'Fechas, lugares y precios'],
          ['url'=>'admin/lugares.php',    'icon'=>'place',        'title'=>'Lugares',     'desc'=>'Sedes y teatros'],
          ['url'=>'admin/mercaderia.php', 'icon'=>'inventory_2',  'title'=>'Mercancía',   'desc'=>'Tienda y stock'],
      ];
      foreach ($modules as $m): ?>
        <a href="<?php echo htmlspecialchars(mitos_url($m['url'])); ?>"
           style="display:block; padding:1.5rem; background:var(--surface-card); border:1px solid var(--border-subtle); border-radius:0.75rem; text-decoration:none; color:#fff; transition:border-color 0.2s, background 0.2s;"
           onmouseover="this.style.borderColor='var(--gold)'; this.style.background='rgba(227,176,75,0.05)';"
           onmouseout="this.style.borderColor=''; this.style.background='var(--surface-card)';">
          <span class="material-symbols-outlined" style="font-size:2rem; color:var(--gold); display:block; margin-bottom:0.75rem;"><?php echo $m['icon']; ?></span>
          <h3 style="font-size:1rem; font-weight:700; margin:0 0 0.25rem;"><?php echo $m['title']; ?></h3>
          <p style="font-size:0.8rem; color:var(--text-muted); margin:0;"><?php echo $m['desc']; ?></p>
        </a>
      <?php endforeach; ?>
    </div>

  </main>
</div>

</body>
</html>
