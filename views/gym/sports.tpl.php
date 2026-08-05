<div class="section-header">
    <span class="section-title">Sports</span>
    <button class="btn btn-primary" onclick="openModal('modal-sport')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Nuevo deporte
    </button>
</div>

<div class="grid" id="sports-grid">
    <div class="empty-state" id="sports-empty-state" style="grid-column:1/-1">
        <p>Aun no hay deportes registrados.</p>
    </div>
</div>

<div class="overlay" id="modal-sport" onclick="closeOnBg(event,'modal-sport')">
    <div class="modal">
        <h2>Nuevo deporte</h2>
        <div class="field">
            <label>Nombre</label>
            <input type="text" id="sport-name" placeholder="ej. Voley, Baloncesto, Escalada, Futbol" autocomplete="off">
        </div>
        <div class="field">
            <label>Icono</label>
            <select id="sport-icon">
                <option value="fa-solid fa-volleyball">Voley</option>
                <option value="fa-solid fa-basketball">Baloncesto</option>
                <option value="fa-solid fa-mountain">Escalada</option>
                <option value="fa-solid fa-futbol">Futbol</option>
                <option value="fa-solid fa-person-hiking">Senderismo</option>
                <option value="fa-solid fa-person-swimming">Natacion</option>
            </select>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modal-sport')">Cancelar</button>
            <button class="btn btn-primary" onclick="addSport()">Guardar</button>
        </div>
    </div>
</div>

<div class="overlay" id="modal-sport-register" onclick="closeOnBg(event,'modal-sport-register')">
    <div class="modal">
        <h2 id="sport-register-title">Registrar sesion</h2>
        <input type="hidden" id="sport-id">

        <div class="field">
            <label>Fecha</label>
            <input type="date" id="sport-date">
        </div>

        <div class="field">
            <label>Duracion (min)</label>
            <input type="number" id="sport-duration" step="1" min="1" placeholder="60">
        </div>

        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modal-sport-register')">Cancelar</button>
            <button class="btn btn-primary" onclick="addSportRegister()">Guardar</button>
        </div>
    </div>
</div>

<div class="overlay" id="modal-sport-history" onclick="closeOnBg(event,'modal-sport-history')">
    <div class="modal" style="max-width:520px">
        <h2 id="sport-history-title">Historial</h2>
        <div class="history-list" id="sport-history-list"></div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modal-sport-history')">Cerrar</button>
        </div>
    </div>
</div>
