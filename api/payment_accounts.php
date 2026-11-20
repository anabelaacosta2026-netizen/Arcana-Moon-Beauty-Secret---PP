<?php
/**
 * ARCANA — Payment Accounts Management API Endpoint (api/payment_accounts.php)
 * Permite a los administradores gestionar cuentas de cobro, keys de Mercado Pago / Ualá y seleccionar la cuenta receptora activa.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/JsonDB.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once __DIR__ . '/response.php';

$db = JsonDB::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Endpoints públicos o de checkout (obtener cuenta activa de destino)
if ($method === 'GET' && (isset($_GET['active']) || $action === 'get_active')) {
    $cuentas = $db->getTable('cuentas_cobro');
    
    // Cuenta principal activa
    $activas = array_values(array_filter($cuentas, function($c) {
        return (int)($c['es_activa'] ?? 0) === 1 && ($c['estado'] ?? 'activo') === 'activo';
    }));

    $cuentaActiva = !empty($activas) ? $activas[0] : (!empty($cuentas) ? $cuentas[0] : null);

    // Opciones sanitizadas para el cliente (NO exponer access_token)
    $cuentasSanitizadas = array_map(function($c) {
        return [
            'id_cuenta' => (int)($c['id_cuenta'] ?? $c['id'] ?? 0),
            'nombre_referencia' => $c['nombre_referencia'] ?? '',
            'proveedor' => $c['proveedor'] ?? 'mercadopago',
            'titular' => $c['titular'] ?? '',
            'cuit_cuil' => $c['cuit_cuil'] ?? '',
            'alias' => $c['alias'] ?? '',
            'cbu_cvu' => $c['cbu_cvu'] ?? '',
            'banco_billetera' => $c['banco_billetera'] ?? '',
            'public_key' => $c['public_key'] ?? '',
            'modo' => $c['modo'] ?? 'sandbox',
            'es_activa' => (int)($c['es_activa'] ?? 0)
        ];
    }, $cuentas);

    $activaSanitizada = null;
    if ($cuentaActiva) {
        $activaSanitizada = [
            'id_cuenta' => (int)($cuentaActiva['id_cuenta'] ?? $cuentaActiva['id'] ?? 0),
            'nombre_referencia' => $cuentaActiva['nombre_referencia'] ?? '',
            'proveedor' => $cuentaActiva['proveedor'] ?? 'mercadopago',
            'titular' => $cuentaActiva['titular'] ?? '',
            'cuit_cuil' => $cuentaActiva['cuit_cuil'] ?? '',
            'email_asociado' => $cuentaActiva['email_asociado'] ?? '',
            'alias' => $cuentaActiva['alias'] ?? '',
            'cbu_cvu' => $cuentaActiva['cbu_cvu'] ?? '',
            'banco_billetera' => $cuentaActiva['banco_billetera'] ?? '',
            'public_key' => $cuentaActiva['public_key'] ?? '',
            'modo' => $cuentaActiva['modo'] ?? 'sandbox',
            'es_activa' => 1
        ];
    }

    jsonResponse(true, [
        'cuenta_activa' => $activaSanitizada,
        'todas_cuentas' => $cuentasSanitizadas
    ], 'Configuración de cobro activa obtenida.');
}

// Para operaciones de gestión (GET lista completa, POST, PUT, DELETE), verificar rol de Administrador o Superadmin
if (!isAdmin() && !isSuperAdmin()) {
    jsonResponse(false, null, 'Acceso no autorizado. Se requieren permisos de administración.', 403);
}

// 1. OBTENER TODAS LAS CUENTAS (Para el panel de administración)
if ($method === 'GET') {
    $cuentas = $db->getTable('cuentas_cobro');
    
    // Si la tabla no existía o está vacía, inicializar con las cuentas por defecto SOLO si la BD está conectada
    if (empty($cuentas) && $db->isConnectedToMysql()) {
        $cuentas = [
            [
                'id_cuenta' => 1,
                'nombre_referencia' => 'Mercado Pago Principal (Admin Anabela)',
                'proveedor' => 'mercadopago',
                'titular' => 'Anabela Acosta',
                'cuit_cuil' => '27-40123456-4',
                'email_asociado' => 'anabelaacosta2026@gmail.com',
                'alias' => 'arcana.anabela.mp',
                'cbu_cvu' => '0000003100098765432190',
                'banco_billetera' => 'Mercado Pago',
                'public_key' => 'TEST-4f56a89c-1234-5678-9abc-def012345678',
                'access_token' => 'TEST-1234567890123456-123456-abcdef0123456789abcdef0123456789-123456789',
                'modo' => 'sandbox',
                'es_activa' => 1,
                'estado' => 'activo',
                'creado_en' => date('Y-m-d H:i:s')
            ]
        ];
        foreach ($cuentas as $cta) {
            $db->insert('cuentas_cobro', $cta, 'id_cuenta');
        }
        $cuentas = $db->getTable('cuentas_cobro');
    }

    jsonResponse(true, array_values($cuentas), 'Cuentas de cobro cargadas exitosamente.');
}

// 2. ACTIVAR UNA CUENTA DE COBRO ESPECÍFICA COMO RECEPTORA
if ($action === 'set_active' || ($method === 'POST' && isset($_GET['set_active']))) {
    $input = getJsonInput();
    $idCuenta = (int)($input['id_cuenta'] ?? $input['id'] ?? $_GET['id'] ?? 0);

    if (!$idCuenta) {
        jsonResponse(false, null, 'ID de cuenta no especificado.', 400);
    }

    $cuentas = $db->getTable('cuentas_cobro');
    $encontrada = false;

    foreach ($cuentas as $c) {
        $cid = (int)($c['id_cuenta'] ?? $c['id'] ?? 0);
        $esEsta = ($cid === $idCuenta);
        if ($esEsta) $encontrada = true;

        $db->update('cuentas_cobro', 'id_cuenta', $cid, [
            'es_activa' => $esEsta ? 1 : 0,
            'actualizado_en' => date('Y-m-d H:i:s')
        ]);
    }

    if (!$encontrada) {
        jsonResponse(false, null, 'No se encontró la cuenta especificada.', 404);
    }

    $cuentaActualizada = $db->getById('cuentas_cobro', 'id_cuenta', $idCuenta);
    $nombreCuenta = $cuentaActualizada['nombre_referencia'] ?? "#$idCuenta";
    registrarCambioDashboard('💳 Cuentas y Cobros', 'Activación de Cuenta Receptora', 'Activó la cuenta "' . $nombreCuenta . '" (Titular: ' . ($cuentaActualizada['titular'] ?? '') . ') para recibir todos los fondos');

    jsonResponse(true, $cuentaActualizada, '¡Cuenta activada con éxito! Todos los cobros y señas se redirigirán a esta cuenta.');
}

// 3. CREAR NUEVA CUENTA DE COBRO
if ($method === 'POST') {
    $input = getJsonInput();
    $nombreRef = trim($input['nombre_referencia'] ?? '');
    $proveedor = trim($input['proveedor'] ?? 'mercadopago');
    $titular = trim($input['titular'] ?? '');

    if (empty($nombreRef) || empty($titular)) {
        jsonResponse(false, null, 'El nombre de referencia y el titular son obligatorios.', 400);
    }

    $esActiva = !empty($input['es_activa']) ? 1 : 0;

    // Si se marca como activa, desmarcar las otras
    if ($esActiva === 1) {
        $cuentas = $db->getTable('cuentas_cobro');
        foreach ($cuentas as $c) {
            $cid = (int)($c['id_cuenta'] ?? $c['id'] ?? 0);
            $db->update('cuentas_cobro', 'id_cuenta', $cid, ['es_activa' => 0]);
        }
    }

    $nuevaCuenta = [
        'nombre_referencia' => $nombreRef,
        'proveedor' => $proveedor,
        'titular' => $titular,
        'cuit_cuil' => trim($input['cuit_cuil'] ?? ''),
        'email_asociado' => trim($input['email_asociado'] ?? ''),
        'alias' => trim($input['alias'] ?? ''),
        'cbu_cvu' => trim($input['cbu_cvu'] ?? ''),
        'banco_billetera' => trim($input['banco_billetera'] ?? 'Mercado Pago'),
        'public_key' => trim($input['public_key'] ?? ''),
        'access_token' => trim($input['access_token'] ?? ''),
        'modo' => trim($input['modo'] ?? 'sandbox'),
        'es_activa' => $esActiva,
        'estado' => trim($input['estado'] ?? 'activo'),
        'creado_en' => date('Y-m-d H:i:s'),
        'actualizado_en' => date('Y-m-d H:i:s')
    ];

    $creada = $db->insert('cuentas_cobro', $nuevaCuenta, 'id_cuenta');

    registrarCambioDashboard('💳 Cuentas y Cobros', 'Creación de Cuenta de Cobro', 'Creó la cuenta "' . $nombreRef . '" (' . strtoupper($proveedor) . ', Titular: ' . $titular . ')');

    jsonResponse(true, $creada, 'Cuenta de cobro creada exitosamente.', 201);
}

// 4. ACTUALIZAR CUENTA DE COBRO
if ($method === 'PUT') {
    $input = getJsonInput();
    $idCuenta = (int)($input['id_cuenta'] ?? $input['id'] ?? 0);

    if (!$idCuenta) {
        jsonResponse(false, null, 'ID de cuenta no especificado para actualizar.', 400);
    }

    $cuentaActual = $db->getById('cuentas_cobro', 'id_cuenta', $idCuenta);
    if (!$cuentaActual) {
        jsonResponse(false, null, 'Cuenta de cobro no encontrada.', 404);
    }

    $esActiva = isset($input['es_activa']) ? ((int)$input['es_activa'] === 1 ? 1 : 0) : (int)($cuentaActual['es_activa'] ?? 0);

    if ($esActiva === 1 && (int)($cuentaActual['es_activa'] ?? 0) === 0) {
        $cuentas = $db->getTable('cuentas_cobro');
        foreach ($cuentas as $c) {
            $cid = (int)($c['id_cuenta'] ?? $c['id'] ?? 0);
            $db->update('cuentas_cobro', 'id_cuenta', $cid, ['es_activa' => 0]);
        }
    }

    $dataUpdate = [
        'nombre_referencia' => trim($input['nombre_referencia'] ?? $cuentaActual['nombre_referencia']),
        'proveedor' => trim($input['proveedor'] ?? $cuentaActual['proveedor']),
        'titular' => trim($input['titular'] ?? $cuentaActual['titular']),
        'cuit_cuil' => trim($input['cuit_cuil'] ?? $cuentaActual['cuit_cuil']),
        'email_asociado' => trim($input['email_asociado'] ?? $cuentaActual['email_asociado']),
        'alias' => trim($input['alias'] ?? $cuentaActual['alias']),
        'cbu_cvu' => trim($input['cbu_cvu'] ?? $cuentaActual['cbu_cvu']),
        'banco_billetera' => trim($input['banco_billetera'] ?? $cuentaActual['banco_billetera']),
        'public_key' => trim($input['public_key'] ?? $cuentaActual['public_key']),
        'modo' => trim($input['modo'] ?? $cuentaActual['modo']),
        'es_activa' => $esActiva,
        'estado' => trim($input['estado'] ?? $cuentaActual['estado']),
        'actualizado_en' => date('Y-m-d H:i:s')
    ];

    // Solo actualizar access_token si el usuario envió un valor no vacío
    $tokenModificado = false;
    if (!empty($input['access_token'])) {
        $dataUpdate['access_token'] = trim($input['access_token']);
        $tokenModificado = true;
    }

    // Detectar campos modificados para auditoría específica
    $cambiosDetectados = [];
    if (isset($input['nombre_referencia']) && trim($input['nombre_referencia']) !== ($cuentaActual['nombre_referencia'] ?? '')) {
        $cambiosDetectados[] = 'nombre de "' . ($cuentaActual['nombre_referencia'] ?? '') . '" a "' . trim($input['nombre_referencia']) . '"';
    }
    if (isset($input['alias']) && trim($input['alias']) !== ($cuentaActual['alias'] ?? '')) {
        $cambiosDetectados[] = 'Alias de "' . ($cuentaActual['alias'] ?? '') . '" a "' . trim($input['alias']) . '"';
    }
    if (isset($input['cbu_cvu']) && trim($input['cbu_cvu']) !== ($cuentaActual['cbu_cvu'] ?? '')) {
        $cambiosDetectados[] = 'CBU/CVU de "' . ($cuentaActual['cbu_cvu'] ?? '') . '" a "' . trim($input['cbu_cvu']) . '"';
    }
    if (isset($input['titular']) && trim($input['titular']) !== ($cuentaActual['titular'] ?? '')) {
        $cambiosDetectados[] = 'titular a "' . trim($input['titular']) . '"';
    }
    if (isset($input['banco_billetera']) && trim($input['banco_billetera']) !== ($cuentaActual['banco_billetera'] ?? '')) {
        $cambiosDetectados[] = 'banco/billetera a "' . trim($input['banco_billetera']) . '"';
    }
    if (isset($input['modo']) && trim($input['modo']) !== ($cuentaActual['modo'] ?? '')) {
        $cambiosDetectados[] = 'modo de ' . ($cuentaActual['modo'] ?? 'produccion') . ' a ' . trim($input['modo']);
    }
    if ($tokenModificado) {
        $cambiosDetectados[] = 'renovó credenciales API / Access Token';
    }
    if ($esActiva !== (int)($cuentaActual['es_activa'] ?? 0)) {
        $cambiosDetectados[] = $esActiva === 1 ? 'establecida como cuenta receptora activa' : 'removida como receptora activa';
    }

    $actualizada = $db->update('cuentas_cobro', 'id_cuenta', $idCuenta, $dataUpdate);

    $nombreCuenta = $dataUpdate['nombre_referencia'] ?? ($cuentaActual['nombre_referencia'] ?? "#$idCuenta");
    $detalleLog = !empty($cambiosDetectados)
        ? 'Modificó en la cuenta de cobro "' . $nombreCuenta . '": ' . implode(', ', $cambiosDetectados)
        : 'Actualizó información de la cuenta de cobro "' . $nombreCuenta . '" (ID #' . $idCuenta . ')';

    registrarCambioDashboard('💳 Cuentas y Cobros', 'Actualización de Cuenta', $detalleLog, $idCuenta);

    jsonResponse(true, $actualizada, 'Cuenta de cobro actualizada correctamente.');
}

// 5. DESACTIVAR CUENTA DE COBRO (Soft Delete)
if ($method === 'DELETE') {
    $input = getJsonInput();
    $idCuenta = (int)($input['id_cuenta'] ?? $input['id'] ?? $_GET['id'] ?? 0);

    if (!$idCuenta) {
        jsonResponse(false, null, 'ID de cuenta no especificado para desactivar.', 400);
    }

    $cuenta = $db->getById('cuentas_cobro', 'id_cuenta', $idCuenta);
    if (!$cuenta) {
        jsonResponse(false, null, 'Cuenta de cobro no encontrada.', 404);
    }

    $nombreRef = $cuenta['nombre_referencia'] ?? "#$idCuenta";

    // Soft delete: cambiar estado a inactivo
    $db->update('cuentas_cobro', 'id_cuenta', $idCuenta, [
        'estado' => 'inactivo',
        'es_activa' => 0,
        'actualizado_en' => date('Y-m-d H:i:s')
    ]);

    // Si se desactivó la activa, marcar la primera activa restante si existe
    if ((int)($cuenta['es_activa'] ?? 0) === 1) {
        $activas = $db->find('cuentas_cobro', function($c) use ($idCuenta) {
            return (int)$c['id_cuenta'] !== $idCuenta && ($c['estado'] ?? 'activo') !== 'inactivo';
        });
        if (!empty($activas)) {
            $primeraId = (int)($activas[0]['id_cuenta'] ?? $activas[0]['id'] ?? 0);
            if ($primeraId) {
                $db->update('cuentas_cobro', 'id_cuenta', $primeraId, ['es_activa' => 1]);
            }
        }
    }

    registrarCambioDashboard('💳 Cuentas y Cobros', 'Desactivación de Cuenta', 'Desactivó (pasó a inactiva) la cuenta "' . $nombreRef . '" (ID #' . $idCuenta . ')');

    jsonResponse(true, ['id_cuenta' => $idCuenta, 'estado' => 'inactivo'], 'Cuenta de cobro desactivada correctamente (estado inactivo).');
}

jsonResponse(false, null, 'Método HTTP o acción no válida.', 400);

