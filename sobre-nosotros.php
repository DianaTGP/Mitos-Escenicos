<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';
$pageTitle = 'Sobre Nosotros | Mitos Escénicos';

$rolesLabel = [
    'actor'     => 'Actor / Actriz',
    'director'  => 'Director / Directora',
    'disenador' => 'Diseñador / Escenógrafo',
    'bailarin'  => 'Bailarín / Bailarina',
    'otro'      => 'Miembro del equipo',
];

try {
    $pdo = mitos_pdo();
    $artistas = $pdo->query('SELECT nombre, rol, especialidad, foto_url FROM artistas WHERE activo = 1 ORDER BY orden, nombre')->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $artistas = [];
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="Conoce a la compañía de teatro Mitos Escénicos. Nuestra misión, historia y equipo artístico."/>
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;900&family=Forum&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
</head>
<body>
<?php mitos_header($pageTitle); ?>

<main style="padding-bottom:4rem;">

  <!-- Hero -->
  <section style="padding:1.5rem 1.5rem 0;">
    <div class="container" style="padding:0;">
      <div style="position:relative; overflow:hidden; border-radius:0.75rem; min-height:380px; display:flex; align-items:flex-end; padding:3rem 2.5rem;
                  background:linear-gradient(to top, rgba(0,0,0,0.92) 30%, rgba(0,0,0,0.35) 70%, rgba(0,0,0,0.1) 100%),
                  url('https://images.unsplash.com/photo-1503095396549-807759245b35?w=1200') center/cover;">
        <div style="max-width:600px;">
          <span style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.15em; color:var(--gold); display:block; margin-bottom:0.75rem;">Nuestra historia</span>
          <h1 style="font-family:var(--font-theatrical); font-size:clamp(2rem,4vw,3.2rem); color:#fff; margin:0 0 1rem; line-height:1.1;">
            La magia nace<br>en el escenario
          </h1>
          <p style="color:rgba(255,255,255,0.70); font-size:1rem; line-height:1.7; max-width:28rem;">
            Mitos Escénicos es una compañía de teatro dedicada a llevar al escenario historias que unen la realidad con el mito.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Misión y valores -->
  <section class="container" style="margin-top:3.5rem;">
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px,1fr)); gap:1.5rem;">
      <div class="surface-card">
        <span class="material-symbols-outlined" style="font-size:2rem; color:var(--primary); display:block; margin-bottom:0.85rem;">stars</span>
        <h2 style="font-family:var(--font-theatrical); font-size:1.4rem; color:#fff; margin:0 0 0.75rem;">Nuestra misión</h2>
        <p style="color:var(--text-muted); font-size:0.9rem; line-height:1.75;">
          Crear experiencias teatrales memorables que unan a las comunidades y transformen al espectador. Cada función es un acto de amor al arte escénico.
        </p>
      </div>
      <div class="surface-card">
        <span class="material-symbols-outlined" style="font-size:2rem; color:#f27f0d; display:block; margin-bottom:0.85rem;">visibility</span>
        <h2 style="font-family:var(--font-theatrical); font-size:1.4rem; color:#fff; margin:0 0 0.75rem;">Nuestra visión</h2>
        <p style="color:var(--text-muted); font-size:0.9rem; line-height:1.75;">
          Ser referente del teatro contemporáneo en México, formando audiencias críticas y artistas comprometidos con la verdad escénica y la excelencia.
        </p>
      </div>
      <div class="surface-card">
        <span class="material-symbols-outlined" style="font-size:2rem; color:var(--deep-maroon); display:block; margin-bottom:0.85rem;">volunteer_activism</span>
        <h2 style="font-family:var(--font-theatrical); font-size:1.4rem; color:#fff; margin:0 0 0.75rem;">Nuestros valores</h2>
        <p style="color:var(--text-muted); font-size:0.9rem; line-height:1.75;">
          Autenticidad, comunidad, rigor artístico y apertura. Creemos que el teatro tiene el poder de cambiar perspectivas y construir puentes entre personas.
        </p>
      </div>
    </div>
  </section>

  <!-- Historia -->
  <section class="container" style="margin-top:3.5rem; max-width:780px;">
    <p class="section-label">La compañía</p>
    <h2 style="font-family:var(--font-theatrical); font-size:2rem; color:#fff; margin:0.25rem 0 1.5rem;">Quiénes somos</h2>
    <p style="color:rgba(255,255,255,0.8); line-height:1.8; margin-bottom:1.25rem; font-size:1rem;">
      Mitos Escénicos nació del deseo de crear un espacio donde el teatro clásico dialogara con el contemporáneo. Fundada por un grupo de artistas apasionados, nuestra compañía ha producido obras que van desde las tragedias griegas hasta guiones originales que retratan la realidad de nuestro tiempo.
    </p>
    <p style="color:rgba(255,255,255,0.8); line-height:1.8; margin-bottom:1.25rem; font-size:1rem;">
      Además de nuestras producciones en cartelera, ofrecemos talleres formativos para quienes desean explorar el arte escénico, ya sea como vocación o como herramienta de autoconocimiento.
    </p>
    <p style="color:rgba(255,255,255,0.8); line-height:1.8; font-size:1rem;">
      Nuestra tienda oficial permite a nuestros seguidores llevar un pedazo del teatro a casa con merchandising de calidad.
    </p>
  </section>

  <!-- Equipo Creativo -->
  <?php if (!empty($artistas)): ?>
  <section class="container" style="margin-top:3.5rem;">
    <p class="section-label">Nuestro equipo</p>
    <h2 style="font-family:var(--font-theatrical); font-size:2rem; color:#fff; margin:0.25rem 0 2rem;">Almas detrás del Telón</h2>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:1.5rem;">
      <?php foreach ($artistas as $art): ?>
        <a href="<?php echo htmlspecialchars(mitos_url('artista.php?id=' . (int)$art['id'])); ?>" style="display:block; position:relative; group: 0; text-decoration:none;">
          <div style="aspect-ratio:3/4; overflow:hidden; border-radius:0.5rem; margin-bottom:0.85rem; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.04); filter:grayscale(1); transition:filter 0.6s, transform 0.6s;"
               onmouseover="this.style.filter='grayscale(0)'; this.style.transform='scale(1.02)'"
               onmouseout="this.style.filter='grayscale(1)'; this.style.transform='scale(1)'">
            <?php if (!empty($art['foto_url'])): ?>
               <img src="<?php echo htmlspecialchars(mitos_url($art['foto_url'])); ?>" alt="<?php echo htmlspecialchars($art['nombre']); ?>" style="width:100%; height:100%; object-fit:cover; display:block;"/>
            <?php else: ?>
              <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:4rem; color:rgba(255,255,255,0.15);">person</span>
              </div>
            <?php endif; ?>
          </div>
          <h4 style="font-size:1rem; font-weight:700; color:#fff; margin:0 0 0.25rem; line-height:1.2; transition:color 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='#fff'"><?php echo htmlspecialchars($art['nombre']); ?></h4>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- CTA -->
  <section class="container" style="margin-top:3.5rem;">
    <div style="background:linear-gradient(135deg,rgba(149,12,19,0.25),rgba(0,0,0,0.5)); border:1px solid rgba(149,12,19,0.3); border-radius:0.75rem; padding:2.5rem; text-align:center;">
      <h2 style="font-family:var(--font-theatrical); font-size:2rem; color:#fff; margin:0 0 0.75rem;">¿Listo para vivir el teatro?</h2>
      <p style="color:var(--text-muted); margin-bottom:2rem;">Reserva tus boletos o inscríbete en nuestros talleres formativos.</p>
      <div style="display:flex; flex-wrap:wrap; gap:1rem; justify-content:center;">
        <a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>" class="btn-primary">
          <span class="material-symbols-outlined" style="font-size:1rem;">theaters</span> Ver cartelera
        </a>
        <a href="<?php echo htmlspecialchars(mitos_url('talleres.php')); ?>" class="btn-secondary">
          <span class="material-symbols-outlined" style="font-size:1rem;">school</span> Talleres formativos
        </a>
        <a href="<?php echo htmlspecialchars(mitos_url('tienda.php')); ?>" class="btn-secondary">
          <span class="material-symbols-outlined" style="font-size:1rem;">shopping_bag</span> Tienda
        </a>
      </div>
    </div>
  </section>

</main>

<?php mitos_footer(); ?>
</body>
</html>
