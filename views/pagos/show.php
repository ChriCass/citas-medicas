<h1>Registrar Pago</h1>

<div class="card">
    <h2>Detalles de la Cita</h2>
    <div class="grid">
        <div class="col-6">
            <p><strong>Paciente:</strong> <?= htmlspecialchars(($cita['paciente_nombre'] ?? '') . ' ' . ($cita['paciente_apellido'] ?? '')) ?></p>
            <p><strong>Doctor:</strong> <?= htmlspecialchars(($cita['doctor_nombre'] ?? '') . ' ' . ($cita['doctor_apellido'] ?? '')) ?></p>
            <p><strong>Especialidad:</strong> <?= htmlspecialchars($cita['especialidad_nombre'] ?? 'N/A') ?></p>
        </div>
        <div class="col-6">
            <p><strong>Sede:</strong> <?= htmlspecialchars($cita['nombre_sede'] ?? 'N/A') ?></p>
            <p><strong>Fecha:</strong> <?= htmlspecialchars(date('Y-m-d', strtotime($cita['fecha']))) ?></p>
            <p><strong>Hora:</strong> <?= htmlspecialchars($cita['hora_inicio']) ?> - <?= htmlspecialchars($cita['hora_fin']) ?></p>
        </div>
    </div>
    
    <?php if (!empty($cita['razon'])): ?>
        <p><strong>Razón:</strong> <?= htmlspecialchars($cita['razon']) ?></p>
    <?php endif; ?>
</div>

<div class="card mt-4">
    <h2>Registrar Pago</h2>
    
    <form method="POST" action="/pagos/registrar" onsubmit="return confirmPayment(event, this);">
        <input type="hidden" name="cita_id" value="<?= (int)$cita['id'] ?>">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
        
        <div class="form-group">
            <label for="monto">Monto a Pagar (S/)</label>
            <input type="number" id="monto" name="monto" step="0.01" min="0" required class="input">
        </div>
        
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
        
        <div class="form-group">
            <label for="observaciones">Observaciones (Opcional)</label>
            <textarea id="observaciones" name="observaciones" class="input" rows="3" placeholder="Notas adicionales sobre el pago..."></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn primary">Registrar Pago</button>
            <a href="/pagos" class="btn">Cancelar</a>
        </div>
    </form>
</div>

<script>
// Función para confirmar registro de pago con SweetAlert2
function confirmPayment(event, form) {
  event.preventDefault();
  
  const monto = document.getElementById('monto').value;
  const metodo = document.getElementById('metodo_pago').value;
  
  if (!monto || parseFloat(monto) <= 0) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Por favor ingresa un monto válido'
    });
    return false;
  }
  
  Swal.fire({
    title: '¿Confirmar pago?',
    html: `
      <div style="text-align: left;">
        <p><strong>Monto:</strong> S/ ${parseFloat(monto).toFixed(2)}</p>
        <p><strong>Método:</strong> ${metodo.charAt(0).toUpperCase() + metodo.slice(1)}</p>
        <p><strong>Paciente:</strong> <?= htmlspecialchars(($cita['paciente_nombre'] ?? '') . ' ' . ($cita['paciente_apellido'] ?? '')) ?></p>
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