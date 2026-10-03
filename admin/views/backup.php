<?php
defined('ADMIN') || exit;
require_admin();

function dir_size(string $dir): array
{
    $n = 0; $bytes = 0;
    foreach (glob($dir . '/*') ?: [] as $f) if (is_file($f) && basename($f) !== '.htaccess') { $n++; $bytes += filesize($f); }
    return [$n, $bytes];
}

function human_size(int $b): string
{
    return $b >= 1048576 ? round($b / 1048576, 1) . ' MB' : max(1, round($b / 1024)) . ' KB';
}

/** Consistent copy of the live database (safe while the site is in use). */
function snapshot_db(): string
{
    $tmp = tempnam(sys_get_temp_dir(), 'dkdb');
    unlink($tmp);
    try {
        db()->exec('VACUUM INTO ' . db()->quote($tmp));
    } catch (PDOException) {
        copy(DATA_DIR . '/site.sqlite', $tmp);
    }
    return $tmp;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    set_time_limit(300);
    $stamp = date('Y-m-d-Hi');
    $db = snapshot_db();
    $type = $_POST['type'] ?? 'full';
    if ($type === 'db' || !class_exists('ZipArchive')) {
        log_activity('Downloaded backup', 'database');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="dakpion-database-' . $stamp . '.sqlite"');
        header('Content-Length: ' . filesize($db));
        readfile($db);
        unlink($db);
        exit;
    }
    $zipPath = tempnam(sys_get_temp_dir(), 'dkzip');
    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::OVERWRITE);
    $zip->addFile($db, 'data/site.sqlite');
    foreach (glob(UPLOAD_DIR . '/*') ?: [] as $f) if (is_file($f) && basename($f) !== '.htaccess') $zip->addFile($f, 'uploads/' . basename($f));
    $zip->addFromString('README.txt', "Dakpion website backup — $stamp\n\nRestore: upload data/site.sqlite into the site's data/ folder and\nthe uploads/ files into uploads/, replacing what is there.\n");
    $zip->close();
    unlink($db);
    log_activity('Downloaded backup', 'full (database + images)');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="dakpion-backup-' . $stamp . '.zip"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);
    unlink($zipPath);
    exit;
}

[$files, $bytes] = dir_size(UPLOAD_DIR);
$dbSize = (int)@filesize(DATA_DIR . '/site.sqlite');
$last = db()->query("SELECT created_at, username FROM activity WHERE action = 'Downloaded backup' ORDER BY id DESC LIMIT 1")->fetch();

admin_head('Backup', 'backup');
?>
<div class="grid2">
  <section class="card">
    <h2>Full backup</h2>
    <p class="muted">Database (shob text, settings, messages, users, blog) + shob chobi, ekta .zip file e.</p>
    <dl class="facts"><dt>Database</dt><dd><?= human_size($dbSize) ?></dd><dt>Images</dt><dd><?= $files ?> files · <?= human_size($bytes) ?></dd><dt>Last backup</dt><dd><?= $last ? e(ago($last['created_at'])) . ' · ' . e($last['username']) : 'Never' ?></dd></dl>
    <form method="post"><?= csrf() ?><input type="hidden" name="type" value="full"><button class="btn btn--main"><?= aicon('download', 16) ?>Download full backup<?= class_exists('ZipArchive') ? '' : ' (database only — server e zip nai)' ?></button></form>
  </section>
  <section class="card">
    <h2>Database only</h2>
    <p class="muted">Choto file — shudhu text, settings ar messages. Chobi nai.</p>
    <form method="post"><?= csrf() ?><input type="hidden" name="type" value="db"><button class="btn"><?= aicon('download', 16) ?>Download database</button></form>
    <h2 class="mt">Restore korte</h2>
    <p class="muted">Zip ta khule <code>data/site.sqlite</code> ar <code>uploads/</code> folder hosting er File Manager diye site e replace korun.</p>
  </section>
</div>
<p class="muted small">Tip: mashe ekbar backup niye Google Drive e rakhun.</p>
<?php admin_foot();
