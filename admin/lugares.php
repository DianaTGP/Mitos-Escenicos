<?php
require_once __DIR__ . '/../php/init.php';
require_once __DIR__ . '/../php/layout.php';
mitos_requiere_admin();

$pdo = mitos_pdo();
$baseUrl = rtrim(mitos_url(''), '/');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM lugares WHERE id = ?')->execute([$id]);
        }
        header('Location: ' . $baseUrl . '/admin/lugares.php');
        exit;
    }
    if ($accion === 'guardar') {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $direccion = trim((string) ($_POST['direccion'] ?? ''));
        $capacidad = (int) ($_POST['capacidad'] ?? 0);
        $mapa_url = trim((string) ($_POST['mapa_url'] ?? ''));
        if ($nombre !== '') {
            if ($id > 0) {
                $pdo->prepare('UPDATE lugares SET nombre = ?, direccion = ?, capacidad = ?, mapa_url = ? WHERE id = ?')
                    ->execute([$nombre, $direccion ?: null, $capacidad ?: null, $mapa_url ?: null, $id]);
            } else {
                $pdo->prepare('INSERT INTO lugares (nombre, direccion, capacidad, mapa_url) VALUES (?, ?, ?, ?)')
                    ->execute([$nombre, $direccion ?: null, $capacidad ?: null, $mapa_url ?: null]);
            }
        }
        header('Location: ' . $baseUrl . '/admin/lugares.php');
        exit;
    }
}

$lugares = $pdo->query('SELECT id, nombre, direccion, capacidad, mapa_url FROM lugares ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
$editar = null;
if (isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];
    foreach ($lugares as $l) {
        if ((int) $l['id'] === $id) {
            $editar = $l;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Lugares | Admin Mitos Escénicos</title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;900&family=Forum&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
</head>
<body style="background:var(--background-dark);">

<div class="admin-layout">
  <?php mitos_admin_sidebar('lugares'); ?>

  <main class="admin-content" style="padding:2rem;">
    <div style="margin-bottom:2rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
      <div>
        <h1 style="font-size:1.8rem; font-weight:800; color:#fff; margin:0 0 0.3rem;">Lugares</h1>
        <p style="color:var(--text-muted); font-size:0.9rem;">Teatros y sedes donde se presentan las obras.</p>
      </div>
      <a href="<?php echo $baseUrl; ?>/admin/lugares.php?nueva=1" class="btn-gold btn-sm">
        <span class="material-symbols-outlined" style="font-size:1rem;">add</span> Agregar lugar
      </a>
    </div>

  <?php if ($editar || isset($_GET['nueva'])): ?>
    <div style="max-width: 560px; margin-bottom: 2rem; padding: 1.5rem; background: rgba(255,255,255,0.03); border-radius: 0.5rem;">
      <h2 style="font-size: 1.25rem; color: #fff; margin-bottom: 1rem;"><?php echo $editar ? 'Editar lugar' : 'Agregar lugar'; ?></h2>
      <form method="post" action="">
        <input type="hidden" name="accion" value="guardar"/>
        <input type="hidden" name="id" value="<?php echo $editar ? (int)$editar['id'] : 0; ?>"/>
        <div style="margin-bottom: 0.75rem;">
          <label style="display: block; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">Nombre *</label>
          <input type="text" name="nombre" required value="<?php echo htmlspecialchars($editar['nombre'] ?? ''); ?>" style="width: 100%;"/>
        </div>
        <div style="margin-bottom: 0.75rem;">
          <label style="display: block; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">Dirección</label>
          <input type="text" name="direccion" value="<?php echo htmlspecialchars($editar['direccion'] ?? ''); ?>" style="width: 100%;"/>
        </div>
        <div style="margin-bottom: 0.75rem;">
          <label style="display: block; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">Capacidad</label>
          <input type="number" name="capacidad" min="0" value="<?php echo (int)($editar['capacidad'] ?? 0); ?>"/>
        </div>
        <div style="margin-bottom: 1rem;">
          <label style="display: block; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">URL mapa</label>
          <input type="text" name="mapa_url" value="<?php echo htmlspecialchars($editar['mapa_url'] ?? ''); ?>" style="width: 100%;"/>
        </div>
        <button type="submit" class="btn-primary">Guardar</button>
        <a href="<?php echo $baseUrl; ?>/admin/lugares.php" class="btn-secondary" style="margin-left: 0.5rem;">Cancelar</a>
      </form>
    </div>
  <?php endif; ?>

  <p style="margin-bottom:1.5rem; color:var(--text-muted);"> </p>

  <table class="admin-table">
    <thead><tr>
      <th>Nombre</th><th>Dirección</th><th style="text-align:right;">Acciones</th>
    </tr></thead>
    <tbody>
      <?php foreach ($lugares as $l): ?>
        <tr>
          <td style="color:#fff; font-weight:600;"><?php echo htmlspecialchars($l['nombre']); ?></td>
          <td><?php echo htmlspecialchars($l['direccion'] ?? '-'); ?></td>
          <td style="text-align:right;">
            <div class="table-actions" style="justify-content:flex-end;">
              <a href="<?php echo $baseUrl; ?>/admin/lugares.php?editar=<?php echo (int)$l['id']; ?>" class="action-btn action-btn-edit">
                <span class="material-symbols-outlined" style="font-size:1.1rem;">edit</span>
              </a>
              <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar este lugar?');">
                <input type="hidden" name="accion" value="eliminar"/>
                <input type="hidden" name="id" value="<?php echo (int)$l['id']; ?>"/>
                <button type="submit" class="action-btn action-btn-delete"><span class="material-symbols-outlined" style="font-size:1.1rem;">delete</span></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($lugares)): ?>
    <p style="color:var(--text-muted); padding:2rem 0;">No hay lugares. Agrega uno desde el botón anterior.</p>
  <?php endif; ?>

  </main>
</div>

</body>
</html>
