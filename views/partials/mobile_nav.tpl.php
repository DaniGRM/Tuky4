<?php
$uri = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$isHome = $uri === '/' || $uri === '/index.php';
$isGym = str_starts_with($uri, '/gym');
$isEat = str_starts_with($uri, '/eat');
$isMoney = str_starts_with($uri, '/money');
$isTask = str_starts_with($uri, '/task');
?>

<style>
.mobile-nav {
  position: fixed;
  left: 50%;
  bottom: calc(.55rem + env(safe-area-inset-bottom));
  transform: translateX(-50%);
  width: min(520px, calc(100% - 1rem));
  background: color-mix(in srgb, var(--card) 88%, transparent);
  border: 1px solid color-mix(in srgb, var(--border) 94%, transparent);
  border-radius: 18px;
  box-shadow: 0 14px 34px rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(10px);
  z-index: 150;
  padding: .32rem;
}

.mobile-nav-list {
  list-style: none;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: .25rem;
}

.mobile-nav-link {
  text-decoration: none;
  color: var(--muted);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: .2rem;
  min-height: 52px;
  border-radius: 13px;
  transition: color .18s, background .18s, transform .12s;
}

.mobile-nav-link:hover {
  color: var(--text);
  background: color-mix(in srgb, var(--accent) 16%, transparent);
}

.mobile-nav-link:active {
  transform: scale(.97);
}

.mobile-nav-link.active {
  color: var(--text);
  background: color-mix(in srgb, var(--accent) 22%, transparent);
  border: 1px solid color-mix(in srgb, var(--accent) 52%, var(--border));
}

.mobile-nav-link svg {
  width: 21px;
  height: 21px;
  display: block;
}

.mobile-nav-label {
  font-size: .68rem;
  font-weight: 700;
  letter-spacing: .35px;
}

@media (max-width: 500px) {
  .mobile-nav {
    width: calc(100% - .7rem);
    bottom: calc(.42rem + env(safe-area-inset-bottom));
  }
  .mobile-nav-link {
    min-height: 50px;
  }
}
</style>

<nav class="mobile-nav" aria-label="Navegacion principal movil">
  <ul class="mobile-nav-list">
    <li>
      <a class="mobile-nav-link<?php echo $isHome ? ' active' : ''; ?>" href="/" aria-label="Inicio">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M3 10.8L12 3l9 7.8"/>
          <path d="M5.5 9.8V21h13V9.8"/>
        </svg>
        <span class="mobile-nav-label">Tuky4</span>
      </a>
    </li>
    <li>
      <a class="mobile-nav-link<?php echo $isGym ? ' active' : ''; ?>" href="/gym" aria-label="Gym">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M6 9v6"/>
          <path d="M18 9v6"/>
          <path d="M3 10v4"/>
          <path d="M21 10v4"/>
          <path d="M9 8v8"/>
          <path d="M15 8v8"/>
          <path d="M6 12h12"/>
        </svg>
        <span class="mobile-nav-label">Tuky4Gym</span>
      </a>
    </li>
    <li>
      <a class="mobile-nav-link<?php echo $isEat ? ' active' : ''; ?>" href="/eat" aria-label="Eat">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M4 3v8"/>
          <path d="M7 3v8"/>
          <path d="M4 7h3"/>
          <path d="M5.5 11v10"/>
          <path d="M14 3c2.8 0 4.5 2.4 4.5 5.4V21"/>
          <path d="M14 13h4.5"/>
        </svg>
        <span class="mobile-nav-label">Tuky4Eat</span>
      </a>
    </li>
    <!-- <li>
      <a class="mobile-nav-link<?php echo $isMoney ? ' active' : ''; ?>" href="/money" aria-label="Money">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M12 4.5v15"/>
          <path d="M16 8.6c0-1.3-1.8-2.3-4-2.3s-4 1-4 2.3 1.8 2.2 4 2.2 4 1 4 2.3-1.8 2.3-4 2.3-4-1-4-2.3"/>
        </svg>
        <span class="mobile-nav-label">Tuky4Money</span>
      </a>
    </li> -->
    <li>
      <a class="mobile-nav-link<?php echo $isTask ? ' active' : ''; ?>" href="/task" aria-label="Task">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect x="4" y="3.5" width="16" height="17" rx="2.5"/>
          <path d="M8 12l2.5 2.5L16 9"/>
        </svg>
        <span class="mobile-nav-label">Tuky4Task</span>
      </a>
    </li>
  </ul>
</nav>
