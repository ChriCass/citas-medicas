<?php
/** Espera $title, $schedules, $user (opcional)
 *  Mejora: vista tipo calendario mensual con selector de mes/año, filtros y export.
 */

// Mes y año seleccionados (se prefieren variables provistas por el controlador)
$selMonth = isset($selMonth) ? (int)$selMonth : (isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n'));
$selYear = isset($selYear) ? (int)$selYear : (isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y'));
if ($selMonth < 1 || $selMonth > 12) $selMonth = (int)date('n');
if ($selYear < 1970) $selYear = (int)date('Y');

// Generar días del mes
$daysInMonth = (int)date('t', strtotime("{$selYear}-{$selMonth}-01"));

// Construir filas a partir de las entradas concretas en la tabla `calendario` si están disponibles.
$rows = [];
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

// Mapa weekday number -> clave en español usada en los patrones
$weekdayMap = [1=>'lunes',2=>'martes',3=>'miércoles',4=>'jueves',5=>'viernes',6=>'sábado',7=>'domingo'];

?>
<section class="hero">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
    <div>
      <h1><?= htmlspecialchars($title ?? 'Horarios Doctores') ?></h1>
      <p class="mt-1 muted">Vista mensual: selecciona mes/año para ver la grilla.</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
      <a href="#" class="btn" id="downloadBtn">📥 Descargar</a>
      <a href="/doctor-schedules/create" class="btn primary">+ Agregar</a>
      <a href="/doctor-schedules/create" class="btn" style="background:#28a745;color:#fff;">+ Agregar Masiva</a>
    </div>
  </div>
</section>

<section class="mt-4">
  <form method="GET" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
    <label class="label" for="month">Seleccionar un Período</label>
    <select name="month" id="month" class="input" style="width:160px;">
      <?php for ($m = 1; $m <= 12; $m++): $mName = strftime('%B', strtotime("2000-{$m}-01")); ?>
        <option value="<?= $m ?>" <?= $m === $selMonth ? 'selected' : '' ?>><?= ucfirst($mName) ?> <?= $selYear ?></option>
      <?php endfor; ?>
    </select>
    <select name="year" id="year" class="input" style="width:110px;">
      <?php for ($y = $selYear - 1; $y <= $selYear + 1; $y++): ?>
        <option value="<?= $y ?>" <?= $y === $selYear ? 'selected' : '' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>

    <input id="filterInput" class="input" placeholder="Filtrar Registro en la Tabla" style="flex:1;" />
    <button class="btn" type="submit">Actualizar</button>
  </form>

  <div style="margin:6px 0 12px;display:flex;justify-content:space-between;align-items:center;">
    <div><small class="muted">Items por página:</small>
      <select id="perPage" class="input" style="width:80px;display:inline-block;">
        <option>10</option><option>25</option><option>50</option>
      </select>
    </div>
    <div><small class="muted">Resultados: <?= count($rows) ?> fila(s)</small></div>
  </div>

  <?php if (empty($rows)): ?>
    <div class="card"><div class="content"><p class="muted">No hay horarios registrados para el período seleccionado.</p></div></div>
  <?php else: ?>
    <div style="overflow:auto;border:1px solid #eee;padding:8px;background:#fff;">
      <table class="table" style="min-width:1200px;border-collapse:collapse;">
        <thead>
          <tr>
            <th style="position:sticky;left:0;background:#f7f7f7;z-index:2;padding:8px;">Colegio / Doctor</th>
            <th style="position:sticky;left:240px;background:#f7f7f7;z-index:2;padding:8px;">Sede</th>
            <?php for ($d = 1; $d <= $daysInMonth; $d++):
                $dateStr = sprintf('%04d-%02d-%02d', $selYear, $selMonth, $d);
                $short = date('d', strtotime($dateStr));
                $w = (int)date('N', strtotime($dateStr));
                $label = $short . ' ' . ucfirst(substr($weekdayMap[$w],0,3));
            ?>
              <th style="padding:6px;text-align:center;"><?= $label ?></th>
            <?php endfor; ?>
          </tr>
        </thead>
        <tbody id="schedulesTbody">
          <?php foreach ($rows as $key => $r):
              $doc = $r['doctor']; $sede = $r['sede'];
              $search = strtolower(($doc?->user?->nombre ?? '') . ' ' . ($doc?->user?->apellido ?? '') . ' ' . ($doc?->user?->email ?? '') . ' ' . ($sede?->nombre_sede ?? ''));
          ?>
          <tr data-search="<?= htmlspecialchars($search) ?>">
            <td style="white-space:nowrap;padding:8px;min-width:220px;"> 
              <strong><?= htmlspecialchars($doc?->user?->nombre ?? '') ?> <?= htmlspecialchars($doc?->user?->apellido ?? '') ?></strong>
              <div class="muted" style="font-size:12px;"><?= htmlspecialchars($doc?->user?->email ?? '') ?></div>
            </td>
            <td style="white-space:nowrap;padding:8px;min-width:200px;"><?= htmlspecialchars($sede?->nombre_sede ?? 'Cualquier sede') ?></td>
            <?php for ($d = 1; $d <= $daysInMonth; $d++):
                $dateStr = sprintf('%04d-%02d-%02d', $selYear, $selMonth, $d);
                $w = (int)date('N', strtotime($dateStr));
                $dayKey = $weekdayMap[$w];
        $cellHtml = '';
        $dateStr = sprintf('%04d-%02d-%02d', $selYear, $selMonth, $d);
        // Si la fila contiene claves 'dates', usamos las entradas de calendario por fecha exacta
        if (!empty($r['dates'][$dateStr])) {
          foreach ($r['dates'][$dateStr] as $entry) {
            $start = $entry->hora_inicio ? date('H:i', strtotime($entry->hora_inicio)) : ($entry->horario?->hora_inicio ? date('H:i', strtotime($entry->horario->hora_inicio)) : '');
            $end = $entry->hora_fin ? date('H:i', strtotime($entry->hora_fin)) : ($entry->horario?->hora_fin ? date('H:i', strtotime($entry->horario->hora_fin)) : '');
            $cellHtml .= '<div style="background:#ffe9b3;padding:4px;margin:2px;border-radius:3px;font-size:12px;">' . htmlspecialchars($start) . ' - ' . htmlspecialchars($end) . '</div>';
          }
        } elseif (!empty($r['items'][$dayKey])) {
          // Fallback a patrones semanales cuando no hay entradas concretas
          foreach ($r['items'][$dayKey] as $it) {
            $start = $it->hora_inicio ? date('H:i', strtotime($it->hora_inicio)) : '';
            $end = $it->hora_fin ? date('H:i', strtotime($it->hora_fin)) : '';
            $cellHtml .= '<div style="background:#ffe9b3;padding:4px;margin:2px;border-radius:3px;font-size:12px;">' . htmlspecialchars($start) . ' - ' . htmlspecialchars($end) . '</div>';
          }
        }
            ?>
              <td style="vertical-align:top;padding:6px;min-width:110px;"><?= $cellHtml ? $cellHtml : '&nbsp;' ?></td>
            <?php endfor; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
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
