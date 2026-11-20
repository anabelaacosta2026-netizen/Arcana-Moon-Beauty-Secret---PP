<?php
/**
 * ARCANA — Panel Tab: Cuentas de Cobro y Pasarelas de Pago
 */
?>
<div class="dashboard__panel" id="panel-cuentas">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
      <h2 class="dashboard__title" style="margin-bottom: 0.25rem;">Destino de Fondos y Pasarelas de Pago</h2>
      <p style="color: var(--color-text-muted); font-size: 0.88rem; margin: 0;">Elige y cambia en cualquier momento qué cuenta o administrador recibe el dinero de las señas y reservas.</p>
    </div>
    <button type="button" id="btn-nueva-cuenta" class="btn-pill btn-pill--primary btn-pill--sm" style="display: inline-flex; align-items: center; gap: 0.4rem;">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
      + Nueva Cuenta de Cobro
    </button>
  </div>

  <!-- Banner de Cuenta Activa Destino -->
  <div id="active-account-banner" class="glass" style="padding: 1.5rem; border-radius: var(--radius-card, 16px); margin-bottom: 2rem; border-left: 4px solid #2ed573; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.25rem;">
    <div style="display: flex; align-items: center; gap: 1.25rem;">
      <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(46, 213, 115, 0.15); color: #2ed573; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
        💰
      </div>
      <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
          <span class="badge" style="background: rgba(46, 213, 115, 0.2); color: #2ed573; font-weight: 700;">RECIBIENDO FONDOS AHORA</span>
          <span id="banner-active-modo" class="badge" style="background: rgba(246, 224, 94, 0.2); color: #f6e05e;">MODO SANDBOX</span>
        </div>
        <h3 id="banner-active-name" style="margin: 0; font-size: 1.15rem; color: #fff;">Cargando cuenta activa...</h3>
        <p id="banner-active-detail" style="margin: 0.25rem 0 0; color: var(--color-text-muted); font-size: 0.85rem;">Titular: - | Proveedor: - | Alias: -</p>
      </div>
    </div>
    <div style="font-size: 0.825rem; color: var(--color-text-muted); max-width: 320px; line-height: 1.4; text-align: right;">
      💡 Todos los pagos ingresados por los clientes en la web se acreditan automáticamente en esta cuenta.
    </div>
  </div>

  <div class="dashboard__filters" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
    <input type="text" id="search-cuentas" class="form-input" placeholder="Buscar por referencia, titular, proveedor o alias..." style="flex: 1; min-width: 240px;">
    <select id="filter-estado-cuentas" class="form-input" style="width: auto; min-width: 170px;">
      <option value="todos">Todos los estados</option>
      <option value="activos" selected>Solo Activas</option>
      <option value="inactivos">Solo Inactivas</option>
    </select>
  </div>

  <p class="scroll-hint">Desliza horizontalmente para ver más &rarr;</p>
  <div class="dashboard__table-wrap">
    <table class="dashboard__table" id="table-cuentas">
      <thead>
        <tr>
          <th>ID</th>
          <th>Referencia / Admin</th>
          <th>Proveedor</th>
          <th>Titular</th>
          <th>Alias / CBU</th>
          <th>Modo</th>
          <th>Estado Receptora</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>
