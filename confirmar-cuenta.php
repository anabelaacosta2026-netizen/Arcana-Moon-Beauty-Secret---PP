<?php
/**
 * ARCANA — Confirmación de Cuenta (confirmar-cuenta.php)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/JsonDB.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/mail.php';

$pageTitle = 'Confirmación de Cuenta — Arcana Moon Beauty Secret';
$pageDescription = 'Verifica y activa tu cuenta en Arcana Moon Beauty Secret.';
$pageName = 'confirmar-cuenta';

$db = JsonDB::getInstance();

$token = trim($_GET['token'] ?? '');
$email = trim($_GET['email'] ?? '');

$status = 'pending'; // 'success', 'expired', 'invalid', 'resend_ok', 'pending'
$message = '';
$userName = '';

if (!empty($token)) {
    $now = date('Y-m-d H:i:s');
    $tokens = $db->find('verification_tokens', function($t) use ($email, $token) {
        if (($t['token'] ?? '') !== $token) return false;
        if (!empty($email) && mb_strtolower($t['email'] ?? '') !== mb_strtolower($email)) return false;
        return true;
    });

    if (empty($tokens)) {
        $status = 'invalid';
        $message = 'El enlace de confirmación es inválido o no existe.';
    } else {
        $matchedToken = $tokens[0];
        $isUsed = (int)($matchedToken['utilizado'] ?? 0) === 1;
        $isExpired = ($matchedToken['expira_en'] ?? '') < $now;

        $targetEmail = $matchedToken['email'];
        $usuarios = $db->find('usuario', function($u) use ($targetEmail) {
            return mb_strtolower($u['email']) === mb_strtolower($targetEmail);
        });

        if (empty($usuarios)) {
            $status = 'invalid';
            $message = 'Usuario no encontrado.';
        } else {
            $user = $usuarios[0];
            $userName = $user['nombre'] ?? '';

            if ((int)($user['activo'] ?? 0) === 1 && (int)($user['email_verificado'] ?? 0) === 1) {
                // Limpiar cualquier sesión previa e iniciar con la cuenta verificada
                $_SESSION = [];
                $idRol = (int)($user['id_rol'] ?? 3);
                $rol = $db->getById('roles', 'id_rol', $idRol);
                $user['rol'] = $rol ? $rol['nombre_rol'] : ($user['rol'] ?? 'usuario');
                $user['id'] = (int)$user['id_usuario'];
                loginUser($user);

                $status = 'success';
                $message = 'Tu cuenta ya se encuentra verificada y activa.';
            } elseif ($isExpired) {
                logoutUser();
                $status = 'expired';
                $message = 'El enlace de confirmación ha expirado. Puedes solicitar uno nuevo a continuación.';
            } else {
                // Activar usuario
                $db->update('usuario', 'id_usuario', $user['id_usuario'], [
                    'activo' => 1,
                    'email_verificado' => 1
                ]);

                // Marcar token utilizado
                $tokenId = $matchedToken['id'] ?? null;
                if ($tokenId) {
                    $db->update('verification_tokens', 'id', $tokenId, [
                        'utilizado' => 1
                    ]);
                }

                // Limpiar cualquier sesión previa e iniciar con la cuenta verificada
                $_SESSION = [];
                $user['activo'] = 1;
                $user['email_verificado'] = 1;
                $idRol = (int)($user['id_rol'] ?? 3);
                $rol = $db->getById('roles', 'id_rol', $idRol);
                $user['rol'] = $rol ? $rol['nombre_rol'] : ($user['rol'] ?? 'usuario');
                $user['id'] = (int)$user['id_usuario'];
                loginUser($user);

                $status = 'success';
                $message = '¡Tu cuenta ha sido confirmada y activada con éxito!';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 120px 1rem 3rem;">
  <div class="glass" style="max-width: 480px; width: 100%; padding: 2.5rem; text-align: center; position: relative;">
    
    <?php if ($status === 'success'): ?>
      <!-- Éxito -->
      <div style="width: 72px; height: 72px; margin: 0 auto 1.5rem; background: rgba(46, 213, 115, 0.15); border: 2px solid #2ed573; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #2ed573; font-size: 32px;">
        ✓
      </div>
      <h1 class="text-section" style="margin-bottom: 0.75rem; font-size: 1.75rem;">¡Cuenta Confirmada!</h1>
      <p style="color: var(--color-text-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 2rem;">
        <?= !empty($userName) ? 'Hola <strong>' . htmlspecialchars($userName) . '</strong>, ' : '' ?><?= htmlspecialchars($message) ?><br>
        Ya puedes iniciar sesión para agendar tus citas y tratamientos en Arcana.
      </p>
      <div style="display: flex; gap: 0.75rem; flex-direction: column;">
        <a href="index.php" class="btn-pill btn-pill--primary btn-pill--full" style="display: block; text-decoration: none;">
          Ir al Inicio y Agendar Turno
        </a>
        <a href="login.php" style="color: var(--color-accent); font-size: 0.85rem; text-decoration: none; margin-top: 0.25rem;">
          O iniciar sesión con otra cuenta
        </a>
      </div>

    <?php elseif ($status === 'expired' || $status === 'invalid'): ?>
      <!-- Error o Expirado -->
      <div style="width: 72px; height: 72px; margin: 0 auto 1.5rem; background: rgba(255, 107, 107, 0.15); border: 2px solid #ff6b6b; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #ff6b6b; font-size: 32px;">
        ✕
      </div>
      <h1 class="text-section" style="margin-bottom: 0.75rem; font-size: 1.75rem; color: #ff6b6b;">Enlace no válido</h1>
      <p style="color: var(--color-text-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;">
        <?= htmlspecialchars($message) ?>
      </p>

      <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--color-glass-border); border-radius: var(--radius-md, 12px); padding: 1.5rem; margin-bottom: 1.5rem; text-align: left;">
        <h3 style="font-size: 0.95rem; margin-top: 0; margin-bottom: 0.5rem; color: var(--color-text);">¿Deseas recibir un nuevo enlace?</h3>
        <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 1rem;">Ingresa tu correo para reenviarte el email de activación:</p>
        <form id="resend-verify-form">
          <div class="form-group" style="margin-bottom: 0.75rem;">
            <input type="email" id="resend-email" class="form-input" placeholder="tu@email.com" required value="<?= htmlspecialchars($email) ?>">
          </div>
          <button type="submit" class="btn-pill btn-pill--secondary btn-pill--full" id="btn-resend">Reenviar Correo de Activación</button>
        </form>
        <div id="resend-feedback" style="margin-top: 0.75rem; font-size: 0.85rem; text-align: center; display: none;"></div>
      </div>

      <a href="login.php" style="color: var(--color-accent); font-size: 0.9rem; text-decoration: none;">
        ← Volver al inicio de sesión
      </a>

    <?php else: ?>
      <!-- Formulario para solicitar reenvío manual -->
      <div style="width: 72px; height: 72px; margin: 0 auto 1.5rem; background: rgba(196, 84, 126, 0.15); border: 2px solid var(--color-accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--color-accent); font-size: 32px;">
        ✉
      </div>
      <h1 class="text-section" style="margin-bottom: 0.75rem; font-size: 1.75rem;">Confirmar tu Cuenta</h1>
      <p style="color: var(--color-text-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;">
        Si te registraste pero no recibiste tu correo de activación, ingresa tu email para reenviarlo:
      </p>

      <form id="resend-verify-form" style="text-align: left;">
        <div class="form-group" style="margin-bottom: 1rem;">
          <label class="form-label" for="resend-email">Email registrado</label>
          <input type="email" id="resend-email" class="form-input" placeholder="tu@email.com" required value="<?= htmlspecialchars($email) ?>">
        </div>
        <button type="submit" class="btn-pill btn-pill--primary btn-pill--full" id="btn-resend">Reenviar Enlace de Confirmación</button>
      </form>
      <div id="resend-feedback" style="margin-top: 1rem; font-size: 0.85rem; text-align: center; display: none;"></div>

      <p style="text-align: center; margin-top: 1.5rem; font-size: 0.85rem; color: var(--color-text-muted);">
        ¿Ya la confirmaste? <a href="login.php" style="color: var(--color-accent); font-weight: 500;">Inicia Sesión</a>
      </p>
    <?php endif; ?>

  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('resend-verify-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const emailInput = document.getElementById('resend-email');
    const btn = document.getElementById('btn-resend');
    const feedback = document.getElementById('resend-feedback');
    const email = emailInput ? emailInput.value.trim() : '';

    if (!email) return;

    btn.disabled = true;
    const prevText = btn.textContent;
    btn.textContent = 'Enviando...';
    feedback.style.display = 'none';

    try {
      const res = await fetch('api/auth.php?action=resend_verification', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
      });
      const data = await res.json();

      feedback.style.display = 'block';
      if (data.success) {
        feedback.style.color = '#2ed573';
        feedback.textContent = '✓ ' + (data.message || 'Correo reenviado con éxito. Revisa tu casilla o spam.');
      } else {
        feedback.style.color = '#ff6b6b';
        feedback.textContent = '✕ ' + (data.message || 'Error al reenviar.');
      }
    } catch (err) {
      feedback.style.display = 'block';
      feedback.style.color = '#ff6b6b';
      feedback.textContent = '✕ Error de conexión al servidor.';
    } finally {
      btn.disabled = false;
      btn.textContent = prevText;
    }
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
