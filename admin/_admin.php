<?php
/* Admin bootstrap: session, auth, CSRF, layout and form-field rendering. */
require __DIR__ . '/../inc/bootstrap.php';

session_name('dk_admin');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
]);
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

/* ── Auth ─────────────────────────────────────────────────── */

function current_user(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
    $id = $_SESSION['uid'] ?? null;
    if (!$id) return $u = null;
    $st = db()->prepare('SELECT id, username FROM users WHERE id = ?');
    $st->execute([$id]);
    return $u = ($st->fetch() ?: null);
}

function require_login(): array
{
    $u = current_user();
    if (!$u) { header('Location: login.php'); exit; }
    return $u;
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

function log_in(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
    db()->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$_SERVER['REMOTE_ADDR'] ?? '']);
}

/* ── CSRF & flash ─────────────────────────────────────────── */

function csrf(): string
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
    return '<input type="hidden" name="_csrf" value="' . $_SESSION['csrf'] . '">';
}

function check_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['_csrf'] ?? ''))) {
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

/* ── Layout ───────────────────────────────────────────────── */

function unread_leads(): int
{
    return (int)db()->query('SELECT COUNT(*) FROM leads WHERE is_read = 0')->fetchColumn();
}

function admin_head(string $title, string $active = ''): void
{
    $unread = unread_leads();
    $link = fn($href, $key, $label, $extra = '') => '<a href="' . $href . '"' . ($active === $key ? ' class="on"' : '') . '>' . $label . $extra . '</a>';
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — Admin</title>
<link rel="icon" href="../assets/images/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css?v=<?= @filemtime(__DIR__ . '/admin.css') ?>">
<script src="admin.js?v=<?= @filemtime(__DIR__ . '/admin.js') ?>" defer></script>
</head>
<body>
<input type="checkbox" id="nav-toggle" hidden>
<aside class="side">
  <a class="side-brand" href="index.php"><img src="../assets/images/logo-dark.webp" alt="Dakpion IMC"><span>Admin</span></a>
  <nav>
    <?= $link('index.php', 'dash', 'Dashboard') ?>
    <?= $link('leads.php', 'leads', 'Messages', $unread ? '<b class="pill">' . $unread . '</b>' : '') ?>
    <h6>Settings</h6>
<?php foreach (SETTINGS as $k => $g): ?>
    <?= $link('settings.php?tab=' . $k, 'set-' . $k, e($g['label'])) ?>
<?php endforeach; foreach (ADMIN_MENU as $group => $keys): ?>
    <h6><?= e($group) ?></h6>
<?php foreach ($keys as $k): ?>
    <?= $link('items.php?c=' . $k, 'c-' . $k, e(COLLECTIONS[$k]['label'])) ?>
<?php endforeach; endforeach; ?>
    <h6>Account</h6>
    <?= $link('account.php', 'account', 'Password') ?>
    <a href="../index.php" target="_blank" rel="noopener">View website ↗</a>
    <a href="logout.php">Log out</a>
  </nav>
</aside>
<label for="nav-toggle" class="side-scrim"></label>
<main class="main">
  <header class="top"><label for="nav-toggle" class="burger" aria-label="Menu"><i></i><i></i><i></i></label><h1><?= e($title) ?></h1></header>
<?php if ($f = $_SESSION['flash'] ?? null): unset($_SESSION['flash']); ?>
  <div class="flash flash--<?= e($f[1]) ?>"><?= e($f[0]) ?></div>
<?php endif;
}

function admin_foot(): void
{
    echo "</main>\n</body>\n</html>\n";
}

/** Small stroke icons for admin buttons. */
function aicon(string $name): string
{
    $paths = [
        'up' => '<path d="M12 19V5M6 11l6-6 6 6"/>',
        'down' => '<path d="M12 5v14M6 13l6 6 6-6"/>',
        'eye' => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off' => '<path d="M3 3l18 18M10.6 5.1A10 10 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4M6.6 6.6C3.9 8.3 2 12 2 12s3.6 7 10 7a9.7 9.7 0 0 0 4.5-1.1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M14 6l4 4"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
    ];
    return '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

/* ── Fields ───────────────────────────────────────────────── */

function render_field(string $name, array $def, ?string $value): string
{
    $id = 'f-' . $name;
    $type = $def['type'];
    $v = (string)$value;
    $req = !empty($def['required']) ? ' required' : '';
    $label = '<label for="' . $id . '">' . e($def['label']) . ($req ? ' <em>*</em>' : '') . '</label>';
    $help = !empty($def['help']) ? '<small>' . e($def['help']) . '</small>' : '';
    $n = 'f[' . $name . ']';

    switch ($type) {
        case 'textarea':
        case 'lines':
            $rows = $type === 'lines' ? max(4, count(lines($v)) + 1) : 4;
            $input = '<textarea id="' . $id . '" name="' . $n . '" rows="' . $rows . '"' . $req . '>' . e($v) . '</textarea>';
            break;
        case 'check':
            return '<div class="field field--check"><input type="hidden" name="' . $n . '" value=""><label><input type="checkbox" id="' . $id . '" name="' . $n
                . '" value="1"' . ($v === '1' ? ' checked' : '') . '> ' . e($def['label']) . '</label>' . $help . '</div>';
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
            $input = '<div class="img-field">';
            $input .= '<div class="img-prev">' . ($v ? '<img src="../' . e($v) . '" alt="">' : '<span>No image</span>') . '</div><div>';
            $input .= '<input type="file" id="' . $id . '" name="img_' . $name . '" accept="image/jpeg,image/png,image/webp,image/gif">';
            if ($v) $input .= '<label class="rm"><input type="checkbox" name="rm_' . $name . '" value="1"> Remove image</label>';
            $input .= '</div></div>';
            break;
        default:
            // url stays type=text so "facebook.com/page" is accepted; https:// is added on save
            $t = ['email' => 'email', 'number' => 'number'][$type] ?? 'text';
            $extra = ['number' => ' step="any"', 'url' => ' inputmode="url" placeholder="https://"'][$type] ?? '';
            $input = '<input type="' . $t . '" id="' . $id . '" name="' . $n . '" value="' . e($v) . '"' . $req . $extra . '>';
    }
    return '<div class="field field--' . $type . '">' . $label . $input . $help . '</div>';
}

/**
 * Read posted values for $fields. Images are stored now; replaced files are returned in
 * $orphans so the caller deletes them only after the save succeeds.
 */
function collect_fields(array $fields, array $old, array &$orphans = []): array
{
    $in = $_POST['f'] ?? [];
    $out = [];
    $created = [];
    try {
        foreach ($fields as $name => $def) {
            $type = $def['type'];
            if ($type === 'image') {
                $cur = (string)($old[$name] ?? '');
                $file = $_FILES['img_' . $name] ?? null;
                if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
                    $out[$name] = $created[] = store_upload($file, $name === 'logo' ? 600 : 1600);
                    if ($cur) $orphans[] = $cur;
                } elseif (!empty($_POST['rm_' . $name])) {
                    $out[$name] = '';
                    if ($cur) $orphans[] = $cur;
                } else {
                    $out[$name] = $cur;
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
        array_map('delete_upload', $created);
        throw $ex;
    }
    return $out;
}

/** Short plain-text preview of a value for list tables. */
function cell(array $def, ?string $v): string
{
    $v = (string)$v;
    return match ($def['type']) {
        'image' => $v ? '<img class="thumbnail" src="../' . e($v) . '" alt="">' : '<span class="muted">—</span>',
        'check' => $v === '1' ? '<span class="yes">✓</span>' : '<span class="muted">—</span>',
        'select' => e($def['options'][$v] ?? $v),
        default => e(mb_strimwidth(str_replace("\n", ' · ', $v), 0, 70, '…')),
    };
}
