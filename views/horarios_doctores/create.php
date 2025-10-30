<?php
// Vista: formulario para CREAR HORARIOS semanales (Step 1: Admin define schedule patterns)
// Espera: $title, $doctors, $sedes, $error (opcional) y $old (opcional)
$role = $_SESSION['user']['rol'] ?? '';
?>
<?php if ($role !== 'superadmin'): ?>
  <div class="alert">No tienes acceso a esta pantalla.</div>
  <?php return; ?>
<?php endif; ?>

<h1><?= htmlspecialchars($title ?? 'Crear patrón de horarios semanales') ?></h1>

<?php if (!empty($error)): ?>
  <div class="alert error mt-2"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" class="form mt-3" action="/doctor-schedules/assign" id="patternForm">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
  <input type="hidden" name="generate_slots" value="1">

  <div class="row">
    <label class="label" for="doctor_id">Doctor <span aria-hidden="true">*</span></label>
    <select name="doctor_id" id="doctor_id" class="input" required>
      <option value="">— Selecciona —</option>
      <?php foreach (($doctors ?? []) as $d): ?>
        <option value="<?= (int)$d['id'] ?>" <?= (!empty($old['doctor_id']) && (int)$old['doctor_id']===(int)$d['id'])? 'selected':'' ?>>
          <?= htmlspecialchars(($d['nombre'] ?? '').' '.($d['apellido'] ?? '')) ?> — <?= htmlspecialchars($d['email'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row compact">
    <label class="label">Mes y Año</label>
    <div style="display:flex;gap:8px;align-items:center;">
      <select name="mes" id="mes" class="input" required>
        <?php
          $months = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
          $oldMonth = !empty($old['mes']) ? (int)$old['mes'] : (int)date('n');
          foreach ($months as $num => $name):
        ?>
          <option value="<?= $num ?>" <?= $oldMonth===$num? 'selected':'' ?>><?= htmlspecialchars($name) ?></option>
        <?php endforeach; ?>
      </select>

      <select name="anio" id="anio" class="input" required>
        <?php
          $startYear = (int)date('Y');
          $oldYear = !empty($old['anio']) ? (int)$old['anio'] : $startYear;
          for ($y = $startYear; $y <= $startYear + 2; $y++):
        ?>
          <option value="<?= $y ?>" <?= $oldYear===$y? 'selected':'' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </div>
  </div>

  <div class="row">
    <label class="label">Horarios de trabajo<span aria-hidden="true">*</span></label>
    <div class="table-responsive">
      <table class="table" id="daysTable" style="width:100%;border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;padding:8px">Día de la semana</th>
            <th style="text-align:left;padding:8px">Sede</th>
            <th style="text-align:left;padding:8px">Hora inicio (24h)</th>
            <th style="text-align:left;padding:8px">Hora fin (24h)</th>
            <th style="text-align:left;padding:8px">Acción</th>
          </tr>
        </thead>
        <tbody id="daysTbody">
          <?php
            // Si hay datos previos en $old (cuando la validación falla), renderizar esas filas.
            $oldDays = $old['days'] ?? [];
            $daysOpts = ['lunes'=>'Lunes','martes'=>'Martes','miércoles'=>'Miércoles','jueves'=>'Jueves','viernes'=>'Viernes','sábado'=>'Sábado'];
            if (!empty($oldDays) && is_array($oldDays)):
              foreach ($oldDays as $idx => $dayKey):
                $dayKey = (string)$dayKey;
                $oldStart = $old['horarios_inicio'][$dayKey] ?? '';
                $oldEnd = $old['horarios_fin'][$dayKey] ?? '';
                $oldSede = $old['sede_for_day'][$dayKey] ?? '';
          ?>
            <tr>
              <td style="padding:6px;vertical-align:middle;">
                <select name="days[]" class="input day-select" required>
                  <option value="">— Selecciona día —</option>
                  <?php foreach ($daysOpts as $optKey => $optLabel): ?>
                    <option value="<?= htmlspecialchars($optKey) ?>" <?= $optKey === $dayKey ? 'selected' : '' ?>><?= htmlspecialchars($optLabel) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td style="padding:6px;vertical-align:middle;">
                <select name="sede_for_day[<?= htmlspecialchars($dayKey) ?>]" class="input sede-select">
                  <option value="">— Cualquier sede —</option>
                  <?php foreach (($sedes ?? []) as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= ((string)$oldSede === (string)$s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nombre_sede'] ?? $s['nombre'] ?? '') ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td style="padding:6px;vertical-align:middle;">
                <select name="horarios_inicio[<?= htmlspecialchars($dayKey) ?>]" class="input time-24 start-select">
                  <option value="">—</option>
                  <?php for ($m = 8*60; $m <= 19*60 + 30; $m += 15): $hh = str_pad(intval($m / 60), 2, '0', STR_PAD_LEFT); $mm = str_pad($m % 60, 2, '0', STR_PAD_LEFT); $t = $hh . ':' . $mm; ?>
                    <option value="<?= $t ?>" <?= ($oldStart === $t) ? 'selected' : '' ?>><?= $t ?></option>
                  <?php endfor; ?>
                </select>
              </td>
              <td style="padding:6px;vertical-align:middle;">
                <select name="horarios_fin[<?= htmlspecialchars($dayKey) ?>]" class="input time-24 end-select">
                  <option value="">—</option>
                  <?php for ($m = 8*60; $m <= 19*60 + 30; $m += 15): $hh = str_pad(intval($m / 60), 2, '0', STR_PAD_LEFT); $mm = str_pad($m % 60, 2, '0', STR_PAD_LEFT); $t = $hh . ':' . $mm; ?>
                    <option value="<?= $t ?>" <?= ($oldEnd === $t) ? 'selected' : '' ?>><?= $t ?></option>
                  <?php endfor; ?>
                </select>
              </td>
              <td style="padding:6px;vertical-align:middle;">
                <button type="button" class="btn small danger remove-row">Eliminar</button>
              </td>
            </tr>
          <?php
              endforeach;
            endif;
          ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="5" style="padding:8px;">
              <button type="button" id="addRowBtn" class="btn">Agregar día</button>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
    <small class="hint">Define el patrón de horarios semanal para el médico. Cada fila representa un día de la semana con su horario de atención.</small>
  </div>

  <!-- Template row for cloning -->
  <template id="rowTemplate">
    <tr>
      <td style="padding:6px;vertical-align:middle;">
        <select name="days[]" class="input day-select" required>
          <option value="">— Selecciona día —</option>
          <?php foreach ($daysOpts as $optKey => $optLabel): ?>
            <option value="<?= htmlspecialchars($optKey) ?>"><?= htmlspecialchars($optLabel) ?></option>
          <?php endforeach; ?>
        </select>
      </td>
      <td style="padding:6px;vertical-align:middle;">
        <select name="sede_for_day[]" class="input sede-select">
          <option value="">— Cualquier sede —</option>
        </select>
      </td>
      <td style="padding:6px;vertical-align:middle;">
        <select name="horarios_inicio[]" class="input time-24 start-select">
          <option value="">—</option>
          <?php for ($m = 8*60; $m <= 19*60 + 30; $m += 15): $hh = str_pad(intval($m / 60), 2, '0', STR_PAD_LEFT); $mm = str_pad($m % 60, 2, '0', STR_PAD_LEFT); $t = $hh . ':' . $mm; ?>
            <option value="<?= $t ?>"><?= $t ?></option>
          <?php endfor; ?>
        </select>
      </td>
      <td style="padding:6px;vertical-align:middle;">
        <select name="horarios_fin[]" class="input time-24 end-select">
          <option value="">—</option>
          <?php for ($m = 8*60; $m <= 19*60 + 30; $m += 15): $hh = str_pad(intval($m / 60), 2, '0', STR_PAD_LEFT); $mm = str_pad($m % 60, 2, '0', STR_PAD_LEFT); $t = $hh . ':' . $mm; ?>
            <option value="<?= $t ?>"><?= $t ?></option>
          <?php endfor; ?>
        </select>
      </td>
      <td style="padding:6px;vertical-align:middle;">
        <button type="button" class="btn small danger remove-row">Eliminar</button>
      </td>
    </tr>
  </template>

  <div class="row">
    <button class="btn primary" type="submit">Crear horarios</button>
    <a class="btn ghost" href="/doctor-schedules">Cancelar</a>
  </div>

  <noscript>
    <div class="alert warning">JavaScript está deshabilitado. Asegúrate de agregar al menos un día.</div>
  </noscript>
</form>

<script>
  // Cargar sedes asociadas al doctor seleccionado via AJAX
  (function(){
    var doctorSelect = document.getElementById('doctor_id');
    if (!doctorSelect) return;

    window.cachedSedes = window.cachedSedes || [];

    window.populateSedeSelect = function(sel, list){
      if (!sel) return;
      sel.innerHTML = '';
      var defaultOpt = document.createElement('option');
      defaultOpt.value = '';
      defaultOpt.textContent = '— Cualquier sede —';
      sel.appendChild(defaultOpt);
      
      (list || []).forEach(function(s){
        var opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.nombre_sede || s.nombre || ('Sede ' + s.id);
        sel.appendChild(opt);
      });
    };

    window.buildSedesFromDOM = function(){
      var existingSelects = document.querySelectorAll('.sede-select');
      if (existingSelects.length > 0) {
        var firstSelect = existingSelects[0];
        var list = [];
        Array.prototype.forEach.call(firstSelect.options, function(opt){
          if (!opt.value) return;
          list.push({ id: opt.value, nombre_sede: opt.textContent.trim() });
        });
        if (list.length > 0) return list;
      }
      return [];
    };

    async function loadSedes(doctorId){
      if (!doctorId) {
        window.cachedSedes = [];
        updateAllSedeSelects([]);
        return;
      }

      try {
        var res = await fetch('/doctors/' + encodeURIComponent(doctorId) + '/sedes', {
          credentials: 'same-origin'
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        
        var data = await res.json();
        window.cachedSedes = Array.isArray(data) ? data : [];
        updateAllSedeSelects(window.cachedSedes);
        
        if (window.console && window.console.log) {
          console.log('[Sedes] Loaded for doctor', doctorId, ':', window.cachedSedes.length, 'sedes');
        }
      } catch (err) {
        console.warn('[Sedes] Error loading:', err);
        window.cachedSedes = [];
        updateAllSedeSelects([]);
      }
    }

    function updateAllSedeSelects(list){
      var selects = document.querySelectorAll('.sede-select');
      Array.prototype.forEach.call(selects, function(sel){
        var currentValue = sel.value;
        window.populateSedeSelect(sel, list);
        if (currentValue) {
          var hasOption = Array.prototype.some.call(sel.options, function(opt){
            return opt.value === currentValue;
          });
          if (hasOption) sel.value = currentValue;
        }
      });
    }

    if (doctorSelect.value) loadSedes(doctorSelect.value);
    
    doctorSelect.addEventListener('change', function(){
      window.cachedSedes = [];
      var selects = document.querySelectorAll('.sede-select');
      Array.prototype.forEach.call(selects, function(sel){
        sel.innerHTML = '';
        var def = document.createElement('option');
        def.value = '';
        def.textContent = '— Cualquier sede —';
        sel.appendChild(def);
      });
      loadSedes(this.value);
    });
  })();
</script>
<script>
  // Validate HH:MM format before submit (solo valida filas con día seleccionado)
  (function(){
    var form = document.getElementById('patternForm');
    if (!form) return;
    var re = /^([01]\d|2[0-3]):[0-5]\d$/;
    form.addEventListener('submit', function(e){
      var rows = Array.prototype.slice.call(form.querySelectorAll('#daysTbody tr'));
      var errors = [];
      rows.forEach(function(row, idx){
        var daySel = row.querySelector('.day-select');
        var startSel = row.querySelector('.start-select');
        var endSel = row.querySelector('.end-select');
        var day = daySel ? (daySel.value || '').trim() : '';
        // Sólo validar si la fila tiene un día seleccionado
        if (!day) return;

        var s = startSel ? (startSel.value || '').trim() : '';
        var f = endSel ? (endSel.value || '').trim() : '';
        if (s === '' || f === '') {
          errors.push('Fila ' + (idx+1) + ': completa hora inicio y fin.');
          return;
        }
        if (!re.test(s)) errors.push('Fila ' + (idx+1) + ': hora inicio inválida ' + s);
        if (!re.test(f)) errors.push('Fila ' + (idx+1) + ': hora fin inválida ' + f);
        // comprobar orden y duración mínima 15 minutos
        var tS = Date.parse('1970-01-01T' + s + ':00Z');
        var tF = Date.parse('1970-01-01T' + f + ':00Z');
        if (isNaN(tS) || isNaN(tF) || tS >= tF) errors.push('Fila ' + (idx+1) + ': inicio debe ser menor que fin.');
        if (!isNaN(tS) && !isNaN(tF) && ((tF - tS) / 60000) < 15) errors.push('Fila ' + (idx+1) + ': duración mínima 15 minutos.');
      });

      if (errors.length) {
        e.preventDefault();
        alert(errors.join('\n'));
      }
    });
  })();
</script>
<script>
  // Row add/remove management with day uniqueness
  (function(){
    var tbody = document.getElementById('daysTbody');
    var addBtn = document.getElementById('addRowBtn');
    var template = document.getElementById('rowTemplate');
    if (!tbody || !addBtn || !template) return;

    function getSelectedDays() {
      var vals = [];
      Array.prototype.forEach.call(tbody.querySelectorAll('.day-select'), function(s){
        var v = (s.value || '').trim(); 
        if (v) vals.push(v);
      });
      return vals;
    }

    function refreshDayOptions() {
      var selected = getSelectedDays();
      Array.prototype.forEach.call(tbody.querySelectorAll('.day-select'), function(s){
        var own = s.value;
        Array.prototype.forEach.call(s.options, function(opt){
          if (!opt.value) return;
          opt.disabled = false;
        });
      });
      
      Array.prototype.forEach.call(tbody.querySelectorAll('.day-select'), function(s){
        var own = s.value;
        Array.prototype.forEach.call(s.options, function(opt){
          if (!opt.value) return;
          if (opt.value !== own && selected.indexOf(opt.value) !== -1) opt.disabled = true;
        });
      });
    }

    function updateNamesForRow(row) {
      var daySel = row.querySelector('.day-select');
      var startSel = row.querySelector('.start-select');
      var endSel = row.querySelector('.end-select');
      var sedeSel = row.querySelector('.sede-select');
      var key = (daySel.value || '').trim();
      if (startSel) {
        startSel.name = key ? ('horarios_inicio[' + key + ']') : 'horarios_inicio[]';
        startSel.required = !!key;
        startSel.disabled = !key;
      }
      if (endSel) {
        endSel.name = key ? ('horarios_fin[' + key + ']') : 'horarios_fin[]';
        endSel.required = !!key;
        endSel.disabled = !key;
      }
      if (sedeSel) {
        sedeSel.name = key ? ('sede_for_day[' + key + ']') : 'sede_for_day[]';
      }
    }

    function attachRow(row) {
      var daySel = row.querySelector('.day-select');
      var rem = row.querySelector('.remove-row');
      if (daySel) {
        daySel.addEventListener('change', function(){
          updateNamesForRow(row);
          refreshDayOptions();
        });
      }
      if (rem) rem.addEventListener('click', function(){
        row.parentNode.removeChild(row);
        refreshDayOptions();
      });
      updateNamesForRow(row);
    }

    Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function(r){ attachRow(r); });
    refreshDayOptions();

    addBtn.addEventListener('click', function(){
      var frag = template.content.cloneNode(true);
      var tr = frag.querySelector('tr');
      if (!tr) return;
      
      var startSel = tr.querySelector('.start-select');
      var endSel = tr.querySelector('.end-select');
      var sedeSel = tr.querySelector('.sede-select');
      
      if (startSel) startSel.name = 'horarios_inicio[]';
      if (endSel) endSel.name = 'horarios_fin[]';
      if (sedeSel) sedeSel.name = 'sede_for_day[]';
      
      tbody.appendChild(tr);
      
      var listToUse = (window.cachedSedes && window.cachedSedes.length) 
        ? window.cachedSedes 
        : window.buildSedesFromDOM();
      
      window.populateSedeSelect(sedeSel, listToUse);
      
      attachRow(tr);
      refreshDayOptions();
      
      if (window.console && window.console.log) {
        console.log('[Row] Added new row with', listToUse.length, 'sedes:', 
          listToUse.map(function(s){ return s.nombre_sede || s.id; }));
      }
    });
  })();
</script>
