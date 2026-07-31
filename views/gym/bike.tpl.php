<div class="section-header">
    <span class="section-title">Ciclismo</span>
    <button class="btn btn-primary" onclick="openModal('modal-bike-activity')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Nueva actividad
    </button>
</div>
<div class="grid" id="bike-activity-grid">
    <div class="empty-state" id="bike-empty-state" style="grid-column:1/-1">
        <p>Aun no hay actividades de ciclismo.</p>
    </div>
</div>

<!-- ── Modal: Anadir actividad bike ── -->
<div class="overlay" id="modal-bike-activity" onclick="closeOnBg(event,'modal-bike-activity')">
    <div class="modal">
        <h2>Nueva actividad de ciclismo</h2>
        <div class="field">
            <label>Nombre de la actividad</label>
            <input type="text" id="bike-activity-name" placeholder="ej. Salida suave, Series en puerto..." autocomplete="off">
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modal-bike-activity')">Cancelar</button>
            <button class="btn btn-primary" onclick="addBikeActivity()">Guardar</button>
        </div>
    </div>
</div>

<!-- ── Modal: Anadir registro bike ── -->
<div class="overlay" id="modal-bike-record" onclick="closeOnBg(event,'modal-bike-record')">
    <div class="modal">
        <h2 id="bike-record-title">Registrar salida</h2>
        <input type="hidden" id="bike-activity-id">

        <div class="field">
            <label>Fecha</label>
            <input type="date" id="bike-date">
        </div>

        <div class="field">
            <label>Distancia (km)</label>
            <input type="number" id="bike-distance" step="0.01" min="0" placeholder="0.00">
        </div>

        <div class="field">
            <label>Velocidad media (km/h)</label>
            <input type="number" id="bike-avg-speed" step="0.01" min="0" placeholder="0.00">
        </div>

        <div class="field">
            <label>Elevacion (m)</label>
            <input type="number" id="bike-elevation" step="1" placeholder="0">
        </div>

        <div class="field">
            <label>Duracion (min)</label>
            <input type="number" id="bike-duration" step="1" min="1" placeholder="0">
        </div>

        <div class="chart-meta" id="bike-preview-meta" style="margin-top:-.2rem;margin-bottom:.5rem">
            <span class="chart-formula" id="bike-preview-text">Volumen estimado: -</span>
            <span class="chart-delta" id="bike-progress-delta">Base</span>
        </div>

        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modal-bike-record')">Cancelar</button>
            <button class="btn btn-primary" onclick="addBikeActivityRecord()">Guardar</button>
        </div>
    </div>
</div>

<!-- ── Modal: Historial bike ── -->
<div class="overlay" id="modal-bike-history" onclick="closeOnBg(event,'modal-bike-history')">
    <div class="modal" style="max-width:520px">
        <h2 id="bike-history-title">Historial</h2>
        <div class="chart-section" id="bike-chart-section" style="display:none">
            <div class="chart-wrap">
                <canvas id="bike-history-chart"></canvas>
            </div>
            <div class="chart-meta">
                <span class="chart-formula">Volumen bike = distancia x velocidad x (1 + desnivel/1000)</span>
                <span class="chart-delta" id="bike-chart-delta"></span>
            </div>
        </div>
        <div class="history-list" id="bike-history-list"></div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modal-bike-history')">Cerrar</button>
        </div>
    </div>
</div>
