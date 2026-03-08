<?php
require_once __DIR__ . '/../php/init.php';
require_once __DIR__ . '/../php/layout.php';
require_once __DIR__ . '/../php/upload.php';
mitos_requiere_admin();

$pdo = mitos_pdo();
$baseUrl = rtrim(mitos_url(''), '/');

$rolesLabel = [
    'actor'     => 'Actor / Actriz',
    'director'  => 'Director / Directora',
    'disenador' => 'Diseñador / Escenógrafo',
    'bailarin'  => 'Bailarín / Bailarina',
    'otro'      => 'Otro...'
];

$uploadErrors = [];

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // ─── Eliminar media de la galería ──────────────────────────────
    if ($accion === 'eliminar_media') {
        $media_id = (int)($_POST['media_id'] ?? 0);
        $artista_id  = (int)($_POST['artista_id'] ?? 0);
        if ($media_id > 0) {
            $sm = $pdo->prepare('SELECT ruta FROM artista_media WHERE id = ?');
            $sm->execute([$media_id]);
            $ru = $sm->fetchColumn();
            if ($ru) mitos_delete_media_file($ru);
            $pdo->prepare('DELETE FROM artista_media WHERE id = ?')->execute([$media_id]);
        }
        header('Location: ' . $baseUrl . '/admin/artistas.php?editar=' . $artista_id);
        exit;
    }

    // ─── Eliminar artista ──────────────────────────────────────────
    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $row = $pdo->prepare('SELECT foto_url, portada_url FROM artistas WHERE id = ?');
            $row->execute([$id]);
            $a = $row->fetch();
            if ($a) {
                if ($a['foto_url']) mitos_delete_media_file($a['foto_url']);
                if ($a['portada_url']) mitos_delete_media_file($a['portada_url']);
            }
            
            // Eliminar archivos de la galería
            $meds = $pdo->prepare('SELECT ruta FROM artista_media WHERE artista_id = ?');
            $meds->execute([$id]);
            foreach ($meds as $m) {
                mitos_delete_media_file($m['ruta']);
            }
            
            $pdo->prepare('DELETE FROM artistas WHERE id = ?')->execute([$id]);
        }
        header('Location: ' . $baseUrl . '/admin/artistas.php');
        exit;
    }

    // ─── Guardar artista ───────────────────────────────────────────
    if ($accion === 'guardar') {
        $id          = (int)($_POST['id'] ?? 0);
        $nombre      = trim((string)($_POST['nombre'] ?? ''));
        $rol         = trim((string)($_POST['rol'] ?? 'actor'));
        $rolOtro     = trim((string)($_POST['rol_otro'] ?? ''));
        $especialidad = trim((string)($_POST['especialidad'] ?? ''));
        $biografia   = trim((string)($_POST['biografia'] ?? ''));
        $trayectoria = trim((string)($_POST['trayectoria'] ?? ''));
        $orden       = (int)($_POST['orden'] ?? 0);
        $activo      = isset($_POST['activo']) ? 1 : 0;

        if ($rol === 'otro' && $rolOtro !== '') {
            $rol = $rolOtro;
        }

        if ($nombre !== '') {
            $foto_url_nueva = null;
            $portada_url_nueva = null;

            // Subir foto si se seleccionó
            if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                $resultado = mitos_upload_media($_FILES['foto'], 'artista_foto');
                $uploadErrors = array_merge($uploadErrors, $resultado['errores']);
                if (!empty($resultado['rutas'])) {
                    $foto_url_nueva = $resultado['rutas'][0]['ruta'];
                }
            }

            // Subir portada si se seleccionó
            if (!empty($_FILES['portada']['name']) && $_FILES['portada']['error'] !== UPLOAD_ERR_NO_FILE) {
                $resultadoPort = mitos_upload_media($_FILES['portada'], 'artista_portada');
                $uploadErrors = array_merge($uploadErrors, $resultadoPort['errores']);
                if (!empty($resultadoPort['rutas'])) {
                    $portada_url_nueva = $resultadoPort['rutas'][0]['ruta'];
                }
            }

            if ($id > 0) {
                // Editar
                if ($foto_url_nueva) {
                    $rowOld = $pdo->prepare('SELECT foto_url FROM artistas WHERE id = ?');
                    $rowOld->execute([$id]);
                    $viejoFoto = $rowOld->fetchColumn();
                    if ($viejoFoto) mitos_delete_media_file($viejoFoto);
                    $pdo->prepare('UPDATE artistas SET foto_url=? WHERE id=?')->execute([$foto_url_nueva, $id]);
                }
                if ($portada_url_nueva) {
                    $rowOldP = $pdo->prepare('SELECT portada_url FROM artistas WHERE id = ?');
                    $rowOldP->execute([$id]);
                    $viejaPortada = $rowOldP->fetchColumn();
                    if ($viejaPortada) mitos_delete_media_file($viejaPortada);
                    $pdo->prepare('UPDATE artistas SET portada_url=? WHERE id=?')->execute([$portada_url_nueva, $id]);
                }

                $pdo->prepare('UPDATE artistas SET nombre=?, rol=?, especialidad=?, biografia=?, trayectoria=?, orden=?, activo=? WHERE id=?')
                    ->execute([$nombre, $rol, $especialidad ?: null, $biografia ?: null, $trayectoria ?: null, $orden, $activo, $id]);
            } else {
                // Insertar
                $pdo->prepare('INSERT INTO artistas (nombre, rol, especialidad, biografia, trayectoria, foto_url, portada_url, orden, activo) VALUES (?,?,?,?,?,?,?,?,?)')
                    ->execute([$nombre, $rol, $especialidad ?: null, $biografia ?: null, $trayectoria ?: null, $foto_url_nueva, $portada_url_nueva, $orden, $activo]);
                $id = (int)$pdo->lastInsertId();
            }

            // Galería (múltiples)
            if (!empty($_FILES['media']['name'][0])) {
                $resMedia = mitos_upload_media($_FILES['media'], 'artista_galeria');
                $uploadErrors = array_merge($uploadErrors, $resMedia['errores']);
                
                $mSql = $pdo->prepare('INSERT INTO artista_media (artista_id, tipo, ruta) VALUES (?, ?, ?)');
                foreach ($resMedia['rutas'] as $it) {
                    $mSql->execute([$id, $it['tipo'], $it['ruta']]);
                }
            }

            if (empty($uploadErrors)) {
                header('Location: ' . $baseUrl . '/admin/artistas.php');
                exit;
            }

            // Recarga el artista para mostrar formulario con errores
            $s = $pdo->prepare('SELECT * FROM artistas WHERE id = ?');
            $s->execute([$id]);
            $editar = $s->fetch() ?: null;
        }
    }
}

// ── GET ───────────────────────────────────────────────────────────────────────
$artistas = $pdo->query('SELECT id, nombre, rol, especialidad, foto_url, orden, activo FROM artistas ORDER BY orden, nombre')->fetchAll(PDO::FETCH_ASSOC);

if (!isset($editar)) {
    $editar = null;
    $editarMedia = [];
    if (isset($_GET['editar'])) {
        $eid = (int)$_GET['editar'];
        $s   = $pdo->prepare('SELECT * FROM artistas WHERE id = ?');
        $s->execute([$eid]);
        $editar = $s->fetch() ?: null;
        
        if ($editar) {
            $sm = $pdo->prepare('SELECT id, tipo, ruta FROM artista_media WHERE artista_id = ? ORDER BY orden, id');
            $sm->execute([$eid]);
            $editarMedia = $sm->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Artistas | Admin Mitos Escénicos</title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;900&family=Forum&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
  <style>
    .avatar-preview{width:100px;height:100px;border-radius:0.5rem;object-fit:cover;border:2px solid rgba(255,255,255,0.12);margin-bottom:0.5rem;display:block;}
    .portada-preview{width:100%;height:140px;border-radius:0.5rem;object-fit:cover;border:2px solid rgba(255,255,255,0.12);margin-bottom:0.5rem;display:block;}
    .upload-area{border:2px dashed rgba(255,255,255,0.15);border-radius:0.5rem;padding:1.25rem;text-align:center;cursor:pointer;transition:border-color 0.2s;}
    .upload-area:hover{border-color:var(--primary);}
    .upload-area input[type=file]{display:none;}
    .artist-card{display:flex;align-items:center;gap:1rem;padding:0.75rem 1rem;background:rgba(255,255,255,0.03);border-radius:0.4rem;border:1px solid rgba(255,255,255,0.07);}
    .artist-avatar{width:48px;height:48px;border-radius:50%;object-fit:cover;background:rgba(255,255,255,0.07);flex-shrink:0;display:flex;align-items:center;justify-content:center;overflow:hidden;}
    .artist-avatar span{font-size:1.2rem;color:var(--primary);}
    .media-card{position:relative;aspect-ratio:1;border-radius:0.5rem;overflow:hidden;background:#000;border:1px solid var(--border-subtle);}
    .media-card img, .media-card video{width:100%;height:100%;object-fit:cover;}
    .del-media-btn{position:absolute;top:0.25rem;right:0.25rem;background:rgba(220,38,38,0.9);color:#fff;border:none;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;cursor:pointer;}
  </style>
</head>
<body style="background:var(--background-dark);">

<div class="admin-layout">
  <?php mitos_admin_sidebar('artistas'); ?>

  <main class="admin-content" style="padding:2rem;">
    <div style="margin-bottom:2rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
      <div>
        <h1 style="font-size:1.8rem; font-weight:800; color:#fff; margin:0 0 0.3rem;">Artistas</h1>
        <p style="color:var(--text-muted); font-size:0.9rem;">Miembros de Mitos Escénicos. Aparecerán en "Sobre Nosotros" y en el elenco de funciones.</p>
      </div>
      <a href="<?php echo $baseUrl; ?>/admin/artistas.php?nueva=1" class="btn-gold btn-sm">
        <span class="material-symbols-outlined" style="font-size:1rem;">add</span> Agregar artista
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
      <h2 style="font-size:1.1rem; color:#fff; margin-bottom:1.5rem;"><?php echo $editar ? 'Editar artista' : 'Agregar artista'; ?></h2>

      <form method="post" action="" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:1rem;">
        <input type="hidden" name="accion" value="guardar"/>
        <input type="hidden" name="id" value="<?php echo $editar ? (int)$editar['id'] : 0; ?>"/>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
          <!-- Foto de perfil -->
          <div>
            <label style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted); margin-bottom:0.5rem;">Foto de perfil</label>
            <?php if ($editar && $editar['foto_url']): ?>
              <img id="avatarPreview" src="<?php echo htmlspecialchars(mitos_url($editar['foto_url'])); ?>" alt="Foto actual" class="avatar-preview"/>
            <?php else: ?>
              <img id="avatarPreview" src="" alt="" class="avatar-preview" style="display:none;"/>
            <?php endif; ?>
            <label class="upload-area" for="fotoInput" style="margin-top:0.5rem;">
              <input type="file" id="fotoInput" name="foto" accept="image/*" onchange="previewAvatar(this)"/>
              <span class="material-symbols-outlined" style="font-size:1.5rem; color:var(--primary); display:block; margin-bottom:0.35rem;">add_a_photo</span>
              <p style="color:var(--text-muted); font-size:0.8rem; margin:0;">
                <?php echo ($editar && $editar['foto_url']) ? 'Cambiar foto' : 'Subir foto'; ?>
              </p>
            </label>
          </div>

          <!-- Portada -->
          <div>
            <label style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted); margin-bottom:0.5rem;">Portada del artista</label>
            <?php if ($editar && !empty($editar['portada_url'])): ?>
              <img id="portadaPreview" src="<?php echo htmlspecialchars(mitos_url($editar['portada_url'])); ?>" class="portada-preview"/>
            <?php else: ?>
              <img id="portadaPreview" src="" class="portada-preview" style="display:none;"/>
            <?php endif; ?>
            <label class="upload-area" for="portadaInput" style="margin-top:0.5rem;">
              <input type="file" id="portadaInput" name="portada" accept="image/*" onchange="previewPortada(this)"/>
              <span class="material-symbols-outlined" style="font-size:1.5rem; color:var(--primary); display:block; margin-bottom:0.35rem;">wallpaper</span>
              <p style="color:var(--text-muted); font-size:0.8rem; margin:0;">
                <?php echo ($editar && !empty($editar['portada_url'])) ? 'Cambiar portada' : 'Subir portada'; ?>
              </p>
            </label>
          </div>
        </div>

        <!-- Nombre -->
        <div>
          <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Nombre del artista *</label>
          <input type="text" name="nombre" required value="<?php echo htmlspecialchars($editar['nombre'] ?? ''); ?>" placeholder="Ej. Sofía Casanova" style="width:100%;"/>
        </div>

        <!-- Rol -->
        <div>
          <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Rol principal</label>
          <?php
            $rolActual = $editar['rol'] ?? 'actor';
            $esOtro = !array_key_exists($rolActual, $rolesLabel) && $rolActual !== 'otro';
          ?>
          <select name="rol" onchange="toggleOtroRol(this)" style="width:100%; padding:0.5rem; background:rgba(255,255,255,0.05); border:1px solid var(--border-dark); border-radius:0.5rem; color:#fff;">
            <?php foreach ($rolesLabel as $val => $lbl): ?>
              <option value="<?php echo $val; ?>" <?php echo (!$esOtro && $rolActual === $val) ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
            <?php endforeach; ?>
            <?php if($esOtro): ?>
              <option value="otro" selected>Otro...</option>
            <?php endif; ?>
          </select>
          <div id="rolOtroContainer" style="margin-top:0.5rem; <?php echo $esOtro ? 'display:block;' : 'display:none;'; ?>">
            <input type="text" name="rol_otro" placeholder="Ej. Coreógrafo Invitado" value="<?php echo $esOtro ? htmlspecialchars($rolActual) : ''; ?>" style="width:100%;" />
          </div>
        </div>

        <!-- Especialidad -->
        <div>
          <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Especialidades (separadas por coma)</label>
          <input type="text" name="especialidad" value="<?php echo htmlspecialchars($editar['especialidad'] ?? ''); ?>" placeholder="Ej. Tragedia Clásica, Danza Contemporánea" style="width:100%;"/>
        </div>

        <!-- Biografía -->
        <div>
          <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Biografía / Descripción</label>
          <textarea name="biografia" rows="4" style="width:100%;" placeholder="Su historia..."><?php echo htmlspecialchars($editar['biografia'] ?? ''); ?></textarea>
        </div>

        <!-- Trayectoria Manual -->
        <div>
          <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Trayectoria / Experiencia (Opcional)</label>
          <textarea name="trayectoria" rows="4" style="width:100%;" placeholder="2023 - Obra Hamlet&#10;2024 - Premio Mejor Actriz..."><?php echo htmlspecialchars($editar['trayectoria'] ?? ''); ?></textarea>
        </div>
        
        <!-- Galería de imágenes / videos -->
        <hr style="border:0; border-top:1px solid var(--border-subtle); margin:1rem 0;"/>
        <div>
          <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Galería de imágenes/videos</label>
          <label class="upload-area" for="mediaInput" style="display:block; padding:1.5rem;">
            <input type="file" id="mediaInput" name="media[]" accept="image/*,video/mp4,video/webm" multiple/>
            <span class="material-symbols-outlined" style="font-size:2rem; color:var(--text-muted);">perm_media</span>
            <p style="margin:0.5rem 0 0; color:var(--text-muted); font-size:0.85rem;">Clic para añadir fotos o videos</p>
          </label>
        </div>

        <!-- Orden -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
          <div>
            <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Orden de aparición</label>
            <input type="number" name="orden" min="0" value="<?php echo (int)($editar['orden'] ?? 0); ?>" style="width:100%;"/>
          </div>
          <div style="display:flex; align-items:flex-end; padding-bottom:0.2rem;">
            <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
              <input type="checkbox" name="activo" value="1" <?php echo ($editar && (int)$editar['activo'] === 1) || !$editar ? 'checked' : ''; ?>/>
              <span style="color:#fff;">Mostrar en "Nosotros"</span>
            </label>
          </div>
        </div>

        <div style="display:flex; gap:0.75rem; margin-top:0.5rem;">
          <button type="submit" class="btn-primary">Guardar artista</button>
          <a href="<?php echo $baseUrl; ?>/admin/artistas.php" class="btn-secondary">Cancelar</a>
        </div>
      </form>

      <div style="margin-top:2rem;">
        <!-- Galería actual -->
        <?php if (!empty($editarMedia)): ?>
          <h3 style="font-size:1rem; color:#fff; margin-bottom:1rem;">Galería actual</h3>
          <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(100px, 1fr)); gap:1rem;">
            <?php foreach ($editarMedia as $m): ?>
              <div class="media-card">
                <?php if ($m['tipo'] === 'imagen'): ?>
                  <img src="<?php echo htmlspecialchars(mitos_url($m['ruta'])); ?>" alt="Media"/>
                <?php else: ?>
                  <video src="<?php echo htmlspecialchars(mitos_url($m['ruta'])); ?>" muted loop onmouseover="this.play()" onmouseout="this.pause()"></video>
                <?php endif; ?>
                <form method="post" onsubmit="return confirm('¿Borrar este archivo?');">
                  <input type="hidden" name="accion" value="eliminar_media"/>
                  <input type="hidden" name="media_id" value="<?php echo (int)$m['id']; ?>"/>
                  <input type="hidden" name="artista_id" value="<?php echo (int)$editar['id']; ?>"/>
                  <button type="submit" class="del-media-btn" title="Borrar">
                    <span class="material-symbols-outlined" style="font-size:1rem;">close</span>
                  </button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Lista de artistas -->
  <?php if (!empty($artistas)): ?>
  <div style="display:flex; flex-direction:column; gap:0.6rem; max-width:800px;">
    <?php foreach ($artistas as $a): ?>
      <div class="artist-card">
        <div class="artist-avatar">
          <?php if ($a['foto_url']): ?>
            <img src="<?php echo htmlspecialchars(mitos_url($a['foto_url'])); ?>" alt="<?php echo htmlspecialchars($a['nombre']); ?>" style="width:100%;height:100%;object-fit:cover;"/>
          <?php else: ?>
            <span class="material-symbols-outlined">person</span>
          <?php endif; ?>
        </div>
        <div style="flex:1; min-width:0;">
          <p style="color:#fff; font-weight:700; margin:0 0 0.1rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($a['nombre']); ?></p>
          <p style="color:var(--primary); font-size:0.75rem; font-weight:600; margin:0; text-transform:uppercase; letter-spacing:0.05em;"><?php echo htmlspecialchars($rolesLabel[$a['rol']] ?? $a['rol']); ?></p>
          <?php if ($a['especialidad']): ?>
            <p style="color:var(--text-muted); font-size:0.78rem; margin:0.1rem 0 0;"><?php echo htmlspecialchars($a['especialidad']); ?></p>
          <?php endif; ?>
        </div>
        <div style="display:flex; align-items:center; gap:0.5rem; flex-shrink:0;">
          <?php if (!(int)$a['activo']): ?>
            <span class="badge" style="background:rgba(255,255,255,0.07); color:var(--text-muted);">Oculto</span>
          <?php endif; ?>
          <a href="<?php echo $baseUrl; ?>/admin/artistas.php?editar=<?php echo (int)$a['id']; ?>" class="action-btn action-btn-edit" title="Editar">
            <span class="material-symbols-outlined" style="font-size:1.1rem;">edit</span>
          </a>
          <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar a <?php echo htmlspecialchars(addslashes($a['nombre'])); ?>?');">
            <input type="hidden" name="accion" value="eliminar"/>
            <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>"/>
            <button type="submit" class="action-btn action-btn-delete" title="Eliminar">
              <span class="material-symbols-outlined" style="font-size:1.1rem;">delete</span>
            </button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <p style="color:var(--text-muted); padding:2rem 0;">No hay artistas. Agrega uno desde el botón anterior.</p>
  <?php endif; ?>

  </main>
</div>

<script>
function previewAvatar(input) {
  const prev = document.getElementById('avatarPreview');
  if (input.files && input.files[0]) {
    prev.src = URL.createObjectURL(input.files[0]);
    prev.style.display = 'block';
  }
}
function previewPortada(input) {
  const prev = document.getElementById('portadaPreview');
  if (input.files && input.files[0]) {
    prev.src = URL.createObjectURL(input.files[0]);
    prev.style.display = 'block';
  }
}
function toggleOtroRol(select) {
  const container = document.getElementById('rolOtroContainer');
  const input = container.querySelector('input');
  if (select.value === 'otro') {
    container.style.display = 'block';
    input.required = true;
  } else {
    container.style.display = 'none';
    input.required = false;
  }
}
</script>
</body>
</html>
