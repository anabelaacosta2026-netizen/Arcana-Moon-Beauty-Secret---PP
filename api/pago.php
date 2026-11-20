<?php
/**
 * ARCANA — Payment Processing API Endpoint (api/pago.php)
 * Procesa el pago de seña según la cuenta receptora activa configurada por los administradores.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/JsonDB.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once __DIR__ . '/response.php';

$db = JsonDB::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = getJsonInput();
    $idCita = (int)($input['id_cita'] ?? $input['id'] ?? 0);
    $metodoPago = trim($input['metodo_pago'] ?? 'mercadopago');
    $montoAbonado = (float)($input['monto_sena'] ?? $input['monto'] ?? 0);
    $idCuenta = (int)($input['id_cuenta'] ?? 0);
    $comprobanteCliente = trim($input['comprobante_cliente'] ?? '');

    if (!$idCita) {
        jsonResponse(false, null, 'ID de cita no proporcionado.', 400);
    }

    $cita = $db->getById('citas', 'id_cita', $idCita);
    if (!$cita) {
        jsonResponse(false, null, 'Cita no encontrada en el sistema.', 404);
    }

    // Buscar información de la cuenta receptora de destino
    $cuentaDestino = null;
    if ($idCuenta) {
        $cuentaDestino = $db->getById('cuentas_cobro', 'id_cuenta', $idCuenta);
    }
    if (!$cuentaDestino) {
        $cuentas = $db->getTable('cuentas_cobro');
        foreach ($cuentas as $c) {
            if ((int)($c['es_activa'] ?? 0) === 1) {
                $cuentaDestino = $c;
                break;
            }
        }
        if (!$cuentaDestino && !empty($cuentas)) {
            $cuentaDestino = $cuentas[0];
        }
    }

    $titularDestino = $cuentaDestino['titular'] ?? 'Arcana Moon';
    $bancoDestino = $cuentaDestino['banco_billetera'] ?? 'Mercado Pago';

    // Generar código de comprobante según proveedor
    $prefijo = 'PAY';
    if ($metodoPago === 'mercadopago') $prefijo = 'MP';
    elseif ($metodoPago === 'ualabis') $prefijo = 'UB';
    elseif ($metodoPago === 'transferencia') $prefijo = 'TR';
    elseif ($metodoPago === 'efectivo') $prefijo = 'EF';

    $comprobante = $prefijo . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    $obsActual = trim($cita['observaciones'] ?? '');
    $notaPago = "Seña abonada: $" . number_format($montoAbonado, 0, ',', '.') . " (" . strtoupper($metodoPago) . " -> {$titularDestino} [{$bancoDestino}]) [{$comprobante}]";
    if (!empty($comprobanteCliente)) {
        $notaPago .= " Ref Cliente: {$comprobanteCliente}";
    }

    $nuevaObs = ($obsActual !== '' && $obsActual !== '-') ? $obsActual . ' | ' . $notaPago : $notaPago;

    // Actualizar cita a estado confirmado
    $updatedCita = $db->update('citas', 'id_cita', $idCita, [
        'estado' => 'confirmado',
        'observaciones' => $nuevaObs,
        'modificada_en' => date('Y-m-d H:i:s')
    ]);

    jsonResponse(true, [
        'id_cita' => $idCita,
        'comprobante' => $comprobante,
        'estado' => 'confirmado',
        'monto_sena' => $montoAbonado,
        'metodo_pago' => $metodoPago,
        'cuenta_destino' => [
            'titular' => $titularDestino,
            'banco' => $bancoDestino
        ],
        'fecha_pago' => date('Y-m-d H:i:s'),
        'redirect_url' => 'pago-confirmado.php?id_cita=' . $idCita . '&comprobante=' . urlencode($comprobante)
    ], 'Pago de seña procesado y acreditado exitosamente.');
}

jsonResponse(false, null, 'Método HTTP no permitido.', 405);
