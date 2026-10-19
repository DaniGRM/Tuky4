let taskDashboard = { date: '', today: [], next: [], upcoming: [], kpis: {} };
let taskInbox = [];
let taskList = [];
let taskFilter = 'open';
const taskIndex = {};

const TASK_STATUS_LABELS = {
  inbox: 'Inbox',
  next: 'Siguiente',
  waiting: 'Esperando',
  someday: 'Algun dia',
  done: 'Hecha',
};

function taskYmd(offsetDays = 0) {
  const d = new Date();
  d.setDate(d.getDate() + offsetDays);
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${d.getFullYear()}-${m}-${day}`;
}

function setTaskSection(section) {
  document.querySelectorAll('.section-switch .pill').forEach(p =>
    p.classList.toggle('selected', p.dataset.val === section)
  );

  const sections = {
    DASHBOARD: 'task-dashboard-section',
    INBOX: 'task-inbox-section',
    LIST: 'task-list-section',
  };
  Object.entries(sections).forEach(([key, id]) => {
    const el = document.getElementById(id);
    if (el) el.style.display = key === section ? '' : 'none';
  });
}

function setTaskFilter(filter) {
  taskFilter = filter;
  document.querySelectorAll('#task-filter-bar .pill').forEach(p =>
    p.classList.toggle('selected', p.dataset.val === filter)
  );
  loadTaskList();
}

function taskDateBadge(t) {
  if (!t.scheduled_date) return '';
  const today = taskYmd(0);
  const date = formatDate(t.scheduled_date);
  if (t.status !== 'done' && t.scheduled_date < today) {
    return `<span class="badge task-badge-late"><i class="fa-solid fa-triangle-exclamation"></i> Atrasada ${esc(date)}</span>`;
  }
  let label = date;
  if (t.scheduled_date === today) label = 'Hoy';
  else if (t.scheduled_date === taskYmd(1)) label = 'Manana';
  return `<span class="badge"><i class="fa-regular fa-calendar"></i> ${esc(label)}</span>`;
}

function taskItemHtml(t) {
  taskIndex[t.id] = t;
  const done = t.status === 'done';

  const badges = [taskDateBadge(t)];
  if (t.status !== 'next') {
    badges.push(`<span class="badge task-badge-status">${esc(TASK_STATUS_LABELS[t.status] || t.status)}</span>`);
  }
  if (t.context) badges.push(`<span class="badge badge-green">@${esc(t.context)}</span>`);
  if (t.blocked) badges.push('<span class="badge task-badge-blocked"><i class="fa-solid fa-lock"></i> Bloqueada</span>');
  if (t.parent_title) badges.push(`<span class="task-link"><i class="fa-solid fa-turn-up fa-rotate-90"></i> ${esc(t.parent_title)}</span>`);
  if (t.children_open > 0) badges.push(`<span class="task-link"><i class="fa-solid fa-code-branch"></i> ${t.children_open} derivada${t.children_open === 1 ? '' : 's'}</span>`);

  return `
    <div class="task-item${done ? ' done' : ''}${t.blocked ? ' blocked' : ''}">
      <button class="task-check" onclick="toggleTask(${t.id}, ${done ? 'false' : 'true'})" title="${done ? 'Reabrir' : 'Completar'}">
        <i class="${done ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle'}"></i>
      </button>
      <div class="task-body" onclick="openTaskModal(${t.id})">
        <div class="task-title">${esc(t.title)}</div>
        ${t.notes ? `<div class="task-notes">${esc(t.notes)}</div>` : ''}
        <div class="task-meta">${badges.join('')}</div>
      </div>
      <div class="task-actions">
        <button class="btn btn-ghost btn-sm btn-icon" onclick="openTaskModal(null, ${t.id})" title="Derivar nueva tarea">
          <i class="fa-solid fa-code-branch"></i>
        </button>
        <button class="btn btn-ghost btn-sm btn-icon" onclick="deleteTask(${t.id})" title="Eliminar">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    </div>`;
}

function renderTaskList(containerId, tasks, emptyText) {
  const root = document.getElementById(containerId);
  if (!root) return;
  root.innerHTML = tasks.length
    ? tasks.map(taskItemHtml).join('')
    : `<div class="card-empty">${esc(emptyText)}</div>`;
}

function renderTaskDashboard() {
  const k = taskDashboard.kpis || {};
  const cards = [
    { title: 'Hoy', value: k.today || 0 },
    { title: 'Atrasadas', value: k.overdue || 0, late: (k.overdue || 0) > 0 },
    { title: 'Inbox', value: k.inbox || 0 },
    { title: 'Siguientes', value: k.next || 0 },
    { title: 'Esperando', value: k.waiting || 0 },
    { title: 'Hechas hoy', value: k.done_today || 0 },
  ];

  document.getElementById('task-kpi-grid').innerHTML = cards.map(c => `
    <div class="card task-kpi-card${c.late ? ' late' : ''}">
      <div class="task-kpi-title">${esc(c.title)}</div>
      <div class="task-kpi-value">${esc(c.value)}</div>
    </div>
  `).join('');

  renderTaskList('task-today-list', taskDashboard.today, 'Nada fechado para hoy.');
  renderTaskList('task-next-list', taskDashboard.next, 'Sin siguientes acciones. Clarifica tu Inbox.');
  renderTaskList('task-upcoming-list', taskDashboard.upcoming, 'Nada programado en los proximos dias.');

  const badge = document.getElementById('task-inbox-count');
  if (badge) {
    badge.textContent = k.inbox || 0;
    badge.style.display = k.inbox > 0 ? '' : 'none';
  }
}

async function loadTaskDashboard() {
  const res = await api('/api/task/dashboard', { date: taskYmd(0) });
  if (!res.ok) {
    toast(res.msg || 'Error cargando dashboard');
    return;
  }
  taskDashboard = res.data;
  renderTaskDashboard();
}

async function loadTaskInbox() {
  const res = await api('/api/task/tasks/list', { filter: 'inbox' });
  if (!res.ok) {
    toast(res.msg || 'Error cargando inbox');
    return;
  }
  taskInbox = res.data;
  renderTaskList('task-inbox-list', taskInbox, 'Inbox vacio.');
}

async function loadTaskList() {
  const res = await api('/api/task/tasks/list', { filter: taskFilter });
  if (!res.ok) {
    toast(res.msg || 'Error cargando tareas');
    return;
  }
  taskList = res.data;
  renderTaskList('task-all-list', taskList, 'No hay tareas en esta vista.');
}

function refreshTasks() {
  return Promise.all([loadTaskDashboard(), loadTaskInbox(), loadTaskList()]);
}

async function quickCaptureTask(e) {
  e.preventDefault();
  const input = document.getElementById('task-capture-input');
  const title = input.value.trim();
  if (!title) return false;

  const res = await api('/api/task/tasks/add', { title, status: 'inbox' });
  if (!res.ok) {
    toast(res.msg || 'No se pudo capturar');
    return false;
  }

  input.value = '';
  toast('Anadida al Inbox');
  refreshTasks();
  return false;
}

function setTaskDate(offsetDays) {
  const el = document.getElementById('task-date');
  if (!el || !el._flatpickr) return;
  if (offsetDays === null) {
    el._flatpickr.clear();
    return;
  }
  el._flatpickr.setDate(taskYmd(offsetDays), true);
}

function initTaskDatePicker() {
  if (typeof flatpickr !== 'function') return;
  const el = document.getElementById('task-date');
  if (!el || el._flatpickr) return;

  flatpickr(el, {
    locale: (window.flatpickr.l10ns && window.flatpickr.l10ns.es) || undefined,
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'd/m/Y',
    disableMobile: true,
    allowInput: false,
  });
}

async function renderTaskParentOptions(selectedId, excludeId) {
  const sel = document.getElementById('task-parent');
  sel.innerHTML = '<option value="">Ninguna</option>';

  const res = await api('/api/task/tasks/list', { filter: 'open' });
  const tasks = res.ok ? res.data : [];
  tasks
    .filter(t => t.id !== excludeId)
    .forEach(t => {
      const opt = document.createElement('option');
      opt.value = String(t.id);
      opt.textContent = t.title;
      sel.appendChild(opt);
    });

  sel.value = selectedId ? String(selectedId) : '';
}

async function openTaskModal(id = null, parentId = null) {
  const task = id ? taskIndex[id] : null;

  document.getElementById('task-modal-title').textContent =
    task ? 'Editar tarea' : (parentId ? 'Derivar tarea' : 'Nueva tarea');
  document.getElementById('task-id').value = task ? String(task.id) : '';
  document.getElementById('task-title').value = task ? task.title : '';
  document.getElementById('task-notes').value = task ? task.notes : '';
  document.getElementById('task-context').value = task ? task.context : '';
  document.getElementById('task-status').value =
    task ? (task.status === 'done' ? 'next' : task.status) : (parentId ? 'next' : 'inbox');

  const dateEl = document.getElementById('task-date');
  if (dateEl._flatpickr) dateEl._flatpickr.setDate(task && task.scheduled_date ? task.scheduled_date : null, false);

  await renderTaskParentOptions(task ? task.parent_id : parentId, task ? task.id : null);
  openModal('modal-task');
  document.getElementById('task-title').focus();
}

async function saveTask() {
  const id = Number(document.getElementById('task-id').value) || 0;
  const title = document.getElementById('task-title').value.trim();
  if (!title) {
    toast('Escribe la tarea');
    return;
  }

  const body = {
    title,
    notes: document.getElementById('task-notes').value,
    status: document.getElementById('task-status').value,
    scheduled_date: document.getElementById('task-date').value,
    context: document.getElementById('task-context').value,
    parent_id: document.getElementById('task-parent').value || null,
  };

  // Editar una tarea hecha sin tocar su estado la mantiene hecha.
  if (id && taskIndex[id] && taskIndex[id].status === 'done') delete body.status;
  if (id) body.id = id;

  const res = await api(id ? '/api/task/tasks/update' : '/api/task/tasks/add', body);
  if (!res.ok) {
    toast(res.msg || 'No se pudo guardar');
    return;
  }

  closeModal('modal-task');
  toast(id ? 'Tarea actualizada' : 'Tarea creada');
  refreshTasks();
}

async function toggleTask(id, done) {
  const res = await api('/api/task/tasks/complete', { id, done });
  if (!res.ok) {
    toast(res.msg || 'No se pudo actualizar');
    return;
  }

  const unlocked = res.data && res.data.unlocked ? res.data.unlocked : 0;
  if (done && unlocked > 0) {
    toast(`Completada. Se desbloquean ${unlocked} derivada${unlocked === 1 ? '' : 's'}`);
  } else {
    toast(done ? 'Completada' : 'Reabierta');
  }
  refreshTasks();
}

async function deleteTask(id) {
  const t = taskIndex[id];
  const extra = t && t.children_count > 0 ? ' Sus derivadas quedaran como tareas independientes.' : '';
  if (!confirm(`Eliminar "${t ? t.title : 'tarea'}"?${extra}`)) return;

  const res = await api('/api/task/tasks/delete', { id });
  if (!res.ok) {
    toast(res.msg || 'No se pudo eliminar');
    return;
  }

  toast('Tarea eliminada');
  refreshTasks();
}

initTaskDatePicker();
refreshTasks();
