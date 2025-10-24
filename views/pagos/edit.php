<h1>Editar Pago</h1>

<div class="card" style="background-color: #f8f9fa; border-left: 4px solid #6c757d;">
    <h2 style="color: #6c757d; margin-bottom: 10px;">
        <i class="icon-info"></i> Información de la Cita (Solo Lectura)
    </h2>
    <p style="font-size: 0.9em; color: #6c757d; margin-bottom: 15px;">
        Esta información pertenece a la cita y no puede ser modificada desde aquí.
    </p>
    <div class="grid">
        <div class="col-6">
            <p><strong>Paciente:</strong> <?= htmlspecialchars(($pago['paciente_nombre'] ?? '') . ' ' . ($pago['paciente_apellido'] ?? '')) ?></p>
            <p><strong>Doctor:</strong> <?= htmlspecialchars(($pago['doctor_nombre'] ?? '') . ' ' . ($pago['doctor_apellido'] ?? '')) ?></p>
            <p><strong>Especialidad:</strong> <?= htmlspecialchars($pago['especialidad_nombre'] ?? 'N/A') ?></p>
        </div>
        <div class="col-6">
            <p><strong>Sede:</strong> <?= htmlspecialchars($pago['nombre_sede'] ?? 'N/A') ?></p>
            <p><strong>Fecha:</strong> <?= htmlspecialchars(date('Y-m-d', strtotime($pago['fecha']))) ?></p>
            <p><strong>Hora:</strong> <?= htmlspecialchars($pago['hora_inicio']) ?> - <?= htmlspecialchars($pago['hora_fin']) ?></p>
        </div>
    </div>
    
    <?php if (!empty($pago['razon'])): ?>
        <p><strong>Razón:</strong> <?= htmlspecialchars($pago['razon']) ?></p>
    <?php endif; ?>
</div>

<div class="card mt-4" style="border-left: 4px solid #28a745;">
    <h2 style="color: #28a745; margin-bottom: 10px;">
        <i class="icon-edit"></i> Información del Pago (Editable)
    </h2>
    <p style="font-size: 0.9em; color: #28a745; margin-bottom: 15px;">
        Puedes modificar estos campos relacionados específicamente con el pago.
    </p>
    
    <form method="POST" action="/pagos/<?= (int)$pago['id'] ?>/editar" onsubmit="return validateEditPayment(event, this);">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
        
        <div class="grid">
            <div class="col-6">
                <div class="form-group">
                    <label for="monto">Monto (S/) *</label>
                    <input type="number" id="monto" name="monto" step="0.01" min="0" required class="input" 
                           value="<?= htmlspecialchars($pago['monto'] ?? '0.00') ?>">
                </div>
                
                <div class="form-group">
                    <label for="metodo_pago">Método de Pago</label>
                    <select id="metodo_pago" name="metodo_pago" class="input">
                        <option value="efectivo" <?= ($pago['metodo_pago'] ?? '') === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                        <option value="tarjeta" <?= ($pago['metodo_pago'] ?? '') === 'tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
                        <option value="transferencia" <?= ($pago['metodo_pago'] ?? '') === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                        <option value="yape" <?= ($pago['metodo_pago'] ?? '') === 'yape' ? 'selected' : '' ?>>Yape</option>
                        <option value="plin" <?= ($pago['metodo_pago'] ?? '') === 'plin' ? 'selected' : '' ?>>Plin</option>
                    </select>
                </div>
            </div>
            
            <div class="col-6">
                <div class="form-group">
                    <label for="estado">Estado del Pago</label>
                    <select id="estado" name="estado" class="input">
                        <option value="completado" <?= ($pago['estado'] ?? '') === 'completado' ? 'selected' : '' ?>>Completado</option>
                        <option value="pendiente" <?= ($pago['estado'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="rechazado" <?= ($pago['estado'] ?? '') === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="fecha_pago">Fecha de Pago</label>
                    <input type="datetime-local" id="fecha_pago" name="fecha_pago" class="input" 
                           value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($pago['fecha_pago'] ?? 'now'))) ?>">
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="input" rows="3" 
                      placeholder="Notas adicionales sobre el pago..."><?= htmlspecialchars($pago['observaciones'] ?? '') ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn primary">Actualizar Pago</button>
            <a href="/pagos" class="btn">Cancelar</a>
        </div>
    </form>
</div>

<script>
// Función para validar el formulario de edición de pago
function validateEditPayment(event, form) {
  event.preventDefault();
  
  const monto = document.getElementById('monto').value;
  
  if (!monto || parseFloat(monto) <= 0) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'El monto debe ser mayor a 0'
    });
    return false;
  }
  
  Swal.fire({
    title: '¿Actualizar pago?',
    html: `
      <div style="text-align: left;">
        <p><strong>Monto:</strong> S/ ${parseFloat(monto).toFixed(2)}</p>
        <p><strong>Método:</strong> ${document.getElementById('metodo_pago').value.charAt(0).toUpperCase() + document.getElementById('metodo_pago').value.slice(1)}</p>
        <p><strong>Estado:</strong> ${document.getElementById('estado').value.charAt(0).toUpperCase() + document.getElementById('estado').value.slice(1)}</p>
        <p><strong>Paciente:</strong> <?= htmlspecialchars(($pago['paciente_nombre'] ?? '') . ' ' . ($pago['paciente_apellido'] ?? '')) ?></p>
      </div>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#28a745',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, actualizar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      form.submit();
    }
  });
  
  return false;
}
</script>
