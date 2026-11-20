<?php
/**
 * ARCANA — Mail & Notification System
 * Direct SMTP integration for Gmail with HTML templates and development fallback.
 */

require_once __DIR__ . '/config.php';

// Configuración SMTP de Gmail (Cargada de forma segura desde .env)
define('MAIL_SMTP_HOST', env('MAIL_SMTP_HOST', 'smtp.gmail.com'));
define('MAIL_SMTP_PORT', (int)env('MAIL_SMTP_PORT', 587));
define('MAIL_SMTP_SECURE', env('MAIL_SMTP_SECURE', 'tls'));
define('MAIL_SMTP_USER', env('MAIL_SMTP_USER', 'arcanabms@gmail.com'));
define('MAIL_SMTP_PASS', env('MAIL_SMTP_PASS', ''));
define('MAIL_FROM_EMAIL', env('MAIL_FROM_EMAIL', 'arcanabms@gmail.com'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Arcana — Moon Beauty Secret'));

/**
 * Cliente SMTP Socket puro en PHP para envío de correos sin dependencias externas
 */
function sendSmtpEmail(string $toEmail, string $subject, string $htmlBody, string $textBody = ''): array {
    $user = MAIL_SMTP_USER;
    $pass = MAIL_SMTP_PASS;

    // Si no hay contraseña de aplicación configurada, registrar en log y devolver modo simulación exitoso
    if (empty($pass) || empty($user) || $user === 'tu-correo@gmail.com') {
        $logDir = APP_ROOT . '/database/mail_logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/mail_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.html';
        @file_put_contents($logFile, "<!-- Para: {$toEmail} | Asunto: {$subject} -->\n" . $htmlBody);
        
        return [
            'success' => true,
            'simulated' => true,
            'message' => 'Correo registrado en modo desarrollo (configure MAIL_SMTP_PASS en config/mail.php para envío real por Gmail).'
        ];
    }

    $host = MAIL_SMTP_HOST;
    $port = MAIL_SMTP_PORT;
    $timeout = 15;

    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$socket) {
        return ['success' => false, 'message' => "No se pudo conectar al servidor SMTP: $errstr ($errno)"];
    }

    $read = function($expectedCode = 250) use ($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        $code = (int)substr($response, 0, 3);
        if ($expectedCode && $code !== $expectedCode) {
            return false;
        }
        return $response;
    };

    $send = function($command, $expectedCode = 250) use ($socket, $read) {
        fputs($socket, $command . "\r\n");
        return $read($expectedCode);
    };

    if (!$read(220)) {
        fclose($socket);
        return ['success' => false, 'message' => 'Respuesta SMTP inicial no válida.'];
    }

    if (!$send("EHLO " . gethostname(), 250)) {
        fclose($socket);
        return ['success' => false, 'message' => 'Error en comando EHLO.'];
    }

    // Iniciar STARTTLS
    if (MAIL_SMTP_SECURE === 'tls') {
        if (!$send("STARTTLS", 220)) {
            fclose($socket);
            return ['success' => false, 'message' => 'Error al iniciar STARTTLS en Gmail.'];
        }

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return ['success' => false, 'message' => 'Error en negociación TLS criptográfica.'];
        }

        if (!$send("EHLO " . gethostname(), 250)) {
            fclose($socket);
            return ['success' => false, 'message' => 'Error en EHLO post-TLS.'];
        }
    }

    // Autenticación AUTH LOGIN
    if (!$send("AUTH LOGIN", 334)) {
        fclose($socket);
        return ['success' => false, 'message' => 'Servidor no admite AUTH LOGIN.'];
    }

    if (!$send(base64_encode($user), 334)) {
        fclose($socket);
        return ['success' => false, 'message' => 'Usuario SMTP rechazado.'];
    }

    if (!$send(base64_encode($pass), 235)) {
        fclose($socket);
        return ['success' => false, 'message' => 'Contraseña de aplicación SMTP rechazada por Google. Verifique la clave en config/mail.php.'];
    }

    // Enviar remitente y destinatario
    if (!$send("MAIL FROM: <" . MAIL_FROM_EMAIL . ">", 250)) {
        fclose($socket);
        return ['success' => false, 'message' => 'MAIL FROM rechazado.'];
    }

    if (!$send("RCPT TO: <" . $toEmail . ">", 250)) {
        fclose($socket);
        return ['success' => false, 'message' => 'Destinatario rechazado por el servidor SMTP.'];
    }

    if (!$send("DATA", 354)) {
        fclose($socket);
        return ['success' => false, 'message' => 'Comando DATA rechazado.'];
    }

    // Encabezados MIME y cuerpo
    $boundary = "==_ArcanaMail_" . md5(uniqid(time()));
    $headers = [];
    $headers[] = "From: " . '=?UTF-8?B?' . base64_encode(MAIL_FROM_NAME) . '?=' . " <" . MAIL_FROM_EMAIL . ">";
    $headers[] = "To: <{$toEmail}>";
    $headers[] = "Subject: " . '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
    $headers[] = "Date: " . date('r');
    $headers[] = "X-Mailer: Arcana Beauty Secret Mailer";

    $body = implode("\r\n", $headers) . "\r\n\r\n";

    // Parte texto plano
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= ($textBody ?: strip_tags($htmlBody)) . "\r\n\r\n";

    // Parte HTML
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $htmlBody . "\r\n\r\n";

    $body .= "--{$boundary}--\r\n";
    $body .= ".";

    fputs($socket, $body . "\r\n");
    $result = $read(250);

    $send("QUIT", 221);
    fclose($socket);

    if (!$result) {
        return ['success' => false, 'message' => 'Error al finalizar la transmisión del correo.'];
    }

    return ['success' => true, 'message' => 'Correo enviado correctamente vía Gmail SMTP.'];
}

/**
 * Plantilla base con estética de lujo de Arcana
 */
function getArcanaEmailTemplate(string $title, string $contentHtml): string {
    return '
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <style>
        body { margin: 0; padding: 0; background-color: #0b0c10; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #e5e5e5; }
        .container { max-width: 580px; margin: 30px auto; background: #13141c; border-radius: 16px; border: 1px solid rgba(196, 84, 126, 0.25); overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .header { background: radial-gradient(circle at center, #2a1120 0%, #13141c 100%); padding: 35px 25px; text-align: center; border-bottom: 1px solid rgba(196, 84, 126, 0.2); }
        .logo { font-size: 26px; font-weight: 700; letter-spacing: 3px; color: #f6e05e; text-transform: uppercase; margin: 0; }
        .sublogo { font-size: 11px; color: #c4547e; letter-spacing: 2px; text-transform: uppercase; margin-top: 5px; }
        .body { padding: 35px 30px; line-height: 1.6; font-size: 15px; color: #cbd5e1; }
        .footer { background: #0c0d13; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid rgba(255,255,255,0.05); }
        .btn { display: inline-block; background: linear-gradient(135deg, #c4547e, #8b3a5b); color: #ffffff !important; padding: 12px 28px; text-decoration: none; border-radius: 25px; font-weight: 600; font-size: 14px; margin: 20px 0; box-shadow: 0 4px 15px rgba(196,84,126,0.3); }
        .code-box { background: rgba(196, 84, 126, 0.1); border: 1px dashed #c4547e; border-radius: 10px; padding: 15px; text-align: center; font-size: 28px; font-weight: 700; letter-spacing: 6px; color: #f6e05e; margin: 20px 0; }
        .card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; margin: 20px 0; }
      </style>
    </head>
    <body>
      <div class="container">
        <div class="header">
          <h1 class="logo">ARCANA</h1>
          <div class="sublogo">Moon Beauty Secret</div>
        </div>
        <div class="body">
          <h2 style="color: #ffffff; font-size: 20px; margin-top: 0; margin-bottom: 15px;">' . htmlspecialchars($title) . '</h2>
          ' . $contentHtml . '
        </div>
        <div class="footer">
          <p style="margin: 0 0 5px 0;">Arcana Moon Beauty Secret — Centro de Estética & Cuidado Personal</p>
          <p style="margin: 0;">Este es un correo automático, por favor no respondas directamente a este mensaje.</p>
        </div>
      </div>
    </body>
    </html>';
}

/**
 * Enviar Correo de Verificación y Activación de Cuenta
 */
function sendAccountVerificationEmail(array $user, string $token, string $verifyLink): array {
    $nombre = htmlspecialchars($user['nombre'] ?? 'Cliente');
    $email = $user['email'];
    $subject = 'Confirma tu cuenta — Arcana Moon Beauty Secret';

    $content = '
      <p>Hola <strong>' . $nombre . '</strong>,</p>
      <p>¡Gracias por registrarte en <strong>Arcana Moon Beauty Secret</strong>!</p>
      <p>Para activar tu cuenta y comenzar a disfrutar de nuestros servicios y agendar tus turnos online, por favor confirma tu dirección de correo electrónico haciendo clic en el siguiente botón:</p>
      <div style="text-align: center; margin: 28px 0;">
        <a href="' . $verifyLink . '" class="btn" style="padding: 14px 36px; font-size: 15px; letter-spacing: 0.5px;">✓ Confirmar mi Cuenta</a>
      </div>
      <p style="font-size: 13px; color: #94a3b8; line-height: 1.6;">
        Si el botón no funciona, puedes copiar y pegar el siguiente enlace en tu navegador:<br>
        <a href="' . $verifyLink . '" style="color: #c4547e; word-break: break-all; font-size: 12px;">' . $verifyLink . '</a>
      </p>
      <p style="font-size: 12px; color: #64748b; margin-top: 25px; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 15px;">
        Este enlace es válido por 24 horas. Si tú no creaste esta cuenta, puedes desestimar este mensaje con total tranquilidad.
      </p>
    ';

    $html = getArcanaEmailTemplate('Confirma tu Correo', $content);
    return sendSmtpEmail($email, $subject, $html);
}

/**
 * Enviar Correo de Bienvenida y Confirmación de Registro
 */
function sendWelcomeEmail(array $user): array {
    $nombre = htmlspecialchars($user['nombre'] ?? 'Cliente');
    $email = $user['email'];
    $subject = '¡Bienvenida a Arcana Moon Beauty Secret!';

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $loginUrl = $protocol . $host . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\') . '/login.php';

    $content = '
      <p>Hola <strong>' . $nombre . '</strong>,</p>
      <p>Te damos una cálida bienvenida a <strong>Arcana Moon Beauty Secret</strong>. Tu cuenta ha sido creada exitosamente.</p>
      <p>A partir de ahora podrás:</p>
      <ul>
        <li>Reservar tus turnos de estética y bienestar 100% online.</li>
        <li>Elegir tus profesionales y horarios preferidos.</li>
        <li>Consultar tu historial de citas y comprobantes de seña.</li>
        <li>Calificar y dejar tus reseñas en cada tratamiento.</li>
      </ul>
      <div style="text-align: center;">
        <a href="' . $loginUrl . '" class="btn">Ingresar a Mi Cuenta</a>
      </div>
      <p style="font-size: 13px; color: #94a3b8; margin-top: 25px;">Si no has solicitado esta cuenta, por favor ignora este mensaje.</p>
    ';

    $html = getArcanaEmailTemplate('¡Tu cuenta está lista!', $content);
    return sendSmtpEmail($email, $subject, $html);
}

/**
 * Enviar Correo de Restablecimiento de Contraseña
 */
function sendPasswordResetEmail(array $user, string $token, string $code, string $resetLink): array {
    $nombre = htmlspecialchars($user['nombre'] ?? 'Cliente');
    $email = $user['email'];
    $subject = 'Recuperación de Contraseña — Arcana Moon Beauty Secret';

    $content = '
      <p>Hola <strong>' . $nombre . '</strong>,</p>
      <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en Arcana.</p>
      <p>Tu código de seguridad de 6 dígitos es:</p>
      <div class="code-box">' . htmlspecialchars($code) . '</div>
      <p>O si prefieres, puedes ingresar directamente con el siguiente botón:</p>
      <div style="text-align: center;">
        <a href="' . $resetLink . '" class="btn">Restablecer mi Contraseña</a>
      </div>
      <p style="font-size: 13px; color: #f6ad55;"><strong>Importante:</strong> Este código y enlace expirarán en <strong>1 hora</strong> por motivos de seguridad.</p>
      <p style="font-size: 12px; color: #64748b; margin-top: 20px;">Si no realizaste esta solicitud, puedes desestimar este correo; tu contraseña actual continuará siendo segura.</p>
    ';

    $html = getArcanaEmailTemplate('Recuperación de Contraseña', $content);
    return sendSmtpEmail($email, $subject, $html);
}

/**
 * Enviar Notificación de Cancelación de Turno
 */
function sendCancellationEmail(array $user, array $cita, string $motivo = ''): array {
    $nombre = htmlspecialchars($user['nombre'] ?? 'Cliente');
    $email = $user['email'] ?? '';
    if (empty($email)) return ['success' => false, 'message' => 'Usuario sin correo.'];

    $subject = 'Aviso de Cancelación de Turno — Arcana Moon Beauty Secret';
    $servicio = htmlspecialchars($cita['servicio'] ?? 'Tratamiento');
    $fechaHora = htmlspecialchars($cita['fecha_hora'] ?? '');

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $agendaUrl = $protocol . $host . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\') . '/agenda.php';

    $content = '
      <p>Hola <strong>' . $nombre . '</strong>,</p>
      <p>Te informamos que el siguiente turno agendado ha sido <strong>Cancelado</strong>:</p>
      <div class="card">
        <p style="margin: 5px 0;"><strong>Servicio:</strong> ' . $servicio . '</p>
        <p style="margin: 5px 0;"><strong>Fecha y Hora:</strong> ' . $fechaHora . ' hs</p>
        ' . (!empty($motivo) ? '<p style="margin: 5px 0; color: #fca5a5;"><strong>Motivo:</strong> ' . htmlspecialchars($motivo) . '</p>' : '') . '
      </div>
      <p>Si deseas reprogramar tu cita o elegir una nueva fecha, puedes hacerlo cuando desees desde nuestra plataforma:</p>
      <div style="text-align: center;">
        <a href="' . $agendaUrl . '" class="btn">Agendar Nuevo Turno</a>
      </div>
    ';

    $html = getArcanaEmailTemplate('Cancelación de Turno', $content);
    return sendSmtpEmail($email, $subject, $html);
}

/**
 * Enviar Confirmación de Reserva de Turno
 */
function sendBookingConfirmationEmail(array $user, array $cita): array {
    $nombre = htmlspecialchars($user['nombre'] ?? 'Cliente');
    $email = $user['email'] ?? '';
    if (empty($email)) return ['success' => false, 'message' => 'Usuario sin correo.'];

    $subject = '¡Turno Reservado con Éxito! — Arcana Moon Beauty Secret';
    $servicio = htmlspecialchars($cita['servicio'] ?? 'Tratamiento');
    $fechaHora = htmlspecialchars($cita['fecha_hora'] ?? '');
    $especialista = htmlspecialchars($cita['empleado'] ?? $cita['especialista'] ?? 'Profesional Arcana');
    $precio = number_format((float)($cita['precio'] ?? $cita['precio_total'] ?? 0), 0, ',', '.');
    $sena = number_format((float)($cita['monto_sena'] ?? 0), 0, ',', '.');

    $content = '
      <p>Hola <strong>' . $nombre . '</strong>,</p>
      <p>¡Tu reserva ha sido registrada con éxito en nuestro sistema!</p>
      <div class="card">
        <p style="margin: 5px 0;"><strong>Tratamiento:</strong> ' . $servicio . '</p>
        <p style="margin: 5px 0;"><strong>Especialista:</strong> ' . $especialista . '</p>
        <p style="margin: 5px 0;"><strong>Fecha y Hora:</strong> ' . $fechaHora . ' hs</p>
        <p style="margin: 5px 0;"><strong>Valor Total:</strong> $' . $precio . '</p>
        <p style="margin: 5px 0; color: #34d399;"><strong>Seña de Reserva:</strong> $' . $sena . '</p>
      </div>
      <p>Te esperamos en nuestro salón 10 minutos antes de tu cita.</p>
    ';

    $html = getArcanaEmailTemplate('Confirmación de Reserva', $content);
    return sendSmtpEmail($email, $subject, $html);
}
