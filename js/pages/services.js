import { getServicios, categorias } from '../data/db.js';
import { qs, debounce, formatPrice } from '../utils/dom.js';
import { Pagination } from '../modules/pagination.js';

let allServicios = [];
let pagination = null;

/* ---- Render single card ---- */
function renderServiceCard(servicio) {
  const template = qs('#service-card-template');
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
  clone.querySelector('.modalidad-name').textContent = servicio.modalidad || 'presencial';
  clone.querySelector('.service-card__price').textContent = formatPrice(servicio.precio);
  clone.querySelector('.service-card__duration').textContent = servicio.duracion + ' min';
  const img = clone.querySelector('.service-card__image');
  img.src = servicio.imagen;
  img.alt = servicio.nombre;
  return clone;
}

/* ---- Render grid ---- */
function renderGrid(servicios) {
  const grid = qs('.services-grid');
  if (!grid) return;
  const fragment = document.createDocumentFragment();
  servicios.forEach(s => fragment.appendChild(renderServiceCard(s)));
  grid.replaceChildren(fragment);
}

/* ---- Apply filters ---- */
function applyFilters() {
  const searchVal = (qs('#search-input')?.value || '').toLowerCase();
  const checkedCats = Array.from(document.querySelectorAll('.cat-checkbox:checked')).map(cb => cb.value);
  const maxPrice = parseInt(qs('#price-range')?.value || '100000', 10);

  let filtered = allServicios.filter(s => {
    const matchSearch = !searchVal || s.nombre.toLowerCase().includes(searchVal) || s.descripcion.toLowerCase().includes(searchVal);
    const matchCat = !checkedCats.length || checkedCats.includes(s.categoria);
    const matchPrice = s.precio <= maxPrice;
    return matchSearch && matchCat && matchPrice;
  });

  if (pagination) {
    pagination.updateItems(filtered);
  } else {
    renderGrid(filtered);
  }
}

/* ---- Populate categories ---- */
function populateCategories() {
  const container = qs('.services-sidebar__categories');
  if (!container) return;

  categorias.forEach(cat => {
    const label = document.createElement('label');
    label.className = 'form-checkbox';

    const cb = document.createElement('input');
    cb.type = 'checkbox';
    cb.className = 'cat-checkbox';
    cb.value = cat;
    cb.addEventListener('change', applyFilters);

    const span = document.createElement('span');
    span.className = 'form-checkbox__label';
    span.textContent = cat;

    label.appendChild(cb);
    label.appendChild(span);
    container.appendChild(label);
  });
}

/* ---- Initialize ---- */
export async function initServices() {
  const grid = qs('.services-grid');
  if (!grid) return;

  // Skeleton loading
  grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--color-text-muted)">Cargando servicios...</div>';

  try {
    const rawServicios = await getServicios();
    allServicios = rawServicios.filter(s => (s.disponibilidad === 1 || s.disponibilidad === true || s.disponible === true || s.disponible === 1 || s.disponibilidad === undefined));

    // Hide skeleton
    populateCategories();

    pagination = new Pagination({
      items: allServicios,
      itemsPerPage: 6,
      renderFn: renderGrid,
      containerSelector: '.services-page__content'
    });
    pagination.render();

    // Event listeners
    const searchInput = qs('#search-input');
    if (searchInput) searchInput.addEventListener('input', debounce(applyFilters, 300));

    const priceRange = qs('#price-range');
    if (priceRange) {
      priceRange.addEventListener('input', () => {
        const valDisplay = qs('#price-value');
        if (valDisplay) valDisplay.textContent = formatPrice(parseInt(priceRange.value, 10));
        applyFilters();
      });
    }

    // Mobile sidebar toggle
    const sidebarToggle = qs('.sidebar-toggle');
    const sidebar = qs('.services-sidebar');
    if (sidebarToggle && sidebar) {
      sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('services-sidebar--open'));
    }

  } catch (err) {
    console.error(err);
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--color-status-inactive)">Error al cargar servicios. Intente nuevamente.</div>';
  }
}
