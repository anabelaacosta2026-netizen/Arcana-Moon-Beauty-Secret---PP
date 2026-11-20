<?php
/**
 * ARCANA — Session and Auth Helpers
 */

if (session_status() === PHP_SESSION_NONE) {
    // Set secure cookie parameters if applicable
    $lifetime = 60 * 60 * 24 * 7; // 7 days
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Check if a user is logged in
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user']) 
        && (!empty($_SESSION['user']['id']) || !empty($_SESSION['user']['id_usuario']));
}

/**
 * Check if the logged in user is a Super Administrator (highest priority)
 */
function isSuperAdmin(): bool {
    if (!isLoggedIn()) {
        return false;
    }
    $rol = strtolower(trim($_SESSION['user']['rol'] ?? ''));
    $idRol = (int)($_SESSION['user']['id_rol'] ?? 0);
    $email = strtolower(trim($_SESSION['user']['email'] ?? ''));
    $isSuper = !empty($_SESSION['user']['is_superadmin']);

    return ($idRol === 1 || $idRol === 4 || $isSuper || str_contains($rol, 'super') || $email === 'anabelaacosta2026@gmail.com');
}

/**
 * Check if the logged in user is an administrator (superadmin inherits all admin privileges)
 */
function isAdmin(): bool {
    if (!isLoggedIn()) {
        return false;
    }
    if (isSuperAdmin()) {
        return true;
    }
    $rol = strtolower(trim($_SESSION['user']['rol'] ?? ''));
    $idRol = (int)($_SESSION['user']['id_rol'] ?? 0);
    $email = strtolower(trim($_SESSION['user']['email'] ?? ''));

    return ($idRol === 2 || str_contains($rol, 'admin') || $email === 'fernandocabraldev@gmail.com');
}

/**
 * Get current logged in user array or null
 */
function getCurrentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Log in a user by saving their info into the session
 */
function loginUser(array $user): void {
    // Exclude password hash from session data
    unset($user['password_hash'], $user['password'], $user['contrasena']);
    
    $idRol = (int)($user['id_rol'] ?? 0);
    $rol = strtolower(trim($user['rol'] ?? ''));
    $email = strtolower(trim($user['email'] ?? ''));

    if ($idRol === 1 || $idRol === 4 || str_contains($rol, 'super') || $email === 'anabelaacosta2026@gmail.com') {
        $user['is_superadmin'] = 1;
        $user['rol'] = 'Super Admin';
        $user['id_rol'] = 1;
    } elseif ($idRol === 2 || str_contains($rol, 'admin') || $email === 'fernandocabraldev@gmail.com') {
        $user['is_superadmin'] = 0;
        $user['rol'] = 'Administrador';
        $user['id_rol'] = 2;
    } else {
        $user['is_superadmin'] = 0;
        $user['rol'] = 'Cliente';
        $user['id_rol'] = 3;
    }
    
    // Asegurar sesión activa y regenerar ID para prevenir Session Fixation
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    session_regenerate_id(true);
    
    $_SESSION['user'] = $user;
}

/**
 * Genera o recupera el token CSRF de la sesión actual
 */
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida un token CSRF con comparación segura en tiempo constante
 */
function validateCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate limiting para prevenir ataques de fuerza bruta
 */
function checkRateLimit(string $key, int $maxAttempts = 5, int $decaySeconds = 900): bool {
    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = ['attempts' => 0, 'first_attempt' => time()];
        return true;
    }
    $entry = &$_SESSION['rate_limits'][$key];
    if (time() - $entry['first_attempt'] > $decaySeconds) {
        $entry = ['attempts' => 0, 'first_attempt' => time()];
        return true;
    }
    return $entry['attempts'] < $maxAttempts;
}

/**
 * Incrementa el contador de intentos fallidos
 */
function recordRateLimitAttempt(string $key): void {
    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = ['attempts' => 0, 'first_attempt' => time()];
    }
    $_SESSION['rate_limits'][$key]['attempts']++;
}

/**
 * Resetea el contador de intentos tras una acción exitosa
 */
function resetRateLimit(string $key): void {
    unset($_SESSION['rate_limits'][$key]);
}

/**
 * Log out user and destroy session
 */
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    // Limpiar estado y cookies de Google GIS
    if (isset($_COOKIE['g_state'])) {
        setcookie('g_state', '', time() - 42000, '/');
    }
    session_destroy();
}

/**
 * Require authentication or redirect
 */
function requireAuth(string $redirectTo = 'login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

/**
 * Require admin privileges or redirect
 */
function requireAdmin(string $redirectTo = 'login.php'): void {
    if (!isAdmin()) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

/**
 * Require Super Administrator privileges or redirect
 */
function requireSuperAdmin(string $redirectTo = 'login.php'): void {
    if (!isSuperAdmin()) {
        if (!isLoggedIn()) {
            header('Location: ' . $redirectTo . '?redirect=superadmin.php');
        } else {
            header('Location: index.php?msg=' . urlencode('Acceso denegado. Se requieren permisos de Super Administrador.'));
        }
        exit;
    }
}

/**
 * Registrar un cambio realizado por un usuario/administrador en el Dashboard
 */
function registrarCambioDashboard(string $modulo, string $accion, string $detalle = ''): void {
    if (!class_exists('JsonDB')) {
        require_once __DIR__ . '/JsonDB.php';
    }
    try {
        $db = JsonDB::getInstance();
        $currentUser = getCurrentUser();
        $nombre = $currentUser ? trim(($currentUser['nombre'] ?? 'Admin') . ' ' . ($currentUser['apellido'] ?? '')) : 'Admin / Sistema';
        $email = $currentUser['email'] ?? '';
        $rol = $currentUser['rol'] ?? (isSuperAdmin() ? 'superadmin' : (isAdmin() ? 'admin' : 'usuario'));

        if (empty($detalle)) {
            $detalle = $accion;
        }

        $db->insert('historial_cambios', [
            'fecha' => date('d/m/Y H:i:s'),
            'timestamp' => time(),
            'usuario' => $nombre ?: 'Admin',
            'email' => $email,
            'rol' => $rol,
            'modulo' => $modulo,
            'accion' => $accion,
            'detalle' => $detalle
        ], 'id_cambio');
    } catch (\Throwable $e) {
        // Silently capture logging errors to never block business logic
        error_log('Error registrando cambio en auditoría: ' . $e->getMessage());
    }
}
