<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

$pdo = mitos_pdo();

// Intentar cargar talleres de BD; si la tabla no existe, usar datos de ejemplo
$talleres_presenciales = [];
$talleres_video        = [];
try {
    $stmt = $pdo->query("SELECT * FROM talleres WHERE activo = 1 AND modalidad = 'presencial' ORDER BY created_at DESC");
    $talleres_presenciales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM talleres WHERE activo = 1 AND modalidad = 'video' ORDER BY created_at DESC");
    $talleres_video = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Tabla aún no existe: se mostrarán ejemplos
}

// Datos de ejemplo cuando la BD está vacía
$ejemplos_presenciales = [
    ['titulo'=>'Actuación Integral','instructor'=>'Roberto Castillo','descripcion'=>'Explora las bases del drama clásico y contemporáneo. Un viaje desde la vulnerabilidad hasta la catarsis escénica.',
     'imagen_url'=>'','precio'=>'1500.00','id'=>0],
    ['titulo'=>'Voz y Dicción','instructor'=>'Elena Gómez','descripcion'=>'Domina la proyección, resonancia e intención comunicativa para cautivar a cualquier audiencia desde la primera palabra.',
     'imagen_url'=>'','precio'=>'1200.00','id'=>0],
    ['titulo'=>'Expresión Corporal','instructor'=>'Marco Ruiz','descripcion'=>'El cuerpo como herramienta narrativa fundamental. Movimiento consciente y creación de personajes desde la fisicalidad.',
     'imagen_url'=>'','precio'=>'1000.00','id'=>0],
];
$ejemplos_video = [
    ['titulo'=>'Teoría de la Tragedia','instructor'=>'Dra. Sofía Valerio','descripcion'=>'Un análisis profundo desde los griegos hasta el teatro del absurdo.','imagen_url'=>'','precio'=>'500.00','id'=>0],
    ['titulo'=>'Diseño de Escenografía','instructor'=>'Arq. Luis Méndez','descripcion'=>'Crea mundos visuales impactantes con recursos mínimos y máxima creatividad.','imagen_url'=>'','precio'=>'450.00','id'=>0],
    ['titulo'=>'Dramaturgia Creativa','instructor'=>'Silvia Pinal','descripcion'=>'Herramientas para escribir tu primera obra de teatro desde la primera página.','imagen_url'=>'','precio'=>'400.00','id'=>0],
    ['titulo'=>'Producción Teatral','instructor'=>'Jorge Ramos','descripcion'=>'Cómo llevar una idea del papel al escenario: gestión, costos y estrategia.','imagen_url'=>'','precio'=>'550.00','id'=>0],
];

$mostrar_presenciales = !empty($talleres_presenciales) ? $talleres_presenciales : $ejemplos_presenciales;
$mostrar_video        = !empty($talleres_video)        ? $talleres_video        : $ejemplos_video;

$pageTitle = 'Talleres Formativos | Mitos Escénicos';
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="Talleres y cursos de teatro de Mitos Escénicos. Actuación, voz, expresión corporal y más."/>
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;900&family=Forum&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
</head>
<body>
<?php mitos_header($pageTitle); ?>

<main style="padding-bottom:4rem;">

  <!-- Hero Talleres -->
  <section style="padding:1.5rem 1.5rem 0;">
    <div class="container" style="padding:0;">
      <div style="position:relative; overflow:hidden; border-radius:0.75rem; min-height:380px; display:flex; align-items:flex-end; padding:3rem 2.5rem;
                  background:linear-gradient(to top, rgba(0,0,0,0.95) 25%, rgba(0,0,0,0.45) 65%, rgba(0,0,0,0.1) 100%),
                  url('https://images.unsplash.com/photo-1603330477590-3d24942b6c3a?w=1400') center/cover no-repeat;">
        <div style="max-width:600px;">
          <span style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.15em; color:var(--gold); display:block; margin-bottom:0.75rem;">Formación artística</span>
          <h1 style="font-family:var(--font-theatrical); font-size:clamp(2rem,4vw,3.2rem); color:#fff; margin:0 0 1rem; line-height:1.1;">
            La escena es tuya
          </h1>
          <p style="color:rgba(255,255,255,0.72); font-size:1rem; line-height:1.7; max-width:30rem;">
            Descubre el poder de la interpretación a través de nuestra formación profesional. El mito cobra vida en el escenario.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Talleres Presenciales -->
  <section class="container" style="margin-top:3.5rem;">
    <div class="section-header" style="border-bottom:1px solid rgba(227,176,75,0.25); padding-bottom:1rem;">
      <div>
        <h2 style="font-family:var(--font-theatrical); font-size:2rem; color:var(--gold); margin:0 0 0.3rem;">Talleres Presenciales</h2>
        <p style="color:var(--text-muted); font-style:italic; font-size:0.9rem; margin:0;">Formación cara a cara en el corazón de la escena.</p>
      </div>
    </div>
    <div class="grid-3" style="margin-top:1.75rem;">
      <?php foreach ($mostrar_presenciales as $t): ?>
        <div class="taller-card">
          <div class="taller-img">
            <span class="taller-badge">Presencial</span>
            <?php if (!empty($t['imagen_url'])): ?>
              <img src="<?php echo htmlspecialchars($t['imagen_url']); ?>"
                   alt="<?php echo htmlspecialchars($t['titulo']); ?>" loading="lazy"/>
            <?php else: ?>
              <div style="width:100%; height:100%; background:linear-gradient(135deg,rgba(149,12,19,0.4),rgba(0,0,0,0.8)); display:flex; align-items:center; justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:3.5rem; color:rgba(255,255,255,0.2);">school</span>
              </div>
            <?php endif; ?>
          </div>
          <div class="taller-body">
            <h4><?php echo htmlspecialchars($t['titulo']); ?></h4>
            <?php if (!empty($t['instructor'])): ?>
              <p class="taller-instructor">Por: <?php echo htmlspecialchars($t['instructor']); ?></p>
            <?php endif; ?>
            <p><?php echo htmlspecialchars($t['descripcion'] ?? ''); ?></p>
            <?php if (!empty($t['precio']) && (float)$t['precio'] > 0): ?>
              <p style="font-size:1rem; font-weight:800; color:var(--gold); margin:0 0 1rem;">
                $<?php echo number_format((float)$t['precio'], 2); ?>
              </p>
            <?php endif; ?>
            <?php if ((int)$t['id'] > 0): ?>
              <a href="<?php echo htmlspecialchars(mitos_url('inscripcion-taller.php?id=' . (int)$t['id'])); ?>"
                 class="btn-primary" style="width:100%; display:block; text-align:center; padding:0.7rem;">Inscribirse</a>
            <?php else: ?>
              <a href="<?php echo htmlspecialchars(mitos_url('login.php')); ?>"
                 class="btn-primary" style="width:100%; display:block; text-align:center; padding:0.7rem;">Inscribirse</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Cursos en Video -->
  <section class="container" style="margin-top:3.5rem;">
    <div class="section-header" style="border-bottom:1px solid rgba(227,176,75,0.25); padding-bottom:1rem;">
      <div>
        <h2 style="font-family:var(--font-theatrical); font-size:2rem; color:var(--gold); margin:0 0 0.3rem;">Cursos en Video</h2>
        <p style="color:var(--text-muted); font-style:italic; font-size:0.9rem; margin:0;">Aprende a tu ritmo con nuestras masterclasses digitales.</p>
      </div>
    </div>
    <div class="grid-4" style="margin-top:1.75rem;">
      <?php foreach ($mostrar_video as $t): ?>
        <div class="taller-card">
          <div class="taller-img" style="height:160px;">
            <span class="taller-badge" style="background:var(--primary);">Video</span>
            <!-- Play icon overlay -->
            <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; z-index:2; opacity:0; transition:opacity 0.25s;"
                 class="play-overlay">
              <span class="material-symbols-outlined" style="font-size:3rem; color:rgba(255,255,255,0.8);">play_circle</span>
            </div>
            <?php if (!empty($t['imagen_url'])): ?>
              <img src="<?php echo htmlspecialchars($t['imagen_url']); ?>"
                   alt="<?php echo htmlspecialchars($t['titulo']); ?>" loading="lazy"
                   style="height:100%; object-fit:cover;"/>
            <?php else: ?>
              <div style="width:100%; height:100%; background:linear-gradient(135deg,rgba(227,176,75,0.15),rgba(0,0,0,0.85)); display:flex; align-items:center; justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:3rem; color:rgba(255,255,255,0.2);">play_circle</span>
              </div>
            <?php endif; ?>
          </div>
          <div class="taller-body">
            <h4 style="font-size:1rem;"><?php echo htmlspecialchars($t['titulo']); ?></h4>
            <?php if (!empty($t['instructor'])): ?>
              <p class="taller-instructor" style="font-size:0.75rem;"><?php echo htmlspecialchars($t['instructor']); ?></p>
            <?php endif; ?>
            <p style="font-size:0.8rem; -webkit-line-clamp:3;"><?php echo htmlspecialchars($t['descripcion'] ?? ''); ?></p>
            <?php if (!empty($t['precio']) && (float)$t['precio'] > 0): ?>
              <p style="font-size:0.95rem; font-weight:800; color:var(--gold); margin:0 0 1rem;">$<?php echo number_format((float)$t['precio'], 2); ?></p>
            <?php endif; ?>
            <?php if ((int)$t['id'] > 0): ?>
              <a href="<?php echo htmlspecialchars(mitos_url('inscripcion-taller.php?id=' . (int)$t['id'])); ?>"
                 class="btn-primary btn-sm" style="width:100%; display:block; text-align:center;">Inscribirse</a>
            <?php else: ?>
              <a href="<?php echo htmlspecialchars(mitos_url('login.php')); ?>"
                 class="btn-primary btn-sm" style="width:100%; display:block; text-align:center;">Inscribirse</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- CTA Newsletter -->
  <section class="container" style="margin-top:3.5rem;">
    <div style="background:rgba(149,12,19,0.1); border:1px solid rgba(149,12,19,0.3); border-radius:0.75rem; padding:3rem 2rem; text-align:center;">
      <h2 style="font-family:var(--font-theatrical); font-size:1.8rem; color:#fff; margin:0 0 0.75rem; text-transform:uppercase; letter-spacing:0.05em;">
        ¿No encuentras lo que buscas?
      </h2>
      <p style="color:var(--text-muted); font-size:0.95rem; max-width:36rem; margin:0 auto 2rem; line-height:1.7;">
        Suscríbete para recibir notificaciones sobre nuevos talleres, becas y contenido exclusivo para la comunidad actoral.
      </p>
      <form method="get" action="<?php echo htmlspecialchars(mitos_url('registro.php')); ?>"
            style="display:flex; flex-direction:column; gap:0.75rem; max-width:420px; margin:0 auto;">
        <div style="display:flex; gap:0.75rem;">
          <input type="email" name="email" placeholder="Tu correo electrónico" style="flex:1;"/>
          <button type="submit" class="btn-gold">Unirse</button>
        </div>
      </form>
    </div>
  </section>

</main>

<?php mitos_footer(); ?>
<script>
// Hover sobre video cards: mostrar play overlay
document.querySelectorAll('.taller-card').forEach(function(card) {
  var overlay = card.querySelector('.play-overlay');
  if (!overlay) return;
  card.addEventListener('mouseenter', function() { overlay.style.opacity = '1'; });
  card.addEventListener('mouseleave', function() { overlay.style.opacity = '0'; });
});
</script>
</body>
</html>
