<section id="task-list-section" style="display:none">
    <div class="section-header">
        <span class="section-title">Tareas</span>
    </div>
    <div class="filter-bar" id="task-filter-bar">
        <button class="pill selected" data-val="open" onclick="setTaskFilter('open')">Abiertas</button>
        <button class="pill" data-val="next" onclick="setTaskFilter('next')">Siguientes</button>
        <button class="pill" data-val="scheduled" onclick="setTaskFilter('scheduled')">Con fecha</button>
        <button class="pill" data-val="waiting" onclick="setTaskFilter('waiting')">Esperando</button>
        <button class="pill" data-val="someday" onclick="setTaskFilter('someday')">Algun dia</button>
        <button class="pill" data-val="done" onclick="setTaskFilter('done')">Hechas</button>
    </div>
    <div class="task-list" id="task-all-list"></div>
</section>
