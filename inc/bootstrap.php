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

function migrate(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE NOT NULL, pass_hash TEXT NOT NULL, created_at TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (ip TEXT, at INTEGER)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS leads (id INTEGER PRIMARY KEY AUTOINCREMENT, created_at TEXT, name TEXT, company TEXT,
        email TEXT, phone TEXT, services TEXT, industry TEXT, budget TEXT, message TEXT, ip TEXT, user_agent TEXT, is_read INTEGER DEFAULT 0)');

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
        $have = array_column($pdo->query("PRAGMA table_info($t)")->fetchAll(), 'name');
        foreach (array_keys($c['fields']) as $f) {
            if (!in_array($f, $have, true)) $pdo->exec("ALTER TABLE $t ADD COLUMN \"$f\" TEXT");
        }
    }

    if (!$pdo->query("SELECT 1 FROM settings WHERE key = '_seeded'")->fetchColumn()) {
        require_once __DIR__ . '/seed.php';
        $st = $pdo->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)');
        foreach (SEED_SETTINGS as $k => $v) $st->execute([$k, $v]);
        $st->execute(['_seeded', date('c')]);
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

/* ── Content helpers ──────────────────────────────────────── */

function setting(string $key, string $default = ''): string
{
    static $all = null;
    $all ??= db()->query('SELECT key, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    $v = $all[$key] ?? '';
    return $v !== '' ? $v : $default;
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

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escaped text where *words* become <em>words</em>. */
function rich(?string $s): string
{
    return preg_replace('/\*(.+?)\*/u', '<em>$1</em>', e($s));
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

function safe_url(?string $url): string
{
    $url = trim((string)$url);
    return preg_match('~^(https?://|mailto:|tel:|/|#|[a-z0-9-]+\.php)~i', $url) ? $url : '';
}

/* ── Image uploads ────────────────────────────────────────── */

/**
 * Store an uploaded image under uploads/. Resized to $maxW and converted to WebP when GD
 * supports it. Returns the relative path, or throws with a user-facing message.
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
            $ok = imagewebp($img, UPLOAD_DIR . "/$name.webp", 82);
            imagedestroy($img);
            if ($ok) return "uploads/$name.webp";
        }
    }
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . "/$name.$ext")) throw new RuntimeException('Upload save hoy nai — uploads folder writable kina dekhun.');
    return "uploads/$name.$ext";
}

function delete_upload(?string $path): void
{
    if ($path && preg_match('~^uploads/[\w.-]+$~', $path) && is_file(ROOT_DIR . '/' . $path)) unlink(ROOT_DIR . '/' . $path);
}
