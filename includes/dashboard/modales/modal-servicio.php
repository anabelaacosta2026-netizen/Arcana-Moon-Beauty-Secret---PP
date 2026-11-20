<?php
/**
 * ARCANA — Modal: Servicio (Crear / Editar)
 */
?>
<div class="modal-overlay" id="modal-servicio">
  <div class="crud-modal">
    <button type="button" class="modal-close crud-modal__close" aria-label="Cerrar">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 6 6 18" />
        <path d="m6 6 12 12" />
      </svg>
    </button>
    <h3 class="crud-modal__title text-section" id="modal-servicio-title">Nuevo Servicio</h3>
    <form class="crud-modal__form" id="form-servicio" novalidate>
      <input type="hidden" id="servicio-id" value="">
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="servicio-nombre">Nombre del Servicio</label>
          <input type="text" id="servicio-nombre" class="form-input" required placeholder="Ej: Kapping Gel + Esmaltado">
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="servicio-categoria">Categoría</label>
          <input type="text" id="servicio-categoria" class="form-input" required placeholder="Manicura, Pedicura, Faciales..." list="list-categorias">
          <datalist id="list-categorias"></datalist>
        </div>
      </div>
      <div class="crud-modal__field">
        <label class="text-label" for="servicio-descripcion">Descripción</label>
        <textarea id="servicio-descripcion" class="form-input" rows="3" required placeholder="Descripción del procedimiento..."></textarea>
      </div>
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="servicio-duracion">Duración (minutos)</label>
          <input type="number" id="servicio-duracion" class="form-input" min="5" step="5" required value="60">
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="servicio-precio">Precio ($ ARS)</label>
          <input type="number" id="servicio-precio" class="form-input" min="0" step="50" required placeholder="15000">
        </div>
      </div>
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
            <label class="text-label" for="servicio-imagen" style="margin: 0;">Imagen del Servicio</label>
            <button type="button" class="btn-toggle-manual-url" data-target="#wrap-servicio-manual-url" style="background: none; border: none; font-size: 0.725rem; color: var(--color-accent); cursor: pointer; text-decoration: underline;">
              Escribir ruta manual
            </button>
          </div>
          <div id="dropzone-servicio" class="image-dropzone-container"></div>
          <div id="wrap-servicio-manual-url" style="display: none; margin-top: 0.5rem;">
            <input type="text" id="servicio-imagen" class="form-input" placeholder="assets/images/service-manicure.jpg">
          </div>
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="servicio-disponible">Estado</label>
          <select id="servicio-disponible" class="form-select">
            <option value="1">Activo (Disponible)</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
      </div>
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="servicio-cuidados-previos">Cuidados Previos (Antes de la sesión)</label>
          <textarea id="servicio-cuidados-previos" class="form-input" rows="2" placeholder="Ej: No cortar cutículas en seco 48 hs antes&#10;Asistir con uñas limpias"></textarea>
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="servicio-cuidados-posteriores">Cuidados Posteriores (Después de la sesión)</label>
          <textarea id="servicio-cuidados-posteriores" class="form-input" rows="2" placeholder="Ej: Evitar agua muy caliente 24 hs&#10;Hidratar cutículas a diario"></textarea>
        </div>
      </div>
      <div class="crud-modal__actions">
        <button type="button" class="btn-pill crud-modal__cancel" id="btn-cancelar-servicio">Cancelar</button>
        <button type="submit" class="btn-pill btn-pill--primary btn-pill--sm">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>
