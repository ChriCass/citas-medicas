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
    <label class="label" for="paciente_search">Buscar Paciente</label>
    <div style="display: flex; gap: 10px; align-items: center;">
      <input type="text" 
             id="paciente_search" 
             placeholder="Buscar por nombre, DNI o usuario_id..."
             style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
      <button type="button" id="search_paciente" class="btn secondary">Buscar</button>
      <button type="button" id="clear_search" class="btn secondary">Limpiar</button>
    </div>
    <div id="search_results" style="margin-top: 10px; max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; display: none;"></div>
  </div>

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
    <label class="label" for="doctor_id">Doctor</label>
    <select class="input" name="doctor_id" id="doctor_id" required>
      <option value="">— Selecciona un doctor —</option>
      <?php foreach (($doctores ?? []) as $d): ?>
        <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars(($d['user_nombre'] ?? '').' '.($d['user_apellido'] ?? '')) ?> — <?= htmlspecialchars($d['especialidad_nombre'] ?? '') ?></option>
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
    const url = `/api/v1/slots?date=${encodeURIComponent(date)}&doctor_id=${encodeURIComponent(d)}&location_id=${encodeURIComponent(l||0)}`;
    
    const res = await fetch(url);
    const data = await res.json();
    
    sel.innerHTML = '';
    
    if (data.slots && data.slots.length > 0) {
      // Agregar opción por defecto
      const defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = '— Selecciona una hora —';
      sel.appendChild(defaultOption);
      
      // Agregar slots disponibles
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

['doctor_id','sede_id','date'].forEach(id => document.getElementById(id).addEventListener('change', loadSlots));
window.addEventListener('DOMContentLoaded', loadSlots);

// Funcionalidad de búsqueda de pacientes
async function searchPacientes() {
  const query = document.getElementById('paciente_search').value.trim();
  const resultsDiv = document.getElementById('search_results');
  
  if (!query) {
    resultsDiv.style.display = 'none';
    return;
  }
  
  try {
    const response = await fetch(`/api/v1/pacientes/search?q=${encodeURIComponent(query)}`);
    const data = await response.json();
    
    if (data.pacientes && data.pacientes.length > 0) {
      resultsDiv.innerHTML = '';
      data.pacientes.forEach(paciente => {
        const div = document.createElement('div');
        div.style.padding = '10px';
        div.style.borderBottom = '1px solid #eee';
        div.style.cursor = 'pointer';
        div.style.backgroundColor = '#f9f9f9';
        div.innerHTML = `
          <strong>${paciente.nombre} ${paciente.apellido}</strong><br>
          <small>DNI: ${paciente.dni} | ID: ${paciente.usuario_id} | Email: ${paciente.email}</small>
        `;
        
        div.addEventListener('click', () => {
          selectPaciente(paciente);
        });
        
        resultsDiv.appendChild(div);
      });
      resultsDiv.style.display = 'block';
    } else {
      resultsDiv.innerHTML = '<div style="padding: 10px; color: #666;">No se encontraron pacientes</div>';
      resultsDiv.style.display = 'block';
    }
  } catch (error) {
    console.error('Error buscando pacientes:', error);
    resultsDiv.innerHTML = '<div style="padding: 10px; color: #d32f2f;">Error al buscar pacientes</div>';
    resultsDiv.style.display = 'block';
  }
}

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



// Event listeners
document.getElementById('search_paciente').addEventListener('click', searchPacientes);
document.getElementById('clear_search').addEventListener('click', clearSearch);
document.getElementById('paciente_search').addEventListener('keypress', (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    searchPacientes();
  }
});
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


document.getElementById('date').addEventListener('change', loadSlots);



// Búsqueda en tiempo real (opcional)
let searchTimeout;
document.getElementById('paciente_search').addEventListener('input', (e) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    if (e.target.value.length >= 2) {
      searchPacientes();
    } else {
      document.getElementById('search_results').style.display = 'none';
    }
  }, 300);
});

window.addEventListener('DOMContentLoaded', async function(){
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
