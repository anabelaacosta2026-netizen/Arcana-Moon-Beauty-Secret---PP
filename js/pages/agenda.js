/* ==========================================
   ARCANA — Agenda & Booking Management Script
   ========================================== */

import { showConfirmationAnimation } from '../modules/confirmation-animation.js';
import { showFormAlert, showFieldError, showArcanaModal } from '../modules/modal.js';

export function initAgenda() {
  const form = document.querySelector('#agenda-form');
  if (!form) return;

  const fechaInput = document.querySelector('#agenda-fecha');
  const horaInput = document.querySelector('#agenda-hora');
  const slotsContainer = document.querySelector('#time-slots-container');
  const labelHora = document.querySelector('#label-hora-seleccionada');
  const bannerBloqueado = document.querySelector('#banner-dia-bloqueado');
  const motivoText = document.querySelector('#motivo-bloqueo-text');
  const btnSubmit = document.querySelector('#btn-submit-agenda');
  const servicioSelect = form.querySelector('#agenda-servicio');
  const especialistaSelect = form.querySelector('#agenda-especialista');

  const listaContratados = document.querySelector('#lista-servicios-contratados');
  const resumenBox = document.querySelector('#resumen-sena-box');
  const totalCostoEl = document.querySelector('#resumen-costo-total');
  const totalSenaEl = document.querySelector('#resumen-monto-sena');
  const btnProceedMp = document.querySelector('#btn-proceder-mp');
  const btnRefreshServicios = document.querySelector('#btn-refresh-mis-servicios');

  let selectedHour = horaInput ? horaInput.value : '10:00';
  let firstPendingCitaId = null;

  // -------------------------------------------------------------
  // Filtro Dinámico: Especialistas a cargo del Servicio
  // -------------------------------------------------------------
  function updateEspecialistasSelect() {
    if (!especialistaSelect) return;
    const selectedOption = servicioSelect?.selectedOptions?.[0];
    const servicioId = selectedOption?.dataset?.id;
    const servicioNombre = servicioSelect?.value || '';

    if (!servicioNombre) {
      especialistaSelect.innerHTML = '<option value="Cualquier especialista disponible" data-modalidad="Mixto">-- Cualquier especialista disponible --</option>';
      updateModalidadOptions();
      return;
    }

    // Buscar en datos embebidos del servidor
    let especialistas = [];
    if (window.arcanaServiciosData && servicioId && window.arcanaServiciosData[servicioId]) {
      especialistas = window.arcanaServiciosData[servicioId].especialistas || [];
    }

    if (especialistas.length === 0) {
      especialistaSelect.innerHTML = '<option value="Cualquier especialista disponible" data-modalidad="Mixto">-- Cualquier especialista disponible --</option>';
      updateModalidadOptions();
      return;
    }

    let html = '<option value="Cualquier especialista disponible" data-modalidad="Mixto">-- Cualquier especialista disponible --</option>';
    especialistas.forEach(emp => {
      const mod = emp.modalidad || 'Presencial';
      html += `<option value="${escapeHtml(emp.nombre_completo)}" data-modalidad="${escapeHtml(mod)}">${escapeHtml(emp.nombre_completo)} (${escapeHtml(mod)})</option>`;
    });

    especialistaSelect.innerHTML = html;

    // Si solo hay 1 especialista a cargo, preseleccionarlo directamente
    if (especialistas.length === 1) {
      especialistaSelect.value = especialistas[0].nombre_completo;
    }

    updateModalidadOptions();
  }

  // -------------------------------------------------------------
  // Control Dinámico de Modalidades según el Especialista
  // -------------------------------------------------------------
  function updateModalidadOptions() {
    const modalidadSelect = form.querySelector('#agenda-modalidad');
    if (!modalidadSelect) return;

    const optPresencial = modalidadSelect.querySelector('option[value="Presencial"]');
    const optDomicilio = modalidadSelect.querySelector('option[value="Domicilio"]');
    const helpEl = document.querySelector('#modalidad-help');
    if (!optPresencial || !optDomicilio) return;

    const selectedEmpOption = especialistaSelect?.selectedOptions?.[0];
    const empNombre = (especialistaSelect?.value || '').trim();

    // Obtener modalidad del empleado seleccionado
    let modalidadEmp = '';
    if (selectedEmpOption && selectedEmpOption.dataset && selectedEmpOption.dataset.modalidad) {
      modalidadEmp = selectedEmpOption.dataset.modalidad;
    } else if (empNombre && empNombre !== 'Cualquier especialista disponible' && window.arcanaEmpleadosMap && window.arcanaEmpleadosMap[empNombre]) {
      modalidadEmp = window.arcanaEmpleadosMap[empNombre].modalidad;
    }

    const modalidadNorm = (modalidadEmp || '').toLowerCase().trim();

    // 1. Si el empleado es exclusivo a Domicilio: bloquear Presencial (Salón)
    if (modalidadNorm === 'domicilio') {
      optPresencial.disabled = true;
      optPresencial.textContent = 'Presencial en salón (No disponible para este especialista)';
      optDomicilio.disabled = false;
      optDomicilio.textContent = 'A Domicilio (Disponible)';
      modalidadSelect.value = 'Domicilio';
      if (helpEl) {
        helpEl.textContent = 'Este especialista solo realiza atención a domicilio.';
        helpEl.style.color = '#60a5fa';
      }
    } 
    // 2. Si el empleado es exclusivo Presencial: bloquear A Domicilio
    else if (modalidadNorm === 'presencial') {
      optPresencial.disabled = false;
      optPresencial.textContent = 'Presencial (En el salón)';
      optDomicilio.disabled = true;
      optDomicilio.textContent = 'A Domicilio (No disponible para este especialista)';
      modalidadSelect.value = 'Presencial';
      if (helpEl) {
        helpEl.textContent = 'Este especialista solo atiende de forma presencial en el salón.';
        helpEl.style.color = 'var(--color-text-muted)';
      }
    } 
    // 3. Si el empleado ofrece modalidad Mixta: habilitar ambas
    else if (modalidadNorm === 'mixto' || modalidadNorm === 'mixta' || modalidadNorm === 'ambas') {
      optPresencial.disabled = false;
      optPresencial.textContent = 'Presencial (En el salón)';
      optDomicilio.disabled = false;
      optDomicilio.textContent = 'A Domicilio';
      if (modalidadSelect.value !== 'Presencial' && modalidadSelect.value !== 'Domicilio') {
        modalidadSelect.value = 'Presencial';
      }
      if (helpEl) {
        helpEl.textContent = 'Este especialista ofrece atención tanto en salón como a domicilio.';
        helpEl.style.color = '#4ade80';
      }
    } 
    // 4. "Cualquier especialista disponible" o sin selección específica
    else {
      const selectedOptionServicio = servicioSelect?.selectedOptions?.[0];
      const servicioId = selectedOptionServicio?.dataset?.id;
      const especialistasServicio = (window.arcanaServiciosData && servicioId && window.arcanaServiciosData[servicioId])
        ? (window.arcanaServiciosData[servicioId].especialistas || [])
        : [];

      if (especialistasServicio.length > 0) {
        const tienePresencial = especialistasServicio.some(e => {
          const m = (e.modalidad || 'presencial').toLowerCase().trim();
          return m === 'presencial' || m === 'mixto' || m === 'mixta' || m === 'ambas';
        });
        const tieneDomicilio = especialistasServicio.some(e => {
          const m = (e.modalidad || 'presencial').toLowerCase().trim();
          return m === 'domicilio' || m === 'mixto' || m === 'mixta' || m === 'ambas';
        });

        optPresencial.disabled = !tienePresencial;
        optPresencial.textContent = tienePresencial ? 'Presencial (En el salón)' : 'Presencial en salón (No disponible)';
        optDomicilio.disabled = !tieneDomicilio;
        optDomicilio.textContent = tieneDomicilio ? 'A Domicilio' : 'A Domicilio (No disponible)';

        if (!tienePresencial && tieneDomicilio) {
          modalidadSelect.value = 'Domicilio';
        } else if (tienePresencial && !tieneDomicilio) {
          modalidadSelect.value = 'Presencial';
        }

        if (helpEl) {
          if (tienePresencial && tieneDomicilio) {
            helpEl.textContent = 'Servicio disponible en salón y a domicilio.';
            helpEl.style.color = '#4ade80';
          } else if (tienePresencial) {
            helpEl.textContent = 'Servicio disponible únicamente en el salón.';
            helpEl.style.color = 'var(--color-text-muted)';
          } else {
            helpEl.textContent = 'Servicio disponible únicamente a domicilio.';
            helpEl.style.color = '#60a5fa';
          }
        }
      } else {
        optPresencial.disabled = false;
        optPresencial.textContent = 'Presencial (En el salón)';
        optDomicilio.disabled = false;
        optDomicilio.textContent = 'A Domicilio';
        if (helpEl) {
          helpEl.textContent = 'Elige la modalidad de atención.';
          helpEl.style.color = 'var(--color-text-muted)';
        }
      }
    }
  }

  // -------------------------------------------------------------
  // 1. Cargar Disponibilidad en tiempo real para la fecha y servicio/especialista
  // -------------------------------------------------------------
  async function checkDisponibilidad(fecha) {
    if (!slotsContainer) return;

    const servicio = servicioSelect ? servicioSelect.value : '';
    const especialista = especialistaSelect ? especialistaSelect.value : '';

    slotsContainer.innerHTML = '<div style="grid-column: 1/-1; text-align: center; font-size: 0.8rem; color: var(--color-text-muted); padding: 0.75rem;">Consultando horarios disponibles...</div>';

    try {
      const res = await fetch(`api/agenda.php?action=disponibilidad&fecha=${encodeURIComponent(fecha)}&servicio=${encodeURIComponent(servicio)}&empleado=${encodeURIComponent(especialista)}`);
      const json = await res.json();

      if (!json.success || !json.data) {
        slotsContainer.innerHTML = '<div style="grid-column: 1/-1; color: #f87171; font-size: 0.8rem;">No se pudo verificar la disponibilidad.</div>';
        return;
      }

      const { bloqueado, motivo_bloqueo, todos_horarios, horarios_ocupados, horarios_disponibles } = json.data;

      // Si el día entero está bloqueado
      if (bloqueado) {
        if (bannerBloqueado) {
          if (motivoText) motivoText.textContent = motivo_bloqueo || 'Día bloqueado en el salón por mantenimiento o fecha especial.';
          bannerBloqueado.style.display = 'flex';
        }
        slotsContainer.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: #f87171; font-size: 0.825rem; padding: 1rem;">No hay turnos disponibles para esta fecha.</div>';
        if (btnSubmit) {
          btnSubmit.disabled = true;
          btnSubmit.textContent = 'Día no disponible';
        }
        if (labelHora) labelHora.textContent = 'Sin disponibilidad';
        return;
      }

      // Si el día está habilitado
      if (bannerBloqueado) bannerBloqueado.style.display = 'none';
      if (btnSubmit) {
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Agendar turno y Ver Seña';
      }

      // Si no quedan horarios libres
      if (!horarios_disponibles || horarios_disponibles.length === 0) {
        slotsContainer.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: #f87171; font-size: 0.825rem; padding: 1rem;">Todos los horarios de este día ya han sido reservados al 100%.</div>';
        if (btnSubmit) btnSubmit.disabled = true;
        if (labelHora) labelHora.textContent = 'Horarios agotados';
        return;
      }

      // Renderizar slots
      slotsContainer.innerHTML = todos_horarios.map(h => {
        const isOcupado = horarios_ocupados.includes(h);

        if (isOcupado) {
          return `
            <button type="button" class="time-slot time-slot--occupied" disabled title="Horario ocupado">
              <span class="time-slot__time">${h}</span>
              <span class="time-slot__badge">Ocupado</span>
            </button>
          `;
        }

        const isSelected = (h === selectedHour);
        return `
          <button type="button" class="time-slot ${isSelected ? 'time-slot--selected' : ''}" data-hora="${h}">
            <span class="time-slot__time">${h}</span>
          </button>
        `;
      }).join('');

      // Auto-seleccionar primer horario disponible si el actual quedó ocupado
      if (horarios_ocupados.includes(selectedHour)) {
        selectedHour = horarios_disponibles[0];
        if (horaInput) horaInput.value = selectedHour;
        const newSelectedBtn = slotsContainer.querySelector(`[data-hora="${selectedHour}"]`);
        if (newSelectedBtn) newSelectedBtn.classList.add('time-slot--selected');
      }

      if (labelHora) {
        labelHora.textContent = `Horario elegido: ${selectedHour} hs`;
      }

      // Event listeners para los botones de slots
      slotsContainer.querySelectorAll('.time-slot:not(.time-slot--occupied)').forEach(btn => {
        btn.addEventListener('click', () => {
          slotsContainer.querySelectorAll('.time-slot').forEach(b => b.classList.remove('time-slot--selected'));
          btn.classList.add('time-slot--selected');
          selectedHour = btn.dataset.hora;
          if (horaInput) horaInput.value = selectedHour;
          if (labelHora) labelHora.textContent = `Horario elegido: ${selectedHour} hs`;
        });
      });

    } catch (e) {
      console.error('Error al verificar disponibilidad:', e);
      slotsContainer.innerHTML = '<div style="grid-column: 1/-1; color: #f87171; font-size: 0.8rem;">Error de conexión al cargar horarios.</div>';
    }
  }

  // -------------------------------------------------------------
  // 2. Cargar Mis Servicios Contratados & Cálculo de Seña
  // -------------------------------------------------------------
  async function loadMisServicios() {
    if (!listaContratados) return;

    const email = form.querySelector('#agenda-email')?.value.trim() || '';

    try {
      const res = await fetch(`api/agenda.php?action=mis_servicios&email=${encodeURIComponent(email)}`);
      const json = await res.json();

      if (!json.success || !json.data || !json.data.citas || json.data.citas.length === 0) {
        listaContratados.innerHTML = `
          <div style="text-align: center; padding: 2.5rem 1rem; color: var(--color-text-muted); font-size: 0.875rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 0.5rem; opacity: 0.5;"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
            <p>Aún no tienes servicios agendados.<br>Completa el formulario para reservar tu cita.</p>
          </div>
        `;
        if (resumenBox) resumenBox.style.display = 'none';
        firstPendingCitaId = null;
        return;
      }

      const { citas, costo_total, seña_total } = json.data;
      firstPendingCitaId = null;

      listaContratados.innerHTML = citas.map(c => {
        const isAbonada = c.sena_abonada;
        if (!isAbonada && !firstPendingCitaId) {
          firstPendingCitaId = c.id_cita;
        }

        const badge = isAbonada
          ? `<span class="badge" style="background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.4); font-size: 0.725rem;">Confirmado · Seña Pagada</span>`
          : `<span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); font-size: 0.725rem;">Pendiente de Seña</span>`;

        const payButton = !isAbonada
          ? `<a href="mercadopago-checkout.php?id_cita=${c.id_cita}" class="btn-pill btn-pill--sm" style="background: #009ee3; color: #fff; font-size: 0.75rem; text-decoration: none; padding: 0.35rem 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.3rem;">Pagar Seña ($ ${formatNumber(c.monto_sena)})</a>`
          : `<span style="font-size: 0.75rem; color: #4ade80;">✓ Cupo asegurado</span>`;

        return `
          <div class="contracted-service-item">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.35rem;">
              <div class="contracted-service-title">${escapeHtml(c.servicio)}</div>
              ${badge}
            </div>
            <div class="contracted-service-meta">
              <span>📅 ${escapeHtml(c.fecha)} a las ${escapeHtml(c.hora)} hs</span>
              <span>👤 ${escapeHtml(c.especialista)}</span>
              <span>📍 ${escapeHtml(c.modalidad)}</span>
            </div>
            <div class="contracted-price-breakdown">
              <div>
                <span style="color: var(--color-text-muted); font-size: 0.8rem;">Costo: $ ${formatNumber(c.precio_total)}</span> · 
                <strong style="color: #4ade80;">Seña (30%): $ ${formatNumber(c.monto_sena)}</strong>
              </div>
              <div>
                ${payButton}
              </div>
            </div>
          </div>
        `;
      }).join('');

      // Mostrar caja de resumen de costos
      if (resumenBox) {
        resumenBox.style.display = 'block';
        if (totalCostoEl) totalCostoEl.textContent = `$ ${formatNumber(costo_total)}`;
        if (totalSenaEl) totalSenaEl.textContent = `$ ${formatNumber(seña_total)}`;
        if (btnProceedMp) {
          if (firstPendingCitaId) {
            btnProceedMp.href = `mercadopago-checkout.php?id_cita=${firstPendingCitaId}`;
            btnProceedMp.style.display = 'flex';
          } else {
            btnProceedMp.style.display = 'none';
          }
        }
      }

    } catch (e) {
      console.error('Error cargando servicios contratados:', e);
    }
  }

  // -------------------------------------------------------------
  // 3. Envío del Formulario de Reserva
  // -------------------------------------------------------------
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const nombre = form.querySelector('#agenda-nombre')?.value.trim() || '';
    const apellido = form.querySelector('#agenda-apellido')?.value.trim() || '';
    const email = form.querySelector('#agenda-email')?.value.trim() || '';
    const telefono = form.querySelector('#agenda-telefono')?.value.trim() || '';
    const servicio = form.querySelector('#agenda-servicio')?.value || '';
    const especialista = form.querySelector('#agenda-especialista')?.value || 'Cualquier especialista disponible';
    const modalidad = form.querySelector('#agenda-modalidad')?.value || 'Presencial';
    const fecha = fechaInput ? fechaInput.value : '';
    const hora = selectedHour;
    const observaciones = form.querySelector('#agenda-observaciones')?.value.trim() || '-';

    if (!nombre || !apellido || !email || !telefono || !servicio) {
      showFormAlert(form, 'Por favor completa todos los campos obligatorios marcados con (*).', 'error');
      return;
    }

    if (!fecha || !hora) {
      showFormAlert(form, 'Por favor selecciona una fecha y un horario disponible en el selector.', 'warning');
      return;
    }

    const originalText = btnSubmit ? btnSubmit.textContent : 'Agendar turno';
    if (btnSubmit) {
      btnSubmit.disabled = true;
      btnSubmit.textContent = 'Verificando y reservando...';
    }

    try {
      const res = await fetch('api/agenda.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          nombre,
          apellido,
          email,
          telefono,
          servicio,
          empleado: especialista,
          fecha,
          hora,
          modalidad,
          observaciones
        })
      });

      const result = await res.json();

      if (!result.success) {
        showFormAlert(form, result.message || 'No se pudo reservar el turno.', 'error');
        if (btnSubmit) {
          btnSubmit.disabled = false;
          btnSubmit.textContent = originalText;
        }
        return;
      }

      if (btnSubmit) {
        btnSubmit.textContent = '¡Turno Agendado!';
        btnSubmit.style.background = '#22c55e';
      }

      // Refrescar disponibilidad de horarios para que el turno tomado quede ocupado de inmediato
      await checkDisponibilidad(fecha);

      // Refrescar panel de servicios contratados
      await loadMisServicios();

      // Disparar animación de confirmación elegante
      await showConfirmationAnimation({
        title: '¡Turno Agendado!',
        subtitle: `Tu sesión de ${result.data.servicio} está agendada para el ${result.data.fecha} a las ${result.data.hora} hs.`,
        details: `Seña requerida: $${formatNumber(result.data.monto_sena)} (Total: $${formatNumber(result.data.precio_total)})`,
        duration: 2600,
        onComplete: () => {
          if (result.data.checkout_url) {
            window.location.href = result.data.checkout_url;
          }
        }
      });

    } catch (err) {
      console.error(err);
      showFormAlert(form, 'Error de conexión al agendar el turno: ' + (err.message || 'Intente nuevamente más tarde.'), 'error');
      if (btnSubmit) {
        btnSubmit.disabled = false;
        btnSubmit.textContent = originalText;
      }
    }
  });

  // Listeners de cambio de fecha, servicio, especialista e email
  if (fechaInput) {
    fechaInput.addEventListener('change', () => {
      checkDisponibilidad(fechaInput.value);
    });
  }

  if (servicioSelect) {
    servicioSelect.addEventListener('change', () => {
      updateEspecialistasSelect();
      if (fechaInput && fechaInput.value) {
        checkDisponibilidad(fechaInput.value);
      }
    });
  }

  if (especialistaSelect) {
    especialistaSelect.addEventListener('change', () => {
      updateModalidadOptions();
      if (fechaInput && fechaInput.value) {
        checkDisponibilidad(fechaInput.value);
      }
    });
  }

  const emailInput = form.querySelector('#agenda-email');
  if (emailInput) {
    emailInput.addEventListener('blur', () => {
      loadMisServicios();
    });
  }

  if (btnRefreshServicios) {
    btnRefreshServicios.addEventListener('click', () => {
      loadMisServicios();
    });
  }

  // Carga inicial
  updateEspecialistasSelect();
  updateModalidadOptions();
  if (fechaInput && fechaInput.value) {
    checkDisponibilidad(fechaInput.value);
  }
  loadMisServicios();
}

function formatNumber(num) {
  if (!num) return '0';
  return Number(num).toLocaleString('es-AR');
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
