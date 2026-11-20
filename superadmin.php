<?php
/**
 * ARCANA — Superadmin Redirect (superadmin.php)
 * Redirige automáticamente al Dashboard unificado.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';

header('Location: dashboard.php');
exit;
