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
        $media_id   = (int)($_POST['media_id'] ?? 0);
        $merc_id    = (int)($_POST['mercaderia_id'] ?? 0);
        if ($media_id > 0) {
            $row = $pdo->prepare('SELECT ruta FROM mercaderia_media WHERE id = ?');
            $row->execute([$media_id]);
            $m = $row->fetch();
            if ($m) {
                mitos_delete_media_file($m['ruta']);
                $pdo->prepare('DELETE FROM mercaderia_media WHERE id = ?')->execute([$media_id]);
            }
        }
        header('Location: ' . $baseUrl . '/admin/mercaderia.php' . ($merc_id ? '?editar=' . $merc_id : ''));
        exit;
    }

    // ─── Eliminar producto completo ────────────────────────────────
    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $medias = $pdo->prepare('SELECT ruta FROM mercaderia_media WHERE mercaderia_id = ?');
            $medias->execute([$id]);
            foreach ($medias->fetchAll() as $m) {
                mitos_delete_media_file($m['ruta']);
            }
            $pdo->prepare('DELETE FROM mercaderia WHERE id = ?')->execute([$id]);
        }
        header('Location: ' . $baseUrl . '/admin/mercaderia.php');
        exit;
    }

    // ─── Guardar (nueva o editar) ──────────────────────────────────
    if ($accion === 'guardar') {
        $id          = (int)($_POST['id'] ?? 0);
        $nombre      = trim((string)($_POST['nombre'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        $precio      = (float)($_POST['precio'] ?? 0);
        $stock       = (int)($_POST['stock'] ?? 0);
        $activo      = isset($_POST['activo']) ? 1 : 0;
        $uploadErrors = [];

        if ($nombre !== '' && $precio >= 0) {
            $portada_url_nueva = null;

            // Procesar portada si se envió
            if (!empty($_FILES['portada']['name']) && $_FILES['portada']['error'] !== UPLOAD_ERR_NO_FILE) {
                $resPortada = mitos_upload_media($_FILES['portada'], 'merc_portada');
                $uploadErrors = array_merge($uploadErrors, $resPortada['errores']);
                if (!empty($resPortada['rutas'])) {
                    $portada_url_nueva = $resPortada['rutas'][0]['ruta'];
                }
            }

            if ($id > 0) {
                if ($portada_url_nueva) {
                    $rowOld = $pdo->prepare('SELECT imagen_url FROM mercaderia WHERE id = ?');
                    $rowOld->execute([$id]);
                    $viejaPortada = $rowOld->fetchColumn();
                    if ($viejaPortada) mitos_delete_media_file($viejaPortada);
                    
                    $pdo->prepare('UPDATE mercaderia SET nombre=?, descripcion=?, precio=?, stock=?, activo=?, imagen_url=? WHERE id=?')
                        ->execute([$nombre, $descripcion ?: null, $precio, $stock, $activo, $portada_url_nueva, $id]);
                } else {
                    $pdo->prepare('UPDATE mercaderia SET nombre=?, descripcion=?, precio=?, stock=?, activo=? WHERE id=?')
                        ->execute([$nombre, $descripcion ?: null, $precio, $stock, $activo, $id]);
                }
            } else {
                $pdo->prepare('INSERT INTO mercaderia (nombre, descripcion, precio, stock, activo, imagen_url) VALUES (?,?,?,?,?,?)')
                    ->execute([$nombre, $descripcion ?: null, $precio, $stock, $activo, $portada_url_nueva]);
                $id = (int)$pdo->lastInsertId();
            }

            // Subir archivos multimedia adicionales (galería)
            if (!empty($_FILES['media']['name'][0]) || (!is_array($_FILES['media']['name']) && $_FILES['media']['name'] !== '')) {
                $resultado = mitos_upload_media($_FILES['media'], 'merc');
                $uploadErrors = array_merge($uploadErrors, $resultado['errores']);
                $orden = (int)$pdo->query("SELECT COALESCE(MAX(orden),0) FROM mercaderia_media WHERE mercaderia_id = $id")->fetchColumn();
                foreach ($resultado['rutas'] as $item) {
                    $orden++;
                    $pdo->prepare('INSERT INTO mercaderia_media (mercaderia_id, tipo, ruta, orden) VALUES (?,?,?,?)')
                        ->execute([$id, $item['tipo'], $item['ruta'], $orden]);
                }
            }
        }

        if (empty($uploadErrors)) {
            header('Location: ' . $baseUrl . '/admin/mercaderia.php');
            exit;
        }
        $editar = null;
        if ($id > 0) {
            $s = $pdo->prepare('SELECT * FROM mercaderia WHERE id = ?');
            $s->execute([$id]);
            $editar = $s->fetch();
        }
    }
}

// ── GET ───────────────────────────────────────────────────────────────────────
$items = $pdo->query('SELECT id, nombre, descripcion, imagen_url, precio, stock, activo FROM mercaderia ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);

if (!isset($editar)) {
    $editar = null;
    if (isset($_GET['editar'])) {
        $id = (int)$_GET['editar'];
        $s  = $pdo->prepare('SELECT * FROM mercaderia WHERE id = ?');
        $s->execute([$id]);
        $editar = $s->fetch() ?: null;
    }
}

$editarMedia = [];
if ($editar) {
    $sm = $pdo->prepare('SELECT id, tipo, ruta, orden FROM mercaderia_media WHERE mercaderia_id = ? ORDER BY orden, id');
    $sm->execute([(int)$editar['id']]);
    $editarMedia = $sm->fetchAll();
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mercancía | Admin Mitos Escénicos</title>
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
  <?php mitos_admin_sidebar('mercaderia'); ?>

  <main class="admin-content" style="padding:2rem;">
    <div style="margin-bottom:2rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
      <div>
        <h1 style="font-size:1.8rem; font-weight:800; color:#fff; margin:0 0 0.3rem;">Mercancía</h1>
        <p style="color:var(--text-muted); font-size:0.9rem;">Productos de la tienda: stock, precios y visibilidad.</p>
      </div>
      <a href="<?php echo $baseUrl; ?>/admin/mercaderia.php?nueva=1" class="btn-gold btn-sm">
        <span class="material-symbols-outlined" style="font-size:1rem;">add</span> Agregar producto
      </a>
    </div>

  <?php if (!empty($uploadErrors)): ?>
    <div class="alert alert-error" style="margin-bottom:1rem;">
      <?php foreach ($uploadErrors as $er): ?>
        <p style="margin:0.2rem 0;"><?php echo htmlspecialchars($er); ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($editar || isset($_GET['nueva'])): ?>
    <div class="surface-card" style="max-width:560px; margin-bottom:2rem;">
      <h2 style="font-size:1.1rem; color:#fff; margin-bottom:1.25rem;"><?php echo $editar ? 'Editar producto' : 'Agregar producto'; ?></h2>

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
                <input type="hidden" name="mercaderia_id" value="<?php echo (int)$editar['id']; ?>"/>
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
          <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Nombre *</label>
          <input type="text" name="nombre" required value="<?php echo htmlspecialchars($editar['nombre'] ?? ''); ?>" style="width:100%;"/>
        </div>

        <div>
          <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Descripción</label>
          <textarea name="descripcion" rows="3" style="width:100%;"><?php echo htmlspecialchars($editar['descripcion'] ?? ''); ?></textarea>
        </div>

        <div>
          <label style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted); margin-bottom:0.5rem;">Portada del producto</label>
          <?php if ($editar && $editar['imagen_url']): ?>
            <img id="portadaPreview" src="<?php echo htmlspecialchars(mitos_url($editar['imagen_url'])); ?>" alt="Portada" style="height:140px; border-radius:0.4rem; object-fit:cover; border:1px solid rgba(255,255,255,0.1); margin-bottom:0.5rem; display:block;"/>
          <?php else: ?>
            <img id="portadaPreview" src="" alt="" style="height:140px; border-radius:0.4rem; object-fit:cover; border:1px solid rgba(255,255,255,0.1); margin-bottom:0.5rem; display:none;"/>
          <?php endif; ?>
          <input type="file" id="portadaInput" name="portada" accept="image/*" onchange="previewPortada(this)" style="width:100%; background:rgba(255,255,255,0.05); border:1px dashed var(--border-dark); padding:0.5rem; color:var(--text-muted);"/>
        </div>

        <!-- Upload de imágenes / videos -->
        <div>
          <label style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted); margin-bottom:0.5rem;">
            <?php echo ($editar && !empty($editarMedia)) ? 'Agregar más imágenes/videos' : 'Imágenes y/o videos'; ?>
          </label>
          <label class="upload-area" for="mediaInputMerc">
            <input type="file" id="mediaInputMerc" name="media[]" multiple accept="image/*,video/mp4,video/webm,video/quicktime" onchange="previewFiles(this)"/>
            <span class="material-symbols-outlined" style="font-size:2rem; color:var(--primary); display:block; margin-bottom:0.5rem;">cloud_upload</span>
            <p style="color:var(--text-muted); font-size:0.85rem; margin:0;">Haz clic o arrastra archivos aquí<br><small>Imágenes (máx 8 MB) · Videos mp4/webm (máx 50 MB) · Múltiples permitidos</small></p>
          </label>
          <div class="upload-preview" id="uploadPreview"></div>
        </div>

        <div>
          <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Precio *</label>
          <input type="number" name="precio" required min="0" step="0.01" value="<?php echo $editar ? (float)$editar['precio'] : ''; ?>" style="width:100%;"/>
        </div>

        <div>
          <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Stock</label>
          <input type="number" name="stock" min="0" value="<?php echo $editar ? (int)$editar['stock'] : 0; ?>"/>
        </div>

        <div>
          <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
            <input type="checkbox" name="activo" value="1" <?php echo ($editar && (int)$editar['activo'] === 1) || !$editar ? 'checked' : ''; ?>/>
            <span style="color:#fff;">Visible en tienda</span>
          </label>
        </div>

        <div style="display:flex; gap:0.75rem; margin-top:0.5rem;">
          <button type="submit" class="btn-primary">Guardar</button>
          <a href="<?php echo $baseUrl; ?>/admin/mercaderia.php" class="btn-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <table class="admin-table">
    <thead><tr>
      <th>Nombre</th>
      <th style="text-align:right;">Precio</th>
      <th style="text-align:right;">Stock</th>
      <th style="text-align:right;">Visible</th>
      <th style="text-align:right;">Acciones</th>
    </tr></thead>
    <tbody>
      <?php foreach ($items as $m): ?>
        <tr>
          <td style="color:#fff; font-weight:600;"><?php echo htmlspecialchars($m['nombre']); ?></td>
          <td style="text-align:right; color:var(--gold); font-weight:700;">$<?php echo number_format((float)$m['precio'], 2); ?></td>
          <td style="text-align:right;"><?php echo (int)$m['stock']; ?></td>
          <td style="text-align:right;">
            <?php echo (int)$m['activo'] === 1 ? '<span class="badge badge-green">Sí</span>' : '<span class="badge" style="background:rgba(255,255,255,0.07);color:var(--text-muted);">No</span>'; ?>
          </td>
          <td style="text-align:right;">
            <div class="table-actions" style="justify-content:flex-end;">
              <a href="<?php echo $baseUrl; ?>/admin/mercaderia.php?editar=<?php echo (int)$m['id']; ?>" class="action-btn action-btn-edit">
                <span class="material-symbols-outlined" style="font-size:1.1rem;">edit</span>
              </a>
              <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar este producto y sus archivos?');">
                <input type="hidden" name="accion" value="eliminar"/>
                <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>"/>
                <button type="submit" class="action-btn action-btn-delete"><span class="material-symbols-outlined" style="font-size:1.1rem;">delete</span></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($items)): ?>
    <p style="color:var(--text-muted); padding:2rem 0;">No hay productos. Agrega uno desde el botón anterior.</p>
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
