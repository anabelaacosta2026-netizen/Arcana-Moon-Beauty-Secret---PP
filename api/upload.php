<?php
/**
 * ARCANA — Image Upload API Endpoint
 * Handles file drag & drop or PC file uploads for services and employees.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once __DIR__ . '/response.php';

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(false, null, 'Sesión no iniciada o expirada.', 401);
}

$uploadDir = APP_ROOT . '/assets/images/uploads';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        jsonResponse(false, null, 'No se pudo crear el directorio de subida.', 500);
    }
}

$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
$maxSize = 6 * 1024 * 1024; // 6 MB

// Case 1: Standard multipart/form-data upload ($_FILES)
if (!empty($_FILES['image']) || !empty($_FILES['file'])) {
    $file = !empty($_FILES['image']) ? $_FILES['image'] : $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = match ($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido por el servidor.',
            UPLOAD_ERR_PARTIAL => 'La subida del archivo fue interrumpida.',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo.',
            default => 'Error al subir el archivo (Código: ' . $file['error'] . ').'
        };
        jsonResponse(false, null, $errorMsg, 400);
    }

    if ($file['size'] > $maxSize) {
        jsonResponse(false, null, 'La imagen supera el límite de 6 MB.', 400);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedMimes)) {
        jsonResponse(false, null, 'Tipo de archivo no permitido. Solo se aceptan JPG, PNG, WEBP, GIF o SVG.', 400);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions)) {
        $ext = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            default => 'jpg'
        };
    }

    $filename = 'img_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        jsonResponse(false, null, 'Error al guardar la imagen en el servidor.', 500);
    }

    $relativePath = 'assets/images/uploads/' . $filename;

    jsonResponse(true, [
        'url' => $relativePath,
        'filename' => $filename,
        'size' => $file['size']
    ], 'Imagen subida correctamente.');
}

// Case 2: Base64 JSON payload
$input = getJsonInput();
if (!empty($input['image_base64'])) {
    $data = $input['image_base64'];

    if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
        $data = substr($data, strpos($data, ',') + 1);
        $ext = strtolower($type[1]);

        if (!in_array($ext, $allowedExtensions)) {
            $ext = 'jpg';
        }

        $decoded = base64_decode($data);
        if ($decoded === false) {
            jsonResponse(false, null, 'Error al decodificar la imagen Base64.', 400);
        }

        if (strlen($decoded) > $maxSize) {
            jsonResponse(false, null, 'La imagen supera el límite de 6 MB.', 400);
        }

        $filename = 'img_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . '/' . $filename;

        if (file_put_contents($targetPath, $decoded) === false) {
            jsonResponse(false, null, 'Error al escribir el archivo de imagen en el servidor.', 500);
        }

        $relativePath = 'assets/images/uploads/' . $filename;

        jsonResponse(true, [
            'url' => $relativePath,
            'filename' => $filename,
            'size' => strlen($decoded)
        ], 'Imagen guardada correctamente.');
    } else {
        jsonResponse(false, null, 'Formato Base64 no válido.', 400);
    }
}

jsonResponse(false, null, 'No se recibió ninguna imagen para subir.', 400);
