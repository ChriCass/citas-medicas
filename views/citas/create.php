<?php $role = $_SESSION['user']['role'] ?? ''; ?>
<?php if ($role !== 'superadmin'): ?>
  <div class="alert mt-2">No tienes acceso a esta pantalla.</div>
<?php else: ?>
<h1>Reservar cita</h1>

<?php if (!empty($error)): ?>
  <div class="alert error mt-2"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form class="form mt-3" method="POST" action="/citas">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">

  <div class="row">
    <label class="label" for="patient_id">Paciente</label>
    <select class="input" name="patient_id" id="patient_id">
      <option value="">— Para mí (superadmin) —</option>
      <?php foreach (($patients ?? []) as $p): ?>
        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?> — <?= htmlspecialchars($p['email']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row">
    <label class="label" for="doctor_id">Doctor</label>
    <select class="input" name="doctor_id" id="doctor_id" required>
      <option value="">— Selecciona un doctor —</option>
      <?php foreach (($doctors ?? []) as $d): ?>
        <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['name']) ?> — <?= htmlspecialchars($d['email']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row">
    <label class="label" for="location_id">Sede</label>
    <select class="input" name="location_id" id="location_id" required>
      <option value="">— Selecciona una sede —</option>
      <?php foreach (($ubicaciones ?? []) as $l): ?>
        <option value="<?= (int)$l['id'] ?>"><?= htmlspecialchars($l['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row">
    <label class="label" for="service_id">Servicio</label>
    <select class="input" name="service_id" id="service_id" required>
      <?php foreach (($servicios ?? []) as $s): ?>
        <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (15 min)</option>
      <?php endforeach; ?>
    </select>
    <small class="hint">Todas las citas son de 15 minutos.</small>
  </div>

  <div class="row">
    <label class="label" for="date">Fecha</label>
    <input class="input" type="date" name="date" id="date"
           value="<?= htmlspecialchars($today ?? date('Y-m-d')) ?>"
           min="<?= htmlspecialchars($today ?? date('Y-m-d')) ?>" required>
  </div>

  <div class="row">
    <label class="label" for="time">Hora</label>
    <select class="input" name="time" id="time" required></select>
  </div>

  <div class="row">
    <label class="label" for="notes">Notas</label>
    <input class="input" type="text" name="notes" id="notes" maxlength="200" placeholder="Opcional">
  </div>

  <button class="btn primary" type="submit">Confirmar</button>
</form>

<script>
async function loadSlots(){
  const d = document.getElementById('doctor_id').value;
  const l = document.getElementById('location_id').value;
  const date = document.getElementById('date').value;
  const sel = document.getElementById('time');
  sel.innerHTML = '';
  if (!d || !l || !date) return;
  try{
    const res = await fetch(`/api/v1/slots?date=${encodeURIComponent(date)}&doctor_id=${encodeURIComponent(d)}&location_id=${encodeURIComponent(l)}`);
    const data = await res.json();
    (data.slots || []).forEach(h => {
      const o = document.createElement('option'); o.value=h; o.textContent=h; sel.appendChild(o);
    });
    if(!sel.options.length){
      const o=document.createElement('option'); o.value=''; o.textContent='Sin horarios'; sel.appendChild(o);
    }
  }catch(e){
    const o=document.createElement('option'); o.value=''; o.textContent='Error'; sel.appendChild(o);
  }
}
['doctor_id','location_id','date'].forEach(id => document.getElementById(id).addEventListener('change', loadSlots));
window.addEventListener('DOMContentLoaded', loadSlots);
</script>
<?php endif; ?>
