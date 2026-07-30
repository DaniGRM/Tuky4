// ── Constantes ────────────────────────────────────────────────────────────────
const ROUTINES = ['PECHO', 'ESPALDA', 'PIERNAS'];
const ROUTINE_META = {
  PECHO:   { label: 'Pecho',   cls: 'rb-pecho'   },
  ESPALDA: { label: 'Espalda', cls: 'rb-espalda' },
  PIERNAS: { label: 'Piernas', cls: 'rb-piernas' },
};
const REP_PATTERNS = {
  2: ['12-10', '10-10', '12-12', '8-8', '10-8'],
  3: ['12-10-10', '10-10-10', '12-12-10', '10-10-8', '8-8-8'],
  4: ['12-10-10-8', '12-12-10-10', '10-10-10-10', '10-10-8-8', '8-8-8-8'],
  5: ['12-10-10-10-8', '10-10-10-10-10', '12-10-10-8-8', '10-10-10-8-8'],
};

// ── Estado global ─────────────────────────────────────────────────────────────
let activeFilter  = localStorage.getItem('gymtrack_filter') || 'TODOS';
let lastExercises = [];
let currentWeight = 20;
let currentSeries = 4;
let currentReps   = '';
let historyChart  = null;

// ── Utils ─────────────────────────────────────────────────────────────────────
function api(body) {
  return fetch(location.pathname, {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(body),
  }).then(r => r.json());
}

function toast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2400);
}

function openModal(id)          { document.getElementById(id).classList.add('open'); }
function closeModal(id)         { document.getElementById(id).classList.remove('open'); }
function closeOnBg(e, id)       { if (e.target === document.getElementById(id)) closeModal(id); }

function esc(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function formatDate(d) {
  if (!d) return '';
  const [y, m, day] = d.split('-');
  return `${day}/${m}/${y}`;
}

// ── Filtro por rutina ─────────────────────────────────────────────────────────
function setFilter(f) {
  activeFilter = f;
  localStorage.setItem('gymtrack_filter', f);
  document.querySelectorAll('#filter-pills .pill').forEach(p =>
    p.classList.toggle('selected', p.dataset.val === f)
  );
  renderExercises(lastExercises);
}

// ── Weight incrementer ────────────────────────────────────────────────────────
function changeWeight(delta) {
  currentWeight = Math.max(0, Math.round((currentWeight + delta) * 10) / 10);
  document.getElementById('weight-display').textContent =
    currentWeight % 1 === 0 ? currentWeight : currentWeight.toFixed(1);
  document.getElementById('rec-weight').value = currentWeight;
}

// ── Pill selectors ────────────────────────────────────────────────────────────
function selectPill(group, el) {
  el.closest('.pills').querySelectorAll('.pill').forEach(p => p.classList.remove('selected'));
  el.classList.add('selected');
  if (group === 'series') {
    currentSeries = parseInt(el.dataset.val);
    document.getElementById('rec-series').value = currentSeries;
    renderRepPills(currentSeries);
  } else if (group === 'routine') {
    document.getElementById('ex-routine').value = el.dataset.val;
  } else {
    currentReps = el.dataset.val;
    document.getElementById('rec-reps').value = currentReps;
  }
}

function renderRepPills(series) {
  const container = document.getElementById('reps-pills');
  const patterns  = REP_PATTERNS[series] || [];
  container.innerHTML = '';
  currentReps = '';
  document.getElementById('rec-reps').value = '';

  patterns.forEach((p, i) => {
    const btn     = document.createElement('button');
    btn.className = 'pill' + (i === 0 ? ' selected' : '');
    btn.dataset.val = p;
    btn.textContent = p;
    btn.onclick     = function () { selectPill('reps', this); };
    container.appendChild(btn);
  });
  if (patterns[0]) {
    currentReps = patterns[0];
    document.getElementById('rec-reps').value = patterns[0];
  }
}

// ── Ejercicios ────────────────────────────────────────────────────────────────
async function loadExercises() {
  const res = await api({ action: 'get_exercises' });
  if (!res.ok) return;
  lastExercises = res.data;
  renderExercises(res.data);
}

function renderExercises(data) {
  const grid  = document.getElementById('exercise-grid');
  const empty = document.getElementById('empty-state');

  const filtered = activeFilter === 'TODOS'
    ? data
    : data.filter(e => e.routine === activeFilter);

  grid.innerHTML = '';
  if (!filtered.length) { grid.appendChild(empty); return; }

  const groups = {};
  filtered.forEach(ex => {
    if (!groups[ex.routine]) groups[ex.routine] = [];
    groups[ex.routine].push(ex);
  });

  const order = activeFilter === 'TODOS'
    ? ROUTINES.filter(r => groups[r])
    : [activeFilter];

  order.forEach(routine => {
    if (!groups[routine]) return;
    const meta = ROUTINE_META[routine];

    const hdr = document.createElement('div');
    hdr.className = 'routine-header';
    hdr.innerHTML = `<span class="routine-badge ${meta.cls}">${meta.label}</span><span class="rh-line"></span>`;
    grid.appendChild(hdr);

    groups[routine].forEach(ex => {
      const card      = document.createElement('div');
      card.className  = 'card';
      const hasRecord = ex.weight !== null;
      const wFmt      = hasRecord
        ? (ex.weight % 1 === 0 ? ex.weight : parseFloat(ex.weight).toFixed(1))
        : 20;

      card.innerHTML = `
        <div class="card-name">${esc(ex.name)}</div>
        ${hasRecord ? `
          <div class="card-record">
            <span class="badge-green badge">${wFmt} kg</span>
            <span class="badge">${ex.series} × ${esc(ex.rep_pattern)}</span>
          </div>
          <div class="card-date">${formatDate(ex.date)}</div>
        ` : '<div class="card-empty">Sin registros aún</div>'}
        <div class="card-actions">
          <button class="btn btn-primary btn-sm"
            onclick="openRecordModal(${ex.id},'${esc(ex.name)}',${hasRecord ? ex.weight : 20},${hasRecord ? ex.series : 4},'${hasRecord ? esc(ex.rep_pattern) : ''}')">
            + Registro
          </button>
          <button class="btn btn-ghost btn-sm" onclick="openHistory(${ex.id},'${esc(ex.name)}')">Historial</button>
          <button class="btn btn-ghost btn-sm btn-icon" title="Eliminar" onclick="deleteExercise(${ex.id},'${esc(ex.name)}')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2.5">
              <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
              <path d="M10 11v6M14 11v6M9 6V4h6v2"/>
            </svg>
          </button>
        </div>`;
      grid.appendChild(card);
    });
  });
}

// ── Modal registro ────────────────────────────────────────────────────────────
function openRecordModal(id, name, lastWeight, lastSeries, lastReps) {
  document.getElementById('rec-exercise-id').value  = id;
  document.getElementById('record-title').textContent = name;
  document.getElementById('rec-date').value = new Date().toISOString().split('T')[0];

  currentWeight = lastWeight || 20;
  document.getElementById('weight-display').textContent =
    currentWeight % 1 === 0 ? currentWeight : parseFloat(currentWeight).toFixed(1);
  document.getElementById('rec-weight').value = currentWeight;

  currentSeries = lastSeries || 4;
  document.querySelectorAll('#series-pills .pill').forEach(p =>
    p.classList.toggle('selected', parseInt(p.dataset.val) === currentSeries)
  );
  document.getElementById('rec-series').value = currentSeries;

  renderRepPills(currentSeries);
  if (lastReps) {
    const btn = [...document.querySelectorAll('#reps-pills .pill')]
      .find(p => p.dataset.val === lastReps);
    if (btn) {
      document.querySelectorAll('#reps-pills .pill').forEach(p => p.classList.remove('selected'));
      btn.classList.add('selected');
      currentReps = lastReps;
      document.getElementById('rec-reps').value = lastReps;
    }
  }
  openModal('modal-record');
}

// ── CRUD ─────────────────────────────────────────────────────────────────────
async function addExercise() {
  const name    = document.getElementById('ex-name').value.trim();
  const routine = document.getElementById('ex-routine').value || 'PECHO';
  if (!name) { toast('Escribe un nombre'); return; }
  const res = await api({ action: 'add_exercise', name, routine });
  if (!res.ok) { toast(res.msg || 'Error'); return; }
  document.getElementById('ex-name').value = '';
  document.querySelectorAll('#routine-pills .pill')
    .forEach(p => p.classList.toggle('selected', p.dataset.val === 'PECHO'));
  document.getElementById('ex-routine').value = 'PECHO';
  closeModal('modal-exercise');
  toast('Ejercicio añadido');
  loadExercises();
}

async function addRecord() {
  const exercise_id = document.getElementById('rec-exercise-id').value;
  const weight      = document.getElementById('rec-weight').value;
  const series      = document.getElementById('rec-series').value;
  const rep_pattern = document.getElementById('rec-reps').value;
  const date        = document.getElementById('rec-date').value;
  if (!rep_pattern) { toast('Elige un patrón de repeticiones'); return; }
  const res = await api({ action: 'add_record', exercise_id, weight, series, rep_pattern, date });
  if (!res.ok) { toast(res.msg || 'Error'); return; }
  closeModal('modal-record');
  toast('Registro guardado ✓');
  loadExercises();
}

async function deleteExercise(id, name) {
  if (!confirm(`¿Eliminar "${name}" y todos sus registros?`)) return;
  const res = await api({ action: 'delete_exercise', exercise_id: id });
  if (res.ok) { toast('Ejercicio eliminado'); loadExercises(); }
}

async function deleteRecord(rid) {
  if (!confirm('¿Eliminar este registro?')) return;
  const res = await api({ action: 'delete_record', record_id: rid });
  if (res.ok) {
    toast('Registro eliminado');
    loadExercises();
    closeModal('modal-history');
  }
}

// ── Historial + gráfica ───────────────────────────────────────────────────────
function computeVolume(r) {
  const reps = r.rep_pattern.split('-').map(Number).filter(n => n > 0);
  return Math.round(parseFloat(r.weight) * reps.reduce((a, b) => a + b, 0) * 10) / 10;
}

async function openHistory(id, name) {
  document.getElementById('history-title').textContent = name;
  const res     = await api({ action: 'get_history', exercise_id: id });
  const list    = document.getElementById('history-list');
  const section = document.getElementById('chart-section');
  list.innerHTML = '';

  if (!res.ok || !res.data.length) {
    section.style.display = 'none';
    list.innerHTML = '<p style="color:var(--muted);font-size:.9rem">Sin registros aún.</p>';
    openModal('modal-history');
    return;
  }

  const chrono  = [...res.data].reverse();
  const volumes = chrono.map(computeVolume);
  const weights = chrono.map(r => parseFloat(r.weight));
  const labels  = chrono.map(r => formatDate(r.date));

  // Delta progreso
  const first = volumes[0], last = volumes[volumes.length - 1];
  const pct   = first > 0 ? Math.round((last - first) / first * 100) : 0;
  const deltaEl = document.getElementById('chart-delta');
  if (volumes.length < 2) {
    deltaEl.textContent = ''; deltaEl.className = 'chart-delta';
  } else if (pct > 0) {
    deltaEl.textContent = '▲ +' + pct + '%'; deltaEl.className = 'chart-delta up';
  } else if (pct < 0) {
    deltaEl.textContent = '▼ ' + pct + '%'; deltaEl.className = 'chart-delta down';
  } else {
    deltaEl.textContent = '= Sin cambio'; deltaEl.className = 'chart-delta flat';
  }

  // Gráfica
  section.style.display = '';
  if (historyChart) { historyChart.destroy(); historyChart = null; }

  const ctx  = document.getElementById('history-chart').getContext('2d');
  const grad = ctx.createLinearGradient(0, 0, 0, 190);
  grad.addColorStop(0, 'rgba(108,99,255,0.4)');
  grad.addColorStop(1, 'rgba(108,99,255,0)');

  historyChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Volumen',
          data: volumes,
          borderColor: '#6c63ff',
          backgroundColor: grad,
          borderWidth: 2.5,
          pointBackgroundColor: '#6c63ff',
          pointBorderColor: '#1a1d27',
          pointBorderWidth: 2,
          pointRadius: 5,
          pointHoverRadius: 7,
          fill: true,
          tension: 0.35,
          yAxisID: 'yVol',
        },
        {
          label: 'Peso',
          data: weights,
          borderColor: '#ff6584',
          backgroundColor: 'transparent',
          borderWidth: 2,
          borderDash: [5, 3],
          pointBackgroundColor: '#ff6584',
          pointBorderColor: '#1a1d27',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          fill: false,
          tension: 0.35,
          yAxisID: 'yKg',
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: {
          display: true,
          labels: { color: '#8892a4', font: { size: 11 }, boxWidth: 12, boxHeight: 2, padding: 12, usePointStyle: true, pointStyle: 'line' },
        },
        tooltip: {
          backgroundColor: '#22263a',
          borderColor: '#2e3348',
          borderWidth: 1,
          titleColor: '#e2e8f0',
          bodyColor: '#8892a4',
          padding: 10,
          callbacks: {
            title: (items) => labels[items[0].dataIndex],
            label: (item) => {
              if (item.dataset.label === 'Peso') return `Peso: ${item.parsed.y} kg`;
              const r    = chrono[item.dataIndex];
              const w    = parseFloat(r.weight) % 1 === 0 ? r.weight : parseFloat(r.weight).toFixed(1);
              const reps = r.rep_pattern.split('-').map(Number).reduce((a, b) => a + b, 0);
              return [`Volumen: ${item.parsed.y} kg·rep`, `  ${w} kg × ${reps} reps (${r.series} series)`];
            },
          },
        },
      },
      scales: {
        x: {
          grid:  { color: '#2e3348' },
          ticks: { color: '#8892a4', font: { size: 11 }, maxRotation: 35 },
        },
        yVol: {
          position: 'left',
          grid:  { color: '#2e3348' },
          ticks: { color: '#6c63ff', font: { size: 10 } },
          beginAtZero: false,
          title: { display: true, text: 'Vol', color: '#6c63ff', font: { size: 10 } },
        },
        yKg: {
          position: 'right',
          grid:  { drawOnChartArea: false },
          ticks: { color: '#ff6584', font: { size: 10 } },
          beginAtZero: false,
          title: { display: true, text: 'kg', color: '#ff6584', font: { size: 10 } },
        },
      },
    },
  });

  // Lista de registros
  res.data.forEach(r => {
    const div     = document.createElement('div');
    div.className = 'history-item';
    const w       = parseFloat(r.weight) % 1 === 0 ? r.weight : parseFloat(r.weight).toFixed(1);
    const vol     = computeVolume(r);
    div.innerHTML = `
      <span class="hi-date">${formatDate(r.date)}</span>
      <span class="hi-detail">
        <span class="badge-green badge">${w} kg</span>
        <span class="badge">${r.series} × ${esc(r.rep_pattern)}</span>
        <span style="font-size:.75rem;color:var(--muted)">${vol} kg·rep</span>
      </span>
      <span class="hi-action">
        <button class="btn btn-ghost btn-sm btn-icon" title="Eliminar" onclick="deleteRecord(${r.id})">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2.5">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
            <path d="M10 11v6M14 11v6M9 6V4h6v2"/>
          </svg>
        </button>
      </span>`;
    list.appendChild(div);
  });

  openModal('modal-history');
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.querySelectorAll('#filter-pills .pill').forEach(p =>
  p.classList.toggle('selected', p.dataset.val === activeFilter)
);
document.getElementById('ex-name').addEventListener('keydown', e => {
  if (e.key === 'Enter') addExercise();
});
renderRepPills(4);
loadExercises();
