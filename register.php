<?php
/**
 * ARCANA — Register Page (register.php)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/JsonDB.php';
require_once __DIR__ . '/config/session.php';

// If already logged in, redirect
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Registro — Arcana Moon Beauty Secret';
$pageDescription = 'Crea tu cuenta en Arcana para agendar turnos y disfrutar de beneficios exclusivos.';
$pageName = 'register';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 120px 1rem 3rem;">
  <div class="glass" style="max-width: 500px; width: 100%; padding: 2.5rem;">
    <h1 class="text-section" style="text-align: center; margin-bottom: 1.5rem;">Crear Cuenta</h1>
    
    <form id="register-form">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="register-nombre">Nombre</label>
          <input type="text" id="register-nombre" class="form-input" placeholder="Escribe tu nombre" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="register-apellido">Apellido</label>
          <input type="text" id="register-apellido" class="form-input" placeholder="Escribe tu apellido" required>
        </div>
      </div>
      
      <div class="form-group">
        <label class="form-label" for="register-telefono">Teléfono</label>
        <input type="tel" id="register-telefono" class="form-input" placeholder="Ej: 3794009000" required>
      </div>
      
      <div class="form-group">
        <label class="form-label" for="register-dni">DNI (opcional)</label>
        <input type="text" id="register-dni" class="form-input" placeholder="Escribe tu DNI">
      </div>
      
      <div class="form-group">
        <label class="form-label" for="register-email">Email</label>
        <input type="email" id="register-email" class="form-input" placeholder="tu@email.com" required>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="password">Contraseña</label>
          <input type="password" id="password" class="form-input" placeholder="Mínimo 6 caracteres" required minlength="6">
        </div>
        <div class="form-group">
          <label class="form-label" for="password2">Confirmar Contraseña</label>
          <input type="password" id="password2" class="form-input" placeholder="Repite tu contraseña" required minlength="6">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="condition">¿Posee alguna condición médica o alergia relevante?</label>
        <select id="condition" class="form-select">
          <option value="no">No</option>
          <option value="si">Sí</option>
        </select>
      </div>

      <div class="form-group" id="condition-detail" style="display: none;">
        <label class="form-label" for="condition-text">Especifique su condición o alergia</label>
        <input type="text" id="condition-text" class="form-input" placeholder="Ej: Diabetes, alergia a esmaltes con tolueno, embarazo...">
      </div>

      <button type="submit" class="btn-pill btn-pill--primary btn-pill--full" style="margin-top: 1.25rem;">Registrarme</button>
      
      <!-- Divisor Google -->
      <div style="display: flex; align-items: center; margin: 1.5rem 0 1.25rem; color: var(--color-text-muted); font-size: 0.8rem;">
        <div style="flex: 1; height: 1px; background: var(--color-glass-border);"></div>
        <span style="padding: 0 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem;">o regístrate con</span>
        <div style="flex: 1; height: 1px; background: var(--color-glass-border);"></div>
      </div>

      <?php $googleClientId = env('GOOGLE_CLIENT_ID', ''); ?>
      <!-- Botón estilizado Arcana & Google -->
      <div style="width: 100%; margin-bottom: 0.5rem;">
        <button type="button" class="btn-google" id="btn-google-register" onclick="handleGoogleButtonClick()">
          <svg class="google-icon" viewBox="0 0 48 48" aria-hidden="true">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.55 10.79l7.98-6.2z"/>
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
          </svg>
          <span>Registrarse con Google</span>
        </button>
      </div>

      <p style="text-align: center; margin-top: 1.25rem; font-size: 0.85rem; color: var(--color-text-muted);">
        ¿Ya tienes cuenta? <a href="login.php" style="color: var(--color-accent); font-weight: 500;">Inicia Sesión</a>
      </p>
    </form>
  </div>
</main>

<!-- Modal de Información / Demo Google OAuth (cuando GOOGLE_CLIENT_ID no está configurado) -->
<div id="google-modal" class="modal-overlay">
  <div class="modal-card" style="max-width: 460px; text-align: center;">
    <button type="button" class="modal-close" onclick="closeGoogleSetupModal()" aria-label="Cerrar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <div style="width: 56px; height: 56px; margin: 0 auto 1.25rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
      <svg width="28" height="28" viewBox="0 0 48 48">
        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.55 10.79l7.98-6.2z"/>
        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
      </svg>
    </div>
    <h3 style="font-family: var(--font-display, serif); font-size: 1.35rem; margin-bottom: 0.75rem; color: #fff;">Autenticación con Google</h3>
    <p style="font-size: 0.875rem; color: var(--color-text-muted); line-height: 1.6; margin-bottom: 1.5rem; text-align: left; background: rgba(255,255,255,0.03); padding: 1rem; border-radius: var(--radius-sm, 8px); border: 1px solid var(--color-glass-border);">
      Para activar el registro real con cuentas de Google, ingresa tu <strong style="color: var(--color-text);">GOOGLE_CLIENT_ID</strong> en el archivo <code style="color: var(--color-accent);">.env</code>.
    </p>
    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
      <button type="button" class="btn-pill btn-pill--primary btn-pill--full" onclick="loginWithGoogleDemo()">
        🚀 Registrarse como Usuario Google (Demo)
      </button>
      <button type="button" class="btn-pill btn-pill--outline btn-pill--full" onclick="closeGoogleSetupModal()">
        Cerrar
      </button>
    </div>
  </div>
</div>

<!-- SDK Oficial de Google Identity Services -->
<script src="https://accounts.google.com/gsi/client" async defer></script>

<script>
const googleClientId = <?= json_encode($googleClientId) ?>;

function handleGoogleButtonClick() {
  if (!googleClientId || googleClientId.trim() === '') {
    openGoogleSetupModal();
    return;
  }

  if (window.google && google.accounts && google.accounts.oauth2) {
    const tokenClient = google.accounts.oauth2.initTokenClient({
      client_id: googleClientId,
      scope: 'email profile openid',
      prompt: 'select_account',
      callback: async (tokenResponse) => {
        if (tokenResponse && tokenResponse.access_token) {
          try {
            let endpoint = 'api/auth.php?action=google_login';
            try {
              const u = new URL(endpoint, window.location.origin + window.location.pathname);
              u.username = '';
              u.password = '';
              endpoint = u.href;
            } catch (e) {}

            const res = await fetch(endpoint, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ access_token: tokenResponse.access_token })
            });
            const result = await res.json();
            if (result.success) {
              window.location.href = result.data?.redirect || 'index.php';
            } else {
              if (window.showArcanaModal) {
                showArcanaModal({ title: 'Error de Registro', message: result.message || 'Error al procesar registro con Google.', type: 'error' });
              } else {
                console.error(result.message);
              }
            }
          } catch (err) {
            console.error('Error Google OAuth:', err);
            if (window.showArcanaModal) {
              showArcanaModal({ title: 'Error de Conexión', message: 'Error de conexión al autenticar con Google.', type: 'error' });
            }
          }
        }
      }
    });
    tokenClient.requestAccessToken({ prompt: 'select_account' });
  } else {
    openGoogleSetupModal();
  }
}

function openGoogleSetupModal() {
  const modal = document.getElementById('google-modal');
  if (modal) modal.classList.add('modal-overlay--open');
}

function closeGoogleSetupModal() {
  const modal = document.getElementById('google-modal');
  if (modal) modal.classList.remove('modal-overlay--open');
}

async function loginWithGoogleDemo() {
  try {
    let endpoint = 'api/auth.php?action=google_login';
    try {
      const u = new URL(endpoint, window.location.origin + window.location.pathname);
      u.username = '';
      u.password = '';
      endpoint = u.href;
    } catch (e) {}

    const res = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        credential: 'demo_google_credential',
        email: 'usuario.google@arcanamoon.com',
        name: 'Cliente Google'
      })
    });
    const result = await res.json();
    if (result.success) {
      window.location.href = result.data?.redirect || 'index.php';
    } else {
      if (window.showArcanaModal) {
        showArcanaModal({ title: 'Aviso', message: result.message || 'Error al procesar registro con Google.', type: 'warning' });
      }
    }
  } catch (err) {
    console.error('Error Google Auth Demo:', err);
    if (window.showArcanaModal) {
      showArcanaModal({ title: 'Error de Servidor', message: 'Error al conectar con el servidor.', type: 'error' });
    }
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

