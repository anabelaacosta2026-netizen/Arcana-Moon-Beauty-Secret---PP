<?php
/**
 * ARCANA — Panel Tab: Usuarios
 */
?>
<div class="dashboard__panel" id="panel-usuarios">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <h2 class="dashboard__title" style="margin-bottom: 0;">Gestión de Usuarios y Roles</h2>
  </div>

  <div class="dashboard__metrics" id="metrics-usuarios">
    <div class="metric-card">
      <div class="metric-card__icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
      </div>
      <div>
        <div class="metric-card__value">0</div>
        <div class="metric-card__label">Total de usuarios</div>
      </div>
    </div>
    <div class="metric-card">
      <div class="metric-card__icon" style="color: var(--color-status-active, #2ed573);">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
      </div>
      <div>
        <div class="metric-card__value">0</div>
        <div class="metric-card__label">Usuarios activos</div>
      </div>
    </div>
    <div class="metric-card">
      <div class="metric-card__icon" style="color: var(--color-status-inactive, #ff6b6b);">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
      </div>
      <div>
        <div class="metric-card__value">0</div>
        <div class="metric-card__label">Usuarios inactivos</div>
      </div>
    </div>
  </div>

  <div class="dashboard__filters" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
    <input type="text" id="search-usuarios" class="form-input" placeholder="Buscar por nombre, apellido, email, DNI o rol..." style="flex: 1; min-width: 240px;">
    <select id="filter-estado-usuarios" class="form-input" style="width: auto; min-width: 170px;">
      <option value="todos">Todos los estados</option>
      <option value="activos" selected>Solo Activos</option>
      <option value="inactivos">Solo Inactivos</option>
    </select>
  </div>

  <p class="scroll-hint">Desliza horizontalmente para ver más &rarr;</p>
  <div class="dashboard__table-wrap">
    <table class="dashboard__table" id="table-usuarios">
      <thead>
        <tr>
          <th>ID</th>
          <th>Rol</th>
          <th>Nombre</th>
          <th>Apellido</th>
          <th>DNI</th>
          <th>Email</th>
          <th>Teléfono</th>
          <th>Registro</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>
