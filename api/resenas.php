<?php
/**
 * ARCANA — Reseñas y Calificaciones API Endpoint (api/resenas.php)
 * Publicación y consulta de opiniones con validación de turnos completados.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/JsonDB.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once __DIR__ . '/response.php';

$db = JsonDB::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

// Helper: Enriquecer reseña con datos del usuario
function enrichResena(array $r, JsonDB $db): array {
    $idUsuario = (int)($r['id_usuario'] ?? 0);
    $user = $db->getById('usuario', 'id_usuario', $idUsuario);

    $autor = 'Cliente Arcana';
    if ($user) {
        $autor = $user['nombre'] . ' ' . (mb_substr($user['apellido'] ?? '', 0, 1) ? mb_substr($user['apellido'], 0, 1) . '.' : '');
    }

    return [
        'id_resena' => (int)($r['id_resena'] ?? 0),
        'id_servicio' => (int)($r['id_servicio'] ?? 0),
        'id_usuario' => $idUsuario,
        'autor' => $autor,
        'puntuacion' => (int)($r['puntuacion'] ?? 5),
        'comentario' => $r['comentario'] ?? '',
        'fecha_publicacion' => $r['fecha_publicacion'] ?? date('Y-m-d H:i:s'),
        'fecha_formateada' => date('d/m/Y', strtotime($r['fecha_publicacion'] ?? date('Y-m-d')))
    ];
}

// -------------------------------------------------------------
// GET: Listar reseñas y calcular promedio de un servicio
// -------------------------------------------------------------
if ($method === 'GET') {
    $idServicio = (int)($_GET['id_servicio'] ?? $_GET['id'] ?? 0);

    $resenas = $db->getTable('resenas');

    if ($idServicio > 0) {
        $resenas = array_values(array_filter($resenas, function($r) use ($idServicio) {
            return (int)($r['id_servicio'] ?? 0) === $idServicio;
        }));
    }

    // Ordenar de más recientes a más antiguas
    usort($resenas, function($a, $b) {
        return strcmp($b['fecha_publicacion'] ?? '', $a['fecha_publicacion'] ?? '');
    });

    $total = count($resenas);
    $sumaPuntos = 0;
    $distribucion = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

    foreach ($resenas as $r) {
        $pts = max(1, min(5, (int)($r['puntuacion'] ?? 5)));
        $sumaPuntos += $pts;
        $distribucion[$pts]++;
    }

    $promedio = ($total > 0) ? round($sumaPuntos / $total, 1) : 5.0;

    $enriched = array_map(function($r) use ($db) {
        return enrichResena($r, $db);
    }, $resenas);

    // Verificar si el usuario en sesión puede calificar este servicio
    $puedeCalificar = false;
    $motivoBloqueo = '';
    $idCitaCompletada = null;

    if (isLoggedIn()) {
        $currentUser = getCurrentUser();
        $userId = (int)($currentUser['id_usuario'] ?? $currentUser['id'] ?? 0);

        if ($userId > 0 && $idServicio > 0) {
            // Buscar si el usuario tiene una cita completada con este servicio
            $citasUsuario = $db->find('citas', function($c) use ($userId) {
                return (int)($c['id_usuario'] ?? 0) === $userId && mb_strtolower($c['estado'] ?? '') === 'completado';
            });

            foreach ($citasUsuario as $cita) {
                $idCita = (int)$cita['id_cita'];
                $citaServicios = $db->find('cita_servicio', function($cs) use ($idCita) {
                    return (int)$cs['id_cita'] === $idCita;
                });

                foreach ($citaServicios as $cs) {
                    $idSE = (int)$cs['id_servicio_empleado'];
                    $se = $db->getById('servicio_empleado', 'id_servicio_empleado', $idSE);
                    if ($se && (int)$se['id_servicio'] === $idServicio) {
                        $puedeCalificar = true;
                        $idCitaCompletada = $idCita;
                        break 2;
                    }
                }
            }

            if (!$puedeCalificar) {
                $motivoBloqueo = 'Para calificar debes haber completado al menos una sesión de este tratamiento.';
            }
        }
    } else {
        $motivoBloqueo = 'Inicia sesión con tu cuenta para dejar tu reseña.';
    }

    jsonResponse(true, [
        'id_servicio' => $idServicio,
        'promedio' => $promedio,
        'total' => $total,
        'distribucion' => $distribucion,
        'resenas' => $enriched,
        'puede_calificar' => $puedeCalificar,
        'motivo_bloqueo' => $motivoBloqueo,
        'id_cita_asociada' => $idCitaCompletada
    ]);
}

// -------------------------------------------------------------
// POST: Publicar nueva reseña
// -------------------------------------------------------------
if ($method === 'POST') {
    if (!isLoggedIn()) {
        jsonResponse(false, null, 'Debes iniciar sesión para publicar una reseña.', 401);
    }

    $currentUser = getCurrentUser();
    $userId = (int)($currentUser['id_usuario'] ?? $currentUser['id'] ?? 0);

    $input = getJsonInput();
    $idServicio = (int)($input['id_servicio'] ?? 0);
    $puntuacion = (int)($input['puntuacion'] ?? 5);
    $comentario = trim($input['comentario'] ?? '');

    if ($idServicio <= 0) {
        jsonResponse(false, null, 'Servicio no especificado.', 400);
    }

    if ($puntuacion < 1 || $puntuacion > 5) {
        jsonResponse(false, null, 'La puntuación debe ser de 1 a 5 estrellas.', 400);
    }

    if (empty($comentario)) {
        jsonResponse(false, null, 'Por favor escribe un comentario sobre tu experiencia.', 400);
    }

    // VALIDACIÓN ESTRICTA: El usuario debe tener al menos un turno en estado COMPLETADO de este servicio
    $citasUsuario = $db->find('citas', function($c) use ($userId) {
        return (int)($c['id_usuario'] ?? 0) === $userId && mb_strtolower($c['estado'] ?? '') === 'completado';
    });

    $idCitaCompletada = null;
    foreach ($citasUsuario as $cita) {
        $idCita = (int)$cita['id_cita'];
        $citaServicios = $db->find('cita_servicio', function($cs) use ($idCita) {
            return (int)$cs['id_cita'] === $idCita;
        });

        foreach ($citaServicios as $cs) {
            $idSE = (int)$cs['id_servicio_empleado'];
            $se = $db->getById('servicio_empleado', 'id_servicio_empleado', $idSE);
            if ($se && (int)$se['id_servicio'] === $idServicio) {
                $idCitaCompletada = $idCita;
                break 2;
            }
        }
    }

    if (!$idCitaCompletada) {
        jsonResponse(false, null, 'No puedes calificar un servicio que no has completado en nuestro salón.', 403);
    }

    // Insertar reseña
    $nuevaResena = $db->insert('resenas', [
        'id_servicio' => $idServicio,
        'id_usuario' => $userId,
        'id_cita' => $idCitaCompletada,
        'puntuacion' => $puntuacion,
        'comentario' => $comentario,
        'fecha_publicacion' => date('Y-m-d H:i:s')
    ], 'id_resena');

    jsonResponse(true, enrichResena($nuevaResena, $db), '¡Gracias por compartir tu opinión! Tu reseña ha sido publicada.', 201);
}

jsonResponse(false, null, 'Método no permitido.', 405);
