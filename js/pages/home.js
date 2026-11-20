import { getServicios } from '../data/db.js';
import { qs, formatPrice } from '../utils/dom.js';

/* ---- Hero Text Cycling Animation ---- */
function initHeroPhrases() {
  const phrases = document.querySelectorAll('.hero__phrase');
  if (!phrases.length) return;

  let current = 0;

  function showPhrase(index) {
    phrases.forEach((p, i) => {
      const isActive = i === index;
      p.classList.toggle('hero__phrase--active', isActive);
      p.style.opacity = isActive ? '1' : '0';
      p.style.visibility = isActive ? 'visible' : 'hidden';
      p.style.transform = isActive ? 'translateY(0)' : 'translateY(6px)';
    });
  }

  function nextPhrase() {
    showPhrase(current);
    current = (current + 1) % phrases.length;
  }

  nextPhrase();
  setInterval(nextPhrase, 4000);
}

/* ---- Render Featured Services (3) ---- */
async function renderFeaturedServices() {
  const grid = qs('.featured__grid');
  if (!grid) return;

  const template = qs('#service-card-template');
  if (!template) return;

  try {
    const allServicios = await getServicios();
    const featured = allServicios.filter(s => s.disponible).slice(0, 3);

    const fragment = document.createDocumentFragment();
    featured.forEach(servicio => {
      fragment.appendChild(renderServiceCard(servicio, template));
    });
    grid.replaceChildren(fragment);
  } catch (err) {
    console.error('Error loading services:', err);
    grid.innerHTML = '<p style="text-align:center;color:var(--color-text-muted)">No se pudieron cargar los servicios.</p>';
  }
}

/**
 * Render a single service card using template + cloneNode
 * @param {Object} servicio
 * @param {HTMLTemplateElement} template
 * @returns {DocumentFragment}
 */
function renderServiceCard(servicio, template) {
  const clone = template.content.cloneNode(true);
  const id = servicio.id || servicio.id_servicio;
  const detailUrl = `servicio.php?id=${id}`;

  const titleLink = clone.querySelector('.service-card__title-link');
  if (titleLink) {
    titleLink.textContent = servicio.nombre;
    titleLink.href = detailUrl;
  } else {
    clone.querySelector('.service-card__name').textContent = servicio.nombre;
  }

  const imgWrap = clone.querySelector('.service-card__link-wrap');
  if (imgWrap) imgWrap.href = detailUrl;

  const btnDetail = clone.querySelector('.service-card__btn');
  if (btnDetail) btnDetail.href = detailUrl;

  clone.querySelector('.service-card__description').textContent = servicio.descripcion;
  clone.querySelector('.specialist-name').textContent = servicio.especialista;
  clone.querySelector('.service-card__price').textContent = formatPrice(servicio.precio);
  clone.querySelector('.service-card__duration').textContent = servicio.duracion + ' min';

  const img = clone.querySelector('.service-card__image');
  img.src = servicio.imagen;
  img.alt = servicio.nombre;

  return clone;
}

/* ---- Initialize ---- */
export function initHome() {
  initHeroPhrases();
  renderFeaturedServices();
}
