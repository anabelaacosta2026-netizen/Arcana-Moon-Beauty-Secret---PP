<?php
/**
 * ARCANA — Contact Form API Endpoint
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/response.php';

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Método no permitido.', 405);
}

$input = getJsonInput();
$nombre = trim($input['nombre'] ?? '');
$apellido = trim($input['apellido'] ?? '');
$email = trim($input['email'] ?? '');
$telefono = trim($input['telefono'] ?? '');
$direccion = trim($input['direccion'] ?? '');
$mensaje = trim($input['mensaje'] ?? '');

if (empty($nombre) || empty($apellido) || empty($email) || empty($mensaje)) {
    jsonResponse(false, null, 'Por favor complete todos los campos obligatorios del mensaje.', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, null, 'El formato de correo electrónico no es válido.', 400);
}

$stmt = $pdo->prepare("INSERT INTO mensajes_contacto (nombre, apellido, email, telefono, direccion, mensaje, fecha_envio, leido) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, 0)");
$stmt->execute([$nombre, $apellido, $email, $telefono, $direccion, $mensaje]);

jsonResponse(true, [
    'id' => (int)$pdo->lastInsertId()
], '¡Mensaje enviado con éxito! Te responderemos a la brevedad.');
