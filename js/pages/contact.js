/* ==========================================
   ARCANA — Contact Form Script
   ========================================== */

import { showFormAlert, showArcanaModal } from '../modules/modal.js';

export function initContact() {
  const form = document.querySelector('#contact-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn ? btn.textContent : 'Enviar mensaje';

    const nombre = form.querySelector('#contact-nombre')?.value.trim() || '';
    const apellido = form.querySelector('#contact-apellido')?.value.trim() || '';
    const email = form.querySelector('#contact-email')?.value.trim() || '';
    const telefono = form.querySelector('#contact-telefono')?.value.trim() || '';
    const direccion = form.querySelector('#contact-direccion')?.value.trim() || '';
    const mensaje = form.querySelector('#contact-mensaje')?.value.trim() || '';

    if (btn) {
      btn.textContent = 'Enviando...';
      btn.disabled = true;
    }

    try {
      const res = await fetch('api/contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          nombre,
          apellido,
          email,
          telefono,
          direccion,
          mensaje
        })
      });

      const result = await res.json();

      if (!result.success) {
        showFormAlert(form, result.message || 'No se pudo enviar el mensaje.', 'error');
        if (btn) {
          btn.textContent = originalText;
          btn.disabled = false;
        }
        return;
      }

      if (btn) {
        btn.textContent = '¡Mensaje Enviado!';
        btn.style.backgroundColor = 'var(--color-status-active, #2ed573)';
      }

      showArcanaModal({
        title: '¡Mensaje Enviado!',
        message: '¡Gracias por contactarnos! Tu mensaje ha sido recibido con éxito. Nos pondremos en contacto a la brevedad.',
        type: 'success'
      });
      form.reset();

      setTimeout(() => {
        if (btn) {
          btn.textContent = originalText;
          btn.style.backgroundColor = '';
          btn.disabled = false;
        }
      }, 4000);

    } catch (err) {
      console.error(err);
      showFormAlert(form, 'Error de conexión al enviar el mensaje. Intente nuevamente.', 'error');
      if (btn) {
        btn.textContent = originalText;
        btn.disabled = false;
      }
    }
  });
}
