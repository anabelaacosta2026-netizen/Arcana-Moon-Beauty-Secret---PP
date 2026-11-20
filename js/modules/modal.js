const keyHandlers = new WeakMap();

/**
 * Open a modal by selector or element
 * @param {string|HTMLElement} target
 */
export function openModal(target) {
  const overlay = typeof target === 'string' ? document.querySelector(target) : target;
  if (!overlay) return;

  const handler = keyHandlers.get(overlay);
  if (typeof handler === 'function') {
    document.removeEventListener('keydown', handler);
  }
  keyHandlers.delete(overlay);

  overlay.classList.add('modal-overlay--open');
  document.body.style.overflow = 'hidden';

  // Close on escape
  const onKey = (e) => {
    if (e.key === 'Escape') closeModal(overlay);
  };
  keyHandlers.set(overlay, onKey);
  document.addEventListener('keydown', onKey);
}

/**
 * Close a modal by selector or element
 * @param {string|HTMLElement} target
 */
export function closeModal(target) {
  const overlay = typeof target === 'string' ? document.querySelector(target) : target;
  if (!overlay) return;

  overlay.classList.remove('modal-overlay--open');
  document.body.style.overflow = '';
  const handler = keyHandlers.get(overlay);
  if (typeof handler === 'function') {
    document.removeEventListener('keydown', handler);
  }
  keyHandlers.delete(overlay);
}

/**
 * Initialize all modals with close buttons
 */
export function initModals() {
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    const closeBtn = overlay.querySelector('.modal-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        closeModal(overlay);
      });
    }
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        closeModal(overlay);
      }
    });
  });
}

/**
 * Show a styled Arcana modal instead of window.alert()
 */
export function showArcanaModal({
  title = 'Aviso',
  message = '',
  type = 'info', // 'info', 'success', 'error', 'warning'
  confirmText = 'Entendido',
  cancelText = null,
  onConfirm = null,
  onCancel = null
} = {}) {
  let modal = document.getElementById('arcana-global-alert-modal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'arcana-global-alert-modal';
    modal.className = 'modal-overlay';
    modal.style.zIndex = '99999';
    modal.innerHTML = `
      <div class="modal-card" style="max-width: 440px; text-align: center; padding: 2.25rem 2rem; position: relative;">
        <button class="modal-close" type="button" aria-label="Cerrar modal" style="position: absolute; top: 1.25rem; right: 1.25rem; background: none; border: none; color: var(--color-text-muted); cursor: pointer; font-size: 1.25rem; transition: color 0.2s;">✕</button>
        <div class="modal-icon" style="width: 58px; height: 58px; margin: 0 auto 1.25rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 26px;"></div>
        <h3 class="modal-title" style="margin-bottom: 0.75rem; font-family: var(--font-heading); font-size: 1.5rem; color: var(--color-text-primary);"></h3>
        <div class="modal-message" style="color: var(--color-text-secondary); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.75rem; word-break: break-word;"></div>
        <div class="modal-buttons" style="display: flex; gap: 0.75rem; justify-content: center; align-items: center; flex-wrap: wrap;">
          <button class="btn modal-cancel-btn" type="button" style="display: none; min-width: 120px; padding: 0.75rem 1.5rem; border-radius: 50px; font-weight: 600; cursor: pointer; background: rgba(255,255,255,0.08); color: var(--color-text-secondary); border: 1px solid var(--color-glass-border); transition: all 0.2s;"></button>
          <button class="btn btn-primary modal-action-btn" type="button" style="min-width: 140px; padding: 0.75rem 2rem; border-radius: 50px; font-weight: 600; cursor: pointer; border: none; color: #fff;"></button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);

    const handleClose = () => {
      closeModal(modal);
      if (modal._onCancel) modal._onCancel();
    };

    modal.querySelector('.modal-close').addEventListener('click', handleClose);
    modal.addEventListener('click', (e) => {
      if (e.target === modal) handleClose();
    });
  }

  modal._onCancel = onCancel;

  const iconEl = modal.querySelector('.modal-icon');
  const titleEl = modal.querySelector('.modal-title');
  const messageEl = modal.querySelector('.modal-message');
  const btnEl = modal.querySelector('.modal-action-btn');
  const cancelBtn = modal.querySelector('.modal-cancel-btn');

  const themes = {
    success: {
      bg: 'rgba(34, 197, 94, 0.15)',
      border: '1px solid rgba(34, 197, 94, 0.4)',
      color: '#4ade80',
      icon: '✓',
      btnBg: 'linear-gradient(135deg, #16a34a, #22c55e)'
    },
    error: {
      bg: 'rgba(239, 68, 68, 0.15)',
      border: '1px solid rgba(239, 68, 68, 0.4)',
      color: '#f87171',
      icon: '✕',
      btnBg: 'linear-gradient(135deg, #dc2626, #ef4444)'
    },
    warning: {
      bg: 'rgba(245, 158, 11, 0.15)',
      border: '1px solid rgba(245, 158, 11, 0.4)',
      color: '#fbbf24',
      icon: '⚠',
      btnBg: 'linear-gradient(135deg, #d97706, #f59e0b)'
    },
    info: {
      bg: 'rgba(196, 84, 126, 0.15)',
      border: '1px solid rgba(196, 84, 126, 0.4)',
      color: 'var(--color-accent, #c4547e)',
      icon: 'ℹ',
      btnBg: 'var(--gradient-accent, linear-gradient(135deg, #c4547e, #e8a8c8))'
    }
  };

  const theme = themes[type] || themes.info;
  iconEl.style.background = theme.bg;
  iconEl.style.border = theme.border;
  iconEl.style.color = theme.color;
  iconEl.textContent = theme.icon;

  titleEl.textContent = title;
  messageEl.innerHTML = message;
  btnEl.textContent = confirmText;
  btnEl.style.background = theme.btnBg;

  if (cancelText) {
    cancelBtn.style.display = 'inline-block';
    cancelBtn.textContent = cancelText;
    cancelBtn.onclick = () => {
      closeModal(modal);
      if (typeof onCancel === 'function') onCancel();
    };
  } else {
    cancelBtn.style.display = 'none';
  }

  btnEl.onclick = () => {
    modal._onCancel = null;
    closeModal(modal);
    if (typeof onConfirm === 'function') {
      onConfirm();
    }
  };

  openModal(modal);
}

/**
 * Promise-based confirmation dialog replacing window.confirm()
 */
export function showArcanaConfirm({
  title = '¿Confirmar acción?',
  message = '',
  type = 'warning',
  confirmText = 'Confirmar',
  cancelText = 'Cancelar'
} = {}) {
  return new Promise((resolve) => {
    showArcanaModal({
      title,
      message,
      type,
      confirmText,
      cancelText,
      onConfirm: () => resolve(true),
      onCancel: () => resolve(false)
    });
  });
}

/**
 * Show an error / alert banner directly above an input or at the top of a form
 */
export function showFormAlert(formOrElement, message, type = 'error', timeout = 6000) {
  if (!formOrElement) return;
  const form = formOrElement.tagName === 'FORM' ? formOrElement : formOrElement.closest('form');
  if (!form) return;

  let alertBox = form.querySelector('.form-alert');
  if (!alertBox) {
    alertBox = document.createElement('div');
    alertBox.className = 'form-alert';
    form.insertBefore(alertBox, form.firstChild);
  }

  const isError = type === 'error';
  const isSuccess = type === 'success';

  alertBox.style.cssText = `
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.85rem 1.15rem;
    border-radius: 10px;
    margin-bottom: 1.25rem;
    font-size: 0.875rem;
    line-height: 1.5;
    backdrop-filter: blur(10px);
    transition: opacity 0.3s ease;
    ${isError ? 'background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5;' : ''}
    ${isSuccess ? 'background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.4); color: #86efac;' : ''}
    ${!isError && !isSuccess ? 'background: rgba(196, 84, 126, 0.15); border: 1px solid rgba(196, 84, 126, 0.4); color: #f472b6;' : ''}
  `;

  alertBox.innerHTML = `
    <span style="font-size: 1.15rem; flex-shrink: 0;">${isError ? '⚠️' : (isSuccess ? '✓' : 'ℹ️')}</span>
    <span style="flex-grow: 1;">${message}</span>
    <button type="button" style="background: none; border: none; color: inherit; cursor: pointer; font-size: 1.1rem; opacity: 0.7; padding: 0 0.25rem;" aria-label="Cerrar">✕</button>
  `;

  alertBox.querySelector('button')?.addEventListener('click', () => {
    alertBox.style.display = 'none';
  });

  alertBox.style.display = 'flex';
  alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

  if (timeout > 0) {
    setTimeout(() => {
      if (alertBox && alertBox.parentElement) {
        alertBox.style.display = 'none';
      }
    }, timeout);
  }
}

/**
 * Show error directly ABOVE a specific input field in red
 */
export function showFieldError(input, message, timeout = 7000, isHtml = false) {
  if (!input) return;
  const parent = input.parentElement;
  if (!parent) return;

  let err = parent.querySelector('.field-error');
  if (!err) {
    err = document.createElement('span');
    err.className = 'field-error';
    parent.insertBefore(err, input);
  } else {
    if (err.nextElementSibling !== input) {
      parent.insertBefore(err, input);
    }
  }

  err.style.cssText = 'color: #ff6b6b; font-size: 0.8125rem; font-weight: 500; margin-bottom: 0.35rem; margin-top: 0.15rem; display: flex; align-items: center; gap: 0.35rem; line-height: 1.4;';

  const prefixIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ff6b6b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';

  if (isHtml) {
    err.innerHTML = `${prefixIcon} <span>${message}</span>`;
  } else {
    err.innerHTML = `${prefixIcon} <span class="field-error-text"></span>`;
    const spanText = err.querySelector('.field-error-text');
    if (spanText) spanText.textContent = message;
  }
  err.style.display = 'flex';
  input.style.borderColor = '#ff6b6b';

  const clearHandler = () => {
    if (err) {
      err.innerHTML = '';
      err.style.display = 'none';
    }
    input.style.borderColor = '';
    input.removeEventListener('input', clearHandler);
    input.removeEventListener('change', clearHandler);
  };
  input.addEventListener('input', clearHandler);
  input.addEventListener('change', clearHandler);

  try {
    input.focus({ preventScroll: false });
  } catch (e) {}

  if (timeout > 0) {
    setTimeout(() => {
      if (err) {
        err.innerHTML = '';
        err.style.display = 'none';
      }
      if (input) input.style.borderColor = '';
    }, timeout);
  }
}

// Global overrides so NO native browser alert() ever appears
if (typeof window !== 'undefined') {
  window.showArcanaModal = showArcanaModal;
  window.showArcanaConfirm = showArcanaConfirm;
  window.showFormAlert = showFormAlert;
  window.showFieldError = showFieldError;
  window.alert = function(msg) {
    showArcanaModal({
      title: 'Atención',
      message: String(msg ?? ''),
      type: 'info'
    });
  };
}
