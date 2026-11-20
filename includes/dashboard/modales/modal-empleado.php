<?php
/**
 * ARCANA — Modal: Empleado (Crear / Editar)
 */
?>
<div class="modal-overlay" id="modal-empleado">
  <div class="crud-modal">
    <button type="button" class="modal-close crud-modal__close" aria-label="Cerrar">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 6 6 18" />
        <path d="m6 6 12 12" />
      </svg>
    </button>
    <h3 class="crud-modal__title text-section" id="modal-empleado-title">Nuevo Empleado</h3>
    <form class="crud-modal__form" id="form-empleado" novalidate>
      <input type="hidden" id="empleado-id" value="">
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="empleado-nombre">Nombre</label>
          <input type="text" id="empleado-nombre" class="form-input" required placeholder="Nombre">
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="empleado-apellido">Apellido</label>
          <input type="text" id="empleado-apellido" class="form-input" required placeholder="Apellido">
        </div>
      </div>
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="empleado-email">Email</label>
          <input type="email" id="empleado-email" class="form-input" required placeholder="empleado@arcana.com">
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="empleado-telefono">Teléfono</label>
          <input type="text" id="empleado-telefono" class="form-input" placeholder="11-1234-5678">
        </div>
      </div>
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
            <label class="text-label" for="empleado-foto" style="margin: 0;">Foto del Empleado</label>
            <button type="button" class="btn-toggle-manual-url" data-target="#wrap-empleado-manual-url" style="background: none; border: none; font-size: 0.725rem; color: var(--color-accent); cursor: pointer; text-decoration: underline;">
              Escribir ruta manual
            </button>
          </div>
          <div id="dropzone-empleado" class="image-dropzone-container"></div>
          <div id="wrap-empleado-manual-url" style="display: none; margin-top: 0.5rem;">
            <input type="text" id="empleado-foto" class="form-input" placeholder="assets/images/team-avatar.jpg">
          </div>
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="empleado-modalidad">Modalidad de Disponibilidad</label>
          <select id="empleado-modalidad" class="form-select">
            <option value="Presencial">Presencial</option>
            <option value="Domicilio">Domicilio</option>
            <option value="Mixto">Mixto</option>
          </select>
        </div>
      </div>
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="empleado-especialidades">Especialidades (separadas por coma)</label>
          <input type="text" id="empleado-especialidades" class="form-input" placeholder="Nail Art, Esculpidas, Lifting...">
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="empleado-estado">Estado</label>
          <select id="empleado-estado" class="form-select">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
      </div>
      <div class="crud-modal__actions">
        <button type="button" class="btn-pill crud-modal__cancel" id="btn-cancelar-empleado">Cancelar</button>
        <button type="submit" class="btn-pill btn-pill--primary btn-pill--sm">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>
