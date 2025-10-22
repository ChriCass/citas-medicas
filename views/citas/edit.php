<?php if(($_SESSION['user']['rol'] ?? '') !== 'doctor'): ?>
  <div class="alert mt-2">No tienes acceso a esta pantalla.</div>
<?php else: ?>
  <h1>Editar cita</h1>

  <style>
    .invalid { border-color: #e53e3e !important; box-shadow: 0 0 0 3px rgba(229,62,62,0.06); }
    .field-error { color: #e53e3e; font-size: 0.9em; margin-top: 4px; display: none; }
    .field-error.visible { display: block; }
    #client_error { background: #ffe6e6; border-left: 4px solid #e53e3e; padding: 8px; }
    .input-with-toggle { position: relative; }
    .input-with-toggle .toggle-btn { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 28px; height: 28px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:#666; background:transparent; border:none; }
    .input-with-toggle .toggle-btn:focus { outline: none; box-shadow: 0 0 0 3px rgba(0,0,0,0.06); }
    #diagnostico_input { padding-right: 38px; }
  </style>

  <?php if (!empty($error)): ?>
    <div class="alert error mt-2"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php
    $a = $appointment ?? null;
    $c = $consulta ?? null;

    // Helpers para formatear fecha y hora de forma robusta
    $fmtDate = function($v){
      if ($v instanceof \DateTimeInterface) return $v->format('d-m-Y');
      if (is_string($v) && $v !== '') {
        // Si viene como 'YYYY-MM-DD' o 'YYYY-MM-DD HH:MM:SS'
        $ts = strtotime($v);
        if ($ts !== false) return date('d-m-Y', $ts);
        return $v;
      }
      return '';
    };

    $fmtTime = function($v){
      if ($v instanceof \DateTimeInterface) return $v->format('H:i');
      if (is_string($v) && $v !== '') {
        $ts = strtotime($v);
        if ($ts !== false) return date('H:i', $ts);
        return $v;
      }
      return '';
    };
  ?>

  <?php
    // Nombre/apellido paciente - preferir campos planos si están presentes, si no intentar rutas anidadas
    $pacienteNombre = $a['paciente_nombre'] ?? (isset($a['paciente']['user']['nombre']) ? $a['paciente']['user']['nombre'] : '');
    $pacienteApellido = $a['paciente_apellido'] ?? (isset($a['paciente']['user']['apellido']) ? $a['paciente']['user']['apellido'] : '');
    $citaFecha = $a['fecha'] ?? ($a['fecha'] ?? '');
    $citaHora = $a['hora_inicio'] ?? ($a['hora_inicio'] ?? '');
    $citaMotivo = $a['razon'] ?? '';
  ?>
  <div class="card">
    <div><strong>Paciente:</strong> <?= htmlspecialchars($pacienteNombre) ?> <?= htmlspecialchars($pacienteApellido) ?></div>
    <div><strong>Fecha:</strong> <?= htmlspecialchars($fmtDate($citaFecha)) ?></div>
    <div><strong>Hora:</strong> <?= htmlspecialchars($fmtTime($citaHora)) ?></div>
    <div><strong>Motivo:</strong> <?= htmlspecialchars($citaMotivo) ?></div>
  </div>

  <form method="POST" action="/citas/<?= (int)$a['id'] ?>/edit">
    <input type="hidden" name="recetas_payload" id="recetas_payload" value="">
    <div id="client_error" class="alert error mt-2" style="display:none"></div>
    <div class="row" style="position:relative;">
      <label class="label">Diagnóstico</label>
  <div class="input-with-toggle">
  <input class="input" type="text" name="diagnostico" id="diagnostico_input" value="<?= htmlspecialchars($c['diagnostico_nombre'] ?? '') ?>" autocomplete="on" aria-haspopup="listbox" aria-expanded="false">
    <button type="button" class="toggle-btn" id="diagnostico_toggle" aria-label="Mostrar lista de diagnósticos" title="Mostrar lista de diagnósticos">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 9l6 6l6 -6" /></svg>
    </button>
  <input type="hidden" name="diagnostico_id" id="diagnostico_id" value="<?= htmlspecialchars($c['diagnostico_id'] ?? '') ?>">
  </div>
      <div id="diagnostico_dropdown" class="dropdown" style="display:none; position:absolute; z-index:1000; left:0; right:0; background:#fff; border:1px solid #ddd; max-height:200px; overflow:auto;"></div>
      <div id="err_diagnostico" class="field-error"></div>
    </div>

    <div class="row">
      <label class="label">Observaciones</label>
  <textarea class="input" style="resize: none; height:80px" name="observaciones"><?= htmlspecialchars($c['observaciones'] ?? '') ?></textarea>
      <div id="err_observaciones" class="field-error"></div>
    </div>

    <div class="row">
      <label class="label">Receta</label>
      <table id="recetas_container" style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left; padding:6px; border-bottom:1px solid #ddd;">Medicamento</th>
            <th style="text-align:left; padding:6px; border-bottom:1px solid #ddd;">Indicación</th>
            <th style="text-align:left; padding:6px; border-bottom:1px solid #ddd; width:140px">Duración</th>
            <th style="padding:6px; border-bottom:1px solid #ddd;"></th>
          </tr>
        </thead>
        <tbody>
          <!-- filas de receta dinámicas -->
        </tbody>
      </table>
      <div style="margin-top:8px">
        <button type="button" id="add_receta_btn" class="btn">Agregar receta</button>
      </div>
      <div id="err_receta" class="field-error"></div>
    </div>

    <div class="row">
      <label class="label">Estado postconsulta</label>
      <select class="input" name="estado_postconsulta">
        <?php
          $opts = ['No problemático','Pasivo','Problemático'];
          $sel = $c['estado_postconsulta'] ?? '';
        ?>
        <option value="">— Selecciona —</option>
        <?php foreach ($opts as $o): ?>
          <option value="<?= htmlspecialchars($o) ?>" <?= $sel === $o ? 'selected' : '' ?>><?= htmlspecialchars($o) ?></option>
        <?php endforeach; ?>
      </select>
      <div id="err_estado" class="field-error"></div>
    </div>

    <div class="row">
      <button class="btn primary" type="submit" name="save_changes" value="save">Guardar cambios</button>
    </div>
  </form>
  <script>
  (function(){
    const input = document.getElementById('diagnostico_input');
    const dropdown = document.getElementById('diagnostico_dropdown');
  let items = [];
  let selected = -1;
  let t = null;
  // diagnosticos provistos por el servidor (si los hay) - array de {id,nombre_enfermedad}
  const serverDiagnosticos = <?= json_encode($diagnosticos ?? []) ?> || [];

    function renderDropdown(){
      dropdown.innerHTML = '';
      if (!items || items.length === 0) {
        dropdown.style.display = 'none';
        return;
      }
      items.forEach((it, idx) => {
        const div = document.createElement('div');
        div.className = 'dropdown-item';
        // it can be string or object {id,nombre_enfermedad}
        const label = (typeof it === 'string') ? it : (it.nombre_enfermedad ?? it.nombre ?? '');
        div.textContent = label;
        div.style.padding = '6px 8px';
        div.style.cursor = 'pointer';
        if (idx === selected) div.style.background = '#eef';
        div.addEventListener('mousedown', function(e){
          // mousedown para evitar blur antes del click
          e.preventDefault();
          selectItem(idx);
        });
        dropdown.appendChild(div);
      });
      dropdown.style.display = 'block';
    }

    function selectItem(idx){
      if (idx < 0 || idx >= items.length) return;
      const it = items[idx];
      const hid = document.getElementById('diagnostico_id');
      if (typeof it === 'string') {
        input.value = it;
        if (hid) hid.value = '';
      } else {
        input.value = it.nombre_enfermedad ?? it.nombre ?? '';
        if (hid) hid.value = it.id ?? '';
      }
      hideDropdown();
    }

    function hideDropdown(){
      dropdown.style.display = 'none';
      selected = -1;
      items = [];
    }

    async function fetchDiagnosticos(q){
      // If server provided a list, filter it client-side for instant response
      if (serverDiagnosticos && serverDiagnosticos.length > 0) {
        items = serverDiagnosticos.filter(d => (d.nombre_enfermedad||d.nombre||'').toLowerCase().includes((q||'').toLowerCase()));
        selected = -1;
        if (items.length > 0) { renderDropdown(); return; }
        // otherwise fall through to API
      }
      if (!q || q.length < 1) { hideDropdown(); return; }
      try{
        const url = `/api/v1/diagnosticos?q=${encodeURIComponent(q)}`;
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) { hideDropdown(); return; }
        const json = await res.json();
        items = json.data || [];
        selected = -1;
        renderDropdown();
      }catch(err){
        console.error('Error buscando diagnosticos', err);
        hideDropdown();
      }
    }

    // Obtener todos los diagnósticos (para mostrar lista completa al hacer click)
    async function fetchAllDiagnosticos(){
      try{
        if (serverDiagnosticos && serverDiagnosticos.length > 0) {
          items = serverDiagnosticos;
          selected = -1;
          renderDropdown();
          return;
        }
        const url = `/api/v1/diagnosticos`;
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) { hideDropdown(); return; }
        const json = await res.json();
        items = json.data || [];
        selected = -1;
        renderDropdown();
      }catch(err){ console.error('Error fetching all diagnosticos', err); hideDropdown(); }
    }

    input.addEventListener('input', function(e){
      const v = (e.target.value || '').trim();
      // Si el usuario está escribiendo, limpiar el hidden para forzar selección
      const hid = document.getElementById('diagnostico_id');
      if (hid) hid.value = '';
      // ocultar cualquier error previo
      const cerr = document.getElementById('client_error'); if (cerr) cerr.style.display = 'none';
      if (t) clearTimeout(t);
      t = setTimeout(()=> fetchDiagnosticos(v), 200);
    });

    // Cuando el usuario hace click o focus en el input, mostrar la lista completa
    input.addEventListener('focus', function(){
      // si ya hay texto y items mostrados no hacemos fetchAll
      const v = (input.value||'').trim();
      if (v.length === 0) {
        fetchAllDiagnosticos();
      }
    });
    input.addEventListener('click', function(){
      const v = (input.value||'').trim(); if (v.length === 0) fetchAllDiagnosticos();
    });

    // Toggle button to open the full list
    const toggle = document.getElementById('diagnostico_toggle');
    if (toggle) {
      toggle.addEventListener('click', function(){
        const expanded = input.getAttribute('aria-expanded') === 'true';
        if (expanded) { hideDropdown(); input.setAttribute('aria-expanded','false'); }
        else { fetchAllDiagnosticos(); input.setAttribute('aria-expanded','true'); input.focus(); }
      });
      // keyboard activation
      toggle.addEventListener('keydown', function(e){ if (e.key==='Enter' || e.key===' ') { e.preventDefault(); toggle.click(); } });
    }

  // Validación antes de enviar: diagnostico_id, observaciones, al menos una receta (salvo ausente) y estado_postconsulta obligatorios
    (function(){
      const form = input.closest('form');
      if (!form) return;
      function showClientError(msg){
        const el = document.getElementById('client_error');
        if (!el) { alert(msg); return; }
        el.textContent = msg; el.style.display = 'block'; el.scrollIntoView({behavior:'smooth', block:'center'});
      }

      form.addEventListener('submit', function(e){
        // Asumimos que el botón es 'atendido' en este formulario
        const hid = document.getElementById('diagnostico_id');
        const obs = (document.querySelector('textarea[name="observaciones"]')?.value || '').trim();
        // comprobar si hay al menos una receta no-vacía
        const recetaRows = Array.from(document.querySelectorAll('.receta-row'));
        const recEmpty = recetaRows.length === 0 || recetaRows.every(r => {
          const med = r.querySelector('select[name="recetas[][id_medicamento]"]');
          const ind = r.querySelector('textarea[name="recetas[][indicacion]"]');
          const dur = r.querySelector('input[name="recetas[][duracion]"]');
          const has = ((med && med.value) || (ind && ind.value && ind.value.trim() !== '') || (dur && dur.value && dur.value.trim() !== ''));
          return !has;
        });
        const estado = (document.querySelector('select[name="estado_postconsulta"]')?.value || '').trim();
        // limpiar errores previos por campo
        function clearFieldErr(id){ const el = document.getElementById(id); if (el) { el.textContent=''; el.classList.remove('visible'); } }
        function setFieldErr(fieldEl, errId, msg){ if (fieldEl) fieldEl.classList.add('invalid'); const e = document.getElementById(errId); if (e) { e.textContent = msg; e.classList.add('visible'); } }
        function clearAllFieldErrs(){ ['err_diagnostico','err_observaciones','err_receta','err_estado'].forEach(id=>{ const e=document.getElementById(id); if(e){e.textContent=''; e.classList.remove('visible');} });
          // remove invalid class
          const fields = [document.getElementById('diagnostico_input'), document.querySelector('textarea[name="observaciones"]'), document.querySelector('select[name="estado_postconsulta"]')];
          fields.forEach(f=>{ if(f) f.classList.remove('invalid'); });
        }

        clearAllFieldErrs();

        let firstInvalid = null;
        if (!hid || (hid.value || '').trim() === ''){
          e.preventDefault(); setFieldErr(document.getElementById('diagnostico_input'),'err_diagnostico','Debes seleccionar un diagnóstico existente de la lista. No se permiten crear diagnósticos nuevos desde aquí.'); firstInvalid = firstInvalid || document.getElementById('diagnostico_input');
        }
        if (estado === ''){ e.preventDefault(); setFieldErr(document.querySelector('select[name="estado_postconsulta"]'),'err_estado','El estado postconsulta es obligatorio.'); firstInvalid = firstInvalid || document.querySelector('select[name="estado_postconsulta"]'); }
        if (obs === ''){ e.preventDefault(); setFieldErr(document.querySelector('textarea[name="observaciones"]'),'err_observaciones','Las observaciones no pueden estar vacías.'); firstInvalid = firstInvalid || document.querySelector('textarea[name="observaciones"]'); }
        if (recEmpty && hid && hid.value !== '' ){ e.preventDefault(); setFieldErr(document.getElementById('add_receta_btn'),'err_receta','Debes agregar al menos una receta con medicamento, indicación o duración.'); firstInvalid = firstInvalid || document.getElementById('add_receta_btn'); }

        // VALIDACIÓN ADICIONAL: por cada fila de receta que contenga algún dato, exigir
        // que los campos `indicacion` y `duracion` no estén vacíos.
        if (!recEmpty) {
          let recetaFieldInvalid = false;
          for (let i = 0; i < recetaRows.length; i++) {
            const r = recetaRows[i];
            const med = r.querySelector('select[name="recetas[][id_medicamento]"]');
            const ind = r.querySelector('textarea[name="recetas[][indicacion]"]');
            const dur = r.querySelector('input[name="recetas[][duracion]"]');
            const anyFilled = (med && med.value) || (ind && ind.value && ind.value.trim() !== '') || (dur && dur.value && dur.value.trim() !== '');
            if (anyFilled) {
              // validar indicacion
              if (!ind || (ind.value || '').trim() === '') {
                e.preventDefault();
                recetaFieldInvalid = true;
                setFieldErr(ind || document.getElementById('add_receta_btn'), 'err_receta', 'Cada receta requiere indicación (no puede estar vacía).');
                firstInvalid = firstInvalid || (ind || document.getElementById('add_receta_btn'));
              }
              // validar duracion
              if (!dur || (dur.value || '').trim() === '') {
                e.preventDefault();
                recetaFieldInvalid = true;
                // si ya marcamos indicacion, evitar sobrescribir el mensaje; de lo contrario mostrar mensaje genérico
                if (!ind || (ind.value || '').trim() === '') {
                  setFieldErr(dur || document.getElementById('add_receta_btn'), 'err_receta', 'Cada receta requiere duración (no puede estar vacía).');
                } else {
                  setFieldErr(dur || document.getElementById('add_receta_btn'), 'err_receta', 'La duración no puede estar vacía.');
                }
                firstInvalid = firstInvalid || (dur || document.getElementById('add_receta_btn'));
              }
            }
          }
          if (recetaFieldInvalid) {
            // si hay error en filas de receta, enfocamos el primer campo inválido
            // (firstInvalid se asignó arriba)
          }
        }

        if (firstInvalid) {
          firstInvalid.focus();
        }
      });
    })();

    // Clear individual field error when user interacts with it
    const diagEl = document.getElementById('diagnostico_input');
    if (diagEl) {
      diagEl.addEventListener('input', ()=>{
        diagEl.classList.remove('invalid');
        const e = document.getElementById('err_diagnostico'); if (e){ e.textContent=''; e.classList.remove('visible'); }
      });
    }

    // No single 'receta' textarea anymore; per-row inputs are handled dynamically.

    const obsEl = document.querySelector('textarea[name="observaciones"]'); if (obsEl) obsEl.addEventListener('input', ()=>{ obsEl.classList.remove('invalid'); const e=document.getElementById('err_observaciones'); if(e){ e.textContent=''; e.classList.remove('visible'); } });
    const estadoEl = document.querySelector('select[name="estado_postconsulta"]'); if (estadoEl) estadoEl.addEventListener('change', ()=>{ estadoEl.classList.remove('invalid'); const e=document.getElementById('err_estado'); if(e){ e.textContent=''; e.classList.remove('visible'); } });

    input.addEventListener('keydown', function(e){
      if (dropdown.style.display === 'none') return;
      if (e.key === 'ArrowDown') {
        e.preventDefault(); selected = Math.min(selected + 1, items.length - 1); renderDropdown();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault(); selected = Math.max(selected - 1, 0); renderDropdown();
      } else if (e.key === 'Enter') {
        if (selected >= 0) { e.preventDefault(); selectItem(selected); }
      } else if (e.key === 'Escape') { hideDropdown(); }
    });

    document.addEventListener('click', function(e){
      if (!input.contains(e.target) && !dropdown.contains(e.target)) hideDropdown();
    });

    // Inicializar: NO abrir el dropdown automáticamente al cargar la vista.
    // Si el input ya tiene texto, no hacemos fetch ni mostramos la lista; el médico podrá
    // abrirla mediante el toggle o al borrar/editar el texto.
    (function(){
      try{
        input.setAttribute('aria-expanded','false');
        dropdown.style.display = 'none';
      }catch(e){ /* no crítico */ }
    })();

    // ---------- Recetas dynamic UI ----------
    const recetasContainer = document.getElementById('recetas_container');
    const addRecetaBtn = document.getElementById('add_receta_btn');
    // medicamentos pasados desde servidor
    const medicamentosList = <?= json_encode($medicamentos ?? []) ?> || [];
    const serverRecetas = <?= json_encode($recetas ?? []) ?> || [];
    let nextRecetaIdx = 0;

    function createMedicamentoSelect(name, selectedId){
      const sel = document.createElement('select');
      sel.name = name;
      sel.className = 'input';
      const emptyOpt = document.createElement('option'); emptyOpt.value=''; emptyOpt.textContent='— Selecciona medicamento —'; sel.appendChild(emptyOpt);
      medicamentosList.forEach(m => {
        const o = document.createElement('option'); o.value = m.id; o.textContent = m.nombre; if (selectedId && parseInt(selectedId) === parseInt(m.id)) o.selected = true; sel.appendChild(o);
      });
      return sel;
    }

    function renderRecetaRow(data){
      const idx = nextRecetaIdx++;
      const tbody = recetasContainer.querySelector('tbody') || recetasContainer;
      const tr = document.createElement('tr'); tr.className = 'receta-row';

      const hidId = document.createElement('input'); hidId.type = 'hidden'; hidId.name = 'recetas[][id]'; hidId.value = data?.id ? data.id : '';

      const tdMed = document.createElement('td'); tdMed.style.padding = '6px';
      const sel = createMedicamentoSelect(`recetas[][id_medicamento]`, data?.id_medicamento ?? data?.medicamento?.id ?? ''); sel.style.width = '100%';
      tdMed.appendChild(sel);

  const tdInd = document.createElement('td'); tdInd.style.padding = '8px 6px'; tdInd.style.verticalAlign = 'middle';
  const ind = document.createElement('textarea'); ind.name = `recetas[][indicacion]`; ind.className='input'; ind.style.width='100%'; ind.style.height='64px'; ind.style.resize = 'none'; ind.style.boxSizing = 'border-box'; ind.value = data?.indicacion ?? '';
  tdInd.appendChild(ind);

  const tdDur = document.createElement('td'); tdDur.style.padding = '8px 4px'; tdDur.style.width = '120px'; tdDur.style.verticalAlign = 'middle';
  const dur = document.createElement('input'); dur.type='text'; dur.name = `recetas[][duracion]`; dur.className='input'; dur.style.width='100%'; dur.style.boxSizing = 'border-box'; dur.value = data?.duracion ?? '';
  tdDur.appendChild(dur);

  const tdAct = document.createElement('td'); tdAct.style.padding = '8px 4px'; tdAct.style.textAlign = 'center'; tdAct.style.verticalAlign = 'middle'; tdAct.style.width = '80px';
  const del = document.createElement('button'); del.type='button'; del.className='btn small danger'; del.textContent='Eliminar'; del.setAttribute('aria-label','Eliminar receta'); del.style.marginLeft = '6px'; del.addEventListener('click', ()=>{ tr.remove(); });
  tdAct.appendChild(del);

      tr.appendChild(hidId);
      tr.appendChild(tdMed);
      tr.appendChild(tdInd);
      tr.appendChild(tdDur);
      tr.appendChild(tdAct);

      tbody.appendChild(tr);
      return tr;
    }

    // Prefill from serverRecetas
    if (serverRecetas && serverRecetas.length > 0) {
      serverRecetas.forEach(r => {
        renderRecetaRow(r);
      });
    }

    if (addRecetaBtn) {
      addRecetaBtn.addEventListener('click', function(){
        if (!recetasContainer) return;
        try{
          const tr = renderRecetaRow({});
          const sel = tr.querySelector('select'); if (sel) sel.focus();
        }catch(err){
          console.error('Error renderizando fila de receta, fallback', err);
          const tbody = recetasContainer.querySelector('tbody') || recetasContainer;
          const row = document.createElement('tr'); row.className='receta-row';
          row.innerHTML = '<td style="padding:8px 6px"><select name="recetas[][id_medicamento]" class="input"></select></td><td style="padding:8px 6px"><textarea name="recetas[][indicacion]" class="input" style="width:100%;height:48px;resize:none;box-sizing:border-box"></textarea></td><td style="padding:8px 4px;width:120px;vertical-align:middle"><input type="text" name="recetas[][duracion]" class="input" style="width:100%;box-sizing:border-box"></td><td style="padding:8px 4px;text-align:center;vertical-align:middle;width:80px"><button type="button" class="btn small danger">Eliminar</button></td>';
          // populate select options from medicamentosList so fallback rows are usable
          const sel = row.querySelector('select[name="recetas[][id_medicamento]"]');
          if (sel) {
            const empty = document.createElement('option'); empty.value=''; empty.textContent='— Selecciona medicamento —'; sel.appendChild(empty);
            medicamentosList.forEach(m => {
              const o = document.createElement('option'); o.value = m.id; o.textContent = m.nombre; sel.appendChild(o);
            });
          }
          tbody.appendChild(row);
          const del = row.querySelector('button'); if (del) del.addEventListener('click', ()=> row.remove());
        }
      });
    }

    // Construir array asociativo de recetas a partir del DOM
    function collectRecetas(){
      // tr.receta-row or div.receta-row both supported
      const rows = Array.from(document.querySelectorAll('.receta-row'));
      const out = rows.map(r => {
        const idEl = r.querySelector('input[name="recetas[][id]"]');
        const medEl = r.querySelector('select[name="recetas[][id_medicamento]"]');
        const indEl = r.querySelector('textarea[name="recetas[][indicacion]"]');
        const durEl = r.querySelector('input[name="recetas[][duracion]"]');
        return {
          id: idEl ? (idEl.value || '') : '',
          id_medicamento: medEl ? (medEl.value || '') : '',
          indicacion: indEl ? (indEl.value || '').trim() : '',
          duracion: durEl ? (durEl.value || '').trim() : ''
        };
      }).filter(r => !(r.id === '' && r.id_medicamento === '' && r.indicacion === '' && r.duracion === ''));
      return out;
    }

    // Antes de enviar el formulario serializamos el array en recetas_payload
    const formEl = document.querySelector('form[action^="/citas/"]');
    if (formEl) {
      formEl.addEventListener('submit', function(e){
        try{
          const payload = collectRecetas();
          const hidden = document.getElementById('recetas_payload');
          if (hidden) hidden.value = JSON.stringify(payload);
        }catch(err){
          console.error('Error construyendo recetas payload', err);
        }
      });
    }

  })();
  </script>
<?php endif; ?>
