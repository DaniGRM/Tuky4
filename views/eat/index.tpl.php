<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0f1117">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Tuky4Eat</title>
    <link rel="icon" type="image/x-icon" href="Tuky4Gym.ico">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
</head>

<body>

    <header>
        <a href="/" class="btn btn-ghost" title="Inicio">
            <img src="Tuky4Gym.png" alt="Logo" width="64" height="64">
        </a>

        <h1>Tuky<span>4Eat</span></h1>
    </header>

    <main>
        <div class="section-switch pills" aria-label="Secciones Eat">
            <button class="pill selected" data-val="DASHBOARD" onclick="setEatSection('DASHBOARD')" title="Dashboard">
                <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
            </button>
            <button class="pill" data-val="CRUD" onclick="setEatSection('CRUD')" title="Raciones">
                <i class="fa-solid fa-utensils" aria-hidden="true"></i>
            </button>
        </div>

        <?php require __DIR__ . '/dashboard.tpl.php'; ?>
        <?php require __DIR__ . '/portions.tpl.php'; ?>
    </main>

    <div id="toast"></div>

    <?php require __DIR__ . '/../partials/mobile_nav.tpl.php'; ?>

    <div class="overlay" id="modal-portion" onclick="closeOnBg(event,'modal-portion')">
        <div class="modal">
            <h2 id="portion-modal-title">Nueva racion</h2>
            <input type="hidden" id="portion-id" value="">

            <div class="field">
                <label>Nombre</label>
                <input type="text" id="portion-name" placeholder="ej. Huevo, Arroz cocido" autocomplete="off">
            </div>
            <div class="field">
                <label>Cantidad</label>
                <input type="number" id="portion-amount" min="0.01" step="0.01" placeholder="ej. 1">
            </div>
            <div class="field">
                <label>Unidad</label>
                <select id="portion-unit-id"></select>
            </div>
            <div class="field">
                <label>Calorias (kcal)</label>
                <input type="number" id="portion-calories" min="1" step="1" placeholder="ej. 75">
            </div>

            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('modal-portion')">Cancelar</button>
                <button class="btn btn-primary" id="portion-save-btn" onclick="savePortion()">Guardar</button>
            </div>
        </div>
    </div>

    <div class="overlay" id="modal-eat-meal" onclick="closeOnBg(event,'modal-eat-meal')">
        <div class="modal" style="max-width:520px">
            <h2 id="eat-meal-modal-title">Desayuno</h2>
            <input type="hidden" id="eat-meal-key" value="breakfast">

            
            <div class="history-list" id="eat-meal-list"></div>
            <div class="field" style="margin-top:1rem">
                <label>Selecciona una racion</label>
                <div class="eat-ration-grid" id="eat-meal-ration-grid"></div>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('modal-eat-meal')">Cerrar</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/eat.js"></script>
</body>

</html>