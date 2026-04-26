<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

mitos_requiere_login('login.php');

$pdo = mitos_pdo();
$user_id = $_SESSION['usuario_id'];

// Obtener todas las inscripciones del usuario que no estén canceladas
$stmt = $pdo->prepare("
    SELECT i.id as inscripcion_id, i.estado, i.created_at as fecha_solicitud, 
           t.id as taller_id, t.titulo, t.imagen_url, t.instructor, t.modalidad
    FROM inscripciones_talleres i
    JOIN talleres t ON i.taller_id = t.id
    WHERE i.usuario_id = ? AND i.estado != 'cancelada'
    ORDER BY i.created_at DESC
");
$stmt->execute([$user_id]);
$mis_talleres = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Mis talleres | Mitos Escénicos';
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
    .taller-row {
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 0.75rem;
      padding: 1.5rem;
      margin-bottom: 2rem;
      transition: background 0.2s;
    }
    .taller-row:hover {
      background: rgba(255,255,255,0.05);
    }
    @media (min-width: 768px) {
      .taller-row {
        flex-direction: row;
        align-items: center;
      }
    }
    .t-portada {
      width: 100%;
      height: 160px;
      object-fit: cover;
      border-radius: 0.5rem;
      background: #111;
    }
    @media (min-width: 768px) {
      .t-portada {
        width: 250px;
        height: 140px;
      }
    }
    .badge {
      display: inline-block;
      padding: 0.2rem 0.6rem;
      border-radius: 0.25rem;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .badge-confirmada { background: var(--primary); color: #fff; }
    .badge-pendiente { background: #555; color: #fff; }
    .badge-pago { background: var(--gold); color: #000; }
  </style>
</head>
<body>
<?php mitos_header($pageTitle); ?>

<main class="container" style="padding-top:2rem; padding-bottom:4rem;">
  
  <nav style="font-size:0.8rem; color:var(--text-muted); margin-bottom:1.5rem; display:flex; align-items:center; gap:0.4rem;">
    <a href="<?php echo htmlspecialchars(mitos_url('index.php')); ?>" style="color:var(--text-muted);">Inicio</a>
    <span class="material-symbols-outlined" style="font-size:0.85rem;">chevron_right</span>
    <a href="<?php echo htmlspecialchars(mitos_url('perfil.php')); ?>" style="color:var(--text-muted);">Mi perfil</a>
    <span class="material-symbols-outlined" style="font-size:0.85rem;">chevron_right</span>
    <span style="color:#fff;">Mis talleres</span>
  </nav>

  <h1 style="font-size:2rem; font-weight:800; color:#fff; margin-bottom:0.5rem;">Mis Talleres</h1>
  <p style="color:var(--text-muted); margin-bottom:2.5rem;">Cursos a los que has solicitado inscripción o en los que estás cursando actualmente.</p>

  <?php if (empty($mis_talleres)): ?>
    <div style="text-align:center; padding:4rem 2rem; background:rgba(255,255,255,0.03); border-radius:1rem; border:1px dashed var(--border-subtle);">
      <span class="material-symbols-outlined" style="font-size:4rem; color:var(--text-muted); margin-bottom:1rem;">school</span>
      <h2 style="font-size:1.5rem; color:#fff; margin-bottom:0.5rem;">Aún no estás en ningún taller</h2>
      <p style="color:var(--text-muted); margin-bottom:1.5rem;">¿Deseas mejorar tus habilidades actorales y llevar tu talento al siguiente nivel?</p>
      <a href="<?php echo mitos_url('talleres.php'); ?>" class="btn-gold">Explorar Talleres</a>
    </div>
  <?php else: ?>

    <?php foreach ($mis_talleres as $t): 
        $estado = $t['estado'];
        $badgeClass = '';
        $estadoLabel = '';
        
        switch ($estado) {
            case 'confirmada':
                $badgeClass = 'badge-confirmada';
                $estadoLabel = 'Inscripción Confirmada';
                break;
            case 'aprobado_para_pago':
                $badgeClass = 'badge-pago';
                $estadoLabel = 'Aprobado (Falta Pago)';
                break;
            default:
                $badgeClass = 'badge-pendiente';
                $estadoLabel = 'Pendiente de Revisión';
                break;
        }
    ?>
      <div class="taller-row">
        <?php if (!empty($t['imagen_url'])): ?>
          <img src="<?php echo htmlspecialchars($t['imagen_url']); ?>" alt="" class="t-portada" />
        <?php else: ?>
          <div class="t-portada" style="display:flex; align-items:center; justify-content:center; background:linear-gradient(45deg,#222,#111);">
            <span class="material-symbols-outlined" style="font-size:3rem; color:#444;">theaters</span>
          </div>
        <?php endif; ?>

        <div style="flex:1;">
          <h3 style="font-size:1.4rem; color:#fff; margin:0 0 0.25rem; font-family:var(--font-theatrical);"><?php echo htmlspecialchars($t['titulo']); ?></h3>
          <p style="font-size:0.9rem; color:var(--text-muted); margin:0 0 0.5rem;">Instructor: <?php echo htmlspecialchars($t['instructor']); ?> | Modalidad: <?php echo ucfirst(htmlspecialchars($t['modalidad'])); ?></p>
          
          <div style="margin-top:0.75rem;">
              <span class="badge <?php echo $badgeClass; ?>"><?php echo $estadoLabel; ?></span>
          </div>
          <p style="font-size:0.8rem; color:#777; margin-top:0.5rem;">Solicitado el: <?php echo date('d M Y', strtotime($t['fecha_solicitud'])); ?></p>
        </div>

        <div style="text-align:right;">
            <?php if ($estado === 'confirmada'): ?>
                <a href="<?php echo htmlspecialchars(mitos_url('mi_taller.php?id=' . $t['taller_id'])); ?>" class="btn-primary" style="display:inline-flex; align-items:center; gap:0.5rem;">
                    Entrar al Aula <span class="material-symbols-outlined" style="font-size:1.1rem;">login</span>
                </a>
            <?php elseif ($estado === 'aprobado_para_pago'): ?>
                <a href="<?php echo htmlspecialchars(mitos_url('inscripcion-taller.php?id=' . $t['taller_id'])); ?>" class="btn-gold" style="display:inline-flex; align-items:center; gap:0.5rem;">
                    Realizar Pago <span class="material-symbols-outlined" style="font-size:1.1rem;">payment</span>
                </a>
            <?php else: ?>
                <button disabled class="btn-secondary" style="opacity:0.6; cursor:not-allowed;">Validando datos...</button>
            <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>

  <?php endif; ?>
</main>

<?php mitos_footer(); ?>
</body>
</html>
