<?php
/**
 * ARCANA — Panel Tab: Historial de Cambios y Auditoría (Exclusivo Super Admin)
 */
if (!isSuperAdmin()) return;
?>
<div class="dashboard__panel" id="panel-historial">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
      <h2 class="dashboard__title" style="margin-bottom: 0.25rem;">Historial de Cambios y Actividad</h2>
      <p style="color: var(--color-text-muted); font-size: 0.88rem; margin: 0;">Registro cronológico de modificaciones realizadas en el dashboard por administradores.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center;">
      <button type="button" id="btn-refresh-historial" class="btn-pill btn-pill--sm" style="background: rgba(255, 255, 255, 0.08); border: 1px solid var(--color-glass-border); display: inline-flex; align-items: center; gap: 0.4rem;" title="Recargar registro de actividad">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/></svg>
        Actualizar
      </button>
      <button type="button" id="btn-vaciar-historial" class="btn-pill btn-pill--sm" style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; display: inline-flex; align-items: center; gap: 0.4rem;" title="Vaciar registro de auditoría">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        Vaciar
      </button>
    </div>
  </div>

  <div class="dashboard__filters" style="display: flex; gap: 0.75rem; margin-bottom: 1.25rem; flex-wrap: wrap;">
    <input type="text" id="search-historial" class="form-input" placeholder="Buscar por usuario, correo, módulo o detalle de cambio..." style="flex: 2; min-width: 240px;">
    <select id="filter-modulo-historial" class="form-input" style="flex: 1; min-width: 180px;">
      <option value="">Todos los módulos</option>
      <option value="Turnos">📅 Turnos</option>
      <option value="Servicios">💅 Servicios</option>
      <option value="Usuarios">👤 Usuarios</option>
      <option value="Empleados">👩‍💼 Empleados</option>
      <option value="Cuentas">💳 Cuentas y Cobros</option>
      <option value="Bloqueo">🔒 Bloqueo de Días</option>
      <option value="Seguridad">🛡️ Seguridad</option>
    </select>
  </div>

  <p class="scroll-hint">Desliza horizontalmente para ver más &rarr;</p>
  <div class="dashboard__table-wrap">
    <table class="dashboard__table" id="table-historial">
      <thead>
        <tr>
          <th style="width: 155px;">Fecha y Hora</th>
          <th style="width: 175px;">Administrador</th>
          <th style="width: 140px;">Módulo</th>
          <th style="width: 190px;">Cambio Realizado</th>
          <th>Detalle del Cambio</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td colspan="5" style="text-align: center; color: var(--color-text-muted); padding: 2rem;">Cargando historial de cambios...</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
