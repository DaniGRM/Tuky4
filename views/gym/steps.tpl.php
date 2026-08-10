<div class="section-header">
    <span class="section-title">Pasos</span>
    <div class="eat-date-nav" aria-label="Navegacion por fecha de pasos">
        <button type="button" class="eat-date-btn" id="steps-day-prev" onclick="shiftStepsDate(-1)" aria-label="Dia anterior">&lt;</button>
        <span class="eat-date-label" id="steps-day-label">Hoy</span>
        <button type="button" class="eat-date-btn" id="steps-day-next" onclick="shiftStepsDate(1)" aria-label="Dia siguiente">&gt;</button>
    </div>
</div>

<div class="card" style="max-width:480px;margin:0 auto;gap:.9rem">
    <div class="card-name">Configurador diario</div>

    <div class="field" style="margin-bottom:0">
        <label>Pasos</label>
    </div>
    <div class="card-record" style="justify-content:center">
        Escribe los pasos del dia
    </div>

    <div class="weight-ctrl">
        <input type="number" class="weight-val" id="steps-display" min="0" step="100" inputmode="numeric" oninput="onStepsInputChange()">
    </div>

    <hr style="margin:.25rem 0 .35rem">

    <div class="field" style="margin-bottom:0">
        <label>Peso (kg)</label>
    </div>
    <div class="card-record" style="justify-content:center">
        Ajusta tu peso del dia en bloques de 0.1 kg
    </div>

    <div class="weight-ctrl">
        <button onclick="changeDailyWeight(-0.1)">-</button>
        <div class="weight-val" id="daily-weight-display">0.0</div>
        <button onclick="changeDailyWeight(0.1)">+</button>
    </div>
</div>

<div class="card" style="max-width:640px;margin:1rem auto 0;gap:.8rem">
    <div class="card-name">Perfil y objetivos</div>

    <div class="steps-config-grid">
        <div class="field">
            <label>Sexo</label>
            <select id="ui-sex">
                <option value="male">Hombre</option>
                <option value="female">Mujer</option>
            </select>
        </div>

        <div class="field">
            <label>Edad</label>
            <input type="number" id="ui-age" min="1" max="120" step="1" placeholder="30">
        </div>

        <div class="field">
            <label>Altura (cm)</label>
            <input type="number" id="ui-height-cm" min="80" max="260" step="0.1" placeholder="170">
        </div>

        <div class="field">
            <label>Meta pasos/dia</label>
            <input type="number" id="ui-steps-goal" min="0" step="100" placeholder="10000">
        </div>

        <div class="field" style="grid-column:1/-1">
            <label>Objetivo deficit calorico (kcal)</label>
            <input type="number" id="ui-deficit-goal" step="1" placeholder="300">
        </div>
    </div>

    <div class="modal-footer" style="margin-top:.1rem">
        <button class="btn btn-primary" onclick="saveUserInfo()">Guardar perfil</button>
    </div>
</div>
