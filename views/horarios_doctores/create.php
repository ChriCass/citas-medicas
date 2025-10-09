<?php
// Espera $title, $doctors, $sedes, $error (opcional) y $old (opcional)
// El rol ya lo valida el middleware/controlador; si igual quieres bloquear:
$role = $_SESSION['user']['rol'] ?? '';
?>
<?php if ($role !== 'superadmin'): ?>
  <div class="alert">No tienes acceso a esta pantalla.</div>
  <?php return; ?>
<?php endif; ?>

<h1><?= htmlspecialchars($title ?? 'Nuevo Horario') ?></h1>

<?php if (!empty($error)): ?>
  <div class="alert error mt-2"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" class="form mt-3" action="/doctor-schedules">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">

  <div class="row">
    <label class="label" for="doctor_id">Doctor</label>
    <select name="doctor_id" id="doctor_id" class="input" required>
      <option value="">— Selecciona —</option>
      <?php foreach (($doctors ?? []) as $d): ?>
        <option value="<?= (int)$d['id'] ?>" <?= (!empty($old['doctor_id']) && (int)$old['doctor_id']===(int)$d['id'])? 'selected':'' ?>>
          <?= htmlspecialchars(($d['nombre'] ?? '').' '.($d['apellido'] ?? '')) ?> — <?= htmlspecialchars($d['email'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row">
    <label class="label" for="sede_id">Sede</label>
    <select name="sede_id" id="sede_id" class="input">
      <option value="">— Selecciona —</option>
      <?php foreach (($sedes ?? []) as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= (!empty($old['sede_id']) && (int)$old['sede_id']===(int)$s['id'])? 'selected':'' ?>>
          <?= htmlspecialchars($s['nombre_sede'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row">
    <label class="label" for="fecha">Fecha</label>
    <input type="date" name="fecha" id="fecha" class="input" required
           value="<?= htmlspecialchars($old['fecha'] ?? '') ?>">
    <small class="hint">Selecciona la fecha específica para este horario.</small>
  </div>

  <div class="row">
    <label class="label" for="start_time">Hora inicio</label>
    <input type="time" name="start_time" id="start_time" class="input" required
           value="<?= htmlspecialchars($old['start_time'] ?? '') ?>">
  </div>

  <div class="row">
    <label class="label" for="end_time">Hora fin</label>
    <input type="time" name="end_time" id="end_time" class="input" required
           value="<?= htmlspecialchars($old['end_time'] ?? '') ?>">
    <small class="hint">Las citas se generan en bloques de 15 minutos dentro de este rango.</small>
  </div>

  <button class="btn primary" type="submit">Guardar</button>
  <a class="btn ghost" href="/doctor-schedules">Cancelar</a>
</form>
