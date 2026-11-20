<?php
/**
 * ARCANA — Notification Layer (api/notificaciones.php)
 * Decoupled notification system supporting Email (Gmail SMTP), with future readiness for SMS/Push.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/JsonDB.php';
require_once dirname(__DIR__) . '/config/mail.php';

/**
 * Disparar notificación de cancelación de cita desacoplada
 */
function notificarCancelacion(array $cita, string $motivo = ''): array {
    $db = JsonDB::getInstance();
    $idUsuario = (int)($cita['id_usuario'] ?? 0);
    $user = $db->getById('usuario', 'id_usuario', $idUsuario);

    if (!$user) {
        return ['success' => false, 'message' => 'Usuario no encontrado para notificar.'];
    }

    // 1. Enviar notificación por correo electrónico
    $emailResult = sendCancellationEmail($user, $cita, $motivo);

    // 2. Extensibilidad futura: registrar en log de notificaciones o enviar SMS / push
    return [
        'success' => true,
        'email_status' => $emailResult,
        'user_notified' => $user['email']
    ];
}

/**
 * Disparar confirmación de turno desacoplada
 */
function notificarConfirmacionTurno(array $cita): array {
    $db = JsonDB::getInstance();
    $idUsuario = (int)($cita['id_usuario'] ?? 0);
    $user = $db->getById('usuario', 'id_usuario', $idUsuario);

    if (!$user) {
        return ['success' => false, 'message' => 'Usuario no encontrado para notificar.'];
    }

    $emailResult = sendBookingConfirmationEmail($user, $cita);

    return [
        'success' => true,
        'email_status' => $emailResult,
        'user_notified' => $user['email']
    ];
}
