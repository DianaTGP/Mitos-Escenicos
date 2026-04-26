<?php
require_once __DIR__ . '/../php/init.php';
require_once __DIR__ . '/../php/layout.php';
require_once __DIR__ . '/../php/upload.php';
mitos_requiere_admin();

$pdo = mitos_pdo();
$baseUrl = rtrim(mitos_url(''), '/');

$taller_id = (int)($_GET['id'] ?? 0);
if ($taller_id <= 0) {
    header("Location: $baseUrl/admin/talleres.php");
    exit;
}

$actTab = $_GET['tab'] ?? 'inscritos';

// ── Manejo de acciones (POST) ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // Cambiar estado de la inscripción
    if ($accion === 'cambiar_estado') {
        $inscripcion_id = (int)($_POST['inscripcion_id'] ?? 0);
        $nuevo_estado   = $_POST['estado'] ?? 'pendiente';

        if ($inscripcion_id > 0 && in_array($nuevo_estado, ['pendiente', 'confirmada', 'cancelada', 'aprobado_para_pago'], true)) {
            $stmt = $pdo->prepare('SELECT estado, nombre_completo, email FROM inscripciones_talleres WHERE id = ? AND taller_id = ?');
            $stmt->execute([$inscripcion_id, $taller_id]);
            $inscripcion = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($inscripcion) {
                $pdo->prepare('UPDATE inscripciones_talleres SET estado = ?, updated_at = GETDATE() WHERE id = ?')
                    ->execute([$nuevo_estado, $inscripcion_id]);

                $enviar_email = false;
                $subject = ""; $msg = "";

                $stmtTaller = $pdo->prepare('SELECT titulo, fecha_inicio FROM talleres WHERE id = ?');
                $stmtTaller->execute([$taller_id]);
                $tallerData = $stmtTaller->fetch(PDO::FETCH_ASSOC);

                if ($nuevo_estado === 'confirmada' && $inscripcion['estado'] !== 'confirmada') {
                    $enviar_email = true;
                    $subject = "Confirmación de Inscripción: " . $tallerData['titulo'];
                    $msg = "Hola " . $inscripcion['nombre_completo'] . ",\n\n";
                    $msg .= "¡Nos emociona comunicarte que tu inscripción para '" . $tallerData['titulo'] . "' está confirmada financieramente y tu lugar está asegurado!\n";
                    $msg .= "Inicia sesión en nuestra web para comenzar a acceder al material.\n\nSaludos,\nEl equipo de Mitos Escénicos.";
                } else if ($nuevo_estado === 'aprobado_para_pago' && $inscripcion['estado'] !== 'aprobado_para_pago') {
                    $enviar_email = true;
                    $subject = "Aprobado para Pago: " . $tallerData['titulo'];
                    $msg = "Hola " . $inscripcion['nombre_completo'] . ",\n\n";
                    $msg .= "Tu solicitud para el taller manual ha sido evaluada y aprobada.\n";
                    $msg .= "Por favor, ingresa a la página web del taller con tu cuenta para procesar el cargo y asegurar tu plaza oficial.\n\nSaludos,\nEl equipo de Mitos Escénicos.";
                }

                if ($enviar_email) {
                    $to = $inscripcion['email'];
                    $headers = "From: admin@mitosescenicos.com\r\nReply-To: admin@mitosescenicos.com\r\n";
                    @mail($to, $subject, $msg, $headers);
                }
            }
        }
        header("Location: $baseUrl/admin/taller.php?id=$taller_id&tab=inscritos&msg=estado_actualizado");
        exit;
    }

    // Crear Bloque
    if ($accion === 'crear_bloque') {
        $titulo = trim((string)($_POST['titulo'] ?? ''));
        $desc = trim((string)($_POST['descripcion'] ?? ''));
        if ($titulo !== '') {
            $pdo->prepare('INSERT INTO taller_bloques (taller_id, titulo, descripcion) VALUES (?, ?, ?)')
                ->execute([$taller_id, $titulo, $desc ?: null]);
        }
        header("Location: $baseUrl/admin/taller.php?id=$taller_id&tab=temario&msg=bloque_creado");
        exit;
    }

    // Eliminar Bloque
    if ($accion === 'eliminar_bloque') {
        $bloque_id = (int)($_POST['bloque_id'] ?? 0);
        if ($bloque_id > 0) {
            $stmtFiles = $pdo->prepare("SELECT valor_recurso FROM taller_contenidos WHERE bloque_id = ? AND tipo = 'archivo'");
            $stmtFiles->execute([$bloque_id]);
            foreach ($stmtFiles->fetchAll() as $fileRow) {
                if ($fileRow['valor_recurso'] && function_exists('mitos_delete_media_file')) {
                    $relUrl = str_replace($baseUrl . '/', '', $fileRow['valor_recurso']);
                    mitos_delete_media_file($relUrl);
                }
            }
            $pdo->prepare('DELETE FROM taller_bloques WHERE id = ? AND taller_id = ?')->execute([$bloque_id, $taller_id]);
        }
        header("Location: $baseUrl/admin/taller.php?id=$taller_id&tab=temario&msg=bloque_eliminado");
        exit;
    }

    // Crear Contenido
    if ($accion === 'crear_contenido') {
        $bloque_id = (int)($_POST['bloque_id'] ?? 0);
        $tipo      = $_POST['tipo'] ?? 'texto';
        $titulo    = trim((string)($_POST['titulo'] ?? ''));
        $link      = trim((string)($_POST['valor_recurso'] ?? ''));
        $texto     = trim((string)($_POST['contenido_texto'] ?? ''));

        if ($bloque_id > 0 && $titulo !== '' && in_array($tipo, ['zoom', 'video_link', 'archivo', 'texto'], true)) {
            if ($tipo === 'archivo' && isset($_FILES['file_recurso']) && $_FILES['file_recurso']['error'] === UPLOAD_ERR_OK) {
                if (function_exists('mitos_upload_media')) {
                    $resUpload = mitos_upload_media(['name' => [$_FILES['file_recurso']['name']], 'type' => [$_FILES['file_recurso']['type']], 'tmp_name' => [$_FILES['file_recurso']['tmp_name']], 'error' => [$_FILES['file_recurso']['error']], 'size' => [$_FILES['file_recurso']['size']]], 'taller_doc');
                    if (!empty($resUpload['rutas'][0]['ruta'])) {
                        $link = $baseUrl . '/' . $resUpload['rutas'][0]['ruta'];
                    }
                }
            }
            $pdo->prepare('INSERT INTO taller_contenidos (bloque_id, tipo, titulo, valor_recurso, contenido_texto) VALUES (?, ?, ?, ?, ?)')
                ->execute([$bloque_id, $tipo, $titulo, $link ?: null, $texto ?: null]);
        }
        header("Location: $baseUrl/admin/taller.php?id=$taller_id&tab=temario&msg=contenido_creado");
        exit;
    }
    
    // Editar Contenido
    if ($accion === 'editar_contenido') {
        $cont_id   = (int)($_POST['contenido_id'] ?? 0);
        $tipo      = $_POST['tipo'] ?? 'texto';
        $titulo    = trim((string)($_POST['titulo'] ?? ''));
        $link      = trim((string)($_POST['valor_recurso'] ?? ''));
        $texto     = trim((string)($_POST['contenido_texto'] ?? ''));

        if ($cont_id > 0 && $titulo !== '' && in_array($tipo, ['zoom', 'video_link', 'archivo', 'texto'], true)) {
            if ($tipo === 'archivo' && isset($_FILES['file_recurso']) && $_FILES['file_recurso']['error'] === UPLOAD_ERR_OK) {
                if (function_exists('mitos_upload_media')) {
                    $resUpload = mitos_upload_media(['name' => [$_FILES['file_recurso']['name']], 'type' => [$_FILES['file_recurso']['type']], 'tmp_name' => [$_FILES['file_recurso']['tmp_name']], 'error' => [$_FILES['file_recurso']['error']], 'size' => [$_FILES['file_recurso']['size']]], 'taller_doc');
                    if (!empty($resUpload['rutas'][0]['ruta'])) {
                        $link = $baseUrl . '/' . $resUpload['rutas'][0]['ruta'];
                    }
                }
            } else if ($tipo === 'archivo') {
                $link = trim((string)($_POST['valor_recurso_actual'] ?? ''));
            }

            $pdo->prepare('UPDATE taller_contenidos SET tipo=?, titulo=?, valor_recurso=?, contenido_texto=? WHERE id=?')
                ->execute([$tipo, $titulo, $link ?: null, $texto ?: null, $cont_id]);
        }
        header("Location: $baseUrl/admin/taller.php?id=$taller_id&tab=temario&msg=contenido_editado");
        exit;
    }

    // Eliminar Contenido
    if ($accion === 'eliminar_contenido') {
        $cont_id = (int)($_POST['contenido_id'] ?? 0);
        if ($cont_id > 0) {
            $stmtInfo = $pdo->prepare("SELECT tipo, valor_recurso FROM taller_contenidos WHERE id = ?");
            $stmtInfo->execute([$cont_id]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);
            if ($info) {
                if ($info['tipo'] === 'archivo' && $info['valor_recurso'] && function_exists('mitos_delete_media_file')) {
                    $relUrl = str_replace($baseUrl . '/', '', $info['valor_recurso']);
                    mitos_delete_media_file($relUrl);
                }
                $pdo->prepare('DELETE FROM taller_contenidos WHERE id = ?')->execute([$cont_id]);
            }
        }
        header("Location: $baseUrl/admin/taller.php?id=$taller_id&tab=temario&msg=contenido_eliminado");
        exit;
    }
}

// ── GET: Cargar datos ────────────────────────────────────────────────────────
$stmt = $pdo->prepare('SELECT * FROM talleres WHERE id = ?');
$stmt->execute([$taller_id]);
$taller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$taller) {
    header("Location: $baseUrl/admin/talleres.php");
    exit;
}

$inscripciones = $pdo->prepare('
    SELECT *,
           (SELECT email FROM usuarios WHERE id = inscripciones_talleres.usuario_id) AS cuenta_email,
           (SELECT foto_perfil FROM usuarios WHERE id = inscripciones_talleres.usuario_id) AS cuenta_foto
    FROM inscripciones_talleres
    WHERE taller_id = ?
    ORDER BY created_at DESC
');
$inscripciones->execute([$taller_id]);
$inscripciones = $inscripciones->fetchAll(PDO::FETCH_ASSOC);

$confirmados = 0;
foreach ($inscripciones as $i) {
    if ($i['estado'] === 'confirmada') $confirmados++;
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
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Detalle de Taller | Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;900&family=Forum&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
  <style>
    .tabs-nav {
      display: flex;
      gap: 1rem;
      border-bottom: 1px solid var(--border-dark);
      margin-bottom: 1.5rem;
    }
    .tab-btn {
      background: none;
      border: none;
      color: var(--text-muted);
      font-weight: 600;
      padding: 0.75rem 1rem;
      cursor: pointer;
      border-bottom: 2px solid transparent;
      transition: all 0.2s;
    }
    .tab-btn:hover { color: #fff; }
    .tab-btn.active {
      color: var(--gold);
      border-bottom-color: var(--gold);
    }
    .tab-pane { display: none; }
    .tab-pane.active { display: block; }

    .bloque-wrap {
      background: rgba(255,255,255,0.02);
      border: 1px solid var(--border-dark);
      border-radius: 0.75rem;
      margin-bottom: 1rem;
      overflow: hidden;
    }
    .bloque-header {
      padding: 1rem;
      background: rgba(255,255,255,0.03);
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
    }
    .bloque-header:hover { background: rgba(255,255,255,0.05); }
    .bloque-body {
      padding: 1rem;
      border-top: 1px solid var(--border-dark);
    }
    
    .recurso-row {
      display: flex;
      align-items: center;
      padding: 0.75rem;
      background: rgba(0,0,0,0.2);
      border-radius: 0.5rem;
      margin-bottom: 0.5rem;
      gap: 1rem;
    }
    
    .add-content-form {
      display: none;
      padding: 1rem;
      background: rgba(255,255,255,0.02);
      border-radius: 0.5rem;
      margin-top: 1rem;
      border: 1px dashed var(--border-subtle);
    }
    .add-content-form.open { display: block; }
  </style>
</head>
<body style="background:var(--background-dark);">

<div class="admin-layout">
  <?php mitos_admin_sidebar('talleres'); ?>

  <main class="admin-content" style="padding:2rem;">
    <!-- Migas de pan y encabeado rápido -->
    <a href="<?php echo $baseUrl; ?>/admin/talleres.php" style="color:var(--primary); font-size:0.9rem; display:inline-flex; align-items:center; gap:0.25rem; margin-bottom:1.5rem; text-decoration:none; font-weight:600;">
        <span class="material-symbols-outlined" style="font-size:1rem;">arrow_back</span> Volver a Talleres
    </a>

    <!-- Ficha del Curso -->
    <div style="background:var(--surface-card); padding:1.5rem; border-radius:0.75rem; display:flex; gap:1.5rem; margin-bottom:2rem; border:1px solid var(--border-subtle); flex-wrap:wrap;">
        <?php if (!empty($taller['imagen_url'])): ?>
            <img src="<?php echo htmlspecialchars($taller['imagen_url']); ?>" alt="Portada" style="width:160px; aspect-ratio:4/3; object-fit:cover; border-radius:0.5rem;"/>
        <?php else: ?>
            <div style="width:160px; aspect-ratio:4/3; background:var(--surface-dark); display:flex; align-items:center; justify-content:center; border-radius:0.5rem;">
                <span class="material-symbols-outlined" style="font-size:3rem; color:var(--text-muted);">imagesmode</span>
            </div>
        <?php endif; ?>

        <div style="flex:1;">
            <p style="text-transform:uppercase; font-size:0.75rem; letter-spacing:0.05em; font-weight:800; color:var(--text-muted); margin:0 0 0.25rem;">Gestión de Taller</p>
            <h1 style="font-size:1.6rem; font-weight:800; color:#fff; margin:0 0 0.5rem;"><?php echo htmlspecialchars($taller['titulo']); ?></h1>
            <p style="color:#aaa; font-size:0.9rem; margin-bottom:1rem; line-height:1.4;">
                <span style="font-weight:600; color:#fff;">Instructor:</span> <?php echo htmlspecialchars($taller['instructor'] ?: 'No asignado'); ?><br/>
                <span style="font-weight:600; color:#fff;">Modalidad:</span> <?php echo htmlspecialchars(ucfirst($taller['modalidad'])); ?> | 
                <span style="font-weight:600; color:#fff;">Precio:</span> $<?php echo number_format((float)$taller['precio'], 2); ?>
            </p>

            <div style="display:flex; gap:1rem; margin-top:auto;">
                <div style="background:rgba(0,0,0,0.3); padding:0.5rem 1rem; border-radius:0.5rem; border:1px solid rgba(255,255,255,0.05);">
                    <div style="font-size:0.75rem; color:var(--text-muted);">Inscripciones Totales</div>
                    <div style="font-size:1.25rem; font-weight:800; color:#fff;"><?php echo count($inscripciones); ?></div>
                </div>
                <div style="background:rgba(0,0,0,0.3); padding:0.5rem 1rem; border-radius:0.5rem; border:1px solid rgba(255,255,255,0.05);">
                    <div style="font-size:0.75rem; color:var(--text-muted);">Alumnos Confirmados</div>
                    <div style="font-size:1.25rem; font-weight:800; color:var(--primary);"><?php echo $confirmados; ?> <?php echo $taller['cupo_maximo'] ? '/ ' . $taller['cupo_maximo'] : ''; ?></div>
                </div>
            </div>
        </div>
    </div>


    <!-- Tabs -->
    <div class="tabs-nav">
        <button type="button" class="tab-btn <?php echo $actTab === 'inscritos' ? 'active' : ''; ?>" onclick="switchTab('inscritos')">Alumnos / Inscripciones</button>
        <button type="button" class="tab-btn <?php echo $actTab === 'temario' ? 'active' : ''; ?>" onclick="switchTab('temario')">Temario y Contenidos LMS</button>
    </div>

    <!-- PESTAÑA: Inscritos -->
    <div id="tab-inscritos" class="tab-pane <?php echo $actTab === 'inscritos' ? 'active' : ''; ?>">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Alumno</th>
              <th>Contacto</th>
              <th>Fecha Solicitud</th>
              <th>Experiencia Declarada</th>
              <th>Estado Actual</th>
              <th style="min-width: 140px;">Acción Rápida</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($inscripciones as $i): ?>
              <tr>
                <td style="color:#fff; font-weight:600; display:flex; align-items:center; gap:0.75rem;">
                    <?php if (!empty($i['cuenta_foto'])): ?>
                        <img src="<?php echo htmlspecialchars($baseUrl . '/' . $i['cuenta_foto']); ?>" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:1px solid var(--gold);" alt="Avatar">
                    <?php else: ?>
                        <div style="width:36px; height:36px; border-radius:50%; background:rgba(255,255,255,0.1); border:1px solid var(--border-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <span class="material-symbols-outlined" style="font-size:1.2rem; color:#aaa;">person</span>
                        </div>
                    <?php endif; ?>
                    <div>
                        <?php echo htmlspecialchars($i['nombre_completo']); ?>
                        <span style="display:block; font-size:0.75rem; color:var(--text-muted); font-weight:400;"><?php echo htmlspecialchars($i['cuenta_email'] ?: 'No vinculada'); ?></span>
                    </div>
                </td>
                <td style="font-size:0.85rem;">
                    <a href="mailto:<?php echo htmlspecialchars($i['email']); ?>" style="color:var(--primary); display:block;"><?php echo htmlspecialchars($i['email']); ?></a>
                    <span style="display:inline-block; margin-top:0.25rem; color:#aaa;"><?php echo htmlspecialchars($i['telefono'] ?: 'Sin teléfono'); ?></span>
                </td>
                <td style="color:var(--text-muted); font-size:0.85rem;">
                    <?php echo date('d/M/Y H:i', strtotime($i['created_at'])); ?>
                </td>
                <td style="color:#ccc; font-size:0.85rem; max-width: 200px;">
                    <div style="overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">
                        <?php echo htmlspecialchars($i['experiencia'] ?: 'N/A'); ?>
                    </div>
                </td>
                <td>
                    <?php if ($i['estado'] === 'confirmada'): ?>
                        <span class="badge badge-green" title="Pagado / Acreditado directamente">Confirmada</span>
                    <?php elseif ($i['estado'] === 'cancelada'): ?>
                        <span class="badge badge-maroon">Cancelada</span>
                    <?php elseif ($i['estado'] === 'aprobado_para_pago'): ?>
                        <span class="badge" style="background:var(--gold); color:#000;">Aprobado(Falta Pago)</span>
                    <?php else: ?>
                         <span class="badge" style="background:#555;">Pendiente</span>
                    <?php endif; ?>
                </td>
                <td>
                  <form method="post" action="">
                    <input type="hidden" name="accion" value="cambiar_estado"/>
                    <input type="hidden" name="inscripcion_id" value="<?php echo (int)$i['id']; ?>"/>
                    <select name="estado" onchange="this.form.submit()" style="padding:0.35rem; background:rgba(255,255,255,0.05); border:1px solid var(--border-dark); border-radius:0.4rem; color:#fff; font-size:0.85rem; width:100%;">
                        <option value="pendiente" <?php echo $i['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente de revisión</option>
                        <option value="aprobado_para_pago" <?php echo $i['estado'] === 'aprobado_para_pago' ? 'selected' : ''; ?>>Aprobado para Pago</option>
                        <option value="confirmada" <?php echo $i['estado'] === 'confirmada' ? 'selected' : ''; ?>>Confirmada / Cobro Externo</option>
                        <option value="cancelada" <?php echo $i['estado'] === 'cancelada' ? 'selected' : ''; ?>>Cancelada / Rechazar</option>
                    </select>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php if (empty($inscripciones)): ?>
          <p style="color:var(--text-muted); padding:2rem 0; text-align:center;">No hay solicitudes de inscripción para este taller aún.</p>
        <?php endif; ?>
    </div>


    <!-- PESTAÑA: Temario -->
    <div id="tab-temario" class="tab-pane <?php echo $actTab === 'temario' ? 'active' : ''; ?>">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <p style="color:#aaa; font-size:0.95rem;">Diseña la estructura de tu curso. Los alumnos confirmados podrán ver esto en su panel.</p>
            <button class="btn-gold btn-sm" onclick="toggleForm('form-nuevo-bloque')">
                <span class="material-symbols-outlined">add</span> Añadir Nuevo Bloque/Módulo
            </button>
        </div>

        <!-- Formulario Crear Bloque -->
        <div id="form-nuevo-bloque" style="display:none; background:var(--surface-card); padding:1rem; border-radius:0.5rem; margin-bottom:1.5rem; border:1px solid var(--border-dark);">
            <form method="post" action="">
                <input type="hidden" name="accion" value="crear_bloque"/>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div>
                        <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:0.25rem;">Título del Bloque (Ej: Módulo 1: Inicio)</label>
                        <input type="text" name="titulo" required style="width:100%;">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:0.25rem;">Descripción Opcional</label>
                        <input type="text" name="descripcion" style="width:100%;">
                    </div>
                </div>
                <div style="margin-top:1rem;">
                    <button class="btn-primary btn-sm" type="submit">Guardar Bloque</button>
                    <button class="btn-secondary btn-sm" type="button" onclick="toggleForm('form-nuevo-bloque')">Cancelar</button>
                </div>
            </form>
        </div>

        <!-- Listado de Bloques -->
        <?php foreach ($bloques as $idx => $b): ?>
            <div class="bloque-wrap">
                <div class="bloque-header" onclick="toggleBloqueBody(<?php echo $b['id']; ?>)">
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <span class="material-symbols-outlined" style="color:var(--text-muted);">view_week</span>
                        <div>
                            <h3 style="margin:0; font-size:1rem; color:#fff; font-weight:700;"><?php echo htmlspecialchars($b['titulo']); ?></h3>
                            <?php if(!empty($b['descripcion'])): ?>
                                <p style="margin:0; font-size:0.8rem; color:#888;"><?php echo htmlspecialchars($b['descripcion']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display:flex; gap:0.5rem;" onclick="event.stopPropagation()">
                        <form method="post" action="" onsubmit="return confirm('¿Seguro que deseas eliminar el bloque y todo su contenido?');" style="margin:0;">
                            <input type="hidden" name="accion" value="eliminar_bloque">
                            <input type="hidden" name="bloque_id" value="<?php echo $b['id']; ?>">
                            <button class="action-btn action-btn-delete" title="Borrar bloque" style="padding:0.4rem; background:rgba(239, 68, 68, 0.1); border-radius:4px;"><span class="material-symbols-outlined" style="font-size:1.2rem;">delete</span></button>
                        </form>
                    </div>
                </div>

                <div class="bloque-body" id="bloque-body-<?php echo $b['id']; ?>" style="display:block;">
                    <?php 
                    $contenidos = $contenidosPorBloque[$b['id']] ?? []; 
                    foreach ($contenidos as $c): 
                        $icono = $tipoIconos[$c['tipo']] ?? 'article';
                        $color = $tipoColores[$c['tipo']] ?? '#fff';
                    ?>
                        <div id="display-contenido-<?php echo $c['id']; ?>" class="recurso-row">
                            <span class="material-symbols-outlined" style="color:<?php echo $color; ?>; font-size:1.5rem;"><?php echo $icono; ?></span>
                            <div style="flex:1;">
                                <h4 style="margin:0; font-size:0.95rem; color:#fff;"><?php echo htmlspecialchars($c['titulo']); ?></h4>
                                <?php if ($c['tipo'] !== 'texto' && !empty($c['valor_recurso'])): ?>
                                    <a href="<?php echo htmlspecialchars($c['valor_recurso']); ?>" target="_blank" style="font-size:0.8rem; color:var(--primary); text-decoration:none;">
                                        Abrir enlace/recurso <span class="material-symbols-outlined" style="font-size:0.8rem; vertical-align:middle;">open_in_new</span>
                                    </a>
                                <?php elseif ($c['tipo'] === 'texto' && !empty($c['contenido_texto'])): ?>
                                    <p style="font-size:0.8rem; color:#aaa; margin:0.2rem 0;"><?php echo nl2br(htmlspecialchars(substr($c['contenido_texto'], 0, 50) . '...')); ?></p>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex; gap:0.5rem; align-items:center;">
                                <button type="button" style="background:rgba(255,255,255,0.05); color:var(--text-muted); padding:0.4rem; border-radius:0.4rem; border:1px solid var(--border-dark); cursor:pointer; display:flex; align-items:center; justify-content:center;" title="Editar Contenido" onclick="toggleEditContenido(<?php echo $c['id']; ?>)">
                                    <span class="material-symbols-outlined" style="font-size:1.2rem;">edit</span>
                                </button>
                                <form method="post" action="" onsubmit="return confirm('¿Borrar contenido?');" style="margin:0;">
                                    <input type="hidden" name="accion" value="eliminar_contenido">
                                    <input type="hidden" name="contenido_id" value="<?php echo $c['id']; ?>">
                                    <button type="submit" style="background:rgba(239, 68, 68, 0.1); color:#ef4444; padding:0.4rem; border-radius:0.4rem; border:1px solid rgba(239, 68, 68, 0.3); cursor:pointer; display:flex; align-items:center; justify-content:center;" title="Borrar Contenido">
                                        <span class="material-symbols-outlined" style="font-size:1.2rem;">delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Formulario Editar Contenido Inteligente Mismo Lugar -->
                        <div id="editar-contenido-<?php echo $c['id']; ?>" style="display:none; padding:1rem; background:rgba(0,0,0,0.3); border-radius:0.5rem; margin-bottom:0.5rem; border:1px dashed var(--border-subtle);">
                            <form method="post" action="" enctype="multipart/form-data">
                                <input type="hidden" name="accion" value="editar_contenido">
                                <input type="hidden" name="contenido_id" value="<?php echo $c['id']; ?>">
                                <input type="hidden" name="valor_recurso_actual" value="<?php echo htmlspecialchars($c['valor_recurso'] ?? ''); ?>">
                                
                                <div style="display:grid; grid-template-columns:1fr 2fr; gap:1rem; margin-bottom:0.75rem;">
                                    <div>
                                        <label style="display:block; font-size:0.75rem; color:var(--text-muted); margin-bottom:0.25rem;">Tipo de Recurso</label>
                                        <select name="tipo" required style="width:100%; padding:0.4rem; border-radius:0.3rem;"
                                                onchange="updateContenidoFormEdit(this, <?php echo $c['id']; ?>)">
                                            <option value="video_link" <?php echo $c['tipo'] === 'video_link' ? 'selected' : ''; ?>>Enlace a Video</option>
                                            <option value="zoom" <?php echo $c['tipo'] === 'zoom' ? 'selected' : ''; ?>>Link de Videollamada</option>
                                            <option value="archivo" <?php echo $c['tipo'] === 'archivo' ? 'selected' : ''; ?>>Archivo Físico</option>
                                            <option value="texto" <?php echo $c['tipo'] === 'texto' ? 'selected' : ''; ?>>Instrucciones / Texto</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="display:block; font-size:0.75rem; color:var(--text-muted); margin-bottom:0.25rem;">Título del Recurso</label>
                                        <input type="text" name="titulo" required style="width:100%; padding:0.4rem;" value="<?php echo htmlspecialchars($c['titulo']); ?>">
                                    </div>
                                </div>

                                <div id="edit-fila-recurso-<?php echo $c['id']; ?>" style="margin-top:0.75rem; <?php echo $c['tipo']==='texto' ? 'display:none;' : 'display:block;'; ?>">
                                    <label style="display:block; font-size:0.75rem; color:var(--text-muted); margin-bottom:0.25rem;">Enlace / URL</label>
                                    <input type="url" name="valor_recurso" id="edit-input-url-<?php echo $c['id']; ?>" style="width:100%; padding:0.4rem; <?php echo $c['tipo']==='archivo' ? 'display:none;' : 'display:block;'; ?>" value="<?php echo htmlspecialchars($c['valor_recurso'] ?? ''); ?>">
                                    
                                    <div id="edit-input-file-<?php echo $c['id']; ?>" style=" <?php echo $c['tipo']==='archivo' ? 'display:block;' : 'display:none;'; ?>">
                                        <?php if($c['tipo']==='archivo' && !empty($c['valor_recurso'])): ?>
                                            <p style="font-size:0.8rem; color:var(--gold); margin:0 0 0.5rem;">Archivo actual válido. Selecciona otro sólo para reemplazarlo.</p>
                                        <?php endif; ?>
                                        <input type="file" name="file_recurso" style="width:100%;">
                                    </div>
                                </div>

                                <div id="edit-fila-texto-<?php echo $c['id']; ?>" style="margin-top:0.75rem; <?php echo $c['tipo']==='texto' ? 'display:block;' : 'display:none;'; ?>">
                                    <label style="display:block; font-size:0.75rem; color:var(--text-muted); margin-bottom:0.25rem;">Texto / Instrucciones</label>
                                    <textarea name="contenido_texto" id="edit-input-texto-<?php echo $c['id']; ?>" style="width:100%; padding:0.4rem; border-radius:0.3rem;" rows="4" placeholder="Escribe tu contenido aquí..."><?php echo htmlspecialchars($c['contenido_texto'] ?? ''); ?></textarea>
                                </div>

                                <div style="margin-top:1rem;">
                                    <button type="submit" class="btn-primary btn-sm">Actualizar Cambios</button>
                                    <button type="button" class="btn-secondary btn-sm" onclick="toggleEditContenido(<?php echo $c['id']; ?>)">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($contenidos)): ?>
                        <p style="color:#666; font-size:0.85rem; margin-bottom:1rem;">Aún no hay contenidos en este bloque.</p>
                    <?php endif; ?>

                    <!-- BOTÓN PRINCIPAL PARA AÑADIR RECURSO -->
                    <button class="btn-gold btn-sm" style="margin-top:0.5rem; display:inline-flex; align-items:center; gap:0.25rem;" onclick="toggleForm('form-contenido-<?php echo $b['id']; ?>')">
                        <span class="material-symbols-outlined" style="font-size:1rem;">add</span> Añadir Recurso de Estudio
                    </button>

                    <!-- Formulario Añadir Contenido -->
                    <div id="form-contenido-<?php echo $b['id']; ?>" class="add-content-form">
                        <form method="post" action="" enctype="multipart/form-data">
                            <input type="hidden" name="accion" value="crear_contenido">
                            <input type="hidden" name="bloque_id" value="<?php echo $b['id']; ?>">
                            
                            <div style="display:grid; grid-template-columns:1fr 2fr; gap:1rem;">
                                <div>
                                    <label style="display:block; font-size:0.75rem; color:var(--text-muted); margin-bottom:0.25rem;">Tipo de Recurso</label>
                                    <select name="tipo" required style="width:100%; padding:0.4rem; border-radius:0.3rem;"
                                            onchange="updateContenidoForm(this, <?php echo $b['id']; ?>)">
                                        <option value="video_link">Enlace a Video (YouTube/Vimeo/Drive)</option>
                                        <option value="zoom">Link de Videollamada (Zoom/Meet)</option>
                                        <option value="archivo">Archivo Físico (Subir PDF/Docs)</option>
                                        <option value="texto">Solo Texto / Instrucciones</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="display:block; font-size:0.75rem; color:var(--text-muted); margin-bottom:0.25rem;">Título del Recurso</label>
                                    <input type="text" name="titulo" required style="width:100%; padding:0.4rem;" placeholder="Ej: Clase Grabada #1">
                                </div>
                            </div>

                            <!-- Input para Links o Archivos -->
                            <div id="fila-recurso-<?php echo $b['id']; ?>" style="margin-top:0.75rem; display:block;">
                                <label style="display:block; font-size:0.75rem; color:var(--text-muted); margin-bottom:0.25rem;">Enlace / URL</label>
                                <input type="url" name="valor_recurso" id="input-url-<?php echo $b['id']; ?>" required style="width:100%; padding:0.4rem;" placeholder="https://...">
                                
                                <input type="file" name="file_recurso" id="input-file-<?php echo $b['id']; ?>" style="width:100%; display:none;">
                            </div>

                            <!-- Input para Textos -->
                            <div id="fila-texto-<?php echo $b['id']; ?>" style="margin-top:0.75rem; display:none;">
                                <label style="display:block; font-size:0.75rem; color:var(--text-muted); margin-bottom:0.25rem;">Texto / Instrucciones</label>
                                <textarea name="contenido_texto" id="input-texto-<?php echo $b['id']; ?>" style="width:100%; padding:0.4rem; border-radius:0.3rem;" rows="4" placeholder="Escribe instrucciones aquí..."></textarea>
                            </div>

                            <div style="margin-top:1rem;">
                                <button type="submit" class="btn-primary btn-sm">Añadir al Bloque</button>
                                <button type="button" class="btn-secondary btn-sm" onclick="toggleForm('form-contenido-<?php echo $b['id']; ?>')">Cancelar</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($bloques)): ?>
          <div style="text-align:center; padding:3rem 1rem;">
            <span class="material-symbols-outlined" style="font-size:3rem; color:var(--text-muted);">view_agenda</span>
            <p style="color:var(--text-muted); margin-top:0.5rem;">Comienza diseñando el programa de tu taller creando tu primer bloque.</p>
          </div>
        <?php endif; ?>

    </div>
    
  </main>
</div>

<script>
// Switch de Pestañas
function switchTab(tabId) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
    
    // Activar botón actual
    event.currentTarget.classList.add('active');
    // Activar pane
    document.getElementById('tab-' + tabId).classList.add('active');
}

// Toggle Forms
function toggleForm(id) {
    const el = document.getElementById(id);
    if(el.classList.contains('open') || el.style.display === 'block') {
        el.classList.remove('open');
        el.style.display = 'none';
    } else {
        el.classList.add('open');
        el.style.display = 'block';
    }
}

// Editar contenido Toggle
function toggleEditContenido(id) {
    const displayE = document.getElementById('display-contenido-' + id);
    const formE = document.getElementById('editar-contenido-' + id);
    if(formE.style.display === 'none' || formE.style.display === '') {
        formE.style.display = 'block';
        displayE.style.display = 'none';
    } else {
        formE.style.display = 'none';
        displayE.style.display = 'flex';
    }
}

// Abrir/Cerrar acordeón de bloque
function toggleBloqueBody(id) {
    const el = document.getElementById('bloque-body-' + id);
    if(el.style.display === 'none' || el.style.display === '') {
        el.style.display = 'block';
    } else {
        el.style.display = 'none';
    }
}

// Cambiar tipo de input para Nuevo Contenido
function updateContenidoForm(selectEl, blockId) {
    const tipo = selectEl.value;
    const urlInput = document.getElementById('input-url-' + blockId);
    const fileInput = document.getElementById('input-file-' + blockId);
    const recFila = document.getElementById('fila-recurso-' + blockId);
    const texFila = document.getElementById('fila-texto-' + blockId);

    if (tipo === 'texto') {
        recFila.style.display = 'none';
        texFila.style.display = 'block';
        urlInput.removeAttribute('required');
        fileInput.removeAttribute('required');
    } else if (tipo === 'archivo') {
        recFila.style.display = 'block';
        texFila.style.display = 'none';
        urlInput.style.display = 'none';
        fileInput.style.display = 'block';
        urlInput.removeAttribute('required');
        fileInput.setAttribute('required', 'true');
    } else {
        recFila.style.display = 'block';
        texFila.style.display = 'none';
        urlInput.style.display = 'block';
        fileInput.style.display = 'none';
        fileInput.removeAttribute('required');
        urlInput.setAttribute('required', 'true');
    }
}

// Cambiar tipo de input para Edición
function updateContenidoFormEdit(selectEl, cid) {
    const tipo = selectEl.value;
    const urlInput = document.getElementById('edit-input-url-' + cid);
    const fileWrapper = document.getElementById('edit-input-file-' + cid);
    const recFila = document.getElementById('edit-fila-recurso-' + cid);
    const texFila = document.getElementById('edit-fila-texto-' + cid);

    if (tipo === 'texto') {
        recFila.style.display = 'none';
        texFila.style.display = 'block';
        urlInput.removeAttribute('required');
    } else if (tipo === 'archivo') {
        recFila.style.display = 'block';
        texFila.style.display = 'none';
        urlInput.style.display = 'none';
        fileWrapper.style.display = 'block';
        urlInput.removeAttribute('required');
    } else {
        recFila.style.display = 'block';
        texFila.style.display = 'none';
        urlInput.style.display = 'block';
        fileWrapper.style.display = 'none';
        urlInput.setAttribute('required', 'true');
    }
}
</script>
</body>
</html>
