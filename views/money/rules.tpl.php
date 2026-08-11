<section id="money-rules-section" style="display:none">
    <div class="section-header">
        <span class="section-title">Reglas por concepto bancario</span>
    </div>

    <div class="card" style="margin-bottom:.9rem">
        <div class="card-record">Si llega de nuevo el mismo concepto del banco, se aplicara automaticamente el nombre visible, tipo y tag definidos aqui.</div>
    </div>

    <div class="card" style="margin-bottom:.9rem">
        <div class="card-name">Nueva regla</div>
        <div class="money-quick-grid">
            <div class="field" style="margin-bottom:0">
                <label>Concepto banco</label>
                <input type="text" id="money-rule-bank-item" placeholder="ej. BIZUM TRANSFER">
            </div>
            <div class="field" style="margin-bottom:0">
                <label>Nombre visible</label>
                <input type="text" id="money-rule-pretty-name" placeholder="ej. Cena con amigos">
            </div>
            <div class="field" style="margin-bottom:0">
                <label>Aplicar a</label>
                <select id="money-rule-apply-to">
                    <option value="expense">Solo gastos</option>
                    <option value="income">Solo ingresos</option>
                    <option value="both">Gastos e ingresos</option>
                </select>
            </div>
            <div class="field" style="margin-bottom:0">
                <label>Tipo gasto</label>
                <select id="money-rule-expense-type">
                    <option value="variable">Variable</option>
                    <option value="fixed">Fijo</option>
                </select>
            </div>
            <div class="field" style="margin-bottom:0">
                <label>Tag</label>
                <select id="money-rule-tag-id"></select>
            </div>
            <div class="field" style="margin-bottom:0">
                <label>Nueva tag (opcional)</label>
                <input type="text" id="money-rule-tag-name" placeholder="ej. Transporte">
            </div>
            <div class="field" style="margin-bottom:0;align-self:end">
                <button class="btn btn-primary" onclick="saveMoneyRuleFromForm()">Guardar regla</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="history-list" id="money-rules-list"></div>
    </div>
</section>
