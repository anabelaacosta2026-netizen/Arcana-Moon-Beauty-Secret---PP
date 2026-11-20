<?php
/**
 * ARCANA — Empleados CRUD API Endpoint (Schema: arcanaestetica)
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/JsonDB.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once __DIR__ . '/response.php';

$db = JsonDB::getInstance();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/**
 * Enriquecer datos del empleado con sus especialidades y modalidad
 */
function enrichEmpleado(array $e, JsonDB $db): array {
    $id = (int)$e['id_empleado'];

    // Buscar especialidades
    $relaciones = $db->find('empleado_especialidad', function($ee) use ($id) {
        return (int)$ee['id_empleado'] === $id;
    });

    $especialidadesNombres = [];
    $especialidadesIds = [];
    foreach ($relaciones as $rel) {
        $espId = (int)$rel['id_especialidad'];
        $especialidadesIds[] = $espId;
        $esp = $db->getById('especialidad', 'id_especialidad', $espId);
        if ($esp) {
            $especialidadesNombres[] = $esp['nombre_especialidad'];
        }
    }

    // Modalidad asignada en el registro de empleado o en servicio_empleado
    $modalidad = $e['modalidad'] ?? null;
    if (!$modalidad) {
        $servemps = $db->find('servicio_empleado', function($se) use ($id) {
            return (int)$se['id_empleado'] === $id;
        });
        $modalidad = !empty($servemps) ? ($servemps[0]['modalidad_disponib'] ?? 'Presencial') : 'Presencial';
    }

    $activo = isset($e['activo']) ? (int)$e['activo'] : 1;

    return [
        'id' => $id,
        'id_empleado' => $id,
        'nombre' => $e['nombre'],
        'apellido' => $e['apellido'],
        'nombre_completo' => $e['nombre'] . ' ' . $e['apellido'],
        'telefono' => $e['telefono'] ?? '',
        'email' => $e['email'],
        'foto' => $e['imagenEmpleado'] ?? 'assets/images/team-avatar.jpg',
        'imagenEmpleado' => $e['imagenEmpleado'] ?? 'assets/images/team-avatar.jpg',
        'modalidad' => $modalidad,
        'activo' => $activo,
        'especialidades' => $especialidadesNombres,
        'especialidades_ids' => $especialidadesIds
    ];
}

// GET: Listar o individual
if ($method === 'GET') {
    $id = $_GET['id'] ?? $_GET['id_empleado'] ?? null;

    if ($id) {
        $empleado = $db->getById('empleado', 'id_empleado', $id);
        if (!$empleado) {
            jsonResponse(false, null, 'Empleado no encontrado.', 404);
        }
        jsonResponse(true, enrichEmpleado($empleado, $db));
    }

    $q = mb_strtolower(trim($_GET['q'] ?? ''));
    $soloActivos = isset($_GET['activo']) ? (int)$_GET['activo'] : null;

    $empleados = $db->getTable('empleado');
    $enriched = array_map(function($e) use ($db) {
        return enrichEmpleado($e, $db);
    }, $empleados);

    if ($soloActivos !== null) {
        $enriched = array_values(array_filter($enriched, function($e) use ($soloActivos) {
            return ((int)$e['activo'] === $soloActivos);
        }));
    }

    if ($q !== '') {
        $enriched = array_values(array_filter($enriched, function($e) use ($q) {
            return (
                mb_stripos($e['nombre'], $q) !== false ||
                mb_stripos($e['apellido'], $q) !== false ||
                mb_stripos($e['email'], $q) !== false ||
                mb_stripos($e['telefono'], $q) !== false ||
                mb_stripos(implode(' ', $e['especialidades']), $q) !== false
            );
        }));
    }

    jsonResponse(true, $enriched);
}

// POST: Crear nuevo empleado
if ($method === 'POST' && ($_POST['_method'] ?? '') !== 'PUT' && ($_POST['_method'] ?? '') !== 'DELETE') {
    $input = getJsonInput();
    $nombre = trim($input['nombre'] ?? '');
    $apellido = trim($input['apellido'] ?? '');
    $email = trim($input['email'] ?? '');
    $telefono = trim($input['telefono'] ?? '');
    $foto = trim($input['imagenEmpleado'] ?? $input['foto'] ?? 'assets/images/team-avatar.jpg');
    $modalidad = trim($input['modalidad'] ?? 'Presencial');
    $activo = isset($input['activo']) ? ((int)$input['activo'] === 1 || $input['activo'] === true || $input['activo'] === '1' ? 1 : 0) : 1;

    $especialidadesInput = $input['especialidades'] ?? [];
    if (is_string($especialidadesInput)) {
        $especialidadesInput = array_map('trim', explode(',', $especialidadesInput));
    }

    if (empty($nombre) || empty($apellido) || empty($email)) {
        jsonResponse(false, null, 'Nombre, apellido y correo electrónico son obligatorios.', 400);
    }

    // Verificar email único
    $existente = $db->find('empleado', function($e) use ($email) {
        return mb_strtolower($e['email']) === mb_strtolower($email);
    });
    if (!empty($existente)) {
        jsonResponse(false, null, 'Ya existe un empleado con este correo electrónico.', 409);
    }

    // 1. Insertar empleado
    $nuevoEmpleado = $db->insert('empleado', [
        'nombre' => $nombre,
        'apellido' => $apellido,
        'telefono' => $telefono,
        'email' => $email,
        'imagenEmpleado' => $foto,
        'modalidad' => $modalidad,
        'activo' => $activo
    ], 'id_empleado');

    $idEmpleado = (int)$nuevoEmpleado['id_empleado'];

    // 2. Asociar especialidades
    foreach ($especialidadesInput as $espNombre) {
        $espNombre = trim($espNombre);
        if (empty($espNombre)) continue;

        $espExistente = $db->find('especialidad', function($esp) use ($espNombre) {
            return mb_strtolower($esp['nombre_especialidad']) === mb_strtolower($espNombre);
        });

        if (!empty($espExistente)) {
            $espId = (int)$espExistente[0]['id_especialidad'];
        } else {
            $nuevaEsp = $db->insert('especialidad', ['nombre_especialidad' => $espNombre], 'id_especialidad');
            $espId = (int)$nuevaEsp['id_especialidad'];
        }

        $db->insert('empleado_especialidad', [
            'id_empleado' => $idEmpleado,
            'id_especialidad' => $espId
        ], 'id_empleado_especialidad');
    }

    registrarCambioDashboard('👩‍💼 Empleados', 'Creación de Empleado', 'Creó el empleado/profesional "' . $nombre . ' ' . $apellido . '" (' . $email . ', Modalidad: ' . $modalidad . ')');

    jsonResponse(true, enrichEmpleado($nuevoEmpleado, $db), 'Empleado registrado correctamente.', 201);
}

// PUT: Actualizar empleado
if ($method === 'PUT' || ($method === 'POST' && ($_POST['_method'] ?? '') === 'PUT')) {
    $input = getJsonInput();
    $id = (int)($input['id_empleado'] ?? $input['id'] ?? $_GET['id'] ?? 0);

    if (!$id) {
        jsonResponse(false, null, 'ID de empleado no especificado.', 400);
    }

    $existing = $db->getById('empleado', 'id_empleado', $id);
    if (!$existing) {
        jsonResponse(false, null, 'Empleado no encontrado.', 404);
    }

    $nombre = trim($input['nombre'] ?? $existing['nombre']);
    $apellido = trim($input['apellido'] ?? $existing['apellido']);
    $email = trim($input['email'] ?? $existing['email']);
    $telefono = trim($input['telefono'] ?? $existing['telefono']);
    $foto = trim($input['imagenEmpleado'] ?? $input['foto'] ?? $existing['imagenEmpleado']);
    $modalidad = trim($input['modalidad'] ?? ($existing['modalidad'] ?? 'Presencial'));
    $activo = isset($input['activo']) ? ((int)$input['activo'] === 1 || $input['activo'] === true || $input['activo'] === '1' ? 1 : 0) : (isset($existing['activo']) ? (int)$existing['activo'] : 1);

    // Actualizar empleado
    $updated = $db->update('empleado', 'id_empleado', $id, [
        'nombre' => $nombre,
        'apellido' => $apellido,
        'telefono' => $telefono,
        'email' => $email,
        'imagenEmpleado' => $foto,
        'modalidad' => $modalidad,
        'activo' => $activo
    ]);

    // Sincronizar modalidad_disponib en servicio_empleado para este empleado
    $servemps = $db->find('servicio_empleado', function($se) use ($id) {
        return (int)$se['id_empleado'] === $id;
    });
    foreach ($servemps as $se) {
        $db->update('servicio_empleado', 'id_servicio_empleado', $se['id_servicio_empleado'], [
            'modalidad_disponib' => $modalidad
        ]);
    }

    // Actualizar especialidades si vienen
    if (isset($input['especialidades'])) {
        $especialidadesInput = $input['especialidades'];
        if (is_string($especialidadesInput)) {
            $especialidadesInput = array_map('trim', explode(',', $especialidadesInput));
        }

        // Eliminar anteriores
        $db->deleteWhere('empleado_especialidad', 'id_empleado', $id);

        foreach ($especialidadesInput as $espNombre) {
            $espNombre = trim($espNombre);
            if (empty($espNombre)) continue;

            $espExistente = $db->find('especialidad', function($esp) use ($espNombre) {
                return mb_strtolower($esp['nombre_especialidad']) === mb_strtolower($espNombre);
            });

            if (!empty($espExistente)) {
                $espId = (int)$espExistente[0]['id_especialidad'];
            } else {
                $nuevaEsp = $db->insert('especialidad', ['nombre_especialidad' => $espNombre], 'id_especialidad');
                $espId = (int)$nuevaEsp['id_especialidad'];
            }

            $db->insert('empleado_especialidad', [
                'id_empleado' => $id,
                'id_especialidad' => $espId
            ], 'id_empleado_especialidad');
        }
    }

    $oldActivo = (int)($existing['activo'] ?? 1);
    $cambiosEmp = [];
    $accionEmp = 'Actualización de Empleado';

    if ($activo === 1 && $oldActivo === 0) {
        $accionEmp = 'Reactivación de Empleado';
        $detalleEmp = 'Reactivó (pasó a activo) al profesional "' . $nombre . ' ' . $apellido . '" (ID #' . $id . ')';
    } elseif ($activo === 0 && $oldActivo === 1) {
        $accionEmp = 'Desactivación de Empleado';
        $detalleEmp = 'Desactivó (pasó a inactivo) al profesional "' . $nombre . ' ' . $apellido . '" (ID #' . $id . ')';
    } else {
        if ($modalidad !== ($existing['modalidad'] ?? 'Presencial')) {
            $cambiosEmp[] = 'modalidad a ' . $modalidad;
        }
        if (isset($input['especialidades'])) {
            $cambiosEmp[] = 'especialidades a [' . implode(', ', (array)$especialidadesInput) . ']';
        }
        if ($email !== ($existing['email'] ?? '')) {
            $cambiosEmp[] = 'email a ' . $email;
        }
        if ($telefono !== ($existing['telefono'] ?? '')) {
            $cambiosEmp[] = 'teléfono a ' . $telefono;
        }

        if (!empty($cambiosEmp)) {
            $detalleEmp = 'Modificó en el profesional "' . $nombre . ' ' . $apellido . '" (ID #' . $id . '): ' . implode(', ', $cambiosEmp);
        } else {
            $detalleEmp = 'Actualizó datos del profesional "' . $nombre . ' ' . $apellido . '" (ID #' . $id . ', Modalidad: ' . $modalidad . ')';
        }
    }

    registrarCambioDashboard('👩‍💼 Empleados', $accionEmp, $detalleEmp, $id);

    jsonResponse(true, enrichEmpleado($updated, $db), 'Empleado actualizado correctamente.');
}

// DELETE: Desactivar empleado (Soft Delete)
if ($method === 'DELETE' || ($method === 'POST' && ($_POST['_method'] ?? '') === 'DELETE')) {
    $input = getJsonInput();
    $id = (int)($input['id_empleado'] ?? $input['id'] ?? $_GET['id'] ?? 0);

    if (!$id) {
        jsonResponse(false, null, 'ID de empleado no especificado.', 400);
    }

    $existing = $db->getById('empleado', 'id_empleado', $id);
    if (!$existing) {
        jsonResponse(false, null, 'Empleado no encontrado.', 404);
    }
    $nombreEmp = $existing['nombre'] . ' ' . $existing['apellido'];

    // Soft delete: cambiar activo a 0 preservando todas las tablas relacionadas
    $db->update('empleado', 'id_empleado', $id, [
        'activo' => 0
    ]);

    registrarCambioDashboard('👩‍💼 Empleados', 'Desactivación de Empleado', 'Desactivó (pasó a inactivo) al profesional "' . $nombreEmp . '" (ID #' . $id . ')');

    jsonResponse(true, ['id' => $id, 'id_empleado' => $id, 'activo' => 0], 'Empleado desactivado correctamente (estado inactivo).');
}

jsonResponse(false, null, 'Método HTTP no permitido.', 405);
