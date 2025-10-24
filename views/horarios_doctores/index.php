<?php
/** Espera $title, $schedules, $user (opcional) */
?>
<section class="hero">
  <div>
    <h1><?= htmlspecialchars($title ?? 'Horarios Doctores') ?></h1>
    <p class="mt-2">Administra turnos por <strong>doctor</strong>, <strong>sede</strong> y <strong>fecha específica</strong>.</p>
    <div class="auth-links mt-3">
      <a href="/doctor-schedules/create" class="btn primary">+ Nuevo horario</a>
    </div>
  </div>
</section>

<section class="mt-6">
  <?php if (empty($schedules)): ?>
    <div class="card"><div class="content">
      <p class="muted">No hay horarios registrados.</p>
    </div></div>
  <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Doctor</th>
            <th>Sede</th>
            <th>Fecha</th>
            <th>Día</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Estado</th>
            <th>Observaciones</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($schedules as $h): ?>
          <tr>
            <td>
              <?= htmlspecialchars(($h['doctor_nombre'] ?? '') . ' ' . ($h['doctor_apellido'] ?? '')) ?><br>
              <small class="muted">Doctor ID: <?= htmlspecialchars($h['doctor_id'] ?? '') ?></small>
            </td>
            <td><?= htmlspecialchars($h['nombre_sede'] ?? 'Sin sede') ?></td>
            <td>
              <?php 
                $fecha = $h['fecha'] ?? '';
                echo htmlspecialchars(date('d/m/Y', strtotime($fecha))); 
              ?>
            </td>
            <td>
              <?php 
                $fecha = $h['fecha'] ?? '';
                if ($fecha) {
                  $dias = ['Sunday' => 'Domingo', 'Monday' => 'Lunes', 'Tuesday' => 'Martes', 
                           'Wednesday' => 'Miércoles', 'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado'];
                  $dia = $dias[date('l', strtotime($fecha))] ?? date('l', strtotime($fecha));
                  echo htmlspecialchars($dia);
                }
              ?>
            </td>
            <td><?= htmlspecialchars(substr((string)$h['hora_inicio'],0,5)) ?></td>
            <td><?= htmlspecialchars(substr((string)$h['hora_fin'],0,5)) ?></td>
            <td>
              <span class="badge <?= ($h['activo'] ?? 1) ? 'success' : 'danger' ?>">
                <?= ($h['activo'] ?? 1) ? 'Activo' : 'Inactivo' ?>
              </span>
            </td>
            <td><?= htmlspecialchars($h['observaciones'] ?? '') ?></td>
            <td>
              <form method="POST" action="/doctor-schedules/<?= (int)$h['id'] ?>/delete"
                    onsubmit="return confirmDeleteSchedule(event, this);" style="display:inline">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                <button type="submit" class="btn small danger">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<script>
// Función para confirmar eliminación de horario con SweetAlert2
function confirmDeleteSchedule(event, form) {
  event.preventDefault();
  
  Swal.fire({
    title: '¿Eliminar horario?',
    text: '¿Estás seguro de que deseas eliminar este horario? Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      form.submit();
    }
  });
  
  return false;
}
</script>
