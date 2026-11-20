<?php
/**
 * ARCANA — Shared Header Template
 * Parameters expected:
 *   $pageTitle (string) - Page title
 *   $pageDescription (string, optional) - Meta description
 *   $pageName (string) - data-page attribute for JS dispatcher
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/session.php';

$pageTitle = $pageTitle ?? 'Arcana — Tu momento de belleza única';
$pageDescription = $pageDescription ?? 'Arcana Beauty Secret — Estética premium y bienestar. Manicura, pedicura, faciales, masajes y más.';
$pageName = $pageName ?? '';

// Cache-busting: versión global = mtime más alto entre todos los módulos JS
$jsDir = dirname(__DIR__) . '/js/';
$jsFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($jsDir, FilesystemIterator::SKIP_DOTS));
$maxMtime = 0;
foreach ($jsFiles as $file) {
  if ($file->isFile() && $file->getExtension() === 'js') {
    $maxMtime = max($maxMtime, $file->getMTime());
  }
}
$jsVer  = $maxMtime ?: time();
$cssVer = file_exists(dirname(__DIR__) . '/css/main.css') ? filemtime(dirname(__DIR__) . '/css/main.css') : time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/main.css?v=<?= $cssVer ?>">
  <script>
    // Sanitizar credenciales para evitar TypeError en fetch() cuando el navegador almacena credenciales de Basic Auth
    (function() {
      try {
        if (window.location.username || window.location.password) {
          window.history.replaceState(null, '', window.location.pathname + window.location.search + window.location.hash);
        }
      } catch (e) {}

      const origFetch = window.fetch;
      window.fetch = function(resource, init) {
        if (typeof resource === 'string' || resource instanceof URL) {
          try {
            const u = new URL(resource, window.location.origin + window.location.pathname);
            u.username = '';
            u.password = '';
            resource = u.href;
          } catch (e) {}
        }
        return origFetch.call(this, resource, init);
      };
    })();
  </script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <script type="module" src="js/main.js?v=<?= $jsVer ?>"></script>
</head>
<body data-page="<?= htmlspecialchars($pageName) ?>">
<?php include __DIR__ . '/orbs.php'; ?>
