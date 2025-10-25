<?php if(($_SESSION['user']['rol'] ?? '') !== 'doctor'): ?>
  <div class="alert mt-2">No tienes acceso a esta pantalla.</div>
<?php else: ?>
  <h1>Atender cita</h1>

  <style>
    .invalid { border-color: #e53e3e !important; box-shadow: 0 0 0 3px rgba(229,62,62,0.06); }
    .field-error { color: #e53e3e; font-size: 0.9em; margin-top: 4px; display: none; }
    .field-error.visible { display: block; }
    #client_error { background: #ffe6e6; border-left: 4px solid #e53e3e; padding: 8px; }
    .input-with-toggle { position: relative; }
    .input-with-toggle .toggle-btn { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 28px; height: 28px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:#666; background:transparent; border:none; }
    .input-with-toggle .toggle-btn:focus { outline: none; box-shadow: 0 0 0 3px rgba(0,0,0,0.06); }
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

  <div class="card">
    <div><strong>Paciente:</strong> <?= htmlspecialchars($a['paciente_nombre'] ?? ($a->paciente?->user?->nombre ?? '')) ?> <?= htmlspecialchars($a['paciente_apellido'] ?? ($a->paciente?->user?->apellido ?? '')) ?></div>
    <div><strong>Fecha:</strong> <?= htmlspecialchars($fmtDate($a['fecha'] ?? ($a->fecha ?? ''))) ?></div>
    <div><strong>Hora:</strong> <?= htmlspecialchars($fmtTime($a['hora_inicio'] ?? ($a->hora_inicio ?? ''))) ?></div>
    <div><strong>Motivo:</strong> <?= htmlspecialchars($a['razon'] ?? '') ?></div>
  </div>

  <form method="POST" action="/citas/<?= (int)$a['id'] ?>/attend">
    <input type="hidden" name="recetas_payload" id="recetas_payload" value="">
    <div id="client_error" class="alert error mt-2" style="display:none"></div>

    <div class="row" style="position:relative;">
      <label class="label">Diagnóstico(s)</label>
      <div id="diagnosticos_container">
        <!-- filas dinámicas de diagnóstico serán insertadas aquí -->
      </div>
      <div style="margin-top:8px">
        <button type="button" id="add_diagnostico_btn" class="btn">Agregar diagnóstico</button>
      </div>
      <div id="err_diagnostico" class="field-error"></div>
    </div>

    <div class="row">
      <label class="label">Observaciones</label>
      <textarea class="input" style="resize: none; height:80px" name="observaciones"><?= htmlspecialchars($c['observaciones'] ?? ($c->observaciones ?? '')) ?></textarea>
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
          $sel = $c['estado_postconsulta'] ?? ($c->estado_postconsulta ?? '');
        ?>
        <option value="">— Selecciona —</option>
        <?php foreach ($opts as $o): ?>
          <option value="<?= htmlspecialchars($o) ?>" <?= $sel === $o ? 'selected' : '' ?>><?= htmlspecialchars($o) ?></option>
        <?php endforeach; ?>
      </select>
      <div id="err_estado" class="field-error"></div>
    </div>

    <div class="row">
      <button class="btn primary" type="submit" name="marcar_estado" value="atendido">Marcar como Atendido</button>
    </div>
  </form>

  <script>
  (function(){
    // Diagnósticos dinámicos: permite múltiples diagnósticos (mínimo 1)
    const serverDiagnosticos = <?= json_encode($diagnosticos ?? []) ?> || [];
    const diagnosticosContainer = document.getElementById('diagnosticos_container');
    const addBtn = document.getElementById('add_diagnostico_btn');

    function createDropdown(){
      const d = document.createElement('div');
      d.className = 'dropdown';
      d.style.display = 'none';
      d.style.position = 'absolute';
      d.style.zIndex = '1000';
      d.style.left = '0';
      d.style.right = '0';
      d.style.background = '#fff';
      d.style.border = '1px solid #ddd';
      d.style.maxHeight = '200px';
      d.style.overflow = 'auto';
      return d;
    }

    function createRow(initial){
      initial = initial || {};
      const wrapper = document.createElement('div');
      wrapper.className = 'diagnostico-row';
      wrapper.style.position = 'relative';
      wrapper.style.marginBottom = '8px';

      const inputWrap = document.createElement('div'); inputWrap.className = 'input-with-toggle';
      const input = document.createElement('input'); input.type = 'text'; input.className = 'input diagnostico-input'; input.autocomplete = 'on';
      input.value = initial.nombre_enfermedad || initial.nombre || '';
      input.setAttribute('aria-haspopup','listbox'); input.setAttribute('aria-expanded','false');
      const toggle = document.createElement('button'); toggle.type='button'; toggle.className='toggle-btn'; toggle.title='Mostrar lista de diagnósticos';
      toggle.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 9l6 6l6 -6" /></svg>';
      const hid = document.createElement('input'); hid.type='hidden'; hid.name='diagnosticos[][id]'; hid.className='diagnostico-id'; hid.value = initial.id || initial.diagnostico_id || '';

      inputWrap.appendChild(input); inputWrap.appendChild(toggle); inputWrap.appendChild(hid);
      wrapper.appendChild(inputWrap);

      const dropdown = createDropdown(); wrapper.appendChild(dropdown);

      const actions = document.createElement('div'); actions.style.marginTop='4px';
      const remove = document.createElement('button'); remove.type='button'; remove.className='btn small danger'; remove.textContent='Eliminar'; remove.style.marginLeft='6px';
      remove.addEventListener('click', ()=>{ wrapper.remove(); });
      actions.appendChild(remove);
      wrapper.appendChild(actions);

      // attach behavior
      attachAutocomplete(input, hid, dropdown, toggle);

      return wrapper;
    }

    function attachAutocomplete(input, hid, dropdown, toggle){
      let items = [];
      let selected = -1;
      let t = null;

      function render(){
        dropdown.innerHTML = '';
        if (!items || items.length === 0) { dropdown.style.display = 'none'; return; }
        items.forEach((it, idx) => {
          const div = document.createElement('div'); div.className='dropdown-item';
          const label = (typeof it === 'string') ? it : (it.nombre_enfermedad ?? it.nombre ?? '');
          div.textContent = label; div.style.padding='6px 8px'; div.style.cursor='pointer';
          if (idx === selected) div.style.background = '#eef';
          div.addEventListener('mousedown', function(e){ e.preventDefault(); pick(idx); });
          dropdown.appendChild(div);
        });
        dropdown.style.display = 'block';
      }

      function pick(idx){
        if (idx < 0 || idx >= items.length) return;
        const it = items[idx];
        if (typeof it === 'string') { input.value = it; hid.value = ''; }
        else { input.value = it.nombre_enfermedad ?? it.nombre ?? ''; hid.value = it.id ?? ''; }
        hide();
      }

      function hide(){ dropdown.style.display='none'; selected=-1; items=[]; }

      async function search(q){
        if (serverDiagnosticos && serverDiagnosticos.length > 0){
          items = serverDiagnosticos.filter(d => (d.nombre_enfermedad||d.nombre||'').toLowerCase().includes((q||'').toLowerCase())); selected=-1; render(); return;
        }
        if (!q || q.length < 1) { hide(); return; }
        try{
          const url = `/api/v1/diagnosticos?q=${encodeURIComponent(q)}`;
          const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
          if (!res.ok) { hide(); return; }
          const json = await res.json(); items = json.data || []; selected=-1; render();
        }catch(err){ console.error('Error buscando diagnosticos', err); hide(); }
      }

      async function fetchAll(){
        try{
          if (serverDiagnosticos && serverDiagnosticos.length > 0){ items = serverDiagnosticos; selected=-1; render(); return; }
          const res = await fetch('/api/v1/diagnosticos', { headers:{ 'Accept':'application/json' } });
          if (!res.ok) { hide(); return; }
          const json = await res.json(); items = json.data || []; selected=-1; render();
        }catch(err){ console.error('Error fetching all diagnosticos', err); hide(); }
      }

      input.addEventListener('input', function(e){ const v=(e.target.value||'').trim(); hid.value=''; const cerr = document.getElementById('client_error'); if (cerr) cerr.style.display='none'; if (t) clearTimeout(t); t=setTimeout(()=>search(v),200); });
      input.addEventListener('focus', function(){ const v=(input.value||'').trim(); if (v.length===0) fetchAll(); });
      input.addEventListener('click', function(){ const v=(input.value||'').trim(); if (v.length===0) fetchAll(); });

      toggle.addEventListener('click', function(){ const expanded = input.getAttribute('aria-expanded') === 'true'; if (expanded){ hide(); input.setAttribute('aria-expanded','false'); } else { fetchAll(); input.setAttribute('aria-expanded','true'); input.focus(); } });
      toggle.addEventListener('keydown', function(e){ if (e.key==='Enter' || e.key===' ') { e.preventDefault(); toggle.click(); } });

      input.addEventListener('keydown', function(e){ if (dropdown.style.display==='none') return; if (e.key==='ArrowDown'){ e.preventDefault(); selected = Math.min(selected+1, items.length-1); render(); } else if (e.key==='ArrowUp'){ e.preventDefault(); selected = Math.max(selected-1, 0); render(); } else if (e.key==='Enter'){ if (selected>=0){ e.preventDefault(); pick(selected); } } else if (e.key==='Escape'){ hide(); } });

      document.addEventListener('click', function(e){ if (!input.contains(e.target) && !dropdown.contains(e.target) && !toggle.contains(e.target)) { hide(); } });
    }

    // Prefill: si hay diagnósticos guardados en servidor (detalle_consulta)
    const serverDiagnosticosPrefill = <?= json_encode($consulta_diagnosticos ?? ($c['diagnosticos'] ?? ($c->diagnosticos ?? []))) ?> || [];
    if (Array.isArray(serverDiagnosticosPrefill) && serverDiagnosticosPrefill.length > 0) {
      serverDiagnosticosPrefill.forEach(function(d){ diagnosticosContainer.appendChild(createRow({id:d.id, nombre_enfermedad:d.nombre})); });
    } else {
      // al menos una fila vacía
      diagnosticosContainer.appendChild(createRow());
    }

    if (addBtn) addBtn.addEventListener('click', function(){ diagnosticosContainer.appendChild(createRow()); });

    // Form submit validation: exigir al menos un diagnóstico con id
    (function(){
      const form = document.querySelector('form[action^="/citas/"]'); if (!form) return;
      form.addEventListener('submit', function(e){
        // limpiar errores previos
        const errDiag = document.getElementById('err_diagnostico'); if (errDiag){ errDiag.textContent=''; errDiag.classList.remove('visible'); }
        const diagRows = Array.from(document.querySelectorAll('.diagnostico-row'));
        const hasValid = diagRows.some(r => { const hid = r.querySelector('.diagnostico-id'); return hid && (hid.value||'').trim() !== ''; });
        if (!hasValid){ e.preventDefault(); if (errDiag){ errDiag.textContent = 'Debes seleccionar al menos un diagnóstico existente de la lista.'; errDiag.classList.add('visible'); }
          // focus on first input
          const firstInput = document.querySelector('.diagnostico-input'); if (firstInput) firstInput.focus();
        }
      });
    })();

    // ---------- Recetas dynamic UI (original code preserved) ----------
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
      // create a table row with cells for medicamento, indicacion, duracion and actions
      const tbody = recetasContainer.querySelector('tbody') || recetasContainer;
      const tr = document.createElement('tr'); tr.className = 'receta-row';

      // hidden id (preserva id si viene)
      const hidId = document.createElement('input'); hidId.type = 'hidden'; hidId.name = 'recetas[][id]'; hidId.value = data?.id ? data.id : '';

  const tdMed = document.createElement('td'); tdMed.style.padding = '8px 6px'; tdMed.style.width = '280px'; tdMed.style.verticalAlign = 'middle';
  const sel = createMedicamentoSelect(`recetas[][id_medicamento]`, data?.id_medicamento ?? data?.medicamento?.id ?? ''); sel.style.width = '100%'; sel.style.boxSizing = 'border-box';
  tdMed.appendChild(sel);

  const tdInd = document.createElement('td'); tdInd.style.padding = '8px 6px'; tdInd.style.verticalAlign = 'middle';
  const ind = document.createElement('textarea'); ind.name = `recetas[][indicacion]`; ind.className='input'; ind.style.width='100%'; ind.style.height='48px'; ind.style.resize='none'; ind.style.boxSizing='border-box'; ind.value = data?.indicacion ?? '';
  tdInd.appendChild(ind);

  const tdDur = document.createElement('td'); tdDur.style.padding = '8px 6px'; tdDur.style.width = '140px'; tdDur.style.verticalAlign = 'middle';
  const dur = document.createElement('input'); dur.type='text'; dur.name = `recetas[][duracion]`; dur.className='input'; dur.style.width='100%'; dur.style.boxSizing='border-box'; dur.value = data?.duracion ?? '';
  tdDur.appendChild(dur);
  // tighten spacing between duration and action columns
  tdDur.style.padding = '8px 4px'; tdDur.style.width = '120px';

  const tdAct = document.createElement('td'); tdAct.style.padding = '8px 4px'; tdAct.style.textAlign = 'center'; tdAct.style.verticalAlign = 'middle'; tdAct.style.width = '80px';
  const del = document.createElement('button'); del.type = 'button'; del.className = 'btn small danger'; del.textContent = 'Eliminar'; del.setAttribute('aria-label','Eliminar receta'); del.style.marginLeft = '6px'; del.addEventListener('click', ()=>{ tr.remove(); });
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
          row.innerHTML = '<td style="padding:8px 6px"><select name="recetas[][id_medicamento]" class="input"><option value="">— Selecciona medicamento —</option></select></td><td style="padding:8px 6px"><textarea name="recetas[][indicacion]" class="input" style="width:100%;height:48px;resize:none;box-sizing:border-box"></textarea></td><td style="padding:8px 4px;width:120px;vertical-align:middle"><input type="text" name="recetas[][duracion]" class="input" style="width:100%;box-sizing:border-box"></td><td style="padding:8px 4px;text-align:center;vertical-align:middle;width:80px"><button type="button" class="btn small danger">Eliminar</button></td>';
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
        // select may be nested inside td
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
