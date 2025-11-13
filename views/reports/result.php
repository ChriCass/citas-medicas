<?php
// Variables esperadas: $title, $rows, $message, $desde, $hasta
$role = $_SESSION['user']['rol'] ?? null;
if ($role !== 'superadmin') {
    echo '<div class="alert">No tienes acceso a esta pantalla.</div>';
    return;
}

$title = $title ?? 'Reporte';
$rows = $rows ?? [];
?>

<h1><?= htmlspecialchars($title) ?></h1>
<?php
// Información de encabezado: quién generó el reporte, momento y rango de fechas
$generatedBy = trim((
    ($_SESSION['user']['nombre'] ?? '') . ' ' . ($_SESSION['user']['apellido'] ?? '')
));
if ($generatedBy === '') {
    $generatedBy = $_SESSION['user']['username'] ?? ($_SESSION['user']['email'] ?? 'Usuario desconocido');
}
$generatedAt = date('Y-m-d H:i:s');
$rangeDesde = $desde ?? '';
$rangeHasta = $hasta ?? '';
?>
<?php if (!empty($message)): ?>
    <div class="alert mt-2"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (empty($rows)): ?>
    <div class="card"><p class="mt-2">No hay registros que mostrar.</p>
        <div class="form-actions"><a class="btn" href="/reports">Volver</a></div>
    </div>
<?php else: ?>
    <div class="card" style="padding:16px;">
    <div class="report-meta mb-2" style="line-height:1.4; padding-bottom:8px;">
        <div><strong>Generado por:</strong> <?= htmlspecialchars($generatedBy) ?></div>
        <div><strong>Fecha/Hora:</strong> <?= htmlspecialchars($generatedAt) ?></div>
        <div><strong>Desde</strong> <?= htmlspecialchars($rangeDesde) ?> <strong>Hasta</strong> <?= htmlspecialchars($rangeHasta) ?></div>
    </div>
    <p>
        Mostrando <?= count($rows) ?> registros.
        &nbsp;
        <a class="btn primary" href="/reports/export?format=pdf&desde=<?= urlencode($desde ?? '') ?>&hasta=<?= urlencode($hasta ?? '') ?>&tipo=<?= urlencode($tipo ?? '') ?>">Exportar PDF</a>
        <a class="btn" href="/reports/export?format=xlsx&desde=<?= urlencode($desde ?? '') ?>&hasta=<?= urlencode($hasta ?? '') ?>&tipo=<?= urlencode($tipo ?? '') ?>">Exportar Excel</a>
    </p>
        <div class="table-responsive" style="padding-top:8px;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Estado</th><th>Pago</th><th>Sede</th><th>Doctor</th><th>Paciente</th><th>Email</th><th>Teléfono</th><th>Diagnóstico</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['estado'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['pago'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['sede_nombre'] ?? '') ?></td>
                        <td><?= htmlspecialchars((($r['doctor_nombre'] ?? '') . ' ' . ($r['doctor_apellido'] ?? '')) ) ?></td>
                        <td><?= htmlspecialchars(($r['paciente_nombre'] ?? '') . ' ' . ($r['paciente_apellido'] ?? '')) ?></td>
                        <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['telefono'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['diagnostico_nombre'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="form-actions" style="margin-top:16px;"><a class="btn primary" href="/reports">Nuevo reporte</a></div>
    </div>
<?php endif; ?>
