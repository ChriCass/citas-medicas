<?php $role = $_SESSION['user']['rol'] ?? ''; ?>
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
    <label class="label" for="paciente_id">Paciente</label>
    <select class="input" name="paciente_id" id="paciente_id">
      <option value="">— Para mí (superadmin) —</option>
      <?php foreach (($pacientes ?? []) as $p): ?>
        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars(($p['nombre'] ?? '').' '.($p['apellido'] ?? '')) ?> — <?= htmlspecialchars($p['email'] ?? '') ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row">
    <label class="label" for="doctor_id">Doctor</label>
    <select class="input" name="doctor_id" id="doctor_id" required>
      <option value="">— Selecciona un doctor —</option>
      <?php foreach (($doctores ?? []) as $d): ?>
        <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars(($d['nombre'] ?? '').' '.($d['apellido'] ?? '')) ?> — <?= htmlspecialchars($d['email'] ?? '') ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row">
    <label class="label" for="sede_id">Sede</label>
    <select class="input" name="sede_id" id="sede_id">
      <option value="">— Selecciona una sede —</option>
      <?php foreach (($sedes ?? []) as $s): ?>
        <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['nombre_sede'] ?? '') ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <small class="hint">Todas las citas son de 15 minutos.</small>

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
  const l = document.getElementById('sede_id').value;
  const date = document.getElementById('date').value;
  const sel = document.getElementById('time');
  sel.innerHTML = '<option value="">Cargando...</option>';
  
  if (!d || !date) {
    sel.innerHTML = '<option value="">Selecciona doctor y fecha</option>';
    return;
  }
  
  try{
    const res = await fetch(`/api/v1/slots?date=${encodeURIComponent(date)}&doctor_id=${encodeURIComponent(d)}&location_id=${encodeURIComponent(l||0)}`);
    const data = await res.json();
    
    sel.innerHTML = '';
    
    if (data.slots && data.slots.length > 0) {
      // Agregar opción por defecto
      const defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = '— Selecciona una hora —';
      sel.appendChild(defaultOption);
      
      // Agregar slots disponibles
      data.slots.forEach(h => {
        const o = document.createElement('option'); 
        o.value = h; 
        o.textContent = h + ' (15 min)'; 
        sel.appendChild(o);
      });
    } else {
      const o = document.createElement('option'); 
      o.value = ''; 
      o.textContent = 'Sin horarios disponibles para esta fecha'; 
      sel.appendChild(o);
    }
  } catch(e) {
    console.error('Error cargando slots:', e);
    sel.innerHTML = '<option value="">Error al cargar horarios</option>';
  }
}
['doctor_id','sede_id','date'].forEach(id => document.getElementById(id).addEventListener('change', loadSlots));
window.addEventListener('DOMContentLoaded', loadSlots);
</script>
<?php endif; ?>
