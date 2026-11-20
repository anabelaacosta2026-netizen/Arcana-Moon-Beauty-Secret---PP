<?php
/**
 * ARCANA — Recuperación de Contraseña (recuperar-contrasena.php)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/JsonDB.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/mail.php';

// Si entra a recuperar contraseña, limpiar cualquier sesión previa para evitar conflictos
if (isLoggedIn()) {
    logoutUser();
}

$pageTitle = 'Recuperar Contraseña — Arcana Moon Beauty Secret';
$pageDescription = 'Recupera el acceso a tu cuenta mediante un código o enlace enviado a tu correo.';
$pageName = 'recuperar-contrasena';

$db = JsonDB::getInstance();
$successData = null;
$errorMsg = '';
$emailVal = '';

// Procesamiento por POST tradicional si JavaScript está deshabilitado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $emailVal = trim($_POST['email']);
    if (empty($emailVal)) {
        $errorMsg = 'Por favor ingresa tu correo electrónico.';
    } elseif (!filter_var($emailVal, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'El formato de correo no es válido.';
    } else {
        $usuarios = $db->find('usuario', function($u) use ($emailVal) {
            return mb_strtolower($u['email']) === mb_strtolower($emailVal);
        });

        if (empty($usuarios)) {
            $errorMsg = 'No encontramos ninguna cuenta registrada con este correo electrónico.';
        } else {
            $user = $usuarios[0];
            $token = bin2hex(random_bytes(24));
            $code = sprintf('%06d', mt_rand(100000, 999999));
            $expiraEn = date('Y-m-d H:i:s', time() + 3600);

            $db->insert('reset_tokens', [
                'email' => $emailVal,
                'id_usuario' => (int)$user['id_usuario'],
                'token' => $token,
                'code' => $code,
                'expira_en' => $expiraEn,
                'creado_en' => date('Y-m-d H:i:s'),
                'utilizado' => 0
            ], 'id');

            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            $baseUrl = rtrim($protocol . $host . $scriptDir, '/');
            $resetLink = $baseUrl . '/restablecer-contrasena.php?token=' . urlencode($token) . '&email=' . urlencode($emailVal);

            // Envío con plantilla HTML y SMTP
            $mailRes = sendPasswordResetEmail($user, $token, $code, $resetLink);

            $successData = [
                'email' => $emailVal,
                'token' => $token,
                'reset_link' => $resetLink
            ];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 120px 1rem 3rem;">
  <div class="glass" style="max-width: 480px; width: 100%; padding: 2.5rem; border-radius: var(--radius-card, 16px);">
    
    <div style="text-align: center; margin-bottom: 1.5rem;">
      <div style="width: 56px; height: 56px; margin: 0 auto 1rem; border-radius: 50%; background: rgba(196, 84, 126, 0.15); display: flex; align-items: center; justify-content: center; color: var(--color-accent);">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          <circle cx="12" cy="16" r="1"></circle>
        </svg>
      </div>
      <h1 class="text-section" style="font-size: 1.75rem; margin-bottom: 0.5rem;">Recuperar Contraseña</h1>
      <p style="font-size: 0.88rem; color: var(--color-text-muted); line-height: 1.4;">
        Ingresa tu correo electrónico y te enviaremos las instrucciones de recuperación.
      </p>
    </div>

    <div id="alert-container">
      <?php if (!empty($errorMsg)): ?>
        <div style="background: rgba(255, 107, 107, 0.15); border: 1px solid #ff6b6b; color: #ff6b6b; padding: 0.85rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.875rem; text-align: center;">
          <?= htmlspecialchars($errorMsg) ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- PASO 1: Ingreso de correo -->
    <div id="step-request" style="<?= $successData ? 'display: none;' : '' ?>">
      <form id="forgot-form" method="POST" action="recuperar-contrasena.php">
        <div class="form-group">
          <label class="form-label" for="email">Correo Electrónico Registrado</label>
          <input type="email" id="email" name="email" class="form-input" placeholder="tu-correo@gmail.com" required value="<?= htmlspecialchars($emailVal) ?>">
        </div>

        <button type="submit" id="btn-submit" class="btn-pill btn-pill--primary btn-pill--full" style="margin-top: 1.25rem;">
          <span id="btn-text">Enviar Correo de Recuperación</span>
        </button>
      </form>
    </div>

    <!-- PASO 2: Correo enviado -->
    <div id="step-sent" style="<?= $successData ? '' : 'display: none;' ?>">
      <div style="background: rgba(46, 213, 115, 0.12); border: 1px solid rgba(46, 213, 115, 0.35); color: #2ed573; padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; text-align: center; font-size: 0.92rem; line-height: 1.6;">
        <div style="font-size: 28px; margin-bottom: 6px;">✉</div>
        <strong style="font-size: 1.05rem;">¡Correo Enviado con Éxito!</strong><br>
        Hemos enviado un enlace de recuperación y un código PIN a:<br>
        <span id="sent-email-display" style="font-weight: 600; color: #f6e05e; display: inline-block; margin-top: 4px; word-break: break-all;"><?= htmlspecialchars($successData['email'] ?? '') ?></span>
      </div>

      <div style="background: rgba(246, 224, 94, 0.08); border: 1px dashed rgba(246, 224, 94, 0.3); border-radius: 8px; padding: 0.85rem; font-size: 0.82rem; color: #f6ad55; margin-bottom: 1.5rem; text-align: left;">
        💡 <strong>Revisa tu bandeja de entrada o Spam:</strong> Haz clic en el botón dentro del correo para restablecer tu contraseña directamente.
      </div>

      <!-- Acciones de paso 2 -->
      <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        <a id="btn-enter-code" href="restablecer-contrasena.php<?= !empty($successData['email']) ? '?email=' . urlencode($successData['email']) : '' ?>" class="btn-pill btn-pill--primary btn-pill--full" style="text-align: center; text-decoration: none;">
          Ingresar Código de 6 Dígitos →
        </a>
        <button type="button" onclick="location.reload()" class="btn-pill btn-pill--outline btn-pill--full" style="font-size: 0.85rem;">
          Reintentar con otro correo
        </button>
      </div>
    </div>

    <!-- Enlaces inferiores -->
    <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--color-glass-border); font-size: 0.85rem; color: var(--color-text-muted);">
      ¿Recordaste tu contraseña? <a href="login.php" style="color: var(--color-accent); font-weight: 500; text-decoration: none;">Iniciar Sesión</a>
    </div>

  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('forgot-form');
  const alertBox = document.getElementById('alert-container');
  const stepRequest = document.getElementById('step-request');
  const stepSent = document.getElementById('step-sent');
  const sentEmailDisplay = document.getElementById('sent-email-display');
  const btnEnterCode = document.getElementById('btn-enter-code');
  const btnSubmit = document.getElementById('btn-submit');
  const btnText = document.getElementById('btn-text');

  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value.trim();

    if (!email) {
      showAlert('Por favor ingresa tu correo electrónico.', 'error');
      return;
    }

    btnSubmit.disabled = true;
    btnText.textContent = 'Enviando correo...';
    alertBox.innerHTML = '';

    try {
      const res = await fetch('api/auth.php?action=forgot_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        throw new Error(data.message || 'Error al procesar la solicitud.');
      }

      // Éxito: mostrar paso 2
      if (sentEmailDisplay) sentEmailDisplay.textContent = data.data.email || email;
      if (btnEnterCode) {
        btnEnterCode.href = `restablecer-contrasena.php?email=${encodeURIComponent(data.data.email || email)}`;
      }

      stepRequest.style.display = 'none';
      stepSent.style.display = 'block';

    } catch (err) {
      showAlert(err.message || 'Error de conexión. Intenta nuevamente.', 'error');
      btnSubmit.disabled = false;
      btnText.textContent = 'Enviar Correo de Recuperación';
    }
  });

  function showAlert(msg, type) {
    const isErr = type === 'error';
    alertBox.innerHTML = `
      <div style="background: ${isErr ? 'rgba(255, 107, 107, 0.15)' : 'rgba(46, 213, 115, 0.15)'}; 
                  border: 1px solid ${isErr ? '#ff6b6b' : '#2ed573'}; 
                  color: ${isErr ? '#ff6b6b' : '#2ed573'}; 
                  padding: 0.85rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.875rem; text-align: center;">
        ${msg}
      </div>
    `;
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
