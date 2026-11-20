<?php
/**
 * ARCANA — Database Backup / Sync API Endpoint (api/backup.php)
 * Permite Exportar (download), Importar (upload) y Restablecer la base de datos JSON.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/JsonDB.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once __DIR__ . '/response.php';

$db = JsonDB::getInstance();
$action = $_GET['action'] ?? $_POST['action'] ?? 'export';
$method = $_SERVER['REQUEST_METHOD'];

// Exportación (Descarga de archivo JSON o JSON payload)
if ($action === 'export') {
    if (isset($_GET['download']) && $_GET['download'] === '1') {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="arcanaestetica_backup_' . date('Ymd_His') . '.json"');
        echo $db->exportJson();
        exit;
    }
    jsonResponse(true, $db->getAllData(), 'Copia de seguridad exportada correctamente.');
}

// Importación (Upload de archivo JSON o recepción de JSON vía body)
if ($action === 'import' && $method === 'POST') {
    $content = null;

    // Caso 1: Archivo subido vía multipart/form-data
    if (!empty($_FILES['file']['tmp_name'])) {
        $content = file_get_contents($_FILES['file']['tmp_name']);
    } elseif (!empty($_FILES['backup_file']['tmp_name'])) {
        $content = file_get_contents($_FILES['backup_file']['tmp_name']);
    } else {
        // Caso 2: Payload JSON directo
        $input = getJsonInput();
        if (!empty($input)) {
            $content = $input;
        }
    }

    if (!$content) {
        jsonResponse(false, null, 'No se proporcionó ningún archivo o datos JSON válidos para importar.', 400);
    }

    $success = $db->importJson($content);
    if ($success) {
        jsonResponse(true, $db->getAllData(), 'Base de datos importada y sincronizada exitosamente.');
    } else {
        jsonResponse(false, null, 'Error al procesar o guardar el archivo JSON. Verifique la estructura del archivo.', 400);
    }
}

// Restablecimiento a valores por defecto
if ($action === 'reset' && $method === 'POST') {
    $db->resetToDefault();
    jsonResponse(true, $db->getAllData(), 'Base de datos restablecida a los valores iniciales de prueba.');
}

jsonResponse(false, null, 'Acción no válida o método HTTP no permitido.', 405);
