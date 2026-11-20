import { initNavbar } from './modules/navbar.js';
import { initOrbs } from './modules/orbs.js';
import { initReveal } from './modules/reveal.js';
import { initModals } from './modules/modal.js';
import { initHome } from './pages/home.js';
import { initServices } from './pages/services.js';
import { initDashboard } from './pages/dashboard.js';
import { initLogin } from './pages/login.js';
import { initRegister } from './pages/register.js';
import { initContact } from './pages/contact.js';
import { initAgenda } from './pages/agenda.js';

// Initialize shared modules
document.addEventListener('DOMContentLoaded', () => {
  initNavbar();
  initOrbs();
  initReveal();
  initModals();

  // Initialize page-specific modules based on body data attribute
  const page = document.body.dataset.page;
  switch (page) {
    case 'home':
      initHome();
      break;
    case 'services':
      initServices();
      break;
    case 'dashboard':
      initDashboard();
      break;
    case 'login':
      initLogin();
      break;
    case 'register':
      initRegister();
      break;
    case 'contact':
      initContact();
      break;
    case 'agenda':
      initAgenda();
      break;
  }
});
