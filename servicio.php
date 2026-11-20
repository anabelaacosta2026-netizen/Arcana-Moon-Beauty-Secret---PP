<?php
/**
 * ARCANA — Página Individual de Servicio (servicio.php)
 * Muestra información detallada, cuidados antes y después de realizar el tratamiento.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/JsonDB.php';
require_once __DIR__ . '/config/session.php';

$db = JsonDB::getInstance();

// 1. Obtener ID del servicio solicitado
$idServicio = (int)($_GET['id'] ?? $_GET['id_servicio'] ?? 0);

if (!$idServicio) {
    header('Location: services.php');
    exit;
}

// 2. Consultar servicio
$servicio = $db->getById('servicio', 'id_servicio', $idServicio);

if (!$servicio) {
    // Servicio no encontrado
    $pageTitle = 'Servicio No Encontrado — Arcana';
    require_once __DIR__ . '/includes/header.php';
    require_once __DIR__ . '/includes/navbar.php';
    ?>
    <main style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 120px 1rem 3rem;">
      <div class="glass" style="max-width: 500px; width: 100%; padding: 3rem 2rem; text-align: center; border-radius: var(--radius-card, 16px);">
        <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
        <h1 class="text-section" style="font-size: 1.5rem; margin-bottom: 0.5rem;">Servicio no encontrado</h1>
        <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-bottom: 1.75rem;">
          El servicio que buscas no existe o ha sido despublicado temporalmente.
        </p>
        <a href="services.php" class="btn-pill btn-pill--primary">Explorar Servicios</a>
      </div>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// 3. Obtener precio actual
$precios = $db->find('precio_servicio', function($p) use ($idServicio) {
    return (int)$p['id_servicio'] === $idServicio;
});
$precioActual = 25000;
if (!empty($precios)) {
    $ultimo = end($precios);
    $precioActual = (float)($ultimo['precio'] ?? 25000);
}
$montoSena = round($precioActual * 0.30);

// 4. Obtener especialistas
$servemps = $db->find('servicio_empleado', function($se) use ($idServicio) {
    return (int)$se['id_servicio'] === $idServicio;
});
$empleados = [];
foreach ($servemps as $se) {
    $emp = $db->getById('empleado', 'id_empleado', (int)$se['id_empleado']);
    if ($emp) {
        $empleados[] = $emp;
    }
}

// 5. Otros servicios relacionados
$relacionados = $db->find('servicio', function($s) use ($idServicio) {
    return (int)$s['id_servicio'] !== $idServicio && (int)($s['disponibilidad'] ?? 1) === 1;
});
$relacionados = array_slice($relacionados, 0, 3);

// 6. Guía de Cuidados según categoría o servicio
$categoria = $servicio['categoria'] ?? 'Estética';
$nombreLower = mb_strtolower($servicio['nombre_servicio']);

// Cuidados por defecto según categoría
$cuidadosAntes = [];
$cuidadosDespues = [];
$preguntasFrecuentes = [];

if (str_contains($nombreLower, 'kapping') || str_contains($nombreLower, 'esculpidas') || str_contains($nombreLower, 'uña') || $categoria === 'Manicura' || $categoria === 'Uñas') {
    $cuidadosAntes = [
        [
            'titulo' => 'No arrancar ni retirar producto previo con fuerza',
            'desc' => 'Si tienes restos de esmalte semipermanente o esculpidas anteriores, evita arrancarlas con los dientes o limas agresivas para no adelgazar las capas de queratina.'
        ],
        [
            'titulo' => 'No cortar las cutículas en seco 48 hs antes',
            'desc' => 'Déjalas intactas. En cabina realizamos una manicura combinada o rusa con instrumental esterilizado en autoclave.'
        ],
        [
            'titulo' => 'Asistir con uñas limpias y libres de crema densa',
            'desc' => 'Las cremas o aceites aplicados justo antes del turno pueden generar desprendimientos prematuros del gel debido a la oleosidad.'
        ],
        [
            'titulo' => 'Avisar alergias o sensibilidad química previa',
            'desc' => 'Comunícanos si has tenido reacciones adversas a primers o polímeros acrílicos para utilizar productos HEMA-Free.'
        ]
    ];

    $cuidadosDespues = [
        [
            'titulo' => 'Evitar agua muy caliente las primeras 24 horas',
            'desc' => 'No tomes baños de inmersión prolongados, saunas ni laves platos con agua hirviendo durante el primer día mientras el sellado molecular se asienta.'
        ],
        [
            'titulo' => 'Usar guantes para tareas domésticas y de limpieza',
            'desc' => 'Los productos químicos abrasivos (lavandina, cloro, desengrasantes) desgastan el brillo y debilitan la adherencia.'
        ],
        [
            'titulo' => 'No usar las uñas como herramientas',
            'desc' => 'Evita abrir latas, presionar botones duros o raspar superficies con la punta de las uñas para prevenir palancas y fracturas.'
        ],
        [
            'titulo' => 'Hidratar cutículas a diario con aceite nutritivo',
            'desc' => 'Aplica aceite de almendras o jojoba cada noche en el contorno ungueal para prolongar la elasticidad y nutrición.'
        ],
        [
            'titulo' => 'Service o retoque cada 18 a 21 días',
            'desc' => 'No dejes pasar más de 3 semanas para el mantenimiento para no alterar el centro de gravedad de la uña.'
        ]
    ];

    $preguntasFrecuentes = [
        ['p' => '¿Daña mi uña natural?', 'r' => 'No. Con técnicas modernas y remoción profesional, tu uña natural crece protegida bajo la capa de gel.'],
        ['p' => '¿Cuánto tiempo dura la sesión?', 'r' => 'Aproximadamente entre 50 y 120 minutos según la técnica elegida.']
    ];

} elseif (str_contains($nombreLower, 'lifting') || str_contains($nombreLower, 'pestaña') || str_contains($nombreLower, 'ceja') || $categoria === 'Pestañas' || $categoria === 'Cejas') {
    $cuidadosAntes = [
        [
            'titulo' => 'Asistir sin máscara ni maquillaje en los ojos',
            'desc' => 'Los restos de cera o aceites de cosméticos impiden la penetración adecuada de los geles fijadores y neutralizadores.'
        ],
        [
            'titulo' => 'Retirar lentes de contacto antes de iniciar',
            'desc' => 'Recomendamos asistir con anteojos o traer tu estuche para quitarte los lentes de contacto durante el procedimiento.'
        ],
        [
            'titulo' => 'No rizar las pestañas con arquero mecánico',
            'desc' => 'Evita arquearlas mecánicamente durante las 48 horas previas para no quebrar la fibra capilar.'
        ],
        [
            'titulo' => 'Informar antecedentes de conjuntivitis o blefaritis',
            'desc' => 'El área ocular debe estar completamente sana y libre de inflamaciones activas.'
        ]
    ];

    $cuidadosDespues = [
        [
            'titulo' => '¡Regla de oro! Cero agua ni vapor por 24 horas',
            'desc' => 'No mojes tus ojos, no te duches con vapor caliente, ni asistas al gimnasio o pileta durante las primeras 24 horas.'
        ],
        [
            'titulo' => 'Dormir boca arriba la primera noche',
            'desc' => 'Evita aplastar los ojos contra la almohada mientras la queratina termina de polimerizarse y fijar la curvatura.'
        ],
        [
            'titulo' => 'Peinar en seco diariamente',
            'desc' => 'Usa el cepillito tipo spoolie que te obsequiamos en el salón peinando suavemente hacia arriba.'
        ],
        [
            'titulo' => 'No utilizar desmaquillantes bifásicos o aceites',
            'desc' => 'Prefiere agua micelar libre de aceites para no desarmar la curvatura del lifting.'
        ],
        [
            'titulo' => 'Repetición recomendada',
            'desc' => 'El tratamiento tiene una durabilidad de 6 a 8 semanas, coincidiendo con el ciclo natural de renovación del pelo.'
        ]
    ];

    $preguntasFrecuentes = [
        ['p' => '¿Incluye tinte y nutrición?', 'r' => 'Sí, incluye tintura intensiva negra o castaña y baño de Botox de queratina para engrosar el vello.'],
        ['p' => '¿Puedo usar rímel luego?', 'r' => 'Sí, pasadas las 24 horas iniciales puedes maquillarte normalmente con máscaras al agua.']
    ];

} elseif (str_contains($nombreLower, 'limpieza') || str_contains($nombreLower, 'facial') || $categoria === 'Faciales') {
    $cuidadosAntes = [
        [
            'titulo' => 'Suspender exfoliantes y ácidos 3 días antes',
            'desc' => 'Evita utilizar ácido glicólico, salicílico, retinoico o exfoliantes granulados caseros 72 horas previas a la sesión.'
        ],
        [
            'titulo' => 'No depilarse con cera en el rostro 48 horas antes',
            'desc' => 'La depilación deja la piel reactiva; es preferible no sensibilizar la epidermis antes de la microdermoabrasión.'
        ],
        [
            'titulo' => 'No tomar sol directo ni camas solares',
            'desc' => 'La piel no debe presentar quemaduras solares ni irritación al momento de la extracción.'
        ],
        [
            'titulo' => 'Comentar medicamentos tópicos u orales',
            'desc' => 'Si estás bajo tratamiento con isotretinoína o corticoides avísale a la cosmetóloga.'
        ]
    ];

    $cuidadosDespues = [
        [
            'titulo' => 'Uso obligatorio de Protector Solar FPS 50+',
            'desc' => 'Reaplica protector solar cada 2 a 3 horas, incluso en interiores o días nublados para evitar hiperpigmentaciones.'
        ],
        [
            'titulo' => 'No maquillarse durante las primeras 12 a 24 horas',
            'desc' => 'Permite que los poros respiren y aprovechen al máximo los principios activos y sueros descongestivos aplicados.'
        ],
        [
            'titulo' => 'No tocarse ni rascarse el rostro',
            'desc' => 'Tus manos transportan bacterias. Evita pellizcar cualquier pequeña rojez posterior a la extracción.'
        ],
        [
            'titulo' => 'Hidratar intensamente con cremas humectantes',
            'desc' => 'Aplica geles descongestivos de aloe vera, manzanilla o crema humectante neutra para restaurar la barrera lipídica.'
        ],
        [
            'titulo' => 'Frecuencia de higiene recomendada',
            'desc' => 'Idealmente realizar una sesión cada 28 a 35 días para mantener los poros purificados y libres de puntos negros.'
        ]
    ];

    $preguntasFrecuentes = [
        ['p' => '¿Duele la extracción de puntos negros?', 'r' => 'Utilizamos ablandadores epidérmicos y puntas de diamante que hacen que el proceso sea muy cómodo y suave.'],
        ['p' => '¿Queda la cara muy roja?', 'r' => 'Aplicamos máscaras descongestivas y altafrecuencia para calmar la piel, saldrás fresca y lista para tu día.']
    ];

} else {
    // Masajes, Pedicura, Podología, Depilación o Corporal
    $cuidadosAntes = [
        [
            'titulo' => 'Hidratación abundante previa',
            'desc' => 'Bebe suficiente agua antes del tratamiento para optimizar la elasticidad de los tejidos y la circulación sanguínea.'
        ],
        [
            'titulo' => 'No ingerir comidas pesadas 1 hora antes',
            'desc' => 'Recomendamos un refrigerio liviano para mayor comodidad durante la relajación en camilla.'
        ],
        [
            'titulo' => 'Vestir prendas cómodas y holgadas',
            'desc' => 'Facilita la aplicación de óleos esenciales y evita marcas por elásticos ajustados en la piel.'
        ],
        [
            'titulo' => 'Avisar condiciones médicas o zonas con dolor agudo',
            'desc' => 'Informa si padeces hipertensión, cirugías recientes, embarazo o contracturas agudas.'
        ]
    ];

    $cuidadosDespues = [
        [
            'titulo' => 'Beber agua para eliminar toxinas',
            'desc' => 'El masaje moviliza ácido láctico y líquidos retenidos. El agua ayuda al sistema linfático a drenar eficientemente.'
        ],
        [
            'titulo' => 'Descansar y evitar ejercicio de alto impacto',
            'desc' => 'Dedica el resto de tu jornada a la relajación; evita entrenamientos intensos que sobrecarguen la musculatura relajada.'
        ],
        [
            'titulo' => 'Ducha tibia con jabón neutro',
            'desc' => 'Permite que los aceites esenciales penetren en la piel unas horas antes de ducharte con agua templada.'
        ],
        [
            'titulo' => 'Plan de mantenimiento regular',
            'desc' => 'Para estrés crónico recomendamos sesiones quincenales; para mantenimiento preventivo, una vez al mes.'
        ]
    ];

    $preguntasFrecuentes = [
        ['p' => '¿Puedo elegir la intensidad de la presión?', 'r' => 'Por supuesto, coordinas en todo momento con la masoterapeuta la presión adecuada a tu confort.'],
        ['p' => '¿Se utilizan aceites esenciales hipoalergénicos?', 'r' => 'Sí, utilizamos óleos vegetales puros con aromaterapia relajante de grado estético profesional.']
    ];
}

// 7. Sobrescribir con Cuidados Personalizados de la Entidad si existen
$customPrevios = trim($servicio['cuidados_previos'] ?? '');
if (!empty($customPrevios)) {
    $lineasPrev = array_filter(array_map('trim', explode("\n", $customPrevios)));
    $cuidadosAntes = [];
    foreach ($lineasPrev as $idx => $linea) {
        $partes = explode(':', $linea, 2);
        $tit = count($partes) > 1 ? trim($partes[0]) : "Recomendación " . ($idx + 1);
        $desc = count($partes) > 1 ? trim($partes[1]) : $linea;
        $cuidadosAntes[] = ['titulo' => $tit, 'desc' => $desc];
    }
}

$customPosteriores = trim($servicio['cuidados_posteriores'] ?? '');
if (!empty($customPosteriores)) {
    $lineasPost = array_filter(array_map('trim', explode("\n", $customPosteriores)));
    $cuidadosDespues = [];
    foreach ($lineasPost as $idx => $linea) {
        $partes = explode(':', $linea, 2);
        $tit = count($partes) > 1 ? trim($partes[0]) : "Cuidados Post-Sesión " . ($idx + 1);
        $desc = count($partes) > 1 ? trim($partes[1]) : $linea;
        $cuidadosDespues[] = ['titulo' => $tit, 'desc' => $desc];
    }
}

// 8. Consultar Reseñas y Calificaciones
$resenasList = $db->getTable('resenas');
$resenasServicio = array_values(array_filter($resenasList, function($r) use ($idServicio) {
    return (int)($r['id_servicio'] ?? 0) === $idServicio;
}));

usort($resenasServicio, function($a, $b) {
    return strcmp($b['fecha_publicacion'] ?? '', $a['fecha_publicacion'] ?? '');
});

$totalResenas = count($resenasServicio);
$sumaRating = 0;
$distribucionRating = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

foreach ($resenasServicio as $r) {
    $pts = max(1, min(5, (int)($r['puntuacion'] ?? 5)));
    $sumaRating += $pts;
    $distribucionRating[$pts]++;
}
$promedioRating = ($totalResenas > 0) ? round($sumaRating / $totalResenas, 1) : 5.0;

// Verificar si el usuario logueado completó el servicio para poder reseñar
$puedeCalificar = false;
$motivoBloqueoResena = 'Inicia sesión para dejar tu reseña.';
if (isLoggedIn()) {
    $currUser = getCurrentUser();
    $currUserId = (int)($currUser['id_usuario'] ?? $currUser['id'] ?? 0);
    $citasCompletadas = $db->find('citas', function($c) use ($currUserId) {
        return (int)($c['id_usuario'] ?? 0) === $currUserId && mb_strtolower($c['estado'] ?? '') === 'completado';
    });

    foreach ($citasCompletadas as $cc) {
        $idC = (int)$cc['id_cita'];
        $css = $db->find('cita_servicio', function($item) use ($idC) {
            return (int)$item['id_cita'] === $idC;
        });
        foreach ($css as $item) {
            $se = $db->getById('servicio_empleado', 'id_servicio_empleado', (int)$item['id_servicio_empleado']);
            if ($se && (int)$se['id_servicio'] === $idServicio) {
                $puedeCalificar = true;
                break 2;
            }
        }
    }

    if (!$puedeCalificar) {
        $motivoBloqueoResena = 'Solo puedes calificar tratamientos que hayas completado previamente en nuestro centro.';
    }
}

$pageTitle = htmlspecialchars($servicio['nombre_servicio']) . ' — Cuidados y Reserva | Arcana';
$pageDescription = 'Conoce todo sobre ' . htmlspecialchars($servicio['nombre_servicio']) . ': duración, precios, cuidados antes y después del tratamiento.';
$pageName = 'services';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="service-detail-page" style="padding-top: 100px; padding-bottom: 5rem;">
  <div class="container">

    <!-- Navegación y migas de pan -->
    <div style="margin-bottom: 1.75rem;">
      <a href="services.php" style="display: inline-flex; align-items: center; gap: 0.4rem; color: var(--color-accent); font-size: 0.88rem; text-decoration: none; font-weight: 500;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m15 18-6-6 6-6"/>
        </svg>
        Volver a todos los servicios
      </a>
    </div>

    <!-- SECCIÓN HERO DEL SERVICIO -->
    <div class="glass" style="padding: 2.5rem; border-radius: var(--radius-card, 16px); margin-bottom: 2.5rem;">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2.5rem; align-items: center;">
        
        <!-- Imagen destacada -->
        <div style="position: relative; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-height: 380px;">
          <img src="<?= htmlspecialchars(!empty($servicio['imagen_servicio']) ? $servicio['imagen_servicio'] : 'assets/images/service-manicure.jpg') ?>" 
               alt="<?= htmlspecialchars($servicio['nombre_servicio']) ?>"
               style="width: 100%; height: 380px; object-fit: cover; display: block;">
          
          <div style="position: absolute; top: 1rem; left: 1rem;">
            <span style="background: rgba(196, 84, 126, 0.9); backdrop-filter: blur(8px); color: #fff; padding: 0.35rem 0.85rem; border-radius: 20px; font-size: 0.78rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
              <?= htmlspecialchars($servicio['categoria'] ?? 'Estética') ?>
            </span>
          </div>

          <?php if ((int)($servicio['disponibilidad'] ?? 1) === 1): ?>
            <div style="position: absolute; bottom: 1rem; right: 1rem;">
              <span style="background: rgba(46, 213, 115, 0.9); backdrop-filter: blur(8px); color: #000; padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">
                ✓ Disponible
              </span>
            </div>
          <?php endif; ?>
        </div>

        <!-- Información y Tarifas -->
        <div>
          <h1 class="text-section text-section-xl" style="font-size: 2.2rem; line-height: 1.15; margin-bottom: 0.75rem;">
            <?= htmlspecialchars($servicio['nombre_servicio']) ?>
          </h1>

          <p style="font-size: 1.05rem; color: var(--color-text-muted); line-height: 1.6; margin-bottom: 1.5rem;">
            <?= nl2br(htmlspecialchars($servicio['descripcion'])) ?>
          </p>

          <!-- Badges de características -->
          <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.75rem;">
            <div style="background: rgba(246, 224, 94, 0.1); border: 1px solid rgba(246, 224, 94, 0.3); padding: 0.5rem 1rem; border-radius: 10px; display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: #f6e05e;">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              <span><strong><?= number_format($promedioRating, 1) ?></strong> (<?= $totalResenas ?> <?= $totalResenas === 1 ? 'opinión' : 'opiniones' ?>)</span>
            </div>

            <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--color-glass-border); padding: 0.5rem 1rem; border-radius: 10px; display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-accent);"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <span>Duración: <strong><?= (int)($servicio['duracion_minutos'] ?? 60) ?> minutos</strong></span>
            </div>

            <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--color-glass-border); padding: 0.5rem 1rem; border-radius: 10px; display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-accent);"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <span>Especialistas: <strong><?= !empty($empleados) ? htmlspecialchars(implode(', ', array_map(fn($e) => $e['nombre'], $empleados))) : 'Profesionales Arcana' ?></strong></span>
            </div>
          </div>

          <!-- Caja de precios y CTA -->
          <div style="background: rgba(196, 84, 126, 0.08); border: 1px solid rgba(196, 84, 126, 0.3); padding: 1.5rem; border-radius: 14px; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
              <div>
                <span style="font-size: 0.82rem; text-transform: uppercase; color: var(--color-text-muted); letter-spacing: 0.5px;">Valor del Tratamiento</span>
                <div style="font-size: 2.2rem; font-weight: 700; color: #fff;">
                  $<?= number_format($precioActual, 0, ',', '.') ?>
                </div>
                <div style="font-size: 0.85rem; color: var(--color-accent); font-weight: 500; margin-top: 0.2rem;">
                  Seña para confirmar reserva: <strong>$<?= number_format($montoSena, 0, ',', '.') ?></strong> (30%)
                </div>
              </div>

              <div>
                <a href="agenda.php?servicio_id=<?= $idServicio ?>" class="btn-pill btn-pill--primary btn-pill--lg" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                  Reservar este Servicio
                </a>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>

    <!-- SECCIÓN DE CUIDADOS Y RECOMENDACIONES (ANTES Y DESPUÉS) -->
    <div style="margin-bottom: 3.5rem;">
      
      <div style="text-align: center; margin-bottom: 2rem;">
        <span style="color: var(--color-accent); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Guía de Cuidado Integral</span>
        <h2 class="text-section text-section-lg" style="margin-top: 0.25rem;">Recomendaciones para el mejor resultado</h2>
        <p style="color: var(--color-text-muted); max-width: 650px; margin: 0 auto; font-size: 0.95rem;">
          Para maximizar la durabilidad, estética y salud de tu piel y uñas, sigue estas indicaciones elaboradas por nuestras profesionales.
        </p>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.75rem;">
        
        <!-- COLUMNA: CUIDADOS ANTES -->
        <div class="glass" style="padding: 2rem; border-radius: var(--radius-card, 16px); border-top: 4px solid var(--color-accent);">
          <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(196, 84, 126, 0.15); display: flex; align-items: center; justify-content: center; color: var(--color-accent); flex-shrink: 0;">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 8 14"></polyline>
              </svg>
            </div>
            <div>
              <h3 style="font-size: 1.25rem; margin: 0; font-weight: 600;">Cuidados ANTES del turno</h3>
              <span style="font-size: 0.8rem; color: var(--color-text-muted);">Preparación previa</span>
            </div>
          </div>

          <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <?php foreach ($cuidadosAntes as $idx => $item): ?>
              <div style="display: flex; gap: 0.85rem; align-items: flex-start;">
                <span style="width: 24px; height: 24px; border-radius: 50%; background: rgba(196, 84, 126, 0.2); color: var(--color-accent); font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                  <?= $idx + 1 ?>
                </span>
                <div>
                  <h4 style="font-size: 0.95rem; margin: 0 0 0.25rem 0; font-weight: 600; color: #fff;">
                    <?= htmlspecialchars($item['titulo']) ?>
                  </h4>
                  <p style="font-size: 0.85rem; color: var(--color-text-muted); margin: 0; line-height: 1.45;">
                    <?= htmlspecialchars($item['desc']) ?>
                  </p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- COLUMNA: CUIDADOS DESPUÉS -->
        <div class="glass" style="padding: 2rem; border-radius: var(--radius-card, 16px); border-top: 4px solid #2ed573;">
          <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(46, 213, 115, 0.15); display: flex; align-items: center; justify-content: center; color: #2ed573; flex-shrink: 0;">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
            </div>
            <div>
              <h3 style="font-size: 1.25rem; margin: 0; font-weight: 600;">Cuidados DESPUÉS del turno</h3>
              <span style="font-size: 0.8rem; color: var(--color-text-muted);">Mantenimiento y fijación</span>
            </div>
          </div>

          <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <?php foreach ($cuidadosDespues as $idx => $item): ?>
              <div style="display: flex; gap: 0.85rem; align-items: flex-start;">
                <span style="width: 24px; height: 24px; border-radius: 50%; background: rgba(46, 213, 115, 0.2); color: #2ed573; font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                  ✓
                </span>
                <div>
                  <h4 style="font-size: 0.95rem; margin: 0 0 0.25rem 0; font-weight: 600; color: #fff;">
                    <?= htmlspecialchars($item['titulo']) ?>
                  </h4>
                  <p style="font-size: 0.85rem; color: var(--color-text-muted); margin: 0; line-height: 1.45;">
                    <?= htmlspecialchars($item['desc']) ?>
                  </p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div>

    </div>

    <!-- PREGUNTAS FRECUENTES DEL SERVICIO -->
    <?php if (!empty($preguntasFrecuentes)): ?>
      <div class="glass" style="padding: 2rem; border-radius: var(--radius-card, 16px); margin-bottom: 3rem;">
        <h3 style="font-size: 1.2rem; margin-bottom: 1.25rem; font-weight: 600;">Preguntas Frecuentes sobre <?= htmlspecialchars($servicio['nombre_servicio']) ?></h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
          <?php foreach ($preguntasFrecuentes as $faq): ?>
            <div style="background: rgba(255, 255, 255, 0.03); padding: 1.25rem; border-radius: 10px; border: 1px solid var(--color-glass-border);">
              <h4 style="font-size: 0.95rem; color: var(--color-accent); margin: 0 0 0.4rem 0;">
                <?= htmlspecialchars($faq['p']) ?>
              </h4>
              <p style="font-size: 0.88rem; color: var(--color-text-muted); margin: 0; line-height: 1.45;">
                <?= htmlspecialchars($faq['r']) ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- SECCIÓN DE CALIFICACIONES Y RESEÑAS DE CLIENTES -->
    <div class="glass" style="padding: 2.5rem; border-radius: var(--radius-card, 16px); margin-bottom: 3.5rem;" id="seccion-resenas">
      <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 2rem;">
        <div>
          <span style="color: var(--color-accent); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Opiniones Verificadas</span>
          <h2 class="text-section text-section-lg" style="margin-top: 0.25rem;">Experiencias de nuestras clientas</h2>
          <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0;">
            Calificaciones de quienes ya realizaron este tratamiento en Arcana.
          </p>
        </div>

        <!-- Promedio Card -->
        <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid var(--color-glass-border); padding: 1rem 1.75rem; border-radius: 14px; text-align: center;">
          <div style="font-size: 2.4rem; font-weight: 700; color: #f6e05e; line-height: 1;">
            <?= number_format($promedioRating, 1) ?>
          </div>
          <div style="color: #f6e05e; font-size: 1.1rem; margin: 0.25rem 0;">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <?= ($i <= round($promedioRating)) ? '★' : '☆' ?>
            <?php endfor; ?>
          </div>
          <div style="font-size: 0.78rem; color: var(--color-text-muted);">
            <?= $totalResenas ?> <?= $totalResenas === 1 ? 'opinión verificada' : 'opiniones verificadas' ?>
          </div>
        </div>
      </div>

      <!-- FORMULARIO / BOTÓN DE CALIFICACIÓN -->
      <?php if ($puedeCalificar): ?>
        <div style="background: rgba(196, 84, 126, 0.08); border: 1px solid rgba(196, 84, 126, 0.3); border-radius: 14px; padding: 1.75rem; margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.25rem;" id="box-review-cta">
          <div>
            <h3 style="font-size: 1.15rem; margin: 0 0 0.35rem 0; color: #fff; display: flex; align-items: center; gap: 0.5rem;">
              <span>⭐</span> ¡Comparte tu experiencia sobre este tratamiento!
            </h3>
            <p style="font-size: 0.88rem; color: var(--color-text-muted); margin: 0; max-width: 600px;">
              Completaste este servicio en nuestro centro. Abre el formulario de reseña para calificar la atención, durabilidad y los resultados.
            </p>
          </div>
          <div>
            <button type="button" id="btn-abrir-modal-resena" class="btn-pill btn-pill--primary" style="padding: 0.65rem 1.75rem; font-size: 0.92rem; display: inline-flex; align-items: center; gap: 0.5rem;">
              <span>⭐</span> Dejar Mi Reseña
            </button>
          </div>
        </div>
      <?php else: ?>
        <div style="background: rgba(255, 255, 255, 0.03); border: 1px dashed var(--color-glass-border); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 2.5rem; display: flex; align-items: center; gap: 1rem;">
          <div style="font-size: 1.5rem; color: var(--color-accent);">✨</div>
          <div style="font-size: 0.88rem; color: var(--color-text-muted);">
            <?= htmlspecialchars($motivoBloqueoResena) ?>
            <?php if (!isLoggedIn()): ?>
              <a href="login.php?redirect=servicio.php?id=<?= $idServicio ?>" style="color: var(--color-accent); font-weight: 600; text-decoration: underline; margin-left: 0.35rem;">Iniciar sesión aquí</a>.
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- LISTADO DE RESEÑAS -->
      <div id="lista-resenas" style="display: flex; flex-direction: column; gap: 1rem;">
        <?php if (empty($resenasServicio)): ?>
          <div style="text-align: center; padding: 2.5rem 1rem; color: var(--color-text-muted); font-size: 0.9rem;">
            Aún no hay reseñas publicadas para este servicio. ¡Sé la primera en reservar y calificarlo!
          </div>
        <?php else: ?>
          <?php foreach ($resenasServicio as $r): ?>
            <?php
              $rUser = $db->getById('usuario', 'id_usuario', (int)($r['id_usuario'] ?? 0));
              $autor = $rUser ? ($rUser['nombre'] . ' ' . (mb_substr($rUser['apellido'] ?? '', 0, 1) ? mb_substr($rUser['apellido'], 0, 1) . '.' : '')) : 'Cliente Arcana';
              $pts = (int)($r['puntuacion'] ?? 5);
              $fecha = date('d/m/Y', strtotime($r['fecha_publicacion'] ?? date('Y-m-d')));
            ?>
            <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--color-glass-border); border-radius: 12px; padding: 1.25rem 1.5rem;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                <div style="display: flex; align-items: center; gap: 0.6rem;">
                  <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, var(--color-accent), #8b3a5b); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.8rem; font-weight: 700;">
                    <?= mb_substr($autor, 0, 1) ?>
                  </div>
                  <div>
                    <span style="font-weight: 600; color: #fff; font-size: 0.95rem;"><?= htmlspecialchars($autor) ?></span>
                    <span style="display: inline-flex; align-items: center; gap: 0.2rem; background: rgba(46, 213, 115, 0.15); color: #2ed573; padding: 0.15rem 0.5rem; border-radius: 10px; font-size: 0.72rem; font-weight: 600; margin-left: 0.5rem;">
                      ✓ Turno Completado
                    </span>
                  </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <span style="color: #f6e05e; font-size: 0.95rem;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <?= ($i <= $pts) ? '★' : '☆' ?>
                    <?php endfor; ?>
                  </span>
                  <span style="color: var(--color-text-muted); font-size: 0.8rem;"><?= $fecha ?></span>
                </div>
              </div>

              <p style="color: #cbd5e1; font-size: 0.9rem; line-height: 1.5; margin: 0;">
                <?= nl2br(htmlspecialchars($r['comentario'])) ?>
              </p>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- MODAL PARA PUBLICAR RESEÑA -->
    <?php if ($puedeCalificar): ?>
    <div id="modal-resena" class="modal-overlay" aria-hidden="true">
      <div class="modal-card" style="max-width: 520px;">
        <button type="button" class="modal-close" id="btn-cerrar-modal-resena" aria-label="Cerrar modal">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>

        <div style="margin-bottom: 1.5rem; text-align: left;">
          <span style="color: var(--color-accent); font-size: 0.78rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Calificación de Servicio</span>
          <h3 style="font-size: 1.4rem; color: #fff; margin: 0.25rem 0 0.5rem 0; font-family: var(--font-heading, 'Cormorant Garamond', serif); letter-spacing: 0.5px;">
            <?= htmlspecialchars($servicio['nombre_servicio']) ?>
          </h3>
          <p style="color: var(--color-text-muted); font-size: 0.88rem; margin: 0;">
            Has completado este turno en Arcana. Cuéntanos qué tal fue tu experiencia y los resultados obtenidos.
          </p>
        </div>

        <form id="form-nueva-resena">
          <input type="hidden" name="id_servicio" value="<?= $idServicio ?>">

          <!-- Calificación por estrellas -->
          <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--color-glass-border); border-radius: 12px; padding: 1.15rem; margin-bottom: 1.25rem; text-align: center;">
            <label style="display: block; font-size: 0.85rem; color: #cbd5e1; margin-bottom: 0.5rem; font-weight: 500;">
              Tu valoración general:
            </label>
            <div style="display: inline-flex; gap: 0.6rem; font-size: 2.2rem; color: #f6e05e; cursor: pointer;" id="star-selector">
              <span data-val="1" class="star-rating-item" style="transition: transform 0.15s, color 0.15s;">★</span>
              <span data-val="2" class="star-rating-item" style="transition: transform 0.15s, color 0.15s;">★</span>
              <span data-val="3" class="star-rating-item" style="transition: transform 0.15s, color 0.15s;">★</span>
              <span data-val="4" class="star-rating-item" style="transition: transform 0.15s, color 0.15s;">★</span>
              <span data-val="5" class="star-rating-item" style="transition: transform 0.15s, color 0.15s;">★</span>
            </div>
            <div id="star-label" style="font-size: 0.82rem; color: #f6e05e; font-weight: 600; margin-top: 0.25rem;">
              5 estrellas — ¡Excelente experiencia!
            </div>
            <input type="hidden" id="puntuacion-input" name="puntuacion" value="5">
          </div>

          <!-- Comentario -->
          <div style="margin-bottom: 1.5rem; text-align: left;">
            <label for="comentario-resena" style="display: block; font-size: 0.85rem; color: #cbd5e1; margin-bottom: 0.4rem; font-weight: 500;">
              Tu opinión o comentario: <span style="color: var(--color-accent);">*</span>
            </label>
            <textarea id="comentario-resena" name="comentario" rows="4" class="form-input" placeholder="Escribe tu opinión sobre la atención recibida, los productos utilizados y el resultado final..." required style="width: 100%; border-radius: 10px; resize: vertical;"></textarea>
            <div id="resena-error-inline" style="display: none; color: #ff6b6b; font-size: 0.8rem; margin-top: 0.35rem;"></div>
          </div>

          <!-- Botones de Acción -->
          <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
            <button type="button" id="btn-cancelar-resena" class="btn-pill btn-pill--outline" style="padding: 0.6rem 1.25rem; font-size: 0.88rem;">
              Cancelar
            </button>
            <button type="submit" id="btn-submit-resena" class="btn-pill btn-pill--primary" style="padding: 0.6rem 1.75rem; font-size: 0.88rem;">
              Publicar Mi Reseña
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- MODAL DE FEEDBACK / RESPUESTA (ÉXITO O ERROR) -->
    <div id="modal-feedback-resena" class="modal-overlay" aria-hidden="true">
      <div class="modal-card" style="max-width: 440px; text-align: center; padding: 2.25rem 2rem;">
        <div id="feedback-icon-container" style="width: 64px; height: 64px; margin: 0 auto 1.25rem; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
          <!-- Icono inyectado dinámicamente -->
        </div>

        <h3 id="feedback-title" style="font-size: 1.35rem; color: #fff; margin-bottom: 0.5rem; font-family: var(--font-heading, 'Cormorant Garamond', serif);">
          Título del Estado
        </h3>

        <p id="feedback-message" style="color: var(--color-text-muted); font-size: 0.92rem; margin-bottom: 1.75rem; line-height: 1.5;">
          Mensaje descriptivo.
        </p>

        <button type="button" id="btn-feedback-ok" class="btn-pill btn-pill--primary" style="width: 100%; padding: 0.65rem 1.5rem; font-size: 0.92rem;">
          Aceptar
        </button>
      </div>
    </div>

    <!-- Script de interacción del Modal de Reseñas -->
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const starSelector = document.getElementById('star-selector');
        const starLabel = document.getElementById('star-label');
        const puntuacionInput = document.getElementById('puntuacion-input');
        const formResena = document.getElementById('form-nueva-resena');
        const btnSubmit = document.getElementById('btn-submit-resena');
        const modalResena = document.getElementById('modal-resena');
        const btnAbrirModal = document.getElementById('btn-abrir-modal-resena');
        const btnCerrarModal = document.getElementById('btn-cerrar-modal-resena');
        const btnCancelarResena = document.getElementById('btn-cancelar-resena');
        const errorInline = document.getElementById('resena-error-inline');

        // Modal Feedback
        const modalFeedback = document.getElementById('modal-feedback-resena');
        const feedbackIcon = document.getElementById('feedback-icon-container');
        const feedbackTitle = document.getElementById('feedback-title');
        const feedbackMsg = document.getElementById('feedback-message');
        const btnFeedbackOk = document.getElementById('btn-feedback-ok');
        let feedbackSuccessCallback = null;

        const starTexts = {
          1: '1 estrella — Regular / Podría mejorar',
          2: '2 estrellas — Aceptable',
          3: '3 estrellas — Buen servicio',
          4: '4 estrellas — Muy bueno, recomendado',
          5: '5 estrellas — ¡Excelente experiencia!'
        };

        function showFeedbackModal(isSuccess, title, message, onOk = null) {
          if (!modalFeedback) return;
          feedbackSuccessCallback = onOk;

          if (isSuccess) {
            feedbackIcon.style.background = 'rgba(46, 213, 115, 0.15)';
            feedbackIcon.style.color = '#2ed573';
            feedbackIcon.style.border = '1px solid rgba(46, 213, 115, 0.3)';
            feedbackIcon.innerHTML = `
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
            `;
          } else {
            feedbackIcon.style.background = 'rgba(255, 171, 0, 0.15)';
            feedbackIcon.style.color = '#ffab00';
            feedbackIcon.style.border = '1px solid rgba(255, 171, 0, 0.3)';
            feedbackIcon.innerHTML = `
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            `;
          }

          feedbackTitle.textContent = title;
          feedbackMsg.textContent = message;
          modalFeedback.classList.add('modal-overlay--open');
        }

        if (btnFeedbackOk) {
          btnFeedbackOk.addEventListener('click', () => {
            modalFeedback.classList.remove('modal-overlay--open');
            if (typeof feedbackSuccessCallback === 'function') {
              feedbackSuccessCallback();
            }
          });
        }

        function openReviewModal() {
          if (modalResena) {
            modalResena.classList.add('modal-overlay--open');
            if (errorInline) {
              errorInline.style.display = 'none';
              errorInline.textContent = '';
            }
          }
        }

        function closeReviewModal() {
          if (modalResena) {
            modalResena.classList.remove('modal-overlay--open');
          }
        }

        if (btnAbrirModal) btnAbrirModal.addEventListener('click', openReviewModal);
        if (btnCerrarModal) btnCerrarModal.addEventListener('click', closeReviewModal);
        if (btnCancelarResena) btnCancelarResena.addEventListener('click', closeReviewModal);

        // Auto-abrir modal si la URL contiene el parámetro review=1
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('review') === '1') {
          openReviewModal();
        }

        // Estrellas interactivas
        if (starSelector && puntuacionInput) {
          const stars = starSelector.querySelectorAll('.star-rating-item');
          
          function updateStars(val) {
            stars.forEach((s, idx) => {
              s.style.color = (idx < val) ? '#f6e05e' : '#4a5568';
              s.style.transform = (idx < val) ? 'scale(1.08)' : 'scale(1)';
            });
            if (starLabel) {
              starLabel.textContent = starTexts[val] || `${val} estrellas`;
            }
          }

          stars.forEach((star, index) => {
            star.addEventListener('click', () => {
              const val = index + 1;
              puntuacionInput.value = val;
              updateStars(val);
            });
            star.addEventListener('mouseenter', () => updateStars(index + 1));
          });

          starSelector.addEventListener('mouseleave', () => updateStars(parseInt(puntuacionInput.value) || 5));
          updateStars(5);
        }

        // Envío del formulario de reseña
        if (formResena) {
          formResena.addEventListener('submit', async (e) => {
            e.preventDefault();
            const idServicio = formResena.querySelector('input[name="id_servicio"]').value;
            const puntuacion = formResena.querySelector('#puntuacion-input').value;
            const comentario = formResena.querySelector('#comentario-resena').value.trim();

            if (!comentario) {
              if (errorInline) {
                errorInline.textContent = 'Por favor escribe un comentario sobre tu experiencia.';
                errorInline.style.display = 'block';
              } else {
                showFeedbackModal(false, 'Atención', 'Por favor escribe tu opinión sobre este tratamiento antes de publicar.');
              }
              return;
            }

            if (errorInline) errorInline.style.display = 'none';

            if (btnSubmit) {
              btnSubmit.disabled = true;
              btnSubmit.textContent = 'Publicando...';
            }

            try {
              const res = await fetch('api/resenas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_servicio: idServicio, puntuacion, comentario })
              });

              const json = await res.json();
              if (!json.success) {
                closeReviewModal();
                showFeedbackModal(false, 'No se pudo publicar', json.message || 'Ocurrió un error al procesar tu reseña.', () => {
                  openReviewModal();
                });
                if (btnSubmit) {
                  btnSubmit.disabled = false;
                  btnSubmit.textContent = 'Publicar Mi Reseña';
                }
                return;
              }

              closeReviewModal();
              showFeedbackModal(true, '¡Tu reseña ha sido publicada!', 'Muchas gracias por compartir tu experiencia con nosotros. Ya se encuentra disponible.', () => {
                window.location.href = `servicio.php?id=${idServicio}#seccion-resenas`;
                window.location.reload();
              });
            } catch (err) {
              console.error(err);
              closeReviewModal();
              showFeedbackModal(false, 'Error de Conexión', 'Hubo un inconveniente al conectar con el servidor. Por favor intenta nuevamente.', () => {
                openReviewModal();
              });
              if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Publicar Mi Reseña';
              }
            }
          });
        }
      });
    </script>

    <!-- BANNER FINAL CTA -->
    <div class="glass" style="padding: 2.5rem; border-radius: var(--radius-card, 16px); text-align: center; background: radial-gradient(circle at center, rgba(196, 84, 126, 0.15), transparent 70%); margin-bottom: 3.5rem;">
      <h3 style="font-size: 1.75rem; margin-bottom: 0.5rem;">¿Lista para renovar tu imagen?</h3>
      <p style="color: var(--color-text-muted); max-width: 500px; margin: 0 auto 1.5rem; font-size: 0.95rem;">
        Reserva tu turno online en menos de 2 minutos y abona la seña con Mercado Pago para asegurar tu lugar.
      </p>
      <a href="agenda.php?servicio_id=<?= $idServicio ?>" class="btn-pill btn-pill--primary btn-pill--lg" style="text-decoration: none;">
        Reservar <?= htmlspecialchars($servicio['nombre_servicio']) ?>
      </a>
    </div>

    <!-- SERVICIOS RELACIONADOS -->
    <?php if (!empty($relacionados)): ?>
      <div>
        <h3 class="text-section" style="font-size: 1.35rem; margin-bottom: 1.25rem;">Otros servicios recomendados</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem;">
          <?php foreach ($relacionados as $rel): ?>
            <?php
              $relPrecios = $db->find('precio_servicio', function($p) use ($rel) {
                  return (int)$p['id_servicio'] === (int)$rel['id_servicio'];
              });
              $relPrecio = !empty($relPrecios) ? end($relPrecios)['precio'] : 20000;
            ?>
            <a href="servicio.php?id=<?= $rel['id_servicio'] ?>" class="glass--hover" style="text-decoration: none; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; background: var(--color-glass);">
              <img src="<?= htmlspecialchars(!empty($rel['imagen_servicio']) ? $rel['imagen_servicio'] : 'assets/images/service-manicure.jpg') ?>" alt="<?= htmlspecialchars($rel['nombre_servicio']) ?>" style="width: 100%; height: 160px; object-fit: cover;">
              <div style="padding: 1rem; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                  <span style="font-size: 0.72rem; color: var(--color-accent); text-transform: uppercase; font-weight: 600;"><?= htmlspecialchars($rel['categoria'] ?? '') ?></span>
                  <h4 style="font-size: 1rem; color: #fff; margin: 0.25rem 0 0.5rem 0; font-weight: 600;"><?= htmlspecialchars($rel['nombre_servicio']) ?></h4>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--color-glass-border); padding-top: 0.5rem; margin-top: 0.5rem; font-size: 0.85rem;">
                  <span style="font-weight: 700; color: #fff;">$<?= number_format($relPrecio, 0, ',', '.') ?></span>
                  <span style="color: var(--color-accent);">Ver detalles →</span>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
