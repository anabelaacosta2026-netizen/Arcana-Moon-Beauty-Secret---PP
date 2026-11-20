let toastTimer = null;

/**
 * Show a toast notification
 * @param {string} message
 * @param {string} [type='info'] - 'success' | 'error' | 'info'
 */
export function showToast(message, type = 'info') {
  // Remove existing toast
  const existing = document.querySelector('.toast');
  if (existing) existing.remove();
  if (toastTimer) clearTimeout(toastTimer);

  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.style.cssText = `
    position: fixed;
    top: 1.5rem;
    right: 1.5rem;
    z-index: var(--z-toast, 300);
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    font-family: var(--font-body);
    font-weight: 500;
    font-size: 0.875rem;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    transform: translateX(120%);
    opacity: 0;
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease;
    max-width: 320px;
    word-wrap: break-word;
  `;

  const colors = {
    success: 'background: rgba(76, 175, 80, 0.2); border: 1px solid rgba(76, 175, 80, 0.4); color: #81c784;',
    error: 'background: rgba(244, 67, 54, 0.2); border: 1px solid rgba(244, 67, 54, 0.4); color: #e57373;',
    info: 'background: rgba(30, 10, 20, 0.8); border: 1px solid var(--color-glass-border); color: var(--color-text-primary);'
  };
  toast.style.cssText += colors[type] || colors.info;
  toast.textContent = message;

  document.body.appendChild(toast);

  // Animate in
  requestAnimationFrame(() => {
    toast.style.transform = 'translateX(0)';
    toast.style.opacity = '1';
  });

  // Auto dismiss
  toastTimer = setTimeout(() => {
    toast.style.transform = 'translateX(120%)';
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 400);
  }, 4000);
}
