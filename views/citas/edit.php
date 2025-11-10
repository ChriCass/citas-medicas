<?php $role = $_SESSION['user']['rol'] ?? ''; ?>
<?php
// Esta vista se usa para editar una cita. El controlador ya valida permisos
// (por ejemplo, que el paciente pueda modificar su propia cita). Aquí
// asumimos que $cita está definido y contiene los campos de la cita.
if (empty($cita) || !is_array($cita)) : ?>
  <div class="alert mt-2">Cita no encontrada o no válida.</div>
<?php else: ?>
<h1>Modificar cita</h1>

<?php
// Determinar la especialidad asociada al doctor de la cita (si está disponible)
$selectedEspecialidad = null;
foreach (($doctores ?? []) as $dd) {
    if (isset($cita['doctor_id']) && (int)$dd['id'] === (int)$cita['doctor_id']) {
        $selectedEspecialidad = $dd['especialidad_id'] ?? null;
        break;
    }
}
?>

<?php if (!empty($error)): ?>
  <div class="alert error mt-2"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form class="form mt-3" method="POST" action="/citas/<?= (int)($cita['id'] ?? 0) ?>/update" onsubmit="return validateAppointmentForm(event, this);">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">

  

  <?php
  // El campo paciente no debe ser editable en la vista de edición; lo mantenemos
  // como hidden para enviar el paciente original al servidor.
  ?>
  <input type="hidden" id="paciente_id" name="paciente_id" value="<?= (int)($cita['paciente_id'] ?? 0) ?>">
  <input type="hidden" id="paciente_label" value="<?= htmlspecialchars(($cita['paciente_nombre'] ?? '') . ' ' . ($cita['paciente_apellido'] ?? '')) ?>">

  <div class="row">
    <label class="label" for="especialidad_id">Especialidad</label>
    <select class="input" name="especialidad_id" id="especialidad_id" required>
      <option value="">— Selecciona una especialidad —</option>
      <?php foreach (($especialidades ?? []) as $e): ?>
        <option value="<?= (int)$e['id'] ?>" <?= ($selectedEspecialidad && (int)$selectedEspecialidad === (int)$e['id']) ? 'selected' : '' ?>><?= htmlspecialchars($e['nombre'] ?? $e['nombre_especialidad'] ?? '') ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row">
    <label class="label" for="doctor_id">Doctor</label>
    <select class="input" name="doctor_id" id="doctor_id" required>
      <option value="">— Selecciona un doctor —</option>
      <?php foreach (($doctores ?? []) as $d): ?>
        <option value="<?= (int)$d['id'] ?>" data-especialidad-id="<?= (int)($d['especialidad_id'] ?? 0) ?>" <?= (isset($cita['doctor_id']) && (int)$cita['doctor_id'] === (int)$d['id']) ? 'selected' : '' ?>><?= htmlspecialchars(($d['user_nombre'] ?? '').' '.($d['user_apellido'] ?? '')) ?> — <?= htmlspecialchars($d['especialidad_nombre'] ?? '') ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row">
    <label class="label" for="sede_id">Sede</label>
    <select class="input" name="sede_id" id="sede_id" required>
      <option value="">— Selecciona una sede —</option>
      <?php foreach (($sedes ?? []) as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= (isset($cita['sede_id']) && (int)$cita['sede_id'] === (int)$s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nombre_sede'] ?? '') ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <small class="hint">Todas las citas son de 15 minutos.</small>

  <div class="row">
    <label class="label" for="date">Fecha</label>
    <input class="input" type="date" name="fecha" id="date"
      value="<?= htmlspecialchars(isset($cita['fecha']) ? date('Y-m-d', strtotime($cita['fecha'])) : ($today ?? date('Y-m-d'))) ?>"
      min="<?= htmlspecialchars($today ?? date('Y-m-d')) ?>" required>
    <small id="date-hint" class="hint" style="display:none;color:#666">Selecciona una fecha disponible</small>
  </div>

  <div class="row">
    <label class="label" for="time">Hora</label>
    <select class="input" name="time" id="time" required></select>
    <input type="hidden" name="hora_inicio" id="hora_inicio" value="<?= htmlspecialchars(isset($cita['hora_inicio']) ? substr($cita['hora_inicio'],0,5) : '') ?>">
    <input type="hidden" name="hora_fin" id="hora_fin" value="<?= htmlspecialchars(isset($cita['hora_fin']) ? substr($cita['hora_fin'],0,5) : '') ?>">
    <input type="hidden" name="calendario_id" id="calendario_id" value="<?= htmlspecialchars($cita['calendario_id'] ?? '') ?>">
    <input type="hidden" name="slot_id" id="slot_id" value="<?= htmlspecialchars($cita['slot_id'] ?? '') ?>">
  </div>

  <div class="row">
    <label class="label" for="notes">Notas</label>
    <input class="input" type="text" name="razon" id="notes" maxlength="200" placeholder="Opcional" value="<?= htmlspecialchars($cita['razon'] ?? '') ?>">
  </div>

  <button class="btn primary" type="submit">Confirmar</button>
</form>

<!-- flatpickr (CDN) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
// Helper para fetch JSON con manejo de errores
async function fetchJson(url) {
  const res = await fetch(url, { credentials: 'same-origin' });
  if (!res.ok) {
    const text = await res.text().catch(() => '');
    throw new Error(`HTTP ${res.status} - ${text}`);
  }
  return res.json();
}

// Datos iniciales de la cita para la vista de edición (hora, calendario_id, hora_fin)
const initialAppointment = <?= json_encode([
  'time' => isset($cita['hora_inicio']) ? substr($cita['hora_inicio'],0,5) : '',
  'calendario_id' => isset($cita['calendario_id']) ? $cita['calendario_id'] : '',
  'slot_id' => isset($cita['slot_id']) ? $cita['slot_id'] : '',
  'hora_fin' => isset($cita['hora_fin']) ? substr($cita['hora_fin'],0,5) : '',
  'doctor_id' => isset($cita['doctor_id']) ? (int)$cita['doctor_id'] : 0,
  'sede_id' => isset($cita['sede_id']) ? (int)$cita['sede_id'] : 0,
  'date' => isset($cita['fecha']) ? date('Y-m-d', strtotime($cita['fecha'])) : ''
]) ?>;

/**
 * Carga los slots desde la tabla slots_calendario (endpoint /slots_db)
 * y rellena el select de horas. Sólo se muestran slots no reservados
 * (reservado_por_cita_id IS NULL) tal y como realiza el endpoint.
 */
async function loadSlots(){
  const doctorId = document.getElementById('doctor_id').value;
  const locationId = document.getElementById('sede_id').value || 0;
  const date = document.getElementById('date').value;
  const sel = document.getElementById('time');

  sel.innerHTML = '<option value="">Cargando...</option>';

  if (!doctorId || !date) {
    sel.innerHTML = '<option value="">Selecciona doctor y fecha</option>';
    document.getElementById('calendario_id').value = '';
    return;
  }

  try {
    // Usar el endpoint /slots_db que devuelve únicamente los slots almacenados
    // en la tabla slots_calendario (y el backend ya filtra reservado_por_cita_id)
    const url = `/api/v1/slots_db?date=${encodeURIComponent(date)}&doctor_id=${encodeURIComponent(doctorId)}&location_id=${encodeURIComponent(locationId)}`;
    const data = await fetchJson(url);

    sel.innerHTML = '';

    const slots = data.slots || [];
    if (slots.length === 0) {
      const o = document.createElement('option');
      o.value = '';
      o.textContent = 'Sin horarios disponibles para esta fecha';
      sel.appendChild(o);
      document.getElementById('calendario_id').value = '';
      return;
    }

    // Opción por defecto
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = '— Selecciona una hora —';
    sel.appendChild(defaultOption);

    for (const slot of slots) {
      const o = document.createElement('option');
      const calId = slot.calendario_id ?? '';
      const slotId = slot.slot_id ?? slot.id ?? '';
      const hi = slot.hora_inicio || slot.start || '';
      const hf = slot.hora_fin || slot.end || '';
      o.value = hi;
      if (calId !== '') o.dataset.calendarioId = calId;
      if (slotId !== '') o.dataset.slotId = slotId;
      if (hf !== '') o.dataset.horaFin = hf;
      o.textContent = (hi ? hi : '—') + (hf ? ` — ${hf}` : '') + ' (15 min)';
      sel.appendChild(o);
    }

    // Intentar seleccionar la hora original de la cita (si venimos desde edición)
    try {
    if (initialAppointment && initialAppointment.time) {
        // buscar opción que coincida por hora y, si existe, por calendario_id
  const wantedTime = initialAppointment.time;
  const wantedCal = initialAppointment.calendario_id ? String(initialAppointment.calendario_id) : '';
        let found = Array.from(sel.options).find(o => o.value === wantedTime && (wantedCal === '' || o.dataset.calendarioId === wantedCal));
        if (found) {
          sel.value = found.value;
          document.getElementById('calendario_id').value = found.dataset.calendarioId || initialAppointment.calendario_id || '';
          document.getElementById('slot_id').value = found.dataset.slotId || initialAppointment.slot_id || '';
          document.getElementById('hora_inicio').value = found.value || initialAppointment.time || '';
          document.getElementById('hora_fin').value = found.dataset.horaFin || initialAppointment.hora_fin || '';
        } else {
          // si no existe (slot reservado por la propia cita o eliminado), insertar opción "actual"
          const appOpt = document.createElement('option');
          appOpt.value = initialAppointment.time;
          if (initialAppointment.calendario_id) appOpt.dataset.calendarioId = initialAppointment.calendario_id;
          if (initialAppointment.slot_id) appOpt.dataset.slotId = initialAppointment.slot_id;
          if (initialAppointment.hora_fin) appOpt.dataset.horaFin = initialAppointment.hora_fin;
          appOpt.textContent = initialAppointment.time + (initialAppointment.hora_fin ? ` — ${initialAppointment.hora_fin}` : '') + ' (actual)';
          // insertar justo después de la opción por defecto
          const first = sel.querySelector('option');
          if (first) sel.insertBefore(appOpt, first.nextSibling);
          sel.value = appOpt.value;
          document.getElementById('calendario_id').value = appOpt.dataset.calendarioId || initialAppointment.calendario_id || '';
          document.getElementById('slot_id').value = appOpt.dataset.slotId || initialAppointment.slot_id || '';
          document.getElementById('hora_inicio').value = appOpt.value || initialAppointment.time || '';
          document.getElementById('hora_fin').value = appOpt.dataset.horaFin || initialAppointment.hora_fin || '';
        }
        return; // ya quedó seleccionada
      }
    } catch(e) {
      console.error('Inicializacion hora cita error', e);
    }

    // Seleccionar la primera opción real y sincronizar hidden calendario_id
    const firstReal = sel.querySelector('option[data-calendario-id]');
    if (firstReal) {
      if (!sel.value || !sel.querySelector(`option[value="${sel.value}"]`)) {
        sel.value = firstReal.value;
      }
      document.getElementById('calendario_id').value = firstReal.dataset.calendarioId || '';
      document.getElementById('hora_inicio').value = firstReal.value || '';
      document.getElementById('hora_fin').value = firstReal.dataset.horaFin || '';
    } else {
      // Si no hay calendario_id (fallback), limpiar hidden
      document.getElementById('calendario_id').value = '';
      document.getElementById('hora_inicio').value = '';
      document.getElementById('hora_fin').value = '';
    }

  } catch (err) {
    console.error('Error cargando slots:', err);
    sel.innerHTML = '<option value="">Error al cargar horarios</option>';
    document.getElementById('calendario_id').value = '';
  }
}

// Note: listeners for doctor/sede/date are wired more explicitly below to ensure
// sedes and fechas se carguen primero (avoid race conditions).

// (Removed patient search UI and helpers in edit view)

async function loadEspecialidades() {
  const sel = document.getElementById('especialidad_id');
  sel.innerHTML = '<option value="">Cargando...</option>';

  try {
    const res = await fetch('/api/v1/especialidades');
    const data = await res.json();

    sel.innerHTML = '';

    if (data.data && data.data.length > 0) {
      const defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = '— Selecciona una especialidad —';
      sel.appendChild(defaultOption);

      data.data.forEach(e => {
        const o = document.createElement('option');
        o.value = e.id;
        o.textContent = e.nombre ?? e.nombre_especialidad ?? '';
        sel.appendChild(o);
      });
    } else {
      const o = document.createElement('option');
      o.value = '';
      o.textContent = 'Sin especialidades disponibles';
      sel.appendChild(o);
    }
  } catch(e) {
    console.error('Error cargando especialidades:', e);
    sel.innerHTML = '<option value="">Error al cargar especialidades</option>';
  }
}

function filterDoctoresByEspecialidad() {
  const especialidadId = document.getElementById('especialidad_id').value;
  const doctorSel = document.getElementById('doctor_id');
  const allOptions = doctorSel.querySelectorAll('option');

  if (!especialidadId) {
    // Mostrar todos los doctores
    allOptions.forEach(opt => {
      if (opt.value) opt.style.display = '';
    });
    return;
  }

  // Filtrar doctores por especialidad
  allOptions.forEach(opt => {
    if (!opt.value) {
      opt.style.display = '';
      return;
    }
    
    const optEspecialidadId = opt.getAttribute('data-especialidad-id');
    if (optEspecialidadId == especialidadId) {
      opt.style.display = '';
    } else {
      opt.style.display = 'none';
    }
  });

  // Resetear selección de doctor si no coincide con la especialidad
  const selectedOption = doctorSel.selectedOptions[0];
  if (selectedOption && selectedOption.value) {
    const selectedEspecialidadId = selectedOption.getAttribute('data-especialidad-id');
    if (selectedEspecialidadId != especialidadId) {
      doctorSel.value = '';
      // Limpiar campos dependientes
      document.getElementById('sede_id').innerHTML = '<option value="">— Selecciona una sede —</option>';
      document.getElementById('time').innerHTML = '<option value="">— Selecciona una hora —</option>';
    }
  }
}

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
      // Si venimos desde edición, intentar preseleccionar la sede original
      try {
        if (initialAppointment && initialAppointment.sede_id) {
          const as = String(initialAppointment.sede_id);
          const opt = sel.querySelector(`option[value="${as}"]`);
          if (opt) sel.value = as;
        }
      } catch(e) { /* ignore */ }
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
    // No hay doctor seleccionado -> deshabilitar input de fecha
    dateInput.value = '';
    dateInput.disabled = true;
    dateInput.setCustomValidity('Selecciona un doctor');
    dateHint.textContent = 'Selecciona un doctor para ver fechas disponibles';
    dateHint.style.display = 'block';
    try { initFlatpickr(); } catch(e) { /* ignore */ }
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

      // Preferir la fecha original de la cita si está disponible
      if (initialAppointment && initialAppointment.date && allowedDates.includes(initialAppointment.date)) {
        dateInput.value = initialAppointment.date;
      } else {
        // si el valor actual no está en la lista, usar el primero disponible
        if (!allowedDates.includes(dateInput.value)) {
          dateInput.value = allowedDates[0];
        }
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



// Event listeners
// patient search removed: no bindings
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

// cuando cambia la hora seleccionada, actualizar calendario_id hidden
document.getElementById('time').addEventListener('change', function(e){
  const opt = e.target.selectedOptions && e.target.selectedOptions[0];
  const hidden = document.getElementById('calendario_id');
  const hiddenSlot = document.getElementById('slot_id');
  if (!opt) { hidden.value = ''; return; }
  // dataset.calendarioId contiene el id; actualizar hidden
  hidden.value = opt.dataset && opt.dataset.calendarioId ? opt.dataset.calendarioId : '';
  if (hiddenSlot) hiddenSlot.value = opt.dataset && opt.dataset.slotId ? opt.dataset.slotId : '';
  // sincronizar hora_inicio / hora_fin hidden
  const hi = opt.value || '';
  const hf = (opt.dataset && opt.dataset.horaFin) ? opt.dataset.horaFin : '';
  const hiEl = document.getElementById('hora_inicio');
  const hfEl = document.getElementById('hora_fin');
  if (hiEl) hiEl.value = hi;
  if (hfEl) hfEl.value = hf;
});

// Listeners: cuando cambia el doctor, cargamos sedes y luego slots; sede/date solo recargan slots
document.getElementById('especialidad_id').addEventListener('change', function(){
  filterDoctoresByEspecialidad();
});

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



// Búsqueda en tiempo real (opcional)
// realtime patient search removed in edit view

window.addEventListener('DOMContentLoaded', async function(){
  // Prefill básicos desde servidor
  try {
    if (initialAppointment && initialAppointment.time) {
      // valor inicial de hora_inicio/hora_fin ya puesto en inputs hidden por PHP
      // asegurar que el select muestre algo hasta que loadSlots lo cargue
      const timeSel = document.getElementById('time');
      if (timeSel && !timeSel.querySelector(`option[value="${initialAppointment.time}"]`)) {
        const tmp = document.createElement('option');
        tmp.value = initialAppointment.time;
        if (initialAppointment.hora_fin) tmp.dataset.horaFin = initialAppointment.hora_fin;
        if (initialAppointment.calendario_id) tmp.dataset.calendarioId = initialAppointment.calendario_id;
        if (initialAppointment.slot_id) tmp.dataset.slotId = initialAppointment.slot_id;
        tmp.textContent = initialAppointment.time + (initialAppointment.hora_fin ? ` — ${initialAppointment.hora_fin}` : '') + ' (actual)';
        timeSel.appendChild(tmp);
        timeSel.value = tmp.value;
      }
    }
  } catch(e) { console.error('init prefills', e); }

  // Cargar sedes/fechas/slots (no recargamos especialidades porque están renderizadas desde servidor)
  await loadSedes();
  await loadDates();
  loadSlots();
});

// Función para validar formulario de cita con SweetAlert2
function validateAppointmentForm(event, form) {
  event.preventDefault();
  
  // paciente_id puede estar en un input hidden en la vista edit
  const pacienteEl = document.getElementById('paciente_id');
  const pacienteId = pacienteEl ? pacienteEl.value : '';
  const doctorId = document.getElementById('doctor_id').value;
  const fecha = document.getElementById('date').value;
  const hora = document.getElementById('time').value;
  
  if (!pacienteId) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Por favor selecciona un paciente'
    });
    return false;
  }
  
  if (!doctorId) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Por favor selecciona un doctor'
    });
    return false;
  }
  
  if (!fecha) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Por favor selecciona una fecha'
    });
    return false;
  }
  
  if (!hora) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Por favor selecciona una hora'
    });
    return false;
  }
  
  // Validar que la fecha sea futura
  const fechaSeleccionada = new Date(fecha);
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  
  if (fechaSeleccionada <= hoy) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'La fecha debe ser futura'
    });
    return false;
  }
  
  // Mostrar confirmación: si existe un select usamos su opción, si no usamos el hidden paciente_label
  let pacienteNombre = '';
  const pacienteOpt = document.querySelector('#paciente_id option:checked');
  if (pacienteOpt) {
    pacienteNombre = pacienteOpt.textContent;
  } else {
    const pl = document.getElementById('paciente_label');
    pacienteNombre = pl ? pl.value : '';
  }
  const doctorNombre = document.querySelector('#doctor_id option:checked').textContent;
  const sedeNombre = document.querySelector('#sede_id option:checked').textContent;
  
  Swal.fire({
    title: '¿Confirmar cita?',
    html: `
      <div style="text-align: left;">
        <p><strong>Paciente:</strong> ${pacienteNombre}</p>
        <p><strong>Doctor:</strong> ${doctorNombre}</p>
        <p><strong>Sede:</strong> ${sedeNombre}</p>
        <p><strong>Fecha:</strong> ${fecha}</p>
        <p><strong>Hora:</strong> ${hora}</p>
      </div>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#28a745',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, crear cita',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      // Mostrar loading mientras se procesa
      Swal.fire({
        title: 'Creando cita...',
        text: 'Por favor espera',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
      
      // Ensure slot_id hidden is populated from selected option as a last-resort
      try {
        const selOpt = document.querySelector('#time option:checked');
        const hiddenSlot = document.getElementById('slot_id');
        if (selOpt && hiddenSlot && (!hiddenSlot.value || hiddenSlot.value === '')) {
          if (selOpt.dataset && selOpt.dataset.slotId) hiddenSlot.value = selOpt.dataset.slotId;
        }
      } catch(e) { /* ignore */ }

      // Enviar formulario normalmente (el servidor redirigirá con mensaje de éxito)
      form.submit();
      
    } else {
      // Usuario canceló - redirigir directamente (el servidor mostrará el modal)
      // Crear un input hidden para indicar cancelación
      const cancelInput = document.createElement('input');
      cancelInput.type = 'hidden';
      cancelInput.name = 'cancel';
      cancelInput.value = '1';
      form.appendChild(cancelInput);
      
      // Enviar formulario normalmente (el servidor redirigirá con parámetro de cancelación)
      // Also ensure slot_id present for consistency
      try {
        const selOpt = document.querySelector('#time option:checked');
        const hiddenSlot = document.getElementById('slot_id');
        if (selOpt && hiddenSlot && (!hiddenSlot.value || hiddenSlot.value === '')) {
          if (selOpt.dataset && selOpt.dataset.slotId) hiddenSlot.value = selOpt.dataset.slotId;
        }
      } catch(e) { /* ignore */ }

      form.submit();
    }
  });
  
  return false;
}
</script>
<?php endif; ?>
