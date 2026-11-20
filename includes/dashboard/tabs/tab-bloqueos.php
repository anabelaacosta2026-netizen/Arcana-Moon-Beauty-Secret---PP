<?php
/**
 * ARCANA — Panel Tab: Bloqueo de Días y Feriados
 */
?>
<div class="dashboard__panel" id="panel-bloqueos">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
      <h2 class="dashboard__title" style="margin-bottom: 0.25rem;">Bloqueo de Días y Feriados</h2>
      <p style="color: var(--color-text-muted); font-size: 0.88rem; margin: 0;">Configura períodos no disponibles para todo el salón o especialistas puntuales.</p>
    </div>
    <button type="button" id="btn-nuevo-bloqueo" class="btn-pill btn-pill--primary btn-pill--sm">+ Nuevo Bloqueo</button>
  </div>

  <div class="dashboard__table-wrap">
    <table class="dashboard__table" id="table-bloqueos">
      <thead>
        <tr>
          <th>ID</th>
          <th>Alcance / Especialista</th>
          <th>Fecha Desde</th>
          <th>Fecha Hasta</th>
          <th>Motivo</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>
