<?php
/** Espera $title, $schedules, $user (opcional)
 *  Mejora: vista tipo calendario mensual con selector de mes/año, filtros y export.
 */

// Mes y año seleccionados (se prefieren variables provistas por el controlador)
$selMonth = isset($selMonth) ? (int)$selMonth : (isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n'));
$selYear = isset($selYear) ? (int)$selYear : (isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y'));
if ($selMonth < 1 || $selMonth > 12) $selMonth = (int)date('n');
if ($selYear < 1970) $selYear = (int)date('Y');

// Detectar si se aplicaron filtros en la querystring
$hasFilter = isset($_GET['month']) || isset($_GET['year']) || isset($_GET['week']) || isset($_GET['doctor_id']) || isset($_GET['sede_id']);

// Generar días del mes SOLO si hay filtro (evitar valores por defecto automáticos)
if (isset($_GET['week'])) {
  // Si filtra por semana, mostramos 7 días
  $daysInMonth = 7;
} elseif (isset($_GET['month']) && isset($_GET['year'])) {
  $daysInMonth = (int)date('t', strtotime("{$selYear}-{$selMonth}-01"));
} else {
  $daysInMonth = 0;
}
// Detectar si hay filtros aplicados (evitar mostrar registros automáticamente)
$hasFilter = isset($_GET['month']) || isset($_GET['year']) || isset($_GET['week']) || isset($_GET['doctor_id']) || isset($_GET['sede_id']);

// Construir filas a partir de las entradas concretas en la tabla `calendario` si hay filtros.
$rows = [];
if ($hasFilter) {
  if (!empty($calendars)) {
    foreach ($calendars as $c) {
      $docId = $c->doctor_id ?? 0;
      // Determinar sede: se puede derivar desde el horario asociado cuando exista
      $sedeId = $c->horario?->sede_id ?? 0;
      $key = $docId . '_' . ($sedeId === null ? '0' : $sedeId);
      if (!isset($rows[$key])) {
        $rows[$key] = [
          'doctor' => $c->doctor ?? ($c->horario?->doctor ?? null),
          'sede' => ($c->horario?->sede) ?? null,
          // dates -> fecha (Y-m-d) => array of calendario entries
          'dates' => []
        ];
      }
      $fecha = (string)($c->fecha ?? '');
      if (!isset($rows[$key]['dates'][$fecha])) $rows[$key]['dates'][$fecha] = [];
      $rows[$key]['dates'][$fecha][] = $c;
    }
  } else {
    // Fallback: construir filas desde patrones (schedules) agrupando por doctor/sede
    foreach ($schedules as $s) {
      $docId = $s->doctor_id ?? 0;
      $sedeId = $s->sede_id ?? 0;
      $key = $docId . '_' . ($sedeId === null ? '0' : $sedeId);
      if (!isset($rows[$key])) {
        $rows[$key] = [
          'doctor' => $s->doctor ?? null,
          'sede' => $s->sede ?? null,
          'items' => []
        ];
      }
      $dayKey = mb_strtolower(trim((string)$s->dia_semana));
      if (!isset($rows[$key]['items'][$dayKey])) $rows[$key]['items'][$dayKey] = [];
      $rows[$key]['items'][$dayKey][] = $s;
    }
  }
} else {
  // Sin filtros: no construir filas automáticamente
  $rows = [];
}

// Mapa weekday number -> clave en español usada en los patrones
$weekdayMap = [1=>'lunes',2=>'martes',3=>'miércoles',4=>'jueves',5=>'viernes',6=>'sábado',7=>'domingo'];

?>
<section class="hero">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
    <div>
      <h1><?= htmlspecialchars($title ?? 'Horarios Doctores') ?></h1>
      <a href="/doctor-schedules/create" class="btn primary">+ Agregar</a>
    </div>    
  </div>
</section>

<section class="mt-4">
  <form method="GET" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
    <label class="label" for="month">Seleccionar un Período</label>
    <select name="month" id="month" class="input" style="width:160px;">
      <option value="" <?= !isset($_GET['month']) ? 'selected' : '' ?>>--</option>
      <?php
        // Nombres de meses en español (evita depender de la configuración de locale)
        $monthNames = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
        for ($m = 1; $m <= 12; $m++):
      ?>
        <option value="<?= $m ?>" <?= (isset($_GET['month']) && $m === $selMonth) ? 'selected' : '' ?>><?= ucfirst($monthNames[$m]) ?></option>
      <?php endfor; ?>
    </select>
    <select name="year" id="year" class="input" style="width:110px;">
      <option value="" <?= !isset($_GET['year']) ? 'selected' : '' ?>>--</option>
      <?php for ($y = $selYear - 1; $y <= $selYear + 1; $y++): ?>
        <option value="<?= $y ?>" <?= (isset($_GET['year']) && $y === $selYear) ? 'selected' : '' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>

    <button class="btn" type="submit">Filtrar</button>
  </form>
  <script>
    // Antes de enviar el formulario, quitar name de selects/inputs vacíos para que no se envíen como GET
    (function(){
      var form = document.querySelector('section.mt-4 form');
      if (!form) return;
      form.addEventListener('submit', function(){
        Array.prototype.forEach.call(form.querySelectorAll('select,input'), function(el){
          if (!el.name) return;
          // Para selects y campos de texto, si el valor es vacío, remover el atributo name
          if ((el.tagName === 'SELECT' || el.type === 'text' || el.type === 'hidden') && (el.value === null || el.value === '')) {
            el.removeAttribute('name');
          }
        });
      });
    })();
  </script>
  <div>
    <input id="filterInput" class="input" placeholder="Filtrar Registro en la Tabla" style="width:20%" />
  </div>
  
  <div style="margin:6px 0 12px;display:flex;justify-content:space-between;align-items:center;">
    <div><small class="muted">Items por página:</small>
      <select id="perPage" class="input" style="width:80px;display:inline-block;">
        <option value="" <?= !isset($_GET['perPage']) ? 'selected' : '' ?>>--</option>
        <option value="10">10</option><option value="25">25</option><option value="50">50</option>
      </select>
    </div>
    <div><small class="muted">Resultados: <?= count($rows) ?> fila(s)</small></div>
  </div>

  <?php if (!$hasFilter): ?>
    <div class="card"><div class="content"><p class="muted">Aplica filtros para ver registros.</p></div></div>
  <?php elseif (empty($rows)): ?>
    <div class="card"><div class="content"><p class="muted">No hay horarios registrados para el período seleccionado.</p></div></div>
  <?php else: ?>
    <!-- Tabulator assets (local) -->
    <link href="/lib/tabulator/tabulator.min.css" rel="stylesheet">
    <script src="/lib/tabulator/tabulator.min.js"></script>

    <style>
      /* Visual tuning to resemble the screenshot */
      /* Make container 50px less than the width of .page (if present). Falls back to parent width minus 50px. */
      .page .schedulesGridWrapper{ width: calc(100% - 50px); max-width: calc(100% - 50px); overflow-x:auto; overflow-y:visible; box-sizing:border-box; }
      .schedulesGridWrapper{ width: calc(100% - 50px); max-width: calc(100% - 50px); overflow-x:auto; overflow-y:visible; box-sizing:border-box; }
      #schedulesGrid{ padding:0; }
      #schedulesGrid .tabulator-header{ background:#f8f9fa; border-bottom:2px solid #dee2e6; }
      #schedulesGrid .tabulator-header .tabulator-col{ padding:8px 10px; text-align:center; font-weight:600; color:#333; }
      #schedulesGrid .tabulator-row .tabulator-cell{ padding:8px; vertical-align:top; }
      #schedulesGrid .tabulator-table .tabulator-row:nth-child(even) .tabulator-cell{ background:#f2f2f2; }
      #schedulesGrid .tabulator-table .tabulator-row:nth-child(odd) .tabulator-cell{ background:#fff; }
      /* frozen col visual separation */
      #schedulesGrid .tabulator .tabulator-header .tabulator-col.frozen{ background:#f8f9fa; }
      /* Allow internal table to grow horizontally without expanding the page */
      #schedulesGrid .tabulator{ display:inline-block; width:auto !important; min-width:100%; box-sizing:border-box; }
      #schedulesGrid .tabulator .tabulator-table{ min-width: max-content; width:auto !important; }
    </style>

    <!-- Tabulator-based grid that preserves original headers -->
    <div class="schedulesGridWrapper" style="overflow-x:auto;overflow-y:visible;box-sizing:border-box;">
      <div id="schedulesGrid" style="border:1px solid #ddd;background:#fff;"></div>
    </div>

  <?php endif; ?>

  <?php if (!empty($rows)): ?>
    <?php
      $rowsForJs = [];
      foreach ($rows as $key => $r) {
        $doc = $r['doctor'] ?? null;
        $sede = $r['sede'] ?? null;

        // Determine a representative pattern id (horarios_medicos.id) for this row when possible
        $patternId = null;
        // If we have grouped patterns in 'items', pick the first pattern id found
        if (!empty($r['items'])) {
          foreach ($r['items'] as $dayItems) {
            if (!empty($dayItems) && is_array($dayItems)) {
              $first = $dayItems[0];
              $patternId = $first->id ?? null;
              break;
            }
          }
        }
        // If not found, try to extract from concrete calendar entries (dates -> entry->horario)
        if ($patternId === null && !empty($r['dates'])) {
          foreach ($r['dates'] as $dateArr) {
            if (!empty($dateArr) && is_array($dateArr)) {
              $entry = $dateArr[0];
              $patternId = $entry->horario->id ?? $entry->horario_id ?? null;
              break;
            }
          }
        }

        // Build URLs: if we have a pattern id, link to edit/delete that pattern; otherwise, link to create (prefill doctor if available)
        $editUrl = $patternId ? ('/doctor-schedules/' . (int)$patternId . '/edit') : ('/doctor-schedules/create' . (($doc?->id ?? 0) ? ('?doctor_id=' . (int)$doc->id) : ''));
        $acciones = '<div style="display:flex;gap:4px;justify-content:center;">'
          . '<a href="' . $editUrl . '" style="background:#28a745;color:#fff;border-radius:4px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;text-decoration:none;" title="Editar">✎</a>';

        if ($patternId) {
          $acciones .= '<form method="POST" action="/doctor-schedules/' . (int)$patternId . '/delete" style="display:inline-block;margin:0;padding:0;">'
            . '<input type="hidden" name="_csrf" value="' . htmlspecialchars(\App\Core\Csrf::token()) . '">'
            . '<button type="submit" onclick="return confirm(\'¿Eliminar este horario?\')" style="background:#ff0063;color:#fff;border:none;border-radius:4px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;" title="Eliminar">🗑</button>'
            . '</form>';
        }

        $acciones .= '</div>';

        $doctorHtml = '<strong>' . htmlspecialchars($doc?->user?->nombre ?? '') . ' ' . htmlspecialchars($doc?->user?->apellido ?? '') . '</strong>'
          . '<div class="muted" style="font-size:12px;">' . htmlspecialchars($doc?->user?->email ?? '') . '</div>';

        $sedeHtml = htmlspecialchars($sede?->nombre_sede ?? 'Cualquier sede');

        $row = ['acciones' => $acciones, 'doctor' => $doctorHtml, 'sede' => $sedeHtml];

        for ($d = 1; $d <= $daysInMonth; $d++) {
          $dateStr = sprintf('%04d-%02d-%02d', $selYear, $selMonth, $d);
          $w = (int)date('N', strtotime($dateStr));
          $dayKey = $weekdayMap[$w];
          $cellHtml = '';

          if (!empty($r['dates'][$dateStr])) {
            foreach ($r['dates'][$dateStr] as $entry) {
              $start = $entry->hora_inicio ? date('H:i', strtotime($entry->hora_inicio)) : ($entry->horario?->hora_inicio ? date('H:i', strtotime($entry->horario->hora_inicio)) : '');
              $end = $entry->hora_fin ? date('H:i', strtotime($entry->hora_fin)) : ($entry->horario?->hora_fin ? date('H:i', strtotime($entry->horario->hora_fin)) : '');
              $cellHtml .= '<div style="background:#ffe9b3;padding:4px;margin:2px;border-radius:3px;font-size:12px;">' . htmlspecialchars($start) . ' - ' . htmlspecialchars($end) . '</div>';
            }
          } elseif (!empty($r['items'][$dayKey])) {
            foreach ($r['items'][$dayKey] as $it) {
              $start = $it->hora_inicio ? date('H:i', strtotime($it->hora_inicio)) : '';
              $end = $it->hora_fin ? date('H:i', strtotime($it->hora_fin)) : '';
              $cellHtml .= '<div style="background:#ffe9b3;padding:4px;margin:2px;border-radius:3px;font-size:12px;">' . htmlspecialchars($start) . ' - ' . htmlspecialchars($end) . '</div>';
            }
          }

          $row['day' . $d] = $cellHtml;
        }

        $rowsForJs[] = $row;
      }
    ?>

    <script>
      (function(){
        var schedulesData = <?= json_encode($rowsForJs, JSON_UNESCAPED_UNICODE) ?>;

        var columns = [
          {title:"Acciones", field:"acciones", frozen:true, hozAlign:"center", width:110, formatter:"html"},
          {title:"Doctor", field:"doctor", frozen:true, width:200, formatter:"html"},
          {title:"Sede", field:"sede", frozen:true, width:200, formatter:"html"},
        ];

        <?php for ($d = 1; $d <= $daysInMonth; $d++):
            $dateStr = sprintf('%04d-%02d-%02d', $selYear, $selMonth, $d);
            $short = date('d', strtotime($dateStr));
            $w = (int)date('N', strtotime($dateStr));
            $label = $short . ' ' . ucfirst(mb_substr($weekdayMap[$w],0,3,'UTF-8'));
        ?>
          columns.push({title:"<?= $label ?>", field:"day<?= $d ?>", formatter:"html", hozAlign:"center", minWidth:150});
        <?php endfor; ?>

        var table = new Tabulator("#schedulesGrid", {
          data: schedulesData,
          columns: columns,
          layout: "fitData",
          autoResize: false,
          renderHorizontal: "virtual",
          virtualDom: true,
          pagination: "local",
          paginationSize: parseInt(document.getElementById('perPage')?.value || 10, 10),
          movableColumns: false,
          resizableColumns: true,
          placeholder: "No hay horarios para el período seleccionado",
        });

        var filterInput = document.getElementById('filterInput');
        if (filterInput) {
          filterInput.addEventListener('input', function(){
            var q = (this.value||'').toLowerCase().trim();
            if (!q) { table.clearFilter(); return; }
            table.setFilter(function(data){
              var hay = (data.doctor || '') + ' ' + (data.sede || '') + ' ' + (data.acciones || '');
              for (var k in data) { if (k.indexOf('day') === 0) hay += ' ' + (data[k] || ''); }
              return hay.toLowerCase().indexOf(q) !== -1;
            });
          });
        }

        var perPage = document.getElementById('perPage');
        if (perPage) {
          perPage.addEventListener('change', function(){ table.setPageSize(parseInt(this.value || 10, 10)); });
        }
      })();
    </script>
  <?php endif; ?>
</section>

<script>
  // Filtro cliente simple
  (function(){
    var input = document.getElementById('filterInput');
    var tbody = document.getElementById('schedulesTbody');
    if (!input || !tbody) return;
    input.addEventListener('input', function(){
      var q = (this.value||'').toLowerCase().trim();
      Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function(tr){
        var s = (tr.getAttribute('data-search')||'').toLowerCase();
        if (!q || s.indexOf(q) !== -1) tr.style.display = ''; else tr.style.display = 'none';
      });
    });
  })();
</script>

<script>
  // Determinar el ancho inicial de la página UNA VEZ y mantenerlo inmutable.
  (function(){
    try {
      var page = document.querySelector('.page');
      var wrapper = document.querySelector('.schedulesGridWrapper');
      if (!wrapper) return;

      // Si ya existe PAGE_WIDTH definida, úsala (no recalculamos)
      if (typeof window.PAGE_WIDTH !== 'undefined' && window.PAGE_WIDTH !== null) {
        var fixedTarget = Math.max(0, window.PAGE_WIDTH - 50);
        wrapper.style.width = fixedTarget + 'px';
        wrapper.style.maxWidth = fixedTarget + 'px';
      } else {
        // Medir ahora y definir PAGE_WIDTH de forma no escribible
        var measured = null;
        if (page) measured = Math.round(page.getBoundingClientRect().width);
        else measured = Math.round((wrapper.parentElement || document.body).getBoundingClientRect().width || 0);

        var safeMeasured = Math.max(0, measured);
        try {
          Object.defineProperty(window, 'PAGE_WIDTH', {
            value: safeMeasured,
            writable: false,
            configurable: false,
            enumerable: true
          });
        } catch (err) {
          // Si falla (navegadores antiguos), asignar pero evita modificaciones posteriores por convención
          if (typeof window.PAGE_WIDTH === 'undefined') window.PAGE_WIDTH = safeMeasured;
        }

        try { if (page) page.dataset.pageWidth = String(safeMeasured); } catch (e) {}

        var target = Math.max(0, safeMeasured - 50);
        wrapper.style.width = target + 'px';
        wrapper.style.maxWidth = target + 'px';
      }
    } catch (e) {
      console && console.error && console.error('Error inicializando ancho fijo para schedulesGridWrapper', e);
    }
    // Nota: no añadimos listener de resize para mantener el ancho inicial intacto.
  })();
</script>
