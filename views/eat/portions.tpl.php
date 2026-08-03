<section id="eat-crud-section" style="display:none">
    <div class="section-header">
        <span class="section-title">CRUD de raciones</span>
        <button class="btn btn-primary" onclick="openCreatePortionModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Nueva racion
        </button>
    </div>

    <div class="grid" id="portion-grid">
        <div class="empty-state" id="portion-empty" style="grid-column:1/-1">
            <p>Aun no hay raciones guardadas.</p>
        </div>
    </div>
</section>
