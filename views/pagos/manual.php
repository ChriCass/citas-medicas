<h1>Registrar Pago Manual</h1>

<div class="card">
    <h2>Datos del Pago</h2>
    
    <form method="POST" action="/pagos/registrar-manual" onsubmit="return validateManualPayment(event, this);">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
        
        <div class="grid">
            <div class="col-6">
                <div class="form-group">
                    <label for="paciente_id">Paciente *</label>
                    <select id="paciente_id" name="paciente_id" required class="input">
                        <option value="">Seleccionar paciente...</option>
                        <?php foreach ($pacientes as $paciente): ?>
                            <option value="<?= (int)$paciente['id'] ?>">
                                <?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido']) ?> 
                                (<?= htmlspecialchars($paciente['dni']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="doctor_id">Doctor *</label>
                    <select id="doctor_id" name="doctor_id" required class="input">
                        <option value="">Seleccionar doctor...</option>
                        <?php foreach ($doctores as $doctor): ?>
                            <option value="<?= (int)$doctor['id'] ?>">
                                <?= htmlspecialchars($doctor['nombre'] . ' ' . $doctor['apellido']) ?>
                                <?php if ($doctor['especialidad_nombre']): ?>
                                    - <?= htmlspecialchars($doctor['especialidad_nombre']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="sede_id">Sede</label>
                    <select id="sede_id" name="sede_id" class="input">
                        <option value="">Seleccionar sede...</option>
                        <?php foreach ($sedes as $sede): ?>
                            <option value="<?= (int)$sede['id'] ?>">
                                <?= htmlspecialchars($sede['nombre_sede']) ?>
                                <?php if ($sede['direccion']): ?>
                                    - <?= htmlspecialchars($sede['direccion']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="col-6">
                <div class="form-group">
                    <label for="fecha">Fecha *</label>
                    <input type="date" id="fecha" name="fecha" required class="input" value="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label for="hora">Hora *</label>
                    <input type="time" id="hora" name="hora" required class="input" value="<?= date('H:i') ?>">
                </div>
                
                <div class="form-group">
                    <label for="monto">Monto (S/) *</label>
                    <input type="number" id="monto" name="monto" step="0.01" min="0" required class="input" placeholder="0.00">
                </div>
            </div>
        </div>
        
        <div class="grid">
            <div class="col-6">
                <div class="form-group">
                    <label for="metodo_pago">Método de Pago</label>
                    <select id="metodo_pago" name="metodo_pago" class="input">
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="yape">Yape</option>
                        <option value="plin">Plin</option>
                    </select>
                </div>
            </div>
            
            <div class="col-6">
                <div class="form-group">
                    <label for="estado">Estado del Pago</label>
                    <select id="estado" name="estado" class="input">
                        <option value="pagado">Pagado</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="rechazado">Rechazado</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="input" rows="3" placeholder="Notas adicionales sobre el pago..."></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn primary">Registrar Pago</button>
            <a href="/pagos" class="btn">Cancelar</a>
        </div>
    </form>
</div>

<script>
// Función para validar el formulario de pago manual
function validateManualPayment(event, form) {
  event.preventDefault();
  
  const paciente = document.getElementById('paciente_id').value;
  const doctor = document.getElementById('doctor_id').value;
  const monto = document.getElementById('monto').value;
  const fecha = document.getElementById('fecha').value;
  const hora = document.getElementById('hora').value;
  
  if (!paciente || !doctor || !monto || !fecha || !hora) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Por favor completa todos los campos requeridos'
    });
    return false;
  }
  
  if (parseFloat(monto) <= 0) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'El monto debe ser mayor a 0'
    });
    return false;
  }
  
  // Validar que la fecha no sea futura (opcional)
  const fechaPago = new Date(fecha + ' ' + hora);
  const ahora = new Date();
  
  if (fechaPago > ahora) {
    Swal.fire({
      icon: 'warning',
      title: 'Advertencia',
      text: 'La fecha y hora del pago es futura. ¿Estás seguro de continuar?',
      showCancelButton: true,
      confirmButtonText: 'Sí, continuar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
    return false;
  }
  
  Swal.fire({
    title: '¿Confirmar pago manual?',
    html: `
      <div style="text-align: left;">
        <p><strong>Paciente:</strong> ${document.getElementById('paciente_id').selectedOptions[0].text}</p>
        <p><strong>Doctor:</strong> ${document.getElementById('doctor_id').selectedOptions[0].text}</p>
        <p><strong>Monto:</strong> S/ ${parseFloat(monto).toFixed(2)}</p>
        <p><strong>Método:</strong> ${document.getElementById('metodo_pago').value.charAt(0).toUpperCase() + document.getElementById('metodo_pago').value.slice(1)}</p>
        <p><strong>Fecha:</strong> ${fecha} ${hora}</p>
      </div>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#28a745',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, registrar pago',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      form.submit();
    }
  });
  
  return false;
}
</script>
