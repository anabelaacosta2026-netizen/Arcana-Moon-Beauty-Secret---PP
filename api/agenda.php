<?php
/**
 * ARCANA — Public Appointment (Agenda) Booking & Availability API Endpoint (Schema: arcanaestetica)
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/JsonDB.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/config/mail.php';
require_once __DIR__ . '/notificaciones.php';
require_once __DIR__ . '/response.php';

$db = JsonDB::getInstance();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Horarios estándar de atención del salón
const HORARIOS_STANDARD = ['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];
const PORCENTAJE_SENA = 0.30; // 30% de seña para confirmar reserva

/**
 * Helper: Obtener precio de un servicio
 */
function getPrecioServicio(int $idServicio, JsonDB $db): float {
    $precios = $db->find('precio_servicio', function($p) use ($idServicio) {
        return (int)$p['id_servicio'] === $idServicio;
    });
    if (!empty($precios)) {
        $ultimo = end($precios);
        return (float)($ultimo['precio'] ?? 25000);
    }
    return 25000.0;
}

/**
 * Helper: Obtener los empleados autorizados para un servicio
 */
function getEmpleadosPorServicio(int $idServicio, JsonDB $db): array {
    $servemps = $db->find('servicio_empleado', function($se) use ($idServicio) {
        return (int)$se['id_servicio'] === $idServicio;
    });

    $empleados = [];
    $empleadosIds = [];

    foreach ($servemps as $se) {
        $idEmp = (int)$se['id_empleado'];
        if (in_array($idEmp, $empleadosIds)) continue;
        $emp = $db->getById('empleado', 'id_empleado', $idEmp);
        if ($emp) {
            $empleadosIds[] = $idEmp;
            $empleados[] = [
                'id_empleado' => $idEmp,
                'nombre' => $emp['nombre'],
                'apellido' => $emp['apellido'],
                'nombre_completo' => $emp['nombre'] . ' ' . $emp['apellido'],
                'modalidad' => $se['modalidad_disponib'] ?? 'Presencial'
            ];
        }
    }

    // Si no hay asignación directa en servicio_empleado, buscar por categoría / especialidad
    if (empty($empleados)) {
        $servicioObj = $db->getById('servicio', 'id_servicio', $idServicio);
        $catServicio = mb_strtolower($servicioObj['categoria'] ?? '');
        $allEmpleados = $db->getTable('empleado');
        foreach ($allEmpleados as $emp) {
            $idEmp = (int)$emp['id_empleado'];
            if (in_array($idEmp, $empleadosIds)) continue;
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
                $empleadosIds[] = $idEmp;
                $empleados[] = [
                    'id_empleado' => $idEmp,
                    'nombre' => $emp['nombre'],
                    'apellido' => $emp['apellido'],
                    'nombre_completo' => $emp['nombre'] . ' ' . $emp['apellido'],
                    'modalidad' => 'Presencial'
                ];
            }
        }
    }

    return $empleados;
}

// -------------------------------------------------------------
// Endpoint: Especialistas por Servicio (Filtro Dinámico)
// -------------------------------------------------------------
if ($action === 'especialistas_servicio' && $method === 'GET') {
    $idServicio = (int)($_GET['id_servicio'] ?? 0);
    $nombreServicio = trim($_GET['servicio'] ?? '');

    if (!$idServicio && !empty($nombreServicio)) {
        $servObj = $db->find('servicio', function($s) use ($nombreServicio) {
            return mb_strtolower($s['nombre_servicio']) === mb_strtolower($nombreServicio) ||
                   mb_stripos($s['nombre_servicio'], $nombreServicio) !== false;
        });
        if (!empty($servObj)) {
            $idServicio = (int)$servObj[0]['id_servicio'];
        }
    }

    if (!$idServicio) {
        $empleados = $db->getTable('empleado');
        $enriched = array_map(function($e) {
            return [
                'id_empleado' => (int)$e['id_empleado'],
                'nombre' => $e['nombre'],
                'apellido' => $e['apellido'],
                'nombre_completo' => $e['nombre'] . ' ' . $e['apellido'],
                'modalidad' => 'Presencial'
            ];
        }, $empleados);
        jsonResponse(true, $enriched);
    }

    $empleados = getEmpleadosPorServicio($idServicio, $db);
    jsonResponse(true, $empleados);
}

// -------------------------------------------------------------
// 1. GET: Disponibilidad de Horarios y Días Bloqueados (General y por Empleado)
// -------------------------------------------------------------
if ($action === 'disponibilidad' && $method === 'GET') {
    $fecha = trim($_GET['fecha'] ?? date('Y-m-d'));
    $idServicio = (int)($_GET['id_servicio'] ?? 0);
    $nombreServicio = trim($_GET['servicio'] ?? '');
    $idEmpleado = (int)($_GET['id_empleado'] ?? 0);
    $nombreEmpleado = trim($_GET['empleado'] ?? '');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        jsonResponse(false, null, 'Formato de fecha no válido (YYYY-MM-DD).', 400);
    }

    if (!$idServicio && !empty($nombreServicio)) {
        $servObj = $db->find('servicio', function($s) use ($nombreServicio) {
            return mb_strtolower($s['nombre_servicio']) === mb_strtolower($nombreServicio) ||
                   mb_stripos($s['nombre_servicio'], $nombreServicio) !== false;
        });
        if (!empty($servObj)) {
            $idServicio = (int)$servObj[0]['id_servicio'];
        }
    }

    $empleadosHabilitados = ($idServicio > 0) ? getEmpleadosPorServicio($idServicio, $db) : [];
    $idsHabilitados = array_map(function($e) { return (int)$e['id_empleado']; }, $empleadosHabilitados);

    if (!$idEmpleado && !empty($nombreEmpleado) && $nombreEmpleado !== 'Cualquier especialista disponible') {
        $empObj = $db->find('empleado', function($e) use ($nombreEmpleado) {
            $nom = $e['nombre'] . ' ' . $e['apellido'];
            return (mb_stripos($nom, $nombreEmpleado) !== false || mb_stripos($e['nombre'], $nombreEmpleado) !== false);
        });
        if (!empty($empObj)) $idEmpleado = (int)$empObj[0]['id_empleado'];
    }

    // A. Verificar si el día está en bloqueo_dia (General o para este empleado)
    $bloqueos = $db->find('bloqueo_dia', function($b) use ($fecha, $idEmpleado, $idsHabilitados) {
        $desde = $b['fecha_desde'] ?? '';
        $hasta = $b['fecha_hasta'] ?? '';
        $enRango = ($fecha >= $desde && $fecha <= $hasta);
        if (!$enRango) return false;

        $empBloqueo = (int)($b['id_empleado'] ?? 0);
        // Si es bloqueo general (0 o null), bloquea todo el salón
        if ($empBloqueo === 0) return true;
        // Si es bloqueo puntual de este empleado
        if ($idEmpleado > 0 && $empBloqueo === $idEmpleado) return true;
        // Si no se eligió empleado específico pero todos los habilitados están bloqueados
        if ($idEmpleado === 0 && !empty($idsHabilitados) && in_array($empBloqueo, $idsHabilitados) && count($idsHabilitados) === 1) {
            return true;
        }

        return false;
    });

    $diaBloqueado = !empty($bloqueos);
    $motivoBloqueo = $diaBloqueado ? ($bloqueos[0]['motivo'] ?? 'Día no disponible para turnos') : '';

    // B. Obtener citas activas en esa fecha
    $citasDelDia = $db->find('citas', function($c) use ($fecha, $idEmpleado, $idsHabilitados, $db) {
        $f = substr($c['fecha_hora'] ?? '', 0, 10);
        $estado = strtolower($c['estado'] ?? '');
        if ($f !== $fecha || $estado === 'cancelado') return false;

        if ($idEmpleado > 0) {
            $idCita = (int)$c['id_cita'];
            $css = $db->find('cita_servicio', function($item) use ($idCita) {
                return (int)$item['id_cita'] === $idCita;
            });
            if (!empty($css)) {
                $se = $db->getById('servicio_empleado', 'id_servicio_empleado', (int)$css[0]['id_servicio_empleado']);
                if ($se && (int)$se['id_empleado'] !== $idEmpleado) {
                    return false;
                }
            }
        }
        return true;
    });

    // C. Verificar si el empleado tiene cita a domicilio ese día (bloquea todo el día)
    $tieneDomicilio = false;
    foreach ($citasDelDia as $c) {
        if (mb_stripos($c['modalidad'] ?? '', 'domicilio') !== false) {
            $tieneDomicilio = true;
            $diaBloqueado = true;
            $motivoBloqueo = 'El profesional realiza atención a domicilio en esta fecha.';
            break;
        }
    }

    $horariosOcupados = [];
    if (!$diaBloqueado) {
        // Contar citas por horario
        $citasPorHora = [];
        foreach ($citasDelDia as $c) {
            $hora = substr($c['fecha_hora'] ?? '', 11, 5);
            if ($hora) {
                if (!isset($citasPorHora[$hora])) $citasPorHora[$hora] = 0;
                $citasPorHora[$hora]++;
            }
        }

        // Si se eligió empleado específico, 1 cita ya ocupa el horario
        // Si es general y hay múltiples especialistas para el servicio, solo se ocupa si todos los especialistas están ocupados
        $maxSimultaneos = ($idEmpleado > 0) ? 1 : (max(1, count($idsHabilitados)));

        foreach ($citasPorHora as $hora => $cant) {
            if ($cant >= $maxSimultaneos) {
                $horariosOcupados[] = $hora;
            }
        }
    }

    $horariosDisponibles = $diaBloqueado ? [] : array_values(array_diff(HORARIOS_STANDARD, $horariosOcupados));
    $completo = $diaBloqueado || empty($horariosDisponibles);

    jsonResponse(true, [
        'fecha' => $fecha,
        'id_servicio' => $idServicio,
        'id_empleado' => $idEmpleado,
        'bloqueado' => $diaBloqueado,
        'motivo_bloqueo' => $motivoBloqueo,
        'completo' => $completo,
        'todos_horarios' => HORARIOS_STANDARD,
        'horarios_ocupados' => $horariosOcupados,
        'horarios_disponibles' => $horariosDisponibles
    ]);
}

// -------------------------------------------------------------
// GESTIÓN DE BLOQUEO DE DÍAS (Para Administradora / Dashboard)
// -------------------------------------------------------------
if ($action === 'listar_bloqueos' && $method === 'GET') {
    $bloqueos = $db->getTable('bloqueo_dia');
    $enriched = array_map(function($b) use ($db) {
        $idEmp = (int)($b['id_empleado'] ?? 0);
        $empNombre = 'Todo el Salón (General)';
        if ($idEmp > 0) {
            $emp = $db->getById('empleado', 'id_empleado', $idEmp);
            if ($emp) $empNombre = $emp['nombre'] . ' ' . $emp['apellido'];
        }
        return [
            'id_bloqueo_dia' => (int)($b['id_bloqueo_dia'] ?? 0),
            'id_empleado' => $idEmp,
            'empleado' => $empNombre,
            'fecha_desde' => $b['fecha_desde'],
            'fecha_hasta' => $b['fecha_hasta'],
            'motivo' => $b['motivo'] ?? 'Día no laborable'
        ];
    }, $bloqueos);

    usort($enriched, function($a, $b) {
        return strcmp($b['fecha_desde'], $a['fecha_desde']);
    });

    jsonResponse(true, $enriched);
}

if ($action === 'crear_bloqueo' && $method === 'POST') {
    if (!isAdmin()) {
        jsonResponse(false, null, 'Acceso denegado. Solo administradores pueden bloquear fechas.', 403);
    }
    $input = getJsonInput();
    $fechaDesde = trim($input['fecha_desde'] ?? '');
    $fechaHasta = trim($input['fecha_hasta'] ?? $fechaDesde);
    $idEmpleado = (int)($input['id_empleado'] ?? 0);
    $motivo = trim($input['motivo'] ?? 'Día no disponible');

    if (empty($fechaDesde)) {
        jsonResponse(false, null, 'Debe especificar al menos la fecha de inicio del bloqueo.', 400);
    }

    $currentUser = getCurrentUser();
    $nuevoBloqueo = $db->insert('bloqueo_dia', [
        'id_usuario' => (int)($currentUser['id_usuario'] ?? 1),
        'id_empleado' => $idEmpleado,
        'fecha_desde' => $fechaDesde,
        'fecha_hasta' => $fechaHasta,
        'motivo' => $motivo
    ], 'id_bloqueo_dia');

    $destinatario = 'Todo el Salón';
    if ($idEmpleado > 0) {
        $empRecord = $db->getById('empleado', 'id_empleado', $idEmpleado);
        if ($empRecord) {
            $destinatario = 'Especialista ' . $empRecord['nombre'] . ' ' . $empRecord['apellido'];
        }
    }

    $periodo = $fechaDesde . ($fechaHasta && $fechaHasta !== $fechaDesde ? ' al ' . $fechaHasta : '');
    $detalleLog = "Bloqueó fechas ({$periodo}) para {$destinatario}" . ($motivo ? " por motivo: \"{$motivo}\"" : '');

    registrarCambioDashboard('🔒 Bloqueo de Días', 'Bloqueo de Fechas', $detalleLog, (int)($nuevoBloqueo['id_bloqueo_dia'] ?? 0));

    jsonResponse(true, $nuevoBloqueo, 'Período bloqueado exitosamente.', 201);
}

if ($action === 'eliminar_bloqueo' && ($method === 'POST' || $method === 'DELETE')) {
    if (!isAdmin()) {
        jsonResponse(false, null, 'Acceso denegado.', 403);
    }
    $input = getJsonInput();
    $idBloqueo = (int)($input['id_bloqueo_dia'] ?? $_GET['id_bloqueo_dia'] ?? 0);
    if (!$idBloqueo) {
        jsonResponse(false, null, 'ID de bloqueo requerido.', 400);
    }

    $bloqueoActual = $db->getById('bloqueo_dia', 'id_bloqueo_dia', $idBloqueo);
    $descBloqueo = '';
    if ($bloqueoActual) {
        $periodo = ($bloqueoActual['fecha_desde'] ?? '') . (($bloqueoActual['fecha_hasta'] ?? '') !== ($bloqueoActual['fecha_desde'] ?? '') ? ' al ' . ($bloqueoActual['fecha_hasta'] ?? '') : '');
        $descBloqueo = " del período {$periodo}";
    }

    $db->delete('bloqueo_dia', 'id_bloqueo_dia', $idBloqueo);
    registrarCambioDashboard('🔒 Bloqueo de Días', 'Desbloqueo de Fechas', 'Eliminó el bloqueo de agenda' . $descBloqueo . ' (ID #' . $idBloqueo . ')', $idBloqueo);

    jsonResponse(true, null, 'Bloqueo eliminado correctamente.');
}

// -------------------------------------------------------------
// 2. GET: Mis Servicios Contratados / Turnos del Usuario
// -------------------------------------------------------------
if ($action === 'mis_servicios' && $method === 'GET') {
    $email = trim($_GET['email'] ?? '');
    $idUsuario = 0;

    $currentUser = getCurrentUser();
    if ($currentUser && !empty($currentUser['id_usuario'])) {
        $idUsuario = (int)$currentUser['id_usuario'];
        $email = $currentUser['email'];
    } elseif (!empty($email)) {
        $usuarios = $db->find('usuario', function($u) use ($email) {
            return mb_strtolower($u['email']) === mb_strtolower($email);
        });
        if (!empty($usuarios)) {
            $idUsuario = (int)$usuarios[0]['id_usuario'];
        }
    }

    if (!$idUsuario && empty($email)) {
        jsonResponse(true, [
            'citas' => [],
            'total_servicios' => 0,
            'costo_total' => 0,
            'seña_total' => 0
        ]);
    }

    // Buscar citas del usuario
    $citas = $db->find('citas', function($c) use ($idUsuario) {
        return (int)($c['id_usuario'] ?? 0) === $idUsuario;
    });

    // Ordenar de más reciente a más antigua
    usort($citas, function($a, $b) {
        return strcmp($b['fecha_hora'] ?? '', $a['fecha_hora'] ?? '');
    });

    $citasEnriquecidas = [];
    $costoTotalAcumulado = 0.0;
    $señaTotalAcumulada = 0.0;

    foreach ($citas as $c) {
        $idCita = (int)$c['id_cita'];

        // Cita Servicio -> Servicio
        $citaServicios = $db->find('cita_servicio', function($cs) use ($idCita) {
            return (int)$cs['id_cita'] === $idCita;
        });

        $servicioNombre = 'Servicio Especial';
        $empleadoNombre = 'Especialista';
        $precio = 25000.0;

        if (!empty($citaServicios)) {
            $idSE = (int)$citaServicios[0]['id_servicio_empleado'];
            $se = $db->getById('servicio_empleado', 'id_servicio_empleado', $idSE);
            if ($se) {
                $srv = $db->getById('servicio', 'id_servicio', (int)$se['id_servicio']);
                if ($srv) {
                    $servicioNombre = $srv['nombre_servicio'];
                    $precio = getPrecioServicio((int)$srv['id_servicio'], $db);
                }
                $emp = $db->getById('empleado', 'id_empleado', (int)$se['id_empleado']);
                if ($emp) {
                    $empleadoNombre = $emp['nombre'] . ' ' . $emp['apellido'];
                }
            }
        }

        $montoSena = round($precio * PORCENTAJE_SENA);
        $estado = strtolower($c['estado'] ?? 'pendiente');
        $senaAbonada = ($estado === 'confirmado' || $estado === 'completado' || str_contains(strtolower($c['observaciones'] ?? ''), 'seña pagada'));

        if ($estado !== 'cancelado') {
            $costoTotalAcumulado += $precio;
            $señaTotalAcumulada += $montoSena;
        }

        $citasEnriquecidas[] = [
            'id_cita' => $idCita,
            'servicio' => $servicioNombre,
            'especialista' => $empleadoNombre,
            'fecha_hora' => $c['fecha_hora'],
            'fecha' => substr($c['fecha_hora'], 0, 10),
            'hora' => substr($c['fecha_hora'], 11, 5),
            'modalidad' => $c['modalidad'] ?? 'Presencial',
            'estado' => $estado,
            'observaciones' => $c['observaciones'] ?? '',
            'precio_total' => $precio,
            'monto_sena' => $montoSena,
            'porcentaje_sena' => (PORCENTAJE_SENA * 100) . '%',
            'sena_abonada' => $senaAbonada
        ];
    }

    jsonResponse(true, [
        'citas' => $citasEnriquecidas,
        'total_servicios' => count($citasEnriquecidas),
        'costo_total' => $costoTotalAcumulado,
        'seña_total' => $señaTotalAcumulada
    ]);
}

// -------------------------------------------------------------
// 3. POST: Agendar Cita con Validaciones de Disponibilidad
// -------------------------------------------------------------
if ($method === 'POST' && ($action === '' || $action === 'agendar' || $action === 'crear')) {
    $input = getJsonInput();
    $nombre = trim($input['nombre'] ?? '');
    $apellido = trim($input['apellido'] ?? '');
    $email = trim($input['email'] ?? '');
    $telefono = trim($input['telefono'] ?? '');
    $servicio = trim($input['servicio'] ?? '');
    $empleado = trim($input['empleado'] ?? 'Cualquier especialista disponible');
    $fecha = trim($input['fecha'] ?? date('Y-m-d'));
    $hora = trim($input['hora'] ?? '10:00');
    $modalidad = trim($input['modalidad'] ?? 'Presencial');
    $observaciones = trim($input['observaciones'] ?? '-');

    if (empty($nombre) || empty($apellido) || empty($email) || empty($telefono) || empty($servicio)) {
        jsonResponse(false, null, 'Por favor completa todos los campos obligatorios.', 400);
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        jsonResponse(false, null, 'Fecha inválida.', 400);
    }

    // Normalizar hora a HH:MM:00
    if (strlen($hora) === 5) {
        $horaFormateada = $hora . ':00';
    } else {
        $horaFormateada = substr($hora, 0, 8);
    }
    $fechaHora = $fecha . ' ' . $horaFormateada;

    // VALIDACIÓN 1: El día está bloqueado en bloqueo_dia
    $bloqueos = $db->find('bloqueo_dia', function($b) use ($fecha) {
        return ($fecha >= ($b['fecha_desde'] ?? '') && $fecha <= ($b['fecha_hasta'] ?? ''));
    });
    if (!empty($bloqueos)) {
        $motivo = $bloqueos[0]['motivo'] ?? 'Día no laborable';
        jsonResponse(false, null, "El día seleccionado ({$fecha}) no se encuentra disponible para turnos ({$motivo}). Por favor selecciona otra fecha.", 409);
    }

    // VALIDACIÓN 2: Buscar o registrar cliente
    $usuarios = $db->find('usuario', function($u) use ($email) {
        return mb_strtolower($u['email']) === mb_strtolower($email);
    });

    if (!empty($usuarios)) {
        $idUsuario = (int)$usuarios[0]['id_usuario'];
    } else {
        $nuevoU = $db->insert('usuario', [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'email' => $email,
            'contrasena' => password_hash('123456', PASSWORD_BCRYPT),
            'fecha_registro' => date('Y-m-d'),
            'activo' => 1,
            'telefono' => $telefono,
            'DNI' => (string)rand(30000000, 48000000),
            'id_rol' => 2
        ], 'id_usuario');
        $idUsuario = (int)$nuevoU['id_usuario'];
    }

    // VALIDACIÓN 3: Que el MISMO USUARIO no tenga la misma cita el mismo día y a la misma hora
    $citaDuplicadaUsuario = $db->find('citas', function($c) use ($idUsuario, $fechaHora) {
        $cHora = substr($c['fecha_hora'] ?? '', 0, 16);
        $targetHora = substr($fechaHora, 0, 16);
        $estado = strtolower($c['estado'] ?? '');
        return ((int)($c['id_usuario'] ?? 0) === $idUsuario && $cHora === $targetHora && $estado !== 'cancelado');
    });

    if (!empty($citaDuplicadaUsuario)) {
        jsonResponse(false, null, "Ya tienes una cita agendada para el día {$fecha} a las " . substr($hora, 0, 5) . " hs. No es posible agendar dos turnos al mismo tiempo.", 409);
    }

    // 4. Resolver espacio
    $espacios = $db->getTable('espacio');
    $idEspacio = !empty($espacios) ? (int)$espacios[0]['id_espacio'] : 1;

    // 5. Resolver servicio
    $servicios = $db->find('servicio', function($s) use ($servicio) {
        return mb_stripos($s['nombre_servicio'], $servicio) !== false;
    });
    $idServicio = !empty($servicios) ? (int)$servicios[0]['id_servicio'] : 1;
    $precioTotal = getPrecioServicio($idServicio, $db);
    $montoSena = round($precioTotal * PORCENTAJE_SENA);

    // Obtener especialistas habilitados para este servicio
    $empleadosAutorizados = getEmpleadosPorServicio($idServicio, $db);
    $idsAutorizados = array_map(function($e) { return (int)$e['id_empleado']; }, $empleadosAutorizados);

    $idEmpleadoSeleccionado = 0;
    if (!empty($empleado) && $empleado !== 'Cualquier especialista disponible') {
        $empObj = $db->find('empleado', function($e) use ($empleado) {
            $nom = $e['nombre'] . ' ' . $e['apellido'];
            return (mb_stripos($nom, $empleado) !== false || mb_stripos($e['nombre'], $empleado) !== false);
        });
        if (!empty($empObj)) {
            $idEmpleadoSeleccionado = (int)$empObj[0]['id_empleado'];
        }

        // VALIDACIÓN: El empleado seleccionado debe estar habilitado para este servicio
        if (!empty($idsAutorizados) && !in_array($idEmpleadoSeleccionado, $idsAutorizados)) {
            $nomServ = $servicios[0]['nombre_servicio'] ?? $servicio;
            jsonResponse(false, null, "El profesional seleccionado ({$empleado}) no realiza el servicio '{$nomServ}'. Por favor selecciona un especialista a cargo de este servicio.", 400);
        }

        // VALIDACIÓN: Modalidad ofrecida por el especialista seleccionado
        if ($idEmpleadoSeleccionado > 0) {
            $empRecord = $db->getById('empleado', 'id_empleado', $idEmpleadoSeleccionado);
            $modEmp = strtolower(trim($empRecord['modalidad'] ?? 'presencial'));
            $modSolicitada = strtolower(trim($modalidad));

            if ($modEmp === 'presencial' && $modSolicitada === 'domicilio') {
                jsonResponse(false, null, "El profesional seleccionado ({$empleado}) no ofrece atención a domicilio (solo presencial en el salón).", 400);
            }
            if ($modEmp === 'domicilio' && $modSolicitada === 'presencial') {
                jsonResponse(false, null, "El profesional seleccionado ({$empleado}) no ofrece atención en el salón (solo servicio a domicilio).", 400);
            }
        }
    }

    // Si no especificó profesional o puso "Cualquier especialista", asignar uno disponible para este servicio
    if ($idEmpleadoSeleccionado === 0) {
        if (!empty($idsAutorizados)) {
            foreach ($idsAutorizados as $idEmpCheck) {
                // Verificar si tiene cita a esa hora
                $citaExistente = $db->find('citas', function($c) use ($idEmpCheck, $fechaHora, $db) {
                    $cHora = substr($c['fecha_hora'] ?? '', 0, 16);
                    $targetHora = substr($fechaHora, 0, 16);
                    $estado = strtolower($c['estado'] ?? '');
                    if ($cHora !== $targetHora || $estado === 'cancelado') return false;

                    $idCita = (int)$c['id_cita'];
                    $css = $db->find('cita_servicio', function($item) use ($idCita) {
                        return (int)$item['id_cita'] === $idCita;
                    });
                    if (!empty($css)) {
                        $se = $db->getById('servicio_empleado', 'id_servicio_empleado', (int)$css[0]['id_servicio_empleado']);
                        if ($se && (int)$se['id_empleado'] === $idEmpCheck) {
                            return true;
                        }
                    }
                    return false;
                });

                if (empty($citaExistente)) {
                    $idEmpleadoSeleccionado = $idEmpCheck;
                    break;
                }
            }
            if ($idEmpleadoSeleccionado === 0) {
                $idEmpleadoSeleccionado = $idsAutorizados[0];
            }
        } else {
            $allEmp = $db->getTable('empleado');
            $idEmpleadoSeleccionado = !empty($allEmp) ? (int)$allEmp[0]['id_empleado'] : 1;
        }
    }

    $idEmpleado = $idEmpleadoSeleccionado;

    // VALIDACIÓN 4: Que el horario no esté ya ocupado por este empleado específico
    $citaHorarioOcupado = $db->find('citas', function($c) use ($fechaHora, $idEmpleado, $db) {
        $cHora = substr($c['fecha_hora'] ?? '', 0, 16);
        $targetHora = substr($fechaHora, 0, 16);
        $estado = strtolower($c['estado'] ?? '');
        if ($cHora !== $targetHora || $estado === 'cancelado') return false;

        $idCita = (int)$c['id_cita'];
        $css = $db->find('cita_servicio', function($item) use ($idCita) {
            return (int)$item['id_cita'] === $idCita;
        });
        if (!empty($css)) {
            $se = $db->getById('servicio_empleado', 'id_servicio_empleado', (int)$css[0]['id_servicio_empleado']);
            if ($se && (int)$se['id_empleado'] === $idEmpleado) {
                return true;
            }
        }
        return false;
    });

    if (!empty($citaHorarioOcupado)) {
        jsonResponse(false, null, "El horario de las " . substr($hora, 0, 5) . " hs para el día {$fecha} ya se encuentra ocupado con este especialista. Por favor elige otro horario disponible.", 409);
    }

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

    // 6. Crear cita
    $nuevaCita = $db->insert('citas', [
        'id_usuario' => $idUsuario,
        'id_espacio' => $idEspacio,
        'fecha_hora' => $fechaHora,
        'estado' => 'pendiente',
        'modalidad' => $modalidad,
        'observaciones' => $observaciones,
        'creada_en' => date('Y-m-d H:i:s'),
        'modificada_en' => date('Y-m-d H:i:s')
    ], 'id_cita');

    $idCita = (int)$nuevaCita['id_cita'];

    // 7. Vincular cita_servicio
    $db->insert('cita_servicio', [
        'id_cita' => $idCita,
        'id_servicio_empleado' => $idServicioEmpleado
    ], 'id_cita_servicio');

    // Auto-login o actualización de sesión si es usuario no autenticado
    if (!isLoggedIn()) {
        $sessionUser = $db->getById('usuario', 'id_usuario', $idUsuario);
        if ($sessionUser) {
            $sessionUser['id'] = $idUsuario;
            $sessionUser['rol'] = 'usuario';
            loginUser($sessionUser);
        }
    }

    jsonResponse(true, [
        'id' => $idCita,
        'id_cita' => $idCita,
        'cliente' => $nombre . ' ' . $apellido,
        'servicio' => $servicio,
        'fecha_hora' => $fechaHora,
        'fecha' => $fecha,
        'hora' => substr($hora, 0, 5),
        'modalidad' => $modalidad,
        'estado' => 'pendiente',
        'precio_total' => $precioTotal,
        'monto_sena' => $montoSena,
        'checkout_url' => 'mercadopago-checkout.php?id_cita=' . $idCita
    ], '¡Turno reservado con éxito! Procede a abonar la seña para confirmar tu lugar.');
}

jsonResponse(false, null, 'Acción no soportada.', 400);
