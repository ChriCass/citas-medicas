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
              <?= htmlspecialchars($h['doctor_name'] ?? '') ?><br>
              <small class="muted"><?= htmlspecialchars($h['doctor_email'] ?? '') ?></small>
            </td>
            <td><?= htmlspecialchars($h['sede_nombre'] ?? 'Sin sede') ?></td>
            <td>
              <?php 
                $fecha = $h['fecha'] ?? '';
                echo htmlspecialchars(date('d/m/Y', strtotime($fecha))); 
              ?>
            </td>
            <td><?= htmlspecialchars($h['dia_nombre'] ?? '') ?></td>
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
                    onsubmit="return confirm('¿Eliminar este horario?');" style="display:inline">
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
