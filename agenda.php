<?php
/**
 * ARCANA — Appointment Booking & Contracted Services Page (agenda.php)
 */

$pageTitle = 'Agenda y Servicios Contratados — Arcana Moon Beauty Secret';
$pageDescription = 'Reserva tu cita online, consulta tus servicios contratados y abona la seña con Mercado Pago.';
$pageName = 'agenda';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/JsonDB.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$db = JsonDB::getInstance();

// Cargar servicios activos y empleados desde JsonDB
$serviciosDb = $db->find('servicio', function($s) {
    return (int)($s['disponibilidad'] ?? 1) === 1;
});
usort($serviciosDb, function($a, $b) {
    return strcmp($a['nombre_servicio'] ?? '', $b['nombre_servicio'] ?? '');
});

$empleadosDb = $db->find('empleado', function($e) {
    return (int)($e['activo'] ?? 1) === 1;
});
usort($empleadosDb, function($a, $b) {
    return strcmp($a['nombre'] ?? '', $b['nombre'] ?? '');
});

$selectedServicioId = (int)($_GET['servicio_id'] ?? $_GET['id'] ?? 0);
$selectedServicioNombre = trim($_GET['servicio'] ?? '');

// Mapear servicios con sus especialistas habilitados
$serviciosConEspecialistas = [];
$especialistasIniciales = [];

foreach ($serviciosDb as $s) {
    $idS = (int)$s['id_servicio'];
    $servemps = $db->find('servicio_empleado', function($se) use ($idS) {
        return (int)$se['id_servicio'] === $idS;
    });

    $empsAsignados = [];
    $idsEmpYa = [];

    foreach ($servemps as $se) {
        $idEmp = (int)$se['id_empleado'];
        if (in_array($idEmp, $idsEmpYa)) continue;
        $emp = $db->getById('empleado', 'id_empleado', $idEmp);
        if ($emp && (int)($emp['activo'] ?? 1) === 1) {
            $idsEmpYa[] = $idEmp;
            $modalidadEmp = $emp['modalidad'] ?? ($se['modalidad_disponib'] ?? 'Presencial');
            $empsAsignados[] = [
                'id_empleado' => $idEmp,
                'nombre_completo' => $emp['nombre'] . ' ' . $emp['apellido'],
                'nombre' => $emp['nombre'],
                'apellido' => $emp['apellido'],
                'modalidad' => $modalidadEmp
            ];
        }
    }

    // Si no tiene asignación explícita, buscar por categoría / especialidad
    if (empty($empsAsignados)) {
        $catServicio = mb_strtolower($s['categoria'] ?? '');
        foreach ($empleadosDb as $emp) {
            $idEmp = (int)$emp['id_empleado'];
            if (in_array($idEmp, $idsEmpYa)) continue;
            $relEsp = $db->find('empleado_especialidad', function($ee) use ($idEmp) {
                return (int)$ee['id_empleado'] === $idEmp;
            });
            $match = false;
            foreach ($relEsp as $re) {
                $esp = $db->getById('especialidad', 'id_especialidad', (int)$re['id_especialidad']);
                if ($esp && (mb_stripos($esp['nombre_especialidad'], $catServicio) !== false || mb_stripos($catServicio, $esp['nombre_especialidad']) !== false)) {
                    $match = true;
                    break;
                }
            }
            if ($match) {
                $idsEmpYa[] = $idEmp;
                $modalidadEmp = $emp['modalidad'] ?? 'Presencial';
                $empsAsignados[] = [
                    'id_empleado' => $idEmp,
                    'nombre_completo' => $emp['nombre'] . ' ' . $emp['apellido'],
                    'nombre' => $emp['nombre'],
                    'apellido' => $emp['apellido'],
                    'modalidad' => $modalidadEmp
                ];
            }
        }
    }

    $serviciosConEspecialistas[$idS] = [
        'id_servicio' => $idS,
        'nombre_servicio' => $s['nombre_servicio'],
        'categoria' => $s['categoria'] ?? 'Estética',
        'especialistas' => $empsAsignados
    ];

    if (($selectedServicioId && $idS === $selectedServicioId) ||
        ($selectedServicioNombre && mb_strtolower($s['nombre_servicio']) === mb_strtolower($selectedServicioNombre))) {
        $especialistasIniciales = $empsAsignados;
    }
}

// Mapa de todos los empleados con sus modalidades
$empleadosMap = [];
foreach ($empleadosDb as $emp) {
    $empleadosMap[$emp['nombre'] . ' ' . $emp['apellido']] = [
        'id_empleado' => (int)$emp['id_empleado'],
        'nombre_completo' => $emp['nombre'] . ' ' . $emp['apellido'],
        'modalidad' => $emp['modalidad'] ?? 'Presencial'
    ];
}

$currentUser = getCurrentUser();
?>

<script>
  window.arcanaServiciosData = <?= json_encode($serviciosConEspecialistas, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  window.arcanaEmpleadosMap = <?= json_encode($empleadosMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>

<main class="agenda-page" style="padding-top: 100px; padding-bottom: 4rem;">
  <div class="container">
    
    <div class="agenda-layout">
      
      <!-- ============================================================== -->
      <!-- COLUMNA 1: Formulario de Reserva de Turno                      -->
      <!-- ============================================================== -->
      <div class="glass" style="padding: 2.25rem; border-radius: var(--radius-card, 16px);">
        <h1 class="text-section text-section-xl" style="margin-bottom: 0.35rem;">Reserva tu turno</h1>
        <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
          Completa tus datos, elige día y horario disponible para reservar tu lugar.
        </p>

        <form id="agenda-form" novalidate>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="agenda-nombre">Nombre *</label>
              <input type="text" id="agenda-nombre" class="form-input" placeholder="Nombre" required value="<?= htmlspecialchars($currentUser['nombre'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="agenda-apellido">Apellido *</label>
              <input type="text" id="agenda-apellido" class="form-input" placeholder="Apellido" required value="<?= htmlspecialchars($currentUser['apellido'] ?? '') ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="agenda-email">Email *</label>
              <input type="email" id="agenda-email" class="form-input" placeholder="correo@ejemplo.com" required value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="agenda-telefono">Teléfono *</label>
              <input type="tel" id="agenda-telefono" class="form-input" placeholder="11-1234-5678" required value="<?= htmlspecialchars($currentUser['telefono'] ?? '') ?>">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="agenda-servicio">Servicio *</label>
            <select id="agenda-servicio" class="form-select" required>
              <option value="">-- Seleccionar servicio --</option>
              <?php foreach ($serviciosDb as $s): ?>
                <?php
                  $isSelected = ($selectedServicioId && (int)$s['id_servicio'] === $selectedServicioId) ||
                                ($selectedServicioNombre && mb_strtolower($s['nombre_servicio']) === mb_strtolower($selectedServicioNombre));
                ?>
                <option value="<?= htmlspecialchars($s['nombre_servicio']) ?>" data-id="<?= $s['id_servicio'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                  <?= htmlspecialchars($s['nombre_servicio']) ?> (<?= htmlspecialchars($s['categoria'] ?? 'Estética') ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="agenda-especialista">Especialista a cargo</label>
              <select id="agenda-especialista" class="form-select">
                <option value="Cualquier especialista disponible" data-modalidad="Mixto">-- Cualquier especialista disponible --</option>
                <?php if (!empty($especialistasIniciales)): ?>
                  <?php foreach ($especialistasIniciales as $emp): ?>
                    <option value="<?= htmlspecialchars($emp['nombre_completo']) ?>" data-modalidad="<?= htmlspecialchars($emp['modalidad'] ?? 'Presencial') ?>">
                      <?= htmlspecialchars($emp['nombre_completo']) ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
              <small id="especialista-help" style="display: block; margin-top: 4px; font-size: 0.75rem; color: var(--color-text-muted);">
                Solo se muestran los profesionales capacitados para el servicio seleccionado.
              </small>
            </div>
            <div class="form-group">
              <label class="form-label" for="agenda-modalidad">Modalidad</label>
              <select id="agenda-modalidad" class="form-select">
                <option value="Presencial">Presencial (En salón)</option>
                <option value="Domicilio">A Domicilio</option>
              </select>
              <small id="modalidad-help" style="display: block; margin-top: 4px; font-size: 0.75rem; color: var(--color-text-muted);">
                Modalidades ofrecidas por el especialista.
              </small>
            </div>
          </div>

          <!-- Selector de Fecha y Horarios Dinámicos -->
          <div class="form-group" style="margin-top: 0.5rem;">
            <label class="form-label" for="agenda-fecha">Fecha del Turno *</label>
            <input type="date" id="agenda-fecha" class="form-input" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
          </div>

          <!-- Alerta de Día Bloqueado -->
          <div id="banner-dia-bloqueado" class="day-blocked-banner" style="display: none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span id="motivo-bloqueo-text">Este día se encuentra completamente bloqueado o no laborable.</span>
          </div>

          <!-- Grilla de Horarios Dinámica -->
          <div class="form-group" id="group-horarios" style="margin-top: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <label class="form-label" style="margin: 0;">Horario Disponible *</label>
              <span id="label-hora-seleccionada" style="font-size: 0.8rem; color: var(--color-accent); font-weight: 600;">
                Selecciona una hora
              </span>
            </div>
            <input type="hidden" id="agenda-hora" value="10:00">
            <div class="time-slots-grid" id="time-slots-container">
              <!-- Renderizado dinámicamente vía JS -->
            </div>
          </div>

          <div class="form-group" style="margin-top: 1rem;">
            <label class="form-label" for="agenda-observaciones">Observaciones / Preferencias</label>
            <textarea id="agenda-observaciones" class="form-textarea" placeholder="¿Tienes alguna indicación especial para tu cita?"></textarea>
          </div>

          <button type="submit" id="btn-submit-agenda" class="btn-pill btn-pill--primary btn-pill--full" style="margin-top: 1.25rem;">
            Agendar turno y Ver Seña
          </button>
        </form>
      </div>

      <!-- ============================================================== -->
      <!-- COLUMNA 2: Mis Servicios Contratados & Pago de Seña             -->
      <!-- ============================================================== -->
      <div class="contracted-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
          <div>
            <h2 style="font-family: var(--font-display); font-size: 1.35rem; margin: 0; color: #fff;">Servicios Contratados</h2>
            <span style="font-size: 0.8rem; color: var(--color-text-muted);">Tus turnos reservados y señas</span>
          </div>
          <button type="button" id="btn-refresh-mis-servicios" class="btn-pill btn-pill--sm" style="background: rgba(255, 255, 255, 0.06); font-size: 0.75rem; padding: 0.3rem 0.65rem;" title="Actualizar mis citas">
            Actualizar
          </button>
        </div>

        <!-- Lista de Servicios Contratados -->
        <div id="lista-servicios-contratados">
          <div style="text-align: center; padding: 2.5rem 1rem; color: var(--color-text-muted); font-size: 0.875rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 0.5rem; opacity: 0.5;"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
            <p>Aún no tienes servicios agendados.<br>Completa el formulario para reservar tu cita.</p>
          </div>
        </div>

        <!-- Resumen de Costo Total y Seña -->
        <div id="resumen-sena-box" class="deposit-summary-box" style="display: none;">
          <div class="deposit-row">
            <span style="color: var(--color-text-secondary);">Costo Total de Servicios:</span>
            <strong id="resumen-costo-total" style="color: var(--color-text-primary);">$ 0</strong>
          </div>
          <div class="deposit-row">
            <span style="color: var(--color-text-secondary);">Monto de Seña Requerida (30%):</span>
            <strong id="resumen-monto-sena" style="color: #4ade80; font-size: 1.1rem;">$ 0</strong>
          </div>
          <div style="font-size: 0.75rem; color: var(--color-text-muted); margin-top: 0.25rem; line-height: 1.4;">
            * La seña es obligatoria para garantizar y asegurar el cupo en la agenda del salón. El saldo restante se cancela el día del turno.
          </div>

          <!-- Botón de Mercado Pago -->
          <a href="#" id="btn-proceder-mp" class="deposit-btn-mp">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
            </svg>
            Pagar Seña con Mercado Pago
          </a>
        </div>

      </div>

    </div>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
