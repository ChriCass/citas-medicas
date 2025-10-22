<?php $role = $user['rol'] ?? ''; ?>
<h1><?= htmlspecialchars($title ?? 'Citas') ?></h1>

<style>
  /* Ocultar visualmente la columna de acciones sin eliminar el código */
  .col-actions { display: none; }
</style>

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
          <td>
            <?php
              // Mostrar solo la hora (HH:MM). Acepta formatos como 'YYYY-mm-dd HH:MM:SS', 'HH:MM:SS' o con microsegundos
              $hi = $a['hora_inicio'] ?? '';
              try {
                if ($hi === '' || $hi === null) {
                  echo htmlspecialchars('');
                } else {
                  // Normalizar microsegundos si existieran
                  $hiClean = preg_replace('/\.[0-9]+$/', '', $hi);
                  $dt = new DateTime($hiClean);
                  echo htmlspecialchars($dt->format('H:i'));
                }
              } catch (Exception $e) {
                // Fallback: mostrar el raw truncado a 5 caracteres (HH:MM)
                echo htmlspecialchars(substr((string)$hi, 0, 5));
              }
            ?>
          </td>
          <td>
            <?php
              $hf = $a['hora_fin'] ?? '';
              try {
                if ($hf === '' || $hf === null) {
                  echo htmlspecialchars('');
                } else {
                  $hfClean = preg_replace('/\.[0-9]+$/', '', $hf);
                  $dt2 = new DateTime($hfClean);
                  echo htmlspecialchars($dt2->format('H:i'));
                }
              } catch (Exception $e) {
                echo htmlspecialchars(substr((string)$hf, 0, 5));
              }
            ?>
          </td>
          <td><?= htmlspecialchars($a['razon'] ?? '') ?></td>
          <td>
            <span class="chip status-<?= htmlspecialchars($a['estado']) ?>">
              <?= htmlspecialchars(ucfirst($a['estado'])) ?>
            </span>
          </td>
          <td>
            
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
              <form method="POST" action="/citas/<?= (int)$a['id'] ?>/cancel" style="display:inline" onsubmit="return confirm('¿Cancelar esta cita? (hasta 24h antes)');">
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
