<?php
/**
 * Exportar datos de reportes/transacciones a CSV (Excel compatible).
 * Query params: tipo (reportes|transacciones), desde, hasta, filtro_tipo
 */
require_once __DIR__ . '/../php/init.php';
mitos_requiere_admin();

$pdo   = mitos_pdo();
$tipo  = $_GET['tipo']  ?? 'transacciones';
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-t');
$filtroTipo = $_GET['filtro_tipo'] ?? 'todos';
$desdeTs = $desde . ' 00:00:00';
$hastaTs = $hasta . ' 23:59:59';

// ── Función auxiliar para líneas CSV seguras ──────────────────────────────
function csvRow(array $cols): string {
    return implode(',', array_map(function($c) {
        $c = str_replace('"', '""', (string)$c);
        return '"' . $c . '"';
    }, $cols)) . "\r\n";
}

// ── Cabeceras HTTP ────────────────────────────────────────────────────────
$filename = 'mitos_' . $tipo . '_' . $desde . '_' . $hasta . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');

// BOM para que Excel reconozca UTF-8
echo "\xEF\xBB\xBF";

if ($tipo === 'transacciones') {
    // ── Exportar Transacciones ────────────────────────────────────────────
    echo csvRow(['#', 'Order ID', 'Nombre Titular', 'Email', 'Tipo Pago', 'Charge ID Openpay', 'Monto (MXN)', 'Fecha']);

    try {
        $where = "o.estado = 'pagado' AND o.created_at BETWEEN ? AND ?";
        $params = [$desdeTs, $hastaTs];

        if ($filtroTipo === 'openpay') {
            $where .= " AND o.openpay_charge_id IS NOT NULL";
        } elseif ($filtroTipo === 'manual') {
            $where .= " AND o.openpay_charge_id IS NULL";
        }

        $stmt = $pdo->prepare("
            SELECT o.id, o.openpay_charge_id, o.total, o.created_at,
                   o.nombre_factura, o.email_factura,
                   u.nombre as user_nombre, u.email as user_email
            FROM ordenes o
            LEFT JOIN usuarios u ON o.usuario_id = u.id
            WHERE $where
            ORDER BY o.created_at DESC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $i = 1;
        foreach ($rows as $r) {
            $tipoPago = !empty($r['openpay_charge_id']) ? 'OPENPAY' : 'MANUAL';
            echo csvRow([
                $i++,
                '#' . str_pad($r['id'], 6, '0', STR_PAD_LEFT),
                $r['nombre_factura'] ?: ($r['user_nombre'] ?? ''),
                $r['email_factura']  ?: ($r['user_email']  ?? ''),
                $tipoPago,
                $r['openpay_charge_id'] ?? '',
                number_format((float)$r['total'], 2, '.', ''),
                $r['created_at'],
            ]);
        }
    } catch (PDOException $e) {
        echo csvRow(['ERROR', $e->getMessage()]);
    }

} else {
    // ── Exportar Resumen de Reportes ──────────────────────────────────────

    // 1. KPIs generales
    echo csvRow(['=== RESUMEN GENERAL ===', '', '', '']);
    echo csvRow(['Métrica', 'Valor', '', '']);

    try {
        $stmt = $pdo->prepare("SELECT ISNULL(SUM(total),0), COUNT(*), ISNULL(AVG(total),0) FROM ordenes WHERE estado='pagado' AND created_at BETWEEN ? AND ?");
        $stmt->execute([$desdeTs, $hastaTs]);
        [$ingresos, $ordenes, $promedio] = $stmt->fetch(PDO::FETCH_NUM) + [0, 0, 0];

        $stmtB = $pdo->prepare("SELECT COUNT(*) FROM orden_items oi JOIN ordenes o ON oi.orden_id=o.id WHERE oi.tipo='boleto' AND o.estado='pagado' AND o.created_at BETWEEN ? AND ?");
        $stmtB->execute([$desdeTs, $hastaTs]);
        $boletos = $stmtB->fetchColumn();

        $stmtP = $pdo->prepare("SELECT COUNT(*) FROM orden_items oi JOIN ordenes o ON oi.orden_id=o.id WHERE oi.tipo='mercancia' AND o.estado='pagado' AND o.created_at BETWEEN ? AND ?");
        $stmtP->execute([$desdeTs, $hastaTs]);
        $productos = $stmtP->fetchColumn();

        $stmtT = $pdo->prepare("SELECT COUNT(*) FROM inscripciones_talleres WHERE estado='confirmada' AND created_at BETWEEN ? AND ?");
        $stmtT->execute([$desdeTs, $hastaTs]);
        $talleres = $stmtT->fetchColumn();

        echo csvRow(['Ingresos Totales (MXN)',   '$' . number_format((float)$ingresos, 2)]);
        echo csvRow(['Órdenes Pagadas',           $ordenes]);
        echo csvRow(['Promedio por Pedido (MXN)', '$' . number_format((float)$promedio, 2)]);
        echo csvRow(['Boletos Vendidos',           $boletos]);
        echo csvRow(['Productos Vendidos',         $productos]);
        echo csvRow(['Talleres Confirmados',       $talleres]);
        echo csvRow(['Período',                    $desde . ' al ' . $hasta]);

        echo csvRow(['', '', '', '']);

        // 2. Top producciones
        echo csvRow(['=== TOP PRODUCCIONES ===', '', '', '']);
        echo csvRow(['Producción', 'Boletos', 'Ingresos (MXN)', '']);
        $stmtTop = $pdo->prepare("
            SELECT TOP 10 oi.nombre_item, SUM(oi.cantidad) as cant, SUM(oi.precio_unitario*oi.cantidad) as total
            FROM orden_items oi JOIN ordenes o ON oi.orden_id=o.id
            WHERE oi.tipo='boleto' AND o.estado='pagado' AND o.created_at BETWEEN ? AND ?
            GROUP BY oi.nombre_item ORDER BY total DESC
        ");
        $stmtTop->execute([$desdeTs, $hastaTs]);
        foreach ($stmtTop->fetchAll(PDO::FETCH_ASSOC) as $r) {
            echo csvRow([$r['nombre_item'], $r['cant'], '$' . number_format((float)$r['total'], 2), '']);
        }

        echo csvRow(['', '', '', '']);

        // 3. Top productos
        echo csvRow(['=== TOP PRODUCTOS TIENDA ===', '', '', '']);
        echo csvRow(['Producto', 'Unidades', 'Ingresos (MXN)', '']);
        $stmtProd = $pdo->prepare("
            SELECT TOP 10 oi.nombre_item, SUM(oi.cantidad) as cant, SUM(oi.precio_unitario*oi.cantidad) as total
            FROM orden_items oi JOIN ordenes o ON oi.orden_id=o.id
            WHERE oi.tipo='mercancia' AND o.estado='pagado' AND o.created_at BETWEEN ? AND ?
            GROUP BY oi.nombre_item ORDER BY total DESC
        ");
        $stmtProd->execute([$desdeTs, $hastaTs]);
        foreach ($stmtProd->fetchAll(PDO::FETCH_ASSOC) as $r) {
            echo csvRow([$r['nombre_item'], $r['cant'], '$' . number_format((float)$r['total'], 2), '']);
        }

        echo csvRow(['', '', '', '']);

        // 4. Top talleres
        echo csvRow(['=== TOP TALLERES ===', '', '', '']);
        echo csvRow(['Taller', 'Inscritos', 'Precio Unitario', 'Ingreso Estimado']);
        $stmtTal = $pdo->prepare("
            SELECT TOP 10 t.titulo, COUNT(i.id) as inscritos, t.precio
            FROM inscripciones_talleres i JOIN talleres t ON i.taller_id=t.id
            WHERE i.estado='confirmada' AND i.created_at BETWEEN ? AND ?
            GROUP BY t.titulo, t.precio ORDER BY inscritos DESC
        ");
        $stmtTal->execute([$desdeTs, $hastaTs]);
        foreach ($stmtTal->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $est = (float)$r['inscritos'] * (float)$r['precio'];
            echo csvRow([$r['titulo'], $r['inscritos'], '$' . number_format((float)$r['precio'], 2), '$' . number_format($est, 2)]);
        }

        echo csvRow(['', '', '', '']);

        // 5. Detalle mensual
        echo csvRow(['=== INGRESOS POR MES ===', '', '', '']);
        echo csvRow(['Mes', 'Año', 'Ingresos (MXN)', '']);
        $stmtMes = $pdo->query("
            SELECT FORMAT(created_at,'MMM','es-MX') as mes, YEAR(created_at) as anio, SUM(total) as total
            FROM ordenes WHERE estado='pagado' AND created_at >= DATEADD(MONTH,-11,GETDATE())
            GROUP BY FORMAT(created_at,'MMM','es-MX'), MONTH(created_at), YEAR(created_at)
            ORDER BY YEAR(created_at), MONTH(created_at)
        ");
        if ($stmtMes) {
            foreach ($stmtMes->fetchAll(PDO::FETCH_ASSOC) as $r) {
                echo csvRow([$r['mes'], $r['anio'], '$' . number_format((float)$r['total'], 2), '']);
            }
        }

    } catch (PDOException $e) {
        echo csvRow(['ERROR', $e->getMessage()]);
    }
}
exit;
