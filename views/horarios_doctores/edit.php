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
  
  <div class="row" style="margin-top:12px;">
    <label class="label">Mes</label>
    <?php
      $months = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
      $oldMes = isset($old['mes']) ? (int)$old['mes'] : null;
      $patternMes = isset($pattern->mes) ? strtolower(trim((string)$pattern->mes)) : null;
      $patternMesNum = null;
      foreach ($months as $k=>$v) if ($v === $patternMes) $patternMesNum = $k;
      $selMes = $oldMes ?: ($patternMesNum ?: (int)date('n'));
    ?>
    <select name="mes" id="mes" class="input">
      <option value="">--</option>
      <?php for ($m = 1; $m <= 12; $m++): ?>
        <option value="<?= $m ?>" <?= ($m === (int)$selMes) ? 'selected' : '' ?>><?= ucfirst($months[$m]) ?></option>
      <?php endfor; ?>
    </select>

    <label class="label" style="margin-left:12px;">Año</label>
    <?php
      $currentYear = (int)date('Y');
      $oldAnio = isset($old['anio']) ? (int)$old['anio'] : null;
      $patternAnio = isset($pattern->anio) ? (int)$pattern->anio : $currentYear;
      $selAnio = $oldAnio ?: $patternAnio;
    ?>
    <select name="anio" id="anio" class="input" style="width:120px;">
      <?php for ($y = $currentYear - 1; $y <= $currentYear + 1; $y++): ?>
        <option value="<?= $y ?>" <?= ($y === (int)$selAnio) ? 'selected' : '' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
  </div>
  
  <!-- Tabla con los horarios del doctor/sede seleccionados -->
  <h2 style="margin-top:18px;">Horarios del doctor / sede</h2>
  <div class="card">
    <div class="content">
      <table class="table" style="width:100%;border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;padding:8px;border-bottom:1px solid #eaeaea;">Día de la semana</th>
            <th style="text-align:left;padding:8px;border-bottom:1px solid #eaeaea;">Sede</th>
            <th style="text-align:left;padding:8px;border-bottom:1px solid #eaeaea;">Hora inicio (24h)</th>
            <th style="text-align:left;padding:8px;border-bottom:1px solid #eaeaea;">Hora fin (24h)</th>
            <th style="text-align:left;padding:8px;border-bottom:1px solid #eaeaea;">Observaciones</th>
            <th style="text-align:center;padding:8px;border-bottom:1px solid #eaeaea;">Activo</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($horarios)): ?>
            <?php foreach ($horarios as $h): ?>
              <tr>
                <td style="padding:8px;vertical-align:middle;">
                  <select name="horarios[<?= (int)$h->id ?>][dia_semana]" class="input">
                    <?php
                      $opts = ['lunes'=>'Lunes','martes'=>'Martes','miércoles'=>'Miércoles','jueves'=>'Jueves','viernes'=>'Viernes','sábado'=>'Sábado','domingo'=>'Domingo'];
                      $sel = strtolower((string)($h->dia_semana ?? ''));
                      foreach ($opts as $k => $label):
                    ?>
                      <option value="<?= htmlspecialchars($k) ?>" <?= $k === $sel ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td style="padding:8px;vertical-align:middle;">
                  <select name="horarios[<?= (int)$h->id ?>][sede_id]" class="input">
                    <option value="">— Cualquier sede —</option>
                    <?php foreach (($sedes ?? []) as $s): ?>
                      <option value="<?= (int)$s['id'] ?>" <?= ((int)($h->sede_id ?? 0) === (int)$s['id'])? 'selected':'' ?>><?= htmlspecialchars($s['nombre_sede'] ?? $s['nombre'] ?? '') ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td style="padding:8px;vertical-align:middle;">
                  <select name="horarios[<?= (int)$h->id ?>][hora_inicio]" class="input">
                    <option value="">—</option>
                    <?php $curStart = $h->hora_inicio ? date('H:i', strtotime($h->hora_inicio)) : ''; foreach ($timeOptions as $t): ?>
                      <option value="<?= htmlspecialchars($t) ?>" <?= ($curStart === $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td style="padding:8px;vertical-align:middle;">
                  <select name="horarios[<?= (int)$h->id ?>][hora_fin]" class="input">
                    <option value="">—</option>
                    <?php $curEnd = $h->hora_fin ? date('H:i', strtotime($h->hora_fin)) : ''; foreach ($timeOptions as $t): ?>
                      <option value="<?= htmlspecialchars($t) ?>" <?= ($curEnd === $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td style="padding:8px;vertical-align:middle;">
                  <input type="text" name="horarios[<?= (int)$h->id ?>][observaciones]" value="<?= htmlspecialchars($h->observaciones ?? '') ?>" class="input">
                </td>
                <td style="padding:8px;vertical-align:middle;text-align:center;">
                  <input type="checkbox" name="horarios[<?= (int)$h->id ?>][activo]" value="1" <?= ($h->activo ? 'checked' : '') ?>>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" style="padding:8px;">No hay patrones asociados para este doctor/sede.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="row" style="margin-top:12px;">
    <button class="btn primary" type="submit">Guardar cambios</button>
    <a class="btn ghost" href="/doctor-schedules">Cancelar</a>
    <button type="button" id="applyToCalendar" class="btn">Aplicar al calendario (30 días)</button>
  </div>
</form>

<script>
document.getElementById('applyToCalendar')?.addEventListener('click', function(){
  if (!confirm('Se crearán entradas en el calendario para los próximos 30 días (se respetarán feriados). ¿Continuar?')) return;
  var id = <?= (int)($pattern->id ?? 0) ?>;
  var mesEl = document.getElementById('mes');
  var anioEl = document.getElementById('anio');
  var payload = {};
  if (mesEl && mesEl.value) payload.mes = parseInt(mesEl.value, 10);
  if (anioEl && anioEl.value) payload.anio = parseInt(anioEl.value, 10);
  fetch('/doctor-schedules/' + id + '/apply', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '<?= \App\Core\Csrf::token() ?>'
    },
    body: JSON.stringify(payload)
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
