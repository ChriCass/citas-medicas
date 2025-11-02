<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
// Vista: formulario para EDITAR HORARIOS semanales 
// Espera: $title, $pattern, $doctors, $sedes, $horarios, $error (opcional) y $old (opcional)
$role = $_SESSION['user']['rol'] ?? '';
$pattern = $pattern ?? null;
$horarios = $horarios ?? [];

// Lista explícita de opciones de tiempo (cada 15 minutos, 08:00 - 19:30)
$timeOptions = [
  '08:00','08:15','08:30','08:45','09:00','09:15','09:30','09:45',
  '10:00','10:15','10:30','10:45','11:00','11:15','11:30','11:45',
  '12:00','12:15','12:30','12:45','13:00','13:15','13:30','13:45',
  '14:00','14:15','14:30','14:45','15:00','15:15','15:30','15:45',
  '16:00','16:15','16:30','16:45','17:00','17:15','17:30','17:45',
  '18:00','18:15','18:30','18:45','19:00','19:15','19:30'
];

// Mapear números de mes a nombres
$monthsMap = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',
              7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];

// Helper: formatea una posible instancia de Carbon/DateTime o string a HH:MM
if (!function_exists('\_fmtTime')) {
  function _fmtTime($val) {
    if ($val === null || $val === '') return '';
    if (is_object($val) && method_exists($val, 'format')) {
      try { return $val->format('H:i'); } catch (\Throwable $e) { }
    }
    $s = (string)$val;
    $ts = @strtotime($s);
    if ($ts === false) return trim($s);
    return date('H:i', $ts);
  }
}

// Helper: formatea fecha a Y-m-d si viene como Carbon/DateTime
if (!function_exists('\_fmtDate')) {
  function _fmtDate($val) {
    if ($val === null || $val === '') return '';
    if (is_object($val) && method_exists($val, 'format')) {
      try { return $val->format('Y-m-d'); } catch (\Throwable $e) { }
    }
    return (string)$val;
  }
}

// Si hay un patrón existente, extraer sus datos (pero respeta variables que el controlador ya haya pasado)
if (!isset($doctorId)) $doctorId = $pattern?->doctor_id ?? ($old['doctor_id'] ?? '');
if (!isset($currentMes)) $currentMes = $pattern?->mes ?? null;
if (!isset($currentAnio)) $currentAnio = $pattern?->anio ?? null;
if (!isset($selectedSede)) $selectedSede = $pattern?->sede_id ?? ($old['sede_id'] ?? '');

// Determinar el mes y año actual para el formulario (no sobrescribir si vienen del controlador)
if (!isset($selectedMes)) $selectedMes = null;
if (!isset($selectedAnio)) $selectedAnio = null;

// Priorizar valores en este orden: POST antiguo ($old), luego valor provisto por el controlador
// ($selectedMes / $selectedAnio), y finalmente el mes/año del patrón ($currentMes/$currentAnio).
if (!empty($old['mes'])) {
  $selectedMes = (int)$old['mes'];
} elseif (isset($selectedMes) && is_numeric($selectedMes) && (int)$selectedMes >= 1 && (int)$selectedMes <= 12) {
  // El controlador ya envió un mes numérico (p. ej. desde la ruta). Mantenerlo.
  $selectedMes = (int)$selectedMes;
} elseif ($currentMes) {
  // Aceptar tanto nombres de mes ("Diciembre") como valores numéricos ("12" o 12)
  if (is_numeric($currentMes)) {
    $selectedMes = (int)$currentMes;
  } else {
    $found = array_search(mb_strtolower((string)$currentMes), array_map('mb_strtolower', $monthsMap));
    $selectedMes = ($found === false) ? null : (int)$found;
  }
}

if (!empty($old['anio'])) {
    $selectedAnio = (int)$old['anio'];
} elseif (isset($selectedAnio) && is_numeric($selectedAnio) && (int)$selectedAnio >= 1970) {
    $selectedAnio = (int)$selectedAnio;
} elseif ($currentAnio) {
    $selectedAnio = (int)$currentAnio;
}

// Si no hay mes/año definidos, usar valores por defecto
if (!$selectedMes) $selectedMes = (int)date('n');
if (!$selectedAnio) $selectedAnio = (int)date('Y');

?>

<!-- Controles superiores: Doctor, Sede y Mes -->
<div style="display:flex;gap:12px;align-items:flex-end;margin-bottom:12px;flex-wrap:wrap;">
  <div class="row compact" style="min-width:240px;">
    <label class="label">Doctor</label>
    <select id="doctor_top" class="input" style="width:100%;">
      <option value="">-- Seleccionar doctor --</option>
      <?php foreach (($doctors ?? []) as $d): ?>
        <?php $did = (int)($d->id ?? 0); ?>
        <option value="<?= $did ?>" <?= ((string)$did === (string)$doctorId) ? 'selected' : '' ?>>
          <?= htmlspecialchars(($d->user->nombre ?? '') . ' ' . ($d->user->apellido ?? '') . ' — ' . ($d->user->email ?? '')) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

</div>

<h3 style="margin: 24px 0 16px 0; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 8px;">Horarios del doctor / sede</h3>

  <!-- Tabla de horarios existentes -->
  <div class="row">
    <div class="table-responsive">
      <table class="table" style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="background: #f8f9fa;">
            <th style="text-align:left;padding:12px;font-weight:600;border:1px solid #dee2e6;">Mes</th>
            <th style="text-align:left;padding:12px;font-weight:600;border:1px solid #dee2e6;">Día de la semana</th>
            <th style="text-align:left;padding:12px;font-weight:600;border:1px solid #dee2e6;">Sede</th>
            <th style="text-align:left;padding:12px;font-weight:600;border:1px solid #dee2e6;">Hora inicio (24h)</th>
            <th style="text-align:left;padding:12px;font-weight:600;border:1px solid #dee2e6;">Hora fin (24h)</th>
            <th style="text-align:left;padding:12px;font-weight:600;border:1px solid #dee2e6;">Observaciones</th>
            <th style="text-align:left;padding:12px;font-weight:600;border:1px solid #dee2e6;">Acciones</th>
          </tr>
        </thead>
        <tbody id="horariosTbody">
          <?php
            $daysOpts = [
              'lunes' => 'Lunes',
              'martes' => 'Martes', 
              'miércoles' => 'Miércoles',
              'jueves' => 'Jueves',
              'viernes' => 'Viernes',
              'sábado' => 'Sábado'
            ];

            // Filtrar solo los registros que vienen de horarios_medicos (excluir `calendario`)
            $patternHorarios = [];
            if (!empty($horarios) && is_array($horarios)) {
              foreach ($horarios as $hh) {
                if (empty($hh->is_calendar)) $patternHorarios[] = $hh;
              }
            }

            if (!empty($patternHorarios)):
              // cache for doctor->sedes to avoid repeated queries
              $doctorSedesCache = [];
              foreach ($patternHorarios as $h):
                  $horarioId = (int)$h->id;
                  $dayKey = mb_strtolower(trim((string)$h->dia_semana));
                  $startTime = _fmtTime($h->hora_inicio ?? null);
                  $endTime = _fmtTime($h->hora_fin ?? null);
                  $sedeId = $h->sede_id ?? null;
                  $observaciones = $h->observaciones ?? '';
                  // Determine mes number for this pattern: prefer pattern->mes, fall back to selectedMes
                  $rowMes = null;
                  if (!empty($h->mes)) {
                    // $monthsMap defined at top maps 1=>'enero'...; try to find numeric index
                    $found = array_search(mb_strtolower((string)$h->mes), array_map('mb_strtolower', $monthsMap));
                    $rowMes = ($found === false) ? null : (int)$found;
                  }
                  if (!$rowMes) $rowMes = (int)($selectedMes ?? date('n'));
          ?>
              <?php $blocked = !empty($h->has_reserved_slots); $disabledAttr = $blocked ? 'disabled' : ''; ?>
            <tr class="<?= $blocked ? 'blocked-row' : '' ?>" style="border:1px solid #dee2e6;">
              <form method="POST" action="/doctor-schedules/<?= $horarioId ?>/update" style="display: contents;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                <input type="hidden" name="doctor_id" value="<?= htmlspecialchars((string)$doctorId) ?>">
                <input type="hidden" name="mes" value="<?= htmlspecialchars((string)$selectedMes) ?>">
                <input type="hidden" name="anio" value="<?= htmlspecialchars((string)$selectedAnio) ?>">
                <input type="hidden" name="horarios[<?= $horarioId ?>][activo]" value="1">
                
                <td style="padding:8px;vertical-align:middle;border:1px solid #dee2e6;">
                  <?php
                    $monthsDisplay = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
                    // Only allow current month and following months
                    $nowMonth = (int)date('n');
                    $allowedMonths = [];
                    if ($nowMonth === 12) {
                      $allowedMonths = $monthsDisplay;
                    } else {
                      for ($mm = $nowMonth; $mm <= 12; $mm++) { $allowedMonths[$mm] = $monthsDisplay[$mm]; }
                    }
                  ?>
                  <select name="horarios[<?= $horarioId ?>][mes]" class="input mes-select" style="width:100%;" <?= $disabledAttr ?> >
                    <option value="">— Mes —</option>
                    <?php foreach ($allowedMonths as $mnum => $mname): ?>
                      <option value="<?= $mnum ?>" <?= ((int)$rowMes === (int)$mnum) ? 'selected' : '' ?>><?= htmlspecialchars($mname) ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td style="padding:8px;vertical-align:middle;border:1px solid #dee2e6;">
                  <select name="horarios[<?= $horarioId ?>][dia_semana]" class="input day-select" required style="width:100%;" <?= $disabledAttr ?> >
                    <option value="">— Selecciona día —</option>
                    <?php foreach ($daysOpts as $optKey => $optLabel): ?>
                      <option value="<?= htmlspecialchars($optKey) ?>" <?= $optKey === $dayKey ? 'selected' : '' ?>><?= htmlspecialchars($optLabel) ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td style="padding:8px;vertical-align:middle;border:1px solid #dee2e6;">
                  <select name="horarios[<?= $horarioId ?>][sede_id]" class="input sede-select" style="width:100%;" <?= $disabledAttr ?> >
                    <option value="">— Cualquier sede —</option>
                    <?php
                      $docIdForSedes = (int)($h->doctor_id ?? $doctorId);
                      if (!isset($doctorSedesCache[$docIdForSedes])) {
                        try {
                          $dobj = \App\Models\Doctor::where('id', $docIdForSedes)->with('sedes')->first();
                          $doctorSedesCache[$docIdForSedes] = $dobj?->sedes ?? [];
                        } catch (\Throwable $_e) {
                          $doctorSedesCache[$docIdForSedes] = [];
                        }
                      }
                      $assignedSedes = $doctorSedesCache[$docIdForSedes] ?? [];
                    ?>
                    <?php foreach ($assignedSedes as $s): ?>
                      <option value="<?= (int)$s->id ?>" <?= ((string)$sedeId === (string)$s->id) ? 'selected' : '' ?>><?= htmlspecialchars($s->nombre_sede ?? $s->nombre ?? '') ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td style="padding:8px;vertical-align:middle;border:1px solid #dee2e6;">
                  <select name="horarios[<?= $horarioId ?>][hora_inicio]" class="input time-24 start-select" required style="width:100%;" <?= $disabledAttr ?> >
                    <option value="">—</option>
                    <?php foreach ($timeOptions as $t): ?>
                      <option value="<?= htmlspecialchars($t) ?>" <?= $t === $startTime ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td style="padding:8px;vertical-align:middle;border:1px solid #dee2e6;">
                  <select name="horarios[<?= $horarioId ?>][hora_fin]" class="input time-24 end-select" required style="width:100%;" <?= $disabledAttr ?> >
                    <option value="">—</option>
                    <?php foreach ($timeOptions as $t): ?>
                      <option value="<?= htmlspecialchars($t) ?>" <?= $t === $endTime ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td style="padding:8px;vertical-align:middle;border:1px solid #dee2e6;">
                  <input type="text" name="horarios[<?= $horarioId ?>][observaciones]" 
                         class="input" value="<?= htmlspecialchars($observaciones) ?>" 
                         placeholder="modificado 333" style="width:100%;" <?= $disabledAttr ?> >
                </td>
                <td style="padding:8px;vertical-align:middle;text-align:center;border:1px solid #dee2e6;">
                  <?php if ($blocked): ?>
                    <button type="button" class="btn" disabled style="background:#6c757d;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:not-allowed;" title="Este horario tiene al menos una cita y no puede editarse">
                      🔒 Bloqueado
                    </button>
                  <?php else: ?>
                      <button type="button" class="btn primary ajax-save" data-action="/doctor-schedules/<?= $horarioId ?>/update" style="background:#28a745;color:#fff;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;">
                        Guardar
                      </button>
                  <?php endif; ?>
                </td>
              </form>
            </tr>
          <?php
              endforeach;
            else:
          ?>
            <tr>
              <td colspan="7" style="padding:16px;text-align:center;color:#6c757d;border:1px solid #dee2e6;">
                No hay horarios definidos para este doctor/sede/mes.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>


<div class="row" style="margin-top:20px;">
  <a class="btn ghost" href="/doctor-schedules" style="background:#6c757d;color:#fff;border:none;padding:10px 20px;border-radius:4px;text-decoration:none;display:inline-block;">Volver</a>
</div>

<noscript>
  <div class="alert warning">JavaScript está deshabilitado. La funcionalidad puede estar limitada.</div>
</noscript>

<script>
  // Validación de formularios individuales antes de envío
  (function(){
    var forms = document.querySelectorAll('#horariosTbody form');
    var re = /^([01]\d|2[0-3]):[0-5]\d$/;
    
    // Ocultar errores cuando el usuario cambie cualquier input
    document.addEventListener('input', function(e){
      if (e.target.closest('#horariosTbody')) {
        var ce = document.getElementById('clientErrors'); 
        if (ce) ce.style.display = 'none';
      }
    });
    
    Array.prototype.forEach.call(forms, function(form){
      form.addEventListener('submit', function(e){
        var errors = [];
        var daySel = form.querySelector('.day-select');
        var startSel = form.querySelector('.start-select');
        var endSel = form.querySelector('.end-select');
        
        if (!daySel || !startSel || !endSel) return;
        
        var day = daySel.value || '';
        var s = startSel.value || '';
        var f = endSel.value || '';
        
        // Validar que todos los campos estén completos
        if (!day) {
          errors.push('Selecciona un día de la semana.');
        }
        
        if (s === '' || f === '') {
          errors.push('Completa hora inicio y fin.');
        } else {
          if (!re.test(s)) errors.push('Hora inicio inválida: ' + s);
          if (!re.test(f)) errors.push('Hora fin inválida: ' + f);
          
          // Comprobar orden y duración mínima 15 minutos
          var tS = Date.parse('1970-01-01T' + s + ':00Z');
          var tF = Date.parse('1970-01-01T' + f + ':00Z');
          if (isNaN(tS) || isNaN(tF) || tS >= tF) {
            errors.push('La hora inicio debe ser menor que la hora fin.');
          }
          if (!isNaN(tS) && !isNaN(tF) && ((tF - tS) / 60000) < 15) {
            errors.push('La duración mínima es de 15 minutos.');
          }
        }

        var clientErrors = document.getElementById('clientErrors');
        if (errors.length) {
          e.preventDefault();
          if (clientErrors) {
            var html = '<ul style="margin:0;padding-left:20px;">' + 
                      errors.map(function(s){ return '<li>' + s + '</li>'; }).join('') + 
                      '</ul>';
            clientErrors.innerHTML = html;
            clientErrors.style.display = 'block';
            try { 
              clientErrors.scrollIntoView({behavior:'smooth', block:'center'}); 
            } catch(e) {}
          } else {
            alert(errors.join('\n'));
          }
        } else {
          if (clientErrors) clientErrors.style.display = 'none';
        }
      });
    });
  })();
</script>

<script>
  // Enviar formularios de fila por AJAX y quedarse en la misma pantalla
  // Mejorado: delegación + fallback por botón + console.logging para depuración
  (function(){
    var tbody = document.getElementById('horariosTbody');
    if (!tbody) return;

    function stripTags(html){ return String(html || '').replace(/<[^>]*>/g,''); }

    function showRowMessage(form, msg, type) {
      var cell = form.querySelector('td:last-child');
      if (!cell) return;
      var existing = cell.querySelector('.row-save-notice');
      if (!existing) {
        existing = document.createElement('div');
        existing.className = 'row-save-notice';
        existing.style.marginTop = '6px';
        existing.style.padding = '6px';
        existing.style.borderRadius = '4px';
        existing.style.fontSize = '13px';
        cell.appendChild(existing);
      }
      if (type === 'success') {
        existing.style.background = '#d4edda';
        existing.style.color = '#155724';
        existing.style.border = '1px solid #c3e6cb';
      } else {
        existing.style.background = '#f8d7da';
        existing.style.color = '#721c24';
        existing.style.border = '1px solid #f5c6cb';
      }
      existing.textContent = msg;
      try { existing.scrollIntoView({behavior:'smooth', block:'center'}); } catch(e){}
    }

    function handleSaveClick(btn){
      try {
        if (!btn || btn.disabled) return;
        var form = btn.closest('form');
        // Puede que el navegador haya reubicado el <form> (display:contents) y no sea ancestro.
        // Buscamos el <tr> y tomamos inputs desde allí como fallback.
        var row = btn.closest('tr');
        if (!form || !(form instanceof HTMLFormElement)) {
          // no hay formulario ancestro; usaremos el data-action del botón y construiremos FormData desde los inputs del <tr>
          if (!row) {
            console.error('No se encontró el formulario ni la fila padre para el botón Guardar', btn);
            return;
          }
        }

        // Small client-side validation: ensure required selects exist
        var container = (form && form instanceof HTMLFormElement) ? form : row;
        var startSel = container ? container.querySelector('.start-select') : null;
        var endSel = container ? container.querySelector('.end-select') : null;
        var daySel = container ? container.querySelector('.day-select') : null;
        if (!daySel || !startSel || !endSel) {
          console.warn('Campos faltantes en la fila/formulario, abortando envío', { daySel: !!daySel, startSel: !!startSel, endSel: !!endSel });
          return;
        }

        var originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Guardando...';

        var action = (form && form.action) ? form.action : (btn.getAttribute('data-action') || null);
        if (!action) {
          console.error('No se pudo determinar la URL de acción para guardar el horario', btn);
          return;
        }

        var fd;
        if (form && form instanceof HTMLFormElement) {
          fd = new FormData(form);
        } else {
          // Construir FormData a partir de inputs/selects dentro de la fila
          fd = new FormData();
          var inputs = row.querySelectorAll('input,select,textarea');
          Array.prototype.forEach.call(inputs, function(inp){
            if (!inp.name) return;
            if (inp.disabled) return;
            var type = (inp.type || '').toLowerCase();
            if ((type === 'checkbox' || type === 'radio') && !inp.checked) return;
            if (inp.tagName.toLowerCase() === 'select' && inp.multiple) {
              Array.prototype.forEach.call(inp.options, function(opt){ if (opt.selected) fd.append(inp.name, opt.value); });
            } else {
              fd.append(inp.name, inp.value);
            }
          });
        }

  // Debug: print only the JSON payload that will be sent
  try {
    var _payloadPreview = {};
    for (var pair of fd.entries()) {
      var k = pair[0], v = pair[1];
      if (_payloadPreview.hasOwnProperty(k)) {
        if (!Array.isArray(_payloadPreview[k])) _payloadPreview[k] = [_payloadPreview[k]];
        _payloadPreview[k].push(v);
      } else {
        _payloadPreview[k] = v;
      }
    }
  console.log(String(JSON.stringify(_payloadPreview)));
  } catch (e) {
    console.warn('No se pudo serializar FormData para debug', e);
  }

        fetch(action, {
          method: 'POST',
          body: fd,
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(resp){
          // Prefer JSON responses for AJAX; fall back to text/html parsing if not JSON
          return resp.text().then(function(text){
            var parsed = null;
            try { parsed = JSON.parse(text); } catch(e) { parsed = null; }

            if (parsed && typeof parsed === 'object') {
              if (resp.ok) {
                showRowMessage(container, parsed.message || 'Guardado correctamente.', 'success');
              } else {
                showRowMessage(container, parsed.error || parsed.message || 'Error al guardar. Revisar los datos.', 'error');
              }
              return;
            }

            // Not JSON: fallback to old HTML parsing
            if (resp.ok) {
              showRowMessage(container, 'Guardado correctamente.', 'success');
              return;
            }
            var m = text.match(/<div class=\"alert error\">([\s\S]*?)<\/div>/i);
            if (m && m[1]) {
              showRowMessage(container, stripTags(m[1]).trim(), 'error');
            } else {
              // If server returned a raw JSON string (like {"error":"..."}) but parsing failed,
              // show the raw text trimmed
              var txt = stripTags(text).trim();
              showRowMessage(container, txt || 'Error al guardar. Revisar los datos.', 'error');
            }
          });
        }).catch(function(err){
          console.error('Fetch error:', err);
          showRowMessage(container, 'Error de red: ' + (err && err.message ? err.message : ''), 'error');
        }).finally(function(){
          btn.disabled = false;
          btn.innerHTML = originalHtml;
        });
      } catch (ex) {
        console.error('handleSaveClick excepción:', ex);
      }
    }

    // Delegación en tbody (funciona aunque el formulario use display:contents)
    tbody.addEventListener('click', function(e){
      var btn = e.target && e.target.closest ? e.target.closest('.ajax-save') : null;
      if (btn) {
        e.preventDefault();
        handleSaveClick(btn);
      }
    }, false);

    // Fallback: attach listeners directamente a los botones existentes
    var ajaxButtons = tbody.querySelectorAll('.ajax-save');
    Array.prototype.forEach.call(ajaxButtons, function(btn){
      // Avoid double-binding the same handler
      if (btn._hasAjaxHandler) return;
      btn.addEventListener('click', function(e){ e.preventDefault(); handleSaveClick(btn); }, false);
      btn._hasAjaxHandler = true;
    });

  })();
</script>

<script>
  // Actualizar año cuando cambie el mes
  (function(){
    var mesSelect = document.getElementById('mes');
    var anioSelect = document.getElementById('anio');
    
    if (!mesSelect || !anioSelect) return;
    
    mesSelect.addEventListener('change', function(){
      var now = new Date();
      var curMonth = now.getMonth() + 1;
      var curYear = now.getFullYear();
      var nextYear = curYear + 1;
      var chosen = parseInt(this.value, 10);
      var yearToUse = curYear;
      
      if (curMonth === 12 && !isNaN(chosen) && chosen !== 12) {
        yearToUse = nextYear;
      }
      
      // Actualizar el select de año
      anioSelect.innerHTML = '<option value="' + yearToUse + '" selected>' + yearToUse + '</option>';
      
      // Actualizar todos los formularios con el nuevo año
      var forms = document.querySelectorAll('#horariosTbody form');
      Array.prototype.forEach.call(forms, function(form){
        var anioInput = form.querySelector('input[name="anio"]');
        if (anioInput) {
          anioInput.value = String(yearToUse);
        }
        // también actualizar el campo mes oculto en cada formulario
        var mesInput = form.querySelector('input[name="mes"]');
        if (mesInput) mesInput.value = String(chosen);
      });
    });
  })();
</script>

<script>
  // Propagar cambios de Doctor y Sede a los formularios individuales
  (function(){
    var docTop = document.getElementById('doctor_top');
    if (docTop) {
      docTop.addEventListener('change', function(){
        var v = this.value || '';
        Array.prototype.forEach.call(document.querySelectorAll('#horariosTbody input[name="doctor_id"]'), function(inp){ inp.value = v; });
      });
    }

    var sedeTop = document.getElementById('sede_top');
    if (sedeTop) {
      sedeTop.addEventListener('change', function(){
        var v = this.value || '';
        Array.prototype.forEach.call(document.querySelectorAll('#horariosTbody .sede-select'), function(sel){ sel.value = v; });
      });
    }
  })();
</script>

<style>
  .table {
    border-collapse: collapse;
    width: 100%;
    margin-bottom: 1rem;
    background-color: transparent;
  }
  
  .table th,
  .table td {
    border: 1px solid #dee2e6;
    padding: 8px 12px;
    vertical-align: middle;
  }
  
  .table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
  }
  
  .table tbody tr:nth-child(even) {
    background-color: #f9f9f9;
  }
  
  .input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
  }
  
  .btn {
    display: inline-block;
    padding: 8px 16px;
    margin: 4px;
    text-decoration: none;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-align: center;
    transition: background-color 0.2s;
  }
  
  .btn.primary {
    background-color: #007bff;
    color: white;
  }
  
  .btn.primary:hover {
    background-color: #0056b3;
  }
  
  .btn.ghost {
    background-color: #6c757d;
    color: white;
  }
  
  .btn.ghost:hover {
    background-color: #545b62;
  }
  
  .alert {
    padding: 12px;
    margin-bottom: 16px;
    border: 1px solid transparent;
    border-radius: 4px;
  }
  
  .alert.error {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
  }
  
  .alert.warning {
    background-color: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
  }
  
  .row {
    margin-bottom: 16px;
  }
  
  .label {
    display: block;
    margin-bottom: 4px;
    font-weight: 600;
    color: #333;
  }
  
  .hint {
    font-size: 12px;
    color: #6c757d;
    font-style: italic;
  }
  
  .table-responsive {
    overflow-x: auto;
  }

  /* Visual for blocked rows */
  .blocked-row {
    background-color: #f8f9fa;
    opacity: 0.85;
  }
  .blocked-row .input[disabled] { background-color: #e9ecef; }
</style>