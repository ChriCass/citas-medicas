<?php
// Vista: formulario para CREAR HORARIOS semanales (Step 1: Admin define schedule patterns)
// Espera: $title, $doctors, $sedes, $error (opcional) y $old (opcional)
$role = $_SESSION['user']['rol'] ?? '';
// Lista explícita de opciones de tiempo (cada 15 minutos, 08:00 - 19:30)
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
    <label class="label">Mes</label>
    <div style="display:flex;gap:8px;align-items:center;flex-direction:column;align-items:flex-start;">
      <select name="mes" id="mes" class="input" required>
          <?php
            $months = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
            $now = (int)date('n');

            // Si estamos en diciembre, mostrar todos los meses. En otro caso, mostrar desde el mes actual hasta diciembre.
            if ($now === 12) {
              $available = $months;
            } else {
              $available = [];
              for ($m = $now; $m <= 12; $m++) {
                $available[$m] = $months[$m];
              }
            }

            $oldMonth = !empty($old['mes']) ? (int)$old['mes'] : $now;
            // Si el mes anterior seleccionado no está en la lista permitida, usar el mes actual
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
        ?>
        <small class="hint" style="margin-top:6px;">Año utilizado: <?= ($now === 12 && (int)($old['mes'] ?? $now) !== 12) ? $nextYear : $currentYear ?>. (Si hoy es diciembre y eliges un mes distinto a diciembre, se usará el año siguiente).</small>
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
                  <?php foreach ($timeOptions as $t): ?>
                    <option value="<?= htmlspecialchars($t) ?>" <?= ($oldStart === $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td style="padding:6px;vertical-align:middle;">
                <select name="horarios_fin[<?= htmlspecialchars($dayKey) ?>]" class="input time-24 end-select">
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
          <option value="">— Cualquier sede —</option>
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

    // --- Días usados (horarios) del doctor y filtrado de días disponibles ---
    window.usedDays = window.usedDays || [];
  var DAYS_OPTS = <?= json_encode($daysOpts, JSON_UNESCAPED_UNICODE) ?>;
  var MONTHS = {1:'enero',2:'febrero',3:'marzo',4:'abril',5:'mayo',6:'junio',7:'julio',8:'agosto',9:'septiembre',10:'octubre',11:'noviembre',12:'diciembre'};
    var FULL_DAY_KEYS = Object.keys(DAYS_OPTS); // ['lunes','martes',...]

    window.normalizeStr = function(s) {
      if (s === null || s === undefined) return '';
      return String(s).toLowerCase().trim()
        .replace(/á/g, 'a').replace(/é/g, 'e').replace(/í/g, 'i')
        .replace(/ó/g, 'o').replace(/ú/g, 'u').replace(/ü/g, 'u')
        .replace(/ñ/g, 'n');
    }

    window.mapNumberToKey = function(n) {
      var m = parseInt(n, 10);
      if (isNaN(m)) return null;
      // try 1=lunes..7=domingo
      var convA = {1:'lunes',2:'martes',3:'miércoles',4:'jueves',5:'viernes',6:'sábado',7:'domingo'};
      // try 0=domingo,1=lunes..6=sábado
      var convB = {0:'domingo',1:'lunes',2:'martes',3:'miércoles',4:'jueves',5:'viernes',6:'sábado'};
      if (convA[m] && FULL_DAY_KEYS.indexOf(convA[m]) !== -1) return convA[m];
      if (convB[m] && FULL_DAY_KEYS.indexOf(convB[m]) !== -1) return convB[m];
      return null;
    }

    window.normalizeDayValue = function(v) {
      if (v === null || v === undefined) return null;
      if (!isNaN(parseInt(v,10)) && String(v).trim() !== '') {
        var byNum = mapNumberToKey(v);
        if (byNum) return byNum;
      }
      var s = normalizeStr(v);
      for (var i=0;i<FULL_DAY_KEYS.length;i++){
        var key = FULL_DAY_KEYS[i];
        if (normalizeStr(key) === s) return key;
        if (normalizeStr(DAYS_OPTS[key]) === s) return key;
      }
      return null;
    }

    window.loadUsedDays = async function(doctorId){
      if (!doctorId) { window.usedDays = []; window.updateAllDaySelects(); return; }
      // Get selected month from the #mes select and convert to spanish month name
      var mesSel = document.getElementById('mes');
      var mesVal = mesSel ? mesSel.value : null;
      var monthName = null;
      if (mesVal) {
        var mi = parseInt(mesVal, 10);
        if (!isNaN(mi) && MONTHS[mi]) monthName = MONTHS[mi];
        else monthName = String(mesVal).toLowerCase();
      } else {
        // default to current month name
        var now = new Date(); var cm = now.getMonth() + 1; monthName = MONTHS[cm];
      }

      try {
        var url = '/doctors/' + encodeURIComponent(doctorId) + '/' + encodeURIComponent(monthName || '') + '/used-days';
        var res = await fetch(url, { credentials: 'same-origin' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        var data = await res.json();
        var list = Array.isArray(data) ? data : [];
        var norm = [];
        list.forEach(function(it){ var k = normalizeDayValue(it); if (k && norm.indexOf(k) === -1) norm.push(k); });
        window.usedDays = norm;
      } catch (err) {
        console.warn('[UsedDays] Error loading:', err);
        window.usedDays = [];
      }
      window.updateAllDaySelects();
    }

    window.getSelectedDays = function() {
      var vals = [];
      Array.prototype.forEach.call(document.querySelectorAll('.day-select'), function(s){ var v = (s.value||'').trim(); if (v) vals.push(v); });
      return vals;
    }

    window.populateDaySelectWithAvailableDays = function(sel) {
      if (!sel) return;
      var current = sel.value || '';
      // compute available = FULL_DAY_KEYS - usedDays
      var available = FULL_DAY_KEYS.filter(function(k){ return (window.usedDays||[]).indexOf(k) === -1; });
      // Also exclude days already selected in other rows (to keep uniqueness)
      var selectedElsewhere = window.getSelectedDays();
      // Build options
      sel.innerHTML = '';
      var def = document.createElement('option'); def.value=''; def.textContent='— Selecciona día —'; sel.appendChild(def);
      available.forEach(function(k){
        // skip if selected elsewhere and not the current value
        if (selectedElsewhere.indexOf(k) !== -1 && k !== current) return;
        var opt = document.createElement('option'); opt.value = k; opt.textContent = DAYS_OPTS[k]; if (k === current) opt.selected = true; sel.appendChild(opt);
      });
      // If current value is not in options (maybe because it's now used), keep it as an option so user's selection isn't lost
      if (current) {
        var found = Array.prototype.some.call(sel.options, function(o){ return o.value === current; });
        if (!found) {
          var keep = document.createElement('option'); keep.value = current; keep.textContent = DAYS_OPTS[current] || current; keep.selected = true; sel.appendChild(keep);
        }
      }
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
      loadUsedDays(doctorSelect.value);
    }

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
      loadUsedDays(this.value);
    });
    // When the selected month changes, reload used days for the current doctor
    var mesSelect = document.getElementById('mes');
    if (mesSelect) {
      mesSelect.addEventListener('change', function(){
        // Al cambiar el mes, limpiar todas las filas previas del cuerpo de la tabla
        try {
          var tbody = document.getElementById('daysTbody');
          if (tbody) {
            // Remove all child rows
            while (tbody.firstChild) tbody.removeChild(tbody.firstChild);
          }
        } catch (err) {
          console.warn('Error limpiando daysTbody:', err);
        }

        // Recargar días usados para el doctor/mes actual
        loadUsedDays(doctorSelect.value);
      });
    }
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
          if (window.updateAllDaySelects) window.updateAllDaySelects(); else refreshDayOptions();
        });
      }
      if (rem) rem.addEventListener('click', function(){
        row.parentNode.removeChild(row);
        if (window.updateAllDaySelects) window.updateAllDaySelects(); else refreshDayOptions();
      });
      updateNamesForRow(row);
    }

    // populate existing rows with filtered day options then attach handlers
    Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function(r){
      var ds = r.querySelector('.day-select');
      if (ds && window.populateDaySelectWithAvailableDays) window.populateDaySelectWithAvailableDays(ds);
      attachRow(r);
    });
    try { if (window.updateAllDaySelects) window.updateAllDaySelects(); else refreshDayOptions(); } catch(e) {}

    addBtn.addEventListener('click', function(){
      // Clone template and append fragment to tbody (more robust across browsers)
      var clone = template.content.cloneNode(true);
      // Append the fragment (will insert the <tr> into tbody)
      tbody.appendChild(clone);

      // The newly appended row should be the last tr in tbody
      var newRow = tbody.querySelector('tr:last-child');
      if (!newRow) return;

      // Ensure names on selects are set
      var startSel = newRow.querySelector('.start-select');
      var endSel = newRow.querySelector('.end-select');
      var sedeSel = newRow.querySelector('.sede-select');
      if (startSel) startSel.name = 'horarios_inicio[]';
      if (endSel) endSel.name = 'horarios_fin[]';
      if (sedeSel) sedeSel.name = 'sede_for_day[]';

  var listToUse = (window.cachedSedes && window.cachedSedes.length) ? window.cachedSedes : window.buildSedesFromDOM();
  if (sedeSel) window.populateSedeSelect(sedeSel, listToUse);

  // populate day-select of the new row using filtered available days
  var daySel = newRow.querySelector('.day-select');
  if (daySel && window.populateDaySelectWithAvailableDays) window.populateDaySelectWithAvailableDays(daySel);

  attachRow(newRow);
  // rebuild day selects / options
  try { if (window.updateAllDaySelects) window.updateAllDaySelects(); else refreshDayOptions(); } catch (e) { console.warn('updateAllDaySelects/refreshDayOptions error', e); }

      if (window.console && window.console.log) {
        console.log('[Row] Added new row with', listToUse.length, 'sedes:', listToUse.map(function(s){ return s.nombre_sede || s.id; }));
      }
    });
  })();
</script>
