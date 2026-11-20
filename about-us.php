<?php
/**
 * ARCANA — About Us Page (about-us.php)
 */

$pageTitle = 'Nosotros — Arcana Moon Beauty Secret';
$pageDescription = 'Conoce la historia, el equipo y la filosofía detrás de Arcana Moon Beauty Secret.';
$pageName = 'about';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/JsonDB.php';

$db = JsonDB::getInstance();

// Obtener empleados de la base de datos
$empleadosRaw = $db->getTable('empleado') ?? [];
$empleados = [];

foreach ($empleadosRaw as $emp) {
    $id = (int)($emp['id_empleado'] ?? 0);
    if (!$id) continue;
    if (isset($emp['activo']) && (int)$emp['activo'] === 0) continue;

    // Obtener especialidades del empleado
    $relaciones = $db->find('empleado_especialidad', function($ee) use ($id) {
        return (int)($ee['id_empleado'] ?? 0) === $id;
    });

    $especialidadesNombres = [];
    foreach ($relaciones as $rel) {
        $espId = (int)($rel['id_especialidad'] ?? 0);
        $esp = $db->getById('especialidad', 'id_especialidad', $espId);
        if ($esp && !empty($esp['nombre_especialidad'])) {
            $especialidadesNombres[] = $esp['nombre_especialidad'];
        }
    }

    // Modalidad asignada
    $modalidad = $emp['modalidad'] ?? null;
    if (!$modalidad) {
        $servemps = $db->find('servicio_empleado', function($se) use ($id) {
            return (int)($se['id_empleado'] ?? 0) === $id;
        });
        $modalidad = !empty($servemps) ? ($servemps[0]['modalidad_disponib'] ?? 'Presencial') : 'Presencial';
    }

    $emp['modalidad'] = $modalidad;
    $emp['especialidades'] = $especialidadesNombres;
    $empleados[] = $emp;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="about-page" style="padding-top: 100px; padding-bottom: 3rem;">
  <div class="container">
    <!-- Story -->
    <div class="glass story__inner reveal">
      <img src="assets/images/salon-interior.jpg" alt="Interior del salón Arcana" class="story__image">
      <div class="story__content">
        <p class="story__label">NUESTRA HISTORIA</p>
        <h1 class="story__title text-section">Donde el cuidado se convierte en arte</h1>
        <p class="story__body">Arcana nació con la convicción de que cada persona merece un espacio donde sentirse en calma y renovarse por completo. Más que un salón, somos un refugio de bienestar pensado con intención, con materiales de primera calidad y un equipo apasionado que ama lo que hace.</p>
        <div class="story__stats">
          <div class="story__stat">
            <div class="story__stat-number" data-count="1">1+</div>
            <div class="story__stat-label">Años de experiencia</div>
          </div>
          <div class="story__stat">
            <div class="story__stat-number" data-count="30">30+</div>
            <div class="story__stat-label">Clientes activos</div>
          </div>
          <div class="story__stat">
            <div class="story__stat-number" data-count="18">18+</div>
            <div class="story__stat-label">Servicios disponibles</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Team -->
    <section class="team" style="margin-top: 3rem;">
      <h2 class="text-section text-section-lg" style="text-align: center;">Nuestro equipo</h2>
      <div class="team__grid">
        <?php if (!empty($empleados)): ?>
          <?php foreach ($empleados as $index => $emp): 
            $nombreCompleto = trim(($emp['nombre'] ?? '') . ' ' . ($emp['apellido'] ?? ''));
            $foto = !empty($emp['imagenEmpleado']) ? $emp['imagenEmpleado'] : (!empty($emp['foto']) ? $emp['foto'] : 'assets/images/team-avatar.jpg');
            $especialidadesTexto = !empty($emp['especialidades']) ? implode(' • ', $emp['especialidades']) : 'Especialista en Belleza';
            $modalidad = !empty($emp['modalidad']) ? $emp['modalidad'] : 'Especialista';
            $delay = number_format(($index % 4) * 0.1, 1, '.', '');
          ?>
          <div class="team-card reveal" data-reveal-delay="<?= $delay ?>">
            <div class="team-card__avatar">
              <img src="<?= htmlspecialchars($foto) ?>" alt="<?= htmlspecialchars($nombreCompleto) ?>" onerror="this.src='assets/images/team-avatar.jpg'">
            </div>
            <h3 class="team-card__name"><?= htmlspecialchars($nombreCompleto) ?></h3>
            <p class="team-card__role"><?= htmlspecialchars($especialidadesTexto) ?></p>
            <span class="team-card__badge"><?= htmlspecialchars($modalidad) ?></span>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="text-align: center; color: var(--color-text-muted); grid-column: 1 / -1;">No hay miembros del equipo disponibles en este momento.</p>
        <?php endif; ?>
      </div>
    </section>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
