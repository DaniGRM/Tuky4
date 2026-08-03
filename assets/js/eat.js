let portions = [];
let units = [];
const EAT_MEALS = {
	breakfast: { label: 'Desayuno' },
	lunch: { label: 'Comida' },
	dinner: { label: 'Cena' },
	snacks: { label: 'Snacks' },
};
let eatDashboard = {
	date: '',
	meals: { breakfast: [], lunch: [], dinner: [], snacks: [] },
	totals: { breakfast: 0, lunch: 0, dinner: 0, snacks: 0, day_total: 0 },
};
let selectedMealRationId = 0;
let isAddingMealRation = false;

function setEatSection(section) {
	document.querySelectorAll('.section-switch .pill').forEach(p =>
		p.classList.toggle('selected', p.dataset.val === section)
	);

	const dashboard = document.getElementById('eat-dashboard-section');
	const crud = document.getElementById('eat-crud-section');
	if (dashboard) dashboard.style.display = section === 'DASHBOARD' ? '' : 'none';
	if (crud) crud.style.display = section === 'CRUD' ? '' : 'none';
}

function formatAmount(n) {
	const v = Number(n || 0);
	if (!Number.isFinite(v)) return '0';
	return v % 1 === 0 ? String(v) : v.toFixed(2);
}

function todayYmd() {
	const now = new Date();
	const y = now.getFullYear();
	const m = String(now.getMonth() + 1).padStart(2, '0');
	const d = String(now.getDate()).padStart(2, '0');
	return `${y}-${m}-${d}`;
}

function resetPortionForm() {
	const ids = ['portion-id', 'portion-name', 'portion-amount', 'portion-calories'];
	ids.forEach(id => {
		const el = document.getElementById(id);
		if (el) el.value = '';
	});
	const unitSelect = document.getElementById('portion-unit-id');
	if (unitSelect) {
		if (units.length > 0) {
			unitSelect.value = String(units[0].id);
		} else {
			unitSelect.value = '';
		}
	}
	const title = document.getElementById('portion-modal-title');
	if (title) title.textContent = 'Nueva racion';
	const saveBtn = document.getElementById('portion-save-btn');
	if (saveBtn) saveBtn.textContent = 'Guardar';
}

function renderUnitOptions(selectedId = null) {
	const sel = document.getElementById('portion-unit-id');
	if (!sel) return;

	sel.innerHTML = '';
	if (!units.length) {
		const opt = document.createElement('option');
		opt.value = '';
		opt.textContent = 'Sin unidades';
		sel.appendChild(opt);
		return;
	}

	units.forEach(u => {
		const opt = document.createElement('option');
		opt.value = String(u.id);
		opt.textContent = u.name;
		if (selectedId !== null && parseInt(selectedId, 10) === parseInt(u.id, 10)) {
			opt.selected = true;
		}
		sel.appendChild(opt);
	});
}

function renderPortions(data) {
	const grid = document.getElementById('portion-grid');
	if (!grid) return;

	grid.innerHTML = '';
	if (!data.length) {
		const empty = document.createElement('div');
		empty.className = 'empty-state';
		empty.id = 'portion-empty';
		empty.style.gridColumn = '1/-1';
		empty.innerHTML = '<p>Aun no hay raciones guardadas.</p>';
		grid.appendChild(empty);
		return;
	}

	data.forEach(p => {
		const card = document.createElement('div');
		card.className = 'card';
		const unitIcon = p.unit_icon ? `<i class="${esc(p.unit_icon)}" aria-hidden="true"></i>` : '';
		const unitName = p.unit_name || '';
		card.innerHTML = `
			<div class="card-name">${esc(p.name)}</div>
			<div class="card-record">
				<span class="badge">${unitIcon} ${formatAmount(p.amount)} ${esc(unitName)}</span>
				<span class="badge-green badge">${parseInt(p.calories, 10)} kcal</span>
			</div>
			<div class="card-actions">
				<button class="btn btn-primary btn-sm" onclick="editPortion(${p.id})">Editar</button>
				<button class="btn btn-ghost btn-sm btn-icon" title="Eliminar" onclick="deletePortion(${p.id},'${esc(p.name)}')">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2.5">
						<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
						<path d="M10 11v6M14 11v6M9 6V4h6v2"/>
					</svg>
				</button>
			</div>`;
		grid.appendChild(card);
	});
}

function renderDashboardSummary() {
	const totalEl = document.getElementById('eat-day-total');
	if (totalEl) totalEl.textContent = `${eatDashboard.totals.day_total || 0} kcal`;

	Object.keys(EAT_MEALS).forEach(key => {
		const mealTotal = eatDashboard.totals[key] || 0;
		const el = document.getElementById(`eat-kcal-${key}`);
		if (el) el.textContent = `${mealTotal} kcal`;
	});

	// const list = document.getElementById('eat-summary-list');
	// if (!list) return;
	// list.innerHTML = '';

	// Object.keys(EAT_MEALS).forEach(key => {
	// 	const item = document.createElement('div');
	// 	item.className = 'history-item';
	// 	item.innerHTML = `
	// 		<span class="hi-date">${EAT_MEALS[key].label}</span>
	// 		<span class="hi-detail"><span class="badge-green badge">${eatDashboard.totals[key] || 0} kcal</span></span>
	// 		<span class="hi-action"></span>`;
	// 	list.appendChild(item);
	// });
}

function renderMealRationOptions() {
	const grid = document.getElementById('eat-meal-ration-grid');
	if (!grid) return;

	grid.innerHTML = '';

	if (!portions.length) {
		grid.innerHTML = '<p class="eat-ration-empty">No hay raciones disponibles.</p>';
		return;
	}

	portions.forEach(p => {
		const pid = parseInt(p.id, 10);
		const card = document.createElement('button');
		card.type = 'button';
		card.className = `eat-ration-card`;
		card.setAttribute('onclick', `selectMealRation(${pid})`);
		card.setAttribute('aria-label', `Seleccionar ${p.name}`);
		const unitIcon = p.unit_icon ? `<i class="${esc(p.unit_icon)}" aria-hidden="true"></i>` : '';
		card.innerHTML = `
			<span class="eat-ration-amount">${formatAmount(p.amount)} ${unitIcon}</span>
			<span class="eat-ration-name">${esc(p.name)}</span>
			<span class="eat-ration-calories">${parseInt(p.calories, 10)} kcal</span>`;
		if (isAddingMealRation) card.disabled = true;
		grid.appendChild(card);
	});
}

async function selectMealRation(rationId) {
	if (isAddingMealRation) return;
	selectedMealRationId = parseInt(rationId, 10) || 0;
	renderMealRationOptions();
	if (selectedMealRationId > 0) {
		await addRationToMeal(selectedMealRationId);
	}
}

function renderEatMealList(mealKey) {
	const list = document.getElementById('eat-meal-list');
	if (!list) return;
	list.innerHTML = '';

	const items = eatDashboard.meals[mealKey] || [];
	if (!items.length) {
		list.innerHTML = '<p style="color:var(--muted);font-size:.9rem">No hay raciones registradas para esta comida.</p>';
		return;
	}

	const grouped = new Map();
	items.forEach(item => {
		const rationId = parseInt(item.ration_id, 10) || 0;
		const key = rationId > 0 ? `ration-${rationId}` : `row-${item.id}`;
		const amount = Number(item.amount || 0);
		const calories = parseInt(item.calories, 10) || 0;

		if (!grouped.has(key)) {
			grouped.set(key, {
				name: item.name,
				unit_name: item.unit_name,
				unit_icon: item.unit_icon,
				count: 0,
				totalAmount: 0,
				totalCalories: 0,
				ids: [],
			});
		}

		const current = grouped.get(key);
		current.count += 1;
		current.totalAmount += amount;
		current.totalCalories += calories;
		current.ids.push(parseInt(item.id, 10));
	});

	Array.from(grouped.values()).forEach(group => {
		const row = document.createElement('div');
		row.className = 'history-item';
		const icon = group.unit_icon ? `<i class="${esc(group.unit_icon)}" aria-hidden="true"></i>` : '';
		const removeId = group.ids[group.ids.length - 1] || 0;
		row.innerHTML = `
			<span class="hi-date">${esc(group.name)}</span>
			<span class="hi-detail">
				<span class="badge">${icon} ${formatAmount(group.totalAmount)} ${esc(group.unit_name)}</span>
				<span class="badge-green badge">${group.totalCalories} kcal</span>
			</span>
			<span class="hi-action">
				<button class="btn btn-ghost btn-sm btn-icon" title="Eliminar" onclick="deleteRationFromMeal(${removeId})">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2.5">
						<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
						<path d="M10 11v6M14 11v6M9 6V4h6v2"/>
					</svg>
				</button>
			</span>`;
		list.appendChild(row);
	});
}

async function loadUnits() {
	const res = await api('/api/eat/units/list');
	if (!res.ok) {
		toast(res.msg || 'Error cargando unidades');
		return;
	}
	units = Array.isArray(res.data) ? res.data : [];
	renderUnitOptions();
}

async function loadPortions() {
	const res = await api('/api/eat/portions/list');
	if (!res.ok) {
		toast(res.msg || 'Error cargando raciones');
		return;
	}
	portions = Array.isArray(res.data) ? res.data : [];
	renderPortions(portions);
	renderMealRationOptions();
}

async function loadEatDashboard() {
	const res = await api('/api/eat/dashboard/list', { date: todayYmd() });
	if (!res.ok) {
		toast(res.msg || 'Error cargando dashboard');
		return;
	}

	const data = res.data || {};
	eatDashboard = {
		date: data.date || todayYmd(),
		meals: data.meals || { breakfast: [], lunch: [], dinner: [], snacks: [] },
		totals: data.totals || { breakfast: 0, lunch: 0, dinner: 0, snacks: 0, day_total: 0 },
	};

	renderDashboardSummary();
	const currentMeal = document.getElementById('eat-meal-key')?.value || 'breakfast';
	renderEatMealList(currentMeal);
}

function openEatMealModal(mealKey) {
	if (!EAT_MEALS[mealKey]) return;
	document.getElementById('eat-meal-key').value = mealKey;
	document.getElementById('eat-meal-modal-title').textContent = EAT_MEALS[mealKey].label;
	renderMealRationOptions();
	renderEatMealList(mealKey);
	openModal('modal-eat-meal');
}

async function addRationToMeal(forcedRationId = null) {
	if (isAddingMealRation) return;
	const key = document.getElementById('eat-meal-key').value;
	const rationId = forcedRationId !== null
		? parseInt(forcedRationId, 10) || 0
		: selectedMealRationId;
	if (!EAT_MEALS[key]) {
		toast('Comida invalida');
		return;
	}
	if (rationId <= 0) {
		toast('Selecciona una racion');
		return;
	}

	isAddingMealRation = true;
	renderMealRationOptions();

	const res = await api('/api/eat/dashboard/add-ration', {
		date: todayYmd(),
		key,
		ration_id: rationId,
	});
	isAddingMealRation = false;
	if (!res.ok) {
		renderMealRationOptions();
		toast(res.msg || 'Error anadiendo racion');
		return;
	}

	toast('Racion anadida');
	await loadEatDashboard();
	renderMealRationOptions();
	renderEatMealList(key);
}

async function deleteRationFromMeal(recordRationId) {
	const res = await api('/api/eat/dashboard/delete-ration', { id: recordRationId });
	if (!res.ok) {
		toast(res.msg || 'Error eliminando racion');
		return;
	}

	toast('Racion eliminada');
	await loadEatDashboard();
}

function editPortion(id) {
	const p = portions.find(x => parseInt(x.id, 10) === parseInt(id, 10));
	if (!p) return;

	document.getElementById('portion-id').value = p.id;
	document.getElementById('portion-name').value = p.name;
	document.getElementById('portion-amount').value = formatAmount(p.amount);
	renderUnitOptions(p.unit_id);
	document.getElementById('portion-calories').value = parseInt(p.calories, 10);

	const title = document.getElementById('portion-modal-title');
	if (title) title.textContent = 'Editar racion';
	const saveBtn = document.getElementById('portion-save-btn');
	if (saveBtn) saveBtn.textContent = 'Actualizar';

	openModal('modal-portion');
}

function openCreatePortionModal() {
	resetPortionForm();
	openModal('modal-portion');
}

async function savePortion() {
	const id = parseInt(document.getElementById('portion-id').value, 10) || 0;
	const name = document.getElementById('portion-name').value.trim();
	const amount = Number(document.getElementById('portion-amount').value || 0);
	const unit_id = parseInt(document.getElementById('portion-unit-id').value, 10) || 0;
	const calories = parseInt(document.getElementById('portion-calories').value, 10) || 0;

	if (!name || amount <= 0 || unit_id <= 0 || calories <= 0) {
		toast('Completa nombre, cantidad, unidad y calorias');
		return;
	}

	const path = id > 0 ? '/api/eat/portions/update' : '/api/eat/portions/add';
	const body = id > 0 ? { id, name, amount, unit_id, calories } : { name, amount, unit_id, calories };
	const res = await api(path, body);
	if (!res.ok) {
		toast(res.msg || 'Error guardando racion');
		return;
	}

	closeModal('modal-portion');
	resetPortionForm();
	toast(id > 0 ? 'Racion actualizada' : 'Racion creada');
	await loadPortions();
	await loadEatDashboard();
}

async function deletePortion(id, name) {
	if (!confirm(`¿Eliminar racion "${name}"?`)) return;
	const res = await api('/api/eat/portions/delete', { id });
	if (!res.ok) {
		toast(res.msg || 'Error eliminando racion');
		return;
	}

	toast('Racion eliminada');
	await loadPortions();
	await loadEatDashboard();
}

const portionNameInput = document.getElementById('portion-name');
if (portionNameInput) {
	portionNameInput.addEventListener('keydown', e => {
		if (e.key === 'Enter') savePortion();
	});
}

document.getElementById('modal-portion')?.addEventListener('click', e => {
	if (e.target && e.target.id === 'modal-portion') return;
});

Promise.all([loadUnits(), loadPortions()]);
loadEatDashboard();
