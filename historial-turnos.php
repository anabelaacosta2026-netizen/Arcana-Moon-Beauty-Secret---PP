<?php
/**
 * ARCANA — Historial de Turnos del Usuario (historial-turnos.php)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/JsonDB.php';
require_once __DIR__ . '/config/session.php';

// Requiere inicio de sesión
if (!isLoggedIn()) {
    header('Location: login.php?redirect=historial-turnos.php');
    exit;
}

$currentUser = getCurrentUser();
$userId = (int)($currentUser['id_usuario'] ?? $currentUser['id'] ?? 0);
$db = JsonDB::getInstance();

$pageTitle = 'Historial de Turnos — Arcana Moon Beauty Secret';
$pageDescription = 'Consulta todos tus turnos reservados, estados, comprobantes y fechas anteriores.';
$pageName = 'historial-turnos';

// Obtener todas las citas del usuario
$citasUsuario = $db->find('citas', function($c) use ($userId) {
    return (int)($c['id_usuario'] ?? 0) === $userId;
});

// Ordenar cronológicamente descendente (más recientes primero)
usort($citasUsuario, function($a, $b) {
    return strcmp($b['fecha_hora'] ?? '', $a['fecha_hora'] ?? '');
});

// Enriquecer citas con datos de servicio, empleado, precios
$citasEnriquecidas = [];
$totalConfirmados = 0;
$totalPendientes = 0;
$totalCompletados = 0;
$totalCancelados = 0;
$proximoTurno = null;
$ahora = date('Y-m-d H:i:s');

foreach ($citasUsuario as $c) {
    $idCita = (int)$c['id_cita'];

    // Relación cita_servicio -> servicio_empleado
    $citaServicios = $db->find('cita_servicio', function($cs) use ($idCita) {
        return (int)$cs['id_cita'] === $idCita;
    });

    $servicioNombre = 'Servicio Especial';
    $servicioImg = 'assets/images/service-manicure.jpg';
    $servicioCat = 'Estética';
    $servicioDuracion = 60;
    $empleadoNombre = 'Especialista';
    $precio = 25000;
    $idServicio = 1;

    if (!empty($citaServicios)) {
        $idSE = (int)$citaServicios[0]['id_servicio_empleado'];
        $se = $db->getById('servicio_empleado', 'id_servicio_empleado', $idSE);
        if ($se) {
            $idServicio = (int)$se['id_servicio'];
            $srv = $db->getById('servicio', 'id_servicio', $idServicio);
            if ($srv) {
                $servicioNombre = $srv['nombre_servicio'];
                $servicioImg = !empty($srv['imagen_servicio']) ? $srv['imagen_servicio'] : 'assets/images/service-manicure.jpg';
                $servicioCat = $srv['categoria'] ?? 'Estética';
                $servicioDuracion = (int)($srv['duracion_minutos'] ?? 60);

                // Precio
                $precios = $db->find('precio_servicio', function($p) use ($idServicio) {
                    return (int)$p['id_servicio'] === $idServicio;
                });
                if (!empty($precios)) {
                    $ultimo = end($precios);
                    $precio = (float)($ultimo['precio'] ?? 25000);
                }
            }

            $emp = $db->getById('empleado', 'id_empleado', (int)$se['id_empleado']);
            if ($emp) {
                $empleadoNombre = $emp['nombre'] . ' ' . $emp['apellido'];
            }
        }
    }

    // Espacio
    $espacioNombre = 'Cabina Principal';
    if (!empty($c['id_espacio'])) {
        $esp = $db->getById('espacio', 'id_espacio', (int)$c['id_espacio']);
        if ($esp) $espacioNombre = $esp['nombre'];
    }

    $estado = strtolower(trim($c['estado'] ?? 'pendiente'));
    if ($estado === 'confirmado') $totalConfirmados++;
    elseif ($estado === 'pendiente') $totalPendientes++;
    elseif ($estado === 'completado') $totalCompletados++;
    elseif ($estado === 'cancelado') $totalCancelados++;

    // Verificar próximo turno
    if (($estado === 'confirmado' || $estado === 'pendiente') && ($c['fecha_hora'] ?? '') >= $ahora) {
        if ($proximoTurno === null || ($c['fecha_hora'] < $proximoTurno['fecha_hora'])) {
            $proximoTurno = [
                'servicio' => $servicioNombre,
                'fecha_hora' => $c['fecha_hora'],
                'estado' => $estado
            ];
        }
    }

    $senaAbonada = ($estado === 'confirmado' || $estado === 'completado' || str_contains(strtolower($c['observaciones'] ?? ''), 'seña abonada') || str_contains(strtolower($c['observaciones'] ?? ''), 'seña pagada'));
    $montoSena = round($precio * 0.30);

    $citasEnriquecidas[] = [
        'id_cita' => $idCita,
        'id_servicio' => $idServicio,
        'servicio' => $servicioNombre,
        'imagen' => $servicioImg,
        'categoria' => $servicioCat,
        'duracion' => $servicioDuracion,
        'empleado' => $empleadoNombre,
        'espacio' => $espacioNombre,
        'fecha_hora' => $c['fecha_hora'],
        'estado' => $estado,
        'modalidad' => $c['modalidad'] ?? 'Presencial',
        'observaciones' => $c['observaciones'] ?? '',
        'precio' => $precio,
        'monto_sena' => $montoSena,
        'sena_abonada' => $senaAbonada,
        'creada_en' => $c['creada_en'] ?? ''
    ];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="historial-page" style="padding-top: 100px; padding-bottom: 4rem; min-height: 85vh;">
  <div class="container">

    <!-- Header & Breadcrumbs -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
      <div>
        <div style="font-size: 0.85rem; color: var(--color-accent); margin-bottom: 0.25rem; font-weight: 500;">
          Mi Cuenta &bull; Panel de Cliente
        </div>
        <h1 class="text-section text-section-xl" style="margin-bottom: 0.25rem;">Historial de Turnos</h1>
        <p style="color: var(--color-text-muted); font-size: 0.95rem;">
          Revisa todos los turnos que reservaste anteriormente, estados y detalles de cada sesión.
        </p>
      </div>

      <div style="display: flex; gap: 0.75rem;">
        <a href="agenda.php" class="btn-pill btn-pill--primary" style="display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none;">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Reservar Nuevo Turno
        </a>
      </div>
    </div>

    <!-- Métricas del usuario -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
      <div class="glass" style="padding: 1.25rem; border-radius: var(--radius-md, 12px); display: flex; align-items: center; gap: 1rem;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(196, 84, 126, 0.15); display: flex; align-items: center; justify-content: center; color: var(--color-accent);">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
        </div>
        <div>
          <div style="font-size: 0.8rem; color: var(--color-text-muted);">Total Reservados</div>
          <div style="font-size: 1.5rem; font-weight: 700;"><?= count($citasEnriquecidas) ?></div>
        </div>
      </div>

      <div class="glass" style="padding: 1.25rem; border-radius: var(--radius-md, 12px); display: flex; align-items: center; gap: 1rem;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(46, 213, 115, 0.15); display: flex; align-items: center; justify-content: center; color: #2ed573;">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        </div>
        <div>
          <div style="font-size: 0.8rem; color: var(--color-text-muted);">Confirmados</div>
          <div style="font-size: 1.5rem; font-weight: 700; color: #2ed573;"><?= $totalConfirmados ?></div>
        </div>
      </div>

      <div class="glass" style="padding: 1.25rem; border-radius: var(--radius-md, 12px); display: flex; align-items: center; gap: 1rem;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(255, 171, 0, 0.15); display: flex; align-items: center; justify-content: center; color: #ffab00;">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
        </div>
        <div>
          <div style="font-size: 0.8rem; color: var(--color-text-muted);">Pendientes</div>
          <div style="font-size: 1.5rem; font-weight: 700; color: #ffab00;"><?= $totalPendientes ?></div>
        </div>
      </div>

      <div class="glass" style="padding: 1.25rem; border-radius: var(--radius-md, 12px); display: flex; align-items: center; gap: 1rem;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(52, 152, 219, 0.15); display: flex; align-items: center; justify-content: center; color: #3498db;">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
          </svg>
        </div>
        <div>
          <div style="font-size: 0.8rem; color: var(--color-text-muted);">Completados</div>
          <div style="font-size: 1.5rem; font-weight: 700; color: #3498db;"><?= $totalCompletados ?></div>
        </div>
      </div>
    </div>

    <!-- Barra de Filtros por Estado -->
    <div class="glass" style="padding: 0.75rem 1.25rem; border-radius: var(--radius-md, 12px); margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
      <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;" id="status-filters">
        <button class="btn-pill btn-pill--sm filter-btn active" data-filter="all" style="font-size: 0.82rem; padding: 0.4rem 1rem;">
          Todos (<?= count($citasEnriquecidas) ?>)
        </button>
        <button class="btn-pill btn-pill--sm filter-btn" data-filter="confirmado" style="font-size: 0.82rem; padding: 0.4rem 1rem;">
          ✓ Confirmados (<?= $totalConfirmados ?>)
        </button>
        <button class="btn-pill btn-pill--sm filter-btn" data-filter="pendiente" style="font-size: 0.82rem; padding: 0.4rem 1rem;">
          ⏳ Pendientes (<?= $totalPendientes ?>)
        </button>
        <button class="btn-pill btn-pill--sm filter-btn" data-filter="completado" style="font-size: 0.82rem; padding: 0.4rem 1rem;">
          ⭐ Completados (<?= $totalCompletados ?>)
        </button>
        <button class="btn-pill btn-pill--sm filter-btn" data-filter="cancelado" style="font-size: 0.82rem; padding: 0.4rem 1rem;">
          ✕ Cancelados (<?= $totalCancelados ?>)
        </button>
      </div>

      <!-- Buscador rápido dentro del historial -->
      <div style="position: relative; min-width: 220px;">
        <input type="text" id="historial-search" class="form-input" placeholder="Buscar por servicio..." style="padding-left: 2rem; font-size: 0.85rem; height: 36px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 10px; top: 11px; color: var(--color-text-muted);">
          <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </div>
    </div>

    <!-- Contenedor del listado de turnos -->
    <?php if (empty($citasEnriquecidas)): ?>
      <!-- EMPTY STATE -->
      <div class="glass" style="padding: 4rem 2rem; text-align: center; border-radius: var(--radius-card, 16px);">
        <div style="width: 72px; height: 72px; margin: 0 auto 1.5rem; border-radius: 50%; background: rgba(196, 84, 126, 0.12); display: flex; align-items: center; justify-content: center; color: var(--color-accent);">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
        </div>
        <h3 style="font-size: 1.35rem; margin-bottom: 0.5rem;">Aún no tienes turnos reservados</h3>
        <p style="color: var(--color-text-muted); max-width: 440px; margin: 0 auto 1.75rem; font-size: 0.95rem;">
          Cuando reserves una cita para nuestros tratamientos de manicura, faciales, masajes o pestañas, podrás realizar el seguimiento aquí.
        </p>
        <a href="agenda.php" class="btn-pill btn-pill--primary">
          Ver Agenda y Reservar Turno
        </a>
      </div>
    <?php else: ?>
      <div id="turnos-list" style="display: flex; flex-direction: column; gap: 1rem;">
        <?php foreach ($citasEnriquecidas as $item): ?>
          <?php
            $fechaObj = DateTime::createFromFormat('Y-m-d H:i:s', $item['fecha_hora']);
            $fechaTexto = $fechaObj ? $fechaObj->format('d/m/Y') : substr($item['fecha_hora'], 0, 10);
            $horaTexto = $fechaObj ? $fechaObj->format('H:i') . ' hs' : substr($item['fecha_hora'], 11, 5) . ' hs';
            
            // Días de la semana en español
            $dias = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado'];
            $diaSemana = $fechaObj ? ($dias[$fechaObj->format('l')] ?? '') : '';

            // Color del badge
            $badgeBg = 'rgba(255, 171, 0, 0.15)';
            $badgeColor = '#ffab00';
            $badgeBorder = 'rgba(255, 171, 0, 0.35)';
            $badgeLabel = 'Pendiente';

            if ($item['estado'] === 'confirmado') {
                $badgeBg = 'rgba(46, 213, 115, 0.15)';
                $badgeColor = '#2ed573';
                $badgeBorder = 'rgba(46, 213, 115, 0.35)';
                $badgeLabel = 'Confirmado';
            } elseif ($item['estado'] === 'completado') {
                $badgeBg = 'rgba(52, 152, 219, 0.15)';
                $badgeColor = '#3498db';
                $badgeBorder = 'rgba(52, 152, 219, 0.35)';
                $badgeLabel = 'Completado';
            } elseif ($item['estado'] === 'cancelado') {
                $badgeBg = 'rgba(255, 107, 107, 0.15)';
                $badgeColor = '#ff6b6b';
                $badgeBorder = 'rgba(255, 107, 107, 0.35)';
                $badgeLabel = 'Cancelado';
            }
          ?>
          <div class="turno-card glass" data-estado="<?= htmlspecialchars($item['estado']) ?>" data-servicio="<?= htmlspecialchars(mb_strtolower($item['servicio'])) ?>" style="padding: 1.5rem; border-radius: var(--radius-card, 16px); transition: transform 0.2s ease, border-color 0.2s ease;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.25rem;">
              
              <!-- Columna izquierda: Fecha destacada y foto del servicio -->
              <div style="display: flex; gap: 1.25rem; align-items: center; min-width: 260px;">
                <!-- Mini calendario -->
                <div style="width: 72px; height: 72px; border-radius: 12px; background: rgba(255, 255, 255, 0.04); border: 1px solid var(--color-glass-border); display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; flex-shrink: 0;">
                  <span style="font-size: 0.7rem; color: var(--color-accent); text-transform: uppercase; font-weight: 600; line-height: 1;">
                    <?= $fechaObj ? $fechaObj->format('M') : 'MES' ?>
                  </span>
                  <span style="font-size: 1.5rem; font-weight: 700; line-height: 1.1;">
                    <?= $fechaObj ? $fechaObj->format('d') : '00' ?>
                  </span>
                  <span style="font-size: 0.65rem; color: var(--color-text-muted);">
                    <?= $horaTexto ?>
                  </span>
                </div>

                <!-- Info servicio -->
                <div>
                  <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                    <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-accent); font-weight: 600; letter-spacing: 0.5px;">
                      <?= htmlspecialchars($item['categoria']) ?>
                    </span>
                    <span style="color: var(--color-glass-border);">&bull;</span>
                    <span style="font-size: 0.78rem; color: var(--color-text-muted);">
                      Turno #<?= $item['id_cita'] ?>
                    </span>
                  </div>

                  <h3 style="font-size: 1.15rem; margin: 0 0 0.35rem 0; font-weight: 600;">
                    <a href="servicio.php?id=<?= $item['id_servicio'] ?>" style="color: inherit; text-decoration: none;" title="Ver detalles y cuidados del servicio">
                      <?= htmlspecialchars($item['servicio']) ?>
                    </a>
                  </h3>

                  <div style="display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.82rem; color: var(--color-text-muted);">
                    <span>📅 <?= $diaSemana ?>, <?= $fechaTexto ?> a las <?= $horaTexto ?></span>
                    <span>⏱ <?= $item['duracion'] ?> min</span>
                    <span>👤 Especialista: <strong style="color: var(--color-text);"><?= htmlspecialchars($item['empleado']) ?></strong></span>
                    <span>📍 <?= htmlspecialchars($item['modalidad']) ?> (<?= htmlspecialchars($item['espacio']) ?>)</span>
                  </div>
                </div>
              </div>

              <!-- Columna derecha: Estado y Precios / Seña -->
              <div style="display: flex; flex-direction: column; align-items: flex-end; justify-content: space-between; gap: 0.75rem; text-align: right;">
                <!-- Badge de Estado -->
                <div style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.85rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; border: 1px solid <?= $badgeBorder ?>;">
                  <span style="width: 7px; height: 7px; border-radius: 50%; background: currentColor;"></span>
                  <?= $badgeLabel ?>
                </div>

                <!-- Resumen económico -->
                <div>
                  <div style="font-size: 0.8rem; color: var(--color-text-muted);">Costo total: <strong style="color: var(--color-text);">$<?= number_format($item['precio'], 0, ',', '.') ?></strong></div>
                  
                  <?php if ($item['sena_abonada']): ?>
                    <div style="font-size: 0.8rem; color: #2ed573; font-weight: 500;">
                      ✓ Seña abonada ($<?= number_format($item['monto_sena'], 0, ',', '.') ?>)
                    </div>
                  <?php else: ?>
                    <div style="font-size: 0.8rem; color: #ffab00;">
                      Seña pendiente: $<?= number_format($item['monto_sena'], 0, ',', '.') ?>
                    </div>
                  <?php endif; ?>
                </div>

                <!-- Botones contextuales -->
                <div style="display: flex; gap: 0.5rem; margin-top: 0.25rem; flex-wrap: wrap;">
                  <a href="servicio.php?id=<?= $item['id_servicio'] ?>" class="btn-pill btn-pill--sm btn-pill--outline" style="font-size: 0.78rem; padding: 0.3rem 0.75rem; text-decoration: none;">
                    Ver Cuidados
                  </a>

                  <?php if ($item['estado'] === 'completado'): ?>
                    <a href="servicio.php?id=<?= $item['id_servicio'] ?>&review=1" class="btn-pill btn-pill--sm btn-pill--primary" style="font-size: 0.78rem; padding: 0.3rem 0.75rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem;">
                      <span>⭐</span> Calificar
                    </a>
                  <?php endif; ?>

                  <?php if ($item['estado'] === 'pendiente' && !$item['sena_abonada']): ?>
                    <a href="mercadopago-checkout.php?cita_id=<?= $item['id_cita'] ?>" class="btn-pill btn-pill--sm btn-pill--accent" style="font-size: 0.78rem; padding: 0.3rem 0.75rem; text-decoration: none;">
                      Pagar Seña MP
                    </a>
                  <?php endif; ?>
                </div>

              </div>

            </div>

            <!-- Observaciones / Comprobante si existen -->
            <?php if (!empty($item['observaciones']) && $item['observaciones'] !== '-'): ?>
              <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed var(--color-glass-border); font-size: 0.82rem; color: var(--color-text-muted);">
                <strong>Nota / Comprobante:</strong> <?= htmlspecialchars($item['observaciones']) ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Empty state dinámico al filtrar si no hay resultados -->
      <div id="no-filter-results" class="glass" style="display: none; padding: 3rem 1.5rem; text-align: center; border-radius: var(--radius-card, 16px); margin-top: 1rem;">
        <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0;">
          No se encontraron turnos con el filtro seleccionado.
        </p>
      </div>
    <?php endif; ?>

  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const filterBtns = document.querySelectorAll('.filter-btn');
  const cards = document.querySelectorAll('.turno-card');
  const searchInput = document.getElementById('historial-search');
  const noResultsBox = document.getElementById('no-filter-results');

  let currentFilter = 'all';
  let searchQuery = '';

  function applyFilter() {
    let visibleCount = 0;

    cards.forEach(card => {
      const estado = card.getAttribute('data-estado') || '';
      const servicio = card.getAttribute('data-servicio') || '';

      const matchFilter = (currentFilter === 'all' || estado === currentFilter);
      const matchSearch = (!searchQuery || servicio.includes(searchQuery));

      if (matchFilter && matchSearch) {
        card.style.display = 'block';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    if (noResultsBox) {
      noResultsBox.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
    }
  }

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => {
        b.classList.remove('active');
        b.style.background = '';
        b.style.borderColor = '';
      });

      btn.classList.add('active');
      currentFilter = btn.getAttribute('data-filter');
      applyFilter();
    });
  });

  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      searchQuery = e.target.value.toLowerCase().trim();
      applyFilter();
    });
  }
});
</script>

<style>
.filter-btn.active {
  background: var(--color-accent) !important;
  color: #fff !important;
  border-color: var(--color-accent) !important;
}
.turno-card:hover {
  border-color: rgba(196, 84, 126, 0.4) !important;
  transform: translateY(-2px);
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
