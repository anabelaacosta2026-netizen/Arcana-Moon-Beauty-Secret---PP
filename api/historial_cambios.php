<?php
/**
 * ARCANA — API Historial de Cambios del Dashboard
 * Endpoint exclusivo para administradores
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/JsonDB.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once __DIR__ . '/response.php';

// Verificar que sea Super Administrador exclusivamente
if (!isSuperAdmin()) {
    jsonResponse(false, null, 'Acceso denegado. Se requieren permisos de Super Administrador.', 403);
}

$db = JsonDB::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $historial = $db->getTable('historial_cambios');

    // Ordenar de más reciente a más antiguo
    usort($historial, function($a, $b) {
        $tA = $a['timestamp'] ?? 0;
        $tB = $b['timestamp'] ?? 0;
        if ($tA === $tB) {
            return ($b['id_cambio'] ?? 0) <=> ($a['id_cambio'] ?? 0);
        }
        return $tB <=> $tA;
    });

    // Filtros opcionales
    $modulo = trim($_GET['modulo'] ?? '');
    $q = mb_strtolower(trim($_GET['q'] ?? ''));

    if ($modulo !== '') {
        $historial = array_values(array_filter($historial, function($item) use ($modulo) {
            return mb_stripos($item['modulo'] ?? '', $modulo) !== false;
        }));
    }

    if ($q !== '') {
        $historial = array_values(array_filter($historial, function($item) use ($q) {
            return (
                mb_stripos($item['usuario'] ?? '', $q) !== false ||
                mb_stripos($item['email'] ?? '', $q) !== false ||
                mb_stripos($item['modulo'] ?? '', $q) !== false ||
                mb_stripos($item['accion'] ?? '', $q) !== false ||
                mb_stripos($item['detalle'] ?? '', $q) !== false ||
                mb_stripos($item['fecha'] ?? '', $q) !== false
            );
        }));
    }

    // Limitar a los 200 registros más recientes
    $historial = array_slice($historial, 0, 200);

    jsonResponse(true, $historial, 'Historial de cambios obtenido correctamente.');
}

if ($method === 'DELETE') {
    // Solo superadmin puede vaciar el historial si es necesario
    if (!isSuperAdmin()) {
        jsonResponse(false, null, 'Solo el Super Administrador puede purgar el historial.', 403);
    }
    
    // Limpiar tabla historial_cambios
    $db->clearTable('historial_cambios');
    registrarCambioDashboard('🛡️ Seguridad', 'El Super Administrador purgó el historial de cambios.');
    jsonResponse(true, null, 'Historial de cambios vaciado correctamente.');
}

jsonResponse(false, null, 'Método no permitido.', 405);
