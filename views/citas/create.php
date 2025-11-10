<?php $role = $_SESSION['user']['rol'] ?? ''; ?>
<?php if ($role !== 'superadmin'): ?>
  <div class="alert mt-2">No tienes acceso a esta pantalla.</div>
<?php else: ?>
<h1>Reservar cita</h1>

<?php if (!empty($error)): ?>
  <div class="alert error mt-2"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form class="form mt-3" method="POST" action="/citas" onsubmit="return validateAppointmentForm(event, this);">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">

  <div class="row">
    <label class="label" for="paciente_id">Paciente Seleccionado</label>
    <select class="input" name="paciente_id" id="paciente_id" required>
      <option value="">— Selecciona un paciente —</option>
      <?php foreach (($pacientes ?? []) as $p): ?>
        <option value="<?= (int)$p['id'] ?>" data-dni="<?= htmlspecialchars($p['dni'] ?? '') ?>" data-user-id="<?= (int)$p['usuario_id'] ?>">
          <?= htmlspecialchars(($p['nombre'] ?? '').' '.($p['apellido'] ?? '')) ?> — DNI: <?= htmlspecialchars($p['dni'] ?? '') ?> — ID: <?= (int)$p['usuario_id'] ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row">
    <label class="label" for="especialidad_id">Especialidad</label>
    <select class="input" name="especialidad_id" id="especialidad_id" required>
      <option value="">— Selecciona una especialidad —</option>
    </select>
  </div>

  <div class="row">
    <label class="label" for="doctor_id">Doctor</label>
    <select class="input" name="doctor_id" id="doctor_id" required>
      <option value="">— Selecciona un doctor —</option>
      <?php foreach (($doctores ?? []) as $d): ?>
        <option value="<?= (int)$d['id'] ?>" data-especialidad-id="<?= (int)($d['especialidad_id'] ?? 0) ?>"><?= htmlspecialchars(($d['user_nombre'] ?? '').' '.($d['user_apellido'] ?? '')) ?> — <?= htmlspecialchars($d['especialidad_nombre'] ?? '') ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row">
    <label class="label" for="sede_id">Sede</label>
    <select class="input" name="sede_id" id="sede_id" required>
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
// Helper para fetch JSON con manejo de errores
async function fetchJson(url) {
  const res = await fetch(url, { credentials: 'same-origin' });
  if (!res.ok) {
    const text = await res.text().catch(() => '');
    throw new Error(`HTTP ${res.status} - ${text}`);
  }
  return res.json();
}

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
      const hi = slot.hora_inicio || slot.start || '';
      const hf = slot.hora_fin || slot.end || '';
      o.value = hi;
      if (calId !== '') o.dataset.calendarioId = calId;
      if (hf !== '') o.dataset.horaFin = hf;
      o.textContent = (hi ? hi : '—') + (hf ? ` — ${hf}` : '') + ' (15 min)';
      sel.appendChild(o);
    }

    // Seleccionar la primera opción real y sincronizar hidden calendario_id
    const firstReal = sel.querySelector('option[data-calendario-id]');
    if (firstReal) {
      if (!sel.value || !sel.querySelector(`option[value="${sel.value}"]`)) {
        sel.value = firstReal.value;
      }
      document.getElementById('calendario_id').value = firstReal.dataset.calendarioId || '';
    } else {
      // Si no hay calendario_id (fallback), limpiar hidden
      document.getElementById('calendario_id').value = '';
    }

  } catch (err) {
    console.error('Error cargando slots:', err);
    sel.innerHTML = '<option value="">Error al cargar horarios</option>';
    document.getElementById('calendario_id').value = '';
  }
}

// Note: listeners for doctor/sede/date are wired more explicitly below to ensure
// sedes and fechas se carguen primero (avoid race conditions).


function selectPaciente(paciente) {
  const select = document.getElementById('paciente_id');
  const options = select.querySelectorAll('option');
  
  // Buscar la opción que coincida con el usuario_id
  for (let option of options) {
    if (option.getAttribute('data-user-id') == paciente.usuario_id) {
      select.value = option.value;
      break;
    }
  }
  
  // Limpiar búsqueda
  document.getElementById('paciente_search').value = '';
  document.getElementById('search_results').style.display = 'none';
}

function clearSearch() {
  document.getElementById('paciente_search').value = '';
  document.getElementById('search_results').style.display = 'none';
}

async function loadEspecialidades() {
  const sel = document.getElementById('especialidad_id');
  sel.innerHTML = '<option value="">Cargando...</option>';

  try {
    const res = await fetch('/api/v1/especialidades');
    const data = await res.json();
    // Aceptar dos formatos: { data: [...] } o directamente [...]
    const list = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);

    sel.innerHTML = '';

    if (list.length > 0) {
      const defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = '— Selecciona una especialidad —';
      // dejar el placeholder seleccionado por defecto
      defaultOption.selected = true;
      sel.appendChild(defaultOption);

      list.forEach(e => {
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

/**
 * Intentar cargar doctores desde el API por especialidad. Si falla, usar el
 * filtrado de opciones existente (filterDoctoresByEspecialidad) como fallback.
 */
async function loadDoctores(especialidadId) {
  const doctorSel = document.getElementById('doctor_id');

  // Si no se indicó especialidad, mostrar todos los doctores (reset)
  if (!especialidadId) {
    // si el servidor ya renderizó doctores, simplemente mostrar todos
    const allOptions = doctorSel.querySelectorAll('option');
    allOptions.forEach(opt => opt.style.display = '');
    doctorSel.value = '';
    return;
  }

  // Intentar obtener vía API
  try {
    // Preferir endpoint nuevo en inglés que acepta el id en la ruta; mantener compatibilidad
    // con el endpoint en español si existiera.
    let res = await fetch(`/api/v1/doctors/${encodeURIComponent(especialidadId)}`);
    if (res.status === 404) {
      // intentar endpoint en español con query param por compatibilidad con versiones antiguas
      res = await fetch(`/api/v1/doctores?especialidad_id=${encodeURIComponent(especialidadId)}`);
    }
    // Si la ruta no existe (404) o el endpoint no está implementado, usar fallback silencioso
    if (res.status === 404) {
      console.info('API /api/v1/doctores no encontrada (404), usando fallback local');
      filterDoctoresByEspecialidad();
      return;
    }
    if (!res.ok) {
      // Otros errores HTTP: usar fallback pero loguear a nivel debug
      console.info('API doctores respondió con estado', res.status, ', usando fallback local');
      filterDoctoresByEspecialidad();
      return;
    }

    let data = null;
    try {
      data = await res.json();
    } catch (parseErr) {
      console.warn('Respuesta de API doctores no es JSON válido, usando fallback', parseErr);
      filterDoctoresByEspecialidad();
      return;
    }

    if (data && Array.isArray(data.data) && data.data.length > 0) {
      // Vaciar y rellenar opciones
      doctorSel.innerHTML = '';
  const defaultOption = document.createElement('option');
  defaultOption.value = '';
  defaultOption.textContent = '— Selecciona un doctor —';
  // Asegurar que el placeholder quede seleccionado inicialmente
  defaultOption.selected = true;
  doctorSel.appendChild(defaultOption);

      data.data.forEach(d => {
        const o = document.createElement('option');
        o.value = d.id;
        // mantener atributo para compatibilidad con filterDoctoresByEspecialidad
        if (d.especialidad_id) o.setAttribute('data-especialidad-id', d.especialidad_id);
        // soportar distintas formas de respuesta: user_nombre/user_apellido o nombre/apellido
        const nombre = d.user_nombre ?? d.nombre ?? '';
        const apellido = d.user_apellido ?? d.apellido ?? '';
        const esp = d.especialidad_nombre ?? d.nombre_especialidad ?? '';
        o.textContent = (nombre + ' ' + apellido).trim() + (esp ? (' — ' + esp) : '');
        doctorSel.appendChild(o);
      });

      // disparar carga dependiente
      doctorSel.dispatchEvent(new Event('change'));
      return;
    }
  } catch (e) {
    // fallback: si la request falla (network error), usamos el filtrado en-memory
    console.info('Error al llamar API doctores, usando fallback local:', e && e.message ? e.message : e);
    filterDoctoresByEspecialidad();
    return;
  }

  // Si la respuesta no contenía doctores, vaciar select
  doctorSel.innerHTML = '';
  const o = document.createElement('option');
  o.value = '';
  o.textContent = 'Sin doctores disponibles para esta especialidad';
  doctorSel.appendChild(o);
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



// Event listeners (attach only if elements exist to avoid null errors)
const searchPacienteBtn = document.getElementById('search_paciente');
if (searchPacienteBtn) searchPacienteBtn.addEventListener('click', searchPacientes);

const clearSearchBtn = document.getElementById('clear_search');
if (clearSearchBtn) clearSearchBtn.addEventListener('click', clearSearch);

const pacienteSearchInput = document.getElementById('paciente_search');
if (pacienteSearchInput) {
  pacienteSearchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      searchPacientes();
    }
  });
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

// cuando cambia la hora seleccionada, actualizar calendario_id hidden
document.getElementById('time').addEventListener('change', function(e){
  const opt = e.target.selectedOptions && e.target.selectedOptions[0];
  const hidden = document.getElementById('calendario_id');
  if (!opt) { hidden.value = ''; return; }
  // dataset.calendarioId contiene el id; actualizar hidden
  hidden.value = opt.dataset && opt.dataset.calendarioId ? opt.dataset.calendarioId : '';
});

// Listeners: cuando cambia el doctor, cargamos sedes y luego slots; sede/date solo recargan slots
document.getElementById('especialidad_id').addEventListener('change', async function(){
  // Intentar cargar doctores por API para la especialidad seleccionada.
  await loadDoctores(this.value);
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
let searchTimeout;
if (pacienteSearchInput) {
  pacienteSearchInput.addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      if (e.target.value.length >= 2) {
        searchPacientes();
      } else {
        const sr = document.getElementById('search_results');
        if (sr) sr.style.display = 'none';
      }
    }, 300);
  });
}

window.addEventListener('DOMContentLoaded', async function(){
  await loadEspecialidades();
  await loadSedes();
  await loadDates();
  loadSlots();
});

// Función para validar formulario de cita con SweetAlert2
function validateAppointmentForm(event, form) {
  event.preventDefault();
  
  const pacienteId = document.getElementById('paciente_id').value;
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
  
  // Mostrar confirmación
  const pacienteNombre = document.querySelector('#paciente_id option:checked').textContent;
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
      form.submit();
    }
  });
  
  return false;
}
</script>
<?php endif; ?>
