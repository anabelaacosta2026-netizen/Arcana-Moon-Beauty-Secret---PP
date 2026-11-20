<?php
/**
 * ARCANA — Mercado Pago Direct API Client (Pure PHP)
 * Integración oficial con la API REST de Mercado Pago (Preferences y Payments v1)
 */

require_once __DIR__ . '/config.php';

/**
 * Realiza una petición HTTP a la API de Mercado Pago vía cURL
 */
function mp_api_request(string $method, string $endpoint, ?array $payload = null, ?string $accessToken = null): array {
    $token = $accessToken ?: env('MP_ACCESS_TOKEN');
    if (empty($token)) {
        return [
            'success' => false,
            'status' => 400,
            'error' => 'No se configuró el Access Token de Mercado Pago para esta cuenta.'
        ];
    }

    $url = 'https://api.mercadopago.com' . $endpoint;
    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json',
        'X-Idempotency-Key: ' . uniqid('arcana_', true)
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return [
            'success' => false,
            'status' => $httpCode ?: 500,
            'error' => 'Error de conexión cURL con Mercado Pago: ' . $curlError
        ];
    }

    $decoded = json_decode($response, true);
    $isSuccess = ($httpCode >= 200 && $httpCode < 300);

    return [
        'success' => $isSuccess,
        'status' => $httpCode,
        'data' => $decoded,
        'error' => !$isSuccess ? ($decoded['message'] ?? ($decoded['cause'][0]['description'] ?? 'Error en la API de Mercado Pago')) : null
    ];
}

/**
 * Crea una preferencia de pago oficial en Mercado Pago (Checkout Pro / Wallet)
 */
function mp_create_preference(array $preferenceData, string $accessToken): array {
    $endpoint = '/checkout/preferences';
    $res = mp_api_request('POST', $endpoint, $preferenceData, $accessToken);
    return $res;
}

/**
 * Procesa un pago directo con tarjeta o token en Mercado Pago (Payments API v1)
 */
function mp_process_payment(array $paymentData, string $accessToken): array {
    $endpoint = '/v1/payments';
    $res = mp_api_request('POST', $endpoint, $paymentData, $accessToken);
    return $res;
}

/**
 * Consulta el estado de un pago existente en Mercado Pago
 */
function mp_get_payment(string $paymentId, string $accessToken): array {
    $endpoint = '/v1/payments/' . urlencode($paymentId);
    $res = mp_api_request('GET', $endpoint, null, $accessToken);
    return $res;
}
