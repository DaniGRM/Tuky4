<section id="money-config-section" style="display:none">
    <div class="section-header">
        <span class="section-title">Configuracion financiera</span>
        <button class="btn btn-primary btn-sm" onclick="saveMoneySettings()">Guardar</button>
    </div>

    <div class="card" style="max-width:760px;margin:0 auto;gap:.8rem">
        <div class="steps-config-grid">
            <div class="field">
                <label>Nomina mensual</label>
                <input type="number" id="money-salary" min="0" step="0.01" placeholder="0.00">
            </div>
            <div class="field">
                <label>Cantidad actual</label>
                <input type="number" id="money-current-amount" step="0.01" placeholder="0.00">
            </div>
            <div class="field" style="grid-column:1/-1">
                <label>Meta de ahorro mensual</label>
                <input type="number" id="money-savings-goal" min="0" step="0.01" placeholder="0.00">
            </div>
        </div>
    </div>
</section>
