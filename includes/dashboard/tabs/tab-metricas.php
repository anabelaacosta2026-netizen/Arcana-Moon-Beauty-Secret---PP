<?php
/**
 * ARCANA — Panel Tab: Métricas y Rendimiento General
 */
?>
<div class="dashboard__panel" id="panel-metricas">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
      <h2 class="dashboard__title" style="margin-bottom: 0.25rem;">Panel de Métricas y Control</h2>
      <p style="color: var(--color-text-muted); font-size: 0.88rem; margin: 0;">Métricas en tiempo real, ingresos estimados, servicios más demandados y KPIs de especialistas.</p>
    </div>
    <button type="button" id="btn-refresh-metricas" class="btn-pill btn-pill--sm" style="background: rgba(255,255,255,0.06); border: 1px solid var(--color-glass-border);">↻ Actualizar Métricas</button>
  </div>

  <!-- Cards Métricas Principales -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;" id="kpi-grid"></div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Gráfico 7 Días -->
    <div class="glass" style="padding: 1.5rem; border-radius: var(--radius-card, 16px);">
      <h3 style="font-size: 1.05rem; margin: 0 0 1rem 0; color: #fff; font-weight: 600;">Turnos en los últimos 7 días</h3>
      <div id="chart-7-dias" style="display: flex; align-items: flex-end; justify-content: space-between; height: 180px; gap: 0.5rem; padding-top: 1rem;"></div>
    </div>

    <!-- Top 5 Servicios -->
    <div class="glass" style="padding: 1.5rem; border-radius: var(--radius-card, 16px);">
      <h3 style="font-size: 1.05rem; margin: 0 0 1rem 0; color: #fff; font-weight: 600;">Top 5 Servicios más Solicitados</h3>
      <div id="top-servicios-list" style="display: flex; flex-direction: column; gap: 0.75rem;"></div>
    </div>
  </div>

  <!-- Tabla de Desempeño por Empleado -->
  <div class="glass" style="padding: 1.5rem; border-radius: var(--radius-card, 16px);">
    <h3 style="font-size: 1.05rem; margin: 0 0 1rem 0; color: #fff; font-weight: 600;">Desempeño y Rendimiento por Especialista</h3>
    <div class="dashboard__table-wrap">
      <table class="dashboard__table" id="table-empleados-kpi">
        <thead>
          <tr>
            <th>Especialista</th>
            <th>Turnos Asignados</th>
            <th>Completados</th>
            <th>Cancelados</th>
            <th>Efectividad</th>
            <th>Ingresos Generados</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>
