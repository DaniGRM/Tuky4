<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0f1117">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Tuky4Gym</title>
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

        <h1>Tuky<span>4Gym</span></h1>
    </header>

    <main>
        <div class="section-switch pills" aria-label="Secciones">
            <button class="pill selected" data-val="GYM" aria-label="Gym" title="Gym" onclick="setSection('GYM')">
                <i class="fa-solid fa-dumbbell" aria-hidden="true"></i>
            </button>
            <button class="pill" data-val="BIKE" aria-label="Ciclismo" title="Ciclismo" onclick="setSection('BIKE')">
                <i class="fa-solid fa-bicycle" aria-hidden="true"></i>
            </button>
            <button class="pill" data-val="RUNNING" aria-label="Running" title="Running" onclick="setSection('RUNNING')">
                <i class="fa-solid fa-person-running" aria-hidden="true"></i>
            </button>
            <button class="pill" data-val="SPORTS" aria-label="Sports" title="Sports" onclick="setSection('SPORTS')">
                <i class="fa-solid fa-trophy" aria-hidden="true"></i>
            </button>
            <button class="pill" data-val="STEPS" aria-label="Pasos" title="Pasos" onclick="setSection('STEPS')">
                <i class="fa-solid fa-shoe-prints" aria-hidden="true"></i>
            </button>
        </div>
        <div id="gym-section">
            <?php require __DIR__ . '/gym.tpl.php'; ?>
        </div>
        <div id="bike-section" style="display:none">
            <?php require __DIR__ . '/bike.tpl.php'; ?>
        </div>
        <div id="running-section" style="display:none">
            <?php require __DIR__ . '/running.tpl.php'; ?>
        </div>
        <div id="sports-section" style="display:none">
            <?php require __DIR__ . '/sports.tpl.php'; ?>
        </div>
        <div id="steps-section" style="display:none">
            <?php require __DIR__ . '/steps.tpl.php'; ?>
        </div>
    </main>

    <div id="toast"></div>

    <?php require __DIR__ . '/../partials/mobile_nav.tpl.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/gym.js"></script>
</body>

</html>