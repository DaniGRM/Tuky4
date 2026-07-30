<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#0f1117">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<title>Tuky4</title>
<link rel="icon" type="image/x-icon" href="Tuky4Gym.ico">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --bg:      #0f1117;
    --surface: #1a1d27;
    --card:    #22263a;
    --accent:  #6c63ff;
    --accent2: #ff6584;
    --green:   #4ade80;
    --text:    #e2e8f0;
    --muted:   #8892a4;
    --border:  #2e3348;
    --radius:  16px;
    --shadow:  0 4px 24px rgba(0,0,0,.45);
  }
  html { font-size: 18px; }
  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Segoe UI', system-ui, sans-serif;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }

  header {
    background: linear-gradient(135deg, #1a1d27 0%, #22263a 100%);
    border-bottom: 1px solid var(--border);
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    position: sticky;
    top: 0;
    z-index: 100;
    backdrop-filter: blur(8px);
  }
  header h1 { font-size: 1.4rem; font-weight: 700; letter-spacing: -.5px; }
  header h1 span { color: var(--accent); }
  .header-sub { font-size: .8rem; color: var(--muted); margin-top: .1rem; }

  main {
    flex: 1;
    max-width: 860px;
    margin: 0 auto;
    padding: 2rem 1rem;
    width: 100%;
  }
  .section-label {
    font-size: .78rem; font-weight: 700; letter-spacing: 1.2px;
    text-transform: uppercase; color: var(--muted);
    margin-bottom: 1rem;
  }

  .app-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1rem;
    margin-bottom: 2.5rem;
  }

  .app-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.4rem 1.3rem;
    display: flex;
    flex-direction: column;
    gap: .6rem;
    text-decoration: none;
    color: var(--text);
    box-shadow: var(--shadow);
    transition: border-color .2s, transform .15s;
    cursor: pointer;
  }
  .app-card:hover     { border-color: var(--accent); transform: translateY(-2px); }
  .app-card:active    { transform: scale(.98); }
  .app-card.disabled  { opacity: .45; cursor: not-allowed; pointer-events: none; }

  .app-icon {
    width: 2.6rem; height: 2.6rem;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
    margin-bottom: .2rem;
  }
  .icon-gym  { background: rgba(108,99,255,.18); }
  .icon-todo { background: rgba(74,222,128,.15); }
  .icon-diet { background: rgba(255,101,132,.15); }
  .icon-more { background: rgba(136,146,164,.1); }

  .app-name {
    font-size: 1rem;
    font-weight: 700;
  }
  .app-desc {
    font-size: .8rem;
    color: var(--muted);
    line-height: 1.4;
  }
  .app-badge {
    margin-top: auto;
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .4px;
    padding: .2rem .55rem;
    border-radius: 999px;
  }
  .badge-live   { background: rgba(74,222,128,.15);  color: var(--green);   }
  .badge-soon   { background: rgba(136,146,164,.1);  color: var(--muted);   }

  footer {
    text-align: center;
    padding: 1.5rem 1rem calc(1.5rem + env(safe-area-inset-bottom));
    font-size: .75rem;
    color: var(--muted);
    border-top: 1px solid var(--border);
  }

  @media (max-width: 500px) {
    html { font-size: 16px; }
    main { padding: 1.25rem .75rem; }
    .app-grid { grid-template-columns: 1fr 1fr; gap: .75rem; }
    .app-card { padding: 1rem; }
  }
</style>
</head>
<body>

<header>
  <img src="Tuky4Gym.ico" alt="Logo" width="40" height="40">
  <div>
    <h1>Tuky<span>4</span></h1>
    <div class="header-sub">Panel de aplicaciones</div>
  </div>
</header>

<main>
  <div class="section-label">Activas</div>
  <div class="app-grid">

    <a class="app-card" href="tuky4gym.php">
      <div class="app-icon icon-gym"><img src="Tuky4Gym.png" alt="Tuky4Gym" width="40" height="40"></div>
      <div class="app-name">Tuky4Gym</div>
      <div class="app-desc">Seguimiento de ejercicios, pesos y repeticiones con graficas de progreso.</div>
      <span class="app-badge badge-live">● Activa</span>
    </a>

  </div>

  <div class="section-label">Proximamente</div>
  <div class="app-grid">

    <div class="app-card disabled">
      <div class="app-icon icon-diet">🥗</div>
      <div class="app-name">NutriTrack</div>
      <div class="app-desc">Control de alimentacion y calorias diarias.</div>
      <span class="app-badge badge-soon">En desarrollo</span>
    </div>

    <div class="app-card disabled">
      <div class="app-icon icon-todo">✅</div>
      <div class="app-name">Tareas</div>
      <div class="app-desc">Gestion de tareas y recordatorios personales.</div>
      <span class="app-badge badge-soon">En desarrollo</span>
    </div>

    <div class="app-card disabled">
      <div class="app-icon icon-more">＋</div>
      <div class="app-name">Mas apps</div>
      <div class="app-desc">Nuevas funcionalidades iran apareciendo aqui.</div>
      <span class="app-badge badge-soon">Proximamente</span>
    </div>

  </div>
</main>

<footer>Tuky4 &mdash; Tu espacio personal</footer>

</body>
</html>
