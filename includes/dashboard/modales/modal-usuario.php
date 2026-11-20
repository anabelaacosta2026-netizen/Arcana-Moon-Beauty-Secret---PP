<?php
/**
 * ARCANA — Modal: Usuario (Crear / Editar)
 */
?>
<div class="modal-overlay" id="modal-usuario">
  <div class="crud-modal">
    <button type="button" class="modal-close crud-modal__close" aria-label="Cerrar">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 6 6 18" />
        <path d="m6 6 12 12" />
      </svg>
    </button>
    <h3 class="crud-modal__title text-section" id="modal-usuario-title">Nuevo Usuario</h3>
    <form class="crud-modal__form" id="form-usuario" novalidate>
      <input type="hidden" id="usuario-id" value="">
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="usuario-nombre">Nombre</label>
          <input type="text" id="usuario-nombre" class="form-input" required placeholder="Nombre">
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="usuario-apellido">Apellido</label>
          <input type="text" id="usuario-apellido" class="form-input" required placeholder="Apellido">
        </div>
      </div>
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="usuario-email">Email</label>
          <input type="email" id="usuario-email" class="form-input" required placeholder="correo@ejemplo.com">
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="usuario-telefono">Teléfono</label>
          <input type="text" id="usuario-telefono" class="form-input" placeholder="11-1234-5678">
        </div>
      </div>
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="usuario-dni">DNI</label>
          <input type="text" id="usuario-dni" class="form-input" required placeholder="Número de DNI">
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="usuario-rol">Rol</label>
          <select id="usuario-rol" class="form-select">
            <option value="2">Usuario / Cliente</option>
            <option value="1">Administrador</option>
            <option value="3">Empleado</option>
          </select>
        </div>
      </div>
      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="usuario-estado">Estado</label>
          <select id="usuario-estado" class="form-select">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="usuario-password">Contraseña (opcional)</label>
          <input type="password" id="usuario-password" class="form-input" placeholder="Dejar en blanco para mantener">
        </div>
      </div>
      <div class="crud-modal__actions">
        <button type="button" class="btn-pill crud-modal__cancel" id="btn-cancelar-usuario">Cancelar</button>
        <button type="submit" class="btn-pill btn-pill--primary btn-pill--sm">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>
