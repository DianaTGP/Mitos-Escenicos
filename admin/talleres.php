<?php
require_once __DIR__ . '/../php/init.php';
require_once __DIR__ . '/../php/layout.php';
mitos_requiere_admin();

$pdo = mitos_pdo();
$baseUrl = rtrim(mitos_url(''), '/');

// ── Manejo de acciones (POST) ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // Eliminar taller
    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM talleres WHERE id = ?')->execute([$id]);
        }
        header("Location: $baseUrl/admin/talleres.php?msg=eliminado");
        exit;
    }

    // Agregar / Editar
    if ($accion === 'guardar') {
        $id = (int)($_POST['id'] ?? 0);
        
        $titulo         = trim((string)($_POST['titulo'] ?? ''));
        $instructor     = trim((string)($_POST['instructor'] ?? ''));
        $descripcion    = trim((string)($_POST['descripcion'] ?? ''));
        $modalidad      = $_POST['modalidad'] ?? 'presencial';
        $precio         = (float)($_POST['precio'] ?? 0);
        $duracion_horas = $_POST['duracion_horas'] !== '' ? (float)$_POST['duracion_horas'] : null;
        $cupo_maximo    = $_POST['cupo_maximo'] !== '' ? (int)$_POST['cupo_maximo'] : null;
        $fecha_inicio   = $_POST['fecha_inicio'] !== '' ? $_POST['fecha_inicio'] : null;
        $fecha_fin      = $_POST['fecha_fin'] !== '' ? $_POST['fecha_fin'] : null;
        $activo         = isset($_POST['activo']) ? 1 : 0;
        $cobro_automatico = isset($_POST['cobro_automatico']) ? 1 : 0;
        
        $portada_url_nueva = $_POST['imagen_url_actual'] ?? '';

        // Subida de imagen principal si la hay (reusando helper o lógica simple)
        if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
            require_once __DIR__ . '/../php/upload.php';
            if (function_exists('mitos_upload_media')) {
                $resUpload = mitos_upload_media(['name' => [$_FILES['portada']['name']], 'type' => [$_FILES['portada']['type']], 'tmp_name' => [$_FILES['portada']['tmp_name']], 'error' => [$_FILES['portada']['error']], 'size' => [$_FILES['portada']['size']]], 'taller_portada');
                if (!empty($resUpload['rutas'][0]['ruta'])) {
                    $portada_url_nueva = $baseUrl . '/' . $resUpload['rutas'][0]['ruta'];
                }
            }
        }

        if ($titulo !== '') {
            if ($id > 0) {
                // Actualizar
                $stmt = $pdo->prepare('UPDATE talleres SET titulo=?, descripcion=?, instructor=?, imagen_url=?, modalidad=?, duracion_horas=?, precio=?, cupo_maximo=?, fecha_inicio=?, fecha_fin=?, activo=?, cobro_automatico=?, updated_at=GETDATE() WHERE id=?');
                $stmt->execute([$titulo, $descripcion, $instructor, $portada_url_nueva, $modalidad, $duracion_horas, $precio, $cupo_maximo, $fecha_inicio, $fecha_fin, $activo, $cobro_automatico, $id]);
            } else {
                // Insertar
                $stmt = $pdo->prepare('INSERT INTO talleres (titulo, descripcion, instructor, imagen_url, modalidad, duracion_horas, precio, cupo_maximo, fecha_inicio, fecha_fin, activo, cobro_automatico) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$titulo, $descripcion, $instructor, $portada_url_nueva, $modalidad, $duracion_horas, $precio, $cupo_maximo, $fecha_inicio, $fecha_fin, $activo, $cobro_automatico]);
            }
        }
        header("Location: $baseUrl/admin/talleres.php?msg=guardado");
        exit;
    }
}

// ── GET: Cargar datos ────────────────────────────────────────────────────────
$talleres = $pdo->query('SELECT * FROM talleres ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

$talleresConInscritos = [];
$stats = $pdo->query('SELECT taller_id, COUNT(id) AS total_inscripciones FROM inscripciones_talleres GROUP BY taller_id')->fetchAll(PDO::FETCH_KEY_PAIR);

$editar = null;
if (isset($_GET['editar'])) {
    $eid = (int)$_GET['editar'];
    $stmt = $pdo->prepare('SELECT * FROM talleres WHERE id = ?');
    $stmt->execute([$eid]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Administrar Talleres | Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;900&family=Forum&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
  <style>
    .modalidad-badge {
      padding: 0.2rem 0.5rem;
      border-radius: 0.25rem;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
    }
    .mod-presencial { background: var(--primary); color: #fff; }
    .mod-video { background: #4a5568; color: #fff; }
    .mod-hibrido { background: #d69e2e; color: #000; }
  </style>
</head>
<body style="background:var(--background-dark);">

<div class="admin-layout">
  <?php mitos_admin_sidebar('talleres'); ?>

  <main class="admin-content" style="padding:2rem;">
    <div style="margin-bottom:2rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
      <div>
        <h1 style="font-size:1.8rem; font-weight:800; color:#fff; margin:0 0 0.3rem;">Talleres y Cursos</h1>
        <p style="color:var(--text-muted); font-size:0.9rem;">Gestiona la oferta educativa y talleres especiales.</p>
      </div>
      <a href="<?php echo $baseUrl; ?>/admin/talleres.php?nueva=1" class="btn-gold btn-sm">
        <span class="material-symbols-outlined" style="font-size:1rem;">add</span> Crear Taller
      </a>
    </div>

    <!-- Formulario de Creación / Edición -->
    <?php if ($editar || isset($_GET['nueva'])): ?>
    <div class="surface-card" style="max-width:800px; margin-bottom:2rem;">
      <h2 style="font-size:1.1rem; color:#fff; margin-bottom:1.25rem;">
        <?php echo $editar ? 'Editar Taller' : 'Nuevo Taller'; ?>
      </h2>
      <form method="post" action="" enctype="multipart/form-data">
        <input type="hidden" name="accion" value="guardar"/>
        <input type="hidden" name="id" value="<?php echo $editar ? (int)$editar['id'] : 0; ?>"/>
        <input type="hidden" name="imagen_url_actual" value="<?php echo htmlspecialchars($editar['imagen_url'] ?? ''); ?>"/>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
            <!-- Izquierda -->
            <div style="display:flex; flex-direction:column; gap:1rem;">
                <div>
                  <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Nombre del taller *</label>
                  <input type="text" name="titulo" required value="<?php echo htmlspecialchars($editar['titulo'] ?? ''); ?>" style="width:100%;"/>
                </div>

                <div>
                  <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Instructor</label>
                  <input type="text" name="instructor" value="<?php echo htmlspecialchars($editar['instructor'] ?? ''); ?>" style="width:100%;"/>
                </div>

                <div>
                  <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Descripción</label>
                  <textarea name="descripcion" rows="5" style="width:100%;"><?php echo htmlspecialchars($editar['descripcion'] ?? ''); ?></textarea>
                </div>
                
                <div>
                  <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Imagen representativa</label>
                  <?php if (!empty($editar['imagen_url'])): ?>
                    <img src="<?php echo htmlspecialchars($editar['imagen_url']); ?>" alt="Portada actual" style="width:120px; border-radius:0.5rem; margin-bottom:0.5rem;">
                  <?php endif; ?>
                  <input type="file" name="portada" accept="image/*" style="width:100%; display:block;"/>
                </div>
            </div>

            <!-- Derecha -->
            <div style="display:flex; flex-direction:column; gap:1rem;">
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                  <div>
                    <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Modalidad *</label>
                    <select name="modalidad" required style="width:100%; padding:0.5rem; background:rgba(255,255,255,0.05); border:1px solid var(--border-dark); border-radius:0.5rem; color:#fff;">
                      <option value="presencial" <?php echo ($editar['modalidad'] ?? '') === 'presencial' ? 'selected' : ''; ?>>Presencial</option>
                      <option value="hibrido" <?php echo ($editar['modalidad'] ?? '') === 'hibrido' ? 'selected' : ''; ?>>Híbrido</option>
                      <option value="video" <?php echo ($editar['modalidad'] ?? '') === 'video' ? 'selected' : ''; ?>>Video / En línea</option>
                    </select>
                  </div>
                  <div>
                    <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Duración (horas)</label>
                    <input type="number" step="0.5" name="duracion_horas" value="<?php echo htmlspecialchars($editar['duracion_horas'] ?? ''); ?>" style="width:100%;"/>
                  </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                  <div>
                    <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Precio ($) *</label>
                    <input type="number" step="0.01" min="0" required name="precio" value="<?php echo htmlspecialchars($editar['precio'] ?? '0'); ?>" style="width:100%;"/>
                  </div>
                  <div>
                    <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Cupo máximo</label>
                    <input type="number" min="1" name="cupo_maximo" placeholder="Opcional" value="<?php echo htmlspecialchars($editar['cupo_maximo'] ?? ''); ?>" style="width:100%;"/>
                  </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                  <div>
                    <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Fecha inicio</label>
                    <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($editar['fecha_inicio'] ?? ''); ?>" style="width:100%;"/>
                  </div>
                  <div>
                    <label style="display:block; font-size:0.875rem; color:var(--text-muted); margin-bottom:0.25rem;">Fecha fin</label>
                    <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($editar['fecha_fin'] ?? ''); ?>" style="width:100%;"/>
                  </div>
                </div>

                <div>
                    <label style="display:flex; align-items:center; gap:0.5rem; color:#fff; font-size:0.9rem; margin-top:0.5rem; cursor:pointer;">
                      <input type="checkbox" name="activo" value="1" <?php echo (!$editar || !empty($editar['activo'])) ? 'checked' : ''; ?>/>
                      Taller activo y visible al público
                    </label>
                    <label style="display:flex; align-items:center; gap:0.5rem; color:#fff; font-size:0.9rem; margin-top:0.5rem; cursor:pointer;" title="Llevar automáticamente al usuario al carrito de compras de OpenPay">
                      <input type="checkbox" name="cobro_automatico" value="1" <?php echo (!empty($editar['cobro_automatico'])) ? 'checked' : ''; ?>/>
                      Activar cobro automático (Requiere pago para confirmar)
                    </label>
                </div>
            </div>
        </div>

        <div style="display:flex; gap:0.75rem; margin-top:1.5rem;">
          <button type="submit" class="btn-primary">Guardar Taller</button>
          <a href="<?php echo $baseUrl; ?>/admin/talleres.php" class="btn-secondary">Cancelar</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <!-- Tabla de Talleres -->
    <table class="admin-table">
      <thead>
        <tr>
          <th>Taller</th>
          <th>Modalidad</th>
          <th>Fechas</th>
          <th>Costo</th>
          <th>Estado</th>
          <th>Alumnos</th>
          <th style="text-align:right;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($talleres as $t): 
            $numInscritos = $stats[$t['id']] ?? 0;
            $modClass = 'mod-' . $t['modalidad'];
        ?>
          <tr>
            <td style="color:#fff; font-weight:600;">
                <?php echo htmlspecialchars($t['titulo']); ?>
                <span style="display:block; font-size:0.8rem; color:var(--text-muted); font-weight:400;"><?php echo htmlspecialchars($t['instructor']); ?></span>
            </td>
            <td><span class="modalidad-badge <?php echo $modClass; ?>"><?php echo htmlspecialchars($t['modalidad']); ?></span></td>
            <td style="font-size:0.85rem; color:var(--text-muted);">
                <?php echo $t['fecha_inicio'] ? date('d/M/y', strtotime($t['fecha_inicio'])) : 'N/A'; ?> 
                - 
                <?php echo $t['fecha_fin'] ? date('d/M/y', strtotime($t['fecha_fin'])) : 'N/A'; ?>
            </td>
            <td style="color:var(--gold); font-weight:700;">
                $<?php echo number_format((float)$t['precio'], 2); ?>
            </td>
            <td>
                <?php if ($t['activo']): ?>
                    <span class="badge badge-green">Activo</span>
                <?php else: ?>
                    <span class="badge badge-maroon">Inactivo</span>
                <?php endif; ?>
            </td>
            <td style="text-align:center;">
                <span style="display:inline-block; padding:0.2rem 0.5rem; background:rgba(255,255,255,0.1); border-radius:0.5rem; font-weight:600; color:#fff;">
                    <?php echo $numInscritos; ?> <?php echo $t['cupo_maximo'] ? '/ ' . $t['cupo_maximo'] : ''; ?>
                </span>
            </td>
            <td style="text-align:right;">
              <div class="table-actions" style="justify-content:flex-end;">
                <!-- Link al detalle e inscripciones -->
                <a href="<?php echo $baseUrl; ?>/admin/taller.php?id=<?php echo (int)$t['id']; ?>" class="action-btn" style="color:var(--primary); background:rgba(var(--primary-rgb), 0.1);" title="Ver inscritos">
                  <span class="material-symbols-outlined" style="font-size:1.1rem;">group</span>
                </a>
                <!-- Link a editar general -->
                <a href="<?php echo $baseUrl; ?>/admin/talleres.php?editar=<?php echo (int)$t['id']; ?>" class="action-btn action-btn-edit" title="Editar config">
                  <span class="material-symbols-outlined" style="font-size:1.1rem;">edit</span>
                </a>
                <form method="post" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este taller por completo? Eliminará a los alumnos.');">
                  <input type="hidden" name="accion" value="eliminar"/>
                  <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>"/>
                  <button type="submit" class="action-btn action-btn-delete"><span class="material-symbols-outlined" style="font-size:1.1rem;">delete</span></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (empty($talleres)): ?>
      <p style="color:var(--text-muted); padding:2rem 0; text-align:center;">No hay talleres registrados.</p>
    <?php endif; ?>
    
  </main>
</div>
</body>
</html>
