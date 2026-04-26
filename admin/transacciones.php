<?php
require_once __DIR__ . '/../php/init.php';
require_once __DIR__ . '/../php/layout.php';
require_once __DIR__ . '/../php/openpay-helper.php';
mitos_requiere_admin();

$pdo = mitos_pdo();

// ── Filtros ────────────────────────────────────────────────────────────────
$desde    = $_GET['desde']  ?? date('Y-m-01');
$hasta    = $_GET['hasta']  ?? date('Y-m-t');
$filtroTipo = $_GET['tipo'] ?? 'todos'; // todos | openpay | manual
$pagina   = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 20;
$offset   = ($pagina - 1) * $porPagina;
$desdeTs  = $desde . ' 00:00:00';
$hastaTs  = $hasta . ' 23:59:59';

// ── KPIs ───────────────────────────────────────────────────────────────────
try {
    $stmtTotal = $pdo->prepare("
        SELECT ISNULL(SUM(total), 0) FROM ordenes
        WHERE estado = 'pagado' AND created_at BETWEEN ? AND ?
    ");
    $stmtTotal->execute([$desdeTs, $hastaTs]);
    $ingresosTotal = (float)$stmtTotal->fetchColumn();

    $stmtOP = $pdo->prepare("
        SELECT ISNULL(SUM(total), 0), COUNT(*)
        FROM ordenes
        WHERE estado = 'pagado' AND openpay_charge_id IS NOT NULL AND created_at BETWEEN ? AND ?
    ");
    $stmtOP->execute([$desdeTs, $hastaTs]);
    [$ingresosOpenpay, $countOpenpay] = $stmtOP->fetch(PDO::FETCH_NUM) + [0, 0];

    $stmtMan = $pdo->prepare("
        SELECT ISNULL(SUM(total), 0), COUNT(*)
        FROM ordenes
        WHERE estado = 'pagado' AND openpay_charge_id IS NULL AND created_at BETWEEN ? AND ?
    ");
    $stmtMan->execute([$desdeTs, $hastaTs]);
    [$ingresosManual, $countManual] = $stmtMan->fetch(PDO::FETCH_NUM) + [0, 0];

    // Talleres confirmados por cobro externo (sin orden Openpay)
    $stmtExt = $pdo->prepare("
        SELECT COUNT(*), ISNULL(SUM(t.precio), 0)
        FROM inscripciones_talleres i
        JOIN talleres t ON i.taller_id = t.id
        WHERE i.estado = 'confirmada' AND i.created_at BETWEEN ? AND ?
        AND NOT EXISTS (SELECT 1 FROM orden_items oi JOIN ordenes o ON oi.orden_id = o.id
                        WHERE oi.tipo = 'taller' AND oi.referencia_id = i.taller_id
                        AND o.usuario_id = i.usuario_id AND o.estado = 'pagado')
    ");
    $stmtExt->execute([$desdeTs, $hastaTs]);
    [$countCobrosExt, $ingCobrosExt] = $stmtExt->fetch(PDO::FETCH_NUM) + [0, 0];

    // Listado paginado de órdenes-transacciones
    $whereClauses = ["o.created_at BETWEEN ? AND ?"];
    $params = [$desdeTs, $hastaTs];

    if ($filtroTipo === 'openpay') {
        $whereClauses[] = "o.openpay_charge_id IS NOT NULL";
    } elseif ($filtroTipo === 'manual') {
        $whereClauses[] = "o.openpay_charge_id IS NULL";
    }

    $where = implode(' AND ', $whereClauses);

    $countQuery = $pdo->prepare("SELECT COUNT(*) FROM ordenes o WHERE o.estado = 'pagado' AND $where");
    $countQuery->execute($params);
    $totalTx = (int)$countQuery->fetchColumn();

    $stmtTx = $pdo->prepare("
        SELECT o.id, o.openpay_charge_id, o.total, o.created_at, o.nombre_factura, o.email_factura,
               u.nombre as user_nombre, u.email as user_email
        FROM ordenes o
        LEFT JOIN usuarios u ON o.usuario_id = u.id
        WHERE o.estado = 'pagado' AND $where
        ORDER BY o.created_at DESC
        OFFSET $offset ROWS FETCH NEXT $porPagina ROWS ONLY
    ");
    $stmtTx->execute($params);
    $transacciones = $stmtTx->fetchAll(PDO::FETCH_ASSOC);

    $totalPaginas = max(1, (int)ceil($totalTx / $porPagina));

} catch (PDOException $e) {
    $ingresosTotal = $ingresosOpenpay = $ingresosManual = $ingCobrosExt = 0;
    $countOpenpay = $countManual = $countCobrosExt = $totalTx = 0;
    $transacciones = [];
    $totalPaginas = 1;
}

// ── Cargas Openpay en vivo (últimas 50) ───────────────────────────────────
$openpayData = openpay_request('GET', '/' . OPENPAY_MERCHANT_ID . '/charges?limit=50&offset=0');
$openpayChargesMap = [];
if (isset($openpayData['data']) && is_array($openpayData['data'])) {
    foreach ($openpayData['data'] as $charge) {
        $openpayChargesMap[$charge['id']] = $charge;
    }
}

function buildUrl(array $extras = []): string {
    $base = array_merge($_GET, $extras);
    return '?' . http_build_query($base);
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Transacciones | Admin Mitos Escénicos</title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;900&family=Forum&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
  <style>
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .kpi-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 0.75rem; padding: 1.25rem; }
    .kpi-label { font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.4rem; }
    .kpi-value { font-size: 1.8rem; font-weight: 800; color: #fff; margin: 0; }
    .kpi-sub { font-size: 0.72rem; color: #aaa; margin-top: 0.25rem; }
    .filter-bar { display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap; margin-bottom: 1.5rem;
                  background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
                  border-radius: 0.75rem; padding: 1rem 1.25rem; }
    .filter-bar label { font-size: 0.75rem; color: var(--text-muted); display: block; margin-bottom: 0.25rem; }
    .filter-bar input[type=date], .filter-bar select { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15);
        border-radius: 0.4rem; color: #fff; padding: 0.4rem 0.65rem; font-size: 0.82rem; }
    .badge-op { background:rgba(74,222,128,0.12); color:#4ade80; padding:0.15rem 0.5rem; border-radius:0.25rem; font-size:0.7rem; font-weight:700; }
    .badge-man { background:rgba(251,191,36,0.12); color:#fbbf24; padding:0.15rem 0.5rem; border-radius:0.25rem; font-size:0.7rem; font-weight:700; }
    .badge-ext { background:rgba(167,139,250,0.12); color:#a78bfa; padding:0.15rem 0.5rem; border-radius:0.25rem; font-size:0.7rem; font-weight:700; }
    .tx-table { width:100%; border-collapse:collapse; font-size:0.82rem; }
    .tx-table th { padding:0.6rem 0.75rem; color:var(--text-muted); text-align:left; border-bottom:1px solid rgba(255,255,255,0.08); font-size:0.72rem; text-transform:uppercase; }
    .tx-table td { padding:0.65rem 0.75rem; border-bottom:1px solid rgba(255,255,255,0.04); vertical-align:middle; }
    .tx-table tr:hover td { background:rgba(255,255,255,0.02); }
    .pg-btn { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px;
              border-radius:0.4rem; background:rgba(255,255,255,0.05); color:#ccc;
              text-decoration:none; font-size:0.82rem; transition:background 0.15s; }
    .pg-btn:hover, .pg-btn.active { background:var(--primary); color:#fff; }
  </style>
</head>
<body style="background:var(--background-dark);">
<div class="admin-layout">
  <?php mitos_admin_sidebar('transacciones'); ?>

  <main class="admin-content" style="padding:2rem; overflow-y:auto;">

    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom:2rem;">
      <div>
        <h1 style="font-size:1.8rem; font-weight:800; color:#fff; margin:0 0 0.2rem;">Transacciones</h1>
        <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">
          Listado detallado de pagos — <?php echo OPENPAY_SANDBOX ? '<span style="color:#fbbf24;">⚠️ Sandbox</span>' : '<span style="color:#4ade80;">🟢 Producción</span>'; ?>
        </p>
      </div>
      <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
        <a href="<?php echo htmlspecialchars(mitos_url('admin/export-reporte.php')); ?>?tipo=transacciones&desde=<?php echo urlencode($desde); ?>&hasta=<?php echo urlencode($hasta); ?>&filtro_tipo=<?php echo urlencode($filtroTipo); ?>"
           class="btn-secondary" style="display:inline-flex; align-items:center; gap:0.4rem;">
          <span class="material-symbols-outlined" style="font-size:1rem;">download</span> Exportar Excel
        </a>
        <a href="<?php echo htmlspecialchars(mitos_url('admin/reportes.php')); ?>" class="btn-secondary" style="display:inline-flex; align-items:center; gap:0.4rem;">
          <span class="material-symbols-outlined" style="font-size:1rem;">bar_chart</span> Ver Reportes
        </a>
      </div>
    </div>

    <!-- KPIs -->
    <div class="kpi-grid">
      <div class="kpi-card" style="border-color:rgba(74,222,128,0.25);">
        <div class="kpi-label">Total Ingresos Período</div>
        <p class="kpi-value">$<?php echo number_format($ingresosTotal, 2); ?></p>
        <div class="kpi-sub">Combinado: Openpay + Manual</div>
      </div>
      <div class="kpi-card" style="border-color:rgba(74,222,128,0.25);">
        <div class="kpi-label">Pagos vía Openpay</div>
        <p class="kpi-value" style="color:#4ade80;">$<?php echo number_format((float)$ingresosOpenpay, 2); ?></p>
        <div class="kpi-sub"><?php echo number_format((int)$countOpenpay); ?> transacciones validadas</div>
      </div>
      <div class="kpi-card" style="border-color:rgba(251,191,36,0.25);">
        <div class="kpi-label">Cobros Externos / Manuales</div>
        <p class="kpi-value" style="color:#fbbf24;">$<?php echo number_format((float)$ingresosManual, 2); ?></p>
        <div class="kpi-sub"><?php echo number_format((int)$countManual); ?> órdenes manuales</div>
      </div>
      <div class="kpi-card" style="border-color:rgba(167,139,250,0.25);">
        <div class="kpi-label">Talleres Cobro Externo</div>
        <p class="kpi-value" style="color:#a78bfa;"><?php echo number_format((int)$countCobrosExt); ?></p>
        <div class="kpi-sub">~$<?php echo number_format((float)$ingCobrosExt, 2); ?> estimado</div>
      </div>
    </div>

    <!-- Filtros -->
    <form method="GET" class="filter-bar">
      <div>
        <label>Desde</label>
        <input type="date" name="desde" value="<?php echo htmlspecialchars($desde); ?>">
      </div>
      <div>
        <label>Hasta</label>
        <input type="date" name="hasta" value="<?php echo htmlspecialchars($hasta); ?>">
      </div>
      <div>
        <label>Tipo de pago</label>
        <select name="tipo">
          <option value="todos"   <?php echo $filtroTipo === 'todos'   ? 'selected' : ''; ?>>Todos</option>
          <option value="openpay" <?php echo $filtroTipo === 'openpay' ? 'selected' : ''; ?>>Openpay</option>
          <option value="manual"  <?php echo $filtroTipo === 'manual'  ? 'selected' : ''; ?>>Manual / Externo</option>
        </select>
      </div>
      <button type="submit" class="btn-secondary" style="padding:0.4rem 1rem; font-size:0.82rem;">Filtrar</button>
      <span style="margin-left:auto; font-size:0.8rem; color:var(--text-muted);"><?php echo number_format($totalTx); ?> transacciones encontradas</span>
    </form>

    <!-- Tabla -->
    <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:0.75rem; overflow:auto;">
      <table class="tx-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Order ID</th>
            <th>Titular / Email</th>
            <th>Tipo</th>
            <th>Monto</th>
            <th>Estado Openpay</th>
            <th>Fecha</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($transacciones)): ?>
          <tr><td colspan="8" style="text-align:center; color:var(--text-muted); padding:2rem;">Sin transacciones en este periodo.</td></tr>
          <?php else: ?>
          <?php foreach ($transacciones as $idx => $tx):
            $esOpenpay = !empty($tx['openpay_charge_id']);
            $chargeInfo = $esOpenpay ? ($openpayChargesMap[$tx['openpay_charge_id']] ?? null) : null;
            $statusOP = $chargeInfo ? $chargeInfo['status'] : null;
            $rowNum = $offset + $idx + 1;
          ?>
          <tr>
            <td style="color:#666;"><?php echo str_pad($rowNum, 2, '0', STR_PAD_LEFT); ?></td>
            <td style="font-family:monospace; font-size:0.75rem; color:#aaa;">#<?php echo str_pad($tx['id'], 6, '0', STR_PAD_LEFT); ?></td>
            <td>
              <span style="color:#fff; font-weight:600;"><?php echo htmlspecialchars($tx['nombre_factura'] ?: ($tx['user_nombre'] ?? '—')); ?></span>
              <span style="display:block; font-size:0.72rem; color:#777;"><?php echo htmlspecialchars($tx['email_factura'] ?: ($tx['user_email'] ?? '')); ?></span>
            </td>
            <td>
              <?php if ($esOpenpay): ?>
                <span class="badge-op">OPENPAY</span>
              <?php else: ?>
                <span class="badge-man">MANUAL</span>
              <?php endif; ?>
            </td>
            <td style="color:#fff; font-weight:700;">$<?php echo number_format((float)$tx['total'], 2); ?> MXN</td>
            <td>
              <?php if ($statusOP): ?>
                <span style="padding:0.15rem 0.5rem; border-radius:0.25rem; font-size:0.7rem; font-weight:700;
                  background:<?php echo $statusOP === 'completed' ? 'rgba(74,222,128,0.12)' : 'rgba(248,113,113,0.12)'; ?>;
                  color:<?php echo $statusOP === 'completed' ? '#4ade80' : '#f87171'; ?>;">
                  <?php echo strtoupper($statusOP); ?>
                </span>
              <?php elseif ($esOpenpay): ?>
                <span style="color:#777; font-size:0.75rem;">No encontrado</span>
              <?php else: ?>
                <span style="color:#555; font-size:0.75rem;">—</span>
              <?php endif; ?>
            </td>
            <td style="color:var(--text-muted); font-size:0.78rem; white-space:nowrap;"><?php echo date('d/m/Y H:i', strtotime($tx['created_at'])); ?></td>
            <td>
              <?php if ($esOpenpay && $chargeInfo): ?>
                <button onclick='showDetail(<?php echo htmlspecialchars(json_encode($chargeInfo), ENT_QUOTES); ?>)'
                  style="background:none; border:1px solid rgba(255,255,255,0.12); border-radius:0.35rem; color:#aaa; padding:0.2rem 0.5rem; cursor:pointer; font-size:0.75rem;">
                  <span class="material-symbols-outlined" style="font-size:0.9rem; vertical-align:middle;">visibility</span>
                </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Paginación -->
    <?php if ($totalPaginas > 1): ?>
    <div style="display:flex; justify-content:center; gap:0.4rem; margin-top:1.5rem; flex-wrap:wrap;">
      <?php if ($pagina > 1): ?>
        <a href="<?php echo buildUrl(['pagina' => $pagina - 1]); ?>" class="pg-btn">‹</a>
      <?php endif; ?>
      <?php for ($p = max(1, $pagina-2); $p <= min($totalPaginas, $pagina+2); $p++): ?>
        <a href="<?php echo buildUrl(['pagina' => $p]); ?>" class="pg-btn <?php echo $p === $pagina ? 'active' : ''; ?>"><?php echo $p; ?></a>
      <?php endfor; ?>
      <?php if ($pagina < $totalPaginas): ?>
        <a href="<?php echo buildUrl(['pagina' => $pagina + 1]); ?>" class="pg-btn">›</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </main>
</div>

<!-- Modal detalle cargo Openpay -->
<div id="modal-detail" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center;">
  <div style="background:#1a1a1a; border:1px solid #333; border-radius:0.75rem; max-width:480px; width:90%; padding:1.5rem; max-height:80vh; overflow-y:auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <h3 style="color:var(--gold); margin:0;">Detalle Cargo Openpay</h3>
      <button onclick="document.getElementById('modal-detail').style.display='none'"
        style="background:none; border:none; color:#aaa; font-size:1.5rem; cursor:pointer;">×</button>
    </div>
    <pre id="modal-json" style="background:rgba(255,255,255,0.04); border-radius:0.5rem; padding:1rem; font-size:0.72rem; color:#ccc; white-space:pre-wrap; word-break:break-all;"></pre>
  </div>
</div>

<script>
function showDetail(data) {
  document.getElementById('modal-json').textContent = JSON.stringify(data, null, 2);
  document.getElementById('modal-detail').style.display = 'flex';
}
document.getElementById('modal-detail').addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>
