<?php
// Vista para editar un patrón de horario
// Espera: $title, $pattern (DoctorSchedule), $doctors, $sedes, $error (opcional), $old (opcional)
$role = $_SESSION['user']['rol'] ?? '';
// Lista explícita de opciones de tiempo (cada 15 minutos, 08:00 - 19:30)
$timeOptions = [
  '08:00','08:15','08:30','08:45','09:00','09:15','09:30','09:45',
  '10:00','10:15','10:30','10:45','11:00','11:15','11:30','11:45',
  '12:00','12:15','12:30','12:45','13:00','13:15','13:30','13:45',
  '14:00','14:15','14:30','14:45','15:00','15:15','15:30','15:45',
  '16:00','16:15','16:30','16:45','17:00','17:15','17:30','17:45',
  '18:00','18:15','18:30','18:45','19:00','19:15','19:30'
];
?>
<?php if ($role !== 'superadmin'): ?>
  <div class="alert">No tienes acceso a esta pantalla.</div>
  <?php return; ?>
<?php endif; ?>

<h1><?= htmlspecialchars($title ?? 'Editar patrón de horario') ?></h1>

<?php if (!empty($error)): ?>
  <div class="alert error mt-2"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" class="form mt-3" action="/doctor-schedules/<?= (int)($pattern->id ?? 0) ?>/update" id="editPatternForm">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">

  <div class="row">
    <label class="label" for="doctor_id">Doctor <span aria-hidden="true">*</span></label>
    <select name="doctor_id" id="doctor_id" class="input" required>
      <option value="">— Selecciona —</option>
      <?php foreach (($doctors ?? []) as $d): ?>
        <option value="<?= (int)$d['id'] ?>" <?= ((int)($pattern->doctor_id ?? 0) === (int)$d['id'])? 'selected':'' ?>>
          <?= htmlspecialchars(($d['nombre'] ?? '').' '.($d['apellido'] ?? '')) ?> — <?= htmlspecialchars($d['email'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row compact">
    <label class="label">Día de la semana <span aria-hidden="true">*</span></label>
    <select name="dia_semana" class="input" required>
      <?php
        $opts = ['lunes'=>'Lunes','martes'=>'Martes','miércoles'=>'Miércoles','jueves'=>'Jueves','viernes'=>'Viernes','sábado'=>'Sábado','domingo'=>'Domingo'];
        $selDay = strtolower((string)($pattern->dia_semana ?? ''));
        foreach ($opts as $k => $label):
      ?>
        <option value="<?= htmlspecialchars($k) ?>" <?= $k === $selDay ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row">
    <label class="label">Hora inicio</label>
    <select name="hora_inicio" class="input" required>
      <option value="">—</option>
      <?php $curStart = $pattern->hora_inicio ? date('H:i', strtotime($pattern->hora_inicio)) : ''; foreach ($timeOptions as $t): ?>
        <option value="<?= htmlspecialchars($t) ?>" <?= ($curStart === $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row">
    <label class="label">Hora fin</label>
    <select name="hora_fin" class="input" required>
      <option value="">—</option>
      <?php $curEnd = $pattern->hora_fin ? date('H:i', strtotime($pattern->hora_fin)) : ''; foreach ($timeOptions as $t): ?>
        <option value="<?= htmlspecialchars($t) ?>" <?= ($curEnd === $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row">
    <label class="label">Sede</label>
    <select name="sede_id" class="input">
      <option value="">— Cualquier sede —</option>
      <?php foreach (($sedes ?? []) as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= ((int)($pattern->sede_id ?? 0) === (int)$s['id'])? 'selected':'' ?>><?= htmlspecialchars($s['nombre_sede'] ?? $s['nombre'] ?? '') ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row">
    <label class="label">Observaciones</label>
    <input type="text" name="observaciones" value="<?= htmlspecialchars($pattern->observaciones ?? '') ?>" class="input">
  </div>

  <div class="row">
    <label class="label">Activo</label>
    <input type="checkbox" name="activo" value="1" <?= ($pattern->activo ? 'checked' : '') ?>>
  </div>

  <div class="row">
    <button class="btn primary" type="submit">Guardar cambios</button>
    <a class="btn ghost" href="/doctor-schedules">Cancelar</a>
    <button type="button" id="applyToCalendar" class="btn">Aplicar al calendario (30 días)</button>
  </div>
</form>

<script>
document.getElementById('applyToCalendar')?.addEventListener('click', function(){
  if (!confirm('Se crearán entradas en el calendario para los próximos 30 días (se respetarán feriados). ¿Continuar?')) return;
  var id = <?= (int)($pattern->id ?? 0) ?>;
  fetch('/doctor-schedules/' + id + '/apply', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '<?= \App\Core\Csrf::token() ?>'
    },
    body: JSON.stringify({})
  }).then(function(res){
    return res.json();
  }).then(function(json){
    alert(json.message || (json.success? 'Operación completada':'Error'));
    if (json.success) location.href = '/doctor-schedules';
  }).catch(function(err){
    alert('Error: ' + (err.message || err));
  });
});
</script>
