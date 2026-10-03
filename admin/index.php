<?php
/* Admin front controller: every /admin/... address is routed here (see admin/.htaccess). */
require __DIR__ . '/inc/core.php';

$path = admin_path();
$seg = $path === '' ? [''] : explode('/', $path);

// Bookmarks from the first admin version (items.php?c=team, leads.php …)
$legacy = ['items.php' => $_GET['c'] ?? '', 'leads.php' => 'messages', 'settings.php' => 'settings/' . ($_GET['tab'] ?? 'contact'),
    'account.php' => 'account', 'login.php' => 'login', 'logout.php' => 'logout'];
if (isset($legacy[$seg[0]])) redirect(aurl($legacy[$seg[0]]));

$view = match (true) {
    in_array($seg[0], ['login', 'logout'], true) => $seg[0],
    in_array($seg[0], ['', 'messages', 'settings', 'pages', 'blog', 'media', 'users', 'activity', 'backup', 'account'], true) => $seg[0] ?: 'dashboard',
    isset(COLLECTIONS[$seg[0]]) => 'items',
    default => '404',
};

if (!in_array($view, ['login', 'logout'], true)) require_login();
check_csrf();
require __DIR__ . "/views/$view.php";
