<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

$pdo = mitos_pdo();

// Intentar cargar talleres de BD; si la tabla no existe, usar datos de ejemplo
$talleres_presenciales = [];
$talleres_video        = [];
try {
    // Presenciales
    $stmt = $pdo->query("SELECT t.*, (SELECT COUNT(*) FROM inscripciones_talleres WHERE taller_id = t.id AND estado = 'confirmada') as confirmados FROM talleres t WHERE t.activo = 1 AND t.modalidad = 'presencial' ORDER BY t.created_at DESC");
    if($stmt) {
        $talleres_presenciales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Video
    $stmt2 = $pdo->query("SELECT t.*, (SELECT COUNT(*) FROM inscripciones_talleres WHERE taller_id = t.id AND estado = 'confirmada') as confirmados FROM talleres t WHERE t.activo = 1 AND t.modalidad = 'video' ORDER BY t.created_at DESC");
    if($stmt2) {
        $talleres_video = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Si la tabla no existe, simplemente quedarán en arreglo vacío
}

$mostrar_presenciales = $talleres_presenciales;
$mostrar_video        = $talleres_video;

$estadoTaller = [];
$userLogged = $_SESSION['usuario_id'] ?? null;
if ($userLogged) {
    $stmtSt = $pdo->prepare("SELECT taller_id, estado FROM inscripciones_talleres WHERE usuario_id = ? AND estado != 'cancelada'");
    $stmtSt->execute([$userLogged]);
    foreach ($stmtSt->fetchAll(PDO::FETCH_ASSOC) as $rowInfo) {
        $estadoTaller[$rowInfo['taller_id']] = $rowInfo['estado'];
    }
}


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
              
              <?php 
                $cuposMax = (int)($t['cupo_maximo'] ?? 0);
                $confirmados = (int)($t['confirmados'] ?? 0);
                $agotado = false;
                if ($cuposMax > 0):
                    $disponibles = max(0, $cuposMax - $confirmados);
                    $agotado = ($disponibles === 0);
              ?>
                <p style="font-size:0.8rem; color:<?php echo $agotado ? '#ef4444' : '#aaa'; ?>; margin:0 0 0.5rem; font-weight:600;">
                    <?php echo $agotado ? 'Taller Agotado' : "Cupos disponibles: $disponibles / $cuposMax"; ?>
                </p>
              <?php endif; ?>

              <?php if (!empty($t['precio']) && (float)$t['precio'] > 0): ?>
                <p style="font-size:1rem; font-weight:800; color:var(--gold); margin:0 0 1rem;">
                  $<?php echo number_format((float)$t['precio'], 2); ?>
                </p>
              <?php endif; ?>
              <?php 
                   $tId = (int)$t['id'];
                   $estadoEstudiante = $estadoTaller[$tId] ?? null;
                   
                   if ($tId > 0 && $estadoEstudiante === 'confirmada'): ?>
                     <a href="<?php echo htmlspecialchars(mitos_url('mi_taller.php?id=' . $tId)); ?>"
                        class="btn-gold" style="width:100%; display:block; text-align:center; padding:0.7rem; color:#000;">Entrar a Mi Taller</a>
                   <?php elseif ($tId > 0 && $estadoEstudiante === 'aprobado_para_pago'): ?>
                     <a href="<?php echo htmlspecialchars(mitos_url('inscripcion-taller.php?id=' . $tId)); ?>"
                        class="btn-primary" style="width:100%; display:block; text-align:center; padding:0.7rem; background:var(--gold); color:#000; font-weight:800;">Pagar Ahora</a>
                   <?php elseif ($tId > 0 && $estadoEstudiante === 'pendiente'): ?>
                     <button disabled class="btn-secondary" style="width:100%; display:block; text-align:center; padding:0.7rem; opacity:0.6; cursor:not-allowed;">Solicitud Pendiente</button>
                   <?php elseif ($agotado): ?>
                     <button disabled class="btn-secondary" style="width:100%; display:block; text-align:center; padding:0.7rem; opacity:0.5; cursor:not-allowed; color:#ef4444; border-color:#ef4444;">Sin Cupos</button>
                   <?php elseif ($tId > 0): ?>
                     <a href="<?php echo htmlspecialchars(mitos_url('inscripcion-taller.php?id=' . $tId)); ?>"
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
            
            <?php 
              $cuposMax = (int)($t['cupo_maximo'] ?? 0);
              $confirmados = (int)($t['confirmados'] ?? 0);
              $agotado = false;
              if ($cuposMax > 0):
                  $disponibles = max(0, $cuposMax - $confirmados);
                  $agotado = ($disponibles === 0);
            ?>
              <p style="font-size:0.75rem; color:<?php echo $agotado ? '#ef4444' : '#aaa'; ?>; margin:0 0 0.5rem; font-weight:600;">
                  <?php echo $agotado ? 'Taller Agotado' : "Cupos disponibles: $disponibles / $cuposMax"; ?>
              </p>
            <?php endif; ?>

            <?php if (!empty($t['precio']) && (float)$t['precio'] > 0): ?>
              <p style="font-size:0.95rem; font-weight:800; color:var(--gold); margin:0 0 1rem;">$<?php echo number_format((float)$t['precio'], 2); ?></p>
            <?php endif; ?>
            <?php 
                 $tId = (int)$t['id'];
                 $estadoEstudiante = $estadoTaller[$tId] ?? null;
                 
                 if ($tId > 0 && $estadoEstudiante === 'confirmada'): ?>
                   <a href="<?php echo htmlspecialchars(mitos_url('mi_taller.php?id=' . $tId)); ?>"
                      class="btn-gold btn-sm" style="width:100%; display:block; text-align:center; color:#000;">Entrar a Mi Taller</a>
                 <?php elseif ($tId > 0 && $estadoEstudiante === 'aprobado_para_pago'): ?>
                   <a href="<?php echo htmlspecialchars(mitos_url('inscripcion-taller.php?id=' . $tId)); ?>"
                      class="btn-primary btn-sm" style="width:100%; display:block; text-align:center; background:var(--gold); color:#000; font-weight:800;">Pagar Ahora</a>
                 <?php elseif ($tId > 0 && $estadoEstudiante === 'pendiente'): ?>
                   <button disabled class="btn-secondary btn-sm" style="width:100%; display:block; text-align:center; opacity:0.6; cursor:not-allowed;">Pendiente</button>
                 <?php elseif ($agotado): ?>
                   <button disabled class="btn-secondary btn-sm" style="width:100%; display:block; text-align:center; opacity:0.5; cursor:not-allowed; color:#ef4444; border-color:#ef4444;">Sin Cupos</button>
                 <?php elseif ($tId > 0): ?>
                   <a href="<?php echo htmlspecialchars(mitos_url('inscripcion-taller.php?id=' . $tId)); ?>"
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
