/**
 * ARCANA — Dashboard Submódulo: Servicios (js/pages/dashboard/servicios.js)
 */

import { state, paginations, renderAcciones, modalForceClose, triggerApplyFilters } from './state.js';
import { getServicios, addServicio, updateServicio, deleteServicio } from '../../data/db.js';
import { qs, formatPrice } from '../../utils/dom.js';
import { openModal, closeModal, showArcanaConfirm } from '../../modules/modal.js';
import { showToast } from '../../modules/toast.js';

let servicioDropzone = null;

export function setServicioDropzone(dz) {
  servicioDropzone = dz;
}

export function renderServiciosTable(items) {
  const tbody = qs('#table-servicios tbody');
  if (!tbody) return;
  const fragment = document.createDocumentFragment();

  if (items.length === 0) {
    const tr = document.createElement('tr');
    tr.innerHTML = '<td colspan="10" style="text-align: center; color: var(--color-text-muted); padding: 2rem;">No se encontraron servicios registrados.</td>';
    tbody.replaceChildren(tr);
    return;
  }

  items.forEach(s => {
    const idVal = s.id_servicio || s.id;
    const isDisponible = (s.disponibilidad === 1 || s.disponibilidad === true || s.disponible === true || s.disponible === 1);
    const tr = document.createElement('tr');

    const tdId = document.createElement('td');
    tdId.textContent = String(idVal).padStart(2, '0');
    tr.appendChild(tdId);

    const tdImg = document.createElement('td');
    const img = document.createElement('img');
    img.src = s.imagen_servicio || s.imagen || 'assets/images/service-manicure.jpg';
    img.alt = s.nombre_servicio || s.nombre;
    img.className = 'table-thumb';
    img.onerror = () => { img.src = 'assets/images/service-manicure.jpg'; };
    tdImg.appendChild(img);
    tr.appendChild(tdImg);

    const tdNom = document.createElement('td');
    tdNom.innerHTML = `<strong>${s.nombre_servicio || s.nombre}</strong>`;
    tr.appendChild(tdNom);

    const tdDesc = document.createElement('td');
    const descText = s.descripcion || '';
    tdDesc.textContent = descText.length > 35 ? descText.substring(0, 35) + '...' : descText;
    tdDesc.title = descText;
    tr.appendChild(tdDesc);

    const tdCat = document.createElement('td');
    tdCat.innerHTML = `<span class="badge" style="background: rgba(229,178,153,0.15); color: var(--color-accent);">${s.categoria || 'General'}</span>`;
    tr.appendChild(tdCat);

    const tdDur = document.createElement('td');
    tdDur.textContent = (s.duracion_minutos || s.duracion || 60) + ' min';
    tr.appendChild(tdDur);

    const tdPre = document.createElement('td');
    tdPre.innerHTML = `<strong>${formatPrice(s.precio || 0)}</strong>`;
    tr.appendChild(tdPre);

    const tdEst = document.createElement('td');
    const span = document.createElement('span');
    span.className = 'table-status table-status--' + (isDisponible ? 'active' : 'inactive');
    span.textContent = isDisponible ? 'Activo' : 'Inactivo';
    tdEst.appendChild(span);
    tr.appendChild(tdEst);

    const tdEmp = document.createElement('td');
    tdEmp.textContent = s.especialista || 'Todo el equipo';
    tr.appendChild(tdEmp);

    const tdMod = document.createElement('td');
    tdMod.textContent = s.modalidad_disponibl || 'Presencial';
    tr.appendChild(tdMod);

    const tdAct = document.createElement('td');
    tdAct.className = 'table-actions';
    tdAct.innerHTML = renderAcciones(idVal, isDisponible, 'servicio');
    tr.appendChild(tdAct);

    fragment.appendChild(tr);
  });
  tbody.replaceChildren(fragment);
}

export function openServicioModal(servicio = null) {
  const form = qs('#form-servicio');
  const title = qs('#modal-servicio-title');
  if (!form || !title) return;

  form.reset();
  const idVal = servicio ? (servicio.id_servicio || servicio.id) : '';
  qs('#servicio-id').value = idVal;
  title.textContent = servicio ? 'Editar Servicio' : 'Nuevo Servicio';

  if (servicio) {
    qs('#servicio-nombre').value = servicio.nombre_servicio || servicio.nombre || '';
    qs('#servicio-categoria').value = servicio.categoria || '';
    qs('#servicio-descripcion').value = servicio.descripcion || '';
    qs('#servicio-duracion').value = servicio.duracion_minutos || servicio.duracion || 60;
    qs('#servicio-precio').value = servicio.precio || 0;
    qs('#servicio-cuidados-previos').value = servicio.cuidados_previos || '';
    qs('#servicio-cuidados-posteriores').value = servicio.cuidados_posteriores || '';
    const imgPath = servicio.imagen_servicio || servicio.imagen || '';
    qs('#servicio-imagen').value = imgPath;
    if (servicioDropzone) servicioDropzone.setImage(imgPath);
    const isDisp = (servicio.disponibilidad === 1 || servicio.disponibilidad === true || servicio.disponible === 1 || servicio.disponible === true);
    qs('#servicio-disponible').value = isDisp ? '1' : '0';
  } else {
    qs('#servicio-duracion').value = '60';
    qs('#servicio-precio').value = '';
    qs('#servicio-disponible').value = '1';
    qs('#servicio-imagen').value = '';
    qs('#servicio-cuidados-previos').value = '';
    qs('#servicio-cuidados-posteriores').value = '';
    if (servicioDropzone) servicioDropzone.reset();
  }

  openModal('#modal-servicio');
}

export async function refreshServiciosTable() {
  try {
    const servicios = await getServicios();
    state.data.servicios = servicios;
    triggerApplyFilters('servicios');
  } catch (e) {
    showToast('Error al actualizar servicios', 'error');
  }
}

export async function handleServicioFormSubmit(e) {
  e.preventDefault();

  const id = qs('#servicio-id').value;
  const nombre_servicio = qs('#servicio-nombre').value.trim();
  const categoria = qs('#servicio-categoria').value.trim();
  const descripcion = qs('#servicio-descripcion').value.trim();
  const duracion_minutos = Number(qs('#servicio-duracion').value) || 60;
  const precio = Number(qs('#servicio-precio').value) || 0;
  const imagen_servicio = qs('#servicio-imagen').value.trim() || 'assets/images/service-manicure.jpg';
  const cuidados_previos = qs('#servicio-cuidados-previos')?.value.trim() || '';
  const cuidados_posteriores = qs('#servicio-cuidados-posteriores')?.value.trim() || '';
  const disponibilidad = qs('#servicio-disponible').value === '1' ? 1 : 0;

  if (!nombre_servicio || !categoria) {
    showToast('Completa el nombre y la categoría del servicio', 'error');
    return;
  }

  const data = {
    nombre_servicio,
    categoria,
    descripcion,
    duracion_minutos,
    precio,
    imagen_servicio,
    cuidados_previos,
    cuidados_posteriores,
    disponibilidad
  };

  try {
    if (id) {
      await updateServicio(Number(id), data);
      showToast('Servicio actualizado correctamente', 'success');
    } else {
      await addServicio(data);
      showToast('Servicio creado correctamente', 'success');
    }
  } catch (err) {
    showToast(err.message || 'Error al guardar el servicio', 'error');
    return;
  }

  try { closeModal('#modal-servicio'); } catch { modalForceClose('#modal-servicio'); }
  await refreshServiciosTable();
}

export async function handleServiciosTableClick(e) {
  const btn = e.target.closest('[data-action]');
  if (!btn) return;
  const id = Number(btn.dataset.id);
  const servicio = state.data.servicios.find(s => (s.id_servicio || s.id) === id);
  if (!servicio) return;

  if (btn.dataset.action === 'editar') {
    openServicioModal(servicio);
  } else if (btn.dataset.action === 'eliminar') {
    const nom = servicio.nombre_servicio || servicio.nombre;
    const confirmado = await showArcanaConfirm({
      title: '¿Desactivar Servicio?',
      message: `¿Desactivar el servicio "${nom}" y pasarlo a estado inactivo? (Los turnos y relaciones no se perderán)`,
      type: 'warning',
      confirmText: 'Sí, desactivar',
      cancelText: 'Cancelar'
    });
    if (!confirmado) return;
    try {
      await deleteServicio(id);
      await refreshServiciosTable();
      showToast('Servicio desactivado correctamente (inactivo)', 'success');
    } catch (err) {
      showToast(err.message || 'Error al desactivar el servicio', 'error');
    }
  } else if (btn.dataset.action === 'reactivar') {
    try {
      await updateServicio(id, { disponibilidad: 1, disponible: 1 });
      await refreshServiciosTable();
      showToast('Servicio reactivado correctamente', 'success');
    } catch (err) {
      showToast(err.message || 'Error al reactivar el servicio', 'error');
    }
  }
}
