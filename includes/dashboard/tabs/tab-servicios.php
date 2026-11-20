<?php
/**
 * ARCANA — Panel Tab: Servicios
 */
?>
<div class="dashboard__panel dashboard__panel--active" id="panel-servicios">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <h2 class="dashboard__title" style="margin-bottom: 0;">Gestión de Servicios y Precios</h2>
  </div>

  <div class="dashboard__metrics" id="metrics-servicios">
    <div class="metric-card">
      <div class="metric-card__icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
          <path d="M16 3.128a4 4 0 0 1 0 7.744" />
          <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
        </svg>
      </div>
      <div>
        <div class="metric-card__value">0</div>
        <div class="metric-card__label">Total de servicios</div>
      </div>
    </div>
    <div class="metric-card">
      <div class="metric-card__icon" style="color: var(--color-status-active, #2ed573);">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
          <line x1="16" x2="16" y1="2" y2="6" />
          <line x1="8" x2="8" y1="2" y2="6" />
          <line x1="3" x2="21" y1="10" y2="10" />
        </svg>
      </div>
      <div>
        <div class="metric-card__value">0</div>
        <div class="metric-card__label">Servicios activos</div>
      </div>
    </div>
    <div class="metric-card">
      <div class="metric-card__icon" style="color: var(--color-status-inactive, #ff6b6b);">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
          <line x1="16" x2="16" y1="2" y2="6" />
          <line x1="8" x2="8" y1="2" y2="6" />
          <line x1="3" x2="21" y1="10" y2="10" />
          <path d="m14 16-2 2-2-2" />
        </svg>
      </div>
      <div>
        <div class="metric-card__value">0</div>
        <div class="metric-card__label">Servicios inactivos</div>
      </div>
    </div>
  </div>

  <div class="dashboard__filters" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
    <input type="text" id="search-servicios" class="form-input" placeholder="Buscar servicio por nombre, categoría o especialista..." style="flex: 1; min-width: 240px;">
    <select id="filter-estado-servicios" class="form-input" style="width: auto; min-width: 170px;">
      <option value="todos">Todos los estados</option>
      <option value="activos" selected>Solo Activos</option>
      <option value="inactivos">Solo Inactivos</option>
    </select>
  </div>

  <p class="scroll-hint">Desliza horizontalmente para ver más &rarr;</p>
  <div class="dashboard__table-wrap">
    <table class="dashboard__table" id="table-servicios">
      <thead>
        <tr>
          <th>ID</th>
          <th>Imagen</th>
          <th>Nombre</th>
          <th>Descripción</th>
          <th>Categoría</th>
          <th>Duración</th>
          <th>Precio</th>
          <th>Estado</th>
          <th>Especialistas</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>
