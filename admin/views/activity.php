<?php
defined('ADMIN') || exit;
require_admin();

const LOG_PER_PAGE = 50;
$user = (string)($_GET['user'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$where = $user !== '' ? 'WHERE username = ?' : '';
$args = $user !== '' ? [$user] : [];

$count = db()->prepare("SELECT COUNT(*) FROM activity $where");
$count->execute($args);
$pages = max(1, (int)ceil($count->fetchColumn() / LOG_PER_PAGE));
$st = db()->prepare("SELECT * FROM activity $where ORDER BY id DESC LIMIT " . LOG_PER_PAGE . ' OFFSET ' . (($page - 1) * LOG_PER_PAGE));
$st->execute($args);
$rows = $st->fetchAll();
$names = db()->query("SELECT DISTINCT username FROM activity WHERE username <> '' ORDER BY username")->fetchAll(PDO::FETCH_COLUMN);

admin_head('Activity log', 'activity');
?>
<div class="toolbar">
  <form class="row" method="get">
    <select name="user" onchange="this.form.submit()" aria-label="Filter by user"><option value="">All users</option><?php foreach ($names as $n): ?><option<?= $n === $user ? ' selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select>
  </form>
  <p class="muted">Ke kokhon ki change korse — login, save, delete, upload shob.</p>
</div>
<?php if (!$rows): ?>
<div class="card empty"><p class="muted">Ekhono kono activity nai.</p></div>
<?php else: ?>
<div class="card table-wrap">
<table class="table">
  <thead><tr><th>When</th><th>User</th><th>Action</th><th>Item</th><th>Details</th></tr></thead>
  <tbody>
<?php foreach ($rows as $r): ?>
    <tr>
      <td class="nowrap" title="<?= e($r['created_at']) ?>"><?= e(ago($r['created_at'])) ?></td>
      <td><?= $r['username'] !== '' ? e($r['username']) : '<span class="muted">system</span>' ?></td>
      <td><b><?= e($r['action']) ?></b></td>
      <td><?= e($r['target']) ?></td>
      <td class="muted"><?= e($r['details']) ?></td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
</div>
<?= pager($page, $pages, fn($n) => aurl('activity') . '?' . http_build_query(array_filter(['user' => $user, 'page' => $n > 1 ? $n : null]))) ?>
<?php endif;
admin_foot();
