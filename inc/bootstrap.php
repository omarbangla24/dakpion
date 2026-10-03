<?php
declare(strict_types=1);

const ROOT_DIR = __DIR__ . '/..';
const UPLOAD_DIR = ROOT_DIR . '/uploads';

// Optional server-only overrides, e.g. define('DATA_DIR', '/home/account/dakpion-data'); to keep the
// database outside the web root. The file is not in git.
if (is_file(__DIR__ . '/local.php')) require __DIR__ . '/local.php';
defined('DATA_DIR') || define('DATA_DIR', ROOT_DIR . '/data');

require __DIR__ . '/config.php';

date_default_timezone_set('Asia/Dhaka');

/* ── Database ─────────────────────────────────────────────── */

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0775, true);
    $pdo = new PDO('sqlite:' . DATA_DIR . '/site.sqlite', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA busy_timeout = 5000');
    migrate($pdo);
    return $pdo;
}

function add_columns(PDO $pdo, string $table, array $cols): void
{
    $have = array_column($pdo->query("PRAGMA table_info($table)")->fetchAll(), 'name');
    foreach ($cols as $name => $def) {
        if (!in_array($name, $have, true)) $pdo->exec("ALTER TABLE $table ADD COLUMN \"$name\" $def");
    }
}

function migrate(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE NOT NULL, pass_hash TEXT NOT NULL, created_at TEXT)');
    add_columns($pdo, 'users', ['name' => 'TEXT', 'email' => 'TEXT', 'role' => "TEXT DEFAULT 'admin'", 'active' => 'INTEGER DEFAULT 1', 'last_login' => 'TEXT']);
    $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (ip TEXT, at INTEGER)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS leads (id INTEGER PRIMARY KEY AUTOINCREMENT, created_at TEXT, name TEXT, company TEXT,
        email TEXT, phone TEXT, services TEXT, industry TEXT, budget TEXT, message TEXT, ip TEXT, user_agent TEXT, is_read INTEGER DEFAULT 0)');
    add_columns($pdo, 'leads', ['status' => "TEXT DEFAULT 'new'", 'follow_up' => 'TEXT', 'updated_at' => 'TEXT']);
    $pdo->exec('CREATE TABLE IF NOT EXISTS lead_notes (id INTEGER PRIMARY KEY AUTOINCREMENT, lead_id INTEGER, user_id INTEGER, username TEXT, note TEXT, created_at TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS posts (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, slug TEXT UNIQUE, excerpt TEXT, body TEXT,
        cover TEXT, category TEXT, status TEXT DEFAULT \'draft\', published_at TEXT, seo_title TEXT, seo_desc TEXT, author_id INTEGER,
        views INTEGER DEFAULT 0, created_at TEXT, updated_at TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS media (id INTEGER PRIMARY KEY AUTOINCREMENT, path TEXT UNIQUE, name TEXT, size INTEGER,
        width INTEGER, height INTEGER, user_id INTEGER, created_at TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS activity (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, username TEXT, action TEXT,
        target TEXT, details TEXT, ip TEXT, created_at TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS views (day TEXT, path TEXT, count INTEGER DEFAULT 0, PRIMARY KEY (day, path))');

    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    foreach (COLLECTIONS as $key => $c) {
        $t = 'c_' . $key;
        if (!in_array($t, $tables, true)) {
            $cols = implode(', ', array_map(fn($f) => '"' . $f . '" TEXT', array_keys($c['fields'])));
            $pdo->exec("CREATE TABLE $t (id INTEGER PRIMARY KEY AUTOINCREMENT, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1, $cols)");
            require_once __DIR__ . '/seed.php';
            foreach (SEED_COLLECTIONS[$key] ?? [] as $i => $row) {
                $row = array_intersect_key($row, $c['fields']);
                $row['sort'] = $i + 1;
                insert_row($pdo, $t, $row);
            }
            continue;
        }
        add_columns($pdo, $t, array_fill_keys(array_keys($c['fields']), 'TEXT'));
    }

    $flags = $pdo->query("SELECT key FROM settings WHERE key LIKE '\\_%' ESCAPE '\\'")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('_seeded', $flags, true)) {
        require_once __DIR__ . '/seed.php';
        $st = $pdo->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)');
        foreach (SEED_SETTINGS as $k => $v) $st->execute([$k, $v]);
        $st->execute(['_seeded', date('c')]);
    }
    if (!in_array('_v2', $flags, true)) {
        // Hero and story text moved from settings to page text; keep what was entered.
        foreach (MOVED_SETTINGS as $old => $new) {
            $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) SELECT ?, value FROM settings WHERE key = ? AND value <> ''")->execute([$new, $old]);
        }
        // Files uploaded before the media library existed.
        foreach (glob(UPLOAD_DIR . '/*') ?: [] as $f) {
            if (preg_match('/\.(webp|jpe?g|png|gif)$/i', $f)) register_media($pdo, 'uploads/' . basename($f), basename($f));
        }
        $pdo->exec("INSERT OR IGNORE INTO settings (key, value) VALUES ('_v2', '1')");
    }
}

function insert_row(PDO $pdo, string $table, array $row): int
{
    $cols = array_keys($row);
    $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table,
        implode(', ', array_map(fn($c) => '"' . $c . '"', $cols)), implode(', ', array_fill(0, count($cols), '?')));
    $pdo->prepare($sql)->execute(array_values($row));
    return (int)$pdo->lastInsertId();
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

/* ── URLs ─────────────────────────────────────────────────── */

/** Web path of the site root ("/" or "/subfolder/"), worked out from the running script. */
function base_path(): string
{
    static $base = null;
    if ($base !== null) return $base;
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $file = str_replace('\\', '/', (string)realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $root = str_replace('\\', '/', (string)realpath(ROOT_DIR));
    $rel = $file && $root && str_starts_with($file, $root) ? substr($file, strlen($root)) : '/' . basename($script);
    $base = str_ends_with($script, $rel) ? substr($script, 0, -strlen($rel)) : rtrim(dirname($script), '/');
    return $base = rtrim($base, '/') . '/';
}

function url(string $path = ''): string
{
    return base_path() . ltrim($path, '/');
}

/** Static file with a cache-busting version. */
function asset(string $path): string
{
    return url($path) . '?v=' . @filemtime(ROOT_DIR . '/' . $path);
}

/** Uploaded/library image path → URL. */
function media_url(?string $path): string
{
    $path = (string)$path;
    return $path === '' || preg_match('~^https?://~', $path) ? $path : url($path);
}

function abs_url(string $path = ''): string
{
    if (preg_match('~^https?://~', $path)) return $path;
    $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($path);
}

/** External links typed in the admin (social, video): only web, mail and phone links. */
function safe_url(?string $url): string
{
    $url = trim((string)$url);
    return preg_match('~^(https?://|mailto:|tel:)~i', $url) ? $url : '';
}

/** Links typed in the admin that may be internal ("contact", "blog/my-post") or external. */
function link_url(?string $v): string
{
    $v = trim((string)$v);
    if ($v === '') return '';
    if (preg_match('~^(https?://|mailto:|tel:|#)~i', $v)) return $v;
    if (preg_match('~^[a-z][a-z0-9+.-]*:~i', $v)) return ''; // javascript:, data: …
    return url(preg_replace('~\.(php|html)$~', '', ltrim($v, '/')));
}

/* ── Content helpers ──────────────────────────────────────── */

function setting(string $key, string $default = ''): string
{
    static $all = null;
    $all ??= db()->query('SELECT key, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    $v = $all[$key] ?? '';
    return $v !== '' ? $v : $default;
}

/** Editable page text, falling back to the default in PAGES. */
function t(string $page, string $field): string
{
    return setting("$page.$field", PAGES[$page]['fields'][$field][2] ?? '');
}

/** Visible items of a collection, in admin order. */
function items(string $collection, ?string $flag = null, int $limit = 0): array
{
    if (!isset(COLLECTIONS[$collection])) return [];
    $sql = "SELECT * FROM c_$collection WHERE active = 1";
    if ($flag) $sql .= " AND \"$flag\" = '1'";
    $sql .= ' ORDER BY sort, id' . ($limit ? ' LIMIT ' . $limit : '');
    return db()->query($sql)->fetchAll();
}

function posts(int $limit = 0, int $offset = 0, string $category = ''): array
{
    $sql = "SELECT * FROM posts WHERE status = 'published' AND published_at <= ?" . ($category !== '' ? ' AND category = ?' : '')
        . ' ORDER BY published_at DESC, id DESC' . ($limit ? " LIMIT $limit OFFSET $offset" : '');
    $st = db()->prepare($sql);
    $st->execute($category !== '' ? [now(), $category] : [now()]);
    return $st->fetchAll();
}

function has_posts(): bool
{
    static $has = null;
    return $has ??= (bool)posts(1);
}

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escaped text where *words* become <em>words</em>. */
function rich(?string $s): string
{
    return preg_replace('/\*(.+?)\*/u', '<em>$1</em>', e($s));
}

/** Escaped heading: *word* → green highlight, new line → <br>. */
function heading(?string $s): string
{
    return nl2br(preg_replace('/\*(.+?)\*/u', '<span class="hl">$1</span>', e(trim((string)$s))), false);
}

function lines(?string $s): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string)$s)), 'strlen'));
}

function tags(?string $s): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string)$s)), 'strlen'));
}

function initials(string $name): string
{
    $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
    $out = '';
    foreach (array_slice($words, 0, 2) as $w) $out .= mb_strtoupper(mb_substr($w, 0, 1));
    return $out;
}

function slugify(string $s): string
{
    $s = mb_strtolower(trim($s));
    if (function_exists('transliterator_transliterate')) $s = (string)transliterator_transliterate('Any-Latin; Latin-ASCII', $s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim(substr($s, 0, 80), '-') ?: 'post';
}

function tel_href(string $phone): string
{
    return 'tel:' . preg_replace('/[^\d+]/', '', $phone);
}

function wa_href(string $number): string
{
    return 'https://wa.me/' . preg_replace('/\D/', '', $number);
}

/** A number is still a placeholder while it holds X's. */
function is_placeholder(string $v): bool
{
    return $v === '' || stripos($v, 'XXX') !== false;
}

/* ── Activity log & visits ────────────────────────────────── */

/** Record an admin action. The admin sets $GLOBALS['ACTOR'] after login. */
function log_activity(string $action, string $target = '', string $details = ''): void
{
    $u = $GLOBALS['ACTOR'] ?? null;
    insert_row(db(), 'activity', [
        'user_id' => $u['id'] ?? null, 'username' => $u['username'] ?? '', 'action' => $action,
        'target' => mb_substr($target, 0, 200), 'details' => mb_substr($details, 0, 500),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '', 'created_at' => now(),
    ]);
}

/** Count a page view (no cookies, no personal data). */
function track_view(string $path): void
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if ($ua === '' || preg_match('/bot|crawl|spider|slurp|preview|monitor|curl|wget|python|headless|lighthouse/i', $ua)) return;
    if (($_SERVER['HTTP_SEC_PURPOSE'] ?? '') !== '' || ($_SERVER['HTTP_PURPOSE'] ?? '') === 'prefetch') return;
    if (isset($_COOKIE['dk_staff'])) return; // admins browsing their own site
    try {
        db()->prepare('INSERT INTO views (day, path, count) VALUES (?, ?, 1) ON CONFLICT(day, path) DO UPDATE SET count = count + 1')
            ->execute([date('Y-m-d'), mb_substr($path, 0, 120)]);
    } catch (PDOException) {
        // a locked database must never break a page
    }
}

/* ── Rich text ────────────────────────────────────────────── */

/** Keep only safe formatting from the blog editor. */
function clean_html(string $html): string
{
    $allowed = ['p' => [], 'br' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [],
        's' => [], 'a' => ['href'], 'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [], 'img' => ['src', 'alt'], 'figure' => [],
        'figcaption' => [], 'hr' => [], 'code' => [], 'pre' => []];
    if (trim($html) === '') return '';
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementById('__root');
    if (!$root) return '';

    $walk = function (DOMNode $node) use (&$walk, $allowed, $doc) {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'svg', 'math'], true)) {
                    $node->removeChild($child);
                    continue;
                }
                $walk($child);
                if (!isset($allowed[$tag])) {
                    // unknown wrapper (span, div, font…): keep its content; divs become paragraphs
                    if ($tag === 'div') {
                        $p = $doc->createElement('p');
                        while ($child->firstChild) $p->appendChild($child->firstChild);
                        $node->replaceChild($p, $child);
                    } else {
                        while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
                        $node->removeChild($child);
                    }
                    continue;
                }
                foreach (iterator_to_array($child->attributes) as $attr) {
                    if (!in_array($attr->name, $allowed[$tag], true)) $child->removeAttribute($attr->name);
                }
                foreach (['href', 'src'] as $a) {
                    if ($child->hasAttribute($a)) {
                        $v = trim($child->getAttribute($a));
                        $ok = $a === 'href' ? preg_match('~^(https?://|mailto:|tel:|/|#)~i', $v) : preg_match('~^(https?://|/)~i', $v);
                        if (!$ok) $child->removeAttribute($a);
                    }
                }
                if ($tag === 'a' && preg_match('~^https?://~i', $child->getAttribute('href'))) {
                    $child->setAttribute('target', '_blank');
                    $child->setAttribute('rel', 'noopener');
                }
                if ($tag === 'img') {
                    if (!$child->hasAttribute('src')) { $node->removeChild($child); continue; }
                    $child->setAttribute('loading', 'lazy');
                }
            } elseif ($child instanceof DOMComment) {
                $node->removeChild($child);
            }
        }
    };
    $walk($root);
    $out = '';
    foreach ($root->childNodes as $c) $out .= $doc->saveHTML($c);
    return trim(preg_replace('~<p>(\s|&nbsp;|<br>)*</p>~', '', $out));
}

function reading_time(string $html): int
{
    return max(1, (int)round(str_word_count(strip_tags($html)) / 220));
}

/* ── Image uploads & media library ────────────────────────── */

function register_media(PDO $pdo, string $path, string $name, ?int $userId = null): void
{
    $full = ROOT_DIR . '/' . $path;
    $size = @getimagesize($full) ?: [0, 0];
    $pdo->prepare('INSERT OR IGNORE INTO media (path, name, size, width, height, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$path, mb_substr($name, 0, 120), (int)@filesize($full), $size[0], $size[1], $userId, date('Y-m-d H:i:s', (int)@filemtime($full) ?: time())]);
}

/**
 * Store an uploaded image under uploads/ and add it to the media library. Resized to $maxW and
 * converted to WebP when GD supports it. Returns the relative path, or throws a user-facing message.
 */
function store_upload(array $file, int $maxW = 1600): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
            ? 'Chobi ta onek boro. 8MB er niche din.' : 'Upload hoy nai, abar try korun.');
    }
    if ($file['size'] > 8 * 1024 * 1024) throw new RuntimeException('Chobi ta onek boro. 8MB er niche din.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$mime] ?? null;
    if (!$ext || !@getimagesize($file['tmp_name'])) throw new RuntimeException('Shudhu JPG, PNG, WebP ba GIF chobi dewa jabe.');

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0775, true);
    $name = date('Ym') . '-' . bin2hex(random_bytes(6));
    $path = null;

    if ($ext !== 'gif' && function_exists('imagewebp') && function_exists('imagecreatefromstring')) {
        $img = @imagecreatefromstring((string)file_get_contents($file['tmp_name']));
        if ($img) {
            $w = imagesx($img); $h = imagesy($img);
            if ($w > $maxW) {
                $nh = (int)round($h * $maxW / $w);
                $dst = imagecreatetruecolor($maxW, $nh);
                imagealphablending($dst, false); imagesavealpha($dst, true);
                imagecopyresampled($dst, $img, 0, 0, 0, 0, $maxW, $nh, $w, $h);
                imagedestroy($img); $img = $dst;
            } else {
                imagepalettetotruecolor($img); imagealphablending($img, false); imagesavealpha($img, true);
            }
            if (imagewebp($img, UPLOAD_DIR . "/$name.webp", 82)) $path = "uploads/$name.webp";
            imagedestroy($img);
        }
    }
    if (!$path) {
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . "/$name.$ext")) throw new RuntimeException('Upload save hoy nai — uploads folder writable kina dekhun.');
        $path = "uploads/$name.$ext";
    }
    register_media(db(), $path, (string)($file['name'] ?? basename($path)), $GLOBALS['ACTOR']['id'] ?? null);
    return $path;
}

/** Where a library file is used (settings, collections, blog). */
function media_usage(string $path): array
{
    $used = [];
    $st = db()->prepare('SELECT key FROM settings WHERE value = ?');
    $st->execute([$path]);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $k) $used[] = 'Settings: ' . $k;
    foreach (COLLECTIONS as $key => $c) {
        foreach ($c['fields'] as $f => $def) {
            if ($def['type'] !== 'image') continue;
            $st = db()->prepare("SELECT COUNT(*) FROM c_$key WHERE \"$f\" = ?");
            $st->execute([$path]);
            if ($st->fetchColumn()) $used[] = $c['label'];
        }
    }
    $st = db()->prepare('SELECT title FROM posts WHERE cover = ? OR body LIKE ?');
    $st->execute([$path, '%' . $path . '%']);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $t) $used[] = 'Blog: ' . $t;
    return array_values(array_unique($used));
}

function delete_media(string $path): void
{
    if (preg_match('~^uploads/[\w.-]+$~', $path) && is_file(ROOT_DIR . '/' . $path)) unlink(ROOT_DIR . '/' . $path);
    db()->prepare('DELETE FROM media WHERE path = ?')->execute([$path]);
}
