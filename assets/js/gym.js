// ── Constantes ────────────────────────────────────────────────────────────────
const ROUTINES = ['PECHO', 'ESPALDA', 'PIERNAS'];
const ROUTINE_META = {
  PECHO: { label: 'Pecho', cls: 'rb-pecho' },
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
let activeFilter = localStorage.getItem('gymtrack_filter') || 'TODOS';
let lastExercises = [];
let currentWeight = 20;
let currentSeries = 4;
let currentReps = '';
let historyChart = null;
let bikeHistoryChart = null;
let runningHistoryChart = null;
let bikeActivities = [];
let bikeLoaded = false;
let runningActivities = [];
let runningLoaded = false;
let sports = [];
let sportsLoaded = false;
let gymPreviousVolume = null;
let bikePreviousVolume = null;
let runningPreviousVolume = null;
let bikeDurationManual = false;
let runningDurationManual = false;
let currentSteps = 0;
let currentDailyWeight = 0;
let stepsLoaded = false;
let userInfoLoaded = false;
let dailySaveInFlight = false;
let queuedDailyPayload = null;
let userInfoSaveInFlight = false;

function setSection(section) {
  document.querySelectorAll('.section-switch .pill').forEach(p =>
    p.classList.toggle('selected', p.dataset.val === section)
  );
  document.getElementById('gym-section').style.display = section === 'GYM' ? '' : 'none';
  document.getElementById('bike-section').style.display = section === 'BIKE' ? '' : 'none';
  document.getElementById('running-section').style.display = section === 'RUNNING' ? '' : 'none';
  document.getElementById('sports-section').style.display = section === 'SPORTS' ? '' : 'none';
  document.getElementById('steps-section').style.display = section === 'STEPS' ? '' : 'none';

  if (section === 'BIKE' && !bikeLoaded) {
    loadBikeActivities();
  }
  if (section === 'RUNNING' && !runningLoaded) {
    loadRunningActivities();
  }
  if (section === 'SPORTS' && !sportsLoaded) {
    loadSports();
  }
  if (section === 'STEPS' && !stepsLoaded) {
    loadSteps();
  }
  if (section === 'STEPS' && !userInfoLoaded) {
    loadUserInfo();
  }
}

function todayIsoDate() {
  return new Date().toISOString().split('T')[0];
}

function renderStepsValue() {
  const display = document.getElementById('steps-display');
  if (!display) return;
  display.textContent = String(Math.max(0, parseInt(currentSteps, 10) || 0));
}

function renderDailyWeightValue() {
  const display = document.getElementById('daily-weight-display');
  if (!display) return;
  const weight = Math.max(0, Number(currentDailyWeight || 0));
  display.textContent = weight.toFixed(1);
}

function changeSteps(delta) {
  const step = parseInt(delta, 10) || 0;
  currentSteps = Math.max(0, (parseInt(currentSteps, 10) || 0) + step);
  renderStepsValue();
  saveDailyRegisterImmediate();
}

function changeDailyWeight(delta) {
  const step = Number(delta) || 0;
  const next = Math.round((Math.max(0, Number(currentDailyWeight || 0)) + step) * 10) / 10;
  currentDailyWeight = Math.max(0, next);
  renderDailyWeightValue();
  saveDailyRegisterImmediate();
}

async function loadSteps() {
  const res = await api('/api/gym/steps/get', { date: todayIsoDate() });
  if (!res.ok) {
    toast(res.msg || 'Error cargando pasos');
    return;
  }

  currentSteps = Math.max(0, parseInt(res.data?.steps, 10) || 0);
  currentDailyWeight = Math.max(0, Number(res.data?.weight || 0));
  stepsLoaded = true;
  renderStepsValue();
  renderDailyWeightValue();
}

function renderUserInfoForm(data) {
  const sexEl = document.getElementById('ui-sex');
  const ageEl = document.getElementById('ui-age');
  const heightEl = document.getElementById('ui-height-cm');
  const stepsGoalEl = document.getElementById('ui-steps-goal');
  const deficitEl = document.getElementById('ui-deficit-goal');
  if (!sexEl || !ageEl || !heightEl || !stepsGoalEl || !deficitEl) return;

  sexEl.value = (data.sex === 'female' ? 'female' : 'male');
  ageEl.value = String(parseInt(data.age, 10) || 30);
  heightEl.value = String(Number(data.height_cm || 170));
  stepsGoalEl.value = String(Math.max(0, parseInt(data.daily_steps_goal, 10) || 0));
  deficitEl.value = String(parseInt(data.calorie_deficit_goal, 10) || 0);
}

async function loadUserInfo() {
  const res = await api('/api/gym/user-info/get');
  if (!res.ok) {
    toast(res.msg || 'Error cargando perfil');
    return;
  }

  renderUserInfoForm(res.data || {});
  userInfoLoaded = true;
}

async function saveUserInfo() {
  if (userInfoSaveInFlight) return;

  const sex = (document.getElementById('ui-sex')?.value || 'male').trim();
  const age = parseInt(document.getElementById('ui-age')?.value || '0', 10) || 0;
  const height_cm = Number(document.getElementById('ui-height-cm')?.value || 0);
  const daily_steps_goal = Math.max(0, parseInt(document.getElementById('ui-steps-goal')?.value || '0', 10) || 0);
  const calorie_deficit_goal = parseInt(document.getElementById('ui-deficit-goal')?.value || '0', 10) || 0;

  if (!['male', 'female'].includes(sex)) {
    toast('Sexo invalido');
    return;
  }
  if (age <= 0 || age > 120) {
    toast('Edad invalida');
    return;
  }
  if (height_cm <= 0 || height_cm > 260) {
    toast('Altura invalida');
    return;
  }

  userInfoSaveInFlight = true;
  const res = await api('/api/gym/user-info/save', {
    sex,
    age,
    height_cm,
    daily_steps_goal,
    calorie_deficit_goal,
  });
  userInfoSaveInFlight = false;

  if (!res.ok) {
    toast(res.msg || 'Error guardando perfil');
    return;
  }

  renderUserInfoForm(res.data || {
    sex,
    age,
    height_cm,
    daily_steps_goal,
    calorie_deficit_goal,
  });
  toast('Perfil guardado ✓');
}

async function saveSteps() {
  const steps = Math.max(0, parseInt(currentSteps, 10) || 0);
  const weight = Math.max(0, Math.round(Number(currentDailyWeight || 0) * 10) / 10);
  const res = await api('/api/gym/steps/save', {
    date: todayIsoDate(),
    steps,
    weight,
  });

  if (!res.ok) {
    toast(res.msg || 'Error guardando pasos');
    return;
  }

  currentSteps = Math.max(0, parseInt(res.data?.steps, 10) || 0);
  currentDailyWeight = Math.max(0, Number(res.data?.weight || 0));
  renderStepsValue();
  renderDailyWeightValue();
}

async function saveDailyRegisterImmediate() {
  const payload = {
    steps: Math.max(0, parseInt(currentSteps, 10) || 0),
    weight: Math.max(0, Math.round(Number(currentDailyWeight || 0) * 10) / 10),
  };
  queuedDailyPayload = payload;

  if (dailySaveInFlight) return;

  while (queuedDailyPayload !== null) {
    const target = queuedDailyPayload;
    queuedDailyPayload = null;
    dailySaveInFlight = true;

    const res = await api('/api/gym/steps/save', {
      date: todayIsoDate(),
      steps: target.steps,
      weight: target.weight,
    });

    dailySaveInFlight = false;

    if (!res.ok) {
      toast(res.msg || 'Error guardando pasos');
      return;
    }

    currentSteps = Math.max(0, parseInt(res.data?.steps, 10) || 0);
    currentDailyWeight = Math.max(0, Number(res.data?.weight || 0));
    renderStepsValue();
    renderDailyWeightValue();
  }
}

function toNum(n) {
  const value = Number(n);
  return Number.isFinite(value) ? value : 0;
}

function formatNum(n, digits = 2) {
  const value = toNum(n);
  return value % 1 === 0 ? String(value) : value.toFixed(digits);
}

function formatDuration(minutes) {
  const total = Math.max(0, parseInt(minutes, 10) || 0);
  const h = Math.floor(total / 60);
  const m = total % 60;
  if (h === 0) return `${m} min`;
  return `${h} h ${m.toString().padStart(2, '0')} min`;
}

function computeBikeVolume(r) {
  const distance = Math.max(0, toNum(r.distance));
  const speed = Math.max(0, toNum(r.avg_speed));
  const elevationFactor = 1 + (Math.max(0, toNum(r.elevation)) / 1000);
  return Math.round(distance * speed * elevationFactor * 10) / 10;
}

function computeRunningVolume(r) {
  const distance = Math.max(0, toNum(r.distance));
  const speed = Math.max(0, toNum(r.avg_speed));
  const elevationFactor = 1 + (Math.max(0, toNum(r.elevation)) / 1000);
  return Math.round(distance * speed * elevationFactor * 10) / 10;
}

function sumRepsFromPattern(pattern) {
  return String(pattern || '')
    .split('-')
    .map(Number)
    .filter(n => n > 0)
    .reduce((a, b) => a + b, 0);
}

function setPreviewDelta(el, current, previous) {
  if (!el) return;

  if (previous === null || previous === undefined || previous <= 0) {
    el.textContent = 'Base';
    el.className = 'chart-delta';
    return;
  }

  const pct = Math.round(((current - previous) / previous) * 100);
  if (pct > 0) {
    el.textContent = '▲ +' + pct + '%';
    el.className = 'chart-delta up';
  } else if (pct < 0) {
    el.textContent = '▼ ' + pct + '%';
    el.className = 'chart-delta down';
  } else {
    el.textContent = '= 0%';
    el.className = 'chart-delta flat';
  }
}

function computeDurationMinutes(distance, speed) {
  const d = Math.max(0, toNum(distance));
  const s = Math.max(0, toNum(speed));
  if (d <= 0 || s <= 0) return 0;
  return Math.max(1, Math.round((d / s) * 60));
}

function refreshBikeDurationAuto() {
  if (bikeDurationManual) return;
  const distance = document.getElementById('bike-distance')?.value || 0;
  const speed = document.getElementById('bike-avg-speed')?.value || 0;
  const duration = computeDurationMinutes(distance, speed);
  const durationEl = document.getElementById('bike-duration');
  if (!durationEl) return;
  durationEl.value = duration > 0 ? String(duration) : '';
}

function refreshRunningDurationAuto() {
  if (runningDurationManual) return;
  const distance = document.getElementById('running-distance')?.value || 0;
  const speed = document.getElementById('running-avg-speed')?.value || 0;
  const duration = computeDurationMinutes(distance, speed);
  const durationEl = document.getElementById('running-duration');
  if (!durationEl) return;
  durationEl.value = duration > 0 ? String(duration) : '';
}

function updateGymProgressPreview() {
  const textEl = document.getElementById('gym-preview-text');
  const deltaEl = document.getElementById('gym-progress-delta');
  if (!textEl || !deltaEl) return;

  const weight = toNum(document.getElementById('rec-weight')?.value || 0);
  const repsTotal = sumRepsFromPattern(document.getElementById('rec-reps')?.value || '');
  const volume = Math.round(weight * repsTotal * 10) / 10;

  textEl.textContent = `Volumen estimado: ${formatNum(volume, 1)} kg·rep`;
  setPreviewDelta(deltaEl, volume, gymPreviousVolume);
}

function updateBikeProgressPreview() {
  const textEl = document.getElementById('bike-preview-text');
  const deltaEl = document.getElementById('bike-progress-delta');
  if (!textEl || !deltaEl) return;

  const volume = computeBikeVolume({
    distance: document.getElementById('bike-distance')?.value || 0,
    avg_speed: document.getElementById('bike-avg-speed')?.value || 0,
    duration: document.getElementById('bike-duration')?.value || 0,
    elevation: document.getElementById('bike-elevation')?.value || 0,
  });

  textEl.textContent = `Volumen estimado: ${formatNum(volume, 1)}`;
  setPreviewDelta(deltaEl, volume, bikePreviousVolume);
}

function updateRunningProgressPreview() {
  const textEl = document.getElementById('running-preview-text');
  const deltaEl = document.getElementById('running-progress-delta');
  if (!textEl || !deltaEl) return;

  const volume = computeRunningVolume({
    distance: document.getElementById('running-distance')?.value || 0,
    avg_speed: document.getElementById('running-avg-speed')?.value || 0,
    duration: document.getElementById('running-duration')?.value || 0,
    elevation: document.getElementById('running-elevation')?.value || 0,
  });

  textEl.textContent = `Volumen estimado: ${formatNum(volume, 1)}`;
  setPreviewDelta(deltaEl, volume, runningPreviousVolume);
}

function buildProgressBadge(currentValue, previousValue) {
  if (previousValue === null || previousValue === undefined || previousValue <= 0) {
    return '<span class="badge" style="opacity:.75">Base</span>';
  }

  const pct = Math.round(((currentValue - previousValue) / previousValue) * 100);
  if (pct > 0) {
    return `<span class="badge-green badge">▲ +${pct}%</span>`;
  }
  if (pct < 0) {
    return `<span class="badge" style="background:rgba(255,101,132,.15);color:var(--accent2)">▼ ${pct}%</span>`;
  }
  return '<span class="badge">= 0%</span>';
}

function initDatePickers() {
  if (typeof flatpickr !== 'function') return;

  const localeEs = (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.es)
    ? window.flatpickr.l10ns.es
    : undefined;

  const options = {
    locale: localeEs,
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'd/m/Y',
    disableMobile: true,
    allowInput: false,
    defaultDate: 'today',
  };

  ['#rec-date', '#bike-date', '#running-date', '#sport-date'].forEach(selector => {
    const el = document.querySelector(selector);
    if (!el) return;
    if (el._flatpickr) return;
    flatpickr(el, options);
  });
}

async function loadBikeActivities() {
  const res = await api('/api/gym/activities/list', { type: 'bike' });
  if (!res.ok) {
    toast(res.msg || 'Error cargando actividades');
    return;
  }

  const base = Array.isArray(res.data) ? res.data : [];
  const withLatest = await Promise.all(base.map(async activity => {
    const historyRes = await api('/api/gym/activities-records/history', { activity_id: activity.id });
    const latest = historyRes.ok && historyRes.data && historyRes.data.length ? historyRes.data[0] : null;
    return { ...activity, latestRecord: latest };
  }));

  bikeActivities = withLatest;
  bikeLoaded = true;
  renderBikeActivities(withLatest);
}

function renderBikeActivities(data) {
  const grid = document.getElementById('bike-activity-grid');
  if (!grid) return;

  grid.innerHTML = '';
  if (!data.length) {
    const empty = document.createElement('div');
    empty.className = 'empty-state';
    empty.id = 'bike-empty-state';
    empty.style.gridColumn = '1/-1';
    empty.innerHTML = '<p>Aun no hay actividades de ciclismo.</p>';
    grid.appendChild(empty);
    return;
  }

  data.forEach(activity => {
    const card = document.createElement('div');
    card.className = 'card';
    const r = activity.latestRecord;

    card.innerHTML = `
      <div class="card-name">${esc(activity.name)}</div>
      ${r ? `
        <div class="card-record">
          <span class="badge-green badge">${formatNum(r.distance)} km</span>
          <span class="badge">${formatNum(r.avg_speed)} km/h</span>
        </div>
        <div class="card-date">${formatDate(r.date)} · ${r.elevation} m · ${formatDuration(r.duration)}</div>
      ` : '<div class="card-empty">Sin registros aún</div>'}
      <div class="card-actions">
        <button class="btn btn-primary btn-sm" onclick="openBikeRecordModal(${activity.id},'${esc(activity.name)}')">+ Registro</button>
        <button class="btn btn-ghost btn-sm" onclick="openBikeHistory(${activity.id},'${esc(activity.name)}')">Historial</button>
        <button class="btn btn-ghost btn-sm btn-icon" title="Eliminar" onclick="deleteBikeActivity(${activity.id},'${esc(activity.name)}')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2.5">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
            <path d="M10 11v6M14 11v6M9 6V4h6v2"/>
          </svg>
        </button>
      </div>`;
    grid.appendChild(card);
  });
}

function findBikeActivityById(activityId) {
  const id = parseInt(activityId, 10) || 0;
  return bikeActivities.find(a => parseInt(a.id, 10) === id) || null;
}

function openBikeRecordModal(activityId, name) {
  const activity = findBikeActivityById(activityId);
  const last = activity?.latestRecord || null;
  bikePreviousVolume = last ? computeBikeVolume(last) : null;
  bikeDurationManual = false;

  document.getElementById('bike-activity-id').value = activityId;
  document.getElementById('bike-record-title').textContent = name;
  document.getElementById('bike-date').value = new Date().toISOString().split('T')[0];
  document.getElementById('bike-distance').value = last ? formatNum(last.distance) : '';
  document.getElementById('bike-avg-speed').value = last ? formatNum(last.avg_speed) : '';
  document.getElementById('bike-elevation').value = last ? String(parseInt(last.elevation, 10) || 0) : '';
  document.getElementById('bike-duration').value = last ? String(parseInt(last.duration, 10) || 0) : '';
  if (!last) refreshBikeDurationAuto();
  updateBikeProgressPreview();
  openModal('modal-bike-record');
}

async function addBikeActivity() {
  const nameEl = document.getElementById('bike-activity-name');
  const name = (nameEl?.value || '').trim();

  if (!name) {
    toast('Escribe un nombre');
    return;
  }

  const res = await api('/api/gym/activities/add', { name, type: 'bike' });
  if (!res.ok) {
    toast(res.msg || 'Error');
    return;
  }

  // Render optimista para reflejar la nueva actividad al instante.
  bikeActivities = [
    {
      id: parseInt(res.id, 10) || Date.now(),
      name,
      type: 'bike',
      created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
      latestRecord: null,
    },
    ...bikeActivities,
  ];
  renderBikeActivities(bikeActivities);

  nameEl.value = '';
  closeModal('modal-bike-activity');
  toast('Actividad añadida');
  await loadBikeActivities();
}

async function addBikeActivityRecord() {
  const activity_id = parseInt(document.getElementById('bike-activity-id').value, 10) || 0;
  const date = document.getElementById('bike-date').value;
  const distance = toNum(document.getElementById('bike-distance').value);
  const avg_speed = toNum(document.getElementById('bike-avg-speed').value);
  const elevation = parseInt(document.getElementById('bike-elevation').value, 10) || 0;
  const duration = parseInt(document.getElementById('bike-duration').value, 10) || 0;

  if (!activity_id || distance <= 0 || avg_speed <= 0 || duration <= 0) {
    toast('Rellena actividad, distancia, velocidad y duracion');
    return;
  }

  const res = await api('/api/gym/activities-records/add', {
    activity_id,
    date,
    distance,
    avg_speed,
    elevation,
    duration,
  });

  if (!res.ok) {
    toast(res.msg || 'Error');
    return;
  }

  closeModal('modal-bike-record');
  toast('Registro guardado ✓');
  await loadBikeActivities();
}

async function openBikeHistory(activityId, name) {
  document.getElementById('bike-history-title').textContent = name;
  const list = document.getElementById('bike-history-list');
  const section = document.getElementById('bike-chart-section');
  const deltaEl = document.getElementById('bike-chart-delta');
  list.innerHTML = '';

  const res = await api('/api/gym/activities-records/history', { activity_id: activityId });
  if (!res.ok || !res.data.length) {
    section.style.display = 'none';
    list.innerHTML = '<p style="color:var(--muted);font-size:.9rem">Sin registros aun.</p>';
    openModal('modal-bike-history');
    return;
  }

  const chrono = [...res.data].reverse();
  const volumes = chrono.map(computeBikeVolume);
  const speeds = chrono.map(r => toNum(r.avg_speed));
  const distances = chrono.map(r => toNum(r.distance));
  const labels = chrono.map(r => formatDate(r.date));

  const first = volumes[0];
  const last = volumes[volumes.length - 1];
  const pct = first > 0 ? Math.round((last - first) / first * 100) : 0;

  if (volumes.length < 2) {
    deltaEl.textContent = '';
    deltaEl.className = 'chart-delta';
  } else if (pct > 0) {
    deltaEl.textContent = '▲ +' + pct + '%';
    deltaEl.className = 'chart-delta up';
  } else if (pct < 0) {
    deltaEl.textContent = '▼ ' + pct + '%';
    deltaEl.className = 'chart-delta down';
  } else {
    deltaEl.textContent = '= Sin cambio';
    deltaEl.className = 'chart-delta flat';
  }

  section.style.display = '';
  if (bikeHistoryChart) {
    bikeHistoryChart.destroy();
    bikeHistoryChart = null;
  }

  const ctx = document.getElementById('bike-history-chart').getContext('2d');
  const grad = ctx.createLinearGradient(0, 0, 0, 190);
  grad.addColorStop(0, 'rgba(108,99,255,0.4)');
  grad.addColorStop(1, 'rgba(108,99,255,0)');

  bikeHistoryChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Volumen bike',
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
          label: 'Velocidad',
          data: speeds,
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
          yAxisID: 'ySpeed',
        },
        {
          label: 'Distancia',
          data: distances,
          borderColor: '#22d3ee',
          backgroundColor: 'transparent',
          borderWidth: 2,
          pointBackgroundColor: '#22d3ee',
          pointBorderColor: '#1a1d27',
          pointBorderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 5,
          fill: false,
          tension: 0.35,
          yAxisID: 'yDist',
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
              if (item.dataset.label === 'Velocidad') return `Velocidad: ${item.parsed.y} km/h`;
              if (item.dataset.label === 'Distancia') return `Distancia: ${item.parsed.y} km`;
              return `Volumen bike: ${item.parsed.y}`;
            },
          },
        },
      },
      scales: {
        x: {
          grid: { color: '#2e3348' },
          ticks: { color: '#8892a4', font: { size: 11 }, maxRotation: 35 },
        },
        yVol: {
          position: 'left',
          grid: { color: '#2e3348' },
          ticks: { color: '#6c63ff', font: { size: 10 } },
          beginAtZero: false,
          title: { display: true, text: 'Vol', color: '#6c63ff', font: { size: 10 } },
        },
        ySpeed: {
          position: 'right',
          grid: { drawOnChartArea: false },
          ticks: { color: '#ff6584', font: { size: 10 } },
          beginAtZero: false,
          title: { display: true, text: 'km/h', color: '#ff6584', font: { size: 10 } },
        },
        yDist: {
          position: 'right',
          offset: true,
          grid: { drawOnChartArea: false },
          ticks: { color: '#22d3ee', font: { size: 10 } },
          beginAtZero: false,
          title: { display: true, text: 'km', color: '#22d3ee', font: { size: 10 } },
        },
      },
    },
  });

  res.data.forEach((r, i) => {
    const div = document.createElement('div');
    div.className = 'history-item';
    const vol = computeBikeVolume(r);
    const prev = res.data[i + 1] || null;
    const prevVol = prev ? computeBikeVolume(prev) : null;
    const progressBadge = buildProgressBadge(vol, prevVol);
    div.innerHTML = `
      <span class="hi-date">${formatDate(r.date)}</span>
      <span class="hi-detail">
        <span class="badge-green badge">${formatNum(r.distance)} km</span>
        <span class="badge">${formatNum(r.avg_speed)} km/h</span>
        ${progressBadge}
        <span style="font-size:.75rem;color:var(--muted)">${r.elevation} m · ${formatDuration(r.duration)} · Vol ${formatNum(vol, 1)}</span>
      </span>
      <span class="hi-action">
        <button class="btn btn-ghost btn-sm btn-icon" title="Eliminar" onclick="deleteBikeRecord(${r.id})">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2.5">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
            <path d="M10 11v6M14 11v6M9 6V4h6v2"/>
          </svg>
        </button>
      </span>`;
    list.appendChild(div);
  });

  openModal('modal-bike-history');
}

async function deleteBikeActivity(activityId, name) {
  if (!confirm(`¿Eliminar "${name}" y todos sus registros?`)) return;
  const res = await api('/api/gym/activities/delete', { activity_id: activityId });
  if (res.ok) {
    // Render optimista para ocultar la actividad sin recargar la pagina.
    bikeActivities = bikeActivities.filter(a => parseInt(a.id, 10) !== parseInt(activityId, 10));
    renderBikeActivities(bikeActivities);
    toast('Actividad eliminada');
    await loadBikeActivities();
  }
}

async function deleteBikeRecord(recordId) {
  if (!confirm('¿Eliminar este registro?')) return;
  const res = await api('/api/gym/activities-records/delete', { record_id: recordId });
  if (res.ok) {
    toast('Registro eliminado');
    closeModal('modal-bike-history');
    loadBikeActivities();
  }
}

async function loadRunningActivities() {
  const res = await api('/api/gym/activities/list', { type: 'running' });
  if (!res.ok) {
    toast(res.msg || 'Error cargando actividades');
    return;
  }

  const base = Array.isArray(res.data) ? res.data : [];
  const withLatest = await Promise.all(base.map(async activity => {
    const historyRes = await api('/api/gym/activities-records/history', { activity_id: activity.id });
    const latest = historyRes.ok && historyRes.data && historyRes.data.length ? historyRes.data[0] : null;
    return { ...activity, latestRecord: latest };
  }));

  runningActivities = withLatest;
  runningLoaded = true;
  renderRunningActivities(withLatest);
}

function renderRunningActivities(data) {
  const grid = document.getElementById('running-activity-grid');
  if (!grid) return;

  grid.innerHTML = '';
  if (!data.length) {
    const empty = document.createElement('div');
    empty.className = 'empty-state';
    empty.id = 'running-empty-state';
    empty.style.gridColumn = '1/-1';
    empty.innerHTML = '<p>Aun no hay actividades de running.</p>';
    grid.appendChild(empty);
    return;
  }

  data.forEach(activity => {
    const card = document.createElement('div');
    card.className = 'card';
    const r = activity.latestRecord;

    card.innerHTML = `
      <div class="card-name">${esc(activity.name)}</div>
      ${r ? `
        <div class="card-record">
          <span class="badge-green badge">${formatNum(r.distance)} km</span>
          <span class="badge">${formatNum(r.avg_speed)} km/h</span>
        </div>
        <div class="card-date">${formatDate(r.date)} · ${r.elevation} m · ${formatDuration(r.duration)}</div>
      ` : '<div class="card-empty">Sin registros aún</div>'}
      <div class="card-actions">
        <button class="btn btn-primary btn-sm" onclick="openRunningRecordModal(${activity.id},'${esc(activity.name)}')">+ Registro</button>
        <button class="btn btn-ghost btn-sm" onclick="openRunningHistory(${activity.id},'${esc(activity.name)}')">Historial</button>
        <button class="btn btn-ghost btn-sm btn-icon" title="Eliminar" onclick="deleteRunningActivity(${activity.id},'${esc(activity.name)}')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2.5">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
            <path d="M10 11v6M14 11v6M9 6V4h6v2"/>
          </svg>
        </button>
      </div>`;
    grid.appendChild(card);
  });
}

function findRunningActivityById(activityId) {
  const id = parseInt(activityId, 10) || 0;
  return runningActivities.find(a => parseInt(a.id, 10) === id) || null;
}

function openRunningRecordModal(activityId, name) {
  const activity = findRunningActivityById(activityId);
  const last = activity?.latestRecord || null;
  runningPreviousVolume = last ? computeRunningVolume(last) : null;
  runningDurationManual = false;

  document.getElementById('running-activity-id').value = activityId;
  document.getElementById('running-record-title').textContent = name;
  document.getElementById('running-date').value = new Date().toISOString().split('T')[0];
  document.getElementById('running-distance').value = last ? formatNum(last.distance) : '';
  document.getElementById('running-avg-speed').value = last ? formatNum(last.avg_speed) : '';
  document.getElementById('running-elevation').value = last ? String(parseInt(last.elevation, 10) || 0) : '';
  document.getElementById('running-duration').value = last ? String(parseInt(last.duration, 10) || 0) : '';
  if (!last) refreshRunningDurationAuto();
  updateRunningProgressPreview();
  openModal('modal-running-record');
}

async function addRunningActivity() {
  const nameEl = document.getElementById('running-activity-name');
  const name = (nameEl?.value || '').trim();

  if (!name) {
    toast('Escribe un nombre');
    return;
  }

  const res = await api('/api/gym/activities/add', { name, type: 'running' });
  if (!res.ok) {
    toast(res.msg || 'Error');
    return;
  }

  runningActivities = [
    {
      id: parseInt(res.id, 10) || Date.now(),
      name,
      type: 'running',
      created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
      latestRecord: null,
    },
    ...runningActivities,
  ];
  renderRunningActivities(runningActivities);

  nameEl.value = '';
  closeModal('modal-running-activity');
  toast('Actividad añadida');
  await loadRunningActivities();
}

async function addRunningActivityRecord() {
  const activity_id = parseInt(document.getElementById('running-activity-id').value, 10) || 0;
  const date = document.getElementById('running-date').value;
  const distance = toNum(document.getElementById('running-distance').value);
  const avg_speed = toNum(document.getElementById('running-avg-speed').value);
  const elevation = parseInt(document.getElementById('running-elevation').value, 10) || 0;
  const duration = parseInt(document.getElementById('running-duration').value, 10) || 0;

  if (!activity_id || distance <= 0 || avg_speed <= 0 || duration <= 0) {
    toast('Rellena actividad, distancia, velocidad y duracion');
    return;
  }

  const res = await api('/api/gym/activities-records/add', {
    activity_id,
    date,
    distance,
    avg_speed,
    elevation,
    duration,
  });

  if (!res.ok) {
    toast(res.msg || 'Error');
    return;
  }

  closeModal('modal-running-record');
  toast('Registro guardado ✓');
  await loadRunningActivities();
}

async function openRunningHistory(activityId, name) {
  document.getElementById('running-history-title').textContent = name;
  const list = document.getElementById('running-history-list');
  const section = document.getElementById('running-chart-section');
  const deltaEl = document.getElementById('running-chart-delta');
  list.innerHTML = '';

  const res = await api('/api/gym/activities-records/history', { activity_id: activityId });
  if (!res.ok || !res.data.length) {
    section.style.display = 'none';
    list.innerHTML = '<p style="color:var(--muted);font-size:.9rem">Sin registros aun.</p>';
    openModal('modal-running-history');
    return;
  }

  const chrono = [...res.data].reverse();
  const volumes = chrono.map(computeRunningVolume);
  const speeds = chrono.map(r => toNum(r.avg_speed));
  const distances = chrono.map(r => toNum(r.distance));
  const labels = chrono.map(r => formatDate(r.date));

  const first = volumes[0];
  const last = volumes[volumes.length - 1];
  const pct = first > 0 ? Math.round((last - first) / first * 100) : 0;

  if (volumes.length < 2) {
    deltaEl.textContent = '';
    deltaEl.className = 'chart-delta';
  } else if (pct > 0) {
    deltaEl.textContent = '▲ +' + pct + '%';
    deltaEl.className = 'chart-delta up';
  } else if (pct < 0) {
    deltaEl.textContent = '▼ ' + pct + '%';
    deltaEl.className = 'chart-delta down';
  } else {
    deltaEl.textContent = '= Sin cambio';
    deltaEl.className = 'chart-delta flat';
  }

  section.style.display = '';
  if (runningHistoryChart) {
    runningHistoryChart.destroy();
    runningHistoryChart = null;
  }

  const ctx = document.getElementById('running-history-chart').getContext('2d');
  const grad = ctx.createLinearGradient(0, 0, 0, 190);
  grad.addColorStop(0, 'rgba(108,99,255,0.4)');
  grad.addColorStop(1, 'rgba(108,99,255,0)');

  runningHistoryChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Volumen running',
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
          label: 'Velocidad',
          data: speeds,
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
          yAxisID: 'ySpeed',
        },
        {
          label: 'Distancia',
          data: distances,
          borderColor: '#22d3ee',
          backgroundColor: 'transparent',
          borderWidth: 2,
          pointBackgroundColor: '#22d3ee',
          pointBorderColor: '#1a1d27',
          pointBorderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 5,
          fill: false,
          tension: 0.35,
          yAxisID: 'yDist',
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
              if (item.dataset.label === 'Velocidad') return `Velocidad: ${item.parsed.y} km/h`;
              if (item.dataset.label === 'Distancia') return `Distancia: ${item.parsed.y} km`;
              return `Volumen running: ${item.parsed.y}`;
            },
          },
        },
      },
      scales: {
        x: {
          grid: { color: '#2e3348' },
          ticks: { color: '#8892a4', font: { size: 11 }, maxRotation: 35 },
        },
        yVol: {
          position: 'left',
          grid: { color: '#2e3348' },
          ticks: { color: '#6c63ff', font: { size: 10 } },
          beginAtZero: false,
          title: { display: true, text: 'Vol', color: '#6c63ff', font: { size: 10 } },
        },
        ySpeed: {
          position: 'right',
          grid: { drawOnChartArea: false },
          ticks: { color: '#ff6584', font: { size: 10 } },
          beginAtZero: false,
          title: { display: true, text: 'km/h', color: '#ff6584', font: { size: 10 } },
        },
        yDist: {
          position: 'right',
          offset: true,
          grid: { drawOnChartArea: false },
          ticks: { color: '#22d3ee', font: { size: 10 } },
          beginAtZero: false,
          title: { display: true, text: 'km', color: '#22d3ee', font: { size: 10 } },
        },
      },
    },
  });

  res.data.forEach((r, i) => {
    const div = document.createElement('div');
    div.className = 'history-item';
    const vol = computeRunningVolume(r);
    const prev = res.data[i + 1] || null;
    const prevVol = prev ? computeRunningVolume(prev) : null;
    const progressBadge = buildProgressBadge(vol, prevVol);
    div.innerHTML = `
      <span class="hi-date">${formatDate(r.date)}</span>
      <span class="hi-detail">
        <span class="badge-green badge">${formatNum(r.distance)} km</span>
        <span class="badge">${formatNum(r.avg_speed)} km/h</span>
        ${progressBadge}
        <span style="font-size:.75rem;color:var(--muted)">${r.elevation} m · ${formatDuration(r.duration)} · Vol ${formatNum(vol, 1)}</span>
      </span>
      <span class="hi-action">
        <button class="btn btn-ghost btn-sm btn-icon" title="Eliminar" onclick="deleteRunningRecord(${r.id})">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2.5">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
            <path d="M10 11v6M14 11v6M9 6V4h6v2"/>
          </svg>
        </button>
      </span>`;
    list.appendChild(div);
  });

  openModal('modal-running-history');
}

async function deleteRunningActivity(activityId, name) {
  if (!confirm(`¿Eliminar "${name}" y todos sus registros?`)) return;
  const res = await api('/api/gym/activities/delete', { activity_id: activityId });
  if (res.ok) {
    runningActivities = runningActivities.filter(a => parseInt(a.id, 10) !== parseInt(activityId, 10));
    renderRunningActivities(runningActivities);
    toast('Actividad eliminada');
    await loadRunningActivities();
  }
}

async function deleteRunningRecord(recordId) {
  if (!confirm('¿Eliminar este registro?')) return;
  const res = await api('/api/gym/activities-records/delete', { record_id: recordId });
  if (res.ok) {
    toast('Registro eliminado');
    closeModal('modal-running-history');
    await loadRunningActivities();
  }
}

async function loadSports() {
  const res = await api('/api/gym/sports/list');
  if (!res.ok) {
    toast(res.msg || 'Error cargando deportes');
    return;
  }

  const base = Array.isArray(res.data) ? res.data : [];
  const withLatest = await Promise.all(base.map(async sport => {
    const historyRes = await api('/api/gym/sports-register/history', { sport_id: sport.id });
    const latest = historyRes.ok && historyRes.data && historyRes.data.length ? historyRes.data[0] : null;
    return { ...sport, latestRecord: latest };
  }));

  sports = withLatest;
  sportsLoaded = true;
  renderSports(withLatest);
}

function renderSports(data) {
  const grid = document.getElementById('sports-grid');
  if (!grid) return;

  grid.innerHTML = '';
  if (!data.length) {
    const empty = document.createElement('div');
    empty.className = 'empty-state';
    empty.id = 'sports-empty-state';
    empty.style.gridColumn = '1/-1';
    empty.innerHTML = '<p>Aun no hay deportes registrados.</p>';
    grid.appendChild(empty);
    return;
  }

  data.forEach(sport => {
    const card = document.createElement('div');
    card.className = 'card';
    const r = sport.latestRecord;
    const icon = esc(sport.icon || 'fa-solid fa-trophy');

    card.innerHTML = `
      <div class="card-name" style="display:flex;align-items:center;gap:.5rem">
        <i class="${icon}" aria-hidden="true"></i>
        <span>${esc(sport.name)}</span>
      </div>
      ${r ? `
        <div class="card-record">
          <span class="badge-green badge">${formatDuration(r.duration)}</span>
        </div>
        <div class="card-date">${formatDate(r.date)}</div>
      ` : '<div class="card-empty">Sin registros aun</div>'}
      <div class="card-actions">
        <button class="btn btn-primary btn-sm" onclick="openSportRegisterModal(${sport.id},'${esc(sport.name)}')">+ Registro</button>
        <button class="btn btn-ghost btn-sm" onclick="openSportHistory(${sport.id},'${esc(sport.name)}')">Historial</button>
        <button class="btn btn-ghost btn-sm btn-icon" title="Eliminar" onclick="deleteSport(${sport.id},'${esc(sport.name)}')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2.5">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
            <path d="M10 11v6M14 11v6M9 6V4h6v2"/>
          </svg>
        </button>
      </div>`;
    grid.appendChild(card);
  });
}

function findSportById(sportId) {
  const id = parseInt(sportId, 10) || 0;
  return sports.find(s => parseInt(s.id, 10) === id) || null;
}

function openSportRegisterModal(sportId, name) {
  const sport = findSportById(sportId);
  const last = sport?.latestRecord || null;

  document.getElementById('sport-id').value = sportId;
  document.getElementById('sport-register-title').textContent = name;
  document.getElementById('sport-date').value = new Date().toISOString().split('T')[0];
  document.getElementById('sport-duration').value = last ? String(parseInt(last.duration, 10) || 0) : '';

  openModal('modal-sport-register');
}

async function addSport() {
  const nameEl = document.getElementById('sport-name');
  const iconEl = document.getElementById('sport-icon');
  const name = (nameEl?.value || '').trim();
  const icon = (iconEl?.value || 'fa-solid fa-futbol').trim();

  if (!name) {
    toast('Escribe un nombre');
    return;
  }

  const res = await api('/api/gym/sports/add', { name, icon });
  if (!res.ok) {
    toast(res.msg || 'Error');
    return;
  }

  sports = [
    {
      id: parseInt(res.id, 10) || Date.now(),
      name,
      icon,
      created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
      latestRecord: null,
    },
    ...sports,
  ];
  renderSports(sports);

  nameEl.value = '';
  if (iconEl) {
    iconEl.value = 'fa-solid fa-volleyball';
  }
  closeModal('modal-sport');
  toast('Deporte anadido');
  await loadSports();
}

async function addSportRegister() {
  const sport_id = parseInt(document.getElementById('sport-id').value, 10) || 0;
  const date = document.getElementById('sport-date').value;
  const duration = parseInt(document.getElementById('sport-duration').value, 10) || 0;

  if (!sport_id || duration <= 0) {
    toast('Rellena deporte y duracion');
    return;
  }

  const res = await api('/api/gym/sports-register/add', {
    sport_id,
    date,
    duration,
  });

  if (!res.ok) {
    toast(res.msg || 'Error');
    return;
  }

  closeModal('modal-sport-register');
  toast('Registro guardado ✓');
  await loadSports();
}

async function openSportHistory(sportId, name) {
  document.getElementById('sport-history-title').textContent = name;
  const list = document.getElementById('sport-history-list');
  list.innerHTML = '';

  const res = await api('/api/gym/sports-register/history', { sport_id: sportId });
  if (!res.ok || !res.data.length) {
    list.innerHTML = '<p style="color:var(--muted);font-size:.9rem">Sin registros aun.</p>';
    openModal('modal-sport-history');
    return;
  }

  res.data.forEach(r => {
    const div = document.createElement('div');
    div.className = 'history-item';
    div.innerHTML = `
      <span class="hi-date">${formatDate(r.date)}</span>
      <span class="hi-detail">
        <span class="badge-green badge">${formatDuration(r.duration)}</span>
      </span>
      <span class="hi-action">
        <button class="btn btn-ghost btn-sm btn-icon" title="Eliminar" onclick="deleteSportRegister(${r.id})">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2.5">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
            <path d="M10 11v6M14 11v6M9 6V4h6v2"/>
          </svg>
        </button>
      </span>`;
    list.appendChild(div);
  });

  openModal('modal-sport-history');
}

async function deleteSport(sportId, name) {
  if (!confirm(`¿Eliminar "${name}" y todos sus registros?`)) return;
  const res = await api('/api/gym/sports/delete', { sport_id: sportId });
  if (res.ok) {
    sports = sports.filter(s => parseInt(s.id, 10) !== parseInt(sportId, 10));
    renderSports(sports);
    toast('Deporte eliminado');
    await loadSports();
  }
}

async function deleteSportRegister(recordId) {
  if (!confirm('¿Eliminar este registro?')) return;
  const res = await api('/api/gym/sports-register/delete', { record_id: recordId });
  if (res.ok) {
    toast('Registro eliminado');
    closeModal('modal-sport-history');
    await loadSports();
  }
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
  updateGymProgressPreview();
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
    updateGymProgressPreview();
  }
}

function renderRepPills(series) {
  const container = document.getElementById('reps-pills');
  const patterns = REP_PATTERNS[series] || [];
  container.innerHTML = '';
  currentReps = '';
  document.getElementById('rec-reps').value = '';

  patterns.forEach((p, i) => {
    const btn = document.createElement('button');
    btn.className = 'pill' + (i === 0 ? ' selected' : '');
    btn.dataset.val = p;
    btn.textContent = p;
    btn.onclick = function () { selectPill('reps', this); };
    container.appendChild(btn);
  });

  if (patterns[0]) {
    currentReps = patterns[0];
    document.getElementById('rec-reps').value = patterns[0];
    updateGymProgressPreview();
  }
}

// ── Ejercicios ────────────────────────────────────────────────────────────────
async function loadExercises() {
  const res = await api('/api/gym/exercises/list');
  if (!res.ok) return;
  lastExercises = res.data;
  renderExercises(res.data);
}

function renderExercises(data) {
  const grid = document.getElementById('exercise-grid');
  const empty = document.getElementById('empty-state');

  const filtered = activeFilter === 'TODOS'
    ? data
    : data.filter(e => e.routine === activeFilter);

  grid.innerHTML = '';
  if (!filtered.length) {
    grid.appendChild(empty);
    return;
  }

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
      const card = document.createElement('div');
      card.className = 'card';
      const hasRecord = ex.weight !== null;
      const wFmt = hasRecord
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
  document.getElementById('rec-exercise-id').value = id;
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
  const baselineWeight = toNum(lastWeight || 0);
  const baselineReps = sumRepsFromPattern(lastReps || '');
  gymPreviousVolume = baselineWeight > 0 && baselineReps > 0
    ? Math.round(baselineWeight * baselineReps * 10) / 10
    : null;
  updateGymProgressPreview();
  openModal('modal-record');
}

// ── CRUD ─────────────────────────────────────────────────────────────────────
async function addExercise() {
  const name = document.getElementById('ex-name').value.trim();
  const routine = document.getElementById('ex-routine').value || 'PECHO';
  if (!name) {
    toast('Escribe un nombre');
    return;
  }

  const res = await api('/api/gym/exercises/add', { name, routine });
  if (!res.ok) {
    toast(res.msg || 'Error');
    return;
  }

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
  const weight = document.getElementById('rec-weight').value;
  const series = document.getElementById('rec-series').value;
  const rep_pattern = document.getElementById('rec-reps').value;
  const date = document.getElementById('rec-date').value;

  if (!rep_pattern) {
    toast('Elige un patrón de repeticiones');
    return;
  }

  const res = await api('/api/gym/records/add', { exercise_id, weight, series, rep_pattern, date });
  if (!res.ok) {
    toast(res.msg || 'Error');
    return;
  }

  closeModal('modal-record');
  toast('Registro guardado ✓');
  loadExercises();
}

async function deleteExercise(id, name) {
  if (!confirm(`¿Eliminar "${name}" y todos sus registros?`)) return;
  const res = await api('/api/gym/exercises/delete', { exercise_id: id });
  if (res.ok) {
    toast('Ejercicio eliminado');
    loadExercises();
  }
}

async function deleteRecord(rid) {
  if (!confirm('¿Eliminar este registro?')) return;
  const res = await api('/api/gym/records/delete', { record_id: rid });
  if (res.ok) {
    toast('Registro eliminado');
    loadExercises();
    closeModal('modal-history');
  }
}

// ── Historial + grafica ───────────────────────────────────────────────────────
function computeVolume(r) {
  const reps = r.rep_pattern.split('-').map(Number).filter(n => n > 0);
  return Math.round(parseFloat(r.weight) * reps.reduce((a, b) => a + b, 0) * 10) / 10;
}

async function openHistory(id, name) {
  document.getElementById('history-title').textContent = name;
  const res = await api('/api/gym/records/history', { exercise_id: id });
  const list = document.getElementById('history-list');
  const section = document.getElementById('chart-section');
  list.innerHTML = '';

  if (!res.ok || !res.data.length) {
    section.style.display = 'none';
    list.innerHTML = '<p style="color:var(--muted);font-size:.9rem">Sin registros aun.</p>';
    openModal('modal-history');
    return;
  }

  const chrono = [...res.data].reverse();
  const volumes = chrono.map(computeVolume);
  const weights = chrono.map(r => parseFloat(r.weight));
  const labels = chrono.map(r => formatDate(r.date));

  const first = volumes[0];
  const last = volumes[volumes.length - 1];
  const pct = first > 0 ? Math.round((last - first) / first * 100) : 0;
  const deltaEl = document.getElementById('chart-delta');

  if (volumes.length < 2) {
    deltaEl.textContent = '';
    deltaEl.className = 'chart-delta';
  } else if (pct > 0) {
    deltaEl.textContent = '▲ +' + pct + '%';
    deltaEl.className = 'chart-delta up';
  } else if (pct < 0) {
    deltaEl.textContent = '▼ ' + pct + '%';
    deltaEl.className = 'chart-delta down';
  } else {
    deltaEl.textContent = '= Sin cambio';
    deltaEl.className = 'chart-delta flat';
  }

  section.style.display = '';
  if (historyChart) {
    historyChart.destroy();
    historyChart = null;
  }

  const ctx = document.getElementById('history-chart').getContext('2d');
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
              const r = chrono[item.dataIndex];
              const w = parseFloat(r.weight) % 1 === 0 ? r.weight : parseFloat(r.weight).toFixed(1);
              const reps = r.rep_pattern.split('-').map(Number).reduce((a, b) => a + b, 0);
              return [`Volumen: ${item.parsed.y} kg·rep`, `  ${w} kg × ${reps} reps (${r.series} series)`];
            },
          },
        },
      },
      scales: {
        x: {
          grid: { color: '#2e3348' },
          ticks: { color: '#8892a4', font: { size: 11 }, maxRotation: 35 },
        },
        yVol: {
          position: 'left',
          grid: { color: '#2e3348' },
          ticks: { color: '#6c63ff', font: { size: 10 } },
          beginAtZero: false,
          title: { display: true, text: 'Vol', color: '#6c63ff', font: { size: 10 } },
        },
        yKg: {
          position: 'right',
          grid: { drawOnChartArea: false },
          ticks: { color: '#ff6584', font: { size: 10 } },
          beginAtZero: false,
          title: { display: true, text: 'kg', color: '#ff6584', font: { size: 10 } },
        },
      },
    },
  });

  res.data.forEach((r, i) => {
    const div = document.createElement('div');
    div.className = 'history-item';
    const w = parseFloat(r.weight) % 1 === 0 ? r.weight : parseFloat(r.weight).toFixed(1);
    const vol = computeVolume(r);
    const prev = res.data[i + 1] || null;
    const prevVol = prev ? computeVolume(prev) : null;
    const progressBadge = buildProgressBadge(vol, prevVol);
    div.innerHTML = `
      <span class="hi-date">${formatDate(r.date)}</span>
      <span class="hi-detail">
        <span class="badge-green badge">${w} kg</span>
        <span class="badge">${r.series} × ${esc(r.rep_pattern)}</span>
        ${progressBadge}
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

const bikeNameInput = document.getElementById('bike-activity-name');
if (bikeNameInput) {
  bikeNameInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') addBikeActivity();
  });
}

const runningNameInput = document.getElementById('running-activity-name');
if (runningNameInput) {
  runningNameInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') addRunningActivity();
  });
}

const sportNameInput = document.getElementById('sport-name');
if (sportNameInput) {
  sportNameInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') addSport();
  });
}

['bike-distance', 'bike-avg-speed', 'bike-elevation', 'bike-duration'].forEach(id => {
  const el = document.getElementById(id);
  if (!el) return;
  el.addEventListener('input', () => {
    if (id === 'bike-duration') {
      bikeDurationManual = true;
    } else {
      refreshBikeDurationAuto();
    }
    updateBikeProgressPreview();
  });
});

['running-distance', 'running-avg-speed', 'running-elevation', 'running-duration'].forEach(id => {
  const el = document.getElementById(id);
  if (!el) return;
  el.addEventListener('input', () => {
    if (id === 'running-duration') {
      runningDurationManual = true;
    } else {
      refreshRunningDurationAuto();
    }
    updateRunningProgressPreview();
  });
});

initDatePickers();
renderRepPills(4);
loadExercises();
