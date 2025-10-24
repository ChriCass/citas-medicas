<?php $role = $user['rol'] ?? ''; ?>
<h1>Modificar Cita</h1>

<div class="card">
    <h2>Información Actual de la Cita</h2>
    <div class="grid">
        <div class="col-6">
            <p><strong>Doctor:</strong> 
                <?php 
                $doctorActual = null;
                foreach ($doctores as $doctor) {
                    if ($doctor['id'] == $cita['doctor_id']) {
                        $doctorActual = $doctor;
                        break;
                    }
                }
                echo $doctorActual ? htmlspecialchars($doctorActual['user_nombre'] . ' ' . $doctorActual['user_apellido']) : 'N/A';
                ?>
            </p>
            <p><strong>Especialidad:</strong> 
                <?php 
                echo $doctorActual ? htmlspecialchars($doctorActual['especialidad_nombre']) : 'N/A';
                ?>
            </p>
        </div>
        <div class="col-6">
            <p><strong>Sede:</strong> 
                <?php 
                $sedeActual = null;
                foreach ($sedes as $sede) {
                    if ($sede['id'] == $cita['sede_id']) {
                        $sedeActual = $sede;
                        break;
                    }
                }
                echo $sedeActual ? htmlspecialchars($sedeActual['nombre_sede']) : 'N/A';
                ?>
            </p>
            <p><strong>Fecha:</strong> <?= htmlspecialchars($cita['fecha']) ?></p>
            <p><strong>Hora:</strong> <?= htmlspecialchars($cita['hora_inicio']) ?> - <?= htmlspecialchars($cita['hora_fin']) ?></p>
        </div>
    </div>
    <?php if (!empty($cita['razon'])): ?>
        <p><strong>Razón:</strong> <?= htmlspecialchars($cita['razon']) ?></p>
    <?php endif; ?>
</div>

<div class="card mt-4">
    <h2>Modificar Cita</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <form method="POST" action="/citas/<?= (int)$cita['id'] ?>/update" onsubmit="return validateEditAppointmentForm(event, this);">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
        
        <div class="grid">
            <div class="col-6">
                <div class="form-group">
                    <label for="doctor_id">Doctor:</label>
                    <select name="doctor_id" id="doctor_id" class="form-control">
                        <option value="">Seleccionar doctor</option>
                        <?php foreach ($doctores as $doctor): ?>
                            <option value="<?= (int)$doctor['id'] ?>" 
                                    <?= $doctor['id'] == $cita['doctor_id'] ? 'selected' : '' ?>
                                    data-especialidad="<?= htmlspecialchars($doctor['especialidad_nombre']) ?>">
                                <?= htmlspecialchars($doctor['user_nombre'] . ' ' . $doctor['user_apellido']) ?>
                                - <?= htmlspecialchars($doctor['especialidad_nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="sede_id">Sede:</label>
                    <select name="sede_id" id="sede_id" class="form-control">
                        <option value="">Seleccionar sede</option>
                        <?php foreach ($sedes as $sede): ?>
                            <option value="<?= (int)$sede['id'] ?>" 
                                    <?= $sede['id'] == $cita['sede_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sede['nombre_sede']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="col-6">
                <div class="form-group">
                    <label for="fecha">Fecha:</label>
                    <input type="date" 
                           name="fecha" 
                           id="fecha" 
                           value="<?= htmlspecialchars($cita['fecha']) ?>"
                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                           class="form-control" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="horario_disponible">Horario Disponible:</label>
                    <select name="horario_disponible" id="horario_disponible" class="form-control">
                        <option value="">Seleccionar horario</option>
                    </select>
                    <small class="form-text text-muted">Selecciona un horario disponible para el doctor y sede seleccionados</small>
                </div>
                
                <div class="form-group" style="display: none;">
                    <label for="hora_inicio">Hora de Inicio:</label>
                    <input type="time" 
                           name="hora_inicio" 
                           id="hora_inicio" 
                           value="<?= htmlspecialchars($cita['hora_inicio']) ?>"
                           class="form-control" 
                           required>
                </div>
                
                <div class="form-group" style="display: none;">
                    <label for="hora_fin">Hora de Fin:</label>
                    <input type="time" 
                           name="hora_fin" 
                           id="hora_fin" 
                           value="<?= htmlspecialchars($cita['hora_fin']) ?>"
                           class="form-control" 
                           required>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="razon">Razón de la consulta:</label>
            <textarea name="razon" 
                      id="razon" 
                      class="form-control" 
                      rows="3" 
                      placeholder="Describe brevemente el motivo de la consulta"><?= htmlspecialchars($cita['razon'] ?? '') ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn primary">Guardar Cambios</button>
            <a href="/citas" class="btn secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const doctorSelect = document.getElementById('doctor_id');
    const sedeSelect = document.getElementById('sede_id');
    const fechaInput = document.getElementById('fecha');
    const horarioSelect = document.getElementById('horario_disponible');
    const horaInicioInput = document.getElementById('hora_inicio');
    const horaFinInput = document.getElementById('hora_fin');
    
    // Cargar horarios disponibles
    function loadAvailableSlots() {
        const doctorId = doctorSelect.value;
        const sedeId = sedeSelect.value;
        const fecha = fechaInput.value;
        
        if (!doctorId || !fecha) {
            horarioSelect.innerHTML = '<option value="">Selecciona doctor y fecha primero</option>';
            return;
        }
        
        // Mostrar loading
        horarioSelect.innerHTML = '<option value="">Cargando horarios...</option>';
        horarioSelect.disabled = true;
        
        // Hacer petición a la API
        fetch(`/api/v1/slots?date=${fecha}&doctor_id=${doctorId}&location_id=${sedeId}`)
            .then(response => response.json())
            .then(data => {
                horarioSelect.innerHTML = '<option value="">Seleccionar horario</option>';
                
                if (data.slots && data.slots.length > 0) {
                    data.slots.forEach(slot => {
                        const option = document.createElement('option');
                        option.value = `${slot.start}-${slot.end}`;
                        option.textContent = `${slot.start} - ${slot.end}`;
                        horarioSelect.appendChild(option);
                    });
                } else {
                    horarioSelect.innerHTML = '<option value="">No hay horarios disponibles</option>';
                }
                
                horarioSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error al cargar horarios:', error);
                horarioSelect.innerHTML = '<option value="">Error al cargar horarios</option>';
                horarioSelect.disabled = false;
            });
    }
    
    // Cargar horarios cuando cambie doctor, sede o fecha
    doctorSelect.addEventListener('change', loadAvailableSlots);
    sedeSelect.addEventListener('change', loadAvailableSlots);
    fechaInput.addEventListener('change', loadAvailableSlots);
    
    // Actualizar campos de hora cuando se seleccione un horario
    horarioSelect.addEventListener('change', function() {
        const selectedValue = this.value;
        if (selectedValue && selectedValue.includes('-')) {
            const [horaInicio, horaFin] = selectedValue.split('-');
            horaInicioInput.value = horaInicio;
            horaFinInput.value = horaFin;
        }
    });
    
    // Validar que la fecha sea futura
    fechaInput.addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate <= today) {
            this.setCustomValidity('La fecha debe ser futura');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Cargar horarios iniciales si ya hay valores seleccionados
    if (doctorSelect.value && fechaInput.value) {
        loadAvailableSlots();
    }
});

// Función para validar formulario de edición de cita con SweetAlert2
function validateEditAppointmentForm(event, form) {
  event.preventDefault();
  
  const doctorId = document.getElementById('doctor_id').value;
  const sedeId = document.getElementById('sede_id').value;
  const fecha = document.getElementById('fecha').value;
  const horario = document.getElementById('horario_disponible').value;
  
  if (!doctorId) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Por favor selecciona un doctor'
    });
    return false;
  }
  
  if (!sedeId) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Por favor selecciona una sede'
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
  
  if (!horario) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Por favor selecciona un horario disponible'
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
  const doctorNombre = document.querySelector('#doctor_id option:checked').textContent;
  const sedeNombre = document.querySelector('#sede_id option:checked').textContent;
  const [horaInicio, horaFin] = horario.split('-');
  
  Swal.fire({
    title: '¿Confirmar modificación?',
    html: `
      <div style="text-align: left;">
        <p><strong>Doctor:</strong> ${doctorNombre}</p>
        <p><strong>Sede:</strong> ${sedeNombre}</p>
        <p><strong>Fecha:</strong> ${fecha}</p>
        <p><strong>Horario:</strong> ${horaInicio} - ${horaFin}</p>
      </div>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#28a745',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, modificar cita',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      form.submit();
    }
  });
  
  return false;
}
</script>
