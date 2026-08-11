<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0f1117">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Tuky4Money</title>
    <link rel="icon" type="image/x-icon" href="Tuky4Gym.ico">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" referrerpolicy="no-referrer">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
</head>

<body>

    <header class="app-header">
        <a href="/" class="btn btn-ghost app-header-logo" title="Inicio">
            <img src="Tuky4.png" alt="Logo" width="64" height="64">
        </a>
        <div class="app-header-copy">
            <span class="app-header-kicker">Tuky<span>4</span></span>
            <h1 class="app-header-title">Modulo <span>Money</span></h1>
        </div>
    </header>

    <main>
        <div class="section-switch pills" aria-label="Secciones Money">
            <button class="pill selected" data-val="DASHBOARD" onclick="setMoneySection('DASHBOARD')" title="Dashboard">
                <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
            </button>
            <button class="pill" data-val="CONFIG" onclick="setMoneySection('CONFIG')" title="Configuracion">
                <i class="fa-solid fa-gear" aria-hidden="true"></i>
            </button>
            <button class="pill" data-val="EXPENSES" onclick="setMoneySection('EXPENSES')" title="Lista de gastos">
                <i class="fa-solid fa-receipt" aria-hidden="true"></i>
            </button>
            <button class="pill" data-val="RULES" onclick="setMoneySection('RULES')" title="Reglas por concepto">
                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
            </button>
            <button class="pill" data-val="TAGS" onclick="setMoneySection('TAGS')" title="Tags">
                <i class="fa-solid fa-tags" aria-hidden="true"></i>
            </button>
        </div>

        <?php require __DIR__ . '/dashboard.tpl.php'; ?>
        <?php require __DIR__ . '/config.tpl.php'; ?>
        <?php require __DIR__ . '/expenses.tpl.php'; ?>
        <?php require __DIR__ . '/rules.tpl.php'; ?>
        <?php require __DIR__ . '/tags.tpl.php'; ?>
    </main>

    <div id="toast"></div>

    <?php require __DIR__ . '/../partials/mobile_nav.tpl.php'; ?>

    <div class="overlay" id="modal-money-movement" onclick="closeOnBg(event,'modal-money-movement')">
        <div class="modal">
            <h2 id="money-movement-modal-title">Editar movimiento</h2>
            <input type="hidden" id="money-movement-id" value="">
            <input type="hidden" id="money-movement-kind" value="expense">

            <div class="field">
                <label>Concepto banco</label>
                <input type="text" id="money-movement-source-item" autocomplete="off" readonly>
            </div>
            <div class="field">
                <label>Nombre visible</label>
                <input type="text" id="money-movement-name" autocomplete="off" placeholder="ej. Supermercado, Suscripcion musica">
            </div>
            <div class="field">
                <label>Importe</label>
                <input type="text" id="money-movement-amount" readonly>
            </div>
            <div class="field">
                <label>Fecha</label>
                <input type="text" id="money-movement-date" readonly>
            </div>
            <div class="field" id="money-movement-expense-type-wrap">
                <label style="display:flex;align-items:center;gap:.55rem">
                    <input type="checkbox" id="money-movement-is-fixed" style="width:auto">
                    Tratar como gasto fijo
                </label>
            </div>
            <div class="field">
                <label>Tag (opcional)</label>
                <select id="money-movement-tag-id"></select>
            </div>
            <div class="field">
                <label>Nueva tag (opcional)</label>
                <input type="text" id="money-movement-tag-name" autocomplete="off" placeholder="ej. Casa, Ocio, Transporte">
            </div>
            <div class="field">
                <label style="display:flex;align-items:center;gap:.55rem">
                    <input type="checkbox" id="money-movement-save-rule" style="width:auto">
                    Guardar regla para este concepto
                </label>
            </div>
            <div class="field" id="money-movement-rule-apply-wrap" style="display:none">
                <label>Aplicar regla a</label>
                <select id="money-movement-rule-apply-to">
                    <option value="expense">Solo gastos</option>
                    <option value="income">Solo ingresos</option>
                    <option value="both">Gastos e ingresos</option>
                </select>
            </div>

            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('modal-money-movement')">Cancelar</button>
                <button class="btn btn-primary" onclick="saveMoneyMovement()">Guardar</button>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script src="assets/js/money.js"></script>
</body>

</html>
