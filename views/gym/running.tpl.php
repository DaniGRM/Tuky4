<div class="section-header">
    <span class="section-title">Running</span>
    <button class="btn btn-primary" onclick="openModal('modal-running-activity')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Nueva actividad
    </button>
</div>
<div class="grid" id="running-activity-grid">
    <div class="empty-state" id="running-empty-state" style="grid-column:1/-1">
        <p>Aun no hay actividades de running.</p>
    </div>
</div>

<!-- ── Modal: Anadir actividad running ── -->
<div class="overlay" id="modal-running-activity" onclick="closeOnBg(event,'modal-running-activity')">
    <div class="modal">
        <h2>Nueva actividad de running</h2>
        <div class="field">
            <label>Nombre de la actividad</label>
            <input type="text" id="running-activity-name" placeholder="ej. Rodaje suave, Series 8x400..." autocomplete="off">
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modal-running-activity')">Cancelar</button>
            <button class="btn btn-primary" onclick="addRunningActivity()">Guardar</button>
        </div>
    </div>
</div>

<!-- ── Modal: Anadir registro running ── -->
<div class="overlay" id="modal-running-record" onclick="closeOnBg(event,'modal-running-record')">
    <div class="modal">
        <h2 id="running-record-title">Registrar sesion</h2>
        <input type="hidden" id="running-activity-id">

        <div class="field">
            <label>Fecha</label>
            <input type="date" id="running-date">
        </div>

        <div class="field">
            <label>Distancia (km)</label>
            <input type="number" id="running-distance" step="0.01" min="0" placeholder="0.00">
        </div>

        <div class="field">
            <label>Velocidad media (km/h)</label>
            <input type="number" id="running-avg-speed" step="0.01" min="0" placeholder="0.00">
        </div>

        <div class="field">
            <label>Elevacion (m)</label>
            <input type="number" id="running-elevation" step="1" placeholder="0">
        </div>

        <div class="field">
            <label>Duracion (min)</label>
            <input type="number" id="running-duration" step="1" min="1" placeholder="0">
        </div>

        <div class="chart-meta" id="running-preview-meta" style="margin-top:-.2rem;margin-bottom:.5rem">
            <span class="chart-formula" id="running-preview-text">Volumen estimado: -</span>
            <span class="chart-delta" id="running-progress-delta">Base</span>
        </div>

        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modal-running-record')">Cancelar</button>
            <button class="btn btn-primary" onclick="addRunningActivityRecord()">Guardar</button>
        </div>
    </div>
</div>

<!-- ── Modal: Historial running ── -->
<div class="overlay" id="modal-running-history" onclick="closeOnBg(event,'modal-running-history')">
    <div class="modal" style="max-width:520px">
        <h2 id="running-history-title">Historial</h2>
        <div class="chart-section" id="running-chart-section" style="display:none">
            <div class="chart-wrap">
                <canvas id="running-history-chart"></canvas>
            </div>
            <div class="chart-meta">
                <span class="chart-formula">Volumen running = distancia x velocidad x (1 + desnivel/1000)</span>
                <span class="chart-delta" id="running-chart-delta"></span>
            </div>
        </div>
        <div class="history-list" id="running-history-list"></div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modal-running-history')">Cerrar</button>
        </div>
    </div>
</div>
