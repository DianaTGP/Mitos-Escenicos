<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) { header('Location: ' . mitos_url('sobre-nosotros.php')); exit; }

$pdo = mitos_pdo();
$stmt = $pdo->prepare('SELECT id, nombre, rol, especialidad, biografia, trayectoria, foto_url, portada_url FROM artistas WHERE id = ? AND activo = 1');
$stmt->execute([$id]);
$artista = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$artista) { header('Location: ' . mitos_url('sobre-nosotros.php')); exit; }

// Obtener las obras en las que participa (como actor o director)
$stmtObras = $pdo->prepare('
    SELECT DISTINCT o.id, o.titulo, o.imagen_url, o.descripcion 
    FROM obras o
    JOIN funciones f ON o.id = f.obra_id
    JOIN funcion_artistas fa ON f.id = fa.funcion_id
    WHERE fa.artista_id = ?
    ORDER BY o.created_at DESC
');
$stmtObras->execute([$id]);
$obras = $stmtObras->fetchAll(PDO::FETCH_ASSOC);

$stmtMedia = $pdo->prepare('SELECT id, tipo, ruta FROM artista_media WHERE artista_id = ? ORDER BY orden, id');
$stmtMedia->execute([$id]);
$galeria = $stmtMedia->fetchAll(PDO::FETCH_ASSOC);

$rolesLabel = [
    'actor' => 'Actor / Actriz',
    'director' => 'Director / Directora',
    'disenador' => 'Diseñador / Diseñadora',
    'bailarin' => 'Bailarín / Bailarina'
];
$rolTexto = $rolesLabel[$artista['rol']] ?? strtoupper($artista['rol']);

$pageTitle = $artista['nombre'] . ' | Mitos Escénicos';
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;900&family=Forum&family=Elsie+Swash+Caps:wght@400;900&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
  <style>
    .font-elsie { font-family: 'Elsie Swash Caps', serif; }
    .font-forum { font-family: 'Forum', serif; }
    
    .hero-artist {
      position: relative;
      min-height: 80vh;
      display: flex;
      align-items: center;
      overflow: hidden;
      background-color: var(--background-dark);
    }
    .hero-artist-bg {
      position: absolute;
      right: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-size: cover;
      background-position: center top;
      z-index: 1;
    }
    .hero-artist-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(to right, rgba(0,0,0,1) 10%, rgba(0,0,0,0.8) 50%, rgba(0,0,0,0.2) 100%);
      z-index: 2;
    }
    @media (min-width: 768px) {
      .hero-artist-bg { width: 65%; }
      .hero-artist-overlay { background: linear-gradient(to right, rgba(0,0,0,1) 40%, rgba(0,0,0,0.8) 60%, transparent 100%); }
    }
    .hero-artist-content {
      position: relative;
      z-index: 3;
      padding: 4rem 1.5rem;
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .specialty-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: rgba(227,176,75,0.1);
      border: 1px solid rgba(227,176,75,0.2);
      color: var(--gold);
      padding: 0.5rem 1rem;
      border-radius: 2rem;
      font-size: 0.85rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }
  </style>
</head>
<body style="background-color:var(--background-dark);">
<?php mitos_header($pageTitle); ?>

<main>
  <!-- Hero Section -->
  <section class="hero-artist">
    <?php 
    $bgImage = !empty($artista['portada_url']) ? mitos_url($artista['portada_url']) : ''; 
    ?>
    <div class="hero-artist-bg" style="<?php if($bgImage) echo 'background-image: url('.htmlspecialchars($bgImage).');'; else echo 'background: #111;'; ?>"></div>
    <div class="hero-artist-overlay"></div>
    
    <div class="hero-artist-content">
      <div style="display:flex; flex-direction:column; gap:1.5rem; max-width:650px;">
        
        <div style="display:flex; align-items:center; gap:1.5rem;">
          <?php if (!empty($artista['foto_url'])): ?>
            <img src="<?php echo htmlspecialchars(mitos_url($artista['foto_url'])); ?>" alt="Foto" style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid var(--gold); flex-shrink:0;"/>
          <?php endif; ?>
          <div>
            <span style="display:inline-block; color:var(--gold); font-weight:700; letter-spacing:0.2em; text-transform:uppercase; margin-bottom:0.5rem; font-size:0.85rem;">
              <?php echo htmlspecialchars($rolTexto); ?>
            </span>
            <h1 class="font-elsie" style="font-size:clamp(2.5rem, 6vw, 4.5rem); color:#fff; margin:0; line-height:1.1;">
              <?php echo htmlspecialchars($artista['nombre']); ?>
            </h1>
          </div>
        </div>
        
        <?php if (!empty($artista['especialidad'])): ?>
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
          <?php 
            $especialidades = array_map('trim', explode(',', $artista['especialidad']));
            foreach ($especialidades as $esp): 
              if (empty($esp)) continue;
          ?>
            <span class="specialty-badge">
              <span class="material-symbols-outlined" style="font-size:1.1rem;">auto_awesome</span>
              <?php echo htmlspecialchars($esp); ?>
            </span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($artista['biografia'])): ?>
        <div class="font-forum" style="color:rgba(255,255,255,0.8); font-size:1.2rem; line-height:1.8;">
          <?php echo nl2br(htmlspecialchars($artista['biografia'])); ?>
        </div>
        <?php endif; ?>

        <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-top:1rem;">
          <a href="<?php echo htmlspecialchars(mitos_url('sobre-nosotros.php')); ?>" class="btn-secondary">
            <span class="material-symbols-outlined" style="font-size:1.1rem;">groups</span>
            Ver todo el equipo
          </a>
        </div>
      </div>
    </div>
  </section>

  <?php if (!empty($artista['trayectoria'])): ?>
  <!-- Trayectoria -->
  <section class="container" style="padding-top:4.5rem;">
    <div style="display:flex; align-items:center; gap:1rem; margin-bottom:2.5rem;">
      <h2 class="font-elsie" style="font-size:2.5rem; color:var(--gold); margin:0;">Trayectoria del Artista</h2>
      <div style="flex-grow:1; height:1px; background:rgba(227,176,75,0.2);"></div>
    </div>
    <div class="font-forum" style="color:rgba(255,255,255,0.8); font-size:1.2rem; line-height:1.8; white-space:pre-line;">
      <?php echo htmlspecialchars($artista['trayectoria']); ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Obras en las que participa -->
  <section class="container" style="padding-top:4.5rem; padding-bottom:4.5rem;">
    <div style="display:flex; align-items:center; gap:1rem; margin-bottom:2.5rem;">
      <h2 class="font-elsie" style="font-size:2.5rem; color:var(--gold); margin:0;">Trayectoria Destacada</h2>
      <div style="flex-grow:1; height:1px; background:rgba(227,176,75,0.2);"></div>
    </div>

    <?php if (empty($obras)): ?>
      <p style="color:var(--text-muted); font-style:italic;">Actualmente no tiene funciones programadas.</p>
    <?php else: ?>
      <div class="grid-3">
        <?php foreach ($obras as $obra): ?>
          <a href="<?php echo htmlspecialchars(mitos_url('obra.php?id=' . (int)$obra['id'])); ?>" class="poster-card">
            <div class="poster-img-wrap">
              <?php if (!empty($obra['imagen_url'])): ?>
                <img src="<?php echo htmlspecialchars($obra['imagen_url']); ?>"
                     alt="<?php echo htmlspecialchars($obra['titulo']); ?>" loading="lazy"/>
              <?php else: ?>
                <div style="width:100%; aspect-ratio:3/4; background:var(--surface-dark); display:flex; align-items:center; justify-content:center;">
                  <span class="material-symbols-outlined" style="font-size:3.5rem; color:var(--text-muted);">theaters</span>
                </div>
              <?php endif; ?>
              <div class="poster-overlay"></div>
            </div>
            <div class="poster-info">
              <h3><?php echo htmlspecialchars($obra['titulo']); ?></h3>
              <p class="poster-price">Ver detalles de la obra</p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <?php if (!empty($galeria)): ?>
  <!-- Galería de fotos/videos -->
  <section class="container" style="padding-bottom:5rem;">
    <div style="display:flex; align-items:center; gap:1rem; margin-bottom:2.5rem;">
      <h2 class="font-elsie" style="font-size:2.5rem; color:var(--gold); margin:0;">Galería</h2>
      <div style="flex-grow:1; height:1px; background:rgba(227,176,75,0.2);"></div>
    </div>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:1.5rem;">
      <?php foreach ($galeria as $m): ?>
        <div style="aspect-ratio:4/3; border-radius:0.5rem; overflow:hidden; border:1px solid rgba(255,255,255,0.1); background:#000;">
          <?php if ($m['tipo'] === 'imagen'): ?>
            <img src="<?php echo htmlspecialchars(mitos_url($m['ruta'])); ?>" alt="Galería" loading="lazy" style="width:100%; height:100%; object-fit:cover; transition:transform 0.5s;" 
                 onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'"/>
          <?php else: ?>
            <video src="<?php echo htmlspecialchars(mitos_url($m['ruta'])); ?>" controls style="width:100%; height:100%; object-fit:cover;"></video>
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
