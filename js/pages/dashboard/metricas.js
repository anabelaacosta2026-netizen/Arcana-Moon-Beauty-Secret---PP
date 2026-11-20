/**
 * ARCANA — Dashboard Submódulo: Métricas y KPIs (js/pages/dashboard/metricas.js)
 */

import { state } from './state.js';
import { qs, formatPrice } from '../../utils/dom.js';

export function updateMetrics(tab) {
  const items = state.data[tab] || [];
  const container = qs(`#metrics-${tab}`);
  if (!container) return;

  const totalEl = container.querySelector('.metric-card:nth-child(1) .metric-card__value');
  const activeEl = container.querySelector('.metric-card:nth-child(2) .metric-card__value');
  const inactiveEl = container.querySelector('.metric-card:nth-child(3) .metric-card__value');
  const pendingEl = container.querySelector('.metric-card:nth-child(4) .metric-card__value');

  if (totalEl) totalEl.textContent = items.length;

  if (tab === 'servicios') {
    const act = items.filter(s => s.disponibilidad === 1 || s.disponibilidad === true || s.disponible === true || s.disponible === 1).length;
    if (activeEl) activeEl.textContent = act;
    if (inactiveEl) inactiveEl.textContent = items.length - act;
  } else if (tab === 'empleados') {
    const act = items.filter(e => e.activo === 1 || e.activo === true || e.activo === '1' || e.activo === undefined).length;
    if (activeEl) activeEl.textContent = act;
    if (inactiveEl) inactiveEl.textContent = items.length - act;
  } else if (tab === 'turnos') {
    const comp = items.filter(t => (t.estado || '').toLowerCase() === 'completado').length;
    const canc = items.filter(t => (t.estado || '').toLowerCase() === 'cancelado').length;
    const pend = items.filter(t => (t.estado || '').toLowerCase() === 'pendiente').length;
    if (activeEl) activeEl.textContent = comp;
    if (inactiveEl) inactiveEl.textContent = canc;
    if (pendingEl) pendingEl.textContent = pend;
  } else if (tab === 'usuarios') {
    const act = items.filter(u => u.activo === 1 || u.activo === true || u.estado === 'activo').length;
    if (activeEl) activeEl.textContent = act;
    if (inactiveEl) inactiveEl.textContent = items.length - act;
  }
}

export async function loadMetricasAvanzadas() {
  try {
    const resp = await fetch('api/metrics.php');
    const json = await resp.json();
    if (!json.success) return;
    const m = json.data;

    // 1. Render KPI Grid (Tarjetas Superiores)
    const kpiGrid = qs('#kpi-grid');
    const resumen = m.resumen_general || m.kpis || {};
    if (kpiGrid) {
      const ingresos = resumen.ingresos_totales ?? resumen.ingresos_totales_estimados ?? 0;
      const totalTurnos = resumen.total_turnos ?? resumen.total_citas_historicas ?? 0;
      const turnosMes = resumen.turnos_mes ?? resumen.citas_mes_actual ?? 0;
      const totalUsuarios = resumen.total_usuarios ?? resumen.clientes_totales ?? 0;
      const tasaEfectividad = resumen.tasa_efectividad ?? '100%';

      kpiGrid.innerHTML = `
        <div class="metric-card glass" style="padding: 1.25rem;">
          <div class="metric-card__icon" style="color: #2ed573;">💰</div>
          <div>
            <div class="metric-card__value" style="font-size: 1.5rem; color: #2ed573;">${formatPrice(ingresos)}</div>
            <div class="metric-card__label">Ingresos Totales (MySQL)</div>
          </div>
        </div>
        <div class="metric-card glass" style="padding: 1.25rem;">
          <div class="metric-card__icon" style="color: var(--color-accent);">📅</div>
          <div>
            <div class="metric-card__value" style="font-size: 1.5rem;">${totalTurnos}</div>
            <div class="metric-card__label">Total Citas Registradas</div>
          </div>
        </div>
        <div class="metric-card glass" style="padding: 1.25rem;">
          <div class="metric-card__icon" style="color: #60a5fa;">🌸</div>
          <div>
            <div class="metric-card__value" style="font-size: 1.5rem;">${turnosMes}</div>
            <div class="metric-card__label">Citas de Este Mes</div>
          </div>
        </div>
        <div class="metric-card glass" style="padding: 1.25rem;">
          <div class="metric-card__icon" style="color: #c084fc;">👥</div>
          <div>
            <div class="metric-card__value" style="font-size: 1.5rem;">${totalUsuarios}</div>
            <div class="metric-card__label">Usuarios en Base de Datos</div>
          </div>
        </div>
      `;
    }

    // 2. Gráfico 7 Días
    const chart7 = qs('#chart-7-dias');
    const diasGrafico = m.grafico_7_dias || m.turnos_ultimos_7_dias || [];
    if (chart7 && Array.isArray(diasGrafico)) {
      const cantidades = diasGrafico.map(d => Number(d.total ?? d.cantidad ?? 0));
      const maxCant = Math.max(...cantidades, 1);
      chart7.innerHTML = diasGrafico.map(d => {
        const cant = Number(d.total ?? d.cantidad ?? 0);
        const diaNombre = d.dia_nombre ?? d.dia ?? '-';
        const heightPct = cant > 0 ? Math.max(Math.round((cant / maxCant) * 100), 20) : 6;
        return `
          <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.35rem; height: 100%; justify-content: flex-end;">
            <span style="font-size: 0.75rem; color: #fff; font-weight: 600;">${cant}</span>
            <div style="width: 100%; max-width: 34px; height: ${heightPct}%; background: linear-gradient(180deg, var(--color-accent), rgba(229,178,153,0.3)); border-radius: 6px 6px 0 0; min-height: 6px;"></div>
            <span style="font-size: 0.7rem; color: var(--color-text-muted); white-space: nowrap;">${diaNombre}</span>
          </div>
        `;
      }).join('');
    }

    // 3. Top 5 Servicios más Solicitados
    const topServList = qs('#top-servicios-list');
    const topServicios = m.top_servicios || [];
    if (topServList && Array.isArray(topServicios)) {
      if (topServicios.length === 0) {
        topServList.innerHTML = '<p style="color: var(--color-text-muted); font-size: 0.85rem;">No hay datos de servicios aún.</p>';
      } else {
        const maxSrv = Math.max(...topServicios.map(s => Number(s.turnos_total ?? s.solicitudes ?? 0)), 1);
        topServList.innerHTML = topServicios.map((s, idx) => {
          const turnos = Number(s.turnos_total ?? s.solicitudes ?? 0);
          const nombre = s.nombre || s.nombre_servicio || 'Servicio';
          const pct = Math.max((turnos / maxSrv) * 100, 10);
          return `
            <div>
              <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.25rem;">
                <span><strong>#${idx + 1}</strong> ${nombre}</span>
                <span style="color: var(--color-accent); font-weight: 600;">${turnos} ${turnos === 1 ? 'turno' : 'turnos'}</span>
              </div>
              <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.08); border-radius: 3px; overflow: hidden;">
                <div style="width: ${pct}%; height: 100%; background: var(--color-accent);"></div>
              </div>
            </div>
          `;
        }).join('');
      }
    }

    // 4. Tabla de Rendimiento por Especialista
    const tbodyEmp = qs('#table-empleados-kpi tbody');
    const desempeno = m.desempeno_empleados || m.rendimiento_empleados || [];
    if (tbodyEmp && Array.isArray(desempeno)) {
      if (desempeno.length === 0) {
        tbodyEmp.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--color-text-muted); padding: 2rem;">No hay registros de rendimiento disponibles.</td></tr>';
      } else {
        tbodyEmp.innerHTML = desempeno.map(e => {
          const asignados = Number(e.turnos_asignados ?? e.total_citas ?? 0);
          const completados = Number(e.turnos_completados ?? e.completadas ?? 0);
          const cancelados = Number(e.turnos_cancelados ?? e.canceladas ?? 0);
          const efectividad = parseFloat(e.efectividad ?? e.tasa_efectividad ?? 0);
          const ingresos = Number(e.ingresos_generados ?? 0);
          return `
            <tr>
              <td><strong>${e.nombre}</strong></td>
              <td>${asignados}</td>
              <td><span style="color: #2ed573; font-weight: 600;">${completados}</span></td>
              <td><span style="color: #ff6b6b;">${cancelados}</span></td>
              <td><span class="badge" style="background: rgba(46,213,115,0.15); color: #2ed573;">${efectividad}%</span></td>
              <td><strong>${formatPrice(ingresos)}</strong></td>
            </tr>
          `;
        }).join('');
      }
    }
  } catch (err) {
    console.error('Error cargando métricas:', err);
  }
}
