/* ==========================================
   ARCANA — Login Page Script
   ========================================== */

import { showFieldError, showFormAlert } from '../modules/modal.js';

export function initLogin() {
  const form = document.querySelector('#login-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const emailInput = form.querySelector('#email');
    const passInput = form.querySelector('#password');
    const submitBtn = form.querySelector('button[type="submit"]');

    const email = emailInput ? emailInput.value.trim() : '';
    const password = passInput ? passInput.value : '';

    if (!email) { showFieldError(emailInput, 'Ingrese su email'); return; }
    if (!password) { showFieldError(passInput, 'Ingrese su contraseña'); return; }

    const originalBtnText = submitBtn ? submitBtn.textContent : 'Iniciar Sesión';
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Verificando...';
    }

    try {
      let endpoint = 'api/auth.php?action=login';
      try {
        const u = new URL(endpoint, window.location.origin + window.location.pathname);
        u.username = '';
        u.password = '';
        endpoint = u.href;
      } catch (e) {}

      const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
      });
      const result = await res.json();

      if (!result.success) {
        if (result.data && result.data.needs_verification) {
          const verifyUrl = `confirmar-cuenta.php?email=${encodeURIComponent(email)}`;
          showFieldError(emailInput, `${result.message} <a href="${verifyUrl}" style="color: var(--color-accent); text-decoration: underline; display: block; margin-top: 4px;">Haz clic aquí para reenviar o confirmar</a>`, 10000, true);
        } else {
          showFieldError(emailInput, result.message || 'Credenciales incorrectas. Verifique email y contraseña.');
          showFieldError(passInput, 'Verifique su contraseña');
        }
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = originalBtnText;
        }
        return;
      }

      if (submitBtn) {
        submitBtn.textContent = '¡Bienvenido!';
      }
      window.location.href = result.data.redirect || 'index.php';
    } catch (err) {
      console.error(err);
      showFieldError(emailInput, 'Error de conexión con el servidor.');
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
      }
    }
  });
}
