<?php
/**
 * ARCANA — Restablecer Contraseña (restablecer-contrasena.php)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/JsonDB.php';
require_once __DIR__ . '/config/session.php';

// Si accede con token o para restablecer contraseña, limpiar cualquier sesión previa
if (isLoggedIn()) {
    logoutUser();
}

$pageTitle = 'Restablecer Contraseña — Arcana Moon Beauty Secret';
$pageDescription = 'Crea una nueva contraseña segura para tu cuenta.';
$pageName = 'restablecer-contrasena';

$db = JsonDB::getInstance();

$tokenParam = trim($_GET['token'] ?? '');
$emailParam = trim($_GET['email'] ?? '');
$codeParam = trim($_GET['code'] ?? '');

$errorMsg = '';
$success = false;

// Procesamiento POST tradicional fallback
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $token = trim($_POST['token'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (empty($password)) {
        $errorMsg = 'Por favor ingresa una nueva contraseña.';
    } elseif (strlen($password) < 6) {
        $errorMsg = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $passwordConfirm) {
        $errorMsg = 'Las contraseñas no coinciden.';
    } else {
        $now = date('Y-m-d H:i:s');
        $tokens = $db->find('reset_tokens', function($t) use ($email, $code, $token, $now) {
            if ((int)($t['utilizado'] ?? 0) === 1) return false;
            if (($t['expira_en'] ?? '') < $now) return false;

            if (!empty($token) && ($t['token'] ?? '') === $token) return true;
            if (!empty($email) && !empty($code) && mb_strtolower($t['email'] ?? '') === mb_strtolower($email) && ($t['code'] ?? '') === $code) return true;
            return false;
        });

        if (empty($tokens)) {
            $errorMsg = 'El código o enlace de recuperación es inválido o ha expirado. Por favor solicita uno nuevo.';
        } else {
            $matchedToken = $tokens[0];
            $targetEmail = $matchedToken['email'];

            $usuarios = $db->find('usuario', function($u) use ($targetEmail) {
                return mb_strtolower($u['email']) === mb_strtolower($targetEmail);
            });

            if (!empty($usuarios)) {
                $user = $usuarios[0];
                $db->update('usuario', 'id_usuario', $user['id_usuario'], [
                    'contrasena' => password_hash($password, PASSWORD_BCRYPT)
                ]);

                if (isset($matchedToken['id'])) {
                    $db->update('reset_tokens', 'id', $matchedToken['id'], [
                        'utilizado' => 1
                    ]);
                }

                header('Location: login.php?recuperado=1');
                exit;
            } else {
                $errorMsg = 'No se encontró el usuario asociado.';
            }
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
          <path d="m18 2 4 4-10 10H8v-4z"></path>
          <path d="m15 5 3 3"></path>
          <circle cx="6" cy="18" r="4"></circle>
        </svg>
      </div>
      <h1 class="text-section" style="font-size: 1.75rem; margin-bottom: 0.5rem;">Restablecer Contraseña</h1>
      <p style="font-size: 0.88rem; color: var(--color-text-muted); line-height: 1.4;">
        Crea tu nueva contraseña de acceso.
      </p>
    </div>

    <div id="alert-box">
      <?php if (!empty($errorMsg)): ?>
        <div style="background: rgba(255, 107, 107, 0.15); border: 1px solid #ff6b6b; color: #ff6b6b; padding: 0.85rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.875rem; text-align: center;">
          <?= htmlspecialchars($errorMsg) ?>
        </div>
      <?php endif; ?>
    </div>

    <form id="reset-form" method="POST" action="restablecer-contrasena.php">
      <input type="hidden" id="token" name="token" value="<?= htmlspecialchars($tokenParam) ?>">

      <div class="form-group">
        <label class="form-label" for="email">Correo Registrado</label>
        <input type="email" id="email" name="email" class="form-input" required placeholder="correo@ejemplo.com" value="<?= htmlspecialchars($emailParam) ?>" <?= !empty($emailParam) ? 'readonly style="opacity: 0.85;"' : '' ?>>
      </div>

      <?php if (empty($tokenParam)): ?>
        <div class="form-group">
          <label class="form-label" for="code">Código de Verificación (6 dígitos)</label>
          <input type="text" id="code" name="code" class="form-input" placeholder="Ej: 123456" maxlength="6" value="<?= htmlspecialchars($codeParam) ?>" required style="letter-spacing: 4px; font-family: monospace; font-size: 1.1rem; text-align: center;">
        </div>
      <?php else: ?>
        <input type="hidden" id="code" name="code" value="">
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label" for="password">Nueva Contraseña</label>
        <input type="password" id="password" name="password" class="form-input" placeholder="Mínimo 6 caracteres" minlength="6" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="password_confirm">Confirmar Nueva Contraseña</label>
        <input type="password" id="password_confirm" name="password_confirm" class="form-input" placeholder="Repite tu nueva contraseña" minlength="6" required>
      </div>

      <button type="submit" id="btn-save" class="btn-pill btn-pill--primary btn-pill--full" style="margin-top: 1.25rem;">
        <span id="btn-text">Actualizar Contraseña</span>
      </button>
    </form>

    <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--color-glass-border); font-size: 0.85rem; color: var(--color-text-muted);">
      ¿No recibiste el código? <a href="recuperar-contrasena.php" style="color: var(--color-accent); font-weight: 500; text-decoration: none;">Volver a solicitar</a>
    </div>

  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('reset-form');
  const alertBox = document.getElementById('alert-box');
  const btnSave = document.getElementById('btn-save');
  const btnText = document.getElementById('btn-text');

  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const email = document.getElementById('email').value.trim();
    const token = document.getElementById('token').value.trim();
    const code = document.getElementById('code')?.value.trim() || '';
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;

    if (!password) {
      showAlert('Por favor escribe tu nueva contraseña.', 'error');
      return;
    }

    if (password.length < 6) {
      showAlert('La contraseña debe tener al menos 6 caracteres.', 'error');
      return;
    }

    if (password !== passwordConfirm) {
      showAlert('Las contraseñas no coinciden.', 'error');
      return;
    }

    btnSave.disabled = true;
    btnText.textContent = 'Actualizando...';
    alertBox.innerHTML = '';

    try {
      const res = await fetch('api/auth.php?action=reset_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, token, code, password, password_confirm: passwordConfirm })
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        throw new Error(data.message || 'Error al restablecer la contraseña.');
      }

      showAlert('¡Contraseña actualizada con éxito! Redirigiendo...', 'success');
      setTimeout(() => {
        window.location.href = data.data.redirect || 'login.php?recuperado=1';
      }, 1200);

    } catch (err) {
      showAlert(err.message || 'Error al conectar con el servidor.', 'error');
      btnSave.disabled = false;
      btnText.textContent = 'Actualizar Contraseña';
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
