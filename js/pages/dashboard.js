/**
 * ARCANA — Dashboard Main Orchestrator (js/pages/dashboard.js)
 * Arquitectura modular ES6: Coordina pestañas, filtros, búsqueda y orquestación de submódulos.
 */

import { state, paginations, setApplyFiltersHandler } from './dashboard/state.js';
import {
  getServicios, getEmpleados, getUsuarios, getTurnos, getLookups, getCuentasCobro,
  exportBackupJson, importBackupJson, resetBackupData, vaciarHistorialCambios
} from '../data/db.js';
import { qs } from '../utils/dom.js';
import { Pagination } from '../modules/pagination.js';
import { closeModal, showArcanaConfirm } from '../modules/modal.js';
import { showToast } from '../modules/toast.js';
import { initDropzone } from '../modules/dropzone.js';

// Submódulos CRUD del Dashboard
import {
  renderServiciosTable, openServicioModal, refreshServiciosTable,
  handleServicioFormSubmit, handleServiciosTableClick, setServicioDropzone
} from './dashboard/servicios.js';

import {
  renderEmpleadosTable, openEmpleadoModal, refreshEmpleadosTable,
  handleEmpleadoFormSubmit, handleEmpleadosTableClick, setEmpleadoDropzone
} from './dashboard/empleados.js';

import {
  renderTurnosTable, openTurnoModal, refreshTurnosTable,
  handleTurnoFormSubmit, handleTurnosTableClick
} from './dashboard/turnos.js';

import {
  renderUsuariosTable, openUsuarioModal, refreshUsuariosTable,
  handleUsuarioFormSubmit, handleUsuariosTableClick
} from './dashboard/usuarios.js';

import {
  fetchBloqueos, openBloqueoModal,
  handleBloqueoFormSubmit, handleBloqueosTableClick
} from './dashboard/bloqueos.js';

import {
  renderCuentasTable, openCuentaModal, refreshCuentasTable,
  handleCuentaFormSubmit, handleCuentasTableClick, updateActiveAccountBanner, toggleApiKeysSection
} from './dashboard/cuentas.js';

import { loadMetricasAvanzadas, updateMetrics } from './dashboard/metricas.js';
import { loadHistorialCambios } from './dashboard/historial.js';

/* ========== POBLAR SELECTS Y DATALISTS ========== */

function populateLookups() {
  // Categorías en datalist
  const listCat = qs('#list-categorias');
  if (listCat && state.lookups.categorias) {
    listCat.innerHTML = state.lookups.categorias
      .map(c => `<option value="${c.nombre_categoria}">`)
      .join('');
  }

  // Turno: Clientes
  const selectCli = qs('#turno-cliente');
  if (selectCli) {
    const clients = state.lookups.usuarios || [];
    selectCli.innerHTML = '<option value="">-- Seleccione un Cliente --</option>' + clients
      .map(u => `<option value="${u.id_usuario}">${u.nombre_completo} (DNI: ${u.DNI || '-'})</option>`)
      .join('');
  }

  // Turno: Espacios
  const selectEsp = qs('#turno-espacio');
  if (selectEsp) {
    const spaces = state.lookups.espacios || [];
    selectEsp.innerHTML = spaces
      .map(e => `<option value="${e.id_espacio}">${e.nombre} (${e.tipo})</option>`)
      .join('');
  }

  // Turno: Empleados
  const selectEmp = qs('#turno-empleado');
  if (selectEmp) {
    const emps = state.lookups.empleados || [];
    selectEmp.innerHTML = emps
      .map(e => `<option value="${e.id_empleado}">${e.nombre_completo}</option>`)
      .join('');
  }

  // Bloqueo: Empleados
  const selectEmpBloqueo = qs('#bloqueo-empleado');
  if (selectEmpBloqueo) {
    const emps = state.lookups.empleados || [];
    selectEmpBloqueo.innerHTML = '<option value="0">Todo el Salón (General)</option>' + emps
      .map(e => `<option value="${e.id_empleado}">${e.nombre_completo}</option>`)
      .join('');
  }

  // Turno: Servicios
  const selectServ = qs('#turno-servicio');
  if (selectServ) {
    const servs = state.lookups.servicios || [];
    selectServ.innerHTML = servs
      .map(s => `<option value="${s.id_servicio}">${s.nombre_servicio} (${s.categoria})</option>`)
      .join('');
  }
}

async function refreshLookups() {
  try {
    const lookups = await getLookups();
    state.lookups = lookups;
    populateLookups();
  } catch (err) {
    console.error('Error refreshing lookups:', err);
  }
}

/* ========== FILTRADO Y BÚSQUEDA ========== */

function applyFilters(tab) {
  const q = (qs(`#search-${tab}`)?.value || '').toLowerCase().trim();
  const estado = qs(`#filter-estado-${tab}`)?.value || 'todos';

  let items = state.data[tab] || [];

  if (estado !== 'todos') {
    if (tab === 'servicios') {
      const isActivo = estado === 'activos';
      items = items.filter(s => (s.disponibilidad === 1 || s.disponibilidad === true || s.disponible === true || s.disponible === 1) === isActivo);
    } else if (tab === 'empleados') {
      const isActivo = estado === 'activos';
      items = items.filter(e => (e.activo === 1 || e.activo === true || e.activo === '1' || e.activo === undefined) === isActivo);
    } else if (tab === 'turnos') {
      if (estado === 'activos') {
        items = items.filter(t => (t.estado || '').toLowerCase() !== 'cancelado');
      } else if (estado === 'inactivos') {
        items = items.filter(t => (t.estado || '').toLowerCase() === 'cancelado');
      } else {
        items = items.filter(t => (t.estado || '').toLowerCase() === estado);
      }
    } else if (tab === 'usuarios') {
      const isActivo = estado === 'activos';
      items = items.filter(u => (u.activo === 1 || u.activo === true || u.estado === 'activo') === isActivo);
    } else if (tab === 'cuentas') {
      const isActivo = estado === 'activos';
      items = items.filter(c => ((c.estado || 'activo') !== 'inactivo') === isActivo);
    }
  }

  if (q) {
    items = items.filter(item => {
      if (tab === 'servicios') {
        return (item.nombre_servicio || item.nombre || '').toLowerCase().includes(q) ||
          (item.categoria || '').toLowerCase().includes(q) ||
          (item.especialista || '').toLowerCase().includes(q);
      } else if (tab === 'empleados') {
        const esp = Array.isArray(item.especialidades) ? item.especialidades.join(' ') : (item.especialidades || '');
        return (item.nombre || '').toLowerCase().includes(q) ||
          (item.apellido || '').toLowerCase().includes(q) ||
          esp.toLowerCase().includes(q);
      } else if (tab === 'turnos') {
        return (item.cliente || '').toLowerCase().includes(q) ||
          (item.empleado || '').toLowerCase().includes(q) ||
          (item.servicio || '').toLowerCase().includes(q) ||
          (item.espacio || '').toLowerCase().includes(q);
      } else if (tab === 'usuarios') {
        return (item.nombre || '').toLowerCase().includes(q) ||
          (item.apellido || '').toLowerCase().includes(q) ||
          (item.email || '').toLowerCase().includes(q) ||
          (item.DNI || item.dni || '').toLowerCase().includes(q);
      } else if (tab === 'cuentas') {
        return (item.nombre_referencia || '').toLowerCase().includes(q) ||
          (item.titular || '').toLowerCase().includes(q) ||
          (item.alias || '').toLowerCase().includes(q) ||
          (item.proveedor || '').toLowerCase().includes(q);
      }
      return false;
    });
  }

  if (paginations[tab]) {
    paginations[tab].updateItems(items);
  }
  updateMetrics(tab);
}

/* ========== CAMBIO DE PESTAÑAS (TABS) ========== */

function switchTab(tabName) {
  state.activeTab = tabName;

  document.querySelectorAll('.dashboard__tab').forEach(btn => {
    btn.classList.toggle('dashboard__tab--active', btn.dataset.tab === tabName);
  });

  document.querySelectorAll('.dashboard__panel').forEach(panel => {
    panel.classList.toggle('dashboard__panel--active', panel.id === `panel-${tabName}`);
  });

  if (tabName === 'bloqueos') {
    fetchBloqueos();
  } else if (tabName === 'metricas') {
    loadMetricasAvanzadas();
  } else if (tabName === 'cuentas') {
    refreshCuentasTable();
  } else if (tabName === 'historial') {
    loadHistorialCambios();
  } else if (state.data[tabName]?.length && paginations[tabName]) {
    paginations[tabName].render();
    updateMetrics(tabName);
  }
}

/* ========== INICIALIZACIÓN DEL DASHBOARD ========== */

export async function initDashboard() {
  const dashboard = qs('.dashboard');
  if (!dashboard) return;

  // Registrar handler global de filtros
  setApplyFiltersHandler(applyFilters);

  // 1. Eventos de Pestañas (Registrar de inmediato para respuesta instantánea de navegación)
  document.querySelectorAll('.dashboard__tab').forEach(btn => {
    btn.addEventListener('click', () => switchTab(btn.dataset.tab));
  });

  // 2. Filtros de búsqueda y estado
  ['servicios', 'empleados', 'turnos', 'usuarios', 'cuentas'].forEach(tab => {
    const searchEl = qs(`#search-${tab}`);
    if (searchEl) searchEl.addEventListener('input', () => applyFilters(tab));
    const filterEstadoEl = qs(`#filter-estado-${tab}`);
    if (filterEstadoEl) filterEstadoEl.addEventListener('change', () => applyFilters(tab));
  });

  try {
    const [servicios, empleados, turnos, usuarios, lookups, cuentas] = await Promise.all([
      getServicios(), getEmpleados(), getTurnos(), getUsuarios(), getLookups(), getCuentasCobro()
    ]);

    state.data = { servicios, empleados, turnos, usuarios, cuentas };
    state.lookups = lookups;

    paginations.servicios = new Pagination({ items: servicios, itemsPerPage: 5, renderFn: renderServiciosTable, containerSelector: '#panel-servicios' });
    paginations.empleados = new Pagination({ items: empleados, itemsPerPage: 5, renderFn: renderEmpleadosTable, containerSelector: '#panel-empleados' });
    paginations.turnos = new Pagination({ items: turnos, itemsPerPage: 5, renderFn: renderTurnosTable, containerSelector: '#panel-turnos' });
    paginations.usuarios = new Pagination({ items: usuarios, itemsPerPage: 5, renderFn: renderUsuariosTable, containerSelector: '#panel-usuarios' });
    paginations.cuentas = new Pagination({ items: cuentas, itemsPerPage: 6, renderFn: renderCuentasTable, containerSelector: '#panel-cuentas' });

    populateLookups();
    updateActiveAccountBanner(cuentas);

    // Aplicar filtros iniciales y renderizar tablas
    ['servicios', 'empleados', 'turnos', 'usuarios', 'cuentas'].forEach(tab => {
      applyFilters(tab);
    });

    // Event listeners para Historial de Cambios
    const searchHistorial = qs('#search-historial');
    const filterModuloHistorial = qs('#filter-modulo-historial');
    const btnRefreshHistorial = qs('#btn-refresh-historial');
    const btnVaciarHistorial = qs('#btn-vaciar-historial');

    let debounceHistorialTimer = null;
    if (searchHistorial) {
      searchHistorial.addEventListener('input', () => {
        clearTimeout(debounceHistorialTimer);
        debounceHistorialTimer = setTimeout(loadHistorialCambios, 280);
      });
    }
    if (filterModuloHistorial) {
      filterModuloHistorial.addEventListener('change', loadHistorialCambios);
    }
    if (btnRefreshHistorial) {
      btnRefreshHistorial.addEventListener('click', () => {
        showToast('Actualizando historial de cambios...', 'info');
        loadHistorialCambios();
      });
    }
    if (btnVaciarHistorial) {
      btnVaciarHistorial.addEventListener('click', async () => {
        const confirmado = await showArcanaConfirm({
          title: '¿Vaciar Historial?',
          message: '¿Estás seguro de que deseas vaciar todo el registro de cambios de auditoría?',
          type: 'warning',
          confirmText: 'Sí, vaciar',
          cancelText: 'Cancelar'
        });
        if (!confirmado) return;
        try {
          await vaciarHistorialCambios();
          showToast('Historial vaciado correctamente', 'success');
          loadHistorialCambios();
        } catch (err) {
          showToast(err.message || 'Error al vaciar historial', 'error');
        }
      });
    }

    // Botón Flotante (FAB)
    const fab = qs('.dashboard__fab');
    if (fab) {
      fab.addEventListener('click', () => {
        switch (state.activeTab) {
          case 'servicios': openServicioModal(null); break;
          case 'empleados': openEmpleadoModal(null); break;
          case 'turnos': openTurnoModal(null); break;
          case 'usuarios': openUsuarioModal(null); break;
          case 'cuentas': openCuentaModal(null); break;
        }
      });
    }



    // Eventos de Tablas (Delegación de eventos)
    const tablaServicios = qs('#table-servicios tbody');
    if (tablaServicios) tablaServicios.addEventListener('click', handleServiciosTableClick);

    const tablaEmpleados = qs('#table-empleados tbody');
    if (tablaEmpleados) tablaEmpleados.addEventListener('click', handleEmpleadosTableClick);

    const tablaTurnos = qs('#table-turnos tbody');
    if (tablaTurnos) tablaTurnos.addEventListener('click', handleTurnosTableClick);

    const tablaUsuarios = qs('#table-usuarios tbody');
    if (tablaUsuarios) tablaUsuarios.addEventListener('click', handleUsuariosTableClick);

    const tablaCuentas = qs('#table-cuentas tbody');
    if (tablaCuentas) tablaCuentas.addEventListener('click', handleCuentasTableClick);

    const tablaBloqueos = qs('#table-bloqueos tbody');
    if (tablaBloqueos) tablaBloqueos.addEventListener('click', handleBloqueosTableClick);

    // Eventos de Formularios
    const formServicio = qs('#form-servicio');
    if (formServicio) formServicio.addEventListener('submit', handleServicioFormSubmit);
    const btnCancelarServicio = qs('#btn-cancelar-servicio');
    if (btnCancelarServicio) btnCancelarServicio.addEventListener('click', () => closeModal('#modal-servicio'));

    const formEmpleado = qs('#form-empleado');
    if (formEmpleado) formEmpleado.addEventListener('submit', handleEmpleadoFormSubmit);
    const btnCancelarEmpleado = qs('#btn-cancelar-empleado');
    if (btnCancelarEmpleado) btnCancelarEmpleado.addEventListener('click', () => closeModal('#modal-empleado'));

    const formUsuario = qs('#form-usuario');
    if (formUsuario) formUsuario.addEventListener('submit', handleUsuarioFormSubmit);
    const btnCancelarUsuario = qs('#btn-cancelar-usuario');
    if (btnCancelarUsuario) btnCancelarUsuario.addEventListener('click', () => closeModal('#modal-usuario'));

    const formTurno = qs('#form-turno');
    if (formTurno) formTurno.addEventListener('submit', handleTurnoFormSubmit);
    const btnCancelarTurno = qs('#btn-cancelar-turno');
    if (btnCancelarTurno) btnCancelarTurno.addEventListener('click', () => closeModal('#modal-turno'));

    const btnNuevoBloqueo = qs('#btn-nuevo-bloqueo');
    if (btnNuevoBloqueo) btnNuevoBloqueo.addEventListener('click', openBloqueoModal);
    const formBloqueo = qs('#form-bloqueo');
    if (formBloqueo) formBloqueo.addEventListener('submit', handleBloqueoFormSubmit);
    const btnCancelarBloqueo = qs('#btn-cancelar-bloqueo');
    if (btnCancelarBloqueo) btnCancelarBloqueo.addEventListener('click', () => closeModal('#modal-bloqueo'));

    const btnNuevaCuenta = qs('#btn-nueva-cuenta');
    if (btnNuevaCuenta) btnNuevaCuenta.addEventListener('click', () => openCuentaModal(null));
    const formCuenta = qs('#form-cuenta-cobro');
    if (formCuenta) formCuenta.addEventListener('submit', handleCuentaFormSubmit);
    const btnCancelarCuenta = qs('#btn-cancelar-cuenta');
    if (btnCancelarCuenta) btnCancelarCuenta.addEventListener('click', () => closeModal('#modal-cuenta-cobro'));
    const selectProvCuenta = qs('#cuenta-proveedor');
    if (selectProvCuenta) selectProvCuenta.addEventListener('change', toggleApiKeysSection);

    const btnRefreshMetricas = qs('#btn-refresh-metricas');
    if (btnRefreshMetricas) btnRefreshMetricas.addEventListener('click', loadMetricasAvanzadas);

    // Inicializar Drag & Drop para Imágenes en Modales
    try {
      const sDz = initDropzone({
        containerSelector: '#dropzone-servicio',
        inputSelector: '#servicio-imagen',
        defaultImage: ''
      });
      setServicioDropzone(sDz);

      const eDz = initDropzone({
        containerSelector: '#dropzone-empleado',
        inputSelector: '#empleado-foto',
        defaultImage: ''
      });
      setEmpleadoDropzone(eDz);

      document.querySelectorAll('.btn-toggle-manual-url').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          const target = qs(btn.dataset.target);
          if (target) {
            const isHidden = target.style.display === 'none';
            target.style.display = isHidden ? 'block' : 'none';
            btn.textContent = isHidden ? 'Ocultar ruta manual' : 'Escribir ruta manual';
          }
        });
      });

      const inputServicioImg = qs('#servicio-imagen');
      if (inputServicioImg) {
        inputServicioImg.addEventListener('input', () => {
          if (sDz) sDz.setImage(inputServicioImg.value);
        });
      }

      const inputEmpleadoFoto = qs('#empleado-foto');
      if (inputEmpleadoFoto) {
        inputEmpleadoFoto.addEventListener('input', () => {
          if (eDz) eDz.setImage(inputEmpleadoFoto.value);
        });
      }
    } catch (dropzoneErr) {
      console.warn('Error iniciando dropzones:', dropzoneErr);
    }

    switchTab('servicios');
  } catch (err) {
    console.error('Error inicializando dashboard:', err);
    showToast('Error cargando los datos del dashboard', 'error');
  }
}