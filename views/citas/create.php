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
           value="<?= htmlspecialchars($today ?? date('Y-m-d', strtotime('+1 day'))) ?>"
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
        o.value = slot.start; 
        o.textContent = slot.start + ' - ' + slot.end + ' (15 min)'; 
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

// Event listeners
document.getElementById('search_paciente').addEventListener('click', searchPacientes);
document.getElementById('clear_search').addEventListener('click', clearSearch);
document.getElementById('paciente_search').addEventListener('keypress', (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    searchPacientes();
  }
});

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
