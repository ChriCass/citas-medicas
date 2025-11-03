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
    <small id="date-hint" class="hint" style="display:none;color:#666">Selecciona una fecha disponible</small>
  </div>

  <div class="row">
    <label class="label" for="time">Hora</label>
    <select class="input" name="time" id="time" required></select>
    <input type="hidden" name="calendario_id" id="calendario_id" value="">
  </div>

  <div class="row">
    <label class="label" for="notes">Notas</label>
    <input class="input" type="text" name="notes" id="notes" maxlength="200" placeholder="Opcional">
  </div>

  <button class="btn primary" type="submit">Confirmar</button>
</form>

<!-- flatpickr (CDN) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
async function loadSedes(){
  const d = document.getElementById('doctor_id').value;
  const sel = document.getElementById('sede_id');
  sel.innerHTML = '<option value="">Cargando...</option>';

  if (!d) {
    sel.innerHTML = '<option value="">— Selecciona una sede —</option>';
    return;
  }

  try{
    const res = await fetch(`/api/v1/sedes?doctor_id=${encodeURIComponent(d)}`);
    const data = await res.json();

    sel.innerHTML = '';

    if (data.data && data.data.length > 0) {
      const defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = '— Selecciona una sede —';
      sel.appendChild(defaultOption);

      data.data.forEach(s => {
        const o = document.createElement('option');
        o.value = s.id;
        // Algunas respuestas usan 'nombre' o 'nombre_sede'
        o.textContent = s.nombre ?? s.nombre_sede ?? '';
        sel.appendChild(o);
      });
    } else {
      const o = document.createElement('option');
      o.value = '';
      o.textContent = 'Sin sedes disponibles para este doctor';
      sel.appendChild(o);
    }
  } catch(e) {
    console.error('Error cargando sedes:', e);
    sel.innerHTML = '<option value="">Error al cargar sedes</option>';
  }
}

async function loadDates(){
  const d = document.getElementById('doctor_id').value;
  const s = document.getElementById('sede_id').value;
  const dateInput = document.getElementById('date');
  const dateHint = document.getElementById('date-hint');
  dateHint.style.display = 'none';
  // marcar input temporalmente como cargando
  dateInput.disabled = true;

  if (!d) {
    sel.innerHTML = '<option value="">Selecciona doctor y sede</option>';
    return;
  }

  try{
    const res = await fetch(`/api/v1/calendario/fechas?doctor_id=${encodeURIComponent(d)}&sede_id=${encodeURIComponent(s||0)}`);
    const data = await res.json();
    // normalizar lista de fechas disponibles
    const list = (data.data && data.data.length>0) ? data.data : [];
    allowedDates = list.slice(); // actualizar variable global

    if (allowedDates.length > 0) {
      // establecer min y max en el input date
      const first = allowedDates[0];
      const last = allowedDates[allowedDates.length - 1];
      dateInput.min = first;
      dateInput.max = last;
      dateInput.disabled = false;

      // si el valor actual no está en la lista, usar el primero disponible
      if (!allowedDates.includes(dateInput.value)) {
        dateInput.value = allowedDates[0];
      }
      dateInput.setCustomValidity('');
      dateHint.style.display = 'none';
    } else {
      // no hay fechas
      dateInput.value = '';
      dateInput.disabled = true;
      dateInput.setCustomValidity('No hay fechas disponibles para este doctor/sede');
      dateHint.textContent = 'No hay fechas en el calendario para este doctor/sede';
      dateHint.style.display = 'block';
    }
    // inicializar flatpickr para reflejar allowedDates (y habilitar bloqueo visual)
    try { initFlatpickr(); } catch(e) { console.error('initFlatpickr error', e); }
  } catch(e) {
    console.error('Error cargando fechas:', e);
    dateInput.disabled = true;
    dateInput.setCustomValidity('Error al cargar fechas');
    dateHint.textContent = 'Error al cargar fechas';
    dateHint.style.display = 'block';
    try { initFlatpickr(); } catch(e) { /* ignore */ }
  }
}

// allowedDates mantiene las fechas válidas (YYYY-MM-DD)
let allowedDates = [];

// instancia de flatpickr
let fp = null;

function initFlatpickr(){
  const dateInput = document.getElementById('date');
  // destruir si existe
  if (fp) {
    try { fp.destroy(); } catch(e){/* ignore */}
    fp = null;
  }

  // flatpickr acepta array de strings 'YYYY-MM-DD' en option `enable`
  fp = flatpickr(dateInput, {
    dateFormat: 'Y-m-d',
    allowInput: true,
    // si allowedDates está vacío, pasar función que deshabilita todas las fechas
    enable: (allowedDates && allowedDates.length>0) ? allowedDates : [function(){ return false; }],
    onChange: function(selectedDates, dateStr){
      // disparar el evento change para reusar la lógica existente
      dateInput.value = dateStr;
      dateInput.dispatchEvent(new Event('change'));
    }
  });

  // Si no hay fechas, deshabilitar input
  if (!allowedDates || allowedDates.length === 0){
    dateInput.setAttribute('disabled', 'disabled');
  } else {
    dateInput.removeAttribute('disabled');
  }
}

// validar selección del date input
document.getElementById('date').addEventListener('change', function(e){
  const v = e.target.value;
  const hint = document.getElementById('date-hint');
  if (allowedDates.length > 0 && !allowedDates.includes(v)) {
    e.target.setCustomValidity('Fecha no disponible. Selecciona una fecha válida.');
    hint.textContent = 'Fecha no disponible. Selecciona una fecha válida.';
    hint.style.display = 'block';
    // opcionalmente limpiar la selección
    // e.target.value = '';
    e.target.reportValidity();
  } else {
    e.target.setCustomValidity('');
    hint.style.display = 'none';
  }
});

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
  // Llamar al endpoint que lee directamente tabla slots_calendario
  const res = await fetch(`/api/v1/slots_db?date=${encodeURIComponent(date)}&doctor_id=${encodeURIComponent(d)}&location_id=${encodeURIComponent(l||0)}`);
    const data = await res.json();

    sel.innerHTML = '';

    if (data.slots && data.slots.length > 0) {
      const defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = '— Selecciona una hora —';
      sel.appendChild(defaultOption);

      data.slots.forEach(slot => {
        const o = document.createElement('option');
        // slot: { calendario_id, hora_inicio, hora_fin }
        // value será solo la hora; calendario_id queda en dataset
        const calId = slot.calendario_id ?? '';
        const hi = slot.hora_inicio || '';
        o.value = hi;
        o.dataset.calendarioId = calId;
        o.dataset.horaFin = slot.hora_fin ?? '';
        o.textContent = hi + ' (15 min)';
        sel.appendChild(o);
      });

      // si el valor actual no está en las opciones, seleccionar la primera real
      const firstReal = sel.querySelector('option[data-calendario-id]');
      if (firstReal) {
        if (!sel.value || !sel.querySelector(`option[value="${sel.value}"]`)) {
          sel.value = firstReal.value;
        }
        // actualizar hidden calendario_id desde dataset
        const hidden = document.getElementById('calendario_id');
        hidden.value = firstReal.dataset.calendarioId || '';
      }
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

// cuando cambia la hora seleccionada, actualizar calendario_id hidden
document.getElementById('time').addEventListener('change', function(e){
  const opt = e.target.selectedOptions && e.target.selectedOptions[0];
  const hidden = document.getElementById('calendario_id');
  if (!opt) { hidden.value = ''; return; }
  // dataset.calendarioId contiene el id; actualizar hidden
  hidden.value = opt.dataset && opt.dataset.calendarioId ? opt.dataset.calendarioId : '';
});

// Listeners: cuando cambia el doctor, cargamos sedes y luego slots; sede/date solo recargan slots
document.getElementById('doctor_id').addEventListener('change', async function(){
  await loadSedes();
  await loadDates();
  loadSlots();
});
document.getElementById('sede_id').addEventListener('change', async function(){
  await loadDates();
  loadSlots();
});
document.getElementById('date').addEventListener('change', loadSlots);

window.addEventListener('DOMContentLoaded', async function(){
  await loadSedes();
  await loadDates();
  loadSlots();
});
</script>
<?php endif; ?>
