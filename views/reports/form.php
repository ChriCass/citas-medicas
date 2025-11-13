<?php
// Variables esperadas: $title, $error
$role = $_SESSION['user']['rol'] ?? null;
if ($role !== 'superadmin') {
    echo '<div class="alert">No tienes acceso a esta pantalla.</div>';
    return;
}
?>

<h1><?= htmlspecialchars($title ?? 'Generar Reportes') ?></h1>

<?php if (!empty($error)): ?>
    <div class="alert error mt-2"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card" style="padding:16px;">
    <form method="post" class="form mt-3" action="/reports/generate">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">

        <div class="row">
            <label class="label">Desde</label>
            <input type="date" name="desde" required class="input" />
        </div>

        <div class="row">
            <label class="label">Hasta</label>
            <input type="date" name="hasta" required class="input" />
        </div>

        <div class="row">
            <label class="label">Tipo / Estado</label>
            <select name="tipo" class="input">
                <option value="todos">Todos</option>
                <option value="atendidas">Atendidas (atendido)</option>
                <option value="no_atendidas">No atendidas (cualquier estado distinto a atendido)</option>
                <option value="pendiente">Pendiente</option>
                <option value="confirmado">Confirmado</option>
                <option value="ausente">Ausente</option>
                <option value="cancelado">Cancelado</option>
            </select>
        </div>

        <div class="form-actions mt-3">
            <button class="btn primary" type="submit">Generar</button>
            <button type="button" class="btn" onclick="window.location.href='/dashboard'">Cancelar</button>
        </div>
    </form>
</div>
