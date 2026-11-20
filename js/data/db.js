/* ==========================================
   ARCANA — Persistent Backend API Client Layer
   Conecta con los endpoints PHP y base de datos relacional JSON
   ========================================== */

const API_BASE = 'api';

export let categorias = [
  'Manicura',
  'Pedicura',
  'Pestañas',
  'Uñas',
  'Trenzas',
  'Faciales',
  'Masajes',
  'Cejas',
  'Depilación',
  'Podología'
];

async function apiRequest(endpoint, options = {}) {
  const url = `${API_BASE}/${endpoint}`;
  const defaultHeaders = {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  };

  try {
    const res = await fetch(url, {
      ...options,
      headers: options.body instanceof FormData ? undefined : { ...defaultHeaders, ...options.headers }
    });
    const result = await res.json();
    if (!result.success) {
      throw new Error(result.message || 'Error en la solicitud al servidor');
    }
    return result.data;
  } catch (error) {
    console.error(`[API Error] ${url}:`, error);
    throw error;
  }
}

/* ---- SERVICIOS ---- */
export async function getServicios() {
  const list = await apiRequest('servicios.php');
  if (Array.isArray(list)) {
    const cats = [...new Set(list.map(s => s.categoria).filter(Boolean))];
    if (cats.length) categorias = cats;
  }
  return list || [];
}

export async function addServicio(data) {
  return await apiRequest('servicios.php', {
    method: 'POST',
    body: JSON.stringify(data)
  });
}

export async function updateServicio(id, data) {
  return await apiRequest('servicios.php', {
    method: 'PUT',
    body: JSON.stringify({ id, ...data })
  });
}

export async function deleteServicio(id) {
  return await apiRequest('servicios.php', {
    method: 'DELETE',
    body: JSON.stringify({ id })
  });
}

/* ---- EMPLEADOS ---- */
export async function getEmpleados() {
  const list = await apiRequest('empleados.php');
  return list || [];
}

export async function addEmpleado(data) {
  return await apiRequest('empleados.php', {
    method: 'POST',
    body: JSON.stringify(data)
  });
}

export async function updateEmpleado(id, data) {
  return await apiRequest('empleados.php', {
    method: 'PUT',
    body: JSON.stringify({ id, ...data })
  });
}

export async function deleteEmpleado(id) {
  return await apiRequest('empleados.php', {
    method: 'DELETE',
    body: JSON.stringify({ id })
  });
}

/* ---- USUARIOS ---- */
export async function getUsuarios() {
  const list = await apiRequest('usuarios.php');
  return list || [];
}

export async function addUsuario(data) {
  return await apiRequest('usuarios.php', {
    method: 'POST',
    body: JSON.stringify(data)
  });
}

export async function updateUsuario(id, data) {
  return await apiRequest('usuarios.php', {
    method: 'PUT',
    body: JSON.stringify({ id, ...data })
  });
}

export async function deleteUsuario(id) {
  return await apiRequest('usuarios.php', {
    method: 'DELETE',
    body: JSON.stringify({ id })
  });
}

export async function getHistorialUsuario(id) {
  const data = await apiRequest(`usuarios.php?action=historial&id=${id}`);
  return data || { usuario: {}, citas: [], total_turnos: 0 };
}

/* ---- TURNOS / CITAS ---- */
export async function getTurnos() {
  const list = await apiRequest('turnos.php');
  return list || [];
}

export async function addTurno(data) {
  return await apiRequest('turnos.php', {
    method: 'POST',
    body: JSON.stringify(data)
  });
}

export async function updateTurno(id, data) {
  return await apiRequest('turnos.php', {
    method: 'PUT',
    body: JSON.stringify({ id, ...data })
  });
}

export async function deleteTurno(id) {
  return await apiRequest('turnos.php', {
    method: 'DELETE',
    body: JSON.stringify({ id })
  });
}

/* ---- METRICS ---- */
export async function getMetrics() {
  return await apiRequest('metrics.php');
}

/* ---- LOOKUPS / MAESTROS ---- */
export async function getLookups() {
  return await apiRequest('lookups.php');
}

/* ---- BACKUP / SYNC JSON ---- */
export async function exportBackupJson() {
  window.location.href = `${API_BASE}/backup.php?action=export&download=1`;
}

export async function importBackupJson(fileOrJson) {
  if (fileOrJson instanceof File) {
    const formData = new FormData();
    formData.append('file', fileOrJson);
    return await apiRequest('backup.php?action=import', {
      method: 'POST',
      body: formData
    });
  } else {
    return await apiRequest('backup.php?action=import', {
      method: 'POST',
      body: JSON.stringify(fileOrJson)
    });
  }
}

export async function resetBackupData() {
  return await apiRequest('backup.php?action=reset', {
    method: 'POST'
  });
}

/* ---- CUENTAS DE COBRO Y PASARELAS DE PAGO ---- */
export async function getCuentasCobro() {
  const list = await apiRequest('payment_accounts.php');
  return list || [];
}

export async function getCuentaCobroActiva() {
  const data = await apiRequest('payment_accounts.php?active=1');
  return data || null;
}

export async function addCuentaCobro(data) {
  return await apiRequest('payment_accounts.php', {
    method: 'POST',
    body: JSON.stringify(data)
  });
}

export async function updateCuentaCobro(id, data) {
  return await apiRequest('payment_accounts.php', {
    method: 'PUT',
    body: JSON.stringify({ id_cuenta: id, ...data })
  });
}

export async function deleteCuentaCobro(id) {
  return await apiRequest('payment_accounts.php', {
    method: 'DELETE',
    body: JSON.stringify({ id_cuenta: id })
  });
}

export async function setActiveCuentaCobro(id) {
  return await apiRequest('payment_accounts.php?action=set_active', {
    method: 'POST',
    body: JSON.stringify({ id_cuenta: id })
  });
}

/* ---- HISTORIAL DE CAMBIOS Y ACTIVIDAD ---- */
export async function getHistorialCambios(modulo = '', q = '') {
  const params = new URLSearchParams();
  if (modulo) params.set('modulo', modulo);
  if (q) params.set('q', q);
  const qs = params.toString() ? `?${params.toString()}` : '';
  const list = await apiRequest(`historial_cambios.php${qs}`);
  return list || [];
}

export async function vaciarHistorialCambios() {
  return await apiRequest('historial_cambios.php', {
    method: 'DELETE'
  });
}