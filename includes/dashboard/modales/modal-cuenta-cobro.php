<?php
/**
 * ARCANA — Modal: Cuenta de Cobro / Pasarela (Crear / Editar)
 */
?>
<div class="modal-overlay" id="modal-cuenta-cobro">
  <div class="crud-modal" style="max-width: 620px; width: 95%; max-height: 90vh; overflow-y: auto;">
    <button type="button" class="modal-close crud-modal__close" aria-label="Cerrar">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
    <h3 class="crud-modal__title text-section" id="modal-cuenta-title">Nueva Cuenta de Cobro</h3>
    <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-top: -0.5rem; margin-bottom: 1.25rem;">Configura las credenciales o datos bancarios para recibir los pagos de las señas.</p>
    
    <form class="crud-modal__form" id="form-cuenta-cobro" novalidate>
      <input type="hidden" id="cuenta-id" value="">
      
      <div class="crud-modal__field">
        <label class="text-label" for="cuenta-nombre-ref">Nombre de Referencia *</label>
        <input type="text" id="cuenta-nombre-ref" class="form-input" required placeholder="Ej: Mercado Pago - Admin Anabela / Cuenta DNI">
      </div>

      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="cuenta-proveedor">Proveedor / Plataforma *</label>
          <select id="cuenta-proveedor" class="form-select">
            <option value="mercadopago">Mercado Pago (Checkout & Tarjetas)</option>
            <option value="ualabis">Ualá Bis (API & Tarjetas)</option>
            <option value="transferencia">Transferencia Bancaria / CVU / CBU</option>
            <option value="efectivo">Efectivo / En el Salón</option>
          </select>
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="cuenta-modo">Modo de Operación</label>
          <select id="cuenta-modo" class="form-select">
            <option value="sandbox">Sandbox (Pruebas Demo)</option>
            <option value="produccion">Producción (Cobros Reales)</option>
          </select>
        </div>
      </div>

      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="cuenta-titular">Nombre y Apellido del Titular *</label>
          <input type="text" id="cuenta-titular" class="form-input" required placeholder="Ej: Anabela Acosta">
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="cuenta-cuit">CUIT / CUIL</label>
          <input type="text" id="cuenta-cuit" class="form-input" placeholder="Ej: 27-40123456-4">
        </div>
      </div>

      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="cuenta-email">Email Asociado a la Cuenta</label>
          <input type="email" id="cuenta-email" class="form-input" placeholder="correo.titular@gmail.com">
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="cuenta-banco">Banco o Billetera</label>
          <input type="text" id="cuenta-banco" class="form-input" placeholder="Ej: Mercado Pago, Galicia, Cuenta DNI, Personal Pay">
        </div>
      </div>

      <div class="crud-modal__row">
        <div class="crud-modal__field">
          <label class="text-label" for="cuenta-alias">Alias de Cobro</label>
          <input type="text" id="cuenta-alias" class="form-input" placeholder="Ej: arcana.estetica.mp">
        </div>
        <div class="crud-modal__field">
          <label class="text-label" for="cuenta-cbu">CBU o CVU (22 dígitos)</label>
          <input type="text" id="cuenta-cbu" class="form-input" placeholder="00000031000...">
        </div>
      </div>

      <!-- Sección de Credenciales API para Pasarelas (MP / Ualá) -->
      <div id="section-api-keys" style="margin-top: 0.75rem; padding: 1rem; border-radius: 8px; background: rgba(255, 255, 255, 0.03); border: 1px dashed var(--color-glass-border);">
        <div style="font-size: 0.85rem; font-weight: 600; color: #f6e05e; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-1.5 1.5L14 9m-4 4l-4 4-2-2 4-4m2-2l3.5-3.5"/></svg>
          Credenciales API de Desarrollador (Mercado Pago / Ualá)
        </div>
        
        <div class="crud-modal__field" style="margin-bottom: 0.75rem;">
          <label class="text-label" for="cuenta-public-key">Public Key (Clave Pública)</label>
          <input type="text" id="cuenta-public-key" class="form-input" placeholder="Ej: TEST-4f56a89c-1234-5678-9abc-...">
        </div>

        <div class="crud-modal__field">
          <label class="text-label" for="cuenta-access-token">Access Token (Token Secreto)</label>
          <input type="password" id="cuenta-access-token" class="form-input" placeholder="Ej: TEST-1234567890123456-123456-...">
          <small style="font-size: 0.75rem; color: var(--color-text-muted); display: block; margin-top: 0.25rem;">
            Se almacena en el backend y nunca se comparte con los clientes.
          </small>
        </div>
      </div>

      <div style="margin-top: 1.25rem; display: flex; align-items: center; gap: 0.6rem;">
        <input type="checkbox" id="cuenta-es-activa" style="accent-color: #2ed573; width: 18px; height: 18px; cursor: pointer;">
        <label for="cuenta-es-activa" style="font-size: 0.875rem; color: #fff; cursor: pointer; font-weight: 600;">
          Establecer como Cuenta Receptora Activa inmediatamente
        </label>
      </div>

      <div class="crud-modal__actions" style="margin-top: 1.5rem;">
        <button type="button" class="btn-pill crud-modal__cancel" id="btn-cancelar-cuenta">Cancelar</button>
        <button type="submit" class="btn-pill btn-pill--primary btn-pill--sm">Guardar Cuenta</button>
      </div>
    </form>
  </div>
</div>
