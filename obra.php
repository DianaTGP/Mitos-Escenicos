<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) { header('Location: ' . mitos_url('cartelera.php')); exit; }

$pdo = mitos_pdo();
$stmt = $pdo->prepare('SELECT id, titulo, descripcion, imagen_url, duracion_min, venta_boletos_habilitada FROM obras WHERE id = ?');
$stmt->execute([$id]);
$obra = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$obra) { header('Location: ' . mitos_url('cartelera.php')); exit; }

$ventaHabilitada = (int) $obra['venta_boletos_habilitada'] === 1;
$stmt = $pdo->prepare('
  SELECT f.id, f.fecha_hora, f.precio_base, f.aforo, l.nombre AS lugar_nombre, l.direccion AS lugar_direccion
  FROM funciones f
  JOIN lugares l ON l.id = f.lugar_id
  WHERE f.obra_id = ? AND f.fecha_hora > GETDATE()
  ORDER BY f.fecha_hora ASC
');
$stmt->execute([$id]);
$funciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtMedia = $pdo->prepare('SELECT id, tipo, ruta FROM obra_media WHERE obra_id = ? ORDER BY orden, id');
$stmtMedia->execute([$id]);
$galeria = $stmtMedia->fetchAll(PDO::FETCH_ASSOC);

// Obtener el elenco (artistas participantes de las funciones de esta obra)
$stmtElenco = $pdo->prepare('
  SELECT DISTINCT a.id, a.nombre, a.rol, a.foto_url, fa.rol_en_funcion, fa.personaje, a.orden
  FROM artistas a
  JOIN funcion_artistas fa ON a.id = fa.artista_id
  JOIN funciones f ON f.id = fa.funcion_id
  WHERE f.obra_id = ? AND a.activo = 1
  ORDER BY a.orden ASC
');
$stmtElenco->execute([$id]);
$elenco = $stmtElenco->fetchAll(PDO::FETCH_ASSOC);

$rolesLabel = [
    'actor' => 'Actor / Actriz',
    'director' => 'Director / Directora',
    'disenador' => 'Diseñador',
    'bailarin' => 'Bailarín'
];

$pageTitle = $obra['titulo'] . ' | Mitos Escénicos';
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="<?php echo htmlspecialchars(mb_substr($obra['descripcion'] ?? '', 0, 155)); ?>"/>
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;900&family=Forum&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
  <style>
    .funcion-row {
      display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;
      padding: 1rem 1.25rem;
      border: 1px solid var(--border-subtle);
      border-radius: 0.65rem;
      margin-bottom: 0.75rem;
      transition: border-color 0.2s, background 0.2s;
    }
    .funcion-row:hover { border-color: var(--primary); background: rgba(227,176,75,0.04); }
  </style>
</head>
<body>
<?php mitos_header($pageTitle); ?>

<main class="container" style="padding-top:2rem; padding-bottom:3rem;">

  <!-- Breadcrumb -->
  <nav style="font-size:0.8rem; color:var(--text-muted); margin-bottom:1.75rem; display:flex; align-items:center; gap:0.4rem; flex-wrap:wrap;">
    <a href="<?php echo htmlspecialchars(mitos_url('index.php')); ?>" style="color:var(--text-muted);">Inicio</a>
    <span class="material-symbols-outlined" style="font-size:0.85rem;">chevron_right</span>
    <a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>" style="color:var(--text-muted);">Cartelera</a>
    <span class="material-symbols-outlined" style="font-size:0.85rem;">chevron_right</span>
    <span style="color:#fff;"><?php echo htmlspecialchars($obra['titulo']); ?></span>
  </nav>

  <!-- Layout de dos columnas -->
  <div style="display:grid; grid-template-columns: minmax(0,2fr) minmax(0,3fr); gap:3rem; align-items:start;">
    <!-- Imagen -->
    <div>
      <?php if (!empty($obra['imagen_url'])): ?>
        <img src="<?php echo htmlspecialchars($obra['imagen_url']); ?>"
             alt="<?php echo htmlspecialchars($obra['titulo']); ?>"
             style="width:100%; border-radius:0.75rem; object-fit:cover; aspect-ratio:3/4; box-shadow:0 8px 32px rgba(0,0,0,0.5);"/>
      <?php else: ?>
        <div style="aspect-ratio:3/4; background:var(--surface-dark); border-radius:0.75rem; display:flex; align-items:center; justify-content:center;">
          <span class="material-symbols-outlined" style="font-size:5rem; color:var(--text-muted);">theaters</span>
        </div>
      <?php endif; ?>
    </div>

    <!-- Detalle -->
    <div>
      <h1 style="font-family:var(--font-theatrical); font-size:clamp(1.8rem,3vw,2.8rem); font-weight:900; color:#fff; margin:0 0 0.75rem; line-height:1.1;">
        <?php echo htmlspecialchars($obra['titulo']); ?>
      </h1>

      <?php if ((int)$obra['duracion_min'] > 0): ?>
        <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1rem; display:flex; align-items:center; gap:0.35rem;">
          <span class="material-symbols-outlined" style="font-size:1rem;">schedule</span>
          <?php echo (int)$obra['duracion_min']; ?> minutos
        </p>
      <?php endif; ?>

      <?php if (!$ventaHabilitada): ?>
        <div class="alert alert-info" style="margin-bottom:1.25rem;">
          <span class="material-symbols-outlined" style="font-size:1rem; vertical-align:middle;">info</span>
          La venta de boletos está temporalmente deshabilitada.
        </div>
      <?php endif; ?>

      <?php if (!empty($obra['descripcion'])): ?>
        <p style="color:rgba(255,255,255,0.80); line-height:1.75; margin-bottom:2rem; font-size:1rem;">
          <?php echo nl2br(htmlspecialchars($obra['descripcion'])); ?>
        </p>
      <?php endif; ?>

      <!-- Funciones -->
      <h2 style="font-size:1.1rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.1em; margin-bottom:1rem;">
        Funciones disponibles
      </h2>

      <?php if (empty($funciones)): ?>
        <p style="color:var(--text-muted); font-style:italic;">No hay funciones programadas próximamente.</p>
      <?php else: ?>
        <?php foreach ($funciones as $f): ?>
          <div class="funcion-row">
            <div>
              <p style="font-weight:700; color:#fff; margin:0; font-size:0.95rem;">
                <span class="material-symbols-outlined" style="font-size:0.95rem; vertical-align:middle; margin-right:0.2rem;">place</span>
                <?php echo htmlspecialchars($f['lugar_nombre']); ?>
              </p>
              <p style="font-size:0.85rem; color:var(--text-muted); margin:0.25rem 0 0; display:flex; align-items:center; gap:0.3rem;">
                <span class="material-symbols-outlined" style="font-size:0.85rem;">calendar_today</span>
                <?php echo date('d M Y, H:i', strtotime($f['fecha_hora'])); ?> h
              </p>
              <?php if (!empty($f['lugar_direccion'])): ?>
                <p style="font-size:0.78rem; color:var(--text-muted); margin:0.15rem 0 0;"><?php echo htmlspecialchars($f['lugar_direccion']); ?></p>
              <?php endif; ?>
            </div>
            <div style="display:flex; align-items:center; gap:1rem; flex-shrink:0;">
              <span style="font-weight:800; font-size:1.1rem; color:var(--gold);">
                $<?php echo number_format((float)$f['precio_base'], 2); ?>
              </span>
              <?php if ($ventaHabilitada): ?>
                <a href="<?php echo htmlspecialchars(mitos_url('seleccion-boletos.php?funcion_id=' . (int)$f['id'])); ?>"
                   class="btn-primary btn-sm">
                  <span class="material-symbols-outlined" style="font-size:0.95rem;">confirmation_number</span>
                  Comprar
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- Acciones -->
      <div style="margin-top:2rem; display:flex; flex-wrap:wrap; gap:1rem;">
        <a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>" class="btn-secondary">
          <span class="material-symbols-outlined" style="font-size:1rem;">arrow_back</span>
          Volver a cartelera
        </a>
        <a href="<?php echo htmlspecialchars(mitos_url('carrito.php')); ?>" class="btn-secondary">
          <span class="material-symbols-outlined" style="font-size:1rem;">shopping_bag</span>
          Ver carrito
        </a>
      </div>
    </div>
  </div>

  <?php if (!empty($elenco)): ?>
  <!-- Elenco -->
  <section style="margin-top:4rem; border-top:1px solid var(--border-subtle); padding-top:3rem;">
    <h2 style="font-size:1.4rem; font-weight:700; color:#fff; font-family:var(--font-theatrical); margin-bottom:2rem;">
      Elenco de la obra
    </h2>
    <div style="display:flex; gap:2.5rem; overflow-x:auto; padding-bottom:1rem; scroll-behavior:smooth;" class="no-scrollbar">
      <?php foreach ($elenco as $art): ?>
        <a href="<?php echo htmlspecialchars(mitos_url('artista.php?id=' . (int)$art['id'])); ?>" style="flex:none; display:flex; flex-direction:column; align-items:center; text-decoration:none; group:0; width:140px;">
          <div style="position:relative; width:120px; height:120px; border-radius:50%; padding:0.25rem; border:2px solid transparent; transition:transform 0.3s, border-color 0.3s;"
               onmouseover="this.style.transform='scale(1.1)'; this.style.borderColor='var(--gold)'"
               onmouseout="this.style.transform='scale(1)'; this.style.borderColor='transparent'">
            <img src="<?php echo htmlspecialchars(mitos_url($art['foto_url'] ?? '')); ?>" alt="<?php echo htmlspecialchars($art['nombre']); ?>" style="width:100%; height:100%; object-fit:cover; border-radius:50%; filter:grayscale(1); transition:filter 0.3s;"
                 onmouseover="this.style.filter='grayscale(0)'" onmouseout="this.style.filter='grayscale(1)'" />
            <div style="position:absolute; inset:0; border-radius:50%; background:rgba(227,176,75,0.1); pointer-events:none;"></div>
          </div>
          <p style="color:#fff; font-weight:700; margin:0.5rem 0 0.15rem; text-align:center; font-size:0.95rem; transition:color 0.2s;"
             onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='#fff'">
             <?php echo htmlspecialchars($art['nombre']); ?>
          </p>
          <span style="color:var(--text-muted); font-size:0.7rem; text-transform:uppercase; letter-spacing:0.05em; text-align:center;">
             <?php 
               if ($art['rol_en_funcion'] === 'actor' && !empty($art['personaje'])) {
                 echo htmlspecialchars($art['personaje']);
               } else {
                 echo htmlspecialchars(strtoupper($art['rol_en_funcion']));
               }
             ?>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
  <style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
  </style>
  <?php endif; ?>

  <?php if (!empty($galeria)): ?>
  <!-- Galería de fotos/videos -->
  <section style="margin-top:4rem; border-top:1px solid var(--border-subtle); padding-top:3rem;">
    <h2 style="font-size:1.4rem; font-weight:700; color:#fff; font-family:var(--font-theatrical); margin-bottom:2rem;">
      Galería de la obra
    </h2>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px,1fr)); gap:1.5rem;">
      <?php foreach ($galeria as $m): ?>
        <div style="aspect-ratio:4/3; border-radius:0.75rem; overflow:hidden; background:var(--surface-dark); border:1px solid var(--border-subtle); position:relative; group;">
          <?php if ($m['tipo'] === 'video'): ?>
            <video src="<?php echo htmlspecialchars(mitos_url($m['ruta'])); ?>" 
                   style="width:100%; height:100%; object-fit:cover;" 
                   controls preload="metadata"></video>
          <?php else: ?>
            <img src="<?php echo htmlspecialchars(mitos_url($m['ruta'])); ?>" 
                 alt="Galería <?php echo htmlspecialchars($obra['titulo']); ?>" 
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
