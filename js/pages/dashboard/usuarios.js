/**
 * ARCANA — Dashboard Submódulo: Usuarios (js/pages/dashboard/usuarios.js)
 */

import { state, paginations, renderAcciones, modalForceClose, triggerApplyFilters } from './state.js';
import { getUsuarios, addUsuario, updateUsuario, deleteUsuario, getHistorialUsuario } from '../../data/db.js';
import { qs, formatDate } from '../../utils/dom.js';
import { openModal, closeModal, showArcanaConfirm } from '../../modules/modal.js';
import { showToast } from '../../modules/toast.js';

export function renderUsuariosTable(items) {
  const tbody = qs('#table-usuarios tbody');
  if (!tbody) return;
  const fragment = document.createDocumentFragment();

  if (items.length === 0) {
    const tr = document.createElement('tr');
    tr.innerHTML = '<td colspan="10" style="text-align: center; color: var(--color-text-muted); padding: 2rem;">No se encontraron usuarios registrados.</td>';
    tbody.replaceChildren(tr);
    return;
  }

  items.forEach(u => {
    const idVal = u.id_usuario || u.id;
    const isActivo = (u.activo === 1 || u.activo === true || u.estado === 'activo');

    const tr = document.createElement('tr');

    const tdId = document.createElement('td');
    tdId.textContent = String(idVal).padStart(2, '0');
    tr.appendChild(tdId);

    const tdRol = document.createElement('td');
    const spanRol = document.createElement('span');
    spanRol.style.fontWeight = '600';
    spanRol.style.fontSize = '0.78rem';
    spanRol.style.display = 'inline-block';
    spanRol.style.padding = '0.2rem 0.6rem';
    spanRol.style.borderRadius = '12px';

    const rLower = (u.rol || '').toLowerCase();
    if (u.is_superadmin || (u.id_rol === 1) || rLower.includes('super')) {
      spanRol.style.color = '#c084fc';
      spanRol.style.background = 'rgba(192, 132, 252, 0.18)';
      spanRol.style.border = '1px solid rgba(192, 132, 252, 0.35)';
      spanRol.textContent = '👑 Super Admin';
    } else if (u.id_rol === 2 || rLower.includes('admin')) {
      spanRol.style.color = '#60a5fa';
      spanRol.style.background = 'rgba(96, 165, 250, 0.18)';
      spanRol.style.border = '1px solid rgba(96, 165, 250, 0.35)';
      spanRol.textContent = '🛡️ Administrador';
    } else {
      spanRol.style.color = '#cbd5e1';
      spanRol.style.background = 'rgba(255, 255, 255, 0.06)';
      spanRol.style.border = '1px solid var(--color-glass-border)';
      spanRol.textContent = '🌸 Cliente';
    }

    tdRol.appendChild(spanRol);
    tr.appendChild(tdRol);

    const tdNom = document.createElement('td');
    tdNom.innerHTML = `<strong>${u.nombre}</strong>`;
    tr.appendChild(tdNom);

    const tdApe = document.createElement('td');
    tdApe.textContent = u.apellido;
    tr.appendChild(tdApe);

    const tdDni = document.createElement('td');
    tdDni.textContent = u.DNI || u.dni || '-';
    tr.appendChild(tdDni);

    const tdEmail = document.createElement('td');
    tdEmail.textContent = u.email;
    tr.appendChild(tdEmail);

    const tdTel = document.createElement('td');
    tdTel.textContent = u.telefono || '-';
    tr.appendChild(tdTel);

    const tdReg = document.createElement('td');
    tdReg.textContent = formatDate(u.fecha_registro || u.fechaRegistro);
    tr.appendChild(tdReg);

    const tdEst = document.createElement('td');
    const span = document.createElement('span');
    span.className = 'table-status table-status--' + (isActivo ? 'active' : 'inactive');
    span.textContent = isActivo ? 'Activo' : 'Inactivo';
    tdEst.appendChild(span);
    tr.appendChild(tdEst);

    const tdAct = document.createElement('td');
    tdAct.className = 'table-actions';
    tdAct.innerHTML = `<button class="btn-pill btn-pill--sm" data-action="historial-usuario" data-id="${idVal}" style="padding: 0.2rem 0.6rem; font-size: 0.75rem; background: rgba(196,84,126,0.15); color: var(--color-accent); border: 1px solid rgba(196,84,126,0.3); margin-right: 0.35rem; cursor: pointer;" title="Ver historial de turnos y fotos de sesiones">📷 Historial</button>` + renderAcciones(idVal, isActivo, 'usuario');
    tr.appendChild(tdAct);

    fragment.appendChild(tr);
  });
  tbody.replaceChildren(fragment);
}

export function openUsuarioModal(usuario = null) {
  const form = qs('#form-usuario');
  const title = qs('#modal-usuario-title');
  if (!form || !title) return;

  form.reset();
  const idVal = usuario ? (usuario.id_usuario || usuario.id) : '';
  qs('#usuario-id').value = idVal;
  title.textContent = usuario ? 'Editar Usuario' : 'Nuevo Usuario';

  if (usuario) {
    qs('#usuario-nombre').value = usuario.nombre || '';
    qs('#usuario-apellido').value = usuario.apellido || '';
    qs('#usuario-email').value = usuario.email || '';
    qs('#usuario-telefono').value = usuario.telefono || '';
    qs('#usuario-dni').value = usuario.DNI || usuario.dni || '';
    qs('#usuario-rol').value = usuario.id_rol || (usuario.rol === 'admin' ? '1' : '2');
    const isActivo = (usuario.activo === 1 || usuario.activo === true || usuario.estado === 'activo');
    qs('#usuario-estado').value = isActivo ? '1' : '0';
  } else {
    qs('#usuario-rol').value = '2';
    qs('#usuario-estado').value = '1';
  }

  openModal('#modal-usuario');
}

export async function refreshUsuariosTable() {
  try {
    const usuarios = await getUsuarios();
    state.data.usuarios = usuarios;
    triggerApplyFilters('usuarios');
  } catch (e) {
    showToast('Error al actualizar usuarios', 'error');
  }
}

export async function handleUsuarioFormSubmit(e) {
  e.preventDefault();

  const id = qs('#usuario-id').value;
  const nombre = qs('#usuario-nombre').value.trim();
  const apellido = qs('#usuario-apellido').value.trim();
  const email = qs('#usuario-email').value.trim();
  const telefono = qs('#usuario-telefono').value.trim();
  const DNI = qs('#usuario-dni').value.trim();
  const id_rol = Number(qs('#usuario-rol').value);
  const activo = Number(qs('#usuario-estado').value);
  const password = qs('#usuario-password').value;

  if (!nombre || !apellido || !email || !DNI) {
    showToast('Completa todos los campos obligatorios del usuario', 'error');
    return;
  }

  const data = { nombre, apellido, email, telefono, DNI, id_rol, activo };
  if (password) data.password = password;

  try {
    if (id) {
      await updateUsuario(Number(id), data);
      showToast('Usuario actualizado correctamente', 'success');
    } else {
      await addUsuario(data);
      showToast('Usuario creado correctamente', 'success');
    }
  } catch (err) {
    showToast(err.message || 'Error al guardar el usuario', 'error');
    return;
  }

  try { closeModal('#modal-usuario'); } catch { modalForceClose('#modal-usuario'); }
  await refreshUsuariosTable();
}

export async function openHistorialUsuarioModal(idUsuario) {
  const container = qs('#historial-modal-content');
  if (!container) return;

  container.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--color-text-muted);">Cargando historial del cliente...</div>';
  openModal('#modal-historial-usuario');

  try {
    const data = await getHistorialUsuario(idUsuario);
    const u = data.usuario || {};
    const citas = data.citas || [];

    let html = `
      <div style="border-bottom: 1px solid var(--color-glass-border); padding-bottom: 1rem; margin-bottom: 1.5rem;">
        <h3 style="margin: 0 0 0.35rem 0; font-size: 1.25rem; color: #fff;">Historial de Cliente: <span style="color: var(--color-accent);">${u.nombre || 'Cliente'}</span></h3>
        <p style="margin: 0; font-size: 0.85rem; color: var(--color-text-muted);">
          Email: <code>${u.email || '-'}</code> | DNI: <code>${u.dni || '-'}</code> | Tel: <code>${u.telefono || '-'}</code> | Total Turnos: <strong>${data.total_turnos || 0}</strong>
        </p>
      </div>
    `;

    if (citas.length === 0) {
      html += '<p style="text-align: center; color: var(--color-text-muted); padding: 2rem;">Este cliente aún no tiene citas registradas.</p>';
    } else {
      html += '<div style="display: flex; flex-direction: column; gap: 1rem;">';
      citas.forEach(c => {
        const estado = (c.estado || 'pendiente').toLowerCase();
        html += `
          <div class="glass" style="padding: 1rem; border-radius: 12px; border-left: 4px solid var(--color-accent);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
              <strong style="color: #fff; font-size: 1rem;">#${c.id_cita} · ${c.servicio || 'Servicio'}</strong>
              <span class="table-status table-status--${estado}">${estado.toUpperCase()}</span>
            </div>
            <p style="margin: 0.25rem 0; font-size: 0.85rem; color: var(--color-text-muted);">
              📅 Fecha: <strong>${c.fecha_hora || '-'}</strong> | 👩‍💼 Especialista: <strong>${c.empleado || '-'}</strong> | 📍 ${c.espacio || 'Cabina'} (${c.modalidad || 'Presencial'})
            </p>
            ${c.observaciones ? `<p style="margin: 0.25rem 0; font-size: 0.8rem; color: var(--color-text-primary);"><em>Nota: ${c.observaciones}</em></p>` : ''}
          </div>
        `;
      });
      html += '</div>';
    }

    container.innerHTML = html;
  } catch (err) {
    container.innerHTML = `<div style="text-align: center; padding: 2rem; color: #ff6b6b;">Error al cargar historial: ${err.message}</div>`;
  }
}

export async function handleUsuariosTableClick(e) {
  const btn = e.target.closest('[data-action]');
  if (!btn) return;
  const id = Number(btn.dataset.id);

  if (btn.dataset.action === 'historial-usuario') {
    openHistorialUsuarioModal(id);
    return;
  }

  const usuario = state.data.usuarios.find(u => (u.id_usuario || u.id) === id);
  if (!usuario) return;

  if (btn.dataset.action === 'editar') {
    openUsuarioModal(usuario);
  } else if (btn.dataset.action === 'eliminar') {
    const confirmado = await showArcanaConfirm({
      title: '¿Desactivar Usuario?',
      message: `¿Desactivar al usuario "${usuario.nombre} ${usuario.apellido}" y pasarlo a estado inactivo?`,
      type: 'warning',
      confirmText: 'Sí, desactivar',
      cancelText: 'Cancelar'
    });
    if (!confirmado) return;
    try {
      await deleteUsuario(id);
      await refreshUsuariosTable();
      showToast('Usuario desactivado correctamente (inactivo)', 'success');
    } catch (err) {
      showToast(err.message || 'Error al desactivar el usuario', 'error');
    }
  } else if (btn.dataset.action === 'reactivar') {
    try {
      await updateUsuario(id, { activo: 1 });
      await refreshUsuariosTable();
      showToast('Usuario reactivado correctamente', 'success');
    } catch (err) {
      showToast(err.message || 'Error al reactivar el usuario', 'error');
    }
  }
}
