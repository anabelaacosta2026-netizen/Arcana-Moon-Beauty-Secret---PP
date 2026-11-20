<?php
/**
 * ARCANA — Modal: Turno / Cita (Crear / Editar)
 */
?>
<div class="modal-overlay" id="modal-turno">
  <div class="crud-modal">
    <button type="button" class="modal-close crud-modal__close" aria-label="Cerrar">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 6 6 18" />
        <path d="m6 6 12 12" />
      </svg>
    </button>
    <h3 class="crud-modal__title text-section" id="modal-turno-title">Nuevo Turno / Cita</h3>
    <form class="crud-modal__form" id="form-turno" novalidate>
      <input type="hidden" id="turno-id" value="">
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="turno-cliente">Cliente (Usuario)</label>
          <select id="turno-cliente" class="form-select" required></select>
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="turno-espacio">Espacio de Atención</label>
          <select id="turno-espacio" class="form-select" required></select>
        </div>
      </div>
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="turno-empleado">Especialista / Empleado</label>
          <select id="turno-empleado" class="form-select" required></select>
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="turno-servicio">Servicio</label>
          <select id="turno-servicio" class="form-select" required></select>
        </div>
      </div>
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="turno-fecha-hora">Fecha y Hora</label>
          <input type="datetime-local" id="turno-fecha-hora" class="form-input" required>
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="turno-modalidad">Modalidad</label>
          <select id="turno-modalidad" class="form-select">
            <option value="Presencial">Presencial</option>
            <option value="Domicilio">Domicilio</option>
          </select>
        </div>
      </div>
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="turno-estado">Estado del Turno</label>
          <select id="turno-estado" class="form-select">
            <option value="pendiente">Pendiente</option>
            <option value="confirmado">Confirmado</option>
            <option value="completado">Completado</option>
            <option value="cancelado">Cancelado</option>
          </select>
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="turno-observaciones">Observaciones</label>
          <input type="text" id="turno-observaciones" class="form-input" placeholder="Detalles o notas...">
        </div>
      </div>
      <div class="crud-modal__actions">
        <button type="button" class="btn-pill crud-modal__cancel" id="btn-cancelar-turno">Cancelar</button>
        <button type="submit" class="btn-pill btn-pill--primary btn-pill--sm">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>
