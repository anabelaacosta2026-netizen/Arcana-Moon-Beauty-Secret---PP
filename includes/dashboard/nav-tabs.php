<?php
/**
 * ARCANA — Dashboard Navigation Tabs
 */
?>
<div class="dashboard__tabs">
  <button class="dashboard__tab dashboard__tab--active" data-tab="servicios">Servicios</button>
  <button class="dashboard__tab" data-tab="empleados">Empleados</button>
  <button class="dashboard__tab" data-tab="turnos">Turnos y Citas</button>
  <button class="dashboard__tab" data-tab="usuarios">Usuarios</button>
  <button class="dashboard__tab" data-tab="bloqueos">Bloqueo de Días</button>
  <button class="dashboard__tab" data-tab="metricas" style="color: #f6e05e; font-weight: 600;">📊 Métricas</button>
  <button class="dashboard__tab" data-tab="cuentas" style="color: #60a5fa; font-weight: 600;">💳 Cuentas y Cobros</button>
  <?php if (isSuperAdmin()): ?>
    <button class="dashboard__tab" data-tab="historial" style="color: #c084fc; font-weight: 600;">📜 Historial de Cambios</button>
  <?php endif; ?>
</div>
