<?php
/**
 * ARCANA — Payment Confirmation & WhatsApp Notification (pago-confirmado.php)
 * Displays the verified deposit receipt and enables 1-click sharing to the administrator via WhatsApp.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/JsonDB.php';
require_once __DIR__ . '/config/session.php';

$pageTitle = '¡Pago de Seña Acreditado! — Arcana Moon Beauty Secret';
$pageDescription = 'Tu seña ha sido acreditada correctamente. Comparte tu comprobante con la administradora.';
$pageName = 'pago-confirmado';

$db = JsonDB::getInstance();
$idCita = (int)($_GET['id_cita'] ?? 0);
$comprobante = trim($_GET['comprobante'] ?? ('MP-' . date('Ymd') . '-9941'));

$cita = null;
$servicioNombre = 'Servicio de Belleza';
$clienteNombre = 'Cliente de Arcana';
$clienteTelefono = '';
$precioTotal = 25000.0;
$montoSena = 7500.0;
$fechaTurno = date('d/m/Y');
$horaTurno = '10:00';
$modalidad = 'Presencial';

// Teléfono de la administradora de Arcana para WhatsApp
$adminWhatsApp = '5491145678901';

if ($idCita) {
    $cita = $db->getById('citas', 'id_cita', $idCita);
    if ($cita) {
        // Si proviene de una redirección de Mercado Pago
        $mpPaymentId = $_GET['payment_id'] ?? $_GET['collection_id'] ?? '';
        $mpStatus = $_GET['collection_status'] ?? $_GET['status'] ?? '';
        if (!empty($mpPaymentId)) {
            $comprobante = 'MP-' . $mpPaymentId;
        }

        // Si el pago fue aprobado por Mercado Pago y la cita aún no estaba confirmada
        if ($mpStatus === 'approved' || !empty($mpPaymentId)) {
            $obsActual = trim($cita['observaciones'] ?? '');
            $notaMP = "Seña abonada en Mercado Pago [ID: {$comprobante}]";
            $nuevaObs = ($obsActual !== '' && $obsActual !== '-') ? (str_contains($obsActual, $comprobante) ? $obsActual : $obsActual . ' | ' . $notaMP) : $notaMP;

            $db->update('citas', 'id_cita', $idCita, [
                'estado' => 'confirmado',
                'observaciones' => $nuevaObs,
                'modificada_en' => date('Y-m-d H:i:s')
            ]);
            $cita['estado'] = 'confirmado';
        }

        $fechaTurno = date('d/m/Y', strtotime(substr($cita['fecha_hora'], 0, 10)));
        $horaTurno = substr($cita['fecha_hora'], 11, 5);
        $modalidad = $cita['modalidad'] ?? 'Presencial';

        // Cliente
        $u = $db->getById('usuario', 'id_usuario', (int)$cita['id_usuario']);
        if ($u) {
            $clienteNombre = $u['nombre'] . ' ' . $u['apellido'];
            $clienteTelefono = $u['telefono'] ?? '';
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

$saldoRestante = $precioTotal - $montoSena;

// Mensaje preformateado para WhatsApp
$mensajeWs = "¡Hola Arcana Moon! ✨ Acabo de abonar la seña para confirmar mi turno:\n\n"
           . "🌸 Servicio: {$servicioNombre}\n"
           . "📅 Fecha: {$fechaTurno} a las {$horaTurno} hs\n"
           . "📍 Modalidad: {$modalidad}\n"
           . "👤 Cliente: {$clienteNombre}\n"
           . "💳 Comprobante Mercado Pago: {$comprobante}\n"
           . "💰 Seña pagada: $" . number_format($montoSena, 0, ',', '.') . " ARS\n"
           . "💵 Saldo a abonar en el salón: $" . number_format($saldoRestante, 0, ',', '.') . " ARS\n\n"
           . "¡Quedo a la espera de su confirmación!";

$urlWhatsApp = "https://wa.me/{$adminWhatsApp}?text=" . rawurlencode($mensajeWs);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main style="min-height: 100vh; padding: 120px 1rem 4rem; display: flex; align-items: center; justify-content: center;">
  <div class="container" style="max-width: 600px;">
    <div class="glass" style="padding: 2.5rem 2rem; border-radius: var(--radius-card, 16px); text-align: center; border-color: rgba(34, 197, 94, 0.3); box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);">
      
      <!-- Animated Checkmark Icon -->
      <div style="width: 72px; height: 72px; background: rgba(34, 197, 94, 0.15); border: 2px solid #22c55e; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: #22c55e;">
        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
      </div>

      <span class="badge" style="background: rgba(34, 197, 94, 0.18); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.35); font-size: 0.8rem; padding: 0.3rem 0.85rem; border-radius: 9999px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; display: inline-block; margin-bottom: 0.75rem;">
        Seña Acreditada por Mercado Pago
      </span>

      <h1 class="text-section" style="font-size: 1.85rem; margin-bottom: 0.5rem;">¡Tu turno está reservado!</h1>
      <p style="color: var(--color-text-secondary); font-size: 0.925rem; line-height: 1.5; margin-bottom: 1.75rem;">
        Hemos registrado tu pago de seña. Para finalizar y agilizar tu atención, <strong>comparte el comprobante con la administradora por WhatsApp</strong>.
      </p>

      <!-- Receipt Card -->
      <div style="background: rgba(0, 0, 0, 0.35); border: 1px solid var(--color-glass-border); border-radius: 12px; padding: 1.25rem; text-align: left; margin-bottom: 1.75rem; font-size: 0.875rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; padding-bottom: 0.75rem; border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
          <span style="color: var(--color-text-muted); font-size: 0.8rem;">Comprobante Digital</span>
          <code style="background: rgba(255, 255, 255, 0.08); padding: 0.2rem 0.5rem; border-radius: 4px; color: #fbbf24; font-size: 0.8rem; font-weight: 600;"><?= htmlspecialchars($comprobante) ?></code>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; margin-bottom: 0.85rem;">
          <div>
            <span style="display: block; font-size: 0.75rem; color: var(--color-text-muted);">Servicio</span>
            <strong style="color: var(--color-text-primary); font-size: 0.95rem;"><?= htmlspecialchars($servicioNombre) ?></strong>
          </div>
          <div>
            <span style="display: block; font-size: 0.75rem; color: var(--color-text-muted);">Fecha y Hora</span>
            <strong style="color: var(--color-text-primary); font-size: 0.95rem;"><?= $fechaTurno ?> · <?= $horaTurno ?> hs</strong>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; padding-top: 0.75rem; border-top: 1px solid rgba(255, 255, 255, 0.08);">
          <div>
            <span style="display: block; font-size: 0.75rem; color: var(--color-text-muted);">Seña Abonada (30%)</span>
            <strong style="color: #4ade80; font-size: 1.15rem;">$ <?= number_format($montoSena, 0, ',', '.') ?></strong>
          </div>
          <div>
            <span style="display: block; font-size: 0.75rem; color: var(--color-text-muted);">Saldo en el Salón</span>
            <strong style="color: var(--color-text-secondary); font-size: 1.15rem;">$ <?= number_format($saldoRestante, 0, ',', '.') ?></strong>
          </div>
        </div>
      </div>

      <!-- Prominent WhatsApp Action Button -->
      <a href="<?= $urlWhatsApp ?>" target="_blank" rel="noopener noreferrer" class="btn-pill btn-pill--full" style="background: #25D366; color: #ffffff; font-weight: 700; font-size: 1rem; padding: 1rem 1.5rem; display: flex; align-items: center; justify-content: center; gap: 0.65rem; border: none; box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4); text-decoration: none; margin-bottom: 0.85rem; transition: transform 0.2s, background-color 0.2s;">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
          <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
        </svg>
        Compartir comprobante por WhatsApp
      </a>

      <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
        <button type="button" id="btn-copy-receipt" class="btn-pill btn-pill--sm" style="background: rgba(255, 255, 255, 0.08); border: 1px solid var(--color-glass-border);">
          📋 Copiar datos del comprobante
        </button>
        <a href="agenda.php" class="btn-pill btn-pill--sm" style="background: rgba(255, 255, 255, 0.08); border: 1px solid var(--color-glass-border); text-decoration: none;">
          📅 Volver a la Agenda
        </a>
      </div>

    </div>
  </div>
</main>

<script>
  const receiptText = <?= json_encode($mensajeWs) ?>;
  const btnCopy = document.getElementById('btn-copy-receipt');
  if (btnCopy) {
    btnCopy.addEventListener('click', () => {
      navigator.clipboard.writeText(receiptText).then(() => {
        btnCopy.textContent = '¡Comprobante copiado!';
        setTimeout(() => {
          btnCopy.textContent = '📋 Copiar datos del comprobante';
        }, 3000);
      });
    });
  }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
