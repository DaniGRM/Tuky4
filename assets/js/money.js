let moneyTags = [];
let moneyMovements = [];
let moneyRules = [];
let moneyDashboard = {
  date: '',
  settings: {
    monthly_salary: 0,
    current_amount: 0,
    savings_goal: 0,
  },
  kpis: {
    spent_today: 0,
    spent_month_to_date: 0,
    projected_month_spent: 0,
    income_today: 0,
    income_month_to_date: 0,
    monthly_available: 0,
    month_balance: 0,
    remaining_amount: 0,
    savings_goal: 0,
  },
  tag_breakdown: [],
  today_items: [],
};
let moneyTagsChart = null;
let moneyBalanceChart = null;

function setMoneySection(section) {
  document.querySelectorAll('.section-switch .pill').forEach(p =>
    p.classList.toggle('selected', p.dataset.val === section)
  );

  const dashboard = document.getElementById('money-dashboard-section');
  const config = document.getElementById('money-config-section');
  const expenses = document.getElementById('money-expenses-section');
  const rules = document.getElementById('money-rules-section');
  const tags = document.getElementById('money-tags-section');

  if (dashboard) dashboard.style.display = section === 'DASHBOARD' ? '' : 'none';
  if (config) config.style.display = section === 'CONFIG' ? '' : 'none';
  if (expenses) expenses.style.display = section === 'EXPENSES' ? '' : 'none';
  if (rules) rules.style.display = section === 'RULES' ? '' : 'none';
  if (tags) tags.style.display = section === 'TAGS' ? '' : 'none';
}

function moneyFmt(n) {
  const v = Number(n || 0);
  if (!Number.isFinite(v)) return '0.00';
  return v.toFixed(2);
}

function todayYmd() {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, '0');
  const d = String(now.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
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

function buildTagInlineStyle(color) {
  const hex = normalizeHexColor(color);
  const rgb = hexToRgb(hex);
  if (!rgb || !hex) return '';
  const soft = `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, .16)`;
  return ` style="border-color:${hex};background:${soft};color:${hex}"`;
}

function renderMoneyTagOptions() {
  const selects = [
    document.getElementById('money-movement-tag-id'),
    document.getElementById('money-rule-tag-id'),
  ];

  selects.forEach(sel => {
    if (!sel) return;
    const previous = sel.value;
    sel.innerHTML = '';

    const empty = document.createElement('option');
    empty.value = '';
    empty.textContent = 'Sin tag';
    sel.appendChild(empty);

    moneyTags.forEach(tag => {
      const opt = document.createElement('option');
      opt.value = String(tag.id);
      opt.textContent = tag.name;
      sel.appendChild(opt);
    });

    if (previous && Array.from(sel.options).some(o => o.value === previous)) {
      sel.value = previous;
    }
  });
}

function renderMoneyKpis() {
  const root = document.getElementById('money-kpi-grid');
  if (!root) return;

  const k = moneyDashboard.kpis || {};
  const rows = [
    { title: 'Gasto hoy', value: `${moneyFmt(k.spent_today)} EUR` },
    { title: 'Ingreso hoy', value: `${moneyFmt(k.income_today)} EUR` },
    { title: 'Gasto mes (hasta hoy)', value: `${moneyFmt(k.spent_month_to_date)} EUR` },
    { title: 'Ingreso mes (hasta hoy)', value: `${moneyFmt(k.income_month_to_date)} EUR` },
    { title: 'Gasto proyectado mes', value: `${moneyFmt(k.projected_month_spent)} EUR` },
    { title: 'Saldo actual banco', value: `${moneyFmt(k.remaining_amount)} EUR` },
  ];

  root.innerHTML = rows.map(row => `
    <div class="card money-kpi-card">
      <div class="money-kpi-title">${esc(row.title)}</div>
      <div class="money-kpi-value">${esc(row.value)}</div>
    </div>
  `).join('');
}

function renderMoneyCharts() {
  const tagCanvas = document.getElementById('money-tags-chart');
  const balCanvas = document.getElementById('money-balance-chart');
  if (!tagCanvas || !balCanvas || typeof Chart !== 'function') return;

  if (moneyTagsChart) {
    moneyTagsChart.destroy();
    moneyTagsChart = null;
  }
  if (moneyBalanceChart) {
    moneyBalanceChart.destroy();
    moneyBalanceChart = null;
  }

  const tagData = Array.isArray(moneyDashboard.tag_breakdown) ? moneyDashboard.tag_breakdown : [];
  const labels = tagData.map(x => x.name || 'Sin tag');
  const values = tagData.map(x => Number(x.total || 0));
  const colors = tagData.map(x => x.color || '#94A3B8');

  moneyTagsChart = new Chart(tagCanvas.getContext('2d'), {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data: values,
        backgroundColor: colors,
        borderColor: '#1a1d27',
        borderWidth: 2,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { labels: { color: '#8892a4' } },
      },
    },
  });

  const k = moneyDashboard.kpis || {};
  moneyBalanceChart = new Chart(balCanvas.getContext('2d'), {
    type: 'bar',
    data: {
      labels: ['Disponible base', 'Gasto proyectado', 'Ingresos mes'],
      datasets: [{
        label: 'EUR',
        data: [
          Number(k.monthly_available || 0),
          Number(k.projected_month_spent || 0),
          Number(k.income_month_to_date || 0),
        ],
        backgroundColor: ['#4ade80', '#ff6584', '#0ea5e9'],
        borderRadius: 8,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
      },
      scales: {
        x: { ticks: { color: '#8892a4' }, grid: { color: '#2e3348' } },
        y: { ticks: { color: '#8892a4' }, grid: { color: '#2e3348' } },
      },
    },
  });
}

function renderMoneyMovements() {
  const list = document.getElementById('money-expenses-list');
  if (!list) return;

  const all = Array.isArray(moneyMovements) ? moneyMovements : [];
  list.innerHTML = all.length
    ? all.map(item => moneyMovementRow(item)).join('')
    : '<p style="color:var(--muted);font-size:.9rem">No hay movimientos para esta fecha.</p>';
}

function moneyMovementRow(item) {
  const tag = item.tag_name ? `<span class="badge">#${esc(item.tag_name)}</span>` : '';
  const isIncome = item.movement_kind === 'income';
  const kindText = isIncome ? 'Ingreso' : `Gasto ${item.expense_type === 'fixed' ? 'fijo' : 'variable'}`;
  const kindCls = isIncome ? 'badge-green badge' : 'badge';
  const amount = isIncome ? `+${moneyFmt(item.amount)} EUR` : `-${moneyFmt(item.amount)} EUR`;

  return `
    <div class="history-item">
      <span class="hi-date">${esc(formatDate(item.movement_date || ''))}</span>
      <span class="hi-detail">
        <strong>${esc(item.name || '')}</strong>
        <span class="${kindCls}">${esc(amount)}</span>
        ${tag}
        <span style="font-size:.76rem;color:var(--muted)">${esc(kindText)}</span>
        <span style="font-size:.74rem;color:var(--muted)">${esc(item.source_item || '')}</span>
      </span>
      <span class="hi-action">
        <button class="btn btn-ghost btn-sm" onclick="editMoneyMovement(${parseInt(item.id, 10) || 0}, '${item.movement_kind === 'income' ? 'income' : 'expense'}')">Editar</button>
        <button class="btn btn-ghost btn-sm btn-icon" title="Eliminar" onclick="deleteMoneyMovement(${parseInt(item.id, 10) || 0}, '${item.movement_kind === 'income' ? 'income' : 'expense'}')">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2.5">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
            <path d="M10 11v6M14 11v6M9 6V4h6v2"/>
          </svg>
        </button>
      </span>
    </div>
  `;
}

function renderMoneyTags() {
  const grid = document.getElementById('money-tags-grid');
  if (!grid) return;

  if (!moneyTags.length) {
    grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><p>Aun no hay tags de gastos.</p></div>';
    return;
  }

  grid.innerHTML = moneyTags.map(tag => {
    const color = normalizeHexColor(tag.color || '');
    const chipStyle = buildTagInlineStyle(color);
    const inputValue = color || '#6C63FF';

    return `
      <div class="card eat-tag-card">
        <div class="eat-tag-card-head">
          <span class="eat-tag-chip"${chipStyle}>#${esc(tag.name)}</span>
        </div>
        <div class="eat-tag-card-controls">
          <input type="color" id="money-tag-color-${tag.id}" value="${inputValue}" onchange="updateMoneyTagColor(${tag.id}, this.value)">
          <button class="btn btn-ghost btn-sm" onclick="clearMoneyTagColor(${tag.id})">Limpiar</button>
          <button class="btn btn-ghost btn-sm" onclick="deleteMoneyTag(${tag.id})">Eliminar</button>
        </div>
      </div>
    `;
  }).join('');
}

function renderMoneyRules() {
  const list = document.getElementById('money-rules-list');
  if (!list) return;

  const rows = Array.isArray(moneyRules) ? moneyRules : [];
  list.innerHTML = rows.length
    ? rows.map(rule => moneyRuleRow(rule)).join('')
    : '<p style="color:var(--muted);font-size:.9rem">No hay reglas configuradas.</p>';
}

function moneyRuleRow(rule) {
  const tag = rule.tag_name ? `<span class="badge">#${esc(rule.tag_name)}</span>` : '';
  const kind = rule.expense_type === 'fixed' ? 'fijo' : 'variable';
  return `
    <div class="history-item">
      <span class="hi-date">${esc(rule.bank_item || '')}</span>
      <span class="hi-detail">
        <strong>${esc(rule.pretty_name || '(sin alias)')}</strong>
        <span class="badge">${esc(rule.apply_to || 'expense')}</span>
        <span style="font-size:.76rem;color:var(--muted)">${esc(kind)}</span>
        ${tag}
      </span>
      <span class="hi-action">
        <button class="btn btn-ghost btn-sm" onclick="prefillRuleForm(${parseInt(rule.id, 10) || 0})">Editar</button>
      </span>
    </div>
  `;
}

function fillMoneySettingsForm() {
  const s = moneyDashboard.settings || {};
  setInput('money-salary', s.monthly_salary);
  setInput('money-current-amount', s.current_amount);
  setInput('money-savings-goal', s.savings_goal);
  setInput('money-quick-date', moneyDashboard.date || todayYmd());
}

function setInput(id, value) {
  const el = document.getElementById(id);
  if (!el) return;
  el.value = value ?? '';
}

async function loadMoneyDashboard() {
  const date = document.getElementById('money-quick-date')?.value || todayYmd();
  const res = await api('/api/money/dashboard/list', { date });
  if (!res.ok) {
    toast(res.msg || 'Error cargando dashboard money');
    return;
  }

  moneyDashboard = res.data || moneyDashboard;
  fillMoneySettingsForm();
  renderMoneyKpis();
  renderMoneyCharts();
  await loadMoneyMovements();
}

async function loadMoneyTags() {
  const res = await api('/api/money/tags/list');
  if (!res.ok) {
    toast(res.msg || 'Error cargando tags');
    return;
  }
  moneyTags = Array.isArray(res.data) ? res.data : [];
  renderMoneyTagOptions();
  renderMoneyTags();
}

async function loadMoneyMovements() {
  const date = moneyDashboard.date || document.getElementById('money-quick-date')?.value || todayYmd();
  const res = await api('/api/money/expenses/list', { date });
  if (!res.ok) {
    toast(res.msg || 'Error cargando movimientos');
    return;
  }

  moneyMovements = Array.isArray(res.data) ? res.data : [];
  renderMoneyMovements();
}

async function loadMoneyRules() {
  const res = await api('/api/money/rules/list');
  if (!res.ok) {
    toast(res.msg || 'Error cargando reglas');
    return;
  }
  moneyRules = Array.isArray(res.data) ? res.data : [];
  renderMoneyRules();
}

async function importMoneyCsv() {
  const fileInput = document.getElementById('money-csv-file');
  const file = fileInput?.files?.[0] || null;
  if (!file) {
    toast('Selecciona un CSV');
    return;
  }

  let text = '';
  try {
    text = await file.text();
  } catch (_e) {
    toast('No se pudo leer el archivo');
    return;
  }

  const res = await api('/api/money/import/csv', { csv_text: text });
  if (!res.ok) {
    toast(res.msg || 'Error importando CSV');
    return;
  }

  const summary = document.getElementById('money-import-summary');
  if (summary) {
    const d = res.data || {};
    summary.style.display = '';
    summary.innerHTML = `
      Importacion completada. Nuevos gastos: <strong>${esc(d.created_expenses || 0)}</strong>,
      nuevos ingresos: <strong>${esc(d.created_incomes || 0)}</strong>,
      duplicados: <strong>${esc(d.duplicates || 0)}</strong>,
      filas invalidas: <strong>${esc(d.invalid_rows || 0)}</strong>.
    `;
  }

  if (fileInput) fileInput.value = '';
  toast('CSV importado');
  await Promise.all([loadMoneyDashboard(), loadMoneyRules(), loadMoneyTags()]);
}

async function saveMoneySettings() {
  const payload = {
    monthly_salary: Number(document.getElementById('money-salary')?.value || 0),
    current_amount: Number(document.getElementById('money-current-amount')?.value || 0),
    savings_goal: Number(document.getElementById('money-savings-goal')?.value || 0),
  };

  const res = await api('/api/money/settings/save', payload);
  if (!res.ok) {
    toast(res.msg || 'Error guardando configuracion');
    return;
  }

  moneyDashboard.settings = res.data || moneyDashboard.settings;
  toast('Configuracion guardada');
  await loadMoneyDashboard();
}

function findMovementById(id, movementKind) {
  return moneyMovements.find(x => {
    const sameId = (parseInt(x.id, 10) || 0) === (parseInt(id, 10) || 0);
    const sameKind = String(x.movement_kind || 'expense') === String(movementKind || 'expense');
    return sameId && sameKind;
  }) || null;
}

function editMoneyMovement(id, movementKind) {
  const movement = findMovementById(id, movementKind);
  if (!movement) return;

  setInput('money-movement-id', movement.id);
  setInput('money-movement-kind', movement.movement_kind || 'expense');
  setInput('money-movement-source-item', movement.source_item || '');
  setInput('money-movement-name', movement.name || '');
  setInput('money-movement-amount', `${movement.movement_kind === 'income' ? '+' : '-'}${moneyFmt(movement.amount)} EUR`);
  setInput('money-movement-date', formatDate(movement.movement_date || ''));
  setInput('money-movement-tag-id', movement.tag_id || '');
  setInput('money-movement-tag-name', '');

  const isExpense = movement.movement_kind !== 'income';
  const typeWrap = document.getElementById('money-movement-expense-type-wrap');
  if (typeWrap) typeWrap.style.display = isExpense ? '' : 'none';

  const fixedCheck = document.getElementById('money-movement-is-fixed');
  if (fixedCheck) fixedCheck.checked = movement.expense_type === 'fixed';

  const saveRuleCheck = document.getElementById('money-movement-save-rule');
  if (saveRuleCheck) saveRuleCheck.checked = false;
  toggleMovementRuleApply();

  const title = document.getElementById('money-movement-modal-title');
  if (title) title.textContent = 'Editar movimiento';
  openModal('modal-money-movement');
}

function toggleMovementRuleApply() {
  const saveRule = Boolean(document.getElementById('money-movement-save-rule')?.checked);
  const wrap = document.getElementById('money-movement-rule-apply-wrap');
  if (wrap) wrap.style.display = saveRule ? '' : 'none';
}

async function saveMoneyMovement() {
  const id = parseInt(document.getElementById('money-movement-id')?.value || '0', 10) || 0;
  const movement_kind = document.getElementById('money-movement-kind')?.value || 'expense';
  const name = (document.getElementById('money-movement-name')?.value || '').trim();
  const tag_id = parseInt(document.getElementById('money-movement-tag-id')?.value || '0', 10) || 0;
  const tag_name = (document.getElementById('money-movement-tag-name')?.value || '').trim();
  const save_rule = Boolean(document.getElementById('money-movement-save-rule')?.checked);
  const rule_apply_to = document.getElementById('money-movement-rule-apply-to')?.value || 'expense';
  const expense_type = Boolean(document.getElementById('money-movement-is-fixed')?.checked) ? 'fixed' : 'variable';

  if (id <= 0 || !name) {
    toast('Completa el nombre del movimiento');
    return;
  }

  const res = await api('/api/money/movements/update', {
    id,
    movement_kind,
    name,
    tag_id,
    tag_name,
    save_rule,
    rule_apply_to,
    expense_type,
  });

  if (!res.ok) {
    toast(res.msg || 'Error guardando movimiento');
    return;
  }

  closeModal('modal-money-movement');
  toast('Movimiento actualizado');
  await Promise.all([loadMoneyDashboard(), loadMoneyRules(), loadMoneyTags()]);
}

async function deleteMoneyMovement(id, movementKind) {
  if (!confirm('¿Eliminar este movimiento?')) return;
  const res = await api('/api/money/expenses/delete', { id, movement_kind: movementKind });
  if (!res.ok) {
    toast(res.msg || 'Error eliminando movimiento');
    return;
  }
  toast('Movimiento eliminado');
  await loadMoneyDashboard();
}

async function saveMoneyRuleFromForm() {
  const bank_item = (document.getElementById('money-rule-bank-item')?.value || '').trim();
  const pretty_name = (document.getElementById('money-rule-pretty-name')?.value || '').trim();
  const apply_to = document.getElementById('money-rule-apply-to')?.value || 'expense';
  const expense_type = document.getElementById('money-rule-expense-type')?.value || 'variable';
  const tag_id = parseInt(document.getElementById('money-rule-tag-id')?.value || '0', 10) || 0;
  const tag_name = (document.getElementById('money-rule-tag-name')?.value || '').trim();

  if (!bank_item) {
    toast('Concepto banco obligatorio');
    return;
  }

  const res = await api('/api/money/rules/save', {
    bank_item,
    pretty_name,
    apply_to,
    expense_type,
    tag_id,
    tag_name,
  });

  if (!res.ok) {
    toast(res.msg || 'Error guardando regla');
    return;
  }

  setInput('money-rule-bank-item', '');
  setInput('money-rule-pretty-name', '');
  setInput('money-rule-apply-to', 'expense');
  setInput('money-rule-expense-type', 'variable');
  setInput('money-rule-tag-id', '');
  setInput('money-rule-tag-name', '');
  toast('Regla guardada');
  await Promise.all([loadMoneyRules(), loadMoneyTags()]);
}

function prefillRuleForm(ruleId) {
  const rule = moneyRules.find(x => (parseInt(x.id, 10) || 0) === (parseInt(ruleId, 10) || 0));
  if (!rule) return;

  setInput('money-rule-bank-item', rule.bank_item || '');
  setInput('money-rule-pretty-name', rule.pretty_name || '');
  setInput('money-rule-apply-to', rule.apply_to || 'expense');
  setInput('money-rule-expense-type', rule.expense_type || 'variable');
  setInput('money-rule-tag-id', rule.tag_id || '');
  setInput('money-rule-tag-name', '');
}

async function createMoneyTag() {
  const name = (document.getElementById('money-new-tag-name')?.value || '').trim();
  if (!name) {
    toast('Escribe el nombre de la tag');
    return;
  }

  const res = await api('/api/money/tags/save', { name });
  if (!res.ok) {
    toast(res.msg || 'Error creando tag');
    return;
  }

  setInput('money-new-tag-name', '');
  toast('Tag creada');
  await loadMoneyTags();
}

async function updateMoneyTagColor(tagId, color) {
  const normalized = normalizeHexColor(color);
  if (!normalized) {
    toast('Color invalido');
    return;
  }
  const res = await api('/api/money/tags/update-color', { id: tagId, color: normalized });
  if (!res.ok) {
    toast(res.msg || 'Error guardando color');
    return;
  }
  toast('Color actualizado');
  await Promise.all([loadMoneyTags(), loadMoneyDashboard()]);
}

async function clearMoneyTagColor(tagId) {
  const res = await api('/api/money/tags/update-color', { id: tagId, color: '' });
  if (!res.ok) {
    toast(res.msg || 'Error limpiando color');
    return;
  }
  toast('Color limpiado');
  await Promise.all([loadMoneyTags(), loadMoneyDashboard()]);
}

async function deleteMoneyTag(tagId) {
  if (!confirm('¿Eliminar esta tag?')) return;
  const res = await api('/api/money/tags/delete', { id: tagId });
  if (!res.ok) {
    toast(res.msg || 'Error eliminando tag');
    return;
  }
  toast('Tag eliminada');
  await Promise.all([loadMoneyTags(), loadMoneyDashboard()]);
}

document.addEventListener('change', (e) => {
  const target = e.target;
  if (!(target instanceof HTMLElement)) return;
  if (target.id === 'money-movement-save-rule') {
    toggleMovementRuleApply();
  }
});

Promise.all([loadMoneyTags(), loadMoneyRules()]).then(() => {
  setInput('money-quick-date', todayYmd());
  return loadMoneyDashboard();
});
