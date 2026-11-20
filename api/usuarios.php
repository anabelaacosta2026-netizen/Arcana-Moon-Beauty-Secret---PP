<?php
/**
 * ARCANA — Usuarios CRUD API Endpoint (Schema: arcanaestetica)
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/JsonDB.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once __DIR__ . '/response.php';

$db = JsonDB::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Enriquecer y limpiar datos del usuario para el frontend
 */
function enrichUsuario(array $u, JsonDB $db): array {
    unset($u['contrasena'], $u['password_hash']);
    $idRol = (int)($u['id_rol'] ?? 2);
    $rol = $db->getById('roles', 'id_rol', $idRol);
    $rolNombre = $rol ? $rol['nombre_rol'] : ($u['rol'] ?? 'usuario');

    $activo = isset($u['activo']) ? (int)$u['activo'] : 1;
    $estado = ($activo === 1) ? 'activo' : 'inactivo';

    $isSuper = ($idRol === 1 || $idRol === 4 || str_contains(strtolower($rolNombre), 'super') || !empty($u['is_superadmin']) || strtolower($u['email'] ?? '') === 'anabelaacosta2026@gmail.com');

    return [
        'id' => (int)$u['id_usuario'],
        'id_usuario' => (int)$u['id_usuario'],
        'nombre' => $u['nombre'],
        'apellido' => $u['apellido'],
        'nombre_completo' => trim($u['nombre'] . ' ' . ($u['apellido'] ?? '')),
        'email' => $u['email'],
        'telefono' => $u['telefono'] ?? '',
        'dni' => $u['DNI'] ?? ($u['dni'] ?? ''),
        'DNI' => $u['DNI'] ?? ($u['dni'] ?? ''),
        'id_rol' => $idRol,
        'rol' => $rolNombre,
        'is_superadmin' => $isSuper,
        'activo' => $activo,
        'estado' => $estado,
        'fecha_registro' => $u['fecha_registro'] ?? date('Y-m-d'),
        'fechaRegistro' => $u['fecha_registro'] ?? date('Y-m-d')
    ];
}

// GET: Listar, individual o historial
if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    $id = $_GET['id'] ?? $_GET['id_usuario'] ?? null;

    if ($action === 'historial' && $id) {
        $usuario = $db->getById('usuario', 'id_usuario', $id);
        if (!$usuario) {
            jsonResponse(false, null, 'Usuario no encontrado.', 404);
        }
        $enrichedUser = enrichUsuario($usuario, $db);
        if (!isSuperAdmin() && !empty($enrichedUser['is_superadmin'])) {
            jsonResponse(false, null, 'Usuario no encontrado.', 404);
        }

        $citas = $db->getTable('citas');
        $userCitas = array_values(array_filter($citas, function($c) use ($id) {
            return (int)($c['id_usuario'] ?? 0) === (int)$id;
        }));

        $enrichedCitas = array_map(function($c) use ($db) {
            $idCita = (int)$c['id_cita'];
            $serviciosText = [];
            $empleadosText = [];
            $citaServicios = $db->getTable('cita_servicio');
            $relaciones = array_filter($citaServicios, fn($cs) => (int)$cs['id_cita'] === $idCita);

            foreach ($relaciones as $rel) {
                $se = $db->getById('servicio_empleado', 'id_servicio_empleado', (int)$rel['id_servicio_empleado']);
                if ($se) {
                    $srv = $db->getById('servicio', 'id_servicio', (int)$se['id_servicio']);
                    if ($srv) $serviciosText[] = $srv['nombre_servicio'] ?? $srv['nombre'];
                    $emp = $db->getById('empleado', 'id_empleado', (int)$se['id_empleado']);
                    if ($emp) $empleadosText[] = trim($emp['nombre'] . ' ' . ($emp['apellido'] ?? ''));
                }
            }

            if (empty($serviciosText) && !empty($c['id_servicio'])) {
                $srv = $db->getById('servicio', 'id_servicio', (int)$c['id_servicio']);
                if ($srv) $serviciosText[] = $srv['nombre_servicio'] ?? $srv['nombre'];
            }
            if (empty($empleadosText) && !empty($c['id_empleado'])) {
                $emp = $db->getById('empleado', 'id_empleado', (int)$c['id_empleado']);
                if ($emp) $empleadosText[] = trim($emp['nombre'] . ' ' . ($emp['apellido'] ?? ''));
            }

            $espacio = $db->getById('espacio', 'id_espacio', (int)($c['id_espacio'] ?? 1));

            return [
                'id_cita' => $idCita,
                'servicio' => !empty($serviciosText) ? implode(', ', array_unique($serviciosText)) : 'Servicio Arcana',
                'empleado' => !empty($empleadosText) ? implode(', ', array_unique($empleadosText)) : 'Equipo Arcana',
                'espacio' => $espacio['nombre'] ?? 'Cabina Principal',
                'fecha_hora' => $c['fecha_hora'] ?? '-',
                'modalidad' => $c['modalidad'] ?? 'Presencial',
                'estado' => $c['estado'] ?? 'confirmado',
                'observaciones' => $c['observaciones'] ?? ''
            ];
        }, $userCitas);

        jsonResponse(true, [
            'usuario' => $enrichedUser,
            'citas' => $enrichedCitas,
            'total_turnos' => count($enrichedCitas)
        ]);
    }

    if ($id) {
        $usuario = $db->getById('usuario', 'id_usuario', $id);
        if (!$usuario) {
            jsonResponse(false, null, 'Usuario no encontrado.', 404);
        }
        $enrichedUser = enrichUsuario($usuario, $db);
        if (!isSuperAdmin() && !empty($enrichedUser['is_superadmin'])) {
            jsonResponse(false, null, 'Usuario no encontrado.', 404);
        }
        jsonResponse(true, $enrichedUser);
    }

    $rolFilter = trim($_GET['rol'] ?? '');
    $estadoFilter = trim($_GET['estado'] ?? '');
    $q = mb_strtolower(trim($_GET['q'] ?? ''));

    $usuarios = $db->getTable('usuario');
    $enriched = array_map(function($u) use ($db) {
        return enrichUsuario($u, $db);
    }, $usuarios);

    // RESTRICCIÓN DE VISIBILIDAD:
    // Los demás administradores en el dashboard NO pueden ver al Super Administrador.
    // El Super Administrador SÍ puede ver a todos los usuarios, cualquiera sea su rol.
    if (!isSuperAdmin()) {
        $enriched = array_values(array_filter($enriched, function($u) {
            return empty($u['is_superadmin']) && (int)($u['id_rol'] ?? 0) !== 1 && !str_contains(mb_strtolower($u['rol'] ?? ''), 'super') && mb_strtolower($u['email'] ?? '') !== 'anabelaacosta2026@gmail.com';
        }));
    }

    if ($rolFilter !== '') {
        $enriched = array_values(array_filter($enriched, function($u) use ($rolFilter) {
            return (mb_strtolower($u['rol']) === mb_strtolower($rolFilter));
        }));
    }

    if ($estadoFilter !== '') {
        $enriched = array_values(array_filter($enriched, function($u) use ($estadoFilter) {
            return (mb_strtolower($u['estado']) === mb_strtolower($estadoFilter));
        }));
    }

    if ($q !== '') {
        $enriched = array_values(array_filter($enriched, function($u) use ($q) {
            return (
                mb_stripos($u['nombre'], $q) !== false ||
                mb_stripos($u['apellido'], $q) !== false ||
                mb_stripos($u['email'], $q) !== false ||
                mb_stripos($u['telefono'], $q) !== false ||
                mb_stripos($u['dni'], $q) !== false ||
                mb_stripos($u['rol'], $q) !== false
            );
        }));
    }

    jsonResponse(true, $enriched);
}

// POST: Crear nuevo usuario
if ($method === 'POST' && ($_POST['_method'] ?? '') !== 'PUT' && ($_POST['_method'] ?? '') !== 'DELETE') {
    $input = getJsonInput();

    $nombre = trim($input['nombre'] ?? '');
    $apellido = trim($input['apellido'] ?? '');
    $email = trim($input['email'] ?? '');
    $telefono = trim($input['telefono'] ?? '');
    $dni = trim($input['DNI'] ?? $input['dni'] ?? '');
    $password = $input['contrasena'] ?? $input['password'] ?? '123456';
    $rolInput = strtolower(trim($input['rol'] ?? 'usuario'));
    $idRol = isset($input['id_rol']) ? (int)$input['id_rol'] : ($rolInput === 'admin' ? 1 : 2);

    $activo = 1;
    if (isset($input['activo'])) {
        $activo = ($input['activo'] === 1 || $input['activo'] === '1' || $input['activo'] === true || $input['activo'] === 'true') ? 1 : 0;
    } elseif (isset($input['estado'])) {
        $activo = (strtolower($input['estado']) === 'activo') ? 1 : 0;
    }

    if (empty($nombre) || empty($apellido) || empty($email)) {
        jsonResponse(false, null, 'Nombre, apellido y correo electrónico son requeridos.', 400);
    }

    // Verificar email único
    $existente = $db->find('usuario', function($u) use ($email) {
        return mb_strtolower($u['email']) === mb_strtolower($email);
    });
    if (!empty($existente)) {
        jsonResponse(false, null, 'El correo electrónico ya se encuentra registrado.', 409);
    }

    // Verificar DNI único si se proporcionó
    if (!empty($dni)) {
        $existenteDni = $db->find('usuario', function($u) use ($dni) {
            return !empty($u['DNI']) && $u['DNI'] === $dni;
        });
        if (!empty($existenteDni)) {
            jsonResponse(false, null, 'El DNI ya se encuentra registrado.', 409);
        }
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $nuevoUsuario = $db->insert('usuario', [
        'nombre' => $nombre,
        'apellido' => $apellido,
        'email' => $email,
        'contrasena' => $hash,
        'fecha_registro' => date('Y-m-d'),
        'activo' => $activo,
        'telefono' => $telefono,
        'DNI' => $dni,
        'id_rol' => $idRol
    ], 'id_usuario');

    registrarCambioDashboard('👤 Usuarios', 'Creación de Usuario', 'Creó el usuario "' . $nombre . ' ' . $apellido . '" (' . $email . ', Rol ID: ' . $idRol . ')');

    jsonResponse(true, enrichUsuario($nuevoUsuario, $db), 'Usuario registrado con éxito.', 201);
}

// PUT: Actualizar usuario
if ($method === 'PUT' || ($method === 'POST' && ($_POST['_method'] ?? '') === 'PUT')) {
    $input = getJsonInput();
    $id = (int)($input['id_usuario'] ?? $input['id'] ?? $_GET['id'] ?? 0);

    if (!$id) {
        jsonResponse(false, null, 'ID de usuario no especificado.', 400);
    }

    $existing = $db->getById('usuario', 'id_usuario', $id);
    if (!$existing) {
        jsonResponse(false, null, 'Usuario no encontrado.', 404);
    }

    // Proteger cuentas de superadministrador frente a modificaciones no autorizadas
    $targetRoleObj = $db->getById('roles', 'id_rol', (int)($existing['id_rol'] ?? 2));
    $targetRoleName = strtolower($targetRoleObj ? $targetRoleObj['nombre_rol'] : ($existing['rol'] ?? ''));
    $targetIsSuper = ($targetRoleName === 'superadmin' || $targetRoleName === 'super_admin' || !empty($existing['is_superadmin']));
    if ($targetIsSuper && !isSuperAdmin()) {
        jsonResponse(false, null, 'Permiso denegado. Solo un Super Administrador puede modificar esta cuenta de máxima prioridad.', 403);
    }

    $nombre = trim($input['nombre'] ?? $existing['nombre']);
    $apellido = trim($input['apellido'] ?? $existing['apellido']);
    $email = trim($input['email'] ?? $existing['email']);
    $telefono = trim($input['telefono'] ?? $existing['telefono']);
    $dni = trim($input['DNI'] ?? $input['dni'] ?? ($existing['DNI'] ?? ''));

    $idRol = (int)$existing['id_rol'];
    if (isset($input['id_rol'])) {
        $idRol = (int)$input['id_rol'];
    } elseif (isset($input['rol'])) {
        $idRol = (strtolower($input['rol']) === 'admin') ? 1 : 2;
    }

    $activo = (int)($existing['activo'] ?? 1);
    if (isset($input['activo'])) {
        $activo = ($input['activo'] === 1 || $input['activo'] === '1' || $input['activo'] === true || $input['activo'] === 'true') ? 1 : 0;
    } elseif (isset($input['estado'])) {
        $activo = (strtolower($input['estado']) === 'activo') ? 1 : 0;
    }

    $updates = [
        'nombre' => $nombre,
        'apellido' => $apellido,
        'email' => $email,
        'telefono' => $telefono,
        'DNI' => $dni,
        'id_rol' => $idRol,
        'activo' => $activo
    ];

    if (!empty($input['password']) || !empty($input['contrasena'])) {
        $newPass = $input['password'] ?? $input['contrasena'];
        $updates['contrasena'] = password_hash($newPass, PASSWORD_BCRYPT);
    }

    $updated = $db->update('usuario', 'id_usuario', $id, $updates);

    $oldActivo = (int)($existing['activo'] ?? 1);
    $cambios = [];
    $accionAuditoria = 'Actualización de Usuario';

    if ($activo === 1 && $oldActivo === 0) {
        $accionAuditoria = 'Reactivación de Usuario';
        $detalleAuditoria = 'Reactivó (pasó a activo) el usuario "' . $nombre . ' ' . $apellido . '" (' . $email . ', ID #' . $id . ')';
    } elseif ($activo === 0 && $oldActivo === 1) {
        $accionAuditoria = 'Desactivación de Usuario';
        $detalleAuditoria = 'Desactivó (pasó a inactivo) el usuario "' . $nombre . ' ' . $apellido . '" (' . $email . ', ID #' . $id . ')';
    } else {
        if ($idRol !== (int)($existing['id_rol'] ?? 2)) {
            $rolViejo = ((int)$existing['id_rol'] === 1) ? 'Super Admin' : (((int)$existing['id_rol'] === 2) ? 'Administrador' : 'Cliente');
            $rolNuevo = ($idRol === 1) ? 'Super Admin' : (($idRol === 2) ? 'Administrador' : 'Cliente');
            $cambios[] = 'rol de ' . $rolViejo . ' a ' . $rolNuevo;
            $accionAuditoria = 'Cambio de Rol';
        }
        if (!empty($input['password']) || !empty($input['contrasena'])) {
            $cambios[] = 'reestableció contraseña';
            $accionAuditoria = 'Cambio de Contraseña';
        }
        if ($email !== ($existing['email'] ?? '')) {
            $cambios[] = 'email a ' . $email;
        }
        if ($telefono !== ($existing['telefono'] ?? '')) {
            $cambios[] = 'teléfono a ' . $telefono;
        }
        if ($dni !== ($existing['DNI'] ?? '')) {
            $cambios[] = 'DNI a ' . $dni;
        }

        if (!empty($cambios)) {
            $detalleAuditoria = 'Modificó en el usuario "' . $nombre . ' ' . $apellido . '" (ID #' . $id . '): ' . implode(', ', $cambios);
        } else {
            $detalleAuditoria = 'Actualizó datos del usuario "' . $nombre . ' ' . $apellido . '" (' . $email . ', ID #' . $id . ')';
        }
    }

    registrarCambioDashboard('👤 Usuarios', $accionAuditoria, $detalleAuditoria, $id);

    jsonResponse(true, enrichUsuario($updated, $db), 'Usuario actualizado correctamente.');
}

// DELETE: Desactivar usuario (Soft Delete)
if ($method === 'DELETE' || ($method === 'POST' && ($_POST['_method'] ?? '') === 'DELETE')) {
    $input = getJsonInput();
    $id = (int)($input['id_usuario'] ?? $input['id'] ?? $_GET['id'] ?? 0);

    if (!$id) {
        jsonResponse(false, null, 'ID de usuario no especificado.', 400);
    }

    $existing = $db->getById('usuario', 'id_usuario', $id);
    if (!$existing) {
        jsonResponse(false, null, 'Usuario no encontrado.', 404);
    }

    $nombreUsuario = $existing['nombre'] . ' ' . $existing['apellido'];
    $targetRoleObj = $db->getById('roles', 'id_rol', (int)($existing['id_rol'] ?? 2));
    $targetRoleName = strtolower($targetRoleObj ? $targetRoleObj['nombre_rol'] : ($existing['rol'] ?? ''));
    $targetIsSuper = ((int)($existing['id_rol'] ?? 0) === 1 || (int)($existing['id_rol'] ?? 0) === 4 || str_contains($targetRoleName, 'super') || !empty($existing['is_superadmin']) || strtolower($existing['email'] ?? '') === 'anabelaacosta2026@gmail.com');
    if ($targetIsSuper) {
        jsonResponse(false, null, 'Las cuentas de Super Administrador están protegidas y no pueden ser desactivadas.', 403);
    }

    // Soft delete: cambiar activo a 0 preservando citas, historial y relaciones
    $db->update('usuario', 'id_usuario', $id, [
        'activo' => 0
    ]);

    registrarCambioDashboard('👤 Usuarios', 'Desactivación de Usuario', 'Desactivó (pasó a inactivo) el usuario "' . $nombreUsuario . '" (ID #' . $id . ')', $id);

    jsonResponse(true, ['id' => $id, 'id_usuario' => $id, 'activo' => 0], 'Usuario desactivado correctamente (estado inactivo).');
}

jsonResponse(false, null, 'Método HTTP no permitido.', 405);

