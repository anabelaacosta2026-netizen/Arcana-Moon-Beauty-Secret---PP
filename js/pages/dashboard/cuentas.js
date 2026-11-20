/**
 * ARCANA — Dashboard Submódulo: Cuentas de Cobro (js/pages/dashboard/cuentas.js)
 */

import { state, paginations, renderAcciones, modalForceClose, triggerApplyFilters } from './state.js';
import { getCuentasCobro, addCuentaCobro, updateCuentaCobro, deleteCuentaCobro, setActiveCuentaCobro } from '../../data/db.js';
import { qs } from '../../utils/dom.js';
import { openModal, closeModal, showArcanaConfirm } from '../../modules/modal.js';
import { showToast } from '../../modules/toast.js';

export function formatProveedorName(prov) {
  switch (prov) {
    case 'mercadopago': return 'Mercado Pago';
    case 'ualabis': return 'Ualá Bis';
    case 'transferencia': return 'Transferencia Bancaria';
    case 'efectivo': return 'Efectivo en Salón';
    default: return prov || 'Pasarela';
  }
}

export function getProveedorBadge(prov) {
  switch (prov) {
    case 'mercadopago':
      return '<span class="badge" style="background: rgba(0,158,227,0.18); color: #009ee3; font-weight: 600;">Mercado Pago</span>';
    case 'ualabis':
      return '<span class="badge" style="background: rgba(147,51,234,0.18); color: #c084fc; font-weight: 600;">Ualá Bis</span>';
    case 'transferencia':
      return '<span class="badge" style="background: rgba(20,184,166,0.18); color: #2dd4bf; font-weight: 600;">Transferencia Bancaria</span>';
    case 'efectivo':
      return '<span class="badge" style="background: rgba(245,158,11,0.18); color: #fbbf24; font-weight: 600;">Efectivo / Salón</span>';
    default:
      return `<span class="badge">${prov}</span>`;
  }
}

export function updateActiveAccountBanner(cuentas) {
  const list = Array.isArray(cuentas) ? cuentas : [];
  const active = list.find(c => Number(c.es_activa) === 1) || (list.length > 0 ? list[0] : null);
  const nameEl = qs('#banner-active-name');
  const detailEl = qs('#banner-active-detail');
  const modoEl = qs('#banner-active-modo');
  if (!nameEl) return;

  if (active && Number(active.es_activa) === 1) {
    nameEl.textContent = active.nombre_referencia || 'Cuenta Activa';
    detailEl.textContent = `Titular: ${active.titular || '-'} | Proveedor: ${formatProveedorName(active.proveedor)} | Alias/CBU: ${active.alias || active.cbu_cvu || '-'}`;
    if (modoEl) {
      const isSandbox = (active.modo || 'sandbox') === 'sandbox';
      modoEl.textContent = isSandbox ? 'MODO SANDBOX (PRUEBAS)' : 'MODO PRODUCCIÓN (REAL)';
      modoEl.style.background = isSandbox ? 'rgba(246, 224, 94, 0.2)' : 'rgba(46, 213, 115, 0.2)';
      modoEl.style.color = isSandbox ? '#f6e05e' : '#2ed573';
    }
  } else {
    nameEl.textContent = 'Sin cuenta receptora activa';
    detailEl.textContent = 'Selecciona una cuenta en la tabla para que los clientes puedan abonar señas.';
  }
}

export function toggleApiKeysSection() {
  const prov = qs('#cuenta-proveedor')?.value || 'mercadopago';
  const sec = qs('#section-api-keys');
  if (sec) {
    sec.style.display = (prov === 'mercadopago' || prov === 'ualabis') ? 'block' : 'none';
  }
}

export function renderCuentasTable(items) {
  const tbody = qs('#table-cuentas tbody');
  if (!tbody) return;
  const fragment = document.createDocumentFragment();

  updateActiveAccountBanner(state.data.cuentas || items);

  if (!items || items.length === 0) {
    const tr = document.createElement('tr');
    tr.innerHTML = '<td colspan="8" style="text-align: center; color: var(--color-text-muted); padding: 2rem;">No hay cuentas de cobro registradas. Haz clic en "+ Nueva Cuenta de Cobro".</td>';
    tbody.replaceChildren(tr);
    return;
  }

  items.forEach(c => {
    const idVal = c.id_cuenta || c.id;
    const isActiva = Number(c.es_activa) === 1;
    const isSandbox = (c.modo || 'sandbox') === 'sandbox';

    const tr = document.createElement('tr');
    if (isActiva) {
      tr.style.background = 'rgba(46, 213, 115, 0.05)';
    }

    const tdId = document.createElement('td');
    tdId.textContent = String(idVal).padStart(2, '0');
    tr.appendChild(tdId);

    const tdRef = document.createElement('td');
    tdRef.innerHTML = `<strong>${c.nombre_referencia || 'Cuenta'}</strong>` + (c.email_asociado ? `<br><small style="color: var(--color-text-muted); font-size: 0.75rem;">${c.email_asociado}</small>` : '');
    tr.appendChild(tdRef);

    const tdProv = document.createElement('td');
    tdProv.innerHTML = getProveedorBadge(c.proveedor);
    tr.appendChild(tdProv);

    const tdTit = document.createElement('td');
    tdTit.innerHTML = `<span>${c.titular || '-'}</span>` + (c.cuit_cuil ? `<br><small style="color: var(--color-text-muted); font-size: 0.75rem;">CUIT: ${c.cuit_cuil}</small>` : '');
    tr.appendChild(tdTit);

    const tdAlias = document.createElement('td');
    tdAlias.innerHTML = `<code>${c.alias || c.cbu_cvu || '-'}</code>` + (c.banco_billetera ? `<br><small style="color: var(--color-text-muted); font-size: 0.75rem;">${c.banco_billetera}</small>` : '');
    tr.appendChild(tdAlias);

    const tdModo = document.createElement('td');
    tdModo.innerHTML = `<span class="badge" style="background: ${isSandbox ? 'rgba(246,224,94,0.15)' : 'rgba(46,213,115,0.15)'}; color: ${isSandbox ? '#f6e05e' : '#2ed573'}; font-size: 0.75rem;">${isSandbox ? 'Sandbox' : 'Producción'}</span>`;
    tr.appendChild(tdModo);

    const tdEstado = document.createElement('td');
    if (isActiva) {
      tdEstado.innerHTML = `<span class="badge" style="background: rgba(46,213,115,0.22); color: #2ed573; border: 1px solid rgba(46,213,115,0.4); font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">⚡ RECIBIENDO FONDOS</span>`;
    } else {
      tdEstado.innerHTML = `<button type="button" class="btn-pill btn-pill--sm" data-action="activar" data-id="${idVal}" style="padding: 0.25rem 0.65rem; font-size: 0.75rem; background: rgba(255,255,255,0.06); border: 1px solid var(--color-glass-border); cursor: pointer;" title="Hacer que los cobros ingresen a esta cuenta">⚡ Activar</button>`;
    }
    tr.appendChild(tdEstado);

    const tdAct = document.createElement('td');
    tdAct.className = 'table-actions';
    const isCuentaActivaEstado = (c.estado || 'activo') !== 'inactivo';
    tdAct.innerHTML = renderAcciones(idVal, isCuentaActivaEstado, 'cuenta');
    tr.appendChild(tdAct);

    fragment.appendChild(tr);
  });
  tbody.replaceChildren(fragment);
}

export function openCuentaModal(cuenta = null) {
  const form = qs('#form-cuenta-cobro');
  const title = qs('#modal-cuenta-title');
  if (!form || !title) return;

  form.reset();
  const idVal = cuenta ? (cuenta.id_cuenta || cuenta.id) : '';
  qs('#cuenta-id').value = idVal;
  title.textContent = cuenta ? 'Editar Cuenta de Cobro' : 'Nueva Cuenta de Cobro';

  if (cuenta) {
    qs('#cuenta-nombre-ref').value = cuenta.nombre_referencia || '';
    qs('#cuenta-proveedor').value = cuenta.proveedor || 'mercadopago';
    qs('#cuenta-modo').value = cuenta.modo || 'sandbox';
    qs('#cuenta-titular').value = cuenta.titular || '';
    qs('#cuenta-cuit').value = cuenta.cuit_cuil || '';
    qs('#cuenta-email').value = cuenta.email_asociado || '';
    qs('#cuenta-banco').value = cuenta.banco_billetera || '';
    qs('#cuenta-alias').value = cuenta.alias || '';
    qs('#cuenta-cbu').value = cuenta.cbu_cvu || '';
    qs('#cuenta-public-key').value = cuenta.public_key || '';
    qs('#cuenta-access-token').value = '';
    qs('#cuenta-es-activa').checked = Number(cuenta.es_activa) === 1;
  } else {
    qs('#cuenta-proveedor').value = 'mercadopago';
    qs('#cuenta-modo').value = 'sandbox';
    qs('#cuenta-banco').value = 'Mercado Pago';
    qs('#cuenta-es-activa').checked = false;
  }

  toggleApiKeysSection();
  openModal('#modal-cuenta-cobro');
}

export async function refreshCuentasTable() {
  try {
    const cuentas = await getCuentasCobro();
    state.data.cuentas = cuentas;
    triggerApplyFilters('cuentas');
    updateActiveAccountBanner(cuentas);
  } catch (e) {
    showToast('Error al actualizar cuentas de cobro', 'error');
  }
}

export async function handleCuentaFormSubmit(e) {
  e.preventDefault();

  const id = qs('#cuenta-id').value;
  const nombre_referencia = qs('#cuenta-nombre-ref').value.trim();
  const proveedor = qs('#cuenta-proveedor').value;
  const modo = qs('#cuenta-modo').value;
  const titular = qs('#cuenta-titular').value.trim();
  const cuit_cuil = qs('#cuenta-cuit').value.trim();
  const email_asociado = qs('#cuenta-email').value.trim();
  const banco_billetera = qs('#cuenta-banco').value.trim();
  const alias = qs('#cuenta-alias').value.trim();
  const cbu_cvu = qs('#cuenta-cbu').value.trim();
  const public_key = qs('#cuenta-public-key').value.trim();
  const access_token = qs('#cuenta-access-token').value.trim();
  const es_activa = qs('#cuenta-es-activa').checked ? 1 : 0;

  if (!nombre_referencia || !titular) {
    showToast('Completa el nombre de referencia y el titular de la cuenta', 'error');
    return;
  }

  const data = {
    nombre_referencia,
    proveedor,
    modo,
    titular,
    cuit_cuil,
    email_asociado,
    banco_billetera,
    alias,
    cbu_cvu,
    public_key,
    access_token,
    es_activa,
    estado: 'activo'
  };

  try {
    if (id) {
      await updateCuentaCobro(Number(id), data);
      showToast('Cuenta de cobro actualizada correctamente', 'success');
    } else {
      await addCuentaCobro(data);
      showToast('Cuenta de cobro creada correctamente', 'success');
    }
  } catch (err) {
    showToast(err.message || 'Error al guardar la cuenta', 'error');
    return;
  }

  try { closeModal('#modal-cuenta-cobro'); } catch { modalForceClose('#modal-cuenta-cobro'); }
  await refreshCuentasTable();
}

export async function handleCuentasTableClick(e) {
  const btn = e.target.closest('[data-action]');
  if (!btn) return;
  const id = Number(btn.dataset.id);
  const cuenta = state.data.cuentas.find(c => (c.id_cuenta || c.id) === id);
  if (!cuenta) return;

  if (btn.dataset.action === 'activar') {
    try {
      await setActiveCuentaCobro(id);
      showToast(`¡Cuenta "${cuenta.nombre_referencia}" activada como receptora principal!`, 'success');
      await refreshCuentasTable();
    } catch (err) {
      showToast(err.message || 'Error al activar cuenta', 'error');
    }
  } else if (btn.dataset.action === 'editar') {
    openCuentaModal(cuenta);
  } else if (btn.dataset.action === 'eliminar') {
    const nom = cuenta.nombre_referencia || `#${id}`;
    const confirmado = await showArcanaConfirm({
      title: '¿Desactivar Cuenta?',
      message: `¿Estás seguro de desactivar la cuenta "${nom}"?`,
      type: 'warning',
      confirmText: 'Sí, desactivar',
      cancelText: 'Cancelar'
    });
    if (!confirmado) return;
    try {
      await deleteCuentaCobro(id);
      await refreshCuentasTable();
      showToast('Cuenta desactivada correctamente (inactiva)', 'success');
    } catch (err) {
      showToast(err.message || 'Error al desactivar cuenta', 'error');
    }
  } else if (btn.dataset.action === 'reactivar') {
    try {
      await updateCuentaCobro(id, { estado: 'activo' });
      await refreshCuentasTable();
      showToast('Cuenta reactivada correctamente', 'success');
    } catch (err) {
      showToast(err.message || 'Error al reactivar cuenta', 'error');
    }
  }
}
