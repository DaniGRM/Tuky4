<?php
$uri = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$isHome = $uri === '/' || $uri === '/index.php';
$isGym = str_starts_with($uri, '/gym');
$isEat = str_starts_with($uri, '/eat');
?>

<style>
.mobile-nav {
  position: fixed;
  left: 50%;
  bottom: calc(.55rem + env(safe-area-inset-bottom));
  transform: translateX(-50%);
  width: min(520px, calc(100% - 1rem));
  background: rgba(26, 29, 39, 0.92);
  border: 1px solid rgba(46, 51, 72, 0.95);
  border-radius: 18px;
  box-shadow: 0 14px 34px rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(10px);
  z-index: 150;
  padding: .32rem;
}

.mobile-nav-list {
  list-style: none;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: .25rem;
}

.mobile-nav-link {
  text-decoration: none;
  color: #8892a4;
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
  color: #e2e8f0;
  background: rgba(108, 99, 255, 0.16);
}

.mobile-nav-link:active {
  transform: scale(.97);
}

.mobile-nav-link.active {
  color: #e2e8f0;
  background: linear-gradient(140deg, rgba(108, 99, 255, 0.35), rgba(255, 101, 132, 0.24));
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
        <span class="mobile-nav-label">Home</span>
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
        <span class="mobile-nav-label">Gym</span>
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
        <span class="mobile-nav-label">Eat</span>
      </a>
    </li>
  </ul>
</nav>
