<?php
require_once __DIR__ . '/../php/init.php';
require_once __DIR__ . '/../php/layout.php';
require_once __DIR__ . '/../php/openpay-helper.php';
mitos_requiere_admin();

$pdo = mitos_pdo();

// ── Filtro de rango de fechas ──────────────────────────────────────────────
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-t');
$desdeTs = $desde . ' 00:00:00';
$hastaTs = $hasta . ' 23:59:59';

// ── KPIs desde BD ──────────────────────────────────────────────────────────
try {
    // Ingresos totales (órdenes pagadas)
    $stmtKpi = $pdo->prepare("
        SELECT
            ISNULL(SUM(total), 0) as ingresos_totales,
            COUNT(*) as total_ordenes,
            ISNULL(AVG(total), 0) as promedio_pedido
        FROM ordenes
        WHERE estado = 'pagado' AND created_at BETWEEN ? AND ?
    ");
    $stmtKpi->execute([$desdeTs, $hastaTs]);
    $kpi = $stmtKpi->fetch(PDO::FETCH_ASSOC);

    // Boletos vendidos
    $stmtBoletos = $pdo->prepare("
        SELECT COUNT(*) as boletos
        FROM orden_items oi
        JOIN ordenes o ON oi.orden_id = o.id
        WHERE oi.tipo = 'boleto' AND o.estado = 'pagado' AND o.created_at BETWEEN ? AND ?
    ");
    $stmtBoletos->execute([$desdeTs, $hastaTs]);
    $kpiBoletos = (int)$stmtBoletos->fetchColumn();

    // Productos vendidos
    $stmtProductos = $pdo->prepare("
        SELECT COUNT(*) as productos
        FROM orden_items oi
        JOIN ordenes o ON oi.orden_id = o.id
        WHERE oi.tipo = 'mercancia' AND o.estado = 'pagado' AND o.created_at BETWEEN ? AND ?
    ");
    $stmtProductos->execute([$desdeTs, $hastaTs]);
    $kpiProductos = (int)$stmtProductos->fetchColumn();

    // Talleres confirmados en el rango
    $stmtTalleres = $pdo->prepare("
        SELECT COUNT(*) FROM inscripciones_talleres
        WHERE estado = 'confirmada' AND created_at BETWEEN ? AND ?
    ");
    $stmtTalleres->execute([$desdeTs, $hastaTs]);
    $kpiTalleres = (int)$stmtTalleres->fetchColumn();

    // Ingresos por mes (últimos 7 meses)
    $stmtMes = $pdo->query("
        SELECT
            FORMAT(created_at, 'MMM', 'es-MX') as mes,
            MONTH(created_at) as num_mes,
            YEAR(created_at) as anio,
            SUM(total) as total
        FROM ordenes
        WHERE estado = 'pagado' AND created_at >= DATEADD(MONTH, -6, GETDATE())
        GROUP BY FORMAT(created_at, 'MMM', 'es-MX'), MONTH(created_at), YEAR(created_at)
        ORDER BY anio ASC, num_mes ASC
    ");
    $datosMes = $stmtMes ? $stmtMes->fetchAll(PDO::FETCH_ASSOC) : [];

    // Desglose por categoría
    $ingresosBoletos = $pdo->prepare("
        SELECT ISNULL(SUM(oi.precio_unitario * oi.cantidad), 0)
        FROM orden_items oi JOIN ordenes o ON oi.orden_id = o.id
        WHERE oi.tipo = 'boleto' AND o.estado = 'pagado' AND o.created_at BETWEEN ? AND ?
    ");
    $ingresosBoletos->execute([$desdeTs, $hastaTs]);
    $ingBoletos = (float)$ingresosBoletos->fetchColumn();

    $ingresosProductos = $pdo->prepare("
        SELECT ISNULL(SUM(oi.precio_unitario * oi.cantidad), 0)
        FROM orden_items oi JOIN ordenes o ON oi.orden_id = o.id
        WHERE oi.tipo = 'mercancia' AND o.estado = 'pagado' AND o.created_at BETWEEN ? AND ?
    ");
    $ingresosProductos->execute([$desdeTs, $hastaTs]);
    $ingProductos = (float)$ingresosProductos->fetchColumn();

    $ingTalleres = $pdo->prepare("
        SELECT ISNULL(SUM(oi.precio_unitario * oi.cantidad), 0)
        FROM orden_items oi JOIN ordenes o ON oi.orden_id = o.id
        WHERE oi.tipo = 'taller' AND o.estado = 'pagado' AND o.created_at BETWEEN ? AND ?
    ");
    $ingTalleres->execute([$desdeTs, $hastaTs]);
    $ingCursos = (float)$ingTalleres->fetchColumn();

    // Top obras por boletos
    $stmtTopObras = $pdo->prepare("
        SELECT TOP 5 oi.nombre_item as titulo, SUM(oi.cantidad) as cant, SUM(oi.precio_unitario * oi.cantidad) as total
        FROM orden_items oi JOIN ordenes o ON oi.orden_id = o.id
        WHERE oi.tipo = 'boleto' AND o.estado = 'pagado' AND o.created_at BETWEEN ? AND ?
        GROUP BY oi.nombre_item ORDER BY total DESC
    ");
    $stmtTopObras->execute([$desdeTs, $hastaTs]);
    $topObras = $stmtTopObras->fetchAll(PDO::FETCH_ASSOC);

    // Top productos
    $stmtTopProd = $pdo->prepare("
        SELECT TOP 5 oi.nombre_item as titulo, SUM(oi.cantidad) as cant, SUM(oi.precio_unitario * oi.cantidad) as total
        FROM orden_items oi JOIN ordenes o ON oi.orden_id = o.id
        WHERE oi.tipo = 'mercancia' AND o.estado = 'pagado' AND o.created_at BETWEEN ? AND ?
        GROUP BY oi.nombre_item ORDER BY total DESC
    ");
    $stmtTopProd->execute([$desdeTs, $hastaTs]);
    $topProductos = $stmtTopProd->fetchAll(PDO::FETCH_ASSOC);

    // Top talleres
    $stmtTopTaller = $pdo->prepare("
        SELECT TOP 5 t.titulo, COUNT(i.id) as inscritos, t.precio
        FROM inscripciones_talleres i JOIN talleres t ON i.taller_id = t.id
        WHERE i.estado = 'confirmada' AND i.created_at BETWEEN ? AND ?
        GROUP BY t.titulo, t.precio ORDER BY inscritos DESC
    ");
    $stmtTopTaller->execute([$desdeTs, $hastaTs]);
    $topTalleres = $stmtTopTaller->fetchAll(PDO::FETCH_ASSOC);

    // Periodo anterior para comparación
    $diffDias = (strtotime($hasta) - strtotime($desde)) / 86400;
    $desdeAnt = date('Y-m-d', strtotime($desde) - ($diffDias + 1) * 86400);
    $hastaAnt = date('Y-m-d', strtotime($desde) - 86400);
    $stmtAnt = $pdo->prepare("SELECT ISNULL(SUM(total),0) FROM ordenes WHERE estado='pagado' AND created_at BETWEEN ? AND ?");
    $stmtAnt->execute([$desdeAnt . ' 00:00:00', $hastaAnt . ' 23:59:59']);
    $ingresosAnt = (float)$stmtAnt->fetchColumn();
    $cambio = $ingresosAnt > 0 ? (((float)$kpi['ingresos_totales'] - $ingresosAnt) / $ingresosAnt) * 100 : 0;

} catch (PDOException $e) {
    $kpi = ['ingresos_totales' => 0, 'total_ordenes' => 0, 'promedio_pedido' => 0];
    $kpiBoletos = $kpiProductos = $kpiTalleres = 0;
    $datosMes = $topObras = $topProductos = $topTalleres = [];
    $ingBoletos = $ingProductos = $ingCursos = $cambio = 0;
}

// Cargar últimas cargas de Openpay para validar
try {
    $openpayCharges = openpay_request('GET', '/' . OPENPAY_MERCHANT_ID . '/charges?limit=5&offset=0');
} catch (Exception $e) {
    $openpayCharges = [];
}

// Preparar datos para gráfica
$mesesLabels = array_column($datosMes, 'mes');
$mesesData   = array_column($datosMes, 'total');
$totalIngCategoria = $ingBoletos + $ingProductos + $ingCursos;
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reportes de Ventas | Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;900&family=Forum&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .kpi-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 0.75rem; padding: 1.25rem; }
    .kpi-label { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.4rem; display: flex; justify-content: space-between; align-items: center; }
    .kpi-value { font-size: 2rem; font-weight: 800; color: #fff; margin: 0; }
    .kpi-change { font-size: 0.72rem; color: #4ade80; margin-top: 0.25rem; }
    .kpi-change.neg { color: #f87171; }
    .charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
    @media (max-width: 900px) { .charts-row { grid-template-columns: 1fr; } }
    .chart-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 0.75rem; padding: 1.5rem; }
    .chart-title { font-size: 1rem; font-weight: 700; color: var(--gold); margin-bottom: 1.25rem; }
    .tables-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
    .table-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 0.75rem; padding: 1.25rem; }
    .table-card h3 { font-size: 0.9rem; font-weight: 700; color: var(--gold); margin: 0 0 1rem; }
    .table-card table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
    .table-card td { padding: 0.5rem 0.25rem; color: #ccc; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: top; }
    .table-card td:last-child { text-align: right; color: #fff; font-weight: 700; }
    .donut-legend { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem; font-size: 0.8rem; }
    .donut-legend-item { display: flex; justify-content: space-between; align-items: center; }
    .donut-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 0.4rem; }
    .filter-bar { display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap; margin-bottom: 2rem; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 0.75rem; padding: 1rem 1.25rem; }
    .filter-bar label { font-size: 0.75rem; color: var(--text-muted); display: block; margin-bottom: 0.25rem; }
    .filter-bar input[type=date] { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.4rem; color: #fff; padding: 0.45rem 0.75rem; font-size: 0.85rem; }
  </style>
</head>
<body style="background:var(--background-dark);">
<div class="admin-layout">
  <?php mitos_admin_sidebar('reportes'); ?>

  <main class="admin-content" style="padding:2rem; overflow-y:auto;">

    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom:2rem;">
      <div>
        <h1 style="font-size:1.8rem; font-weight:800; color:#fff; margin:0 0 0.2rem;">Reportes de Ventas</h1>
        <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">Análisis de rendimiento y transacciones históricas</p>
      </div>
      <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
        <a href="<?php echo htmlspecialchars(mitos_url('admin/export-reporte.php')); ?>?tipo=reportes&desde=<?php echo urlencode($desde); ?>&hasta=<?php echo urlencode($hasta); ?>"
           class="btn-secondary" style="display:inline-flex; align-items:center; gap:0.4rem;">
          <span class="material-symbols-outlined" style="font-size:1rem;">download</span> Exportar Excel
        </a>
        <a href="<?php echo htmlspecialchars(mitos_url('admin/transacciones.php')); ?>" class="btn-primary" style="display:inline-flex; align-items:center; gap:0.4rem;">
          <span class="material-symbols-outlined" style="font-size:1rem;">receipt_long</span> Ver Transacciones
        </a>
      </div>
    </div>

    <!-- Filtro de fechas -->
    <form method="GET" class="filter-bar">
      <div>
        <label>Desde</label>
        <input type="date" name="desde" value="<?php echo htmlspecialchars($desde); ?>">
      </div>
      <div>
        <label>Hasta</label>
        <input type="date" name="hasta" value="<?php echo htmlspecialchars($hasta); ?>">
      </div>
      <button type="submit" class="btn-secondary" style="padding:0.45rem 1rem; font-size:0.85rem;">Aplicar</button>
    </form>

    <!-- KPIs -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-label">Ingresos Totales <span class="material-symbols-outlined" style="font-size:1.1rem; color:var(--gold);">payments</span></div>
        <p class="kpi-value">$<?php echo number_format((float)$kpi['ingresos_totales'], 2); ?></p>
        <div class="kpi-change <?php echo $cambio < 0 ? 'neg' : ''; ?>">
          <?php echo ($cambio >= 0 ? '▲ +' : '▼ ') . number_format(abs($cambio), 1); ?>% vs periodo ant.
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Boletos Vendidos <span class="material-symbols-outlined" style="font-size:1.1rem; color:#e3b04b;">confirmation_number</span></div>
        <p class="kpi-value"><?php echo number_format($kpiBoletos); ?></p>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Productos Tienda <span class="material-symbols-outlined" style="font-size:1.1rem; color:#60a5fa;">inventory_2</span></div>
        <p class="kpi-value"><?php echo number_format($kpiProductos); ?></p>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Promedio / Pedido <span class="material-symbols-outlined" style="font-size:1.1rem; color:#a78bfa;">avg_pace</span></div>
        <p class="kpi-value">$<?php echo number_format((float)$kpi['promedio_pedido'], 2); ?></p>
      </div>
    </div>

    <!-- Gráficas -->
    <div class="charts-row">
      <div class="chart-card">
        <div class="chart-title">Ingresos Mensuales (MXN)</div>
        <canvas id="chartMeses" height="120"></canvas>
      </div>
      <div class="chart-card">
        <div class="chart-title">Desglose por Categoría</div>
        <canvas id="chartDonut" height="180"></canvas>
        <div class="donut-legend">
          <?php
          $totalCat = $ingBoletos + $ingProductos + $ingCursos ?: 1;
          $cats = [
            ['Boletos', $ingBoletos, '#950c13'],
            ['Productos', $ingProductos, '#e3b04b'],
            ['Cursos',   $ingCursos,   '#a78bfa'],
          ];
          foreach ($cats as [$label, $val, $color]):
            $pct = round($val / $totalCat * 100);
          ?>
          <div class="donut-legend-item">
            <span><span class="donut-dot" style="background:<?php echo $color; ?>"></span><?php echo $label; ?> (<?php echo $pct; ?>%)</span>
            <span style="color:#fff; font-weight:700;"><?php echo number_format($val, 0); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Tablas Top -->
    <div class="tables-row">
      <div class="table-card">
        <h3>🎭 Producciones más Vendidas</h3>
        <?php if (empty($topObras)): ?>
          <p style="color:var(--text-muted); font-size:0.8rem;">Sin datos en este periodo.</p>
        <?php else: ?>
        <table>
          <?php foreach ($topObras as $row): ?>
          <tr>
            <td><?php echo htmlspecialchars($row['titulo'] ?? 'N/A'); ?><br><span style="color:#666;font-size:0.72rem;"><?php echo $row['cant']; ?> boletos</span></td>
            <td>$<?php echo number_format((float)$row['total'], 0); ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
      </div>

      <div class="table-card">
        <h3>🛍️ Productos Destacados (Tienda)</h3>
        <?php if (empty($topProductos)): ?>
          <p style="color:var(--text-muted); font-size:0.8rem;">Sin datos en este periodo.</p>
        <?php else: ?>
        <table>
          <?php foreach ($topProductos as $row): ?>
          <tr>
            <td><?php echo htmlspecialchars($row['titulo'] ?? 'N/A'); ?><br><span style="color:#666;font-size:0.72rem;"><?php echo $row['cant']; ?> unidades</span></td>
            <td>$<?php echo number_format((float)$row['total'], 0); ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
      </div>

      <div class="table-card">
        <h3>🎓 Talleres más Inscritos</h3>
        <?php if (empty($topTalleres)): ?>
          <p style="color:var(--text-muted); font-size:0.8rem;">Sin datos en este periodo.</p>
        <?php else: ?>
        <table>
          <?php foreach ($topTalleres as $row): ?>
          <tr>
            <td><?php echo htmlspecialchars($row['titulo'] ?? 'N/A'); ?><br><span style="color:#666;font-size:0.72rem;"><?php echo $row['inscritos']; ?> inscritos</span></td>
            <td>$<?php echo number_format((float)$row['inscritos'] * (float)$row['precio'], 0); ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- Últimas cargas Openpay -->
    <div class="chart-card" style="margin-bottom:2rem;">
      <div class="chart-title" style="display:flex; justify-content:space-between; align-items:center;">
        <span>Últimas cargas Openpay (en vivo)</span>
        <span style="font-size:0.75rem; color:var(--text-muted);"><?php echo OPENPAY_SANDBOX ? '⚠️ Modo Sandbox' : '🟢 Producción'; ?></span>
      </div>
      <?php if (isset($openpayCharges['data']) && is_array($openpayCharges['data'])): ?>
      <table style="width:100%; border-collapse:collapse; font-size:0.82rem;">
        <thead>
          <tr style="color:var(--text-muted); text-align:left; border-bottom:1px solid rgba(255,255,255,0.08);">
            <th style="padding:0.5rem;">ID</th><th>Descripción</th><th>Monto</th><th>Estatus</th><th>Fecha</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($openpayCharges['data'], 0, 8) as $ch): ?>
          <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
            <td style="padding:0.5rem; color:#777; font-size:0.72rem;"><?php echo htmlspecialchars(substr($ch['id'] ?? '', 0, 12)); ?>…</td>
            <td style="padding:0.5rem; color:#ccc;"><?php echo htmlspecialchars(substr($ch['description'] ?? '', 0, 40)); ?></td>
            <td style="padding:0.5rem; color:#fff; font-weight:700;">$<?php echo number_format((float)($ch['amount'] ?? 0), 2); ?> MXN</td>
            <td style="padding:0.5rem;">
              <?php $st = $ch['status'] ?? ''; ?>
              <span style="padding:0.15rem 0.5rem; border-radius:0.25rem; font-size:0.7rem; font-weight:700;
                background:<?php echo $st === 'completed' ? 'rgba(74,222,128,0.15)' : 'rgba(248,113,113,0.15)'; ?>;
                color:<?php echo $st === 'completed' ? '#4ade80' : '#f87171'; ?>;">
                <?php echo strtoupper($st); ?>
              </span>
            </td>
            <td style="padding:0.5rem; color:var(--text-muted); font-size:0.75rem;"><?php echo isset($ch['creation_date']) ? date('d/m/Y H:i', strtotime($ch['creation_date'])) : '—'; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <p style="color:var(--text-muted); font-size:0.85rem; margin:0;">
          <?php echo isset($openpayCharges['error_code']) ? '⚠️ Error Openpay: ' . htmlspecialchars($openpayCharges['description'] ?? 'Sin conexión') : 'No se pudo conectar con Openpay.'; ?>
        </p>
      <?php endif; ?>
    </div>

  </main>
</div>

<script>
// ── Gráfica de barras mensual ──────────────────────────────────────────────
const ctxM = document.getElementById('chartMeses').getContext('2d');
new Chart(ctxM, {
  type: 'line',
  data: {
    labels: <?php echo json_encode($mesesLabels); ?>,
    datasets: [{
      label: 'Ingresos MXN',
      data: <?php echo json_encode(array_map('floatval', $mesesData)); ?>,
      borderColor: '#e3b04b',
      backgroundColor: 'rgba(227,176,75,0.12)',
      pointBackgroundColor: '#e3b04b',
      fill: true,
      tension: 0.4,
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: {
      x: { ticks: { color: '#888' }, grid: { color: 'rgba(255,255,255,0.05)' } },
      y: { ticks: { color: '#888', callback: v => '$'+v.toLocaleString() }, grid: { color: 'rgba(255,255,255,0.05)' } }
    }
  }
});

// ── Gráfica donut ──────────────────────────────────────────────────────────
const ctxD = document.getElementById('chartDonut').getContext('2d');
new Chart(ctxD, {
  type: 'doughnut',
  data: {
    labels: ['Boletos', 'Productos', 'Cursos'],
    datasets: [{
      data: [<?php echo floatval($ingBoletos); ?>, <?php echo floatval($ingProductos); ?>, <?php echo floatval($ingCursos); ?>],
      backgroundColor: ['#950c13', '#e3b04b', '#a78bfa'],
      borderWidth: 0,
    }]
  },
  options: {
    cutout: '70%',
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ' $' + ctx.parsed.toLocaleString() + ' MXN' }}
    }
  }
});
</script>
</body>
</html>
