<?php
/**
 * ARCANA — Authentication API Endpoint (Schema: arcanaestetica)
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/JsonDB.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/config/mail.php';
require_once __DIR__ . '/response.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$db = JsonDB::getInstance();

// Verificación global de conectividad a MySQL
if (defined('DB_DRIVER') && DB_DRIVER === 'mysql' && !$db->isConnectedToMysql()) {
    $connErr = $db->getConnectionError() ?? 'No se pudo establecer conexión con el servidor MySQL.';
    jsonResponse(false, null, 'Error de conexión con la base de datos MySQL: ' . $connErr . '. Verifique los parámetros en su archivo .env (DB_HOST, DB_USER, DB_PASS, DB_PORT).', 200);
}

// Login
if ($action === 'login' && $method === 'POST') {
    $input = getJsonInput();
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        jsonResponse(false, null, 'Email y contraseña son requeridos.', 400);
    }

    $rateLimitKey = 'login_' . md5(($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') . '_' . strtolower($email));
    if (!checkRateLimit($rateLimitKey, 5, 900)) {
        jsonResponse(false, null, 'Demasiados intentos fallidos. Por seguridad, el acceso ha sido bloqueado temporalmente por 15 minutos.', 429);
    }

    $usuarios = $db->find('usuario', function($u) use ($email) {
        return mb_strtolower($u['email']) === mb_strtolower($email);
    });

    if (empty($usuarios)) {
        recordRateLimitAttempt($rateLimitKey);
        jsonResponse(false, null, 'Credenciales incorrectas. Verifique su email y contraseña.', 200);
    }

    $user = $usuarios[0];
    $passHash = $user['contrasena'] ?? ($user['password_hash'] ?? '');

    if (!password_verify($password, $passHash)) {
        recordRateLimitAttempt($rateLimitKey);
        jsonResponse(false, null, 'Credenciales incorrectas. Verifique su email y contraseña.', 200);
    }

    // Resetear rate limit tras éxito
    resetRateLimit($rateLimitKey);

    if ((int)($user['activo'] ?? 1) === 0) {
        $isPendingVerify = ((int)($user['email_verificado'] ?? 0) === 0);
        if ($isPendingVerify) {
            jsonResponse(false, [
                'needs_verification' => true,
                'email' => $email
            ], 'Tu cuenta aún no ha sido activada. Por favor revisa tu correo para confirmarla.', 403);
        } else {
            jsonResponse(false, null, 'Esta cuenta se encuentra inactiva. Contacte al administrador.', 403);
        }
    }

    // Obtener rol
    $idRol = (int)($user['id_rol'] ?? 3);
    $rol = $db->getById('roles', 'id_rol', $idRol);
    $user['rol'] = $rol ? $rol['nombre_rol'] : ($user['rol'] ?? 'usuario');
    $user['id'] = (int)$user['id_usuario'];
    $userRol = strtolower(trim($user['rol']));
    $email = strtolower(trim($user['email'] ?? ''));

    // Determinar si es Administrador o Super Admin
    $isAdminUser = ($idRol === 1 || $idRol === 2 || str_contains($userRol, 'admin') || str_contains($userRol, 'super') || $email === 'anabelaacosta2026@gmail.com' || $email === 'fernandocabraldev@gmail.com');

    loginUser($user);

    $redirect = $isAdminUser ? 'dashboard.php' : 'index.php';

    jsonResponse(true, [
        'requires_2fa' => false,
        'user' => [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'email' => $user['email'],
            'rol' => $user['rol']
        ],
        'redirect' => $redirect
    ], 'Inicio de sesión exitoso.');
}

// Registro
if ($action === 'register' && $method === 'POST') {
    $input = getJsonInput();
    $nombre = trim($input['nombre'] ?? '');
    $apellido = trim($input['apellido'] ?? '');
    $email = trim($input['email'] ?? '');
    $telefono = trim($input['telefono'] ?? '');
    $dni = trim($input['DNI'] ?? $input['dni'] ?? '');
    $password = $input['password'] ?? '';
    $passwordConfirm = $input['password_confirm'] ?? $input['password2'] ?? '';

    if (empty($nombre) || empty($apellido) || empty($email) || empty($password)) {
        jsonResponse(false, null, 'Por favor complete todos los campos obligatorios.', 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, null, 'El formato de correo electrónico no es válido.', 400);
    }

    if (!empty($passwordConfirm) && $password !== $passwordConfirm) {
        jsonResponse(false, null, 'Las contraseñas no coinciden.', 400);
    }

    // Verificar si el email ya existe
    $existente = $db->find('usuario', function($u) use ($email) {
        return mb_strtolower($u['email'] ?? '') === mb_strtolower($email);
    });
    if (!empty($existente)) {
        jsonResponse(false, null, 'Ya existe una cuenta registrada con este correo electrónico.', 200);
    }

    if (!empty($dni)) {
        $dniExistente = $db->find('usuario', function($u) use ($dni) {
            return ($u['DNI'] ?? '') === $dni;
        });
        if (!empty($dniExistente)) {
            jsonResponse(false, null, 'Ya existe una cuenta registrada con este DNI.', 200);
        }
    }

    try {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $nuevoUsuario = $db->insert('usuario', [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'email' => $email,
            'contrasena' => $passwordHash,
            'fecha_registro' => date('Y-m-d'),
            'activo' => 0, // Inactivo hasta que confirme el correo
            'email_verificado' => 0,
            'telefono' => $telefono,
            'DNI' => $dni ?: (string)rand(30000000, 49000000),
            'id_rol' => 3 // Rol Cliente
        ], 'id_usuario');

        // Generar token seguro de verificación
        $token = bin2hex(random_bytes(24));
        try {
            $db->insert('verification_tokens', [
                'id_usuario' => (int)$nuevoUsuario['id_usuario'],
                'email' => $email,
                'token' => $token,
                'expira_en' => date('Y-m-d H:i:s', time() + 86400), // 24 horas de validez
                'utilizado' => 0,
                'creado_en' => date('Y-m-d H:i:s')
            ], 'id');
        } catch (Throwable $eToken) {
            error_log("Aviso: no se pudo insertar en verification_tokens: " . $eToken->getMessage());
        }

        // Construir enlace de verificación
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')));
        $encodedDir = implode('/', array_map('rawurlencode', explode('/', $scriptDir)));
        $baseUrl = rtrim($protocol . $host . $encodedDir, '/');
        $verifyLink = $baseUrl . '/confirmar-cuenta.php?token=' . urlencode($token) . '&email=' . urlencode($email);

        // Enviar correo de confirmación de forma segura
        $mailResult = ['success' => false];
        try {
            $mailResult = sendAccountVerificationEmail($nuevoUsuario, $token, $verifyLink);
        } catch (Throwable $eMail) {
            error_log("Aviso: Error enviando correo de confirmación: " . $eMail->getMessage());
        }

        jsonResponse(true, [
            'requires_verification' => true,
            'email' => $email,
            'nombre' => $nombre,
            'mail_sent' => $mailResult['success'] ?? false,
            'verify_link' => (!($mailResult['success'] ?? false) || APP_ENV === 'development') ? $verifyLink : null
        ], '¡Registro exitoso! Te hemos enviado un correo electrónico para confirmar y activar tu cuenta.', 200);

    } catch (Throwable $e) {
        error_log("Error al procesar el registro: " . $e->getMessage());
        jsonResponse(false, null, 'Error al procesar el registro: ' . $e->getMessage(), 200);
    }
}

// Reenviar correo de confirmación de cuenta
if ($action === 'resend_verification' && $method === 'POST') {
    $input = getJsonInput();
    $email = trim($input['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, null, 'Ingresa un correo electrónico válido.', 400);
    }

    $usuarios = $db->find('usuario', function($u) use ($email) {
        return mb_strtolower($u['email']) === mb_strtolower($email);
    });

    if (empty($usuarios)) {
        jsonResponse(false, null, 'No encontramos ninguna cuenta registrada con este correo.', 404);
    }

    $user = $usuarios[0];
    if ((int)($user['activo'] ?? 0) === 1 && (int)($user['email_verificado'] ?? 1) === 1) {
        jsonResponse(true, ['already_active' => true], 'Tu cuenta ya está activada. Puedes iniciar sesión normalmente.');
    }

    // Generar nuevo token
    $token = bin2hex(random_bytes(24));
    try {
        $db->insert('verification_tokens', [
            'id_usuario' => (int)$user['id_usuario'],
            'email' => $email,
            'token' => $token,
            'expira_en' => date('Y-m-d H:i:s', time() + 86400),
            'utilizado' => 0,
            'creado_en' => date('Y-m-d H:i:s')
        ], 'id');
    } catch (Throwable $eToken) {
        error_log("Aviso: no se pudo insertar en verification_tokens: " . $eToken->getMessage());
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')));
    $encodedDir = implode('/', array_map('rawurlencode', explode('/', $scriptDir)));
    $baseUrl = rtrim($protocol . $host . $encodedDir, '/');
    $verifyLink = $baseUrl . '/confirmar-cuenta.php?token=' . urlencode($token) . '&email=' . urlencode($email);

    $mailResult = sendAccountVerificationEmail($user, $token, $verifyLink);

    jsonResponse(true, [
        'mail_sent' => $mailResult['success'] ?? false
    ], 'Hemos reenviado el correo de confirmación. Por favor revisa tu bandeja de entrada o spam.');
}

// Verificar confirmación de cuenta
if ($action === 'verify_email' && ($method === 'POST' || $method === 'GET')) {
    $input = ($method === 'POST') ? getJsonInput() : $_GET;
    $email = trim($input['email'] ?? '');
    $token = trim($input['token'] ?? '');

    if (empty($token)) {
        jsonResponse(false, null, 'Enlace de confirmación incompleto o inválido.', 400);
    }

    $now = date('Y-m-d H:i:s');
    $tokens = $db->find('verification_tokens', function($t) use ($email, $token, $now) {
        if ((int)($t['utilizado'] ?? 0) === 1) return false;
        if (($t['expira_en'] ?? '') < $now) return false;
        if (($t['token'] ?? '') !== $token) return false;
        if (!empty($email) && mb_strtolower($t['email'] ?? '') !== mb_strtolower($email)) return false;
        return true;
    });

    if (empty($tokens)) {
        jsonResponse(false, null, 'El enlace de confirmación es inválido o ha expirado.', 400);
    }

    $matchedToken = $tokens[0];
    $targetEmail = $matchedToken['email'];

    $usuarios = $db->find('usuario', function($u) use ($targetEmail) {
        return mb_strtolower($u['email']) === mb_strtolower($targetEmail);
    });

    if (empty($usuarios)) {
        jsonResponse(false, null, 'Usuario no encontrado.', 404);
    }

    $user = $usuarios[0];

    // Activar usuario
    $db->update('usuario', 'id_usuario', $user['id_usuario'], [
        'activo' => 1,
        'email_verificado' => 1
    ]);

    // Marcar token como utilizado
    $tokenId = $matchedToken['id'] ?? null;
    if ($tokenId) {
        $db->update('verification_tokens', 'id', $tokenId, [
            'utilizado' => 1
        ]);
    }

    jsonResponse(true, [
        'email' => $targetEmail,
        'nombre' => $user['nombre']
    ], '¡Tu cuenta ha sido confirmada y activada con éxito! Ya puedes iniciar sesión.');
}

// Logout
if ($action === 'logout') {
    logoutUser();
    if ($method === 'POST' || !empty($_GET['json'])) {
        jsonResponse(true, null, 'Sesión cerrada correctamente.');
    } else {
        header('Location: ../login.php');
        exit;
    }
}

// Solicitar recuperación de contraseña (envía código o enlace por email)
if ($action === 'forgot_password' && $method === 'POST') {
    $input = getJsonInput();
    $email = trim($input['email'] ?? '');

    if (empty($email)) {
        jsonResponse(false, null, 'Por favor ingresa tu correo electrónico.', 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, null, 'El formato de correo no es válido.', 400);
    }

    $usuarios = $db->find('usuario', function($u) use ($email) {
        return mb_strtolower($u['email']) === mb_strtolower($email);
    });

    if (empty($usuarios)) {
        // Por seguridad o claridad
        jsonResponse(false, null, 'No encontramos ninguna cuenta asociada a este correo electrónico.', 404);
    }

    $user = $usuarios[0];
    // Si la cuenta fue inhabilitada por un admin (pero ya estaba verificada)
    if ((int)($user['activo'] ?? 1) === 0 && (int)($user['email_verificado'] ?? 1) === 1) {
        jsonResponse(false, null, 'Esta cuenta se encuentra inhabilitada. Contacte con administración.', 403);
    }

    // Generar token seguro y código numérico de 6 dígitos
    $token = bin2hex(random_bytes(24));
    $code = sprintf('%06d', mt_rand(100000, 999999));
    $expiraEn = date('Y-m-d H:i:s', time() + 3600); // 1 hora de validez

    // Guardar en tabla reset_tokens
    $record = [
        'email' => $email,
        'id_usuario' => (int)$user['id_usuario'],
        'token' => $token,
        'code' => $code,
        'expira_en' => $expiraEn,
        'creado_en' => date('Y-m-d H:i:s'),
        'utilizado' => 0
    ];
    $db->insert('reset_tokens', $record, 'id');

    // Construir enlace de recuperación
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')));
    $encodedDir = implode('/', array_map('rawurlencode', explode('/', $scriptDir)));
    $baseUrl = rtrim($protocol . $host . $encodedDir, '/');
    $resetLink = $baseUrl . '/restablecer-contrasena.php?token=' . urlencode($token) . '&email=' . urlencode($email);

    // Enviar correo de restablecimiento
    $mailResult = sendPasswordResetEmail($user, $token, $code, $resetLink);

    jsonResponse(true, [
        'email' => $email,
        'token' => $token,
        'mail_sent' => $mailResult['success'] ?? false,
        'expira_en' => $expiraEn
    ], 'Se ha enviado un correo con el enlace y código de recuperación a tu casilla.');
}

// Verificar código o token de recuperación
if ($action === 'verify_reset_code' && ($method === 'POST' || $method === 'GET')) {
    $input = ($method === 'POST') ? getJsonInput() : $_GET;
    $email = trim($input['email'] ?? '');
    $code = trim($input['code'] ?? '');
    $token = trim($input['token'] ?? '');

    if (empty($email) && empty($token)) {
        jsonResponse(false, null, 'Datos insuficientes para verificar.', 400);
    }

    $now = date('Y-m-d H:i:s');
    $tokens = $db->find('reset_tokens', function($t) use ($email, $code, $token, $now) {
        if ((int)($t['utilizado'] ?? 0) === 1) return false;
        if (($t['expira_en'] ?? '') < $now) return false;

        if (!empty($token) && ($t['token'] ?? '') === $token) {
            return true;
        }
        if (!empty($email) && !empty($code) && mb_strtolower($t['email'] ?? '') === mb_strtolower($email) && ($t['code'] ?? '') === $code) {
            return true;
        }
        return false;
    });

    if (empty($tokens)) {
        jsonResponse(false, null, 'El código o enlace es inválido o ha expirado.', 400);
    }

    jsonResponse(true, [
        'valid' => true,
        'email' => $tokens[0]['email'],
        'token' => $tokens[0]['token']
    ], 'Código verificado correctamente.');
}

// Restablecer contraseña
if ($action === 'reset_password' && $method === 'POST') {
    $input = getJsonInput();
    $email = trim($input['email'] ?? '');
    $code = trim($input['code'] ?? '');
    $token = trim($input['token'] ?? '');
    $password = $input['password'] ?? '';
    $passwordConfirm = $input['password_confirm'] ?? $input['password2'] ?? '';

    if (empty($password)) {
        jsonResponse(false, null, 'Por favor ingresa una nueva contraseña.', 400);
    }

    if (strlen($password) < 6) {
        jsonResponse(false, null, 'La contraseña debe tener al menos 6 caracteres.', 400);
    }

    if (!empty($passwordConfirm) && $password !== $passwordConfirm) {
        jsonResponse(false, null, 'Las contraseñas no coinciden.', 400);
    }

    $now = date('Y-m-d H:i:s');
    $tokens = $db->find('reset_tokens', function($t) use ($email, $code, $token, $now) {
        if ((int)($t['utilizado'] ?? 0) === 1) return false;
        if (($t['expira_en'] ?? '') < $now) return false;

        if (!empty($token) && ($t['token'] ?? '') === $token) {
            return true;
        }
        if (!empty($email) && !empty($code) && mb_strtolower($t['email'] ?? '') === mb_strtolower($email) && ($t['code'] ?? '') === $code) {
            return true;
        }
        return false;
    });

    if (empty($tokens)) {
        jsonResponse(false, null, 'El enlace o código es inválido o ya expiró.', 400);
    }

    $matchedToken = $tokens[0];
    $targetEmail = $matchedToken['email'];

    // Buscar usuario
    $usuarios = $db->find('usuario', function($u) use ($targetEmail) {
        return mb_strtolower($u['email']) === mb_strtolower($targetEmail);
    });

    if (empty($usuarios)) {
        jsonResponse(false, null, 'Usuario no encontrado.', 404);
    }

    $user = $usuarios[0];
    $newHash = password_hash($password, PASSWORD_BCRYPT);

    // Actualizar usuario
    $db->update('usuario', 'id_usuario', $user['id_usuario'], [
        'contrasena' => $newHash,
        'activo' => 1,
        'email_verificado' => 1
    ]);

    // Marcar token como utilizado
    $tokenId = $matchedToken['id'] ?? null;
    if ($tokenId) {
        $db->update('reset_tokens', 'id', $tokenId, [
            'utilizado' => 1
        ]);
    }

    jsonResponse(true, [
        'email' => $targetEmail,
        'redirect' => 'login.php?recuperado=1'
    ], '¡Contraseña actualizada exitosamente! Ya puedes iniciar sesión con tu nueva contraseña.');
}

// Google OAuth Login / Register (Google Identity Services)
if ($action === 'google_login' && $method === 'POST') {
    $input = getJsonInput();
    $credential = trim($input['credential'] ?? $input['id_token'] ?? '');
    $accessToken = trim($input['access_token'] ?? '');

    if (empty($credential) && empty($accessToken)) {
        jsonResponse(false, null, 'Token de autenticación de Google no recibido.', 400);
    }

    $payload = null;

    // Permitir inicio simulado en desarrollo o con token de prueba
    if ($credential === 'demo_google_credential' || $credential === 'mock_google_token') {
        $payload = [
            'email' => trim($input['email'] ?? 'usuario.google@arcanamoon.com'),
            'given_name' => trim($input['given_name'] ?? 'Usuario'),
            'family_name' => trim($input['family_name'] ?? 'Google'),
            'name' => trim($input['name'] ?? 'Usuario Google')
        ];
    } elseif (!empty($accessToken)) {
        // Verificar access_token consultando el endpoint oficial userinfo de Google
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $payload = json_decode($response, true);
        }
    } else {
        // 1. Verificar id_token consultando la API oficial de Google tokeninfo
        $verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $verifyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $payload = json_decode($response, true);
        } else {
            // Fallback: decodificar payload JWT
            $parts = explode('.', $credential);
            if (count($parts) >= 2) {
                $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            }
        }
    }

    if (!$payload || empty($payload['email'])) {
        jsonResponse(false, null, 'No fue posible validar la cuenta de Google. Inténtalo nuevamente.', 401);
    }

    $email = trim(mb_strtolower($payload['email']));
    $nombre = trim($payload['given_name'] ?? $payload['name'] ?? 'Usuario');
    $apellido = trim($payload['family_name'] ?? '');

    // Buscar si ya existe una cuenta con este email
    $usuarios = $db->find('usuario', function($u) use ($email) {
        return mb_strtolower($u['email']) === $email;
    });

    if (!empty($usuarios)) {
        $user = $usuarios[0];
        // Asegurar que la cuenta esté activa y verificada
        $db->update('usuario', 'id_usuario', $user['id_usuario'], [
            'activo' => 1,
            'email_verificado' => 1
        ]);
        $user['activo'] = 1;
        $user['email_verificado'] = 1;
    } else {
        // Registrar automáticamente nuevo cliente
        $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        $user = $db->insert('usuario', [
            'nombre' => $nombre ?: 'Usuario',
            'apellido' => $apellido,
            'email' => $email,
            'contrasena' => $randomPass,
            'fecha_registro' => date('Y-m-d'),
            'activo' => 1,
            'email_verificado' => 1,
            'telefono' => '',
            'DNI' => (string)rand(30000000, 49000000),
            'id_rol' => 2 // Rol Usuario / Cliente
        ], 'id_usuario');
    }

    // Obtener información del rol
    $idRol = (int)($user['id_rol'] ?? 2);
    $rol = $db->getById('roles', 'id_rol', $idRol);
    $user['rol'] = $rol ? $rol['nombre_rol'] : ($user['rol'] ?? 'usuario');
    $user['id'] = (int)$user['id_usuario'];

    loginUser($user);

    $userRol = strtolower(trim($user['rol']));
    if ($userRol === 'superadmin' || $userRol === 'super_admin' || isSuperAdmin() || $userRol === 'admin' || isAdmin()) {
        $redirect = 'dashboard.php';
    } else {
        $redirect = 'index.php';
    }

    jsonResponse(true, [
        'user' => getCurrentUser(),
        'redirect' => $redirect
    ], '¡Bienvenido(a) ' . htmlspecialchars($user['nombre']) . '!');
}

jsonResponse(false, null, 'Acción no válida.', 400);
