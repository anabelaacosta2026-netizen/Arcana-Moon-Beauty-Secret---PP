<?php
/**
 * ARCANA — Logout Script (logout.php)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';

logoutUser();

if (isset($_COOKIE['g_state'])) {
    setcookie('g_state', '', time() - 42000, '/');
}

header('Location: login.php?logout=1');
exit;

