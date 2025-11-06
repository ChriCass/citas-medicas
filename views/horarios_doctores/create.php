<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
$role = $_SESSION['user']['rol'] ?? '';
$timeOptions = [
  '08:00','08:15','08:30','08:45','09:00','09:15','09:30','09:45',
  '10:00','10:15','10:30','10:45','11:00','11:15','11:30','11:45',
  '12:00','12:15','12:30','12:45','13:00','13:15','13:30','13:45',
  '14:00','14:15','14:30','14:45','15:00','15:15','15:30','15:45',
  '16:00','16:15','16:30','16:45','17:00','17:15','17:30','17:45',
  '18:00','18:15','18:30','18:45','19:00','19:15','19:30'
];
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
  <div id="clientErrors" class="alert error mt-2" style="display:none;"></div>

  <div class="row">
    <label class="label" for="doctor_id">Doctor <span aria-hidden="true">*</span></label>
    <select name="doctor_id" id="doctor_id" class="input" required>
      <option value="">— Selecciona —</option>
      <?php foreach (($doctors ?? []) as $d): ?>
        <?php
          // Support both legacy arrays returned by Doctor::getAll() and Eloquent models
          if (is_array($d)) {
            $did = (int)($d['id'] ?? 0);
            $docNombre = trim((string)($d['user_nombre'] ?? $d['nombre'] ?? '') . ' ' . (string)($d['user_apellido'] ?? $d['apellido'] ?? ''));
            $docEmail = (string)($d['email'] ?? $d['user_email'] ?? '');
          } else {
            $did = (int)($d->id ?? 0);
            $docNombre = trim((string)($d->user->nombre ?? $d->nombre ?? '') . ' ' . (string)($d->user->apellido ?? $d->apellido ?? ''));
            $docEmail = (string)($d->user->email ?? $d->email ?? '');
          }
        ?>
        <option value="<?= $did ?>" <?= (!empty($old['doctor_id']) && (int)$old['doctor_id']=== $did)? 'selected':'' ?>>
          <?= htmlspecialchars($docNombre !== '' ? $docNombre : ('Doctor ' . $did)) ?><?= $docEmail !== '' ? ' — ' . htmlspecialchars($docEmail) : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row compact">
    <label class="label">Mes</label>
    <div style="display:flex;gap:8px;align-items:center;flex-direction:column;align-items:flex-start;">
      <select name="mes" id="mes" class="input" required>
          <?php
            $months = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
            $now = (int)date('n');
            if ($now === 12) {
              $available = $months;
            } else {
              $available = [];
              for ($m = $now; $m <= 12; $m++) {
                $available[$m] = $months[$m];
              }
            }

            $oldMonth = !empty($old['mes']) ? (int)$old['mes'] : $now;
            if (!isset($available[$oldMonth])) {
              $oldMonth = $now;
            }

            foreach ($available as $num => $name):
          ?>
            <option value="<?= $num ?>" <?= $oldMonth===$num? 'selected':'' ?>><?= htmlspecialchars($name) ?></option>
          <?php endforeach; ?>
        </select>
        <?php
          $now = (int)date('n');
          $currentYear = (int)date('Y');
          $nextYear = $currentYear + 1;
          $usedYear = ($now === 12 && (int)($old['mes'] ?? $now) !== 12) ? $nextYear : $currentYear;
        ?>
  <input type="hidden" name="anio" id="anio" value="<?= htmlspecialchars((string)$usedYear) ?>">
        <small id="anioHint" class="hint" style="margin-top:6px;">Año utilizado: <?= $usedYear ?>. (Si hoy es diciembre y eliges un mes distinto a diciembre, se usará el año siguiente).</small>
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
                <select name="sede_for_day[]" class="input sede-select" required>
                  <option value="">— Selecciona Sede —</option>
                  <?php foreach (($sedes ?? []) as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= ((string)$oldSede === (string)$s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nombre_sede'] ?? $s['nombre'] ?? '') ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td style="padding:6px;vertical-align:middle;">
                <select name="horarios_inicio[]" class="input time-24 start-select">
                  <option value="">—</option>
                  <?php foreach ($timeOptions as $t): ?>
                    <option value="<?= htmlspecialchars($t) ?>" <?= ($oldStart === $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td style="padding:6px;vertical-align:middle;">
                <select name="horarios_fin[]" class="input time-24 end-select">
                  <option value="">—</option>
                  <?php foreach ($timeOptions as $t): ?>
                    <option value="<?= htmlspecialchars($t) ?>" <?= ($oldEnd === $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                  <?php endforeach; ?>
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
          <option value="">—</option>
        </select>
      </td>
      <td style="padding:6px;vertical-align:middle;">
        <select name="horarios_inicio[]" class="input time-24 start-select">
          <option value="">—</option>
          <?php foreach ($timeOptions as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
          <?php endforeach; ?>
        </select>
      </td>
      <td style="padding:6px;vertical-align:middle;">
        <select name="horarios_fin[]" class="input time-24 end-select">
          <option value="">—</option>
          <?php foreach ($timeOptions as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
          <?php endforeach; ?>
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
      defaultOpt.textContent = '— Selecciona Sede —';
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

    var DAYS_OPTS = <?= json_encode($daysOpts, JSON_UNESCAPED_UNICODE) ?>;
    var FULL_DAY_KEYS = Object.keys(DAYS_OPTS); // ['lunes','martes',...]

    window.getSelectedDays = function() {
      var vals = [];
      Array.prototype.forEach.call(document.querySelectorAll('.day-select'), function(s){ var v = (s.value||'').trim(); if (v) vals.push(v); });
      return vals;
    }

    window.populateDaySelectWithAvailableDays = function(sel) {
      if (!sel) return;
      var current = sel.value || '';      
      sel.innerHTML = '';
      var def = document.createElement('option'); def.value=''; def.textContent='— Selecciona día —'; sel.appendChild(def);
      FULL_DAY_KEYS.forEach(function(k){
        var opt = document.createElement('option'); 
        opt.value = k; 
        opt.textContent = DAYS_OPTS[k]; 
        if (k === current) opt.selected = true; 
        sel.appendChild(opt);
      });
    }

    window.updateAllDaySelects = function(){
      Array.prototype.forEach.call(document.querySelectorAll('.day-select'), function(s){ window.populateDaySelectWithAvailableDays(s); });
    }


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

    if (doctorSelect.value) {
      loadSedes(doctorSelect.value);
    }

    doctorSelect.addEventListener('change', function(){
      window.cachedSedes = [];
      var selects = document.querySelectorAll('.sede-select');
      Array.prototype.forEach.call(selects, function(sel){
        sel.innerHTML = '';
        var def = document.createElement('option');
        def.value = '';
        def.textContent = '— Selecciona Sede —';
        sel.appendChild(def);
      });
      loadSedes(this.value);
    });
    var mesSelect = document.getElementById('mes');
    if (mesSelect) {
      mesSelect.addEventListener('change', function(){
        try {
          var tbody = document.getElementById('daysTbody');
          if (tbody) {
            while (tbody.firstChild) tbody.removeChild(tbody.firstChild);
          }
        } catch (err) {
          console.warn('Error limpiando daysTbody:', err);
        }

        try {
          var now = new Date();
          var curMonth = now.getMonth() + 1;
          var curYear = now.getFullYear();
          var nextYear = curYear + 1;
          var chosen = parseInt(this.value, 10);
          var yearToUse = curYear;
          if (curMonth === 12 && !isNaN(chosen) && chosen !== 12) yearToUse = nextYear;
          var yEl = document.getElementById('anio'); if (yEl) yEl.value = String(yearToUse);
          var hint = document.getElementById('anioHint'); if (hint) hint.textContent = 'Año utilizado: ' + yearToUse + '. (Si hoy es diciembre y eliges un mes distinto a diciembre, se usará el año siguiente).';
        } catch (e) {}

      });
    }
  })();
</script>
<script>
  (function(){
    var form = document.getElementById('patternForm');
    if (!form) return;
    var re = /^([01]\d|2[0-3]):[0-5]\d$/;
    form.addEventListener('input', function(){
      var ce = document.getElementById('clientErrors'); if (ce) ce.style.display = 'none';
    });
    form.addEventListener('submit', function(e){
      var rows = Array.prototype.slice.call(form.querySelectorAll('#daysTbody tr'));
      var errors = [];
      rows.forEach(function(row, idx){
        var daySel = row.querySelector('.day-select');
        var startSel = row.querySelector('.start-select');
        var endSel = row.querySelector('.end-select');
        var day = daySel ? (daySel.value || '').trim() : '';
        if (!day) return;

        var s = startSel ? (startSel.value || '').trim() : '';
        var f = endSel ? (endSel.value || '').trim() : '';
        if (s === '' || f === '') {
          errors.push('Fila ' + (idx+1) + ': completa hora inicio y fin.');
          return;
        }
        if (!re.test(s)) errors.push('Fila ' + (idx+1) + ': hora inicio inválida ' + s);
        if (!re.test(f)) errors.push('Fila ' + (idx+1) + ': hora fin inválida ' + f);
        var tS = Date.parse('1970-01-01T' + s + ':00Z');
        var tF = Date.parse('1970-01-01T' + f + ':00Z');
        if (isNaN(tS) || isNaN(tF) || tS >= tF) errors.push('Fila ' + (idx+1) + ': inicio debe ser menor que fin.');
        if (!isNaN(tS) && !isNaN(tF) && ((tF - tS) / 60000) < 15) errors.push('Fila ' + (idx+1) + ': duración mínima 15 minutos.');
      });

      var clientErrors = document.getElementById('clientErrors');
      if (errors.length) {
        e.preventDefault();
        if (clientErrors) {
          var html = '<ul style="margin:0;padding-left:20px;">' + errors.map(function(s){ return '<li>' + s + '</li>'; }).join('') + '</ul>';
          clientErrors.innerHTML = html;
          clientErrors.style.display = 'block';
          try { clientErrors.scrollIntoView({behavior:'smooth', block:'center'}); } catch(e) {}
        } else {
          alert(errors.join('\n'));
        }
      } else {
        if (clientErrors) clientErrors.style.display = 'none';
      }
    });
  })();
</script>
<script>
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

    function updateNamesForRow(row) {
      var daySel = row.querySelector('.day-select');
      var startSel = row.querySelector('.start-select');
      var endSel = row.querySelector('.end-select');
      var sedeSel = row.querySelector('.sede-select');
      if (startSel) {
        startSel.name = 'horarios_inicio[]';
        startSel.required = !!(daySel && (daySel.value || '').trim());
        startSel.disabled = !(daySel && (daySel.value || '').trim());
      }
      if (endSel) {
        endSel.name = 'horarios_fin[]';
        endSel.required = !!(daySel && (daySel.value || '').trim());
        endSel.disabled = !(daySel && (daySel.value || '').trim());
      }
      if (sedeSel) {
        sedeSel.name = 'sede_for_day[]';
      }
    }

    function attachRow(row) {
      var daySel = row.querySelector('.day-select');
      var rem = row.querySelector('.remove-row');
      if (daySel) {
        daySel.addEventListener('change', function(){
          updateNamesForRow(row);
          if (window.updateAllDaySelects) window.updateAllDaySelects(); else refreshDayOptions();
        });
      }
      if (rem) rem.addEventListener('click', function(){
        row.parentNode.removeChild(row);
        if (window.updateAllDaySelects) window.updateAllDaySelects(); else refreshDayOptions();
      });
      updateNamesForRow(row);
    }

    Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function(r){
      var ds = r.querySelector('.day-select');
      if (ds && window.populateDaySelectWithAvailableDays) window.populateDaySelectWithAvailableDays(ds);
      attachRow(r);
    });
    try { if (window.updateAllDaySelects) window.updateAllDaySelects(); else refreshDayOptions(); } catch(e) {}

    addBtn.addEventListener('click', function(){
      var clone = template.content.cloneNode(true);
      tbody.appendChild(clone);

      var newRow = tbody.querySelector('tr:last-child');
      if (!newRow) return;

      var startSel = newRow.querySelector('.start-select');
      var endSel = newRow.querySelector('.end-select');
      var sedeSel = newRow.querySelector('.sede-select');
      if (startSel) startSel.name = 'horarios_inicio[]';
      if (endSel) endSel.name = 'horarios_fin[]';
      if (sedeSel) sedeSel.name = 'sede_for_day[]';

  var listToUse = (window.cachedSedes && window.cachedSedes.length) ? window.cachedSedes : window.buildSedesFromDOM();
  if (sedeSel) window.populateSedeSelect(sedeSel, listToUse);
  
  var daySel = newRow.querySelector('.day-select');
  if (daySel && window.populateDaySelectWithAvailableDays) window.populateDaySelectWithAvailableDays(daySel);

  attachRow(newRow);
  try { if (window.updateAllDaySelects) window.updateAllDaySelects(); else refreshDayOptions(); } catch (e) { console.warn('updateAllDaySelects/refreshDayOptions error', e); }

      if (window.console && window.console.log) {
        console.log('[Row] Added new row with', listToUse.length, 'sedes:', listToUse.map(function(s){ return s.nombre_sede || s.id; }));
      }
    });
  })();
</script>
