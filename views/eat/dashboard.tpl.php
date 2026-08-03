<section id="eat-dashboard-section">
    <div class="section-header">
        <span class="section-title">Dashboard</span>
    </div>

    <div class="chart-meta" style="margin-top:-.2rem;margin-bottom:1rem">
        <span class="chart-formula">Consumo del dia</span>
        <span class="chart-delta flat" id="eat-day-total">0 kcal</span>
    </div>

    <div class="eat-meals-grid" id="eat-meals-grid">
        <button class="eat-meal-tile" onclick="openEatMealModal('breakfast')" aria-label="Desayuno">
            <i class="fa-solid fa-mug-saucer" aria-hidden="true"></i>
            <span class="eat-meal-name">Desayuno</span>
            <span class="eat-meal-kcal" id="eat-kcal-breakfast">0 kcal</span>
        </button>
        <button class="eat-meal-tile" onclick="openEatMealModal('lunch')" aria-label="Comida">
            <i class="fa-solid fa-bowl-food" aria-hidden="true"></i>
            <span class="eat-meal-name">Comida</span>
            <span class="eat-meal-kcal" id="eat-kcal-lunch">0 kcal</span>
        </button>
        <button class="eat-meal-tile" onclick="openEatMealModal('dinner')" aria-label="Cena">
            <i class="fa-solid fa-moon" aria-hidden="true"></i>
            <span class="eat-meal-name">Cena</span>
            <span class="eat-meal-kcal" id="eat-kcal-dinner">0 kcal</span>
        </button>
        <button class="eat-meal-tile" onclick="openEatMealModal('snacks')" aria-label="Snacks">
            <i class="fa-solid fa-cookie-bite" aria-hidden="true"></i>
            <span class="eat-meal-name">Snacks</span>
            <span class="eat-meal-kcal" id="eat-kcal-snacks">0 kcal</span>
        </button>
    </div>
</section>
