<?php
/**
 * ARCANA — Servicios CRUD API Endpoint (Schema: arcanaestetica)
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/JsonDB.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once __DIR__ . '/response.php';

$db = JsonDB::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Función auxiliar para enriquecer un servicio con su precio actual, categoría y empleados asignados
 */
function enrichServicio(array $s, JsonDB $db): array {
    $id = (int)$s['id_servicio'];
 $modalidades = [];
 
    // Buscar precio actual en precio_servicio
    $precios = $db->find('precio_servicio', function($p) use ($id) {
        return (int)$p['id_servicio'] === $id;
    });
    $precio = 0.0;
    if (!empty($precios)) {
        // Tomar el último precio vigente
        $ultimoPrecio = end($precios);
        $precio = (float)($ultimoPrecio['precio'] ?? 0);
    }

    // Buscar asignaciones de empleado
    $servemps = $db->find('servicio_empleado', function($se) use ($id) {
        return (int)$se['id_servicio'] === $id;
    });

  
    $empleadosIds = array_map(function($se) { return (int)$se['id_empleado']; }, $servemps);

    
    // Obtener nombres de especialistas
    $especialistas = [];
    foreach ($empleadosIds as $empId) {
        $emp = $db->getById('empleado', 'id_empleado', $empId);
        if ($emp) {
            $especialistas[] = $emp['nombre'] . ' ' . $emp['apellido'];
        } 
    }

     $modalidades = [];
      foreach ($servemps as $se){
    $m = trim($se['modalidad_disponib'] ?? '');
    if ($m !== '' && !in_array($m, $modalidades)){
        $modalidades[] = $m;
    }}

    // Compatibilidad dual con nombres anteriores y nuevos del schema
    return [
        'id' => $id,
        'id_servicio' => $id,
        'nombre' => $s['nombre_servicio'],
        'nombre_servicio' => $s['nombre_servicio'],
        'descripcion' => $s['descripcion'] ?? '',
        'categoria' => $s['categoria'] ?? 'General',
        'duracion' => (int)($s['duracion_minutos'] ?? 60),
        'duracion_minutos' => (int)($s['duracion_minutos'] ?? 60),
        'precio' => $precio,
        'disponible' => (bool)($s['disponibilidad'] ?? 1),
        'disponibilidad' => (int)($s['disponibilidad'] ?? 1),
        'imagen' => $s['imagen_servicio'] ?? 'assets/images/service-manicure.jpg',
        'imagen_servicio' => $s['imagen_servicio'] ?? 'assets/images/service-manicure.jpg',
        'cuidados_previos' => $s['cuidados_previos'] ?? '',
        'cuidados_posteriores' => $s['cuidados_posteriores'] ?? '',
        'modalidad' => !empty($modalidades) ? implode(', ', $modalidades) : 'Todo el equipo',
        'especialista' => !empty($especialistas) ? implode(', ', $especialistas) : 'Todo el equipo',
        'empleados_ids' => $empleadosIds
    ];
}

// GET: Listar o individual
if ($method === 'GET') {
    $id = $_GET['id'] ?? $_GET['id_servicio'] ?? null;

    if ($id) {
        $servicio = $db->getById('servicio', 'id_servicio', $id);
        if (!$servicio) {
            jsonResponse(false, null, 'Servicio no encontrado.', 404);
        }
        jsonResponse(true, enrichServicio($servicio, $db));
    }

    $q = mb_strtolower(trim($_GET['q'] ?? ''));
    $categoria = trim($_GET['categoria'] ?? '');
    $soloDisponibles = isset($_GET['disponible']) ? (int)$_GET['disponible'] : null;

    $servicios = $db->getTable('servicio');
    $enriched = array_map(function($s) use ($db) {
        return enrichServicio($s, $db);
    }, $servicios);

    if ($q !== '') {
        $enriched = array_values(array_filter($enriched, function($s) use ($q) {
            return (
                mb_stripos($s['nombre_servicio'], $q) !== false ||
                mb_stripos($s['descripcion'], $q) !== false ||
                mb_stripos($s['categoria'], $q) !== false ||
                mb_stripos($s['especialista'], $q) !== false
            );
        }));
    }

    if ($categoria !== '') {
        $enriched = array_values(array_filter($enriched, function($s) use ($categoria) {
            return (mb_strtolower($s['categoria']) === mb_strtolower($categoria));
        }));
    }

    if ($soloDisponibles !== null) {
        $enriched = array_values(array_filter($enriched, function($s) use ($soloDisponibles) {
            return ((int)$s['disponibilidad'] === $soloDisponibles);
        }));
    }

    jsonResponse(true, $enriched);
}

// POST: Crear nuevo servicio
if ($method === 'POST' && ($_POST['_method'] ?? '') !== 'PUT' && ($_POST['_method'] ?? '') !== 'DELETE') {
    $input = getJsonInput();
    $nombre = trim($input['nombre_servicio'] ?? $input['nombre'] ?? '');
    $categoria = trim($input['categoria'] ?? 'Manicura');
    $descripcion = trim($input['descripcion'] ?? '');
    $duracion = (int)($input['duracion_minutos'] ?? $input['duracion'] ?? 60);
    $precio = (float)($input['precio'] ?? 0);
    $imagen = trim($input['imagen_servicio'] ?? $input['imagen'] ?? 'assets/images/service-manicure.jpg');
    $cuidadosPrevios = trim($input['cuidados_previos'] ?? '');
    $cuidadosPosteriores = trim($input['cuidados_posteriores'] ?? '');
    
    $disponibilidad = 1;
    if (isset($input['disponibilidad'])) {
        $disponibilidad = ($input['disponibilidad'] === true || $input['disponibilidad'] === 1 || $input['disponibilidad'] === '1' || $input['disponibilidad'] === 'true') ? 1 : 0;
    } elseif (isset($input['disponible'])) {
        $disponibilidad = ($input['disponible'] === true || $input['disponible'] === 1 || $input['disponible'] === '1' || $input['disponible'] === 'true') ? 1 : 0;
    }

    if (empty($nombre) || empty($categoria)) {
        jsonResponse(false, null, 'El nombre del servicio y la categoría son obligatorios.', 400);
    }

    // 1. Insertar servicio
    $nuevoServicio = $db->insert('servicio', [
        'nombre_servicio' => $nombre,
        'descripcion' => $descripcion,
        'categoria' => $categoria,
        'disponibilidad' => $disponibilidad,
        'imagen_servicio' => $imagen,
        'duracion_minutos' => $duracion,
        'cuidados_previos' => $cuidadosPrevios,
        'cuidados_posteriores' => $cuidadosPosteriores
    ], 'id_servicio');

    $idServicio = (int)$nuevoServicio['id_servicio'];

    // 2. Insertar precio en precio_servicio
    $db->insert('precio_servicio', [
        'id_servicio' => $idServicio,
        'precio' => $precio,
        'fecha_desde' => date('Y-m-d'),
        'fecha_hasta' => null
    ], 'id_precio_servicio');

    // 3. Vincular categoría en categoria_servicio si existe
    $catExistente = $db->find('categoria', function($c) use ($categoria) {
        return mb_strtolower($c['nombre_categoria']) === mb_strtolower($categoria);
    });
    if (!empty($catExistente)) {
        $idCat = (int)$catExistente[0]['id_categoria'];
    } else {
        $nuevaCat = $db->insert('categoria', ['nombre_categoria' => $categoria], 'id_categoria');
        $idCat = (int)$nuevaCat['id_categoria'];
    }
    $db->insert('categoria_servicio', [
        'id_categoria' => $idCat,
        'id_servicio' => $idServicio
    ], 'id_categoria_servicio');

    // 4. Asignar empleado por defecto o seleccionado
    $empleados = $db->getTable('empleado');
    if (!empty($empleados)) {
        $empId = (int)($input['id_empleado'] ?? $empleados[0]['id_empleado']);
        $db->insert('servicio_empleado', [
            'id_servicio' => $idServicio,
            'id_empleado' => $empId,
            'modalidad_disponib' => 'Presencial',
            'max_por_dia' => 6
        ], 'id_servicio_empleado');
    }

    registrarCambioDashboard('💅 Servicios', 'Creación de Servicio', 'Creó el servicio "' . $nombre . '" con precio $' . number_format($precio, 0, ',', '.'));

    jsonResponse(true, enrichServicio($nuevoServicio, $db), 'Servicio creado exitosamente.', 201);
}

// PUT: Actualizar servicio existente
if ($method === 'PUT' || ($method === 'POST' && ($_POST['_method'] ?? '') === 'PUT')) {
    $input = getJsonInput();
    $id = (int)($input['id_servicio'] ?? $input['id'] ?? $_GET['id'] ?? 0);

    if (!$id) {
        jsonResponse(false, null, 'ID de servicio no especificado.', 400);
    }

    $existing = $db->getById('servicio', 'id_servicio', $id);
    if (!$existing) {
        jsonResponse(false, null, 'Servicio no encontrado.', 404);
    }

    $nombre = trim($input['nombre_servicio'] ?? $input['nombre'] ?? $existing['nombre_servicio']);
    $categoria = trim($input['categoria'] ?? $existing['categoria']);
    $descripcion = trim($input['descripcion'] ?? $existing['descripcion']);
    $cuidadosPrevios = isset($input['cuidados_previos']) ? trim($input['cuidados_previos']) : ($existing['cuidados_previos'] ?? '');
    $cuidadosPosteriores = isset($input['cuidados_posteriores']) ? trim($input['cuidados_posteriores']) : ($existing['cuidados_posteriores'] ?? '');
    $duracion = isset($input['duracion_minutos']) ? (int)$input['duracion_minutos'] : (isset($input['duracion']) ? (int)$input['duracion'] : (int)$existing['duracion_minutos']);
    $imagen = trim($input['imagen_servicio'] ?? $input['imagen'] ?? $existing['imagen_servicio']);

    $disponibilidad = (int)$existing['disponibilidad'];
    if (isset($input['disponibilidad'])) {
        $disponibilidad = ($input['disponibilidad'] === true || $input['disponibilidad'] === 1 || $input['disponibilidad'] === '1' || $input['disponibilidad'] === 'true') ? 1 : 0;
    } elseif (isset($input['disponible'])) {
        $disponibilidad = ($input['disponible'] === true || $input['disponible'] === 1 || $input['disponible'] === '1' || $input['disponible'] === 'true') ? 1 : 0;
    }

    // Actualizar servicio
    $updated = $db->update('servicio', 'id_servicio', $id, [
        'nombre_servicio' => $nombre,
        'descripcion' => $descripcion,
        'categoria' => $categoria,
        'disponibilidad' => $disponibilidad,
        'imagen_servicio' => $imagen,
        'duracion_minutos' => $duracion,
        'cuidados_previos' => $cuidadosPrevios,
        'cuidados_posteriores' => $cuidadosPosteriores
    ]);

    // Obtener precio anterior para comparación
    $oldPrecios = $db->find('precio_servicio', function($p) use ($id) {
        return (int)$p['id_servicio'] === $id;
    });
    $oldPrecio = !empty($oldPrecios) ? (float)end($oldPrecios)['precio'] : 0.0;
    $newPrecio = isset($input['precio']) ? (float)$input['precio'] : $oldPrecio;

    // Actualizar precio si viene en el input
    if (isset($input['precio'])) {
        if (!empty($oldPrecios)) {
            $ultimo = end($oldPrecios);
            $db->update('precio_servicio', 'id_precio_servicio', $ultimo['id_precio_servicio'], [
                'precio' => $newPrecio
            ]);
        } else {
            $db->insert('precio_servicio', [
                'id_servicio' => $id,
                'precio' => $newPrecio,
                'fecha_desde' => date('Y-m-d'),
                'fecha_hasta' => null
            ], 'id_precio_servicio');
        }
    }

    // Detectar cambios específicos para auditoría detallada
    $cambiosDetalles = [];
    $accionAuditoria = 'Actualización de Servicio';

    if (isset($input['precio']) && (float)$newPrecio !== (float)$oldPrecio) {
        $cambiosDetalles[] = 'precio de $' . number_format($oldPrecio, 0, ',', '.') . ' a $' . number_format($newPrecio, 0, ',', '.');
        $accionAuditoria = 'Actualización de Precio';
    }
    if ($nombre !== $existing['nombre_servicio']) {
        $cambiosDetalles[] = 'nombre de "' . $existing['nombre_servicio'] . '" a "' . $nombre . '"';
    }
    if ($duracion !== (int)$existing['duracion_minutos']) {
        $cambiosDetalles[] = 'duración de ' . $existing['duracion_minutos'] . ' min a ' . $duracion . ' min';
    }
    if ($categoria !== ($existing['categoria'] ?? '')) {
        $cambiosDetalles[] = 'categoría de "' . ($existing['categoria'] ?? '-') . '" a "' . $categoria . '"';
    }
    if ($disponibilidad !== (int)$existing['disponibilidad']) {
        if ($disponibilidad === 1) {
            $cambiosDetalles[] = 'estado a Activo';
            $accionAuditoria = 'Reactivación de Servicio';
        } else {
            $cambiosDetalles[] = 'estado a Inactivo';
            $accionAuditoria = 'Desactivación de Servicio';
        }
    }

    if (!empty($cambiosDetalles)) {
        $textoDetalle = 'Modificó en el servicio "' . $nombre . '" (ID #' . $id . '): ' . implode(', ', $cambiosDetalles);
    } else {
        $textoDetalle = 'Actualizó datos generales del servicio "' . $nombre . '" (ID #' . $id . ', Precio: $' . number_format($newPrecio, 0, ',', '.') . ')';
    }

    registrarCambioDashboard('💅 Servicios', $accionAuditoria, $textoDetalle, $id);

    jsonResponse(true, enrichServicio($updated, $db), 'Servicio actualizado correctamente.');
}

// DELETE: Desactivar servicio (Soft Delete)
if ($method === 'DELETE' || ($method === 'POST' && ($_POST['_method'] ?? '') === 'DELETE')) {
    $input = getJsonInput();
    $id = (int)($input['id_servicio'] ?? $input['id'] ?? $_GET['id'] ?? 0);

    if (!$id) {
        jsonResponse(false, null, 'ID de servicio no especificado.', 400);
    }

    $existing = $db->getById('servicio', 'id_servicio', $id);
    if (!$existing) {
        jsonResponse(false, null, 'Servicio no encontrado.', 404);
    }
    $nombreServicio = $existing['nombre_servicio'] ?? "#$id";

    // Soft delete: cambiar disponibilidad / estado a 0
    $db->update('servicio', 'id_servicio', $id, [
        'disponibilidad' => 0
    ]);

    registrarCambioDashboard('💅 Servicios', 'Desactivación de Servicio', 'Desactivó (pasó a inactivo) el servicio "' . $nombreServicio . '" (ID #' . $id . ')');

    jsonResponse(true, ['id' => $id, 'id_servicio' => $id, 'disponibilidad' => 0], 'Servicio desactivado correctamente (estado inactivo).');
}

jsonResponse(false, null, 'Método HTTP no permitido.', 405);

