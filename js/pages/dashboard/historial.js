/**
 * ARCANA — Dashboard Submódulo: Historial y Auditoría (js/pages/dashboard/historial.js)
 */

import { getHistorialCambios, vaciarHistorialCambios } from '../../data/db.js';
import { qs } from '../../utils/dom.js';
import { showToast } from '../../modules/toast.js';

export function renderHistorialTable(items) {
  const tbody = qs('#table-historial tbody');
  if (!tbody) return;

  if (!items || items.length === 0) {
    const tr = document.createElement('tr');
    tr.innerHTML = '<td colspan="5" style="text-align: center; color: var(--color-text-muted); padding: 2.5rem 1rem;">No se encontraron registros de cambios que coincidan con la búsqueda.</td>';
    tbody.replaceChildren(tr);
    return;
  }

  const fragment = document.createDocumentFragment();
  items.forEach(item => {
    const tr = document.createElement('tr');

    // 1. Fecha / Hora
    const tdFecha = document.createElement('td');
    tdFecha.style.whiteSpace = 'nowrap';
    tdFecha.innerHTML = `<span style="color: var(--color-text-primary); font-size: 0.85rem; font-family: monospace;">${item.fecha || '-'}</span>`;
    tr.appendChild(tdFecha);

    // 2. Administrador
    const tdUser = document.createElement('td');
    const isSuper = (item.rol === 'superadmin' || item.rol === 'super_admin');
    const rolBadge = isSuper
      ? '<span class="badge" style="background: rgba(168, 85, 247, 0.2); color: #c084fc; font-size: 0.65rem; padding: 0.15rem 0.4rem; margin-left: 0.35rem;">SUPER</span>'
      : '<span class="badge" style="background: rgba(229,178,153,0.15); color: var(--color-accent); font-size: 0.65rem; padding: 0.15rem 0.4rem; margin-left: 0.35rem;">ADMIN</span>';
    tdUser.innerHTML = `<div><strong>${item.usuario || 'Admin'}</strong>${rolBadge}</div>` + (item.email ? `<small style="color: var(--color-text-muted); font-size: 0.75rem; display: block;">${item.email}</small>` : '');
    tr.appendChild(tdUser);

    // 3. Módulo
    const tdMod = document.createElement('td');
    tdMod.innerHTML = `<span class="badge" style="background: rgba(255, 255, 255, 0.08); border: 1px solid var(--color-glass-border); color: #fff; font-size: 0.8rem;">${item.modulo || 'General'}</span>`;
    tr.appendChild(tdMod);

    // 4. Cambio Realizado (Acción / Operación)
    const tdAccion = document.createElement('td');
    tdAccion.innerHTML = `<strong style="color: var(--color-accent); font-size: 0.875rem;">${item.accion || item.cambio || 'Modificación'}</strong>`;
    tr.appendChild(tdAccion);

    // 5. Detalle (Descripción exacta del cambio realizado)
    const tdDetalle = document.createElement('td');
    tdDetalle.style.lineHeight = '1.45';
    tdDetalle.innerHTML = `<span style="color: var(--color-text-primary); font-size: 0.875rem;">${item.detalle || '-'}</span>`;
    tr.appendChild(tdDetalle);

    fragment.appendChild(tr);
  });

  tbody.replaceChildren(fragment);
}

export async function loadHistorialCambios() {
  const tbody = qs('#table-historial tbody');
  if (tbody) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--color-text-muted); padding: 2rem;">Cargando historial de cambios...</td></tr>';
  }
  const modulo = qs('#filter-modulo-historial')?.value || '';
  const q = qs('#search-historial')?.value || '';

  try {
    const list = await getHistorialCambios(modulo, q);
    renderHistorialTable(list);
  } catch (err) {
    console.error('Error cargando historial de cambios:', err);
    if (tbody) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #f87171; padding: 2rem;">Error al cargar el historial de cambios.</td></tr>';
    }
  }
}
