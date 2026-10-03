<?php
defined('ADMIN') || exit;

$sub = $seg[1] ?? '';

// JSON for the picker modal (image fields and the blog editor)
if ($sub === 'list') {
    $q = trim((string)($_GET['q'] ?? ''));
    $st = db()->prepare('SELECT path, name, width, height FROM media WHERE name LIKE ? OR path LIKE ? ORDER BY id DESC LIMIT 300');
    $st->execute(["%$q%", "%$q%"]);
    json_out(['items' => array_map(fn($m) => $m + ['url' => media_url($m['path'])], $st->fetchAll())]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'upload') {
        try {
            $path = store_upload($_FILES['file'] ?? []);
            log_activity('Uploaded image', (string)($_FILES['file']['name'] ?? $path));
            $st = db()->prepare('SELECT path, name, width, height, size FROM media WHERE path = ?');
            $st->execute([$path]);
            json_out(['ok' => true, 'item' => ($st->fetch() ?: ['path' => $path]) + ['url' => media_url($path)]]);
        } catch (RuntimeException $ex) {
            json_out(['ok' => false, 'error' => $ex->getMessage()], 422);
        }
    }
    if ($action === 'rename') {
        db()->prepare('UPDATE media SET name = ? WHERE path = ?')->execute([mb_substr(trim((string)($_POST['name'] ?? '')), 0, 120), (string)($_POST['path'] ?? '')]);
        json_out(['ok' => true]);
    }
    if ($action === 'delete') {
        $path = (string)($_POST['path'] ?? '');
        $used = media_usage($path);
        if ($used && empty($_POST['force'])) {
            flash('Eta use hocche: ' . implode(', ', $used) . '. Age oikhan theke shoran, ba abar Delete chap din.', 'err');
            $_SESSION['force_delete'] = $path;
        } else {
            if ($used && ($_SESSION['force_delete'] ?? '') !== $path) redirect(aurl('media'));
            delete_media($path);
            unset($_SESSION['force_delete']);
            log_activity('Deleted image', $path);
            flash('Image deleted.');
        }
        redirect(aurl('media') . (isset($_GET['q']) ? '?q=' . rawurlencode((string)$_GET['q']) : ''));
    }
}

const MEDIA_PER_PAGE = 48;
$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$count = db()->prepare('SELECT COUNT(*), COALESCE(SUM(size), 0) FROM media WHERE name LIKE ? OR path LIKE ?');
$count->execute(["%$q%", "%$q%"]);
[$total, $bytes] = $count->fetch(PDO::FETCH_NUM);
$pages = max(1, (int)ceil($total / MEDIA_PER_PAGE));
$st = db()->prepare('SELECT * FROM media WHERE name LIKE ? OR path LIKE ? ORDER BY id DESC LIMIT ' . MEDIA_PER_PAGE . ' OFFSET ' . (($page - 1) * MEDIA_PER_PAGE));
$st->execute(["%$q%", "%$q%"]);
$items = $st->fetchAll();
$force = $_SESSION['force_delete'] ?? '';

admin_head('Media library', 'media');
?>
<label class="dropzone" data-dropzone>
  <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden>
  <?= aicon('upload', 26) ?>
  <b>Chobi ekhane drag kore chharun, ba click kore choose korun</b>
  <span class="muted">JPG, PNG, WebP, GIF · max 8MB · boro chobi auto choto hoye WebP hobe</span>
  <span class="dz-progress" hidden></span>
</label>

<div class="toolbar">
  <form method="get" class="search"><?= aicon('search', 16) ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search images…" aria-label="Search"></form>
  <p class="muted"><?= (int)$total ?> images · <?= $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : max(1, round($bytes / 1024)) . ' KB' ?></p>
</div>

<?php if (!$items): ?>
<div class="card empty"><p class="muted"><?= $q !== '' ? 'Kichu pawa jay nai.' : 'Ekhono kono chobi nai. Upore drag kore upload korun.' ?></p></div>
<?php else: ?>
<div class="media-grid">
<?php foreach ($items as $m): $u = media_url($m['path']); ?>
  <figure class="media-item">
    <a href="<?= e($u) ?>" target="_blank" rel="noopener" class="media-thumb"><img src="<?= e($u) ?>" alt="" loading="lazy"></a>
    <figcaption>
      <b title="<?= e($m['name']) ?>"><?= e($m['name'] ?: basename($m['path'])) ?></b>
      <small><?= (int)$m['width'] ?>×<?= (int)$m['height'] ?> · <?= max(1, round($m['size'] / 1024)) ?> KB</small>
      <span class="media-actions">
        <button type="button" class="ib" data-copy="<?= e(abs_url($m['path'])) ?>" title="Copy link" aria-label="Copy link"><?= aicon('copy', 16) ?></button>
        <form method="post" action="<?= aurl('media') . ($q !== '' ? '?q=' . rawurlencode($q) : '') ?>"><?= csrf() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="path" value="<?= e($m['path']) ?>"><?php if ($force === $m['path']): ?><input type="hidden" name="force" value="1"><?php endif; ?><button class="ib danger<?= $force === $m['path'] ? ' armed' : '' ?>" title="Delete" aria-label="Delete" data-confirm="<?= $force === $m['path'] ? 'Use hocche — tobuo delete korben?' : 'Chobi ta delete korben?' ?>"><?= aicon('trash', 16) ?></button></form>
      </span>
    </figcaption>
  </figure>
<?php endforeach; ?>
</div>
<?= pager($page, $pages, fn($n) => aurl('media') . '?' . http_build_query(array_filter(['q' => $q, 'page' => $n > 1 ? $n : null]))) ?>
<?php endif;
admin_foot();
