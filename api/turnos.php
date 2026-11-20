<?php
/**
 * ARCANA — Turnos / Citas CRUD API Endpoint (Schema: arcanaestetica)
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/JsonDB.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once __DIR__ . '/notificaciones.php';
require_once __DIR__ . '/response.php';

$db = JsonDB::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

/**
 * Helper: Registrar evento en tabla auditoria_turnos
 */
function logAuditoriaTurno(int $idCita, string $accion, string $detalles, JsonDB $db): void {
    $currentUser = getCurrentUser();
    $idUsuario = (int)($currentUser['id_usuario'] ?? $currentUser['id'] ?? 0);
    $nombreUsuario = $currentUser ? ($currentUser['nombre'] . ' ' . ($currentUser['apellido'] ?? '')) : 'Sistema / Cliente';
    $rolUsuario = $currentUser['rol'] ?? (isSuperAdmin() ? 'superadmin' : (isAdmin() ? 'admin' : 'usuario'));

    $db->insert('auditoria_turnos', [
        'id_cita' => $idCita,
        'accion' => $accion, // 'creacion', 'cambio_estado', 'reprogramacion', 'cancelacion', 'eliminacion', 'foto_subida'
        'detalles' => $detalles,
        'id_usuario_accion' => $idUsuario,
        'nombre_usuario_accion' => trim($nombreUsuario),
        'rol_usuario_accion' => $rolUsuario,
        'fecha_hora' => date('Y-m-d H:i:s')
    ], 'id_auditoria');

    if (function_exists('registrarCambioDashboard')) {
        $accionLegible = ucfirst(str_replace('_', ' ', $accion));
        registrarCambioDashboard('📅 Turnos', "Turno #$idCita ($accionLegible)", $detalles);
    }
}

/**
 * Enriquecer una cita con datos de usuario, espacio, servicio, empleado y fotos antes/después
 */
function enrichCita(array $c, JsonDB $db): array {
    $idCita = (int)$c['id_cita'];

    // Cliente (usuario)
    $idUsuario = (int)($c['id_usuario'] ?? 0);
    $usuario = $db->getById('usuario', 'id_usuario', $idUsuario);
    $clienteNombre = $usuario ? ($usuario['nombre'] . ' ' . $usuario['apellido']) : 'Cliente #' . $idUsuario;

    // Espacio
    $idEspacio = (int)($c['id_espacio'] ?? 0);
    $espacio = $db->getById('espacio', 'id_espacio', $idEspacio);
    $espacioNombre = $espacio ? $espacio['nombre'] : 'Espacio ' . $idEspacio;

    // Cita Servicio -> Servicio Empleado
    $citaServicios = $db->find('cita_servicio', function($cs) use ($idCita) {
        return (int)$cs['id_cita'] === $idCita;
    });

    $servicioNombre = 'Servicio General';
    $empleadoNombre = 'Especialista';
    $idServicio = 1;
    $idEmpleado = 1;
    $idServicioEmpleado = 1;

    if (!empty($citaServicios)) {
        $idServicioEmpleado = (int)$citaServicios[0]['id_servicio_empleado'];
        $servemp = $db->getById('servicio_empleado', 'id_servicio_empleado', $idServicioEmpleado);
        if ($servemp) {
            $idServicio = (int)$servemp['id_servicio'];
            $idEmpleado = (int)$servemp['id_empleado'];

            $srv = $db->getById('servicio', 'id_servicio', $idServicio);
            if ($srv) $servicioNombre = $srv['nombre_servicio'];

            $emp = $db->getById('empleado', 'id_empleado', $idEmpleado);
            if ($emp) $empleadoNombre = $emp['nombre'] . ' ' . $emp['apellido'];
        }
    }

    // Fotos de la sesión (Antes y Después)
    $imagenes = $db->find('imagen_cita', function($img) use ($idCita) {
        return (int)($img['id_cita'] ?? 0) === $idCita;
    });

    $fotosAntes = [];
    $fotosDespues = [];
    foreach ($imagenes as $img) {
        $tipo = strtolower(trim($img['tipo'] ?? 'antes'));
        if ($tipo === 'despues' || $tipo === 'después') {
            $fotosDespues[] = $img;
        } else {
            $fotosAntes[] = $img;
        }
    }

    return [
        'id' => $idCita,
        'id_cita' => $idCita,
        'id_usuario' => $idUsuario,
        'id_espacio' => $idEspacio,
        'id_servicio' => $idServicio,
        'id_empleado' => $idEmpleado,
        'id_servicio_empleado' => $idServicioEmpleado,
        'cliente' => $clienteNombre,
        'espacio' => $espacioNombre,
        'servicio' => $servicioNombre,
        'empleado' => $empleadoNombre,
        'fecha_hora' => $c['fecha_hora'],
        'fechaHora' => $c['fecha_hora'],
        'modalidad' => $c['modalidad'] ?? 'Presencial',
        'estado' => $c['estado'] ?? 'pendiente',
        'observaciones' => $c['observaciones'] ?? '-',
        'fotos_antes' => $fotosAntes,
        'fotos_despues' => $fotosDespues,
        'total_fotos' => count($imagenes),
        'creada_en' => $c['creada_en'] ?? date('Y-m-d H:i:s'),
        'modificada_en' => $c['modificada_en'] ?? date('Y-m-d H:i:s')
    ];
}

// GET: Listar o individual, auditoría e historial por cliente
if ($method === 'GET') {
    // Sub-acción: Auditoría de Turnos (Para Superadmin)
    if ($action === 'auditoria') {
        $logs = $db->getTable('auditoria_turnos');
        usort($logs, function($a, $b) {
            return strcmp($b['fecha_hora'] ?? '', $a['fecha_hora'] ?? '');
        });
        jsonResponse(true, $logs);
    }

    // Sub-acción: Historial completo de un cliente con fotos de sesiones
    $idUsuarioHistorial = (int)($_GET['id_usuario'] ?? 0);
    if ($action === 'historial_usuario' || $idUsuarioHistorial > 0) {
        $usuario = $db->getById('usuario', 'id_usuario', $idUsuarioHistorial);
        if (!$usuario) {
            jsonResponse(false, null, 'Usuario no encontrado.', 404);
        }

        $citas = $db->find('citas', function($c) use ($idUsuarioHistorial) {
            return (int)($c['id_usuario'] ?? 0) === $idUsuarioHistorial;
        });

        usort($citas, function($a, $b) {
            return strcmp($b['fecha_hora'] ?? '', $a['fecha_hora'] ?? '');
        });

        $enrichedCitas = array_map(function($c) use ($db) {
            return enrichCita($c, $db);
        }, $citas);

        jsonResponse(true, [
            'usuario' => [
                'id_usuario' => $idUsuarioHistorial,
                'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
                'email' => $usuario['email'],
                'telefono' => $usuario['telefono'] ?? '',
                'dni' => $usuario['DNI'] ?? ''
            ],
            'total_turnos' => count($enrichedCitas),
            'citas' => $enrichedCitas
        ]);
    }

    $id = $_GET['id'] ?? $_GET['id_cita'] ?? null;
    if ($id) {
        $cita = $db->getById('citas', 'id_cita', $id);
        if (!$cita) {
            jsonResponse(false, null, 'Turno no encontrado.', 404);
        }
        jsonResponse(true, enrichCita($cita, $db));
    }

    $estado = trim($_GET['estado'] ?? '');
    $q = mb_strtolower(trim($_GET['q'] ?? ''));

    $citas = $db->getTable('citas');
    $enriched = array_map(function($c) use ($db) {
        return enrichCita($c, $db);
    }, $citas);

    if ($estado !== '') {
        $enriched = array_values(array_filter($enriched, function($c) use ($estado) {
            return mb_strtolower($c['estado']) === mb_strtolower($estado);
        }));
    }

    if ($q !== '') {
        $enriched = array_values(array_filter($enriched, function($c) use ($q) {
            return (
                mb_stripos($c['cliente'], $q) !== false ||
                mb_stripos($c['empleado'], $q) !== false ||
                mb_stripos($c['servicio'], $q) !== false ||
                mb_stripos($c['espacio'], $q) !== false
            );
        }));
    }

    // Ordenar por fecha_hora descendente
    usort($enriched, function($a, $b) {
        return strcmp($b['fecha_hora'], $a['fecha_hora']);
    });

    jsonResponse(true, $enriched);
}

// POST: Crear turno/cita o adjuntar foto antes/después
if ($method === 'POST' && ($_POST['_method'] ?? '') !== 'PUT' && ($_POST['_method'] ?? '') !== 'DELETE') {
    $input = getJsonInput();

    // Sub-acción: Guardar foto de antes o después
    if ($action === 'guardar_foto' || !empty($input['action']) && $input['action'] === 'guardar_foto') {
        $idCita = (int)($input['id_cita'] ?? 0);
        $url = trim($input['url'] ?? '');
        $tipo = strtolower(trim($input['tipo'] ?? 'antes'));
        if (!in_array($tipo, ['antes', 'despues', 'después'])) $tipo = 'antes';

        if (!$idCita || empty($url)) {
            jsonResponse(false, null, 'ID de turno y URL de imagen son requeridos.', 400);
        }

        $cita = $db->getById('citas', 'id_cita', $idCita);
        if (!$cita) {
            jsonResponse(false, null, 'Turno no encontrado.', 404);
        }

        $idUsuario = (int)($cita['id_usuario'] ?? 0);
        $nuevaImagen = $db->insert('imagen_cita', [
            'id_cita' => $idCita,
            'id_usuario' => $idUsuario,
            'tipo' => ($tipo === 'después') ? 'despues' : $tipo,
            'url' => $url,
            'fecha_subida' => date('Y-m-d H:i:s')
        ], 'id_imagen');

        logAuditoriaTurno($idCita, 'foto_subida', "Se adjuntó foto de '{$tipo}' a la sesión.", $db);

        jsonResponse(true, $nuevaImagen, 'Foto guardada correctamente en la sesión.', 201);
    }

    $fechaHora = trim($input['fecha_hora'] ?? $input['fechaHora'] ?? date('Y-m-d H:i:s'));
    if (strpos($fechaHora, 'T') !== false) {
        $fechaHora = str_replace('T', ' ', rtrim($fechaHora, 'Z'));
        if (strlen($fechaHora) === 16) $fechaHora .= ':00';
    }

    $estado = trim($input['estado'] ?? 'pendiente');
    $modalidad = trim($input['modalidad'] ?? 'Presencial');
    $observaciones = trim($input['observaciones'] ?? '-');

    // 1. Resolver id_usuario
    $idUsuario = (int)($input['id_usuario'] ?? 0);
    if (!$idUsuario && !empty($input['cliente'])) {
        $cliName = trim($input['cliente']);
        $uExistente = $db->find('usuario', function($u) use ($cliName) {
            $nomCompleto = $u['nombre'] . ' ' . $u['apellido'];
            return (mb_strtolower($nomCompleto) === mb_strtolower($cliName) || mb_strtolower($u['nombre']) === mb_strtolower($cliName));
        });
        if (!empty($uExistente)) {
            $idUsuario = (int)$uExistente[0]['id_usuario'];
        } else {
            // Crear usuario rápido
            $partes = explode(' ', $cliName, 2);
            $nuevoU = $db->insert('usuario', [
                'nombre' => $partes[0],
                'apellido' => $partes[1] ?? 'Cliente',
                'email' => 'cliente_' . time() . '@arcanacliente.com',
                'contrasena' => password_hash('123456', PASSWORD_BCRYPT),
                'fecha_registro' => date('Y-m-d'),
                'activo' => 1,
                'telefono' => '',
                'DNI' => (string)rand(30000000, 45000000),
                'id_rol' => 3 // Rol Cliente
            ], 'id_usuario');
            $idUsuario = (int)$nuevoU['id_usuario'];
        }
    }
    if (!$idUsuario) $idUsuario = 2; // Default cliente demo

    // 2. Resolver id_espacio
    $idEspacio = (int)($input['id_espacio'] ?? 0);
    if (!$idEspacio && !empty($input['espacio'])) {
        $espName = trim($input['espacio']);
        $espExistente = $db->find('espacio', function($e) use ($espName) {
            return (mb_stripos($e['nombre'], $espName) !== false);
        });
        if (!empty($espExistente)) {
            $idEspacio = (int)$espExistente[0]['id_espacio'];
        } else {
            $nuevoEsp = $db->insert('espacio', [
                'nombre' => 'Espacio ' . $espName,
                'tipo' => 'Cabina',
                'estado' => 'Disponible'
            ], 'id_espacio');
            $idEspacio = (int)$nuevoEsp['id_espacio'];
        }
    }
    if (!$idEspacio) $idEspacio = 1;

    // 3. Resolver servicio y empleado -> servicio_empleado
    $idServicio = (int)($input['id_servicio'] ?? 0);
    if (!$idServicio && !empty($input['servicio'])) {
        $srvName = trim($input['servicio']);
        $srv = $db->find('servicio', function($s) use ($srvName) {
            return mb_stripos($s['nombre_servicio'], $srvName) !== false;
        });
        if (!empty($srv)) $idServicio = (int)$srv[0]['id_servicio'];
    }
    if (!$idServicio) $idServicio = 1;

    $idEmpleado = (int)($input['id_empleado'] ?? 0);
    if (!$idEmpleado && !empty($input['empleado'])) {
        $empName = trim($input['empleado']);
        $emp = $db->find('empleado', function($e) use ($empName) {
            $nom = $e['nombre'] . ' ' . $e['apellido'];
            return (mb_stripos($nom, $empName) !== false || mb_stripos($e['nombre'], $empName) !== false);
        });
        if (!empty($emp)) $idEmpleado = (int)$emp[0]['id_empleado'];
    }
    if (!$idEmpleado) $idEmpleado = 1;

    // Buscar o crear servicio_empleado
    $servemp = $db->find('servicio_empleado', function($se) use ($idServicio, $idEmpleado) {
        return (int)$se['id_servicio'] === $idServicio && (int)$se['id_empleado'] === $idEmpleado;
    });
    if (!empty($servemp)) {
        $idServicioEmpleado = (int)$servemp[0]['id_servicio_empleado'];
    } else {
        $nuevoSE = $db->insert('servicio_empleado', [
            'id_servicio' => $idServicio,
            'id_empleado' => $idEmpleado,
            'modalidad_disponib' => $modalidad,
            'max_por_dia' => 6
        ], 'id_servicio_empleado');
        $idServicioEmpleado = (int)$nuevoSE['id_servicio_empleado'];
    }

    // Insertar cita
    $nuevaCita = $db->insert('citas', [
        'id_usuario' => $idUsuario,
        'id_espacio' => $idEspacio,
        'fecha_hora' => $fechaHora,
        'estado' => $estado,
        'modalidad' => $modalidad,
        'observaciones' => $observaciones,
        'creada_en' => date('Y-m-d H:i:s'),
        'modificada_en' => date('Y-m-d H:i:s')
    ], 'id_cita');

    $idCita = (int)$nuevaCita['id_cita'];

    // Insertar cita_servicio
    $db->insert('cita_servicio', [
        'id_cita' => $idCita,
        'id_servicio_empleado' => $idServicioEmpleado
    ], 'id_cita_servicio');

    // REGISTRO DE AUDITORÍA: Creación
    $empObj = $db->getById('empleado', 'id_empleado', $idEmpleado);
    $empNombre = $empObj ? ($empObj['nombre'] . ' ' . $empObj['apellido']) : 'Especialista';
    logAuditoriaTurno($idCita, 'creacion', "Turno creado para el {$fechaHora} (Modalidad: {$modalidad}, Especialista: {$empNombre}).", $db);

    // BLOQUEO AUTOMÁTICO POR CITA A DOMICILIO
    if (mb_stripos($modalidad, 'domicilio') !== false) {
        $fechaSolo = substr($fechaHora, 0, 10);
        $bloqueoExistente = $db->find('bloqueo_dia', function($b) use ($idEmpleado, $fechaSolo) {
            return (int)($b['id_empleado'] ?? 0) === $idEmpleado && $b['fecha_desde'] <= $fechaSolo && $b['fecha_hasta'] >= $fechaSolo;
        });

        if (empty($bloqueoExistente)) {
            $db->insert('bloqueo_dia', [
                'id_usuario' => $idUsuario,
                'id_empleado' => $idEmpleado,
                'fecha_desde' => $fechaSolo,
                'fecha_hasta' => $fechaSolo,
                'motivo' => "Bloqueo automático: Cita a Domicilio (Turno #{$idCita})"
            ], 'id_bloqueo_dia');
        }
    }

    jsonResponse(true, enrichCita($nuevaCita, $db), 'Turno creado con éxito.', 201);
}

// PUT: Actualizar turno/cita
if ($method === 'PUT' || ($method === 'POST' && ($_POST['_method'] ?? '') === 'PUT')) {
    $input = getJsonInput();
    $id = (int)($input['id_cita'] ?? $input['id'] ?? $_GET['id'] ?? 0);

    if (!$id) {
        jsonResponse(false, null, 'ID de turno no especificado.', 400);
    }

    $existing = $db->getById('citas', 'id_cita', $id);
    if (!$existing) {
        jsonResponse(false, null, 'Turno no encontrado.', 404);
    }

    $fechaHora = trim($input['fecha_hora'] ?? $input['fechaHora'] ?? $existing['fecha_hora']);
    if (strpos($fechaHora, 'T') !== false) {
        $fechaHora = str_replace('T', ' ', rtrim($fechaHora, 'Z'));
        if (strlen($fechaHora) === 16) $fechaHora .= ':00';
    }

    $oldEstado = strtolower(trim($existing['estado']));
    $oldFechaHora = $existing['fecha_hora'];
    $estado = trim($input['estado'] ?? $existing['estado']);
    $modalidad = trim($input['modalidad'] ?? $existing['modalidad']);
    $observaciones = trim($input['observaciones'] ?? $existing['observaciones']);

    $idUsuario = isset($input['id_usuario']) ? (int)$input['id_usuario'] : (int)$existing['id_usuario'];
    $idEspacio = isset($input['id_espacio']) ? (int)$input['id_espacio'] : (int)$existing['id_espacio'];

    // Actualizar cita
    $updated = $db->update('citas', 'id_cita', $id, [
        'id_usuario' => $idUsuario,
        'id_espacio' => $idEspacio,
        'fecha_hora' => $fechaHora,
        'estado' => $estado,
        'modalidad' => $modalidad,
        'observaciones' => $observaciones,
        'modificada_en' => date('Y-m-d H:i:s')
    ]);

    $idEmpleado = 1;
    // Actualizar servicio y empleado si fueron proporcionados
    if (isset($input['id_servicio']) || isset($input['id_empleado']) || isset($input['servicio']) || isset($input['empleado'])) {
        $idServicio = (int)($input['id_servicio'] ?? 0);
        if (!$idServicio && !empty($input['servicio'])) {
            $srv = $db->find('servicio', function($s) use ($input) {
                return mb_stripos($s['nombre_servicio'], trim($input['servicio'])) !== false;
            });
            if (!empty($srv)) $idServicio = (int)$srv[0]['id_servicio'];
        }

        $idEmpleado = (int)($input['id_empleado'] ?? 0);
        if (!$idEmpleado && !empty($input['empleado'])) {
            $emp = $db->find('empleado', function($e) use ($input) {
                $nom = $e['nombre'] . ' ' . $e['apellido'];
                return (mb_stripos($nom, trim($input['empleado'])) !== false);
            });
            if (!empty($emp)) $idEmpleado = (int)$emp[0]['id_empleado'];
        }

        if ($idServicio && $idEmpleado) {
            $servemp = $db->find('servicio_empleado', function($se) use ($idServicio, $idEmpleado) {
                return (int)$se['id_servicio'] === $idServicio && (int)$se['id_empleado'] === $idEmpleado;
            });
            if (!empty($servemp)) {
                $idServicioEmpleado = (int)$servemp[0]['id_servicio_empleado'];
            } else {
                $nuevoSE = $db->insert('servicio_empleado', [
                    'id_servicio' => $idServicio,
                    'id_empleado' => $idEmpleado,
                    'modalidad_disponib' => $modalidad,
                    'max_por_dia' => 6
                ], 'id_servicio_empleado');
                $idServicioEmpleado = (int)$nuevoSE['id_servicio_empleado'];
            }

            // Actualizar o crear cita_servicio
            $citaServicios = $db->find('cita_servicio', function($cs) use ($id) {
                return (int)$cs['id_cita'] === $id;
            });
            if (!empty($citaServicios)) {
                $db->update('cita_servicio', 'id_cita_servicio', $citaServicios[0]['id_cita_servicio'], [
                    'id_servicio_empleado' => $idServicioEmpleado
                ]);
            } else {
                $db->insert('cita_servicio', [
                    'id_cita' => $id,
                    'id_servicio_empleado' => $idServicioEmpleado
                ], 'id_cita_servicio');
            }
        }
    }

    $enrichedUpdated = enrichCita($updated, $db);

    // REGISTRO DE AUDITORÍA Y NOTIFICACIONES
    $cliNom = $enrichedUpdated['cliente'] ?? 'Cliente';
    $srvNom = $enrichedUpdated['servicio'] ?? 'Servicio';
    $empNom = $enrichedUpdated['empleado'] ?? 'Especialista';

    $newEstado = strtolower(trim($estado));
    if ($oldEstado !== $newEstado) {
        if ($newEstado === 'cancelado') {
            logAuditoriaTurno($id, 'cancelacion', "Canceló el turno #{$id} de {$cliNom} ({$srvNom} con {$empNom}). Motivo/Obs: {$observaciones}", $db);
            // NOTIFICACIÓN AUTOMÁTICA DE CANCELACIÓN
            @notificarCancelacion($enrichedUpdated, $observaciones);
        } else {
            logAuditoriaTurno($id, 'cambio_estado', "Cambió el estado del turno #{$id} de {$cliNom} ({$srvNom}) de '{$oldEstado}' a '{$newEstado}'.", $db);
        }
    } elseif ($oldFechaHora !== $fechaHora) {
        logAuditoriaTurno($id, 'reprogramacion', "Reprogramó la fecha del turno #{$id} de {$cliNom} ({$srvNom}) de {$oldFechaHora} a {$fechaHora}.", $db);
    } else {
        logAuditoriaTurno($id, 'modificacion', "Modificó los datos del turno #{$id} de {$cliNom} ({$srvNom}, Modalidad: {$modalidad}).", $db);
    }

    // BLOQUEO AUTOMÁTICO POR CITA A DOMICILIO
    if (mb_stripos($modalidad, 'domicilio') !== false) {
        $fechaSolo = substr($fechaHora, 0, 10);
        $empIdFinal = $enrichedUpdated['id_empleado'] ?? $idEmpleado;
        $bloqueoExistente = $db->find('bloqueo_dia', function($b) use ($empIdFinal, $fechaSolo) {
            return (int)($b['id_empleado'] ?? 0) === $empIdFinal && $b['fecha_desde'] <= $fechaSolo && $b['fecha_hasta'] >= $fechaSolo;
        });

        if (empty($bloqueoExistente)) {
            $db->insert('bloqueo_dia', [
                'id_usuario' => $idUsuario,
                'id_empleado' => $empIdFinal,
                'fecha_desde' => $fechaSolo,
                'fecha_hasta' => $fechaSolo,
                'motivo' => "Bloqueo automático: Cita a Domicilio (Turno #{$id})"
            ], 'id_bloqueo_dia');
        }
    }

    jsonResponse(true, $enrichedUpdated, 'Turno actualizado correctamente.');
}

// DELETE: Desactivar / Cancelar turno o eliminar foto individual
if ($method === 'DELETE' || ($method === 'POST' && ($_POST['_method'] ?? '') === 'DELETE')) {
    $input = getJsonInput();

    // Sub-acción: Eliminar foto individual
    if ($action === 'eliminar_foto' || !empty($input['action']) && $input['action'] === 'eliminar_foto') {
        $idImagen = (int)($input['id_imagen'] ?? $_GET['id_imagen'] ?? 0);
        if (!$idImagen) {
            jsonResponse(false, null, 'ID de imagen no especificado.', 400);
        }
        $img = $db->getById('imagen_cita', 'id_imagen', $idImagen);
        if ($img) {
            $db->delete('imagen_cita', 'id_imagen', $idImagen);
            logAuditoriaTurno((int)$img['id_cita'], 'foto_eliminada', "Se eliminó una foto de sesión.", $db);
        }
        jsonResponse(true, null, 'Foto eliminada correctamente.');
    }

    $id = (int)($input['id_cita'] ?? $input['id'] ?? $_GET['id'] ?? 0);
    if (!$id) {
        jsonResponse(false, null, 'ID de turno no especificado.', 400);
    }

    $existing = $db->getById('citas', 'id_cita', $id);
    if (!$existing) {
        jsonResponse(false, null, 'Turno no encontrado.', 404);
    }

    // Soft delete: marcar como cancelado / inactivo para preservar integridad relacional
    $db->update('citas', 'id_cita', $id, [
        'estado' => 'cancelado',
        'activo' => 0,
        'modificada_en' => date('Y-m-d H:i:s')
    ]);

    logAuditoriaTurno($id, 'desactivacion', "Turno desactivado / cancelado (Soft Delete).", $db);
    registrarCambioDashboard('📅 Turnos', 'Desactivación de Turno', 'Canceló / pasó a inactivo el turno (ID #' . $id . ')');

    jsonResponse(true, ['id' => $id, 'id_cita' => $id, 'estado' => 'cancelado'], 'Turno cancelado / desactivado correctamente.');
}

jsonResponse(false, null, 'Método HTTP no permitido.', 405);

