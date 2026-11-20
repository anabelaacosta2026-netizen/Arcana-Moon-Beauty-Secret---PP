/**
 * ARCANA — Dashboard Submódulo: Bloqueos de Días (js/pages/dashboard/bloqueos.js)
 */

import { qs, formatDate } from '../../utils/dom.js';
import { openModal, closeModal, showArcanaConfirm } from '../../modules/modal.js';
import { showToast } from '../../modules/toast.js';
import { modalForceClose } from './state.js';

let bloqueosData = [];

export async function fetchBloqueos() {
  const resp = await fetch('api/agenda.php?action=listar_bloqueos');
  const json = await resp.json();
  if (json.success) {
    bloqueosData = json.data;
    renderBloqueosTable(bloqueosData);
  }
}

export function renderBloqueosTable(items) {
  const tbody = qs('#table-bloqueos tbody');
  if (!tbody) return;
  const fragment = document.createDocumentFragment();

  if (items.length === 0) {
    const tr = document.createElement('tr');
    tr.innerHTML = '<td colspan="6" style="text-align: center; color: var(--color-text-muted); padding: 2rem;">No hay bloqueos de fechas registrados.</td>';
    tbody.replaceChildren(tr);
    return;
  }

  items.forEach(b => {
    const idVal = b.id_bloqueo_dia || b.id;
    const tr = document.createElement('tr');

    const tdId = document.createElement('td');
    tdId.textContent = String(idVal).padStart(2, '0');
    tr.appendChild(tdId);

    const tdEmp = document.createElement('td');
    tdEmp.innerHTML = `<span class="badge" style="background: rgba(229,178,153,0.15); color: var(--color-accent);">${b.empleado || 'Todo el Salón'}</span>`;
    tr.appendChild(tdEmp);

    const tdDesde = document.createElement('td');
    tdDesde.textContent = formatDate(b.fecha_desde);
    tr.appendChild(tdDesde);

    const tdHasta = document.createElement('td');
    tdHasta.textContent = formatDate(b.fecha_hasta);
    tr.appendChild(tdHasta);

    const tdMot = document.createElement('td');
    tdMot.textContent = b.motivo || 'Día no disponible';
    tr.appendChild(tdMot);

    const tdAct = document.createElement('td');
    tdAct.className = 'table-actions';
    tdAct.innerHTML = `<button class="table-action-btn" title="Eliminar Bloqueo" data-action="eliminar-bloqueo" data-id="${idVal}" style="color: #ff6b6b;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg></button>`;
    tr.appendChild(tdAct);

    fragment.appendChild(tr);
  });
  tbody.replaceChildren(fragment);
}

export function openBloqueoModal() {
  const form = qs('#form-bloqueo');
  if (form) form.reset();
  openModal('#modal-bloqueo');
}

export async function handleBloqueoFormSubmit(e) {
  e.preventDefault();
  const fecha_desde = qs('#bloqueo-desde').value;
  const fecha_hasta = qs('#bloqueo-hasta').value || fecha_desde;
  const id_empleado = Number(qs('#bloqueo-empleado').value) || 0;
  const motivo = qs('#bloqueo-motivo').value.trim() || 'Día no disponible';

  if (!fecha_desde) {
    showToast('Selecciona la fecha de inicio del bloqueo', 'error');
    return;
  }

  try {
    const resp = await fetch('api/agenda.php?action=crear_bloqueo', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ fecha_desde, fecha_hasta, id_empleado, motivo })
    });
    const json = await resp.json();
    if (!json.success) throw new Error(json.message);

    showToast('Bloqueo guardado correctamente', 'success');
    try { closeModal('#modal-bloqueo'); } catch { modalForceClose('#modal-bloqueo'); }
    await fetchBloqueos();
  } catch (err) {
    showToast(err.message || 'Error al guardar el bloqueo', 'error');
  }
}

export async function handleBloqueosTableClick(e) {
  const btn = e.target.closest('[data-action="eliminar-bloqueo"]');
  if (!btn) return;
  const id = Number(btn.dataset.id);

  const confirmado = await showArcanaConfirm({
    title: '¿Eliminar Bloqueo?',
    message: `¿Estás seguro de eliminar este bloqueo de fecha (#${id})?`,
    type: 'warning',
    confirmText: 'Sí, eliminar',
    cancelText: 'Cancelar'
  });
  if (!confirmado) return;

  try {
    const resp = await fetch('api/agenda.php?action=eliminar_bloqueo', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_bloqueo_dia: id })
    });
    const json = await resp.json();
    if (!json.success) throw new Error(json.message);

    showToast('Bloqueo eliminado correctamente', 'success');
    await fetchBloqueos();
  } catch (err) {
    showToast(err.message || 'Error al eliminar bloqueo', 'error');
  }
}
