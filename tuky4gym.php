<?php
require_once __DIR__ . '/api.php'; // Maneja POST y termina; si es GET continúa
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#0f1117">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<title>Tuky4Gym</title>
<link rel="icon" type="image/x-icon" href="Tuky4Gym.ico">
<link rel="stylesheet" href="assets/css/app.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
</head>
<body>

<header>
  <a href="index.php" class="btn btn-ghost" title="Inicio">
      <img src="Tuky4Gym.png" alt="Logo" width="64" height="64">
    </a>
  
  <h1>Tuky<span>4Gym</span></h1>
  <div class="header-actions">
    
    <button class="btn btn-primary" onclick="openModal('modal-exercise')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Nuevo ejercicio
    </button>
  </div>
</header>

<main>
  <div class="section-header">
    <span class="section-title">Mis ejercicios</span>
  </div>
  <div class="filter-bar" id="filter-pills">
    <button class="pill" data-val="TODOS"   onclick="setFilter('TODOS')">Todos</button>
    <button class="pill" data-val="PECHO"   onclick="setFilter('PECHO')">Pecho</button>
    <button class="pill" data-val="ESPALDA" onclick="setFilter('ESPALDA')">Espalda</button>
    <button class="pill" data-val="PIERNAS" onclick="setFilter('PIERNAS')">Piernas</button>
  </div>
  <div class="grid" id="exercise-grid">
    <div class="empty-state" id="empty-state" style="grid-column:1/-1">
      <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M6 5v14M18 5v14M2 9h4M18 9h4M2 15h4M18 15h4"/>
      </svg>
      <p>Aún no hay ejercicios.<br>Añade el primero.</p>
    </div>
  </div>
</main>

<!-- ── Modal: Añadir ejercicio ── -->
<div class="overlay" id="modal-exercise" onclick="closeOnBg(event,'modal-exercise')">
  <div class="modal">
    <h2>Nuevo ejercicio</h2>
    <div class="field">
      <label>Nombre del ejercicio</label>
      <input type="text" id="ex-name" placeholder="ej. Press Banca, Sentadilla…" autocomplete="off">
    </div>
    <div class="field">
      <label>Rutina</label>
      <div class="pills" id="routine-pills">
        <button class="pill selected" data-val="PECHO"   onclick="selectPill('routine',this)">Pecho</button>
        <button class="pill"          data-val="ESPALDA" onclick="selectPill('routine',this)">Espalda</button>
        <button class="pill"          data-val="PIERNAS" onclick="selectPill('routine',this)">Piernas</button>
      </div>
      <input type="hidden" id="ex-routine" value="PECHO">
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost"   onclick="closeModal('modal-exercise')">Cancelar</button>
      <button class="btn btn-primary" onclick="addExercise()">Guardar</button>
    </div>
  </div>
</div>

<!-- ── Modal: Añadir registro ── -->
<div class="overlay" id="modal-record" onclick="closeOnBg(event,'modal-record')">
  <div class="modal">
    <h2 id="record-title">Registrar progreso</h2>
    <input type="hidden" id="rec-exercise-id">

    <div class="field">
      <label>Fecha</label>
      <input type="date" id="rec-date">
    </div>

    <div class="field">
      <label>Peso (kg)</label>
      <div class="weight-ctrl">
        <button onclick="changeWeight(-2.5)">−</button>
        <div class="weight-val" id="weight-display">20</div>
        <button onclick="changeWeight(+2.5)">+</button>
      </div>
      <input type="hidden" id="rec-weight" value="20">
    </div>

    <div class="field">
      <label>Número de series</label>
      <div class="pills" id="series-pills">
        <button class="pill"          data-val="2" onclick="selectPill('series',this)">2</button>
        <button class="pill"          data-val="3" onclick="selectPill('series',this)">3</button>
        <button class="pill selected" data-val="4" onclick="selectPill('series',this)">4</button>
        <button class="pill"          data-val="5" onclick="selectPill('series',this)">5</button>
      </div>
      <input type="hidden" id="rec-series" value="4">
    </div>

    <div class="field">
      <label>Patrón de repeticiones</label>
      <div class="pills" id="reps-pills"></div>
      <input type="hidden" id="rec-reps" value="">
    </div>

    <div class="modal-footer">
      <button class="btn btn-ghost"   onclick="closeModal('modal-record')">Cancelar</button>
      <button class="btn btn-primary" onclick="addRecord()">Guardar</button>
    </div>
  </div>
</div>

<!-- ── Modal: Historial ── -->
<div class="overlay" id="modal-history" onclick="closeOnBg(event,'modal-history')">
  <div class="modal" style="max-width:520px">
    <h2 id="history-title">Historial</h2>
    <div class="chart-section" id="chart-section" style="display:none">
      <div class="chart-wrap">
        <canvas id="history-chart"></canvas>
      </div>
      <div class="chart-meta">
        <span class="chart-formula">Volumen = peso &times; repeticiones totales</span>
        <span class="chart-delta" id="chart-delta"></span>
      </div>
    </div>
    <div class="history-list" id="history-list"></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-history')">Cerrar</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script src="assets/js/app.js"></script>
</body>
</html>
