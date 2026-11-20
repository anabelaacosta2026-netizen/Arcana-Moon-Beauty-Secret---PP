/* ==========================================
   ARCANA — Confirmation Animation Module
   Overlay modal with animated SVG checkmark & luxurious particles
   ========================================== */

export function showConfirmationAnimation({
  title = '¡Reserva Confirmada!',
  subtitle = 'Tu turno ha sido registrado correctamente.',
  details = '',
  duration = 2500,
  onComplete = null
} = {}) {
  // Eliminar overlay existente si hubiera
  const existing = document.getElementById('arcana-confirmation-modal');
  if (existing) existing.remove();

  const overlay = document.createElement('div');
  overlay.id = 'arcana-confirmation-modal';
  overlay.style.cssText = `
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(11, 12, 16, 0.88);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    opacity: 0;
    transition: opacity 0.35s cubic-bezier(0.16, 1, 0.3, 1);
  `;

  overlay.innerHTML = `
    <div style="
      background: radial-gradient(circle at center, #24121d 0%, #13141c 100%);
      border: 1px solid rgba(196, 84, 126, 0.4);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7), 0 0 40px rgba(196, 84, 126, 0.2);
      border-radius: 24px;
      max-width: 440px;
      width: 100%;
      padding: 2.5rem 2rem;
      text-align: center;
      transform: scale(0.85);
      transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    ">
      <!-- Animated Check Circle SVG -->
      <div style="width: 88px; height: 88px; margin: 0 auto 1.5rem; position: relative;">
        <svg viewBox="0 0 100 100" style="width: 100%; height: 100%;">
          <!-- Glowing pulse circle -->
          <circle cx="50" cy="50" r="44" fill="none" stroke="rgba(196, 84, 126, 0.2)" stroke-width="4"/>
          <!-- Animated drawing circle -->
          <circle cx="50" cy="50" r="44" fill="none" stroke="#2ed573" stroke-width="4"
                  stroke-dasharray="280" stroke-dashoffset="280" style="animation: arcanaStroke 0.6s ease-out forwards;"/>
          <!-- Animated Checkmark -->
          <path d="M30 52 L44 66 L72 36" fill="none" stroke="#2ed573" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"
                stroke-dasharray="100" stroke-dashoffset="100" style="animation: arcanaCheck 0.4s 0.4s ease-out forwards;"/>
        </svg>
      </div>

      <h2 style="font-family: var(--font-heading, 'Cinzel', serif); font-size: 1.6rem; color: #fff; margin: 0 0 0.5rem 0; letter-spacing: 0.5px;">
        ${title}
      </h2>
      <p style="color: #cbd5e1; font-size: 0.95rem; line-height: 1.5; margin: 0 0 1rem 0;">
        ${subtitle}
      </p>

      ${details ? `<div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 0.75rem 1rem; font-size: 0.85rem; color: #f6ad55; margin-bottom: 1.25rem;">${details}</div>` : ''}

      <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.8rem; color: var(--color-text-muted, #94a3b8);">
        <div style="width: 6px; height: 6px; border-radius: 50%; background: var(--color-accent, #c4547e); animation: arcanaPulse 1s infinite;"></div>
        <span>Redirigiendo a tu confirmación...</span>
      </div>
    </div>

    <style>
      @keyframes arcanaStroke {
        to { stroke-dashoffset: 0; }
      }
      @keyframes arcanaCheck {
        to { stroke-dashoffset: 0; }
      }
      @keyframes arcanaPulse {
        0%, 100% { opacity: 0.3; transform: scale(0.8); }
        50% { opacity: 1; transform: scale(1.3); }
      }
    </style>
  `;

  document.body.appendChild(overlay);

  // Trigger animation in
  requestAnimationFrame(() => {
    overlay.style.opacity = '1';
    const card = overlay.firstElementChild;
    if (card) card.style.transform = 'scale(1)';
  });

  return new Promise((resolve) => {
    setTimeout(() => {
      overlay.style.opacity = '0';
      const card = overlay.firstElementChild;
      if (card) card.style.transform = 'scale(0.9)';
      setTimeout(() => {
        overlay.remove();
        if (typeof onComplete === 'function') onComplete();
        resolve();
      }, 350);
    }, duration);
  });
}
