<?php
/**
 * ARCANA — Middleware for Route Protection
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/session.php';

// If user is not logged in or is not an admin, redirect to login page
if (!isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? 'dashboard.php'));
    exit;
}

if (!isAdmin()) {
    header('Location: index.php?msg=' . urlencode('Acceso denegado. Se requieren permisos de administrador.'));
    exit;
}
