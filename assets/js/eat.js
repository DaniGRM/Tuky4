let portions = [];
let units = [];
let tags = [];
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
	energy: {
		weight_kg: 0,
		steps: 0,
		profile: { sex: 'male', height_cm: 170, age: 30 },
		consumed_kcal: 0,
		tef_kcal: 0,
		bmr_kcal: 0,
		steps_kcal: 0,
		workouts_kcal: 0,
		burned_total_kcal: 0,
		net_kcal: 0,
	},
};
let selectedMealRationId = 0;
let isAddingMealRation = false;
let selectedMealTagFilter = '';

function setEatSection(section) {
	document.querySelectorAll('.section-switch .pill').forEach(p =>
		p.classList.toggle('selected', p.dataset.val === section)
	);

	const dashboard = document.getElementById('eat-dashboard-section');
	const crud = document.getElementById('eat-crud-section');
	const tagsSection = document.getElementById('eat-tags-section');
	if (dashboard) dashboard.style.display = section === 'DASHBOARD' ? '' : 'none';
	if (crud) crud.style.display = section === 'CRUD' ? '' : 'none';
	if (tagsSection) tagsSection.style.display = section === 'TAGS' ? '' : 'none';
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
	const ids = ['portion-id', 'portion-name', 'portion-amount', 'portion-calories', 'portion-tag'];
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

function normalizeTagName(name) {
	return String(name || '').trim().toLowerCase();
}

function normalizeHexColor(color) {
	const value = String(color || '').trim().toUpperCase();
	if (!value) return '';
	if (/^#[0-9A-F]{6}$/.test(value)) return value;
	if (/^#[0-9A-F]{3}$/.test(value)) {
		return `#${value[1]}${value[1]}${value[2]}${value[2]}${value[3]}${value[3]}`;
	}
	return '';
}

function hexToRgb(color) {
	const hex = normalizeHexColor(color);
	if (!hex) return null;
	return {
		r: parseInt(hex.slice(1, 3), 16),
		g: parseInt(hex.slice(3, 5), 16),
		b: parseInt(hex.slice(5, 7), 16),
	};
}

function getTagById(tagId) {
	const id = parseInt(tagId, 10) || 0;
	if (id <= 0) return null;
	return tags.find(t => (parseInt(t.id, 10) || 0) === id) || null;
}

function buildTagInlineStyle(color) {
	const value = buildTagStyleValue(color);
	return value ? ` style="${value}"` : '';
}

function buildTagStyleValue(color) {
	const hex = normalizeHexColor(color);
	const rgb = hexToRgb(hex);
	if (!rgb || !hex) return '';
	const soft = `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, .16)`;
	return `border-color:${hex};background:${soft};color:${hex}`;
}

function setTagOptions(rawTags) {
	const list = Array.isArray(rawTags) ? rawTags : [];
	tags = list
		.map(t => ({
			id: parseInt(t.id, 10) || 0,
			name: String(t.name || '').trim(),
			color: normalizeHexColor(t.color || ''),
		}))
		.filter(t => t.id > 0 && t.name !== '');

	const datalist = document.getElementById('portion-tag-list');
	if (datalist) {
		datalist.innerHTML = '';
		tags.forEach(tag => {
			const opt = document.createElement('option');
			opt.value = tag.name;
			datalist.appendChild(opt);
		});
	}

	renderMealTagFilterOptions();
	renderTagsPanel();
}

function collectTagsFromPortions() {
	const byName = new Map();
	portions.forEach(p => {
		const tagName = String(p.tag_name || '').trim();
		const tagId = parseInt(p.tag_id, 10) || 0;
		if (!tagName || tagId <= 0) return;
		const key = normalizeTagName(tagName);
		if (!byName.has(key)) {
			const color = normalizeHexColor(p.tag_color || getTagById(tagId)?.color || '');
			byName.set(key, { id: tagId, name: tagName, color });
		}
	});
	return Array.from(byName.values()).sort((a, b) => a.name.localeCompare(b.name, 'es'));
}

function renderTagsPanel() {
	const grid = document.getElementById('eat-tags-grid');
	if (!grid) return;

	grid.innerHTML = '';
	if (!tags.length) {
		const empty = document.createElement('div');
		empty.className = 'empty-state';
		empty.id = 'eat-tags-empty';
		empty.style.gridColumn = '1/-1';
		empty.innerHTML = '<p>Aun no hay tags creadas.</p>';
		grid.appendChild(empty);
		return;
	}

	tags.forEach(tag => {
		const card = document.createElement('div');
		card.className = 'card eat-tag-card';
		const color = normalizeHexColor(tag.color || '');
		const chipStyle = buildTagInlineStyle(color);
		const inputValue = color || '#6C63FF';
		card.innerHTML = `
			<div class="eat-tag-card-head">
				<span class="eat-tag-chip"${chipStyle}>#${esc(tag.name)}</span>
			</div>
			<div class="eat-tag-card-controls">
				<input type="color" value="${inputValue}" onchange="updateTagColor(${tag.id}, this.value)">
				<button class="btn btn-primary btn-sm" onclick="updateTagColor(${tag.id}, document.querySelector('#tag-color-${tag.id}')?.value || '${inputValue}')" style="display:none">Guardar</button>
				<button class="btn btn-ghost btn-sm" onclick="clearTagColor(${tag.id})">Limpiar</button>
			</div>
		`;
		const colorInput = card.querySelector('input[type="color"]');
		if (colorInput) {
			colorInput.id = `tag-color-${tag.id}`;
		}
		grid.appendChild(card);
	});
}

async function updateTagColor(tagId, color) {
	const id = parseInt(tagId, 10) || 0;
	if (id <= 0) return;
	const normalized = normalizeHexColor(color);
	if (!normalized) {
		toast('Color invalido');
		return;
	}

	const res = await api('/api/eat/tags/update-color', { id, color: normalized });
	if (!res.ok) {
		toast(res.msg || 'Error actualizando color');
		return;
	}

	const tag = getTagById(id);
	if (tag) tag.color = normalized;
	applyTagColorsToPortions();
	renderTagsPanel();
	renderPortions(portions);
	renderMealTagFilterOptions();
	renderMealRationOptions();
	toast('Color guardado');
}

async function clearTagColor(tagId) {
	const id = parseInt(tagId, 10) || 0;
	if (id <= 0) return;

	const res = await api('/api/eat/tags/update-color', { id, color: '' });
	if (!res.ok) {
		toast(res.msg || 'Error limpiando color');
		return;
	}

	const tag = getTagById(id);
	if (tag) tag.color = '';
	applyTagColorsToPortions();
	renderTagsPanel();
	renderPortions(portions);
	renderMealTagFilterOptions();
	renderMealRationOptions();
	toast('Color limpiado');
}

function applyTagColorsToPortions() {
	portions = portions.map(p => {
		const tag = getTagById(p.tag_id);
		return {
			...p,
			tag_color: normalizeHexColor(tag?.color || p.tag_color || ''),
		};
	});
}

function renderMealTagFilterOptions() {
	const container = document.getElementById('eat-meal-tag-filter-badges');
	if (!container) return;

	const merged = (() => {
		const mergedMap = new Map();
		const addTag = (tag) => {
			const name = String(tag?.name || '').trim();
			const id = parseInt(tag?.id, 10) || 0;
			const color = normalizeHexColor(tag?.color || '');
			if (!name || id <= 0) return;
			const key = normalizeTagName(name);
			if (!mergedMap.has(key)) {
				mergedMap.set(key, { id, name, color });
				return;
			}

			const current = mergedMap.get(key);
			if (current && !current.color && color) {
				current.color = color;
			}
		};
		tags.forEach(addTag);
		collectTagsFromPortions().forEach(addTag);
		return Array.from(mergedMap.values()).sort((a, b) => a.name.localeCompare(b.name, 'es'));
	})();

	const previous = selectedMealTagFilter;
	container.innerHTML = '';

	const createBadge = (idValue, label, color = '') => {
		const btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'eat-tag-badge' + (String(selectedMealTagFilter) === String(idValue) ? ' active' : '');
		btn.textContent = label;
		btn.setAttribute('onclick', `onMealTagFilterChange('${String(idValue)}')`);
		const styleValue = buildTagStyleValue(color);
		if (styleValue) {
			btn.style.cssText = styleValue;
		}
		return btn;
	};

	container.appendChild(createBadge('', 'Todas'));
	merged.forEach(tag => {
		container.appendChild(createBadge(String(tag.id), tag.name, tag.color || ''));
	});

	if (previous && merged.some(t => String(t.id) === String(previous))) {
		selectedMealTagFilter = String(previous);
	} else {
		selectedMealTagFilter = '';
	}

	Array.from(container.querySelectorAll('.eat-tag-badge')).forEach(btn => {
		const active = btn.getAttribute('onclick') === `onMealTagFilterChange('${selectedMealTagFilter}')`;
		btn.classList.toggle('active', active);
	});
}

function onMealTagFilterChange(tagId = '') {
	selectedMealTagFilter = String(tagId || '');
	renderMealTagFilterOptions();
	renderMealRationOptions();
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
		const tagName = String(p.tag_name || '').trim();
		const tagColor = normalizeHexColor(p.tag_color || getTagById(p.tag_id)?.color || '');
		const tagBadge = tagName ? `<span class="badge"${buildTagInlineStyle(tagColor)}>#${esc(tagName)}</span>` : '';
		card.innerHTML = `
			<div class="card-name">${esc(p.name)}</div>
			<div class="card-record">
				<span class="badge">${unitIcon} ${formatAmount(p.amount)} ${esc(unitName)}</span>
				${tagBadge}
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

	const energy = eatDashboard.energy || {};
	const energyList = document.getElementById('eat-energy-list');
	if (!energyList) return;

	const consumed = parseInt(energy.consumed_kcal, 10) || 0;
	const bmr = parseInt(energy.bmr_kcal, 10) || 0;
	const steps = parseInt(energy.steps_kcal, 10) || 0;
	const workouts = parseInt(energy.workouts_kcal, 10) || 0;
	const tef = parseInt(energy.tef_kcal, 10) || 0;
	const burned = parseInt(energy.burned_total_kcal, 10) || 0;
	const net = parseInt(energy.net_kcal, 10) || 0;

	const maxValue = Math.max(consumed, bmr, steps, workouts, tef, burned, 1);
	const widthPct = (value) => Math.max(3, Math.min(100, Math.round((value / maxValue) * 100)));

	const netClass = net <= 0 ? 'deficit' : 'surplus';
	const netLabel = net <= 0 ? `Deficit ${Math.abs(net)} kcal` : `Superavit +${net} kcal`;

	const metrics = [
		{ cls: 'consumed', label: 'Ingeridas', value: consumed },
		{ cls: 'bmr', label: 'BMR (Mifflin-St Jeor)', value: bmr },
		{ cls: 'steps', label: 'Actividad por pasos', value: steps },
		{ cls: 'workout', label: 'Entrenamientos', value: workouts },
		{ cls: 'tef', label: 'TEF (10%)', value: tef },
		{ cls: 'burned', label: 'Gasto total estimado', value: burned },
	];

	const metricHtml = metrics.map(metric => `
		<div class="eat-energy-row ${metric.cls}">
			<div class="eat-energy-row-head">
				<span class="eat-energy-label">${metric.label}</span>
				<span class="eat-energy-row-value">${metric.value} kcal</span>
			</div>
			<div class="eat-energy-bar"><span style="--bar-width:${widthPct(metric.value)}%"></span></div>
		</div>
	`).join('');

	energyList.innerHTML = `
		<div class="eat-energy-hero">
			<div>
				<div class="eat-energy-hero-title">Balance</div>
			</div>
			<span class="eat-energy-net ${netClass}">${net} kcal</span>
		</div>
		${metricHtml}
	`;
}

function renderMealRationOptions() {
	const grid = document.getElementById('eat-meal-ration-grid');
	if (!grid) return;

	grid.innerHTML = '';
	const filtered = selectedMealTagFilter
		? portions.filter(p => String(parseInt(p.tag_id, 10) || 0) === String(selectedMealTagFilter))
		: portions;

	if (!filtered.length) {
		grid.innerHTML = '<p class="eat-ration-empty">No hay raciones disponibles.</p>';
		return;
	}

	filtered.forEach(p => {
		const pid = parseInt(p.id, 10);
		const card = document.createElement('button');
		card.type = 'button';
		card.className = `eat-ration-card`;
		card.setAttribute('onclick', `selectMealRation(${pid})`);
		card.setAttribute('aria-label', `Seleccionar ${p.name}`);
		const unitIcon = p.unit_icon ? `<i class="${esc(p.unit_icon)}" aria-hidden="true"></i>` : '';
		const tagName = String(p.tag_name || '').trim();
		const tagColor = normalizeHexColor(p.tag_color || getTagById(p.tag_id)?.color || '');
		const tagRow = tagName ? `<span class="eat-ration-tag"${buildTagInlineStyle(tagColor)}>#${esc(tagName)}</span>` : '';
		card.innerHTML = `
			<span class="eat-ration-amount">${formatAmount(p.amount)} ${unitIcon}</span>
			<span class="eat-ration-name">${esc(p.name)}</span>
			${tagRow}
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
	applyTagColorsToPortions();
	renderPortions(portions);
	renderMealTagFilterOptions();
	renderMealRationOptions();
}

async function loadTags() {
	const res = await api('/api/eat/tags/list');
	if (!res.ok) {
		setTagOptions(collectTagsFromPortions());
		return;
	}
	setTagOptions(res.data || []);
	applyTagColorsToPortions();
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
		energy: data.energy || {
			weight_kg: 0,
			steps: 0,
			profile: { sex: 'male', height_cm: 170, age: 30 },
			consumed_kcal: 0,
			tef_kcal: 0,
			bmr_kcal: 0,
			steps_kcal: 0,
			workouts_kcal: 0,
			burned_total_kcal: 0,
			net_kcal: 0,
		},
	};

	renderDashboardSummary();
	const currentMeal = document.getElementById('eat-meal-key')?.value || 'breakfast';
	renderEatMealList(currentMeal);
}

function openEatMealModal(mealKey) {
	if (!EAT_MEALS[mealKey]) return;
	document.getElementById('eat-meal-key').value = mealKey;
	document.getElementById('eat-meal-modal-title').textContent = EAT_MEALS[mealKey].label;
	renderMealTagFilterOptions();
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
	document.getElementById('portion-tag').value = String(p.tag_name || '');
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
	const tag_name = document.getElementById('portion-tag').value.trim();
	const calories = parseInt(document.getElementById('portion-calories').value, 10) || 0;

	if (!name || amount <= 0 || unit_id <= 0 || calories <= 0) {
		toast('Completa nombre, cantidad, unidad y calorias');
		return;
	}

	const path = id > 0 ? '/api/eat/portions/update' : '/api/eat/portions/add';
	const body = id > 0
		? { id, name, amount, unit_id, tag_name, calories }
		: { name, amount, unit_id, tag_name, calories };
	const res = await api(path, body);
	if (!res.ok) {
		toast(res.msg || 'Error guardando racion');
		return;
	}

	closeModal('modal-portion');
	resetPortionForm();
	toast(id > 0 ? 'Racion actualizada' : 'Racion creada');
	await loadPortions();
	await loadTags();
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
	await loadTags();
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

Promise.all([loadUnits(), loadPortions(), loadTags()]);
loadEatDashboard();
