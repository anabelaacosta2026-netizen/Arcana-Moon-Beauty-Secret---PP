<?php
/**
 * ARCANA — Mercado Pago Webhook / IPN Listener (api/mercadopago_webhook.php)
 * Recibe notificaciones asíncronas de pagos de Mercado Pago y actualiza las citas en tiempo real.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/JsonDB.php';
require_once dirname(__DIR__) . '/config/mercadopago.php';

$db = JsonDB::getInstance();

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true) ?: [];

$topic = $_GET['topic'] ?? $_GET['type'] ?? ($data['type'] ?? '');
$paymentId = $_GET['id'] ?? $_GET['data_id'] ?? ($data['data']['id'] ?? null);

if (($topic === 'payment' || $topic === 'payment.created' || $topic === 'payment.updated') && $paymentId) {
    // Buscar el access token de la cuenta activa
    $cuentas = $db->getTable('cuentas_cobro');
    $cuentaActiva = null;
    foreach ($cuentas as $c) {
        if ((int)($c['es_activa'] ?? 0) === 1) {
            $cuentaActiva = $c;
            break;
        }
    }
    $accessToken = $cuentaActiva['access_token'] ?? env('MP_ACCESS_TOKEN');

    if ($accessToken) {
        $paymentInfo = mp_get_payment((string)$paymentId, $accessToken);
        if ($paymentInfo['success'] && !empty($paymentInfo['data'])) {
            $pData = $paymentInfo['data'];
            $status = $pData['status'] ?? '';
            $externalRef = (int)($pData['external_reference'] ?? 0);

            if ($status === 'approved' && $externalRef > 0) {
                $cita = $db->getById('citas', 'id_cita', $externalRef);
                if ($cita) {
                    $comprobante = 'MP-' . $paymentId;
                    $monto = (float)($pData['transaction_amount'] ?? 0);
                    $obs = trim($cita['observaciones'] ?? '');
                    $nota = "Seña acreditada vía Webhook Mercado Pago: $" . number_format($monto, 0, ',', '.') . " [ID: {$paymentId}]";
                    $nuevaObs = ($obs !== '' && $obs !== '-') ? $obs . ' | ' . $nota : $nota;

                    $db->update('citas', 'id_cita', $externalRef, [
                        'estado' => 'confirmado',
                        'observaciones' => $nuevaObs,
                        'modificada_en' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
    }
}

// Responder 200 OK a Mercado Pago
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['status' => 'received']);
