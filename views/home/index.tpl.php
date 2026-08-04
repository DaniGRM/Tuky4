<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#0f1117">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<title>Tuky4 Dashboard</title>
<link rel="apple-touch-icon" href="Tuky4.png">
<link rel="icon" type="image/x-icon" href="Tuky4Gym.ico">
<link rel="stylesheet" href="assets/css/app.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" referrerpolicy="no-referrer">
<style>
  body { padding-bottom: calc(6.6rem + env(safe-area-inset-bottom)); }
  .home-wrap { max-width: 980px; margin: 0 auto; padding: 1.2rem 1rem; }
  .home-hero {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background:
      radial-gradient(120% 120% at 100% 0%, rgba(108,99,255,.24) 0%, rgba(108,99,255,0) 55%),
      linear-gradient(145deg, #20263a 0%, #1a1d27 100%);
    box-shadow: var(--shadow);
    padding: 1rem 1.1rem;
    margin-bottom: 1rem;
  }
  .home-hero h2 { font-size: 1.15rem; margin-bottom: .25rem; }
  .home-hero p { color: var(--muted); font-size: .86rem; }
  .home-kpi-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .8rem;
    margin-bottom: 1rem;
  }
  .home-kpi {
    border: 1px solid var(--border);
    border-radius: 12px;
    background: rgba(34, 38, 58, .72);
    padding: .8rem .85rem;
  }
  .home-kpi-label { color: var(--muted); font-size: .76rem; text-transform: uppercase; letter-spacing: .45px; font-weight: 700; }
  .home-kpi-value { margin-top: .25rem; font-size: 1.1rem; font-weight: 800; }
  .home-kpi-sub { margin-top: .15rem; color: var(--muted); font-size: .78rem; }
  .home-kpi-value.good { color: var(--green); }
  .home-kpi-value.bad { color: var(--accent2); }
  .home-days-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: .6rem;
  }
  .home-day-card {
    border: 1px solid rgba(108,99,255,.22);
    border-radius: 13px;
    background:
      linear-gradient(180deg, rgba(108,99,255,.12) 0%, rgba(108,99,255,0) 38%),
      rgba(26,29,39,.95);
    box-shadow: 0 8px 20px rgba(0,0,0,.28);
    padding: .62rem .56rem;
    display: flex;
    flex-direction: column;
    gap: .35rem;
    min-height: 112px;
    transition: transform .12s, border-color .2s;
  }
  .home-day-card:hover { transform: translateY(-2px); border-color: rgba(108,99,255,.5); }
  .home-day-card.has-training { border-color: rgba(74,222,128,.42); }
  .home-day-name { color: var(--muted); font-size: .72rem; font-weight: 700; letter-spacing: .35px; text-transform: uppercase; }
  .home-day-date { color: var(--text); font-size: .74rem; }
  .home-day-deficit { font-size: .82rem; font-weight: 800; }
  .home-day-deficit.good { color: var(--green); }
  .home-day-deficit.bad { color: var(--accent2); }
  .home-day-deficit.empty { color: var(--muted); font-weight: 700; }
  .home-day-flags { margin-top: auto; display: flex; gap: .25rem; flex-wrap: wrap; }
  .home-flag {
    font-size: .67rem;
    font-weight: 700;
    border-radius: 999px;
    padding: .15rem .42rem;
    border: 1px solid var(--border);
    color: var(--muted);
  }
  .home-flag.on-gym { color: #c4bfff; border-color: rgba(108,99,255,.5); background: rgba(108,99,255,.14); }
  .home-flag.on-act { color: #ffbbca; border-color: rgba(255,101,132,.5); background: rgba(255,101,132,.14); }
  .home-chart-card { margin-top: 1rem; padding: .9rem .9rem 1rem; }
  .home-chart-wrap { height: 220px; position: relative; }
  .home-loading { color: var(--muted); text-align: center; padding: 1.5rem .3rem; }
  @media (max-width: 900px) {
    .home-days-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
  }
  @media (max-width: 620px) {
    .home-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .home-days-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }
</style>
</head>
<body>

<header>
  <a href="/" class="btn btn-ghost" title="Inicio">
    <img src="Tuky4Gym.png" alt="Logo" width="56" height="56">
  </a>
  <h1>Tuky<span>4</span></h1>
</header>

<main class="home-wrap">

  <section class="home-kpi-grid" id="home-kpi-grid">
    <div class="home-loading" style="grid-column:1/-1">Cargando dashboard semanal...</div>
  </section>

  <section class="card" style="padding:.9rem .9rem 1rem">
    <div class="card-name" style="margin-bottom:.55rem">Ultimos 7 dias</div>
    <div class="home-days-grid" id="home-days-grid"></div>
  </section>

  <section class="card home-chart-card">
    <div class="card-name" style="margin-bottom:.55rem">Progreso deficit acumulado (7 dias)</div>
    <div class="home-chart-wrap">
      <canvas id="home-deficit-chart"></canvas>
    </div>
  </section>
</main>

<?php require __DIR__ . '/../partials/mobile_nav.tpl.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
  let homeDeficitChart = null;

  function fmtYmd(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  function weekdayEs(dateStr) {
    const [y, m, d] = dateStr.split('-').map(Number);
    const dt = new Date(y, m - 1, d);
    return dt.toLocaleDateString('es-ES', { weekday: 'short' }).replace('.', '').toUpperCase();
  }

  function dateShortEs(dateStr) {
    const [y, m, d] = dateStr.split('-').map(Number);
    const dt = new Date(y, m - 1, d);
    return dt.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
  }

  function buildLast7Days() {
    const days = [];
    const now = new Date();
    for (let i = 6; i >= 0; i--) {
      const d = new Date(now);
      d.setDate(now.getDate() - i);
      days.push(fmtYmd(d));
    }
    return days;
  }

  function renderHomeDashboard(daysData) {
    const kpiGrid = document.getElementById('home-kpi-grid');
    const daysGrid = document.getElementById('home-days-grid');
    if (!kpiGrid || !daysGrid) return;

    const sortedDays = [...daysData].sort((a, b) => a.date.localeCompare(b.date));
    const withCalories = sortedDays.filter(d => d.hasConsumedCalories);

    const deficitAccum = withCalories.reduce((acc, d) => acc + (d.deficitSigned || 0), 0);
    const avgDeficit = withCalories.length ? Math.round(deficitAccum / withCalories.length) : 0;
    const trainingDays = sortedDays.filter(d => d.gym || d.activity).length;
    const deficitClass = deficitAccum >= 0 ? 'good' : 'bad';

    kpiGrid.innerHTML = `
      <div class="home-kpi">
        <div class="home-kpi-label">Deficit 7 dias</div>
        <div class="home-kpi-value ${deficitClass}">${deficitAccum} kcal</div>
      </div>
      <div class="home-kpi">
        <div class="home-kpi-label">Media diaria</div>
        <div class="home-kpi-value ${avgDeficit >= 0 ? 'good' : 'bad'}">${avgDeficit} kcal</div>
      </div>
      <div class="home-kpi">
        <div class="home-kpi-label">Dias activos</div>
        <div class="home-kpi-value">${trainingDays}/7</div>
      </div>
    `;

    daysGrid.innerHTML = '';
    sortedDays.forEach(day => {
      const hasDeficitData = day.hasConsumedCalories;
      const deficitClassDay = !hasDeficitData
        ? 'empty'
        : (day.deficitSigned >= 0 ? 'good' : 'bad');
      const deficitText = !hasDeficitData
        ? 'Sin datos calorias'
        : (day.deficitSigned >= 0
            ? `Deficit ${day.deficitSigned}`
            : `Superavit ${Math.abs(day.deficitSigned)}`);
      const deficitUnit = hasDeficitData ? ' kcal' : '';

      const el = document.createElement('div');
      el.className = `home-day-card${(day.gym || day.activity) ? ' has-training' : ''}`;
      el.innerHTML = `
        <div class="home-day-name">${weekdayEs(day.date)}</div>
        <div class="home-day-date">${dateShortEs(day.date)}</div>
        <div class="home-day-deficit ${deficitClassDay}">${deficitText}${deficitUnit}</div>
        <div class="home-day-flags">
          <span class="home-flag ${day.gym ? 'on-gym' : ''}">Gym</span>
          <span class="home-flag ${day.activity ? 'on-act' : ''}">Actividad</span>
        </div>
      `;
      daysGrid.appendChild(el);
    });

    renderDeficitChart(sortedDays);
  }

  function renderDeficitChart(daysData) {
    const canvas = document.getElementById('home-deficit-chart');
    if (!canvas || typeof Chart !== 'function') return;

    const labels = daysData.map(d => `${weekdayEs(d.date)} ${dateShortEs(d.date)}`);
    const cumulative = [];
    let running = 0;
    daysData.forEach(day => {
      if (day.hasConsumedCalories) {
        running += day.deficitSigned;
      }
      cumulative.push(running);
    });

    if (homeDeficitChart) {
      homeDeficitChart.destroy();
      homeDeficitChart = null;
    }

    const ctx = canvas.getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 220);
    grad.addColorStop(0, 'rgba(74, 222, 128, .35)');
    grad.addColorStop(1, 'rgba(74, 222, 128, 0)');

    homeDeficitChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Deficit acumulado',
          data: cumulative,
          borderColor: '#4ade80',
          backgroundColor: grad,
          fill: true,
          borderWidth: 2.4,
          pointRadius: 4,
          pointHoverRadius: 6,
          pointBackgroundColor: '#4ade80',
          pointBorderColor: '#1a1d27',
          pointBorderWidth: 2,
          tension: .32,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            backgroundColor: '#22263a',
            borderColor: '#2e3348',
            borderWidth: 1,
            titleColor: '#e2e8f0',
            bodyColor: '#8892a4',
            callbacks: {
              label: (item) => `Acumulado: ${item.parsed.y} kcal`,
            },
          },
        },
        scales: {
          x: {
            grid: { color: '#2e3348' },
            ticks: { color: '#8892a4', font: { size: 10 }, maxRotation: 32 },
          },
          y: {
            grid: { color: '#2e3348' },
            ticks: { color: '#4ade80', font: { size: 10 } },
          },
        },
      },
    });
  }

  async function loadHomeDashboard() {
    const days = buildLast7Days();
    const start = days[0];
    const end = days[days.length - 1];

    const [trainingRes, ...eatResList] = await Promise.all([
      api('/api/gym/training-days', { start_date: start, end_date: end }),
      ...days.map(date => api('/api/eat/dashboard/list', { date }))
    ]);

    if (!trainingRes.ok) {
      toast(trainingRes.msg || 'Error cargando actividad semanal');
      return;
    }

    const trainingByDate = {};
    (trainingRes.data || []).forEach(row => {
      trainingByDate[row.date] = {
        gym: !!row.gym,
        activity: !!row.activity,
      };
    });

    const daysData = days.map((date, idx) => {
      const eatRes = eatResList[idx];
      const consumed = eatRes && eatRes.ok && eatRes.data && eatRes.data.energy
        ? parseInt(eatRes.data.energy.consumed_kcal, 10) || 0
        : 0;
      const hasConsumedCalories = consumed > 0;
      const net = eatRes && eatRes.ok && eatRes.data && eatRes.data.energy
        ? parseInt(eatRes.data.energy.net_kcal, 10) || 0
        : 0;

      const deficitSigned = hasConsumedCalories ? -net : 0;
      const tr = trainingByDate[date] || { gym: false, activity: false };
      return {
        date,
        deficitSigned,
        hasConsumedCalories,
        gym: tr.gym,
        activity: tr.activity,
      };
    });

    renderHomeDashboard(daysData);
  }

  loadHomeDashboard();
</script>

</body>
</html>
