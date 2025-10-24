<?php $role = $user['rol'] ?? ''; ?>
<h1>Citas</h1>

<?php if ($role === 'superadmin'): ?>
  <a class="btn ghost" href="/citas/create">➕ Reservar nueva</a>
<?php endif; ?>

<div class="table-wrapper">
  <table>
    <thead>
      <tr>
        <th>Paciente</th>
        <th>Doctor</th>
        <th>Especialidad</th>
        <th>Sede</th>
        <th>Fecha</th>
        <th>Hora Inicio</th>
        <th>Hora Fin</th>
        <th>Razón</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($appts ?? []) as $a): ?>
        <tr>
          <td><?= htmlspecialchars(($a['paciente_nombre'] ?? '') . ' ' . ($a['paciente_apellido'] ?? '')) ?></td>
          <td><?= htmlspecialchars(($a['doctor_nombre'] ?? '') . ' ' . ($a['doctor_apellido'] ?? '')) ?></td>
          <td><?= htmlspecialchars($a['especialidad_nombre'] ?? 'N/A') ?></td>
          <td><?= htmlspecialchars($a['sede_nombre'] ?? 'N/A') ?></td>
          <td><?= htmlspecialchars(date('Y-m-d', strtotime($a['fecha']))) ?></td>
          <td><?= htmlspecialchars($a['hora_inicio']) ?></td>
          <td><?= htmlspecialchars($a['hora_fin']) ?></td>
          <td><?= htmlspecialchars($a['razon'] ?? '') ?></td>
          <td>
            <span class="chip status-<?= htmlspecialchars($a['estado']) ?>">
              <?= htmlspecialchars(ucfirst($a['estado'])) ?>
            </span>
          </td>
          <td>
            <?php if ($role === 'doctor' && $a['estado']==='confirmado'): ?>
              <form method="POST" action="/citas/<?= (int)$a['id'] ?>/mark-attended" style="display:inline">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                <button class="btn small primary" type="submit">Marcar atendida</button>
              </form>
            <?php endif; ?>

            <?php if (in_array($role, ['cajero','superadmin'], true)): ?>
              <form method="POST" action="/citas/<?= (int)$a['id'] ?>/status" style="display:inline">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                <select name="status" class="input" style="width:auto;display:inline-block">
                  <option value="pendiente"   <?= $a['estado']==='pendiente'?'selected':'' ?>>Pendiente</option>
                  <option value="confirmado" <?= $a['estado']==='confirmado'?'selected':'' ?>>Confirmado</option>
                  <option value="cancelado" <?= $a['estado']==='cancelado'?'selected':'' ?>>Cancelado</option>
                  <option value="atendido"  <?= $a['estado']==='atendido'?'selected':'' ?>>Atendido</option>
                </select>
                <button class="btn small" type="submit">Guardar</button>
              </form>
            <?php endif; ?>

            <?php if ($role === 'paciente' && $a['estado']!=='cancelado'): ?>
              <a href="/citas/<?= (int)$a['id'] ?>/edit" class="btn small primary" style="margin-right: 5px;">Modificar</a>
              <form method="POST" action="/citas/<?= (int)$a['id'] ?>/cancel" style="display:inline" onsubmit="return confirmCancelAppointment(event, this);">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                <button class="btn small danger" type="submit">Cancelar</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
// Función para confirmar cancelación de cita con SweetAlert2
function confirmCancelAppointment(event, form) {
  event.preventDefault();
  
  Swal.fire({
    title: '¿Cancelar cita?',
    text: '¿Estás seguro de que deseas cancelar esta cita? Solo puedes cancelar hasta 24 horas antes.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, cancelar',
    cancelButtonText: 'No, mantener'
  }).then((result) => {
    if (result.isConfirmed) {
      form.submit();
    }
  });
  
  return false;
}
</script>
