/**
 * ARCANA — Dashboard Submódulo: Turnos y Citas (js/pages/dashboard/turnos.js)
 */

import { state, paginations, renderAcciones, modalForceClose, triggerApplyFilters } from './state.js';
import { getTurnos, addTurno, updateTurno, deleteTurno } from '../../data/db.js';
import { qs, formatDateTime } from '../../utils/dom.js';
import { openModal, closeModal, showArcanaConfirm } from '../../modules/modal.js';
import { showToast } from '../../modules/toast.js';

export function renderTurnosTable(items) {
  const tbody = qs('#table-turnos tbody');
  if (!tbody) return;
  const fragment = document.createDocumentFragment();

  if (items.length === 0) {
    const tr = document.createElement('tr');
    tr.innerHTML = '<td colspan="10" style="text-align: center; color: var(--color-text-muted); padding: 2rem;">No se encontraron turnos registrados.</td>';
    tbody.replaceChildren(tr);
    return;
  }

  items.forEach(t => {
    const idVal = t.id_cita || t.id;
    const estado = (t.estado || 'pendiente').toLowerCase();
    const tr = document.createElement('tr');

    const tdId = document.createElement('td');
    tdId.textContent = String(idVal).padStart(2, '0');
    tr.appendChild(tdId);

    const tdCli = document.createElement('td');
    tdCli.innerHTML = `<strong>${t.cliente || 'Cliente'}</strong>`;
    tr.appendChild(tdCli);

    const tdEmp = document.createElement('td');
    tdEmp.textContent = t.empleado || '-';
    tr.appendChild(tdEmp);

    const tdSer = document.createElement('td');
    tdSer.textContent = t.servicio || '-';
    tr.appendChild(tdSer);

    const tdEsp = document.createElement('td');
    tdEsp.innerHTML = `<span class="badge" style="background: rgba(255,255,255,0.08);">${t.espacio || 'Cabina'}</span>`;
    tr.appendChild(tdEsp);

    const tdFec = document.createElement('td');
    tdFec.textContent = formatDateTime(t.fecha_hora || t.fechaHora);
    tr.appendChild(tdFec);

    const tdMod = document.createElement('td');
    tdMod.textContent = t.modalidad || 'Presencial';
    tr.appendChild(tdMod);

    const tdEst = document.createElement('td');
    const span = document.createElement('span');
    span.className = 'table-status table-status--' + estado;
    span.textContent = estado.charAt(0).toUpperCase() + estado.slice(1);
    tdEst.appendChild(span);
    tr.appendChild(tdEst);

    const tdObs = document.createElement('td');
    tdObs.textContent = t.observaciones || '-';
    tr.appendChild(tdObs);

    const tdAct = document.createElement('td');
    tdAct.className = 'table-actions';
    const isTurnoActivo = estado !== 'cancelado' && estado !== 'inactivo';
    tdAct.innerHTML = renderAcciones(idVal, isTurnoActivo, 'turno');
    tr.appendChild(tdAct);

    fragment.appendChild(tr);
  });
  tbody.replaceChildren(fragment);
}

export function openTurnoModal(turno = null) {
  const form = qs('#form-turno');
  const title = qs('#modal-turno-title');
  if (!form || !title) return;

  form.reset();
  const idVal = turno ? (turno.id_cita || turno.id) : '';
  qs('#turno-id').value = idVal;
  title.textContent = turno ? 'Editar Turno / Cita' : 'Nuevo Turno / Cita';

  if (turno) {
    qs('#turno-cliente').value = turno.id_usuario || '';
    qs('#turno-espacio').value = turno.id_espacio || '';
    qs('#turno-empleado').value = turno.id_empleado || '';
    qs('#turno-servicio').value = turno.id_servicio || '';
    if (turno.fecha_hora || turno.fechaHora) {
      const raw = turno.fecha_hora || turno.fechaHora;
      qs('#turno-fecha-hora').value = raw.replace(' ', 'T').substring(0, 16);
    }
    qs('#turno-modalidad').value = turno.modalidad || 'Presencial';
    qs('#turno-estado').value = (turno.estado || 'pendiente').toLowerCase();
    qs('#turno-observaciones').value = turno.observaciones || '';
  } else {
    qs('#turno-modalidad').value = 'Presencial';
    qs('#turno-estado').value = 'pendiente';
    qs('#turno-observaciones').value = '';
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    qs('#turno-fecha-hora').value = d.toISOString().slice(0, 16);
  }

  openModal('#modal-turno');
}

export async function refreshTurnosTable() {
  try {
    const turnos = await getTurnos();
    state.data.turnos = turnos;
    triggerApplyFilters('turnos');
  } catch (e) {
    showToast('Error al actualizar turnos', 'error');
  }
}

export async function handleTurnoFormSubmit(e) {
  e.preventDefault();

  const id = qs('#turno-id').value;
  const id_usuario = Number(qs('#turno-cliente').value);
  const id_espacio = Number(qs('#turno-espacio').value);
  const id_empleado = Number(qs('#turno-empleado').value);
  const id_servicio = Number(qs('#turno-servicio').value);
  const fecha_hora = qs('#turno-fecha-hora').value.replace('T', ' ') + ':00';
  const modalidad = qs('#turno-modalidad').value;
  const estado = qs('#turno-estado').value;
  const observaciones = qs('#turno-observaciones').value.trim();

  if (!id_usuario || !id_espacio || !id_servicio || !fecha_hora) {
    showToast('Completa los campos obligatorios del turno', 'error');
    return;
  }

  const data = { id_usuario, id_espacio, id_empleado, id_servicio, fecha_hora, modalidad, estado, observaciones };

  try {
    if (id) {
      await updateTurno(Number(id), data);
      showToast('Turno actualizado correctamente', 'success');
    } else {
      await addTurno(data);
      showToast('Turno creado correctamente', 'success');
    }
  } catch (err) {
    showToast(err.message || 'Error al guardar el turno', 'error');
    return;
  }

  try { closeModal('#modal-turno'); } catch { modalForceClose('#modal-turno'); }
  await refreshTurnosTable();
}

export async function handleTurnosTableClick(e) {
  const btn = e.target.closest('[data-action]');
  if (!btn) return;
  const id = Number(btn.dataset.id);
  const turno = state.data.turnos.find(t => (t.id_cita || t.id) === id);
  if (!turno) return;

  if (btn.dataset.action === 'editar') {
    openTurnoModal(turno);
  } else if (btn.dataset.action === 'eliminar') {
    const confirmado = await showArcanaConfirm({
      title: '¿Cancelar Turno?',
      message: `¿Estás seguro de cancelar el turno #${id} del cliente "${turno.cliente}"?`,
      type: 'warning',
      confirmText: 'Sí, cancelar turno',
      cancelText: 'Volver'
    });
    if (!confirmado) return;
    try {
      await deleteTurno(id);
      await refreshTurnosTable();
      showToast('Turno cancelado correctamente', 'success');
    } catch (err) {
      showToast(err.message || 'Error al cancelar el turno', 'error');
    }
  } else if (btn.dataset.action === 'reactivar') {
    try {
      await updateTurno(id, { estado: 'confirmado' });
      await refreshTurnosTable();
      showToast('Turno reactivado como confirmado', 'success');
    } catch (err) {
      showToast(err.message || 'Error al reactivar el turno', 'error');
    }
  }
}
