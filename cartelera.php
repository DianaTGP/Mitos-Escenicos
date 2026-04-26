<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

$pdo = mitos_pdo();

$filtro_temporada = isset($_GET['temporada']) && $_GET['temporada'] !== '' ? trim((string) $_GET['temporada']) : null;
$filtro_obra_id = isset($_GET['obra_id']) && $_GET['obra_id'] !== '' && $_GET['obra_id'] !== 'todas' ? (int) $_GET['obra_id'] : null;
$filtro_estado = isset($_GET['estado']) && in_array($_GET['estado'], ['todas', 'por_estrenar', 'estrenadas'], true) ? $_GET['estado'] : 'todas';

$temporadas = $pdo->query("SELECT DISTINCT temporada FROM obras WHERE temporada IS NOT NULL AND temporada != '' ORDER BY temporada")->fetchAll(PDO::FETCH_COLUMN);
$lista_obras = $pdo->query('SELECT id, titulo FROM obras ORDER BY titulo')->fetchAll(PDO::FETCH_ASSOC);

$sql = 'SELECT o.id, o.titulo, o.descripcion, o.imagen_url, o.duracion_min, o.venta_boletos_habilitada
  FROM obras o WHERE 1=1';
$params = [];
if ($filtro_temporada !== null) { $sql .= ' AND o.temporada = ?'; $params[] = $filtro_temporada; }
if ($filtro_obra_id !== null)   { $sql .= ' AND o.id = ?';        $params[] = $filtro_obra_id; }
if ($filtro_estado === 'por_estrenar') {
    $sql .= ' AND EXISTS (SELECT 1 FROM funciones f WHERE f.obra_id = o.id AND f.fecha_hora > GETDATE())';
} elseif ($filtro_estado === 'estrenadas') {
    $sql .= ' AND NOT EXISTS (SELECT 1 FROM funciones f WHERE f.obra_id = o.id AND f.fecha_hora > GETDATE())
              AND EXISTS (SELECT 1 FROM funciones f WHERE f.obra_id = o.id AND f.fecha_hora <= GETDATE())';
}
$sql .= ' ORDER BY o.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$obras = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Cartelera | Mitos Escénicos';
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="Cartelera de obras de teatro de Mitos Escénicos. Elige tu próxima función y compra boletos."/>
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;900&family=Forum&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
</head>
<body>
<?php mitos_header($pageTitle); ?>

<main class="container" style="padding-top:2rem; padding-bottom:3rem;">

  <!-- Breadcrumb -->
  <nav style="font-size:0.8rem; color:var(--text-muted); margin-bottom:1.5rem; display:flex; align-items:center; gap:0.4rem;">
    <a href="<?php echo htmlspecialchars(mitos_url('index.php')); ?>" style="color:var(--text-muted);">Inicio</a>
    <span class="material-symbols-outlined" style="font-size:0.85rem;">chevron_right</span>
    <span style="color:#fff;">Cartelera</span>
  </nav>

  <div style="margin-bottom:2rem;">
    <p class="section-label">Temporada en curso</p>
    <h1 style="font-family:var(--font-theatrical); font-size:2.2rem; font-weight:700; color:#fff; margin:0 0 0.4rem;">Cartelera</h1>
    <p style="color:var(--text-muted); font-size:0.95rem;">Elige tu próxima función y adquiere tus boletos en línea.</p>
  </div>

  <!-- Filtros -->
  <form method="get" action=""
    style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end; margin-bottom:2rem;
           padding:1.25rem; background:var(--surface-card); border:1px solid var(--border-subtle); border-radius:0.75rem;">
    <div style="flex:1; min-width:140px;">
      <label for="temporada" style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted); margin-bottom:0.4rem;">Temporada</label>
      <select id="temporada" name="temporada">
        <option value="">Todas</option>
        <?php foreach ($temporadas as $t): ?>
          <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $filtro_temporada === $t ? 'selected' : ''; ?>><?php echo htmlspecialchars($t); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="flex:1; min-width:140px;">
      <label for="obra_id" style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted); margin-bottom:0.4rem;">Obra</label>
      <select id="obra_id" name="obra_id">
        <option value="todas">Todas</option>
        <?php foreach ($lista_obras as $o): ?>
          <option value="<?php echo (int)$o['id']; ?>" <?php echo $filtro_obra_id === (int)$o['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($o['titulo']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="flex:1; min-width:140px;">
      <label for="estado" style="display:block; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted); margin-bottom:0.4rem;">Estado</label>
      <select id="estado" name="estado">
        <option value="todas" <?php echo $filtro_estado === 'todas' ? 'selected' : ''; ?>>Todas</option>
        <option value="por_estrenar" <?php echo $filtro_estado === 'por_estrenar' ? 'selected' : ''; ?>>Por estrenar</option>
        <option value="estrenadas" <?php echo $filtro_estado === 'estrenadas' ? 'selected' : ''; ?>>Ya estrenadas</option>
      </select>
    </div>
    <button type="submit" class="btn-primary btn-sm">
      <span class="material-symbols-outlined" style="font-size:1rem;">filter_list</span> Filtrar
    </button>
    <?php if ($filtro_temporada || $filtro_obra_id || $filtro_estado !== 'todas'): ?>
      <a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>" class="btn-secondary btn-sm">Limpiar</a>
    <?php endif; ?>
  </form>

  <!-- Grid de obras -->
  <?php if (!empty($obras)): ?>
  <div class="grid-3">
    <?php foreach ($obras as $obra): ?>
      <a href="<?php echo htmlspecialchars(mitos_url('obra.php?id=' . (int)$obra['id'])); ?>" class="poster-card">
        <div class="poster-img-wrap">
          <?php if (!empty($obra['imagen_url'])): ?>
            <img src="<?php echo htmlspecialchars($obra['imagen_url']); ?>"
                 alt="<?php echo htmlspecialchars($obra['titulo']); ?>" loading="lazy"/>
          <?php else: ?>
            <div style="width:100%; aspect-ratio:3/4; background:var(--surface-dark); display:flex; align-items:center; justify-content:center;">
              <span class="material-symbols-outlined" style="font-size:4rem; color:var(--text-muted);">theaters</span>
            </div>
          <?php endif; ?>
          <div class="poster-overlay"></div>
          <?php if ($obra['venta_boletos_habilitada']): ?>
            <div class="poster-quick-btn">
              <span style="display:block; text-align:center; background:var(--gold); color:#000; padding:0.6rem; border-radius:0.4rem; font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.06em;">
                Comprar boletos
              </span>
            </div>
          <?php endif; ?>
        </div>
        <div class="poster-info">
          <h2 style="font-family:var(--font-theatrical); font-size:1.1rem; margin:0 0 0.2rem; color:#fff;"><?php echo htmlspecialchars($obra['titulo']); ?></h2>
          <?php if ((int)$obra['duracion_min'] > 0): ?>
            <p class="poster-meta"><?php echo (int)$obra['duracion_min']; ?> min</p>
          <?php endif; ?>
          <p class="poster-price"><?php echo $obra['venta_boletos_habilitada'] ? 'Ver funciones →' : 'Más información'; ?></p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <div style="text-align:center; padding:4rem 1rem;">
      <span class="material-symbols-outlined" style="font-size:3rem; color:var(--text-muted); display:block; margin-bottom:1rem;">theaters</span>
      <p style="color:var(--text-muted);">No hay obras que coincidan con los filtros seleccionados.</p>
      <a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>" class="btn-secondary" style="margin-top:1.25rem;">Ver todas las obras</a>
    </div>
  <?php endif; ?>

</main>

<?php mitos_footer(); ?>
</body>
</html>
