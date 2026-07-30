// ── Framework/shared helpers ─────────────────────────────────────────────────
const APP_BASE = location.pathname.replace(/\/[^/]*$/, '');

function api(path, body = {}) {
  const url = `${APP_BASE}${path}`.replace(/\/\/{2,}/g, '/');
  return fetch(url, {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(body),
  }).then(r => r.json());
}

function toast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2400);
}

function openModal(id)          { document.getElementById(id).classList.add('open'); }
function closeModal(id)         { document.getElementById(id).classList.remove('open'); }
function closeOnBg(e, id)       { if (e.target === document.getElementById(id)) closeModal(id); }

function esc(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function formatDate(d) {
  if (!d) return '';
  const [y, m, day] = d.split('-');
  return `${day}/${m}/${y}`;
}
