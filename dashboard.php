<?php
/**
 * ARCANA — Admin Dashboard (dashboard.php)
 * Protected page — only authenticated administrators can access.
 * Schema: arcanaestetica_db (Modular Front-end Architecture)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Dashboard Administrativo — Arcana Moon Beauty Secret';
$pageDescription = 'Panel de administración general y gestión de datos de Arcana Moon Beauty Secret.';
$pageName = 'dashboard';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main style="padding-top: 100px; padding-bottom: 3rem;">
  <div class="container">
    <div class="dashboard">
      <!-- 1. Encabezado y Toolbar de Persistencia -->
      <?php require_once __DIR__ . '/includes/dashboard/header-toolbar.php'; ?>

      <!-- 2. Navegación de Pestañas (Tabs) -->
      <?php require_once __DIR__ . '/includes/dashboard/nav-tabs.php'; ?>

      <!-- 3. Paneles de Contenido (Tabs) -->
      <?php require_once __DIR__ . '/includes/dashboard/tabs/tab-servicios.php'; ?>
      <?php require_once __DIR__ . '/includes/dashboard/tabs/tab-empleados.php'; ?>
      <?php require_once __DIR__ . '/includes/dashboard/tabs/tab-turnos.php'; ?>
      <?php require_once __DIR__ . '/includes/dashboard/tabs/tab-usuarios.php'; ?>
      <?php require_once __DIR__ . '/includes/dashboard/tabs/tab-bloqueos.php'; ?>
      <?php require_once __DIR__ . '/includes/dashboard/tabs/tab-metricas.php'; ?>
      <?php require_once __DIR__ . '/includes/dashboard/tabs/tab-cuentas.php'; ?>
      <?php require_once __DIR__ . '/includes/dashboard/tabs/tab-historial.php'; ?>
    </div>

    <!-- Botón Flotante (FAB): Agregar nuevo registro -->
    <button class="btn-fab dashboard__fab" title="Agregar nuevo registro">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M5 12h14" />
        <path d="M12 5v14" />
      </svg>
    </button>
  </div>
</main>

<!-- 4. Modales CRUD y Operaciones del Dashboard -->
<?php require_once __DIR__ . '/includes/dashboard/modales/modal-usuario.php'; ?>
<?php require_once __DIR__ . '/includes/dashboard/modales/modal-servicio.php'; ?>
<?php require_once __DIR__ . '/includes/dashboard/modales/modal-empleado.php'; ?>
<?php require_once __DIR__ . '/includes/dashboard/modales/modal-turno.php'; ?>
<?php require_once __DIR__ . '/includes/dashboard/modales/modal-bloqueo.php'; ?>
<?php require_once __DIR__ . '/includes/dashboard/modales/modal-historial-usuario.php'; ?>
<?php require_once __DIR__ . '/includes/dashboard/modales/modal-cuenta-cobro.php'; ?>

<footer style="margin-top: 2rem;">
  <div style="max-width: var(--container-max); margin: 0 auto; padding: 1rem var(--container-padding); border-top: 1px solid var(--color-glass-border-subtle); display: flex; justify-content: space-between; align-items: center; font-size: 0.8125rem; color: var(--color-text-muted);">
    <span><strong style="color: var(--color-text-primary);">Arcana</strong> - Panel de Control &copy; <?= date('Y') ?></span>
    <span><a href="index.php" style="color: var(--color-accent);">Ir al Sitio Web &rarr;</a></span>
  </div>
</footer>

<script>
  if (window.lucide) {
    lucide.createIcons();
  }
</script>
</body>
</html>
