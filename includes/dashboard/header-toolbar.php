<?php
/**
 * ARCANA — Dashboard Header
 */
require_once dirname(dirname(__DIR__)) . '/config/JsonDB.php';
$isMysqlConnected = JsonDB::getInstance()->isConnectedToMysql();
$nombreUsuario = htmlspecialchars(getCurrentUser()['nombre'] ?? 'Admin');
?>
<!-- Dashboard Header -->
<div class="dashboard__header" style="margin-bottom: 2rem;">
  <div>
    <h1 class="dashboard__greeting">Bienvenido/a, <span class="text-gradient"><?= $nombreUsuario ?></span></h1>
    <p class="dashboard__greeting-sub">Panel de administración y gestión integral</p>
  </div>
  <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
    <!-- Badge Estado MySQL -->
    <?php if ($isMysqlConnected): ?>
      <div class="dashboard__date-badge" style="background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.3); color: #10b981;">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
        MySQL Conectado
      </div>
    <?php else: ?>
      <div class="dashboard__date-badge" style="background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.3); color: #f87171;">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        MySQL Desconectado
      </div>
    <?php endif; ?>

    <!-- Date badge -->
    <div class="dashboard__date-badge">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
      <?= date('d \d\e F, Y') ?>
    </div>
  </div>
</div>

<?php if (!$isMysqlConnected): ?>
  <!-- Banner Alerta: MySQL Desconectado -->
  <div class="dashboard__alert-disconnected" style="margin-bottom: 2rem; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: var(--radius-card, 16px); padding: 1.25rem 1.5rem; display: flex; align-items: flex-start; gap: 1rem; color: #fca5a5;">
    <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(239, 68, 68, 0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #ef4444;">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <div>
      <h4 style="margin: 0 0 0.25rem; font-size: 0.95rem; color: #f87171; font-weight: 600;">Base de Datos MySQL Desconectada</h4>
      <p style="margin: 0; font-size: 0.85rem; color: var(--color-text-muted); line-height: 1.45;">
        El panel está configurado para operar con MySQL pero no se pudo conectar al servidor de base de datos. Por seguridad e integridad, <strong>no se muestran datos ni se permiten modificaciones</strong> hasta que el servicio MySQL esté activo y conectado.
      </p>
    </div>
  </div>
<?php endif; ?>
