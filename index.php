<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

$pdo = mitos_pdo();
$obras = $pdo->query(
    'SELECT TOP 6 id, titulo, descripcion, imagen_url, duracion_min, temporada, venta_boletos_habilitada
     FROM obras
     ORDER BY created_at DESC'
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Mitos Escénicos | Teatro';
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="Mitos Escénicos – compañía de teatro. Boletos, obras en cartelera, talleres y tienda oficial."/>
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;900&family=Forum&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
</head>
<body>
<?php mitos_header($pageTitle); ?>

<main style="padding-bottom:4rem;">

  <!-- HERO -->
  <section style="padding:1.5rem 1.5rem 0;">
    <div class="container" style="padding:0;">
      <div class="hero-section">
        <div class="hero-content">
          <span class="hero-eyebrow">Compañía Teatral</span>
          <?php if (file_exists(__DIR__ . '/media/logos/logo-redyellow.png')): ?>
              <img src="<?php echo htmlspecialchars(mitos_url('media/logos/logo-redyellow.png')); ?>" alt="Mitos Escénicos" style="max-height:100px; width:auto; margin-bottom:1.5rem; filter:drop-shadow(0 4px 6px rgba(0,0,0,0.5));">
          <?php endif; ?>
          <h1>Vive la magia<br>del teatro</h1>
          <p>Disfruta de nuestras obras en vivo. Boletos, talleres formativos y mercancía oficial de Mitos Escénicos.</p>
          <div class="hero-actions">
            <a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>" class="btn-primary">
              <span class="material-symbols-outlined" style="font-size:1.1rem;">theaters</span>
              Ver cartelera
            </a>
            <a href="<?php echo htmlspecialchars(mitos_url('talleres.php')); ?>" class="btn-secondary">
              Talleres formativos
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- EN CARTELERA -->
  <section class="container" style="margin-top:3.5rem;">
    <div class="section-header">
      <div>
        <p class="section-label">Temporada</p>
        <h2>En cartelera</h2>
      </div>
      <a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>" class="section-see-all">
        Ver todas <span class="material-symbols-outlined" style="font-size:0.95rem;">arrow_forward</span>
      </a>
    </div>

    <?php if (!empty($obras)): ?>
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
            <?php if (!empty($obra['venta_boletos_habilitada'])): ?>
              <div class="poster-quick-btn">
                <span class="btn-gold btn-sm" style="width:100%; display:block; text-align:center;">Reserva rápida</span>
              </div>
            <?php endif; ?>
          </div>
          <div class="poster-info">
            <h3><?php echo htmlspecialchars($obra['titulo']); ?></h3>
            <?php if (!empty($obra['temporada'])): ?>
              <p class="poster-meta">
                <span class="material-symbols-outlined" style="font-size:0.85rem; vertical-align:middle;">calendar_today</span>
                <?php echo htmlspecialchars($obra['temporada']); ?>
              </p>
            <?php elseif ((int)$obra['duracion_min'] > 0): ?>
              <p class="poster-meta"><?php echo (int)$obra['duracion_min']; ?> min</p>
            <?php endif; ?>
            <p class="poster-price">
              <?php echo $obra['venta_boletos_habilitada'] ? 'Ver función y boletos' : 'Información'; ?>
            </p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <p style="color:var(--text-muted); text-align:center; padding:3rem 0; font-style:italic;">
        Próximamente más obras en cartelera.
        <a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>" style="color:var(--primary);">Visita la cartelera</a>.
      </p>
    <?php endif; ?>
  </section>

  <!-- CTA TIENDA + TALLERES -->
  <section class="container" style="margin-top:4rem;">
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px,1fr)); gap:1.5rem;">
      <!-- Tienda -->
      <a href="<?php echo htmlspecialchars(mitos_url('tienda.php')); ?>"
         style="display:block; padding:2rem; border-radius:0.75rem; text-decoration:none;
                background:linear-gradient(135deg, rgba(149,12,19,0.6) 0%, rgba(30,10,5,0.95) 100%);
                border:1px solid rgba(149,12,19,0.4); transition:box-shadow 0.25s;"
         onmouseover="this.style.boxShadow='0 0 30px rgba(149,12,19,0.3)'"
         onmouseout="this.style.boxShadow=''">
        <span class="material-symbols-outlined" style="font-size:2.5rem; color:var(--primary); display:block; margin-bottom:1rem;">shopping_bag</span>
        <h3 style="font-family:var(--font-theatrical); font-size:1.6rem; color:#fff; margin:0 0 0.5rem;">Tienda oficial</h3>
        <p style="color:rgba(255,255,255,0.6); margin:0 0 1.25rem; font-size:0.9rem;">Mercancía exclusiva de Mitos Escénicos.</p>
        <span style="color:var(--primary); font-weight:700; font-size:0.85rem; display:flex; align-items:center; gap:0.3rem;">
          Explorar tienda <span class="material-symbols-outlined" style="font-size:1rem;">arrow_forward</span>
        </span>
      </a>
      <!-- Talleres -->
      <a href="<?php echo htmlspecialchars(mitos_url('talleres.php')); ?>"
         style="display:block; padding:2rem; border-radius:0.75rem; text-decoration:none;
                background:linear-gradient(135deg, rgba(227,176,75,0.2) 0%, rgba(10,8,3,0.95) 100%);
                border:1px solid rgba(227,176,75,0.3); transition:box-shadow 0.25s;"
         onmouseover="this.style.boxShadow='0 0 30px rgba(227,176,75,0.2)'"
         onmouseout="this.style.boxShadow=''">
        <span class="material-symbols-outlined" style="font-size:2.5rem; color:var(--gold); display:block; margin-bottom:1rem;">school</span>
        <h3 style="font-family:var(--font-theatrical); font-size:1.6rem; color:#fff; margin:0 0 0.5rem;">Talleres formativos</h3>
        <p style="color:rgba(255,255,255,0.6); margin:0 0 1.25rem; font-size:0.9rem;">Aprende actuación, voz, expresión corporal y más.</p>
        <span style="color:var(--gold); font-weight:700; font-size:0.85rem; display:flex; align-items:center; gap:0.3rem;">
          Ver talleres <span class="material-symbols-outlined" style="font-size:1rem;">arrow_forward</span>
        </span>
      </a>
    </div>
  </section>

</main>

<?php mitos_footer(); ?>
</body>
</html>
