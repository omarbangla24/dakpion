<?php
/* Admin core: session, auth & roles, CSRF, layout and form fields. Views live in admin/views/. */
require __DIR__ . '/../../inc/bootstrap.php';

const ADMIN = true;

session_name('dk_admin');
session_set_cookie_params([
    'path' => base_path(),
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
]);
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

/** Admin URL: aurl('projects/3') → /admin/projects/3 */
function aurl(string $path = ''): string
{
    return url('admin/' . ltrim($path, '/'));
}

/** Path inside /admin/ for the current request, e.g. "projects/3". */
function admin_path(): string
{
    $uri = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
    $prefix = base_path() . 'admin';
    $p = str_starts_with($uri, $prefix) ? substr($uri, strlen($prefix)) : '';
    return trim(preg_replace('~^/index\.php~', '', $p), '/');
}

/* ── Auth & roles ─────────────────────────────────────────── */

function current_user(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
    $id = $_SESSION['uid'] ?? null;
    if (!$id) return $u = null;
    $st = db()->prepare('SELECT id, username, name, email, role FROM users WHERE id = ? AND active = 1');
    $st->execute([$id]);
    $u = $st->fetch() ?: null;
    if ($u) {
        $u['role'] = $u['role'] ?: 'admin';
        $GLOBALS['ACTOR'] = $u;
    } else {
        unset($_SESSION['uid']);
    }
    return $u;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_login(): array
{
    $u = current_user();
    if (!$u) redirect(aurl('login') . ($_SERVER['REQUEST_METHOD'] === 'GET' ? '?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '') : ''));
    return $u;
}

function require_admin(): void
{
    if (!is_admin()) {
        http_response_code(403);
        admin_head('No access');
        echo '<div class="card empty"><h2>Ei page shudhu Admin der jonno</h2><p class="muted">Apnar role Editor. Access lagle Admin ke bolun.</p><a class="btn" href="' . aurl() . '">← Dashboard</a></div>';
        admin_foot();
        exit;
    }
}

function has_users(): bool
{
    return (bool)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

function too_many_attempts(): bool
{
    db()->prepare('DELETE FROM login_attempts WHERE at < ?')->execute([time() - 900]);
    $st = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ?');
    $st->execute([$_SERVER['REMOTE_ADDR'] ?? '']);
    return $st->fetchColumn() >= 5;
}

function log_in(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$user['id'];
    db()->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$_SERVER['REMOTE_ADDR'] ?? '']);
    db()->prepare('UPDATE users SET last_login = ? WHERE id = ?')->execute([now(), $user['id']]);
    // Tells the public pages not to count this browser's visits.
    setcookie('dk_staff', '1', ['expires' => time() + 86400 * 90, 'path' => base_path(), 'samesite' => 'Lax', 'httponly' => true]);
    $GLOBALS['ACTOR'] = $user;
    log_activity('Logged in');
}

/* ── CSRF, flash, redirects ───────────────────────────────── */

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}

function csrf(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function check_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $sent = (string)($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        exit('Session expired — go back, reload the page and try again.');
    }
}

function flash(string $msg, string $type = 'ok'): void
{
    $_SESSION['flash'] = [$msg, $type];
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── Layout ───────────────────────────────────────────────── */

function unread_leads(): int
{
    return (int)db()->query('SELECT COUNT(*) FROM leads WHERE is_read = 0')->fetchColumn();
}

/** Small stroke icons for the admin. */
function aicon(string $name, int $size = 18): string
{
    $paths = [
        'dash' => '<rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/>',
        'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5h13l3.5 7v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6z"/>',
        'file' => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>',
        'pen' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="9" cy="9" r="2"/><path d="M21 15l-5-5L5 21"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'layers' => '<path d="M12 2 2 7l10 5 10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>',
        'cog' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5a6.5 6.5 0 0 1 3.5 5.5"/>',
        'activity' => '<path d="M22 12h-4l-3 8L9 4l-3 8H2"/>',
        'download' => '<path d="M12 3v12M7 10l5 5 5-5"/><path d="M5 21h14"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h5"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'drag' => '<circle cx="9" cy="6" r="1.3"/><circle cx="15" cy="6" r="1.3"/><circle cx="9" cy="12" r="1.3"/><circle cx="15" cy="12" r="1.3"/><circle cx="9" cy="18" r="1.3"/><circle cx="15" cy="18" r="1.3"/>',
        'eye' => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off' => '<path d="M3 3l18 18M10.6 5.1A10 10 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4M6.6 6.6C3.9 8.3 2 12 2 12s3.6 7 10 7a9.7 9.7 0 0 0 4.5-1.1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M14 6l4 4"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'copy' => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/>',
        'upload' => '<path d="M12 21V9M7 14l5-5 5 5"/><path d="M5 3h14"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'chat' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L3 21l1.9-6.4A8 8 0 1 1 21 12z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'megaphone' => '<path d="M3 11v2a1 1 0 0 0 1 1h3l5 4V6L7 10H4a1 1 0 0 0-1 1z"/><path d="M16 9a3 3 0 0 1 0 6M19 6a7 7 0 0 1 0 12"/>',
    ];
    return '<svg class="ai" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

/**
 * Page chrome. $active marks the sidebar item; $crumbs = [label => url|null].
 */
function admin_head(string $title, string $active = '', array $crumbs = [], string $actions = ''): void
{
    $u = current_user();
    $unread = $u ? unread_leads() : 0;
    $link = function (string $path, string $key, string $label, string $ic, string $extra = '') use ($active) {
        return '<a href="' . aurl($path) . '"' . ($active === $key ? ' class="on" aria-current="page"' : '') . '>' . aicon($ic) . '<span>' . $label . '</span>' . $extra . '</a>';
    };
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — <?= e(setting('site_name', 'Dakpion IMC')) ?> Admin</title>
<link rel="icon" href="<?= url('assets/images/favicon.png') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('admin/admin.css') ?>">
<script>window.ADMIN = <?= json_encode(['base' => aurl(), 'csrf' => csrf_token(), 'site' => url()], JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= asset('admin/admin.js') ?>" defer></script>
</head>
<body>
<?php if ($u): ?>
<input type="checkbox" id="nav-toggle" hidden>
<aside class="side">
  <a class="side-brand" href="<?= aurl() ?>"><img src="<?= url('assets/images/logo-dark.webp') ?>" alt="Dakpion IMC"><span>Admin</span></a>
  <nav>
    <?= $link('', 'dash', 'Dashboard', 'dash') ?>
    <?= $link('messages', 'messages', 'Messages', 'inbox', $unread ? '<b class="pill">' . $unread . '</b>' : '') ?>
    <h6>Website</h6>
    <?= $link('pages', 'pages', 'Pages & SEO', 'file') ?>
    <?= $link('blog', 'blog', 'Blog', 'pen') ?>
    <?= $link('media', 'media', 'Media library', 'image') ?>
    <?= $link('settings/popup', 'set-popup', 'Offer bar & popup', 'megaphone') ?>
<?php foreach (ADMIN_MENU as $group => $keys): ?>
    <h6><?= e($group) ?></h6>
<?php foreach ($keys as $k): ?>
    <?= $link($k, 'c-' . $k, e(COLLECTIONS[$k]['label']), $group === 'Content' ? 'grid' : 'layers') ?>
<?php endforeach; endforeach; if (is_admin()): ?>
    <h6>Settings</h6>
<?php foreach (SETTINGS as $k => $g): if ($k === 'popup') continue; ?>
    <?= $link('settings/' . $k, 'set-' . $k, e($g['label']), 'cog') ?>
<?php endforeach; ?>
    <h6>System</h6>
    <?= $link('users', 'users', 'Users', 'users') ?>
    <?= $link('activity', 'activity', 'Activity log', 'activity') ?>
    <?= $link('backup', 'backup', 'Backup', 'download') ?>
<?php endif; ?>
  </nav>
  <div class="side-user">
    <a href="<?= aurl('account') ?>" class="who<?= $active === 'account' ? ' on' : '' ?>"><span class="av"><?= e(initials($u['name'] ?: $u['username'])) ?></span><span><b><?= e($u['name'] ?: $u['username']) ?></b><small><?= e(ROLES[$u['role']] ?? $u['role']) ?></small></span></a>
    <a href="<?= aurl('logout') ?>" class="ib" title="Log out"><?= aicon('logout') ?></a>
  </div>
</aside>
<label for="nav-toggle" class="side-scrim" aria-hidden="true"></label>
<main class="main">
  <header class="top">
    <label for="nav-toggle" class="burger" aria-label="Menu"><i></i><i></i><i></i></label>
    <div class="top-title">
<?php if ($crumbs): ?>
      <nav class="crumbs"><?php foreach ($crumbs as $label => $href): ?><?= $href ? '<a href="' . e($href) . '">' . e($label) . '</a>' : '<span>' . e($label) . '</span>' ?><i>/</i><?php endforeach; ?></nav>
<?php endif; ?>
      <h1><?= e($title) ?></h1>
    </div>
    <div class="top-actions"><?= $actions ?><a class="btn btn--ghost" href="<?= url() ?>" target="_blank" rel="noopener"><?= aicon('external', 16) ?><span>View site</span></a></div>
  </header>
<?php else: ?>
<main class="main main--bare">
<?php endif;
    if ($f = $_SESSION['flash'] ?? null): unset($_SESSION['flash']); ?>
  <div class="flash flash--<?= e($f[1]) ?>" role="status"><?= e($f[0]) ?></div>
<?php endif;
}

function admin_foot(): void
{
    echo "</main>\n<div class=\"modal\" id=\"media-modal\" hidden></div>\n</body>\n</html>\n";
}

/* ── Fields ───────────────────────────────────────────────── */

/** Fields that read better at full width in the two-column form grid. */
function wide_field(string $type): bool
{
    return in_array($type, ['textarea', 'lines', 'image', 'multi', 'heading', 'check', 'rich'], true);
}

function render_field(string $name, array $def, ?string $value, string $prefix = 'f'): string
{
    $id = 'f-' . str_replace('.', '-', $name);
    $type = $def['type'];
    $v = (string)$value;
    $req = !empty($def['required']) ? ' required' : '';
    $label = '<label for="' . $id . '">' . e($def['label']) . ($req ? ' <em>*</em>' : '') . '</label>';
    $help = !empty($def['help']) ? '<small>' . e($def['help']) . '</small>' : '';
    $n = $prefix . '[' . $name . ']';
    $attrs = $def['attrs'] ?? '';

    switch ($type) {
        case 'textarea':
        case 'lines':
        case 'heading':
            $rows = $type === 'lines' ? max(4, count(lines($v)) + 1) : ($type === 'heading' ? max(2, substr_count($v, "\n") + 1) : 3);
            if ($type === 'heading' && !$help) $help = '<small>Notun line = line break · *word* = green highlight</small>';
            $input = '<textarea id="' . $id . '" name="' . $n . '" rows="' . $rows . '"' . $req . $attrs . '>' . e($v) . '</textarea>';
            break;
        case 'check':
            return '<div class="field field--check wide"><input type="hidden" name="' . $n . '" value=""><label class="switch"><input type="checkbox" id="' . $id . '" name="' . $n
                . '" value="1"' . ($v === '1' ? ' checked' : '') . '><i></i><span>' . e($def['label']) . '</span></label>' . $help . '</div>';
        case 'select':
            $input = '<select id="' . $id . '" name="' . $n . '"><option value="">—</option>';
            foreach ($def['options'] as $k => $l) $input .= '<option value="' . e((string)$k) . '"' . ((string)$k === $v ? ' selected' : '') . '>' . e($l) . '</option>';
            $input .= '</select>';
            break;
        case 'multi':
            $on = array_map('trim', explode(',', $v));
            $input = '<input type="hidden" name="' . $n . '[]" value=""><div class="chips">';
            foreach ($def['options'] as $k => $l) {
                $input .= '<label><input type="checkbox" name="' . $n . '[]" value="' . e((string)$k) . '"' . (in_array((string)$k, $on, true) ? ' checked' : '') . '><span>' . e($l) . '</span></label>';
            }
            $input .= '</div>';
            break;
        case 'image':
            $input = '<div class="img-field" data-img-field>'
                . '<div class="img-prev">' . ($v ? '<img src="' . e(media_url($v)) . '" alt="">' : '<span>No image</span>') . '</div>'
                . '<div class="img-actions"><input type="hidden" name="' . $n . '" value="' . e($v) . '">'
                . '<label class="btn btn--sm">' . aicon('upload', 15) . 'Upload<input type="file" id="' . $id . '" name="img_' . $name . '" accept="image/jpeg,image/png,image/webp,image/gif" hidden></label>'
                . '<button type="button" class="btn btn--sm" data-pick>' . aicon('image', 15) . 'Library</button>'
                . '<button type="button" class="btn btn--sm btn--danger-text" data-clear' . ($v ? '' : ' hidden') . '>Remove</button>'
                . '</div></div>';
            break;
        default:
            // url stays type=text so "facebook.com/page" is accepted; https:// is added on save
            $t = ['email' => 'email', 'number' => 'number', 'date' => 'date'][$type] ?? 'text';
            $extra = ['number' => ' step="any"', 'url' => ' inputmode="url" placeholder="https://"'][$type] ?? '';
            $input = '<input type="' . $t . '" id="' . $id . '" name="' . $n . '" value="' . e($v) . '"' . $req . $extra . $attrs . '>';
    }
    return '<div class="field field--' . $type . (wide_field($type) ? ' wide' : '') . '">' . $label . $input . $help . '</div>';
}

/** A library path chosen in a form must be a real upload. */
function valid_media_path(string $v): bool
{
    if ($v === '') return true;
    if (!preg_match('~^uploads/[\w.-]+$~', $v)) return false;
    $st = db()->prepare('SELECT 1 FROM media WHERE path = ?');
    $st->execute([$v]);
    return (bool)$st->fetchColumn() || is_file(ROOT_DIR . '/' . $v);
}

/** Read posted values for $fields. Uploaded images go to the media library. */
function collect_fields(array $fields, string $prefix = 'f'): array
{
    $in = $_POST[$prefix] ?? [];
    $out = [];
    $created = [];
    try {
        foreach ($fields as $name => $def) {
            $type = $def['type'];
            if ($type === 'image') {
                $file = $_FILES['img_' . $name] ?? null;
                if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
                    $out[$name] = $created[] = store_upload($file);
                } else {
                    $v = trim((string)($in[$name] ?? ''));
                    $out[$name] = valid_media_path($v) ? $v : '';
                }
            } elseif ($type === 'multi') {
                $vals = array_filter((array)($in[$name] ?? []), fn($x) => isset($def['options'][$x]));
                $out[$name] = implode(',', $vals);
            } else {
                $v = trim(str_replace("\r\n", "\n", (string)($in[$name] ?? '')));
                if ($type === 'tags') $v = implode(', ', tags($v));
                if ($type === 'lines') $v = implode("\n", lines($v));
                if ($type === 'check') $v = $v === '1' ? '1' : '';
                if ($type === 'select' && $v !== '' && !isset($def['options'][$v])) $v = '';
                if ($type === 'email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) throw new RuntimeException($def['label'] . ': email ta thik na.');
                if ($type === 'url' && $v !== '' && !preg_match('~^https?://~i', $v)) $v = 'https://' . $v;
                if ($type === 'number' && $v !== '' && !is_numeric($v)) throw new RuntimeException($def['label'] . ': shudhu number din.');
                $out[$name] = $v;
            }
            if (!empty($def['required']) && $out[$name] === '') throw new RuntimeException($def['label'] . ' lagbe.');
        }
    } catch (RuntimeException $ex) {
        array_map('delete_media', $created);
        throw $ex;
    }
    return $out;
}

/** Short preview of a value for list tables. */
function cell(array $def, ?string $v): string
{
    $v = (string)$v;
    return match ($def['type']) {
        'image' => $v ? '<img class="thumbnail" src="' . e(media_url($v)) . '" alt="" loading="lazy">' : '<span class="thumbnail thumbnail--empty"></span>',
        'check' => $v === '1' ? '<span class="yes">' . aicon('check', 16) . '</span>' : '<span class="muted">—</span>',
        'select' => e($def['options'][$v] ?? $v),
        'multi' => e(implode(', ', array_map(fn($k) => $def['options'][$k] ?? $k, array_filter(explode(',', $v))))),
        default => e(mb_strimwidth(str_replace("\n", ' · ', $v), 0, 80, '…')),
    };
}

function ago(?string $date): string
{
    if (!$date) return '—';
    $d = time() - strtotime($date);
    return match (true) {
        $d < 60 => 'just now',
        $d < 3600 => floor($d / 60) . ' min ago',
        $d < 86400 => floor($d / 3600) . ' h ago',
        $d < 86400 * 7 => floor($d / 86400) . ' d ago',
        default => date('d M Y', strtotime($date)),
    };
}

/** Pager links for list pages. */
function pager(int $page, int $pages, callable $href): string
{
    if ($pages < 2) return '';
    $out = '<nav class="pager">';
    for ($n = 1; $n <= $pages; $n++) {
        if ($pages > 9 && $n > 2 && $n < $pages - 1 && abs($n - $page) > 2) { $out .= ($n === 3 || $n === $pages - 2) ? '<span>…</span>' : ''; continue; }
        $out .= '<a href="' . e($href($n)) . '"' . ($n === $page ? ' aria-current="page"' : '') . '>' . $n . '</a>';
    }
    return $out . '</nav>';
}
