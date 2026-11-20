<?php
/**
 * ARCANA — Real Mercado Pago Integration & Checkout (mercadopago-checkout.php)
 * Conexión directa con la API oficial y SDK v2 de Mercado Pago para cobro real de señas.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/JsonDB.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/mercadopago.php';

$db = JsonDB::getInstance();
$idCita = (int)($_GET['id_cita'] ?? 0);

$cita = null;
$servicioNombre = 'Servicio de Belleza';
$clienteNombre = 'Cliente';
$clienteEmail = 'cliente@ejemplo.com';
$precioTotal = 25000.0;
$montoSena = 7500.0;
$fechaTurno = date('d/m/Y');
$horaTurno = '10:00';

if ($idCita) {
    $cita = $db->getById('citas', 'id_cita', $idCita);
    if ($cita) {
        $fechaTurno = date('d/m/Y', strtotime(substr($cita['fecha_hora'], 0, 10)));
        $horaTurno = substr($cita['fecha_hora'], 11, 5);

        // Cliente
        $u = $db->getById('usuario', 'id_usuario', (int)$cita['id_usuario']);
        if ($u) {
            $clienteNombre = trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? ''));
            $clienteEmail = $u['email'] ?? 'cliente@ejemplo.com';
        }

        // Servicio
        $cs = $db->find('cita_servicio', function($item) use ($idCita) {
            return (int)$item['id_cita'] === $idCita;
        });
        if (!empty($cs)) {
            $se = $db->getById('servicio_empleado', 'id_servicio_empleado', (int)$cs[0]['id_servicio_empleado']);
            if ($se) {
                $srv = $db->getById('servicio', 'id_servicio', (int)$se['id_servicio']);
                if ($srv) {
                    $servicioNombre = $srv['nombre_servicio'];
                    $precios = $db->find('precio_servicio', function($p) use ($srv) {
                        return (int)$p['id_servicio'] === (int)$srv['id_servicio'];
                    });
                    if (!empty($precios)) {
                        $ultimo = end($precios);
                        $precioTotal = (float)($ultimo['precio'] ?? 25000);
                    }
                }
            }
        }
        $montoSena = round($precioTotal * 0.30);
    }
}

// Obtener la cuenta de cobro activa configurada por el Administrador
$cuentas = $db->getTable('cuentas_cobro');
$cuentaActiva = null;
foreach ($cuentas as $c) {
    if ((int)($c['es_activa'] ?? 0) === 1 && ($c['estado'] ?? 'activo') === 'activo') {
        $cuentaActiva = $c;
        break;
    }
}
if (!$cuentaActiva && !empty($cuentas)) {
    $cuentaActiva = $cuentas[0];
}

$idCuentaActiva = $cuentaActiva['id_cuenta'] ?? 1;
$proveedorActivo = $cuentaActiva['proveedor'] ?? 'mercadopago';
$titularActivo = $cuentaActiva['titular'] ?? 'Arcana Moon Beauty Secret';
$referenciaActiva = $cuentaActiva['nombre_referencia'] ?? 'Cuenta Principal';
$aliasActivo = $cuentaActiva['alias'] ?? 'arcana.moon.estetica';
$cbuActivo = $cuentaActiva['cbu_cvu'] ?? '0000003100098765432190';
$bancoActivo = $cuentaActiva['banco_billetera'] ?? 'Mercado Pago';
$cuitActivo = $cuentaActiva['cuit_cuil'] ?? '27-40123456-4';
$publicKeyActiva = $cuentaActiva['public_key'] ?? env('MP_PUBLIC_KEY', 'TEST-4f56a89c-1234-5678-9abc-def012345678');
$accessTokenActivo = $cuentaActiva['access_token'] ?? env('MP_ACCESS_TOKEN', '');
$modoActivo = $cuentaActiva['modo'] ?? 'sandbox';

// Crear Preferencia Real en la API de Mercado Pago
$mpPreferenceId = null;
$mpInitPoint = null;
$mpPreferenceError = null;

if (!empty($accessTokenActivo)) {
    $baseUrl = defined('BASE_URL') ? BASE_URL : 'http://localhost:8000';
    $preferencePayload = [
        'items' => [
            [
                'id' => (string)$idCita,
                'title' => 'Seña de Reserva (30%) — ' . $servicioNombre,
                'description' => 'Turno para ' . $servicioNombre . ' el ' . $fechaTurno . ' a las ' . $horaTurno . ' hs en Arcana Moon',
                'quantity' => 1,
                'currency_id' => 'ARS',
                'unit_price' => (float)$montoSena
            ]
        ],
        'payer' => [
            'name' => $clienteNombre,
            'email' => $clienteEmail
        ],
        'back_urls' => [
            'success' => $baseUrl . '/pago-confirmado.php?id_cita=' . $idCita . '&status=approved',
            'pending' => $baseUrl . '/pago-confirmado.php?id_cita=' . $idCita . '&status=pending',
            'failure' => $baseUrl . '/mercadopago-checkout.php?id_cita=' . $idCita . '&error=pago_rechazado'
        ],
        'auto_return' => 'approved',
        'external_reference' => (string)$idCita,
        'statement_descriptor' => 'ARCANA ESTETICA'
    ];

    $mpRes = mp_create_preference($preferencePayload, $accessTokenActivo);
    if ($mpRes['success'] && !empty($mpRes['data']['id'])) {
        $mpPreferenceId = $mpRes['data']['id'];
        $mpInitPoint = ($modoActivo === 'sandbox') ? ($mpRes['data']['sandbox_init_point'] ?? $mpRes['data']['init_point']) : $mpRes['data']['init_point'];
    } else {
        $mpPreferenceError = $mpRes['error'] ?? 'No se pudo generar la preferencia automática en Mercado Pago.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pagar Seña con Mercado Pago — Arcana Moon</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script>
    (function() {
      try {
        if (window.location.username || window.location.password) {
          window.history.replaceState(null, '', window.location.pathname + window.location.search + window.location.hash);
        }
      } catch (e) {}

      const origFetch = window.fetch;
      window.fetch = function(resource, init) {
        if (typeof resource === 'string' || resource instanceof URL) {
          try {
            const u = new URL(resource, window.location.origin + window.location.pathname);
            u.username = '';
            u.password = '';
            resource = u.href;
          } catch (e) {}
        }
        return origFetch.call(this, resource, init);
      };
    })();
  </script>
  
  <!-- SDK Oficial de Mercado Pago v2 -->
  <script src="https://sdk.mercadopago.com/js/v2"></script>

  <style>
    :root {
      --mp-blue: #009ee3;
      --mp-blue-dark: #007eb5;
      --mp-blue-light: #e6f7fc;
      --mp-navy: #1f2937;
      --mp-border: #e5e7eb;
      --mp-text: #333333;
      --mp-muted: #6b7280;
      --mp-green: #00a650;
      --mp-green-dark: #008740;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    body {
      background-color: #ededed;
      color: var(--mp-text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* MP Header */
    .mp-header {
      background: linear-gradient(90deg, #009ee3 0%, #007eb5 100%);
      color: white;
      padding: 0.875rem 1.5rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }

    .mp-header__inner {
      max-width: 920px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.75rem;
    }

    .mp-header__logo {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      font-weight: 700;
      font-size: 1.25rem;
      text-decoration: none;
      color: white;
    }

    .mp-badge-sandbox {
      background: rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.4);
      padding: 0.2rem 0.6rem;
      border-radius: 9999px;
      font-size: 0.725rem;
      font-weight: 600;
      letter-spacing: 0.05em;
    }

    .mp-header__secure {
      display: flex;
      align-items: center;
      gap: 0.35rem;
      font-size: 0.8125rem;
      color: rgba(255, 255, 255, 0.95);
    }

    /* Main Container */
    .mp-main {
      flex: 1;
      max-width: 920px;
      margin: 2rem auto;
      padding: 0 1rem;
      width: 100%;
    }

    .mp-grid {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 1.5rem;
      align-items: start;
    }

    @media (max-width: 768px) {
      .mp-grid {
        grid-template-columns: 1fr;
      }
    }

    /* Destination Notice Banner */
    .destination-banner {
      background: #ffffff;
      border: 1px solid #d1d5db;
      border-left: 4px solid #00a650;
      border-radius: 8px;
      padding: 0.85rem 1.15rem;
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 0.5rem;
      font-size: 0.825rem;
    }

    /* Cards */
    .mp-card {
      background: white;
      border-radius: 8px;
      padding: 1.75rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      border: 1px solid var(--mp-border);
    }

    .mp-title {
      font-size: 1.15rem;
      font-weight: 600;
      margin-bottom: 1.25rem;
      color: #111827;
    }

    /* Payment Methods Tabs */
    .mp-method-option {
      border: 1.5px solid var(--mp-border);
      border-radius: 8px;
      padding: 1rem;
      margin-bottom: 0.75rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.85rem;
      transition: all 0.2s ease;
    }

    .mp-method-option:hover {
      border-color: var(--mp-blue);
      background: var(--mp-blue-light);
    }

    .mp-method-option.selected {
      border-color: var(--mp-blue);
      background: #f0f9fd;
    }

    .mp-method-radio {
      accent-color: var(--mp-blue);
      width: 18px;
      height: 18px;
    }

    .mp-method-info {
      flex: 1;
    }

    .mp-method-name {
      font-weight: 600;
      font-size: 0.92rem;
      color: #111827;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .mp-method-desc {
      font-size: 0.8rem;
      color: var(--mp-muted);
      margin-top: 0.15rem;
    }

    /* Form Fields for Card */
    .mp-form-group {
      margin-bottom: 1rem;
    }

    .mp-label {
      display: block;
      font-size: 0.8rem;
      font-weight: 600;
      color: #374151;
      margin-bottom: 0.35rem;
    }

    .mp-input {
      width: 100%;
      padding: 0.7rem 0.85rem;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      font-size: 0.875rem;
      outline: none;
      transition: border-color 0.2s;
    }

    .mp-input:focus {
      border-color: var(--mp-blue);
      box-shadow: 0 0 0 3px rgba(0, 158, 227, 0.15);
    }

    .mp-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.75rem;
    }

    /* Buttons */
    .mp-btn-mp {
      width: 100%;
      background: #009ee3;
      color: white;
      border: none;
      padding: 1rem;
      border-radius: 6px;
      font-size: 1.05rem;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 0.6rem;
      box-shadow: 0 3px 6px rgba(0, 158, 227, 0.3);
      transition: background 0.2s, transform 0.1s;
      text-decoration: none;
    }

    .mp-btn-mp:hover {
      background: #007eb5;
      transform: translateY(-1px);
    }

    .mp-btn-pay {
      width: 100%;
      background-color: var(--mp-blue);
      color: white;
      border: none;
      padding: 0.95rem;
      border-radius: 6px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 0.5rem;
      margin-top: 1.25rem;
      box-shadow: 0 2px 4px rgba(0, 158, 227, 0.3);
      transition: background-color 0.2s;
    }

    .mp-btn-pay:hover {
      background-color: var(--mp-blue-dark);
    }

    .mp-btn-pay:disabled {
      opacity: 0.65;
      cursor: not-allowed;
    }

    /* Order Summary Sidebar */
    .mp-summary-brand {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      margin-bottom: 1rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--mp-border);
    }

    .mp-summary-brand-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #3b0764;
      color: #fbcfe8;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 1.1rem;
    }

    .mp-summary-item {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.6rem;
      font-size: 0.875rem;
    }

    .mp-summary-total {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1.5px dashed var(--mp-border);
    }

    .mp-summary-total-label {
      font-weight: 700;
      font-size: 1rem;
      color: #111827;
    }

    .mp-summary-total-value {
      font-weight: 700;
      font-size: 1.5rem;
      color: var(--mp-blue-dark);
    }

    .btn-copy {
      background: #ffffff;
      border: 1px solid #d1d5db;
      border-radius: 4px;
      padding: 2px 8px;
      font-size: 0.75rem;
      cursor: pointer;
      margin-left: 0.5rem;
      color: #374151;
    }
    .btn-copy:hover {
      background: #f3f4f6;
    }

    /* Loading overlay */
    .mp-loading-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(255, 255, 255, 0.95);
      z-index: 9999;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 1.25rem;
    }

    .mp-spinner {
      width: 48px;
      height: 48px;
      border: 4px solid #e5e7eb;
      border-top-color: var(--mp-blue);
      border-radius: 50%;
      animation: mp-spin 0.8s linear infinite;
    }

    @keyframes mp-spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>

  <!-- Mercado Pago Official Styled Header -->
  <header class="mp-header">
    <div class="mp-header__inner">
      <div style="display: flex; align-items: center; gap: 0.85rem;">
        <a href="#" class="mp-header__logo">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
          </svg>
          mercado pago
        </a>
        <span class="mp-badge-sandbox"><?= strtoupper(htmlspecialchars($modoActivo)) ?> · <?= strtoupper(htmlspecialchars($proveedorActivo)) ?></span>
      </div>
      <div class="mp-header__secure">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Conexión Segura Mercado Pago
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main class="mp-main">

    <!-- Destino del dinero configurado por el Administrador -->
    <div class="destination-banner">
      <div>
        <strong style="color: #00a650; display: inline-flex; align-items: center; gap: 0.3rem;">
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
          Destino Activo de Fondos:
        </strong>
        <span><strong><?= htmlspecialchars($titularActivo) ?></strong> (<?= htmlspecialchars($referenciaActiva) ?>)</span>
      </div>
      <div style="color: var(--mp-muted);">
        Entidad: <strong><?= htmlspecialchars($bancoActivo) ?></strong>
      </div>
    </div>

    <?php if (!empty($_GET['error'])): ?>
      <div style="background: rgba(239,68,68,0.1); border: 1px solid #ef4444; color: #ef4444; padding: 0.85rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.875rem; text-align: center;">
        El pago no pudo ser completado. Por favor intenta con otro método o verifica los datos.
      </div>
    <?php endif; ?>

    <div class="mp-grid">
      <!-- Left Column: Payment Methods -->
      <div class="mp-card">
        <h2 class="mp-title">¿Cómo quieres pagar tu seña de $ <?= number_format($montoSena, 0, ',', '.') ?>?</h2>

        <!-- OPCIÓN 1: Checkout Oficial de Mercado Pago (Wallet & Checkout Pro) -->
        <div class="mp-method-option selected" data-method="mercadopago_pro">
          <input type="radio" name="metodo_pago" value="mercadopago_pro" id="m-mp-pro" class="mp-method-radio" checked>
          <div class="mp-method-info">
            <label for="m-mp-pro" class="mp-method-name" style="cursor: pointer;">
              💙 Pagar con Mercado Pago (Cuenta, Dinero o Cuotas)
            </label>
            <div class="mp-method-desc">Abre Mercado Pago para pagar con tu saldo, QR, débito o tarjetas guardadas</div>
          </div>
        </div>

        <!-- OPCIÓN 2: Tarjeta Directa (Checkout API con Mercado Pago) -->
        <div class="mp-method-option" data-method="tarjeta_api">
          <input type="radio" name="metodo_pago" value="tarjeta_api" id="m-tarjeta-api" class="mp-method-radio">
          <div class="mp-method-info">
            <label for="m-tarjeta-api" class="mp-method-name" style="cursor: pointer;">
              💳 Tarjeta de Débito o Crédito (Sin salir del sitio)
            </label>
            <div class="mp-method-desc">Procesamiento seguro directo con la API de Mercado Pago</div>
          </div>
        </div>

        <!-- OPCIÓN 3: Transferencia Bancaria / CVU / Alias -->
        <div class="mp-method-option" data-method="transferencia">
          <input type="radio" name="metodo_pago" value="transferencia" id="m-transf" class="mp-method-radio">
          <div class="mp-method-info">
            <label for="m-transf" class="mp-method-name" style="cursor: pointer;">
              🏦 Transferencia Bancaria / CVU / Alias
            </label>
            <div class="mp-method-desc">Transfiere desde Mercado Pago, Cuenta DNI, Personal Pay o tu Banco</div>
          </div>
        </div>

        <!-- OPCIÓN 4: Pago en Efectivo -->
        <div class="mp-method-option" data-method="efectivo">
          <input type="radio" name="metodo_pago" value="efectivo" id="m-efectivo" class="mp-method-radio">
          <div class="mp-method-info">
            <label for="m-efectivo" class="mp-method-name" style="cursor: pointer;">
              💵 Pago en Efectivo en el Salón
            </label>
            <div class="mp-method-desc">Reserva tu turno y abona la seña personalmente en recepción</div>
          </div>
        </div>

        <!-- ================= SECCIÓN A: Checkout Pro / Wallet Button ================= -->
        <div id="section-mp-pro" style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--mp-border);">
          <div style="background: #f0f9fd; border: 1px solid #bae6fd; border-radius: 8px; padding: 1.25rem; text-align: center; margin-bottom: 1.25rem;">
            <div style="font-size: 24px; margin-bottom: 0.35rem;">🛡️</div>
            <strong style="color: #0369a1; font-size: 1rem;">Pasarela Oficial Mercado Pago</strong>
            <p style="font-size: 0.825rem; color: var(--mp-muted); margin-top: 0.25rem;">
              Al hacer clic en el botón, se abrirá la pasarela oficial de Mercado Pago para acreditar los <strong>$ <?= number_format($montoSena, 0, ',', '.') ?></strong> en la cuenta de <strong><?= htmlspecialchars($titularActivo) ?></strong>.
            </p>
          </div>

          <?php if (!empty($mpInitPoint)): ?>
            <a href="<?= htmlspecialchars($mpInitPoint) ?>" class="mp-btn-mp" id="btn-open-mp-pro">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 11V7a5 5 0 0 1 10 0v4"/><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/></svg>
              Pagar $ <?= number_format($montoSena, 0, ',', '.') ?> con Mercado Pago &rarr;
            </a>
          <?php else: ?>
            <button type="button" id="btn-fallback-mp-pro" class="mp-btn-mp">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 11V7a5 5 0 0 1 10 0v4"/><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/></svg>
              Pagar $ <?= number_format($montoSena, 0, ',', '.') ?> con Mercado Pago
            </button>
            <?php if (!empty($mpPreferenceError)): ?>
              <small style="display: block; color: var(--mp-muted); font-size: 0.75rem; text-align: center; margin-top: 0.5rem;">
                (<?= htmlspecialchars($mpPreferenceError) ?> - Se usará procesamiento por token directo)
              </small>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <!-- ================= SECCIÓN B: Formulario Tarjeta (Checkout API) ================= -->
        <div id="section-tarjeta-api" style="display: none; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--mp-border);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
            <span style="font-size: 0.85rem; font-weight: 600; color: #111827;">Datos de la tarjeta</span>
            <button type="button" id="btn-fill-test-card" style="background: none; border: none; color: var(--mp-blue); font-size: 0.775rem; cursor: pointer; text-decoration: underline;">
              ⚡ Llenar tarjeta de prueba
            </button>
          </div>

          <form id="form-card-api">
            <div class="mp-form-group">
              <label class="mp-label" for="card-number">Número de tarjeta</label>
              <input type="text" id="card-number" class="mp-input" placeholder="4509 1234 5678 9012" maxlength="19" required>
            </div>

            <div class="mp-form-group">
              <label class="mp-label" for="card-holder">Nombre del titular como figura en el plástico</label>
              <input type="text" id="card-holder" class="mp-input" placeholder="Ej: <?= htmlspecialchars($clienteNombre) ?>" value="<?= htmlspecialchars($clienteNombre) ?>" required>
            </div>

            <div class="mp-row">
              <div class="mp-form-group">
                <label class="mp-label" for="card-expiry">Vencimiento</label>
                <input type="text" id="card-expiry" class="mp-input" placeholder="MM/AA" maxlength="5" required>
              </div>
              <div class="mp-form-group">
                <label class="mp-label" for="card-cvv">Código de seguridad (CVV)</label>
                <input type="password" id="card-cvv" class="mp-input" placeholder="123" maxlength="4" required>
              </div>
            </div>

            <button type="submit" id="btn-submit-card-api" class="mp-btn-pay">
              Pagar seña de $ <?= number_format($montoSena, 0, ',', '.') ?> vía API
            </button>
          </form>
        </div>

        <!-- ================= SECCIÓN C: Transferencia Bancaria ================= -->
        <div id="section-transferencia" style="display: none; margin-top: 1.5rem; padding: 1.25rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; font-size: 0.85rem; line-height: 1.6;">
          <div style="font-weight: 700; color: #166534; font-size: 0.95rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
            <span>🏦</span> Datos de la cuenta receptora (Admin)
          </div>
          
          <div style="margin-bottom: 0.4rem;">
            • <strong>Titular:</strong> <?= htmlspecialchars($titularActivo) ?><br>
            • <strong>CUIT/CUIL:</strong> <?= htmlspecialchars($cuitActivo) ?><br>
            • <strong>Entidad / Billetera:</strong> <?= htmlspecialchars($bancoActivo) ?>
          </div>

          <div style="background: #ffffff; padding: 0.6rem 0.8rem; border-radius: 6px; border: 1px dashed #86efac; margin: 0.5rem 0;">
            • <strong>Alias:</strong> <code><?= htmlspecialchars($aliasActivo) ?></code>
            <button type="button" class="btn-copy" onclick="copyText('<?= htmlspecialchars($aliasActivo) ?>', this)">Copiar Alias</button><br>
            
            • <strong>CBU / CVU:</strong> <code><?= htmlspecialchars($cbuActivo) ?></code>
            <button type="button" class="btn-copy" onclick="copyText('<?= htmlspecialchars($cbuActivo) ?>', this)">Copiar CBU</button>
          </div>

          <div style="margin-top: 0.75rem;">
            <label class="mp-label" for="nro-comprobante-tr">Nº de Operación / Comprobante de Transferencia:</label>
            <input type="text" id="nro-comprobante-tr" class="mp-input" placeholder="Ej: OP-984124 o Código de la app">
          </div>

          <button type="button" id="btn-submit-transferencia" class="mp-btn-pay" style="background-color: #16a34a;">
            Confirmar Transferencia de $ <?= number_format($montoSena, 0, ',', '.') ?>
          </button>
        </div>

        <!-- ================= SECCIÓN D: Efectivo en Salón ================= -->
        <div id="section-efectivo" style="display: none; margin-top: 1.5rem; padding: 1.25rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; font-size: 0.85rem; line-height: 1.6; color: #92400e;">
          <strong>💵 Pago Presencial en Recepción:</strong><br>
          Tu turno quedará pre-confirmado en el sistema. Deberás abonar los <strong>$ <?= number_format($montoSena, 0, ',', '.') ?></strong> en recepción antes de iniciar el tratamiento.
          
          <button type="button" id="btn-submit-efectivo" class="mp-btn-pay" style="background-color: #d97706;">
            Confirmar Reserva (Pago en Salón)
          </button>
        </div>

        <p style="text-align: center; font-size: 0.75rem; color: var(--mp-muted); margin-top: 1rem;">
          Al abonar o reservar, aceptas las políticas de asistencia de Arcana Moon.
        </p>
      </div>

      <!-- Right Column: Order Summary -->
      <div class="mp-card" style="background: #fafafa;">
        <div class="mp-summary-brand">
          <div class="mp-summary-brand-icon">A</div>
          <div>
            <strong style="font-size: 0.95rem; color: #111827; display: block;">Arcana Moon</strong>
            <span style="font-size: 0.775rem; color: var(--mp-muted);">Beauty Secret · Salón de Estética</span>
          </div>
        </div>

        <div style="margin-bottom: 1.25rem;">
          <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--mp-muted); font-weight: 600; margin-bottom: 0.4rem;">Detalle del Turno</div>
          <div style="font-weight: 600; font-size: 0.95rem; color: #111827;"><?= htmlspecialchars($servicioNombre) ?></div>
          <div style="font-size: 0.825rem; color: var(--mp-muted); margin-top: 0.2rem;">
            📅 <?= $fechaTurno ?> a las <?= $horaTurno ?> hs
          </div>
          <div style="font-size: 0.825rem; color: var(--mp-muted);">
            👤 Cliente: <?= htmlspecialchars($clienteNombre) ?>
          </div>
        </div>

        <div class="mp-summary-item">
          <span style="color: var(--mp-muted);">Valor total del servicio:</span>
          <span>$ <?= number_format($precioTotal, 0, ',', '.') ?></span>
        </div>
        <div class="mp-summary-item">
          <span style="color: var(--mp-muted);">Seña de reserva (30%):</span>
          <span style="font-weight: 600; color: #111827;">$ <?= number_format($montoSena, 0, ',', '.') ?></span>
        </div>
        <div class="mp-summary-item" style="font-size: 0.775rem; color: var(--mp-muted);">
          <span>Saldo restante en el salón:</span>
          <span>$ <?= number_format($precioTotal - $montoSena, 0, ',', '.') ?></span>
        </div>

        <div class="mp-summary-total">
          <span class="mp-summary-total-label">Total Seña:</span>
          <span class="mp-summary-total-value">$ <?= number_format($montoSena, 0, ',', '.') ?></span>
        </div>

        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--mp-border); text-align: center;">
          <a href="agenda.php" style="font-size: 0.825rem; color: var(--mp-muted); text-decoration: none;">&larr; Volver a la agenda</a>
        </div>
      </div>
    </div>
  </main>

  <!-- Fullscreen Loading Overlay -->
  <div class="mp-loading-overlay" id="mp-loader">
    <div class="mp-spinner"></div>
    <div style="text-align: center;">
      <h3 id="loader-title" style="font-size: 1.15rem; font-weight: 600; color: #111827; margin-bottom: 0.35rem;">Conectando con Mercado Pago...</h3>
      <p id="loader-subtitle" style="font-size: 0.85rem; color: var(--mp-muted);">Acreditando seña en la cuenta de <?= htmlspecialchars($titularActivo) ?></p>
    </div>
  </div>

  <script>
    const idCita = <?= json_encode($idCita) ?>;
    const montoSena = <?= json_encode($montoSena) ?>;
    const idCuenta = <?= json_encode($idCuentaActiva) ?>;
    const mpPublicKey = <?= json_encode($publicKeyActiva) ?>;

    // Inicializar SDK de Mercado Pago
    let mp = null;
    try {
      if (window.MercadoPago && mpPublicKey) {
        mp = new MercadoPago(mpPublicKey, { locale: 'es-AR' });
      }
    } catch (e) {
      console.warn('MercadoPago SDK init notice:', e);
    }

    // Selector de métodos de pago
    const methodCards = document.querySelectorAll('.mp-method-option');
    const secMpPro = document.getElementById('section-mp-pro');
    const secTarjetaApi = document.getElementById('section-tarjeta-api');
    const secTransferencia = document.getElementById('section-transferencia');
    const secEfectivo = document.getElementById('section-efectivo');

    methodCards.forEach(card => {
      card.addEventListener('click', () => {
        methodCards.forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        const radio = card.querySelector('.mp-method-radio');
        if (radio) radio.checked = true;

        const method = card.dataset.method;
        secMpPro.style.display = (method === 'mercadopago_pro') ? 'block' : 'none';
        secTarjetaApi.style.display = (method === 'tarjeta_api') ? 'block' : 'none';
        secTransferencia.style.display = (method === 'transferencia') ? 'block' : 'none';
        secEfectivo.style.display = (method === 'efectivo') ? 'block' : 'none';
      });
    });

    // Copiar al portapapeles
    function copyText(text, btn) {
      navigator.clipboard.writeText(text).then(() => {
        const oldText = btn.textContent;
        btn.textContent = '✓ Copiado';
        btn.style.background = '#2ed573';
        btn.style.color = '#fff';
        setTimeout(() => {
          btn.textContent = oldText;
          btn.style.background = '';
          btn.style.color = '';
        }, 1500);
      });
    }

    // Llenar tarjeta de prueba
    const btnFillTest = document.getElementById('btn-fill-test-card');
    if (btnFillTest) {
      btnFillTest.addEventListener('click', () => {
        document.getElementById('card-number').value = '4509 8832 9104 2288';
        document.getElementById('card-expiry').value = '09/28';
        document.getElementById('card-cvv').value = '842';
      });
    }

    const loader = document.getElementById('mp-loader');
    const loaderTitle = document.getElementById('loader-title');

    // Procesar pago vía Backend API
    async function procesarPagoBackend(metodoPago, comprobanteCliente = '', cardData = null) {
      loader.style.display = 'flex';
      loaderTitle.textContent = 'Procesando pago con Mercado Pago...';

      try {
        const payload = {
          id_cita: idCita,
          metodo_pago: metodoPago,
          monto_sena: montoSena,
          id_cuenta: idCuenta,
          comprobante_cliente: comprobanteCliente,
          card_data: cardData
        };

        const res = await fetch('api/pago.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (data.success && data.data && data.data.redirect_url) {
          window.location.href = data.data.redirect_url;
        } else {
          loader.style.display = 'none';
          if (window.showArcanaModal) {
            showArcanaModal({ title: 'Aviso de Pago', message: data.message || 'Error al procesar el pago con Mercado Pago.', type: 'warning' });
          } else {
            console.error(data.message);
          }
        }
      } catch (err) {
        console.error(err);
        loader.style.display = 'none';
        if (window.showArcanaModal) {
          showArcanaModal({ title: 'Error de Conexión', message: 'No se pudo conectar con el servidor de pagos. Intente nuevamente.', type: 'error' });
        }
      }
    }

    // Submit Tarjeta API
    const formCardApi = document.getElementById('form-card-api');
    if (formCardApi) {
      formCardApi.addEventListener('submit', (e) => {
        e.preventDefault();
        const cardNumber = document.getElementById('card-number').value.replace(/\s+/g, '');
        const cardHolder = document.getElementById('card-holder').value.trim();
        const cardExpiry = document.getElementById('card-expiry').value.trim();
        const cardCvv = document.getElementById('card-cvv').value.trim();

        procesarPagoBackend('mercadopago', '', {
          cardNumber,
          cardHolder,
          cardExpiry,
          cardCvv
        });
      });
    }

    // Submit Fallback MP Pro
    const btnFallbackMp = document.getElementById('btn-fallback-mp-pro');
    if (btnFallbackMp) {
      btnFallbackMp.addEventListener('click', () => {
        procesarPagoBackend('mercadopago', 'Checkout Pro MP');
      });
    }

    // Submit Transferencia
    const btnSubmitTransf = document.getElementById('btn-submit-transferencia');
    if (btnSubmitTransf) {
      btnSubmitTransf.addEventListener('click', () => {
        const comp = document.getElementById('nro-comprobante-tr')?.value.trim() || '';
        procesarPagoBackend('transferencia', comp);
      });
    }

    // Submit Efectivo
    const btnSubmitEfectivo = document.getElementById('btn-submit-efectivo');
    if (btnSubmitEfectivo) {
      btnSubmitEfectivo.addEventListener('click', () => {
        procesarPagoBackend('efectivo', 'Pago en Salón');
      });
    }
  </script>
</body>
</html>
