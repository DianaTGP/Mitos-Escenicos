<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

mitos_requiere_login('login.php');

$pdo = mitos_pdo();
$baseUrl = rtrim(mitos_url(''), '/');

$taller_id = (int)($_GET['id'] ?? 0);
if ($taller_id <= 0) {
    header("Location: $baseUrl/talleres.php");
    exit;
}

$userLogged = $_SESSION['usuario_id'];

// Check permission
$stmt = $pdo->prepare("SELECT id FROM inscripciones_talleres WHERE taller_id = ? AND usuario_id = ? AND estado = 'confirmada'");
$stmt->execute([$taller_id, $userLogged]);
if (!$stmt->fetch()) {
    header("Location: $baseUrl/talleres.php?msg=acceso_denegado");
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM talleres WHERE id = ?');
$stmt->execute([$taller_id]);
$taller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$taller) {
    header("Location: $baseUrl/talleres.php");
    exit;
}

$stmtBloques = $pdo->prepare('SELECT * FROM taller_bloques WHERE taller_id = ? ORDER BY orden ASC, id ASC');
$stmtBloques->execute([$taller_id]);
$bloques = $stmtBloques->fetchAll(PDO::FETCH_ASSOC);

$contenidosPorBloque = [];
if (!empty($bloques)) {
    $bloqueIds = array_column($bloques, 'id');
    $placeholders = implode(',', array_fill(0, count($bloqueIds), '?'));
    $stmtC = $pdo->prepare("SELECT * FROM taller_contenidos WHERE bloque_id IN ($placeholders) ORDER BY orden ASC, id ASC");
    $stmtC->execute($bloqueIds);
    foreach ($stmtC->fetchAll(PDO::FETCH_ASSOC) as $c) {
        $contenidosPorBloque[$c['bloque_id']][] = $c;
    }
}

$tipoIconos = [
    'zoom' => 'videocam',
    'video_link' => 'play_circle',
    'archivo' => 'description',
    'texto' => 'article'
];
$tipoColores = [
    'zoom' => '#3b82f6',
    'video_link' => '#ef4444',
    'archivo' => 'var(--gold)',
    'texto' => '#aaa'
];

$pageTitle = 'Aula Virtual: ' . htmlspecialchars($taller['titulo']);
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
    .bloque-wrap {
      background: rgba(255,255,255,0.02);
      border: 1px solid var(--border-dark);
      border-radius: 0.75rem;
      margin-bottom: 1rem;
      overflow: hidden;
    }
    .bloque-header {
      padding: 1.2rem;
      background: rgba(255,255,255,0.04);
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
      transition: background 0.2s;
    }
    .bloque-header:hover { background: rgba(255,255,255,0.06); }
    .bloque-body {
      padding: 1.5rem;
      border-top: 1px solid var(--border-dark);
      display: none;
    }
    .recurso-row {
      display: flex;
      align-items: center;
      padding: 1rem;
      background: rgba(0,0,0,0.3);
      border: 1px solid rgba(255,255,255,0.03);
      border-radius: 0.5rem;
      margin-bottom: 0.75rem;
      gap: 1.25rem;
      transition: transform 0.2s, border-color 0.2s;
    }
    .recurso-row:hover {
        transform: translateY(-2px);
        border-color: rgba(227,176,75,0.3);
    }
    .recurso-row:last-child {
        margin-bottom: 0;
    }
  </style>
</head>
<body>
<?php mitos_header($pageTitle); ?>

<main class="container" style="padding-top:2rem; padding-bottom:5rem;">
    <a href="<?php echo $baseUrl; ?>/talleres.php" style="color:var(--primary); font-size:0.9rem; display:inline-flex; align-items:center; gap:0.25rem; margin-bottom:1.5rem; text-decoration:none; font-weight:600;">
        <span class="material-symbols-outlined" style="font-size:1rem;">arrow_back</span> Volver a Talleres
    </a>

    <!-- Header Taller -->
    <div style="display:flex; flex-wrap:wrap; gap:2rem; align-items:center; margin-bottom:3rem; padding-bottom:2rem; border-bottom:1px solid var(--border-subtle);">
        <?php if (!empty($taller['imagen_url'])): ?>
            <img src="<?php echo htmlspecialchars($taller['imagen_url']); ?>" alt="Portada" style="width:200px; aspect-ratio:4/3; object-fit:cover; border-radius:0.75rem; box-shadow:0 10px 30px rgba(0,0,0,0.5);"/>
        <?php endif; ?>
        <div style="flex:1; min-width:300px;">
            <p style="text-transform:uppercase; font-size:0.8rem; letter-spacing:0.1em; font-weight:800; color:var(--gold); margin:0 0 0.5rem;">Aula Virtual</p>
            <h1 style="font-family:var(--font-theatrical); font-size:2.5rem; color:#fff; margin:0 0 0.5rem; line-height:1.1;">
                <?php echo htmlspecialchars($taller['titulo']); ?>
            </h1>
            <p style="color:var(--text-muted); font-size:1rem; margin-bottom:0.5rem;">
                Impartido por: <strong style="color:#fff;"><?php echo htmlspecialchars($taller['instructor']); ?></strong>
            </p>
        </div>
    </div>

    <!-- Syllabus / Temario -->
    <h2 style="font-family:var(--font-theatrical); font-size:1.8rem; color:#fff; margin-bottom:1.5rem;">Material del Curso</h2>

    <?php if (empty($bloques)): ?>
        <div style="text-align:center; padding:4rem 1rem; background:rgba(255,255,255,0.02); border-radius:1rem; border:1px dashed var(--border-subtle);">
            <span class="material-symbols-outlined" style="font-size:3rem; color:var(--text-muted);">menu_book</span>
            <p style="color:#888; margin-top:1rem; font-size:1.1rem;">El instructor aún no ha subido contenido a este taller.</p>
        </div>
    <?php endif; ?>

    <div style="max-width:800px;">
        <?php foreach ($bloques as $idx => $b): ?>
            <div class="bloque-wrap">
                <div class="bloque-header" onclick="toggleBloque(<?php echo $b['id']; ?>)">
                    <div style="display:flex; align-items:center; gap:1rem;">
                        <span class="material-symbols-outlined" style="color:var(--gold); font-size:1.5rem;">view_week</span>
                        <div>
                            <h3 style="margin:0; font-size:1.1rem; color:#fff; font-weight:700;"><?php echo htmlspecialchars($b['titulo']); ?></h3>
                            <?php if(!empty($b['descripcion'])): ?>
                                <p style="margin:0; font-size:0.9rem; color:#aaa; margin-top:0.25rem;"><?php echo htmlspecialchars($b['descripcion']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="material-symbols-outlined" style="color:var(--text-muted);" id="icon-<?php echo $b['id']; ?>">expand_more</span>
                </div>

                <div class="bloque-body" id="bloque-body-<?php echo $b['id']; ?>">
                    <?php 
                    $contenidos = $contenidosPorBloque[$b['id']] ?? []; 
                    foreach ($contenidos as $c): 
                        $icono = $tipoIconos[$c['tipo']] ?? 'article';
                        $color = $tipoColores[$c['tipo']] ?? '#fff';
                    ?>
                        <div class="recurso-row">
                            <span class="material-symbols-outlined" style="color:<?php echo $color; ?>; font-size:2rem;"><?php echo $icono; ?></span>
                            <div style="flex:1;">
                                <h4 style="margin:0; font-size:1.05rem; color:#fff; font-weight:600;"><?php echo htmlspecialchars($c['titulo']); ?></h4>
                                
                                <?php if ($c['tipo'] === 'texto' && !empty($c['contenido_texto'])): ?>
                                    <div style="margin-top:0.75rem; color:#ddd; font-size:0.95rem; line-height:1.6; background:rgba(255,255,255,0.05); padding:1rem; border-radius:0.5rem; border-left:3px solid var(--gold);">
                                        <?php echo nl2br(htmlspecialchars($c['contenido_texto'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($c['tipo'] !== 'texto' && !empty($c['valor_recurso'])): ?>
                                <div>
                                    <a href="<?php echo htmlspecialchars($c['valor_recurso']); ?>" target="_blank" class="btn-primary btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem;">
                                        <?php echo ($c['tipo'] === 'zoom') ? 'Entrar' : (($c['tipo'] === 'video_link') ? 'Ver Video' : 'Descargar'); ?>
                                        <span class="material-symbols-outlined" style="font-size:1.1rem;">open_in_new</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($contenidos)): ?>
                        <p style="color:#666; font-size:0.9rem; text-align:center; margin:0;">Este módulo está vacío por ahora.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<script>
function toggleBloque(id) {
    const el = document.getElementById('bloque-body-' + id);
    const icon = document.getElementById('icon-' + id);
    if (el.style.display === 'none' || el.style.display === '') {
        el.style.display = 'block';
        icon.textContent = 'expand_less';
    } else {
        el.style.display = 'none';
        icon.textContent = 'expand_more';
    }
}
</script>

<?php mitos_footer(); ?>
</body>
</html>
