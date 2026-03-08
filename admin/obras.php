<?php
require_once __DIR__ . '/../php/init.php';
require_once __DIR__ . '/../php/layout.php';
require_once __DIR__ . '/../php/upload.php';
mitos_requiere_admin();

$pdo = mitos_pdo();
$baseUrl = rtrim(mitos_url(''), '/');

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // ─── Eliminar un archivo de media ─────────────────────────────
    if ($accion === 'eliminar_media') {
        $media_id = (int)($_POST['media_id'] ?? 0);
        $obra_id  = (int)($_POST['obra_id'] ?? 0);
        if ($media_id > 0) {
            $row = $pdo->prepare('SELECT ruta FROM obra_media WHERE id = ?');
            $row->execute([$media_id]);
            $m = $row->fetch();
            if ($m) {
                mitos_delete_media_file($m['ruta']);
                $pdo->prepare('DELETE FROM obra_media WHERE id = ?')->execute([$media_id]);
            }
        }
        header('Location: ' . $baseUrl . '/admin/obras.php' . ($obra_id ? '?editar=' . $obra_id : ''));
        exit;
    }

    // ─── Eliminar obra completa ────────────────────────────────────
    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->beginTransaction();
                // Eliminar archivos físicos de obra_media
                $medias = $pdo->prepare('SELECT ruta FROM obra_media WHERE obra_id = ?');
                $medias->execute([$id]);
                foreach ($medias->fetchAll() as $m) {
                    mitos_delete_media_file($m['ruta']);
                }
                // Eliminar items de órdenes → funciones → obra
                $pdo->prepare('
                    DELETE oi FROM orden_items oi
                    INNER JOIN funciones f ON f.id = oi.funcion_id
                    WHERE f.obra_id = ?
                ')->execute([$id]);
                $pdo->prepare('DELETE FROM funciones WHERE obra_id = ?')->execute([$id]);
                $pdo->prepare('DELETE FROM obras WHERE id = ?')->execute([$id]);
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $errorEliminar = 'No se pudo eliminar la obra: ' . $e->getMessage();
            }
        }
        if (empty($errorEliminar)) {
            header('Location: ' . $baseUrl . '/admin/obras.php');
            exit;
        }
    }

    // ─── Toggle venta ──────────────────────────────────────────────
    if ($accion === 'toggle_venta') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT venta_boletos_habilitada FROM obras WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) {
                $nuevo = (int)$row['venta_boletos_habilitada'] === 1 ? 0 : 1;
                $pdo->prepare('UPDATE obras SET venta_boletos_habilitada = ? WHERE id = ?')->execute([$nuevo, $id]);
            }
        }
        header('Location: ' . $baseUrl . '/admin/obras.php');
        exit;
    }

    // ─── Guardar (nueva o editar) ──────────────────────────────────
    if ($accion === 'guardar') {
        $id                      = (int)($_POST['id'] ?? 0);
        $titulo                  = trim((string)($_POST['titulo'] ?? ''));
        $descripcion             = trim((string)($_POST['descripcion'] ?? ''));
        $duracion_min            = (int)($_POST['duracion_min'] ?? 0);
        $venta_boletos_habilitada = isset($_POST['venta_boletos_habilitada']) ? 1 : 0;
        $tipo_representacion     = (isset($_POST['tipo_representacion']) && $_POST['tipo_representacion'] === 'un_dia') ? 'un_dia' : 'temporada';
        $temporada               = trim((string)($_POST['temporada'] ?? ''));
        $uploadErrors            = [];

        if ($titulo !== '') {
            $portada_url_nueva = null;

            // Procesar portada si se envió
            if (!empty($_FILES['portada']['name']) && $_FILES['portada']['error'] !== UPLOAD_ERR_NO_FILE) {
                $resPortada = mitos_upload_media($_FILES['portada'], 'portada');
                $uploadErrors = array_merge($uploadErrors, $resPortada['errores']);
                if (!empty($resPortada['rutas'])) {
                    $portada_url_nueva = $resPortada['rutas'][0]['ruta'];
                }
            }

            if ($id > 0) {
                if ($portada_url_nueva) {
                    $rowOld = $pdo->prepare('SELECT imagen_url FROM obras WHERE id = ?');
                    $rowOld->execute([$id]);
                    $viejaPortada = $rowOld->fetchColumn();
                    if ($viejaPortada) mitos_delete_media_file($viejaPortada);
                    
                    $pdo->prepare('UPDATE obras SET titulo=?, descripcion=?, duracion_min=?, venta_boletos_habilitada=?, tipo_representacion=?, temporada=?, imagen_url=? WHERE id=?')
                        ->execute([$titulo, $descripcion ?: null, $duracion_min ?: null, $venta_boletos_habilitada, $tipo_representacion, $temporada ?: null, $portada_url_nueva, $id]);
                } else {
                    $pdo->prepare('UPDATE obras SET titulo=?, descripcion=?, duracion_min=?, venta_boletos_habilitada=?, tipo_representacion=?, temporada=? WHERE id=?')
                        ->execute([$titulo, $descripcion ?: null, $duracion_min ?: null, $venta_boletos_habilitada, $tipo_representacion, $temporada ?: null, $id]);
                }
            } else {
                $pdo->prepare('INSERT INTO obras (titulo, descripcion, duracion_min, venta_boletos_habilitada, tipo_representacion, temporada, imagen_url) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$titulo, $descripcion ?: null, $duracion_min ?: null, $venta_boletos_habilitada, $tipo_representacion, $temporada ?: null, $portada_url_nueva]);
                $id = (int)$pdo->lastInsertId();
            }

            // Subir archivos multimedia adicionales (galería)
            if (!empty($_FILES['media']['name'][0]) || (!is_array($_FILES['media']['name']) && $_FILES['media']['name'] !== '')) {
                $resultado = mitos_upload_media($_FILES['media'], 'obra');
                $uploadErrors = array_merge($uploadErrors, $resultado['errores']);
                $orden = (int)$pdo->query("SELECT COALESCE(MAX(orden),0) FROM obra_media WHERE obra_id = $id")->fetchColumn();
                foreach ($resultado['rutas'] as $item) {
                    $orden++;
                    $pdo->prepare('INSERT INTO obra_media (obra_id, tipo, ruta, orden) VALUES (?,?,?,?)')
                        ->execute([$id, $item['tipo'], $item['ruta'], $orden]);
                }
            }
        }

        if (empty($uploadErrors)) {
            header('Location: ' . $baseUrl . '/admin/obras.php');
            exit;
        }
        // Si hay errores de subida, volvemos a mostrar el formulario con los errores
        $editar = null;
        if ($id > 0) {
            $s = $pdo->prepare('SELECT * FROM obras WHERE id = ?');
            $s->execute([$id]);
            $editar = $s->fetch();
        }
    }
}

// ── GET ───────────────────────────────────────────────────────────────────────
$obras = $pdo->query('SELECT id, titulo, descripcion, imagen_url, duracion_min, venta_boletos_habilitada, temporada, tipo_representacion, created_at FROM obras ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

if (!isset($editar)) {
    $editar = null;
    if (isset($_GET['editar'])) {
        $id = (int)$_GET['editar'];
        $s  = $pdo->prepare('SELECT * FROM obras WHERE id = ?');
        $s->execute([$id]);
        $editar = $s->fetch() ?: null;
    }
}

// Media de la obra que se está editando
$editarMedia = [];
if ($editar) {
    $sm = $pdo->prepare('SELECT id, tipo, ruta, orden FROM obra_media WHERE obra_id = ? ORDER BY orden, id');
    $sm->execute([(int)$editar['id']]);
    $editarMedia = $sm->fetchAll();
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Obras | Admin Mitos Escénicos</title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;900&family=Forum&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
  <style>
    .media-grid{display:flex;flex-wrap:wrap;gap:0.75rem;margin-top:0.75rem;}
    .media-thumb{position:relative;width:90px;height:90px;border-radius:0.4rem;overflow:hidden;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);}
    .media-thumb img,.media-thumb video{width:100%;height:100%;object-fit:cover;}
    .media-thumb .del-btn{position:absolute;top:3px;right:3px;background:rgba(180,0,0,0.8);border:none;border-radius:50%;width:20px;height:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;}
    .media-thumb .del-btn span{font-size:14px;color:#fff;}
    .media-tag{position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.65);font-size:0.55rem;text-align:center;padding:2px;color:#E3B04B;font-weight:700;text-transform:uppercase;}
    .upload-area{border:2px dashed rgba(255,255,255,0.15);border-radius:0.5rem;padding:1.5rem;text-align:center;cursor:pointer;transition:border-color 0.2s;}
    .upload-area:hover{border-color:var(--primary);}
    .upload-area input[type=file]{display:none;}
    .upload-preview{display:flex;flex-wrap:wrap;gap:0.5rem;margin-top:0.75rem;}
    .upload-preview-item{width:70px;height:70px;border-radius:0.35rem;object-fit:cover;border:1px solid rgba(255,255,255,0.15);}
  </style>
</head>
<body style="background:var(--background-dark);">

<div class="admin-layout">
  <?php mitos_admin_sidebar('obras'); ?>

  <main class="admin-content" style="padding:2rem;">
    <div style="margin-bottom:2rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
      <div>
        <h1 style="font-size:1.8rem; font-weight:800; color:#fff; margin:0 0 0.3rem;">Obras</h1>
        <p style="color:var(--text-muted); font-size:0.9rem;">Gestiona las obras del catálogo.</p>
      </div>
      <a href="<?php echo $baseUrl; ?>/admin/obras.php?nueva=1" class="btn-gold btn-sm">
        <span class="material-symbols-outlined" style="font-size:1rem;">add</span> Agregar obra
      </a>
    </div>

  <?php if (!empty($uploadErrors)): ?>
    <div class="alert alert-error" style="margin-bottom:1rem;">
      <?php foreach ($uploadErrors as $er): ?>
        <p style="margin:0.2rem 0;"><?php echo htmlspecialchars($er); ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errorEliminar)): ?>
    <div class="alert alert-error" style="margin-bottom:1.5rem;"><?php echo htmlspecialchars($errorEliminar); ?></div>
  <?php endif; ?>

  <?php if ($editar || isset($_GET['nueva'])): ?>
    <div class="surface-card" style="max-width:600px; margin-bottom:2rem;">
      <h2 style="font-size:1.1rem; color:#fff; margin-bottom:1.25rem;"><?php echo $editar ? 'Editar obra' : 'Agregar obra'; ?></h2>

      <?php if ($editar && !empty($editarMedia)): ?>
      <div style="margin-bottom:1.25rem;">
        <label style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted); margin-bottom:0.5rem;">Archivos actuales</label>
        <div class="media-grid">
          <?php foreach ($editarMedia as $m): ?>
            <div class="media-thumb">
              <?php if ($m['tipo'] === 'video'): ?>
                <video src="<?php echo htmlspecialchars(mitos_url($m['ruta'])); ?>" muted playsinline preload="metadata"></video>
              <?php else: ?>
                <img src="<?php echo htmlspecialchars(mitos_url($m['ruta'])); ?>" alt="">
              <?php endif; ?>
              <span class="media-tag"><?php echo $m['tipo']; ?></span>
              <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar este archivo?');">
                <input type="hidden" name="accion" value="eliminar_media"/>
                <input type="hidden" name="media_id" value="<?php echo (int)$m['id']; ?>"/>
                <input type="hidden" name="obra_id"  value="<?php echo (int)$editar['id']; ?>"/>
                <button type="submit" class="del-btn"><span class="material-symbols-outlined">close</span></button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <form method="post" action="" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:1rem;">
        <input type="hidden" name="accion" value="guardar"/>
        <input type="hidden" name="id" value="<?php echo $editar ? (int)$editar['id'] : 0; ?>"/>

        <div>
          <label style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted); margin-bottom:0.5rem;">Portada de la obra</label>
          <?php if ($editar && $editar['imagen_url']): ?>
            <img id="portadaPreview" src="<?php echo htmlspecialchars(mitos_url($editar['imagen_url'])); ?>" alt="Portada" style="height:140px; border-radius:0.4rem; object-fit:cover; border:1px solid rgba(255,255,255,0.1); margin-bottom:0.5rem; display:block;"/>
          <?php else: ?>
            <img id="portadaPreview" src="" alt="" style="height:140px; border-radius:0.4rem; object-fit:cover; border:1px solid rgba(255,255,255,0.1); margin-bottom:0.5rem; display:none;"/>
          <?php endif; ?>
          <input type="file" id="portadaInput" name="portada" accept="image/*" onchange="previewPortada(this)" style="width:100%; background:rgba(255,255,255,0.05); border:1px dashed var(--border-dark); padding:0.5rem; color:var(--text-muted);"/>
        </div>

        <div>
          <label style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted); margin-bottom:0.4rem;">Título *</label>
          <input type="text" name="titulo" required value="<?php echo htmlspecialchars($editar['titulo'] ?? ''); ?>" style="width:100%;"/>
        </div>

        <div>
          <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Descripción</label>
          <textarea name="descripcion" rows="3" style="width:100%;"><?php echo htmlspecialchars($editar['descripcion'] ?? ''); ?></textarea>
        </div>

        <!-- Upload de imágenes / videos -->
        <div>
          <label style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted); margin-bottom:0.5rem;">
            <?php echo ($editar && !empty($editarMedia)) ? 'Agregar más imágenes/videos' : 'Imágenes y/o videos'; ?>
          </label>
          <label class="upload-area" for="mediaInput">
            <input type="file" id="mediaInput" name="media[]" multiple accept="image/*,video/mp4,video/webm,video/quicktime" onchange="previewFiles(this)"/>
            <span class="material-symbols-outlined" style="font-size:2rem; color:var(--primary); display:block; margin-bottom:0.5rem;">cloud_upload</span>
            <p style="color:var(--text-muted); font-size:0.85rem; margin:0;">Haz clic o arrastra archivos aquí<br><small>Imágenes (máx 8 MB) · Videos mp4/webm (máx 50 MB) · Múltiples permitidos</small></p>
          </label>
          <div class="upload-preview" id="uploadPreview"></div>
        </div>

        <div>
          <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Duración (minutos)</label>
          <input type="number" name="duracion_min" min="0" value="<?php echo (int)($editar['duracion_min'] ?? 0); ?>"/>
        </div>

        <div>
          <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Tipo de representación</label>
          <label style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem; cursor:pointer;">
            <input type="radio" name="tipo_representacion" value="temporada" <?php echo (!$editar || ($editar['tipo_representacion'] ?? '') === 'temporada') ? 'checked' : ''; ?>/>
            <span style="color:#fff;">Por temporada</span>
          </label>
          <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
            <input type="radio" name="tipo_representacion" value="un_dia" <?php echo ($editar && ($editar['tipo_representacion'] ?? '') === 'un_dia') ? 'checked' : ''; ?>/>
            <span style="color:#fff;">Solo un día</span>
          </label>
        </div>

        <div>
          <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Temporada (ej. 2024, Primavera)</label>
          <input type="text" name="temporada" value="<?php echo htmlspecialchars($editar['temporada'] ?? ''); ?>" placeholder="Opcional" style="width:100%;"/>
        </div>

        <div>
          <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
            <input type="checkbox" name="venta_boletos_habilitada" value="1" <?php echo ($editar && (int)$editar['venta_boletos_habilitada'] === 1) || !$editar ? 'checked' : ''; ?>/>
            <span style="color:#fff;">Venta de boletos habilitada</span>
          </label>
        </div>

        <div style="display:flex; gap:0.75rem; margin-top:0.5rem;">
          <button type="submit" class="btn-primary">Guardar</button>
          <a href="<?php echo $baseUrl; ?>/admin/obras.php" class="btn-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <table class="admin-table">
    <thead><tr>
      <th>Título</th>
      <th>Tipo</th>
      <th>Temporada</th>
      <th>Venta boletos</th>
      <th style="text-align:right;">Acciones</th>
    </tr></thead>
    <tbody>
      <?php foreach ($obras as $o): ?>
        <tr>
          <td style="color:#fff; font-weight:600;"><?php echo htmlspecialchars($o['titulo']); ?></td>
          <td><?php echo (isset($o['tipo_representacion']) && $o['tipo_representacion'] === 'un_dia') ? 'Un día' : 'Temporada'; ?></td>
          <td><?php echo htmlspecialchars($o['temporada'] ?? '-'); ?></td>
          <td>
            <?php if ((int)$o['venta_boletos_habilitada'] === 1): ?>
              <span class="badge badge-green">Sí</span>
            <?php else: ?>
              <span class="badge" style="background:rgba(255,255,255,0.07); color:var(--text-muted);">No</span>
            <?php endif; ?>
            <form method="post" style="display:inline; margin-left:0.5rem;">
              <input type="hidden" name="accion" value="toggle_venta"/>
              <input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>"/>
              <button type="submit" style="background:none; border:none; color:var(--primary); cursor:pointer; font-size:0.72rem; font-weight:700;"><?php echo (int)$o['venta_boletos_habilitada'] === 1 ? 'Deshabilitar' : 'Habilitar'; ?></button>
            </form>
          </td>
          <td style="text-align:right;">
            <div class="table-actions" style="justify-content:flex-end;">
              <a href="<?php echo $baseUrl; ?>/admin/obras.php?editar=<?php echo (int)$o['id']; ?>" class="action-btn action-btn-edit" title="Editar">
                <span class="material-symbols-outlined" style="font-size:1.1rem;">edit</span>
              </a>
              <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar esta obra y todos sus archivos?');">
                <input type="hidden" name="accion" value="eliminar"/>
                <input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>"/>
                <button type="submit" class="action-btn action-btn-delete" title="Eliminar">
                  <span class="material-symbols-outlined" style="font-size:1.1rem;">delete</span>
                </button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($obras)): ?>
    <p style="color:var(--text-muted); padding:2rem 0;">No hay obras. Agrega una desde el botón anterior.</p>
  <?php endif; ?>

  </main>
</div>

<script>
function previewPortada(input) {
  const prev = document.getElementById('portadaPreview');
  if (input.files && input.files[0]) {
    prev.src = URL.createObjectURL(input.files[0]);
    prev.style.display = 'block';
  }
}

function previewFiles(input) {
  const preview = document.getElementById('uploadPreview');
  preview.innerHTML = '';
  Array.from(input.files).forEach(file => {
    const url = URL.createObjectURL(file);
    let el;
    if (file.type.startsWith('video/')) {
      el = document.createElement('video');
      el.src = url;
      el.muted = true;
      el.preload = 'metadata';
    } else {
      el = document.createElement('img');
      el.src = url;
      el.alt = file.name;
    }
    el.className = 'upload-preview-item';
    preview.appendChild(el);
  });
}
</script>
</body>
</html>
