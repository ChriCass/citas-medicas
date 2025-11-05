<?php $role = $user['rol'] ?? ''; ?>
<h1><?= htmlspecialchars($title ?? "Today's appointments") ?></h1>

<style>
  /* Keep the same visual hiding for actions column for layout parity */
  .col-actions { display: none; }

  /* Action buttons styling */
  .actions { display: flex; flex-direction: row; gap: 6px; align-items: center; }
  .actions form { display: inline-block; margin: 0; }
  /* Uniform font size and height for all controls inside actions */
  .actions .btn, .actions select.input, .actions a.btn { font-size: 0.95rem; padding: 8px 10px; min-height:36px; line-height:1; box-sizing: border-box; }
  .actions select.input { height:36px; }
  /* Button color semantics */
  .btn.small.primary { background: #2e8b57; color: #fff; border: none; } /* Atender - green */
  .btn.small.secondary { background: #f0f0f0; color: #111; border: none; } /* Modificar - neutral */
  .btn.small.danger { background: #d64541; color: #fff; border: none; } /* Cancel / No show - red */
</style>

<?php if ($role === 'superadmin'): ?>
  <a class="btn ghost" href="/citas/create">➕ New appointment</a>
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
        <?php $pacienteFull = htmlspecialchars((($a['paciente_nombre'] ?? '') . ' ' . ($a['paciente_apellido'] ?? ''))); ?>
        <tr data-paciente="<?= $pacienteFull ?>">
          <td><?= htmlspecialchars(($a['paciente_nombre'] ?? '') . ' ' . ($a['paciente_apellido'] ?? '')) ?></td>
          <td><?= htmlspecialchars(($a['doctor_nombre'] ?? '') . ' ' . ($a['doctor_apellido'] ?? '')) ?></td>
          <td><?= htmlspecialchars($a['especialidad_nombre'] ?? 'N/A') ?></td>
          <td><?= htmlspecialchars($a['sede_nombre'] ?? 'N/A') ?></td>
          <td><?= htmlspecialchars(date('Y-m-d', strtotime($a['fecha']))) ?></td>
          <td>
            <?php
              $hi = $a['hora_inicio'] ?? '';
              try {
                if ($hi === '' || $hi === null) {
                  echo htmlspecialchars('');
                } else {
                  $hiClean = preg_replace('/\.[0-9]+$/', '', $hi);
                  $dt = new DateTime($hiClean);
                  echo htmlspecialchars($dt->format('H:i'));
                }
              } catch (Exception $e) {
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
            <div class="actions" role="group" aria-label="Appointment actions">
              <?php if ($role === 'doctor' && $a['estado']==='confirmado'): ?>
                <a class="btn small primary" href="/citas/<?= (int)$a['id'] ?>/attend" title="Atender">Atender</a>
                <form method="POST" action="/citas/<?= (int)$a['id'] ?>/ausente" class="ausente-form">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                  <button class="btn small danger" type="submit" title="Ausente">Ausente</button>
                </form>
              <?php elseif ($role === 'doctor' && $a['estado']==='atendido'): ?>
                <!-- Allow editing for appointments already attended -->
                <a class="btn small secondary" href="/citas/<?= (int)$a['id'] ?>/edit" title="Editar">Editar</a>
              <?php endif; ?>

              <?php if (in_array($role, ['cajero','superadmin'], true)): ?>
                <form method="POST" action="/citas/<?= (int)$a['id'] ?>/status">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                  <select name="status" class="input" style="width:auto;display:inline-block">
                    <option value="pendiente"   <?= $a['estado']==='pendiente'?'selected':'' ?>>Pending</option>
                    <option value="confirmado" <?= $a['estado']==='confirmado'?'selected':'' ?>>Confirmed</option>
                    <option value="cancelado" <?= $a['estado']==='cancelado'?'selected':'' ?>>Cancelled</option>
                    <option value="atendido"  <?= $a['estado']==='atendido'?'selected':'' ?>>Attended</option>
                  </select>
                  <button class="btn small" type="submit">Save</button>
                </form>
              <?php endif; ?>

              <?php if ($role === 'paciente' && $a['estado']!=='cancelado'): ?>
                <form method="POST" action="/citas/<?= (int)$a['id'] ?>/cancel" onsubmit="return confirm('Cancel this appointment? (allowed until 24h before)');">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                  <button class="btn small danger" type="submit">Cancelar</button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<!-- Modal for confirming 'Ausente' action -->
<div id="ausente_modal" style="display:none; position:fixed; left:0; top:0; right:0; bottom:0; background:rgba(0,0,0,0.4); align-items:center; justify-content:center; z-index:2000;">
  <div style="background:#fff; padding:18px; width:400px; max-width:90%; margin:auto; border-radius:6px; box-shadow:0 8px 24px rgba(0,0,0,.2)">
    <h3 style="margin-top:0">Confirmar ausencia</h3>
    <p id="ausente_modal_text">¿Marcar esta cita como ausente?</p>
    <div style="text-align:right; margin-top:12px; display:flex; gap:8px; justify-content:flex-end">
      <button type="button" id="ausente_cancel" class="btn">Cancelar</button>
      <button type="button" id="ausente_confirm" class="btn small danger">Marcar como ausente</button>
    </div>
  </div>
</div>

<script>
  (function(){
    let pendingForm = null;
    function openModal(form){
      pendingForm = form;
      const modal = document.getElementById('ausente_modal');
      const text = document.getElementById('ausente_modal_text');
      // Optionally include appointment id in text
      try{
        const act = form.getAttribute('action') || '';
        const idMatch = act.match(/\/citas\/(\d+)\/ausente/);
        try{
        const tr = form.closest && form.closest('tr');
        const paciente = tr ? (tr.dataset && tr.dataset.paciente ? tr.dataset.paciente : null) : null;
        if (paciente) {
          text.textContent = '¿Marcar la cita del paciente ' + paciente + ' como ausente?';
        } else if (idMatch) {
          text.textContent = '¿Marcar la cita #' + idMatch[1] + ' como ausente?';
        }
      }catch(e){}
      }catch(e){}
      modal.style.display = 'flex';
    }
    function closeModal(){ pendingForm = null; const modal = document.getElementById('ausente_modal'); modal.style.display = 'none'; }

    // Intercept submit on forms with class 'ausente-form'
    document.addEventListener('click', function(e){
      const btn = e.target.closest && e.target.closest('button[type="submit"]');
      if (!btn) return;
      const form = btn.closest && btn.closest('form.ausente-form');
      if (!form) return;
      e.preventDefault();
      openModal(form);
    }, true);

    document.getElementById('ausente_cancel').addEventListener('click', function(){ closeModal(); });
    document.getElementById('ausente_confirm').addEventListener('click', function(){
      if (!pendingForm) return closeModal();
      // submit the form programmatically
      pendingForm.submit();
    });
  })();
</script>
