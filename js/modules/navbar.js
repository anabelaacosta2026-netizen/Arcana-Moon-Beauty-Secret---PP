import { qs } from '../utils/dom.js';

export function initNavbar() {
  const navbar = qs('.navbar');
  if (!navbar) return;

  // Scroll darkening
  let ticking = false;
  window.addEventListener('scroll', () => {
    if (!ticking) {
      requestAnimationFrame(() => {
        navbar.classList.toggle('navbar--scrolled', window.scrollY > 50);
        ticking = false;
      });
      ticking = true;
    }
  });

  // Mobile Drawer (< 860px)
  const hamburger = qs('.navbar__hamburger');
  const overlay = qs('.navbar__overlay');
  const backdrop = qs('.navbar__backdrop') || qs('.navbar__overlay-backdrop');
  const closeBtn = qs('.navbar__overlay-close');
  const overlayLinks = document.querySelectorAll('.navbar__overlay-link, .navbar__overlay a');

  function openDrawer() {
    if (overlay) overlay.classList.add('navbar__overlay--open');
    if (backdrop) backdrop.classList.add('navbar__backdrop--open');
    if (hamburger) hamburger.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }

  function closeDrawer() {
    if (overlay) overlay.classList.remove('navbar__overlay--open');
    if (backdrop) backdrop.classList.remove('navbar__backdrop--open');
    if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  if (hamburger) hamburger.addEventListener('click', openDrawer);
  if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
  if (backdrop) backdrop.addEventListener('click', closeDrawer);
  overlayLinks.forEach(link => link.addEventListener('click', closeDrawer));

  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && overlay && overlay.classList.contains('navbar__overlay--open')) {
      closeDrawer();
    }
  });
}
