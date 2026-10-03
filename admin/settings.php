<?php
require __DIR__ . '/_admin.php';
require_login();

$tab = $_GET['tab'] ?? 'contact';
if (!isset(SETTINGS[$tab])) redirect('settings.php?tab=contact');
$group = SETTINGS[$tab];

$current = [];
foreach ($group['fields'] as $k => $_) $current[$k] = setting($k);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    try {
        $orphans = [];
        $vals = collect_fields($group['fields'], $current, $orphans);
        $st = db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value');
        foreach ($vals as $k => $v) $st->execute([$k, $v]);
        array_map('delete_upload', $orphans);
        flash('Saved.');
        redirect('settings.php?tab=' . $tab);
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'err');
        $current = array_merge($current, array_intersect_key($_POST['f'] ?? [], $current));
    }
}

admin_head($group['label'], 'set-' . $tab);
?>
<form class="card form" method="post" enctype="multipart/form-data">
  <?= csrf() ?>
<?php foreach ($group['fields'] as $k => $def) echo render_field($k, $def, is_array($current[$k]) ? '' : $current[$k]); ?>
  <div class="actions"><button class="btn btn--main" type="submit">Save</button></div>
</form>
<?php admin_foot();
