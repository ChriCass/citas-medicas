<?php
/** Espera $title, $schedules, $user (opcional) */
?>
<section class="hero">
  <div>
    <h1><?= htmlspecialchars($title ?? 'Horarios Doctores') ?></h1>
    <p class="mt-2">Administra <strong>horarios semanales</strong> por <strong>doctor</strong> y <strong>sede</strong>.</p>
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
            <th>Día de la semana</th>
            <th>Hora inicio</th>
            <th>Hora fin</th>
            <th>Estado</th>
            <th>Observaciones</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($schedules as $h): ?>
          <tr>
            <td>
              <?= htmlspecialchars($h->doctor->user->nombre ?? '') ?> <?= htmlspecialchars($h->doctor->user->apellido ?? '') ?><br>
              <small class="muted"><?= htmlspecialchars($h->doctor->user->email ?? '') ?></small>
            </td>
            <td><?= htmlspecialchars($h->sede->nombre_sede ?? 'Cualquier sede') ?></td>
            <td>
              <strong><?= htmlspecialchars(ucfirst($h->dia_semana ?? '')) ?></strong>
            </td>
            <td><?= htmlspecialchars(substr((string)$h->hora_inicio, 0, 5)) ?></td>
            <td><?= htmlspecialchars(substr((string)$h->hora_fin, 0, 5)) ?></td>
            <td>
              <span class="badge <?= $h->activo ? 'success' : 'danger' ?>">
                <?= $h->activo ? 'Activo' : 'Inactivo' ?>
              </span>
            </td>
            <td><?= htmlspecialchars($h->observaciones ?? '') ?></td>
            <td>
              <form method="POST" action="/doctor-schedules/<?= (int)$h->id ?>/delete"
                    onsubmit="return confirm('¿Eliminar este patrón de horario?');" style="display:inline">
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
