<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0f1117">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Tuky4Task</title>
    <link rel="icon" type="image/x-icon" href="Tuky4Gym.ico">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
</head>

<body>

    <header class="app-header">
        <a href="/" class="btn btn-ghost app-header-logo" title="Inicio">
            <img src="Tuky4.png" alt="Logo" width="64" height="64">
        </a>
        <div class="app-header-copy">
            <span class="app-header-kicker">Tuky<span>4</span></span>
            <h1 class="app-header-title">Modulo <span>Task</span></h1>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary btn-icon" onclick="openTaskModal()" title="Nueva tarea">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
            </button>
        </div>
    </header>

    <main>
        <form class="task-capture" onsubmit="return quickCaptureTask(event)">
            <input type="text" id="task-capture-input" maxlength="200" autocomplete="off" placeholder="Captura rapida: que tienes en la cabeza?">
            <button type="submit" class="btn btn-primary" title="Enviar al Inbox">
                <i class="fa-solid fa-inbox" aria-hidden="true"></i>
            </button>
        </form>

        <div class="section-switch pills" aria-label="Secciones Task">
            <button class="pill selected" data-val="DASHBOARD" onclick="setTaskSection('DASHBOARD')" title="Dashboard">
                <i class="fa-solid fa-sun" aria-hidden="true"></i>
            </button>
            <button class="pill" data-val="INBOX" onclick="setTaskSection('INBOX')" title="Inbox">
                <i class="fa-solid fa-inbox" aria-hidden="true"></i>
                <span class="task-pill-count" id="task-inbox-count" style="display:none"></span>
            </button>
            <button class="pill" data-val="LIST" onclick="setTaskSection('LIST')" title="Todas las tareas">
                <i class="fa-solid fa-list-check" aria-hidden="true"></i>
            </button>
        </div>

        <?php require __DIR__ . '/dashboard.tpl.php'; ?>
        <?php require __DIR__ . '/inbox.tpl.php'; ?>
        <?php require __DIR__ . '/list.tpl.php'; ?>
    </main>

    <div id="toast"></div>

    <?php require __DIR__ . '/../partials/mobile_nav.tpl.php'; ?>

    <div class="overlay" id="modal-task" onclick="closeOnBg(event,'modal-task')">
        <div class="modal">
            <h2 id="task-modal-title">Nueva tarea</h2>
            <input type="hidden" id="task-id" value="">

            <div class="field">
                <label>Tarea</label>
                <input type="text" id="task-title" maxlength="200" autocomplete="off" placeholder="ej. Llamar al dentista">
            </div>
            <div class="field">
                <label>Notas (opcional)</label>
                <input type="text" id="task-notes" maxlength="500" autocomplete="off">
            </div>
            <div class="field">
                <label>Tipo</label>
                <select id="task-status">
                    <option value="inbox">Inbox (sin clarificar)</option>
                    <option value="next">Siguiente accion</option>
                    <option value="waiting">Esperando a otro</option>
                    <option value="someday">Algun dia / quiza</option>
                </select>
            </div>
            <div class="field">
                <label>Fecha concreta (opcional)</label>
                <div class="task-date-row">
                    <input type="text" id="task-date" readonly>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="setTaskDate(0)">Hoy</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="setTaskDate(1)">Manana</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="setTaskDate(null)">Quitar</button>
                </div>
            </div>
            <div class="field">
                <label>Contexto (opcional)</label>
                <input type="text" id="task-context" maxlength="60" autocomplete="off" placeholder="ej. casa, ordenador, calle">
            </div>
            <div class="field">
                <label>Deriva de (bloqueada hasta completar la origen)</label>
                <select id="task-parent"></select>
            </div>

            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('modal-task')">Cancelar</button>
                <button class="btn btn-primary" onclick="saveTask()">Guardar</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/task.js"></script>
</body>

</html>
