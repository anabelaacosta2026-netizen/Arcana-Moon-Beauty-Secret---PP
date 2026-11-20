<?php
/**
 * ARCANA — Modal: Bloqueo de Días (Crear)
 */
?>
<div class="modal-overlay" id="modal-bloqueo">
  <div class="crud-modal">
    <button type="button" class="modal-close crud-modal__close" aria-label="Cerrar">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
    <h3 class="crud-modal__title text-section" id="modal-bloqueo-title">Nuevo Bloqueo de Fechas</h3>
    <form class="crud-modal__form" id="form-bloqueo" novalidate>
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="bloqueo-desde">Fecha Desde</label>
          <input type="date" id="bloqueo-desde" class="form-input" required>
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="bloqueo-hasta">Fecha Hasta</label>
          <input type="date" id="bloqueo-hasta" class="form-input" required>
        </div>
      </div>
      <div class="crud-modal__field">
        <label class="text-label" for="bloqueo-empleado">Afecta a</label>
        <select id="bloqueo-empleado" class="form-select">
          <option value="0">Todo el Salón (General)</option>
        </select>
      </div>
      <div class="crud-modal__field">
        <label class="text-label" for="bloqueo-motivo">Motivo o Descripción</label>
        <input type="text" id="bloqueo-motivo" class="form-input" placeholder="Ej: Feriado, Vacaciones, Mantenimiento...">
      </div>
      <div class="crud-modal__actions">
        <button type="button" class="btn-pill crud-modal__cancel" id="btn-cancelar-bloqueo">Cancelar</button>
        <button type="submit" class="btn-pill btn-pill--primary btn-pill--sm">Guardar Bloqueo</button>
      </div>
    </form>
  </div>
</div>
