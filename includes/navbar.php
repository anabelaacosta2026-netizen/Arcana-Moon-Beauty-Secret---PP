<?php
/**
 * ARCANA — Shared Navbar Template
 */

$currentUser = getCurrentUser();
$activePage = basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
?>
<!-- Navbar -->
<nav class="navbar">
  <div class="container">
    <div class="navbar__inner">
      <a href="index.php" class="navbar__logo">
        <svg class="navbar__logo-icon" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M28 18.5C27.5 24.5 22.5 29 16.5 29C9.6 29 4 23.4 4 16.5C4 10.5 8.5 5.5 14.5 5C12 8 11.5 12.5 13.5 16C15.5 19.5 19.5 21 23 20C21 19.5 28 18.5 28 18.5Z" fill="currentColor" />
          <circle cx="22" cy="8" r="1.5" fill="currentColor" opacity="0.6" />
          <circle cx="25" cy="12" r="1" fill="currentColor" opacity="0.4" />
        </svg>
        Arcana
      </a>
      <div class="navbar__links">
        <a href="index.php" class="navbar__link <?= ($activePage === 'index') ? 'navbar__link--active' : '' ?>">Inicio</a>
        <a href="about-us.php" class="navbar__link <?= ($activePage === 'about-us') ? 'navbar__link--active' : '' ?>">Nosotros</a>
        <a href="services.php" class="navbar__link <?= ($activePage === 'services') ? 'navbar__link--active' : '' ?>">Servicios</a>
        <a href="agenda.php" class="navbar__link <?= ($activePage === 'agenda') ? 'navbar__link--active' : '' ?>">Agenda</a>
        <a href="contact.php" class="navbar__link <?= ($activePage === 'contact') ? 'navbar__link--active' : '' ?>">Contacto</a>
      </div>
      <div class="navbar__actions">
        <?php if ($currentUser): ?>
          <?php if (isAdmin() || isSuperAdmin()): ?>
            <a href="dashboard.php" class="btn-pill btn-pill--sm <?= ($activePage === 'dashboard') ? 'btn-pill--accent' : '' ?>" title="Panel de Control">
              <span class="nav-btn-text">Dashboard</span>
              <svg class="nav-btn-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>
              </svg>
            </a>
          <?php else: ?>
            <!-- Usuario cliente: saludo + acceso directo a Agendar y a Mis Turnos -->
            <span class="navbar__user-greeting">Hola, <?= htmlspecialchars($currentUser['nombre']) ?></span>
            <a href="agenda.php" class="btn-pill btn-pill--sm <?= ($activePage === 'agenda') ? 'btn-pill--accent' : '' ?>" title="Agendar nuevo turno" style="background: rgba(196, 84, 126, 0.18); border-color: rgba(196, 84, 126, 0.4); color: var(--color-accent);">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                <line x1="16" x2="16" y1="2" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" x2="21" y1="10" y2="10"/>
                <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/>
              </svg>
              <span class="nav-btn-text">AGENDAR</span>
            </a>
            <a href="historial-turnos.php" class="btn-pill btn-pill--sm <?= ($activePage === 'historial-turnos') ? 'btn-pill--accent' : '' ?>" title="Mis Turnos" style="<?= ($activePage === 'historial-turnos') ? '' : 'background: rgba(255, 255, 255, 0.06);' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/>
              </svg>
              <span class="nav-btn-text">MIS TURNOS</span>
            </a>
          <?php endif; ?>
          <a href="logout.php" class="btn-fab navbar__logout-btn g_id_signout" style="width:36px;height:36px;" title="Cerrar sesión">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
              <polyline points="16 17 21 12 16 7"></polyline>
              <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
          </a>
        <?php else: ?>
          <a href="login.php" class="navbar__login-btn btn-pill btn-pill--sm" title="Ingresar">
            <span class="navbar__login-text">Ingresar</span>
            <svg class="navbar__login-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
              <polyline points="10 17 15 12 10 7"></polyline>
              <line x1="15" y1="12" x2="3" y2="12"></line>
            </svg>
          </a>
        <?php endif; ?>
        <button class="navbar__hamburger" type="button" aria-label="Abrir menú" aria-expanded="false">
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>
    </div>
  </div>
</nav>

<!-- Backdrop click-outside -->
<div class="navbar__backdrop" aria-hidden="true"></div>

<!-- Mobile Sliding Drawer Panel (75% width from right) -->
<aside class="navbar__overlay" id="mobile-menu" aria-label="Menú móvil">
  <div class="navbar__overlay-header">
    <a href="index.php" class="navbar__logo">
      <svg class="navbar__logo-icon" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M28 18.5C27.5 24.5 22.5 29 16.5 29C9.6 29 4 23.4 4 16.5C4 10.5 8.5 5.5 14.5 5C12 8 11.5 12.5 13.5 16C15.5 19.5 19.5 21 23 20C21 19.5 28 18.5 28 18.5Z" fill="currentColor" />
        <circle cx="22" cy="8" r="1.5" fill="currentColor" opacity="0.6" />
        <circle cx="25" cy="12" r="1" fill="currentColor" opacity="0.4" />
      </svg>
      Arcana
    </a>
    <button class="navbar__overlay-close" aria-label="Cerrar menú">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 6 6 18" />
        <path d="m6 6 12 12" />
      </svg>
    </button>
  </div>

  <nav class="navbar__overlay-nav">
    <a href="index.php" class="navbar__overlay-link <?= ($activePage === 'index') ? 'navbar__overlay-link--active' : '' ?>">Inicio</a>
    <a href="about-us.php" class="navbar__overlay-link <?= ($activePage === 'about-us') ? 'navbar__overlay-link--active' : '' ?>">Nosotros</a>
    <a href="services.php" class="navbar__overlay-link <?= ($activePage === 'services') ? 'navbar__overlay-link--active' : '' ?>">Servicios</a>
    <a href="agenda.php" class="navbar__overlay-link <?= ($activePage === 'agenda') ? 'navbar__overlay-link--active' : '' ?>">Agenda</a>
    <a href="contact.php" class="navbar__overlay-link <?= ($activePage === 'contact') ? 'navbar__overlay-link--active' : '' ?>">Contacto</a>
  </nav>

  <?php if ($currentUser): ?>
    <div class="navbar__overlay-footer">
      <?php if (isAdmin() || isSuperAdmin()): ?>
        <a href="dashboard.php" class="btn-pill btn-pill--accent btn-pill--full" style="margin-bottom:0.5rem;">Panel Administrativo</a>
      <?php else: ?>
        <a href="agenda.php" class="btn-pill btn-pill--full" style="margin-bottom:0.5rem; background: rgba(196, 84, 126, 0.18); border-color: rgba(196, 84, 126, 0.4); color: var(--color-accent); display:flex; align-items:center; justify-content:center; gap:0.4rem;">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
          📅 Agendar Turno
        </a>
        <a href="historial-turnos.php" class="btn-pill btn-pill--full" style="margin-bottom:0.5rem; background: rgba(255, 255, 255, 0.05); display:flex; align-items:center; justify-content:center; gap:0.4rem;">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
          📜 Mis Turnos
        </a>
      <?php endif; ?>
      <a href="logout.php" class="btn-pill btn-pill--full g_id_signout">Cerrar Sesión (<?= htmlspecialchars($currentUser['nombre']) ?>)</a>
    </div>
  <?php endif; ?>
</aside>
