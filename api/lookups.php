<?php
/**
 * ARCANA — Master Lookups API Endpoint (api/lookups.php)
 * Proporciona listas de maestros: roles, categorías, especialidades, espacios, empleados y servicios.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/JsonDB.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once __DIR__ . '/response.php';

$db = JsonDB::getInstance();

$rolesList = $db->getTable('roles');
if (!isSuperAdmin()) {
    $rolesList = array_values(array_filter($rolesList, function($r) {
        return (int)($r['id_rol'] ?? 0) !== 1 && (int)($r['id_rol'] ?? 0) !== 4 && !str_contains(strtolower($r['nombre_rol'] ?? ''), 'super');
    }));
}

$usuariosList = $db->getTable('usuario');
if (!isSuperAdmin()) {
    $usuariosList = array_values(array_filter($usuariosList, function($u) {
        $isSuper = (!empty($u['is_superadmin']) || (int)($u['id_rol'] ?? 0) === 1 || (int)($u['id_rol'] ?? 0) === 4 || str_contains(strtolower($u['rol'] ?? ''), 'super') || strtolower($u['email'] ?? '') === 'anabelaacosta2026@gmail.com');
        return !$isSuper;
    }));
}

$data = [
    'roles' => $rolesList,
    'categorias' => $db->getTable('categoria'),
    'especialidades' => $db->getTable('especialidad'),
    'espacios' => $db->getTable('espacio'),
    'empleados' => array_map(function($e) {
        return [
            'id_empleado' => (int)$e['id_empleado'],
            'nombre_completo' => $e['nombre'] . ' ' . $e['apellido'],
            'email' => $e['email'],
            'telefono' => $e['telefono'] ?? '',
            'activo' => isset($e['activo']) ? (int)$e['activo'] : 1
        ];
    }, $db->getTable('empleado')),
    'servicios' => array_map(function($s) {
        return [
            'id_servicio' => (int)$s['id_servicio'],
            'nombre_servicio' => $s['nombre_servicio'],
            'categoria' => $s['categoria'],
            'duracion_minutos' => (int)$s['duracion_minutos'],
            'disponibilidad' => isset($s['disponibilidad']) ? (int)$s['disponibilidad'] : 1
        ];
    }, $db->getTable('servicio')),
    'usuarios' => array_map(function($u) {
        return [
            'id_usuario' => (int)$u['id_usuario'],
            'nombre_completo' => $u['nombre'] . ' ' . ($u['apellido'] ?? ''),
            'email' => $u['email'],
            'DNI' => $u['DNI'] ?? ($u['dni'] ?? ''),
            'id_rol' => (int)($u['id_rol'] ?? 2),
            'activo' => isset($u['activo']) ? (int)$u['activo'] : 1
        ];
    }, $usuariosList)
];

jsonResponse(true, $data);

