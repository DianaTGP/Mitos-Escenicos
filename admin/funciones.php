<?php
require_once __DIR__ . '/../php/init.php';
require_once __DIR__ . '/../php/layout.php';
mitos_requiere_admin();

$pdo = mitos_pdo();
$baseUrl = rtrim(mitos_url(''), '/');

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM funciones WHERE id = ?')->execute([$id]);
        }
        header('Location: ' . $baseUrl . '/admin/funciones.php');
        exit;
    }

    if ($accion === 'guardar') {
        $id          = (int)($_POST['id'] ?? 0);
        $obra_id     = (int)($_POST['obra_id'] ?? 0);
        $lugar_id    = (int)($_POST['lugar_id'] ?? 0);
        $fecha_hora  = trim((string)($_POST['fecha_hora'] ?? ''));
        $precio_base = (float)($_POST['precio_base'] ?? 0);
        $aforo       = (int)($_POST['aforo'] ?? 0);

        if ($obra_id > 0 && $lugar_id > 0 && $fecha_hora !== '' && $precio_base > 0 && $aforo > 0) {
            if ($id > 0) {
                $pdo->prepare('UPDATE funciones SET obra_id=?, lugar_id=?, fecha_hora=?, precio_base=?, aforo=? WHERE id=?')
                    ->execute([$obra_id, $lugar_id, $fecha_hora, $precio_base, $aforo, $id]);
            } else {
                $pdo->prepare('INSERT INTO funciones (obra_id, lugar_id, fecha_hora, precio_base, aforo) VALUES (?,?,?,?,?)')
                    ->execute([$obra_id, $lugar_id, $fecha_hora, $precio_base, $aforo]);
                $id = (int)$pdo->lastInsertId();
            }

            // Guardar elenco: primero borrar asignaciones anteriores
            $pdo->prepare('DELETE FROM funcion_artistas WHERE funcion_id = ?')->execute([$id]);

            $artistasIds = $_POST['artista_ids'] ?? [];
            foreach ($artistasIds as $aid) {
                $aid = (int)$aid;
                if ($aid <= 0) continue;
                $rol_fn   = $_POST['rol_funcion'][$aid] ?? 'actor';
                $rol_fn   = in_array($rol_fn, ['actor', 'director'], true) ? $rol_fn : 'actor';
                $personaje = trim((string)($_POST['personaje'][$aid] ?? ''));
                $pdo->prepare('INSERT INTO funcion_artistas (funcion_id, artista_id, rol_en_funcion, personaje) VALUES (?,?,?,?)')
                    ->execute([$id, $aid, $rol_fn, $personaje ?: null]);
            }
        }
        header('Location: ' . $baseUrl . '/admin/funciones.php');
        exit;
    }
}

// ── GET ───────────────────────────────────────────────────────────────────────
$funciones = $pdo->query('
  SELECT f.id, f.obra_id, f.lugar_id, f.fecha_hora, f.precio_base, f.aforo,
         o.titulo AS obra_titulo, l.nombre AS lugar_nombre
  FROM funciones f
  JOIN obras o ON o.id = f.obra_id
  JOIN lugares l ON l.id = f.lugar_id
  ORDER BY f.fecha_hora DESC
')->fetchAll(PDO::FETCH_ASSOC);

$obras    = $pdo->query('SELECT id, titulo FROM obras ORDER BY titulo')->fetchAll(PDO::FETCH_ASSOC);
$lugares  = $pdo->query('SELECT id, nombre FROM lugares ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
$artistas = $pdo->query('SELECT id, nombre, rol FROM artistas WHERE activo = 1 ORDER BY orden, nombre')->fetchAll(PDO::FETCH_ASSOC);

$editar          = null;
$editarElenco    = [];
if (isset($_GET['editar'])) {
    $eid = (int)$_GET['editar'];
    foreach ($funciones as $f) {
        if ((int)$f['id'] === $eid) {
            $editar = $f;
            break;
        }
    }
    if ($editar) {
        $se = $pdo->prepare('SELECT artista_id, rol_en_funcion, personaje FROM funcion_artistas WHERE funcion_id = ?');
        $se->execute([$eid]);
        foreach ($se->fetchAll() as $e) {
            $editarElenco[$e['artista_id']] = $e;
        }
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Funciones | Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;900&family=Forum&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
  <style>
    .elenco-row{display:grid;grid-template-columns:auto 1fr 160px 200px;align-items:center;gap:0.75rem;padding:0.6rem 0;border-bottom:1px solid rgba(255,255,255,0.05);}
    .elenco-row:last-child{border-bottom:none;}
    .elenco-personaje{display:none;}
    .rol-select:has(option[value="actor"]:checked) ~ .elenco-personaje,
    .rol-select-actor ~ .elenco-personaje { display:block; }
    @media(max-width:600px){.elenco-row{grid-template-columns:auto 1fr;}.elenco-row > *:nth-child(3),.elenco-row > *:nth-child(4){grid-column:2;}}
  </style>
</head>
<body style="background:var(--background-dark);">

<div class="admin-layout">
  <?php mitos_admin_sidebar('funciones'); ?>

  <main class="admin-content" style="padding:2rem;">
    <div style="margin-bottom:2rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
      <div>
        <h1 style="font-size:1.8rem; font-weight:800; color:#fff; margin:0 0 0.3rem;">Funciones</h1>
        <p style="color:var(--text-muted); font-size:0.9rem;">Programa fechas, lugares, precios y elenco de cada obra.</p>
      </div>
      <?php if (!empty($obras) && !empty($lugares)): ?>
        <a href="<?php echo $baseUrl; ?>/admin/funciones.php?nueva=1" class="btn-gold btn-sm">
          <span class="material-symbols-outlined" style="font-size:1rem;">add</span> Agregar función
        </a>
      <?php endif; ?>
    </div>

  <?php if ($editar || isset($_GET['nueva'])): ?>
    <div class="surface-card" style="max-width:640px; margin-bottom:2rem;">
      <h2 style="font-size:1.1rem; color:#fff; margin-bottom:1.25rem;"><?php echo $editar ? 'Editar función' : 'Agregar función'; ?></h2>
      <form method="post" action="">
        <input type="hidden" name="accion" value="guardar"/>
        <input type="hidden" name="id" value="<?php echo $editar ? (int)$editar['id'] : 0; ?>"/>

        <div style="display:flex; flex-direction:column; gap:1rem;">
          <div>
            <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Obra *</label>
            <select name="obra_id" required style="width:100%; padding:0.5rem; background:rgba(255,255,255,0.05); border:1px solid var(--border-dark); border-radius:0.5rem; color:#fff;">
              <?php foreach ($obras as $o): ?>
                <option value="<?php echo (int)$o['id']; ?>" <?php echo $editar && (int)$editar['obra_id'] === (int)$o['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($o['titulo']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Lugar *</label>
            <select name="lugar_id" required style="width:100%; padding:0.5rem; background:rgba(255,255,255,0.05); border:1px solid var(--border-dark); border-radius:0.5rem; color:#fff;">
              <?php foreach ($lugares as $l): ?>
                <option value="<?php echo (int)$l['id']; ?>" <?php echo $editar && (int)$editar['lugar_id'] === (int)$l['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($l['nombre']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Fecha y hora *</label>
            <input type="datetime-local" name="fecha_hora" required value="<?php echo $editar ? date('Y-m-d\TH:i', strtotime($editar['fecha_hora'])) : ''; ?>" style="width:100%;"/>
          </div>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
            <div>
              <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Precio base *</label>
              <input type="number" name="precio_base" required min="0" step="0.01" value="<?php echo $editar ? (float)$editar['precio_base'] : ''; ?>" style="width:100%;"/>
            </div>
            <div>
              <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Aforo *</label>
              <input type="number" name="aforo" required min="1" value="<?php echo $editar ? (int)$editar['aforo'] : ''; ?>" style="width:100%;"/>
            </div>
          </div>

          <!-- ── Elenco ── -->
          <?php if (!empty($artistas)): ?>
          <div>
            <label style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted); margin-bottom:0.75rem; border-top:1px solid rgba(255,255,255,0.07); padding-top:1rem;">
              Elenco de la función
            </label>
            <div>
              <?php foreach ($artistas as $art): ?>
                <?php
                  $aid     = (int)$art['id'];
                  $checked = array_key_exists($aid, $editarElenco);
                  $rolAct  = $editarElenco[$aid]['rol_en_funcion'] ?? 'actor';
                  $persAct = $editarElenco[$aid]['personaje'] ?? '';
                ?>
                <div class="elenco-row" id="erow_<?php echo $aid; ?>">
                  <!-- Checkbox -->
                  <input type="checkbox" name="artista_ids[]" value="<?php echo $aid; ?>"
                         id="cart_<?php echo $aid; ?>"
                         <?php echo $checked ? 'checked' : ''; ?>
                         onchange="toggleElenco(<?php echo $aid; ?>)"/>
                  <!-- Nombre -->
                  <label for="cart_<?php echo $aid; ?>" style="color:#fff; font-weight:600; cursor:pointer; font-size:0.9rem;">
                    <?php echo htmlspecialchars($art['nombre']); ?>
                    <span style="color:var(--text-muted); font-size:0.75rem; font-weight:400;"> · <?php echo htmlspecialchars($art['rol']); ?></span>
                  </label>
                  <!-- Rol en función -->
                  <select name="rol_funcion[<?php echo $aid; ?>]" id="rfun_<?php echo $aid; ?>"
                          onchange="onRolChange(<?php echo $aid; ?>)"
                          style="padding:0.35rem 0.5rem; background:rgba(255,255,255,0.05); border:1px solid var(--border-dark); border-radius:0.4rem; color:#fff; font-size:0.82rem; <?php echo !$checked ? 'opacity:0.3; pointer-events:none;' : ''; ?>">
                    <option value="actor"    <?php echo $rolAct === 'actor'    ? 'selected' : ''; ?>>Actor / Actriz</option>
                    <option value="director" <?php echo $rolAct === 'director' ? 'selected' : ''; ?>>Director / Directora</option>
                  </select>
                  <!-- Personaje (solo si es actor) -->
                  <input type="text" name="personaje[<?php echo $aid; ?>]" id="pers_<?php echo $aid; ?>"
                         value="<?php echo htmlspecialchars($persAct); ?>"
                         placeholder="Personaje que interpreta"
                         style="padding:0.35rem 0.6rem; background:rgba(255,255,255,0.05); border:1px solid var(--border-dark); border-radius:0.4rem; color:#fff; font-size:0.82rem; width:100%;
                         <?php echo (!$checked || $rolAct === 'director') ? 'display:none;' : ''; ?>"/>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php else: ?>
            <p style="color:var(--text-muted); font-size:0.85rem; border-top:1px solid rgba(255,255,255,0.07); padding-top:1rem;">
              No hay artistas registrados. <a href="<?php echo $baseUrl; ?>/admin/artistas.php?nueva=1" style="color:var(--primary);">Agregar artistas</a>
            </p>
          <?php endif; ?>

          <div style="display:flex; gap:0.75rem; margin-top:0.25rem;">
            <button type="submit" class="btn-primary">Guardar</button>
            <a href="<?php echo $baseUrl; ?>/admin/funciones.php" class="btn-secondary">Cancelar</a>
          </div>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <?php if (empty($obras) || empty($lugares)): ?>
    <div class="alert alert-info">Crea al menos una obra y un lugar antes de agregar funciones.</div>
  <?php endif; ?>

  <table class="admin-table">
    <thead><tr>
      <th>Obra</th><th>Lugar</th><th>Fecha</th><th style="text-align:right;">Precio</th><th style="text-align:right;">Acciones</th>
    </tr></thead>
    <tbody>
      <?php foreach ($funciones as $f): ?>
        <tr>
          <td style="color:#fff; font-weight:600;"><?php echo htmlspecialchars($f['obra_titulo']); ?></td>
          <td><?php echo htmlspecialchars($f['lugar_nombre']); ?></td>
          <td><?php echo date('d/m/Y H:i', strtotime($f['fecha_hora'])); ?></td>
          <td style="text-align:right; color:var(--gold); font-weight:700;">$<?php echo number_format((float)$f['precio_base'], 2); ?></td>
          <td style="text-align:right;">
            <div class="table-actions" style="justify-content:flex-end;">
              <a href="<?php echo $baseUrl; ?>/admin/funciones.php?editar=<?php echo (int)$f['id']; ?>" class="action-btn action-btn-edit" title="Editar">
                <span class="material-symbols-outlined" style="font-size:1.1rem;">edit</span>
              </a>
              <form method="post" style="display:inline;" onsubmit="return confirm('Eliminar esta función?');">
                <input type="hidden" name="accion" value="eliminar"/>
                <input type="hidden" name="id" value="<?php echo (int)$f['id']; ?>"/>
                <button type="submit" class="action-btn action-btn-delete"><span class="material-symbols-outlined" style="font-size:1.1rem;">delete</span></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($funciones)): ?>
    <p style="color:var(--text-muted); padding:2rem 0;">No hay funciones.</p>
  <?php endif; ?>

  </main>
</div>

<script>
function toggleElenco(aid) {
  const cb   = document.getElementById('cart_' + aid);
  const sel  = document.getElementById('rfun_' + aid);
  const inp  = document.getElementById('pers_' + aid);
  const on   = cb.checked;
  sel.style.opacity        = on ? '1' : '0.3';
  sel.style.pointerEvents  = on ? 'auto' : 'none';
  if (on) {
    onRolChange(aid);
  } else {
    inp.style.display = 'none';
  }
}
function onRolChange(aid) {
  const cb  = document.getElementById('cart_' + aid);
  const sel = document.getElementById('rfun_' + aid);
  const inp = document.getElementById('pers_' + aid);
  inp.style.display = (cb.checked && sel.value === 'actor') ? 'block' : 'none';
}
</script>
</body>
</html>
