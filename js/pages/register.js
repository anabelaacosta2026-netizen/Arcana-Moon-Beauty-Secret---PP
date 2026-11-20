/* ==========================================
   ARCANA — Register Page Script
   ========================================== */

import { showFormAlert, showFieldError, showArcanaModal } from '../modules/modal.js';

export function initRegister() {
  const form = document.querySelector('#register-form');
  if (!form) return;

  const conditionSelect = form.querySelector('#condition');
  const conditionDetail = form.querySelector('#condition-detail');

  if (conditionSelect && conditionDetail) {
    conditionSelect.addEventListener('change', () => {
      conditionDetail.style.display = conditionSelect.value === 'si' ? 'block' : 'none';
    });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const nombreInput = form.querySelector('#register-nombre');
    const apellidoInput = form.querySelector('#register-apellido');
    const telefonoInput = form.querySelector('#register-telefono');
    const dniInput = form.querySelector('#register-dni');
    const emailInput = form.querySelector('#register-email');
    const passInput = form.querySelector('#password');
    const pass2Input = form.querySelector('#password2');

    const nombre = nombreInput?.value.trim() || '';
    const apellido = apellidoInput?.value.trim() || '';
    const telefono = telefonoInput?.value.trim() || '';
    const dni = dniInput?.value.trim() || '';
    const email = emailInput?.value.trim() || '';
    const password = passInput?.value || '';
    const password2 = pass2Input?.value || '';
    const condicionMedica = conditionSelect?.value === 'si' ? (form.querySelector('#condition-text')?.value.trim() || 'Si') : 'Ninguna';

    const submitBtn = form.querySelector('button[type="submit"]');

    if (!nombre) {
      showFieldError(nombreInput, 'Por favor ingresa tu nombre');
      return;
    }
    if (!apellido) {
      showFieldError(apellidoInput, 'Por favor ingresa tu apellido');
      return;
    }
    if (!email) {
      showFieldError(emailInput, 'Por favor ingresa tu correo electrónico');
      return;
    }
    if (!email.includes('@') || !email.includes('.')) {
      showFieldError(emailInput, 'Ingresa un correo electrónico válido');
      return;
    }
    if (!password) {
      showFieldError(passInput, 'Por favor crea una contraseña');
      return;
    }
    if (password.length < 6) {
      showFieldError(passInput, 'La contraseña debe contener al menos 6 caracteres');
      return;
    }
    if (password !== password2) {
      showFieldError(pass2Input, 'Las contraseñas no coinciden. Por favor verifícalas');
      return;
    }

    const originalBtnText = submitBtn ? submitBtn.textContent : 'Crear Cuenta';
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Registrando...';
    }

    try {
      let endpoint = 'api/auth.php?action=register';
      try {
        const u = new URL(endpoint, window.location.origin + window.location.pathname);
        u.username = '';
        u.password = '';
        endpoint = u.href;
      } catch (e) { }

      const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          nombre,
          apellido,
          telefono,
          dni,
          email,
          password,
          password2,
          condicion_medica: condicionMedica
        })
      });

      let result;
      const text = await res.text();
      try {
        result = JSON.parse(text);
      } catch (jsonErr) {
        console.error('Respuesta no JSON del servidor:', text);
        showArcanaModal({
          title: 'Error de Servidor',
          message: 'Error al procesar el registro en el servidor. Intente nuevamente.',
          type: 'error'
        });
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = originalBtnText;
        }
        return;
      }

      if (!result.success) {
        const msg = result.message || 'Error al procesar el registro.';
        const lower = msg.toLowerCase();
        if (lower.includes('correo') || lower.includes('email')) {
          showFieldError(emailInput, msg);
        } else if (lower.includes('dni')) {
          showFieldError(dniInput, msg);
        } else if (lower.includes('contraseña') || lower.includes('password')) {
          showFieldError(passInput, msg);
        } else {
          showFormAlert(form, msg, 'error');
        }

        showArcanaModal({
          title: 'Atención al Registrarse',
          message: msg,
          type: 'warning'
        });

        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = originalBtnText;
        }
        return;
      }

      // Si requiere verificación por correo
      if (result.data && result.data.requires_verification) {
        const container = form.closest('.glass');
        if (container) {
          container.innerHTML = `
            <div style="text-align: center;">
              <div style="width: 72px; height: 72px; margin: 0 auto 1.5rem; background: rgba(196, 84, 126, 0.18); border: 2px solid var(--color-accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--color-accent); font-size: 32px; box-shadow: 0 0 20px rgba(196,84,126,0.3);">
                ✉
              </div>
              <h2 class="text-section" style="font-size: 1.6rem; margin-bottom: 0.75rem;">¡Revisa tu Correo!</h2>
              <p style="color: var(--color-text); font-size: 1rem; margin-bottom: 0.5rem;">
                Hola <strong>${nombre}</strong>, hemos enviado un enlace de confirmación a:
              </p>
              <div style="background: rgba(255,255,255,0.05); border: 1px solid var(--color-glass-border); padding: 0.75rem 1rem; border-radius: 8px; display: inline-block; color: #f6e05e; font-weight: 600; margin-bottom: 1.25rem; word-break: break-all;">
                ${email}
              </div>
              <p style="color: var(--color-text-muted); font-size: 0.88rem; line-height: 1.6; margin-bottom: 1.5rem;">
                Por favor abre el correo y haz clic en el botón <strong>"Confirmar mi Cuenta"</strong> para activar tu usuario y poder agendar tus turnos.
              </p>
              ${result.data.verify_link ? `
                <div style="background: rgba(34, 197, 94, 0.1); border: 1px dashed rgba(34, 197, 94, 0.4); border-radius: 8px; padding: 0.85rem 1rem; margin-bottom: 1.5rem; text-align: left;">
                  <span style="font-size: 0.85rem; color: #86efac; display: block; margin-bottom: 0.35rem;"><strong>Enlace directo de activación:</strong></span>
                  <a href="${result.data.verify_link}" style="color: #4ade80; font-size: 0.82rem; word-break: break-all; text-decoration: underline;">${result.data.verify_link}</a>
                </div>
              ` : ''}
              <div style="background: rgba(246, 224, 94, 0.08); border: 1px dashed rgba(246, 224, 94, 0.3); border-radius: 8px; padding: 0.75rem 1rem; font-size: 0.8rem; color: #f6ad55; margin-bottom: 1.75rem; text-align: left;">
                💡 <strong>Importante:</strong> Si no lo ves en tu bandeja principal en unos minutos, revisa tu carpeta de <strong>Spam</strong> o <strong>Promociones</strong>.
              </div>
              <div style="display: flex; gap: 0.75rem; flex-direction: column;">
                <a href="login.php?email=${encodeURIComponent(email)}" class="btn-pill btn-pill--primary btn-pill--full" style="text-decoration: none;">
                  Ir a Iniciar Sesión
                </a>
                <button type="button" id="btn-resend-now" class="btn-pill btn-pill--secondary btn-pill--full">
                  Reenviar correo de confirmación
                </button>
              </div>
              <div id="resend-status" style="margin-top: 0.75rem; font-size: 0.85rem; display: none;"></div>
            </div>
          `;

          const resendBtn = container.querySelector('#btn-resend-now');
          const statusDiv = container.querySelector('#resend-status');
          if (resendBtn) {
            resendBtn.addEventListener('click', async () => {
              resendBtn.disabled = true;
              resendBtn.textContent = 'Reenviando...';
              try {
                let resendEndpoint = 'api/auth.php?action=resend_verification';
                try {
                  const u = new URL(resendEndpoint, window.location.origin + window.location.pathname);
                  u.username = '';
                  u.password = '';
                  resendEndpoint = u.href;
                } catch (e) { }

                const resendRes = await fetch(resendEndpoint, {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify({ email })
                });
                const resendData = await resendRes.json();
                statusDiv.style.display = 'block';
                statusDiv.style.color = resendData.success ? '#2ed573' : '#ff6b6b';
                statusDiv.textContent = resendData.message || (resendData.success ? '✓ Correo reenviado.' : '✕ Error al reenviar.');
              } catch (e) {
                statusDiv.style.display = 'block';
                statusDiv.style.color = '#ff6b6b';
                statusDiv.textContent = '✕ Error al conectar con el servidor.';
              } finally {
                resendBtn.disabled = false;
                resendBtn.textContent = 'Reenviar correo de confirmación';
              }
            });
          }
          return;
        }
      }

      if (submitBtn) submitBtn.textContent = '¡Cuenta creada!';
      showArcanaModal({
        title: '¡Registro Exitoso!',
        message: '¡Cuenta creada exitosamente! Bienvenido/a a Arcana Moon Beauty Secret.',
        type: 'success',
        confirmText: 'Continuar',
        onConfirm: () => {
          window.location.href = result.data?.redirect || 'index.php';
        }
      });
    } catch (err) {
      console.error(err);
      showFormAlert(form, 'Error de conexión al registrar su cuenta: ' + (err.message || 'Intente nuevamente más tarde.'), 'error');
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
      }
    }
  });
}
