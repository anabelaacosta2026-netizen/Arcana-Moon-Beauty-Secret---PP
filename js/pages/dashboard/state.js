/**
 * ARCANA — Dashboard State & Shared Helpers
 */

export const state = {
  activeTab: 'servicios',
  data: { servicios: [], empleados: [], turnos: [], usuarios: [], cuentas: [] },
  lookups: { roles: [], categorias: [], especialidades: [], espacios: [], empleados: [], servicios: [], usuarios: [] }
};

export const paginations = {};

export const ACCIONES_ICONOS = {
  editar: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>',
  eliminar: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>',
  reactivar: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>'
};

export function renderAcciones(id, isActivo = true, tipo = '') {
  const btnEditar = `<button class="table-action-btn" title="Editar" data-action="editar" data-id="${id}">${ACCIONES_ICONOS.editar}</button>`;
  if (isActivo) {
    const btnDesactivar = `<button class="table-action-btn" title="Desactivar (pasar a inactivo)" data-action="eliminar" data-id="${id}" style="color: #ff6b6b;">${ACCIONES_ICONOS.eliminar}</button>`;
    return btnEditar + btnDesactivar;
  } else {
    const btnReactivar = `<button class="table-action-btn" title="Reactivar registro" data-action="reactivar" data-id="${id}" style="color: #2ed573;">${ACCIONES_ICONOS.reactivar}</button>`;
    return btnEditar + btnReactivar;
  }
}

export let applyFiltersHandler = null;

export function setApplyFiltersHandler(fn) {
  applyFiltersHandler = fn;
}

export function triggerApplyFilters(tab) {
  if (typeof applyFiltersHandler === 'function') {
    applyFiltersHandler(tab);
  } else if (paginations[tab] && state.data[tab]) {
    paginations[tab].updateItems(state.data[tab]);
  }
}

export function modalForceClose(selector) {
  const overlay = document.querySelector(selector);
  if (overlay) overlay.classList.remove('modal-overlay--open');
  document.body.style.overflow = '';
}
