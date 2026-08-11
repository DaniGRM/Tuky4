<section id="money-dashboard-section">
    <div class="section-header">
        <span class="section-title">Dashboard</span>
        <div style="display:flex;align-items:center;gap:.55rem">
            <button class="btn btn-ghost btn-sm" onclick="loadMoneyDashboard()">Actualizar</button>
        </div>
    </div>
    <div class="grid money-kpi-grid" id="money-kpi-grid"></div>

    <div class="grid" style="margin-top:1rem">
        <div class="card">
            <div class="card-name">Distribucion por tags</div>
            <div class="chart-wrap" style="height:240px"><canvas id="money-tags-chart"></canvas></div>
        </div>
        <div class="card">
            <div class="card-name">Estado mensual</div>
            <div class="chart-wrap" style="height:240px"><canvas id="money-balance-chart"></canvas></div>
        </div>
    </div>
</section>
