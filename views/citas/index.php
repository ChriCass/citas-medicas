<?php $role = $user['role'] ?? ''; ?>
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
        <th>Sede</th>
        <th>Servicio</th>
        <th>Inicio</th>
        <th>Fin</th>
        <th>Estado</th>
        <th>Pago</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($appts ?? []) as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['user_name'] ?? ($user['name'] ?? 'Yo')) ?></td>
          <td><?= htmlspecialchars($a['doctor_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($a['location_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($a['service_name'] ?? '') ?></td>
          <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($a['starts_at']))) ?></td>
          <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($a['ends_at']))) ?></td>
          <td>
            <span class="chip status-<?= htmlspecialchars($a['status']) ?>">
              <?= htmlspecialchars(ucfirst($a['status'])) ?>
            </span>
          </td>
          <td>
            <span class="chip" style="background: <?= $a['payment_status']==='paid' ? 'rgba(102,191,191,.20)' : 'rgba(255,0,99,.12)' ?>;">
              <?= $a['payment_status']==='paid' ? 'Pagado' : 'No pagado' ?>
            </span>
          </td>
          <td>
            <?php if ($role === 'doctor' && (int)$user['id'] === (int)($a['doctor_id'] ?? 0) && $a['status']!=='attended' && $a['status']!=='cancelled'): ?>
              <form method="POST" action="/citas/<?= (int)$a['id'] ?>/attended" style="display:inline">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                <button class="btn small primary" type="submit">Marcar atendida</button>
              </form>
            <?php endif; ?>

            <?php if (in_array($role, ['cashier','superadmin'], true)): ?>
              <form method="POST" action="/citas/<?= (int)$a['id'] ?>/status" style="display:inline">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                <select name="status" class="input" style="width:auto;display:inline-block">
                  <option value="pending"   <?= $a['status']==='pending'?'selected':'' ?>>Pendiente</option>
                  <option value="confirmed" <?= $a['status']==='confirmed'?'selected':'' ?>>Confirmado</option>
                  <option value="cancelled" <?= $a['status']==='cancelled'?'selected':'' ?>>Cancelado</option>
                  <option value="attended"  <?= $a['status']==='attended'?'selected':'' ?>>Atendida</option>
                </select>
                <button class="btn small" type="submit">Guardar</button>
              </form>
            <?php endif; ?>

            <?php if ($role === 'cashier'): ?>
              <form method="POST" action="/citas/<?= (int)$a['id'] ?>/payment" style="display:inline">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                <select name="payment_status" class="input" style="width:auto;display:inline-block" <?= $a['status']!=='attended' ? 'disabled' : '' ?>>
                  <option value="unpaid" <?= $a['payment_status']==='unpaid'?'selected':'' ?>>No pagado</option>
                  <option value="paid"   <?= $a['payment_status']==='paid'?'selected':''   ?>>Pagado</option>
                </select>
                <button class="btn small" type="submit" <?= $a['status']!=='attended' ? 'disabled' : '' ?>>Actualizar pago</button>
              </form>
            <?php endif; ?>

            <?php if ($role === 'patient' && $a['status']!=='cancelled'): ?>
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
