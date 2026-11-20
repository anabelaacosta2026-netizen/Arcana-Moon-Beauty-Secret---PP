/**
 * ARCANA — Dashboard Submódulo: Empleados (js/pages/dashboard/empleados.js)
 */

import { state, paginations, renderAcciones, modalForceClose, triggerApplyFilters } from './state.js';
import { getEmpleados, addEmpleado, updateEmpleado, deleteEmpleado } from '../../data/db.js';
import { qs } from '../../utils/dom.js';
import { openModal, closeModal, showArcanaConfirm } from '../../modules/modal.js';
import { showToast } from '../../modules/toast.js';

let empleadoDropzone = null;

export function setEmpleadoDropzone(dz) {
  empleadoDropzone = dz;
}

export function renderEmpleadosTable(items) {
  const tbody = qs('#table-empleados tbody');
  if (!tbody) return;
  const fragment = document.createDocumentFragment();

  if (items.length === 0) {
    const tr = document.createElement('tr');
    tr.innerHTML = '<td colspan="10" style="text-align: center; color: var(--color-text-muted); padding: 2rem;">No se encontraron empleados registrados.</td>';
    tbody.replaceChildren(tr);
    return;
  }

  items.forEach(e => {
    const idVal = e.id_empleado || e.id;
    const isActivo = (e.activo === 1 || e.activo === true || e.activo === '1' || e.activo === undefined);
    const tr = document.createElement('tr');

    const tdId = document.createElement('td');
    tdId.textContent = String(idVal).padStart(2, '0');
    tr.appendChild(tdId);

    const tdFoto = document.createElement('td');
    const img = document.createElement('img');
    img.src = e.imagenEmpleado || e.foto || 'assets/images/team-avatar.jpg';
    img.alt = e.nombre;
    img.className = 'table-avatar';
    img.onerror = () => { img.src = 'assets/images/team-avatar.jpg'; };
    tdFoto.appendChild(img);
    tr.appendChild(tdFoto);

    const tdNom = document.createElement('td');
    tdNom.innerHTML = `<strong>${e.nombre}</strong>`;
    tr.appendChild(tdNom);

    const tdApe = document.createElement('td');
    tdApe.textContent = e.apellido;
    tr.appendChild(tdApe);

    const tdTel = document.createElement('td');
    tdTel.textContent = e.telefono || '-';
    tr.appendChild(tdTel);

    const tdEmail = document.createElement('td');
    tdEmail.textContent = e.email;
    tr.appendChild(tdEmail);

    const tdEsp = document.createElement('td');
    const espList = Array.isArray(e.especialidades) ? e.especialidades : (e.especialidades ? String(e.especialidades).split(',') : []);
    tdEsp.textContent = espList.join(', ') || 'General';
    tr.appendChild(tdEsp);

    const tdMod = document.createElement('td');
    tdMod.textContent = e.modalidad || 'Presencial';
    tr.appendChild(tdMod);

    const tdEst = document.createElement('td');
    const span = document.createElement('span');
    span.className = 'table-status table-status--' + (isActivo ? 'active' : 'inactive');
    span.textContent = isActivo ? 'Activo' : 'Inactivo';
    tdEst.appendChild(span);
    tr.appendChild(tdEst);

    const tdAct = document.createElement('td');
    tdAct.className = 'table-actions';
    tdAct.innerHTML = renderAcciones(idVal, isActivo, 'empleado');
    tr.appendChild(tdAct);

    fragment.appendChild(tr);
  });
  tbody.replaceChildren(fragment);
}

export function openEmpleadoModal(empleado = null) {
  const form = qs('#form-empleado');
  const title = qs('#modal-empleado-title');
  if (!form || !title) return;

  form.reset();
  const idVal = empleado ? (empleado.id_empleado || empleado.id) : '';
  qs('#empleado-id').value = idVal;
  title.textContent = empleado ? 'Editar Empleado' : 'Nuevo Empleado';

  if (empleado) {
    qs('#empleado-nombre').value = empleado.nombre || '';
    qs('#empleado-apellido').value = empleado.apellido || '';
    qs('#empleado-email').value = empleado.email || '';
    qs('#empleado-telefono').value = empleado.telefono || '';
    const foto = empleado.imagenEmpleado || empleado.foto || '';
    qs('#empleado-foto').value = foto;
    if (empleadoDropzone) empleadoDropzone.setImage(foto);
    qs('#empleado-modalidad').value = empleado.modalidad || 'Presencial';
    const espArr = Array.isArray(empleado.especialidades) ? empleado.especialidades : [];
    qs('#empleado-especialidades').value = espArr.join(', ');
    const isActivo = (empleado.activo === 1 || empleado.activo === true || empleado.activo === '1' || empleado.activo === undefined);
    if (qs('#empleado-estado')) qs('#empleado-estado').value = isActivo ? '1' : '0';
  } else {
    qs('#empleado-foto').value = '';
    if (empleadoDropzone) empleadoDropzone.reset();
    qs('#empleado-modalidad').value = 'Presencial';
    if (qs('#empleado-estado')) qs('#empleado-estado').value = '1';
  }

  openModal('#modal-empleado');
}

export async function refreshEmpleadosTable() {
  try {
    const empleados = await getEmpleados();
    state.data.empleados = empleados;
    triggerApplyFilters('empleados');
  } catch (e) {
    showToast('Error al actualizar empleados', 'error');
  }
}

export async function handleEmpleadoFormSubmit(e) {
  e.preventDefault();

  const id = qs('#empleado-id').value;
  const nombre = qs('#empleado-nombre').value.trim();
  const apellido = qs('#empleado-apellido').value.trim();
  const email = qs('#empleado-email').value.trim();
  const telefono = qs('#empleado-telefono').value.trim();
  const imagenEmpleado = qs('#empleado-foto').value.trim() || 'assets/images/team-avatar.jpg';
  const modalidad = qs('#empleado-modalidad').value;
  const activo = qs('#empleado-estado') ? Number(qs('#empleado-estado').value) : 1;
  const especialidades = qs('#empleado-especialidades').value
    .split(',')
    .map(esp => esp.trim())
    .filter(Boolean);

  if (!nombre || !apellido || !email) {
    showToast('Completa nombre, apellido y email del empleado', 'error');
    return;
  }

  const data = { nombre, apellido, email, telefono, imagenEmpleado, modalidad, activo, especialidades };

  try {
    if (id) {
      await updateEmpleado(Number(id), data);
      showToast('Empleado actualizado correctamente', 'success');
    } else {
      await addEmpleado(data);
      showToast('Empleado creado correctamente', 'success');
    }
  } catch (err) {
    showToast(err.message || 'Error al guardar el empleado', 'error');
    return;
  }

  try { closeModal('#modal-empleado'); } catch { modalForceClose('#modal-empleado'); }
  await refreshEmpleadosTable();
}

export async function handleEmpleadosTableClick(e) {
  const btn = e.target.closest('[data-action]');
  if (!btn) return;
  const id = Number(btn.dataset.id);
  const empleado = state.data.empleados.find(emp => (emp.id_empleado || emp.id) === id);
  if (!empleado) return;

  if (btn.dataset.action === 'editar') {
    openEmpleadoModal(empleado);
  } else if (btn.dataset.action === 'eliminar') {
    const confirmado = await showArcanaConfirm({
      title: '¿Desactivar Empleado?',
      message: `¿Desactivar al empleado "${empleado.nombre} ${empleado.apellido}" y pasarlo a estado inactivo?`,
      type: 'warning',
      confirmText: 'Sí, desactivar',
      cancelText: 'Cancelar'
    });
    if (!confirmado) return;
    try {
      await deleteEmpleado(id);
      await refreshEmpleadosTable();
      showToast('Empleado desactivado correctamente (inactivo)', 'success');
    } catch (err) {
      showToast(err.message || 'Error al desactivar el empleado', 'error');
    }
  } else if (btn.dataset.action === 'reactivar') {
    try {
      await updateEmpleado(id, { activo: 1 });
      await refreshEmpleadosTable();
      showToast('Empleado reactivado correctamente', 'success');
    } catch (err) {
      showToast(err.message || 'Error al reactivar el empleado', 'error');
    }
  }
}
