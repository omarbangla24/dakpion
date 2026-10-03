<?php
/* One page for every collection: list, add, edit, delete, reorder, show/hide. */
require __DIR__ . '/_admin.php';
require_login();

$key = $_GET['c'] ?? '';
if (!isset(COLLECTIONS[$key])) redirect('index.php');
$col = COLLECTIONS[$key];
$table = 'c_' . $key;
$self = 'items.php?c=' . $key;

function find_row(string $table, int $id): ?array
{
    $st = db()->prepare("SELECT * FROM $table WHERE id = ?");
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'save') {
        $old = $id ? find_row($table, $id) : [];
        if ($id && !$old) redirect($self);
        try {
            $orphans = [];
            $vals = collect_fields($col['fields'], $old ?: [], $orphans);
            if ($id) {
                $set = implode(', ', array_map(fn($c) => '"' . $c . '" = ?', array_keys($vals)));
                db()->prepare("UPDATE $table SET $set WHERE id = ?")->execute([...array_values($vals), $id]);
            } else {
                $vals['sort'] = (int)db()->query("SELECT COALESCE(MAX(sort), 0) + 1 FROM $table")->fetchColumn();
                insert_row(db(), $table, $vals);
            }
            array_map('delete_upload', $orphans);
            flash($col['singular'] . ' saved.');
            redirect($self);
        } catch (RuntimeException $ex) {
            flash($ex->getMessage(), 'err');
            $draft = array_merge($old ?: [], array_intersect_key($_POST['f'] ?? [], $col['fields']));
            foreach ($draft as $k => $v) if (is_array($v)) $draft[$k] = implode(',', array_filter($v));
            $_GET[$id ? 'edit' : 'new'] = $id ?: 1;
        }
    } elseif ($row = find_row($table, $id)) {
        if ($action === 'delete') {
            db()->prepare("DELETE FROM $table WHERE id = ?")->execute([$id]);
            foreach ($col['fields'] as $k => $def) if ($def['type'] === 'image') delete_upload($row[$k]);
            flash($col['singular'] . ' deleted.');
        } elseif ($action === 'toggle') {
            db()->prepare("UPDATE $table SET active = 1 - active WHERE id = ?")->execute([$id]);
        } elseif ($action === 'up' || $action === 'down') {
            $ids = db()->query("SELECT id FROM $table ORDER BY sort, id")->fetchAll(PDO::FETCH_COLUMN);
            $i = array_search($id, $ids);
            $j = $action === 'up' ? $i - 1 : $i + 1;
            if (isset($ids[$j])) {
                [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
                $st = db()->prepare("UPDATE $table SET sort = ? WHERE id = ?");
                db()->beginTransaction();
                foreach ($ids as $n => $rid) $st->execute([$n + 1, $rid]);
                db()->commit();
            }
        }
        redirect($self);
    }
}

/* ── Edit / new form ── */
if (isset($_GET['edit']) || isset($_GET['new'])) {
    $id = (int)($_GET['edit'] ?? 0);
    $row = $id ? find_row($table, $id) : [];
    if ($id && !$row) redirect($self);
    $row = array_merge($row ?: [], $draft ?? []);
    admin_head(($id ? 'Edit ' : 'Add ') . strtolower($col['singular']), 'c-' . $key);
    ?>
<p><a href="<?= $self ?>">← <?= e($col['label']) ?></a></p>
<form class="card form" method="post" enctype="multipart/form-data">
  <?= csrf() ?>
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= $id ?>">
<?php foreach ($col['fields'] as $k => $def) echo render_field($k, $def, $row[$k] ?? ''); ?>
  <div class="actions"><button class="btn btn--main" type="submit">Save</button><a class="btn" href="<?= $self ?>">Cancel</a></div>
</form>
<?php
    admin_foot();
    exit;
}

/* ── List ── */
$rows = db()->query("SELECT * FROM $table ORDER BY sort, id")->fetchAll();
$linkCol = current(array_filter($col['list'], fn($f) => $col['fields'][$f]['type'] !== 'image'));
admin_head($col['label'], 'c-' . $key);
?>
<div class="bar">
  <p class="muted"><?= count($rows) ?> <?= count($rows) === 1 ? 'item' : 'items' ?> · arrow diye order bodlan, chokh icon diye site theke lukan.</p>
  <a class="btn btn--main" href="<?= $self ?>&amp;new=1">+ Add <?= e(strtolower($col['singular'])) ?></a>
</div>
<?php if (!$rows): ?>
<div class="card empty"><p>Ekhono kichu nai.</p><a class="btn btn--main" href="<?= $self ?>&amp;new=1">+ Add <?= e(strtolower($col['singular'])) ?></a></div>
<?php else: ?>
<div class="card table-wrap">
<table class="table">
  <thead><tr><th class="ord">Order</th><?php foreach ($col['list'] as $f): ?><th><?= e(preg_replace('/\s*[(—].*$/u', '', $col['fields'][$f]['label'])) ?></th><?php endforeach; ?><th class="act"></th></tr></thead>
  <tbody>
<?php foreach ($rows as $i => $r): $btn = fn($a, $label, $title, $cls = '') => '<form method="post">' . csrf() . '<input type="hidden" name="action" value="' . $a . '"><input type="hidden" name="id" value="' . $r['id'] . '"><button class="ib ' . $cls . '" title="' . $title . '"' . ($a === 'delete' ? ' data-confirm="Delete korben? Eta ar ferot ana jabe na."' : '') . '>' . $label . '</button></form>'; ?>
    <tr class="<?= $r['active'] ? '' : 'off' ?>">
      <td class="ord"><?= $i ? $btn('up', aicon('up'), 'Move up') : '<span class="ib ghost"></span>' ?><?= $i < count($rows) - 1 ? $btn('down', aicon('down'), 'Move down') : '' ?></td>
<?php foreach ($col['list'] as $f): ?>
      <td><?= $f === $linkCol ? '<a class="rowlink" href="' . $self . '&amp;edit=' . $r['id'] . '">' . (cell($col['fields'][$f], $r[$f]) ?: '(blank)') . '</a>' : cell($col['fields'][$f], $r[$f]) ?></td>
<?php endforeach; ?>
      <td class="act"><?= $btn('toggle', aicon($r['active'] ? 'eye' : 'eye-off'), $r['active'] ? 'Visible — click to hide' : 'Hidden — click to show') ?><a class="ib" href="<?= $self ?>&amp;edit=<?= $r['id'] ?>" title="Edit"><?= aicon('edit') ?></a><?= $btn('delete', aicon('trash'), 'Delete', 'danger') ?></td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif;
admin_foot();
