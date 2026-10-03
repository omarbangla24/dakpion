<?php
/* Contact-form submissions: inbox, detail view, CSV export. */
require __DIR__ . '/_admin.php';
require_login();

const LEAD_FIELDS = ['name' => 'Name', 'company' => 'Company', 'email' => 'Email', 'phone' => 'Phone', 'services' => 'Needs',
    'industry' => 'Industry', 'budget' => 'Budget', 'message' => 'Message'];

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="messages-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // Excel reads UTF-8 (৳, Bangla) only with a BOM
    fputcsv($out, ['Date', ...array_values(LEAD_FIELDS)]);
    foreach (db()->query('SELECT * FROM leads ORDER BY id DESC') as $l) {
        // Leading = + - @ would run as a formula in Excel
        $row = array_map(fn($v) => preg_match('/^[=+\-@]/', (string)$v) ? "'" . $v : $v, [$l['created_at'], ...array_map(fn($k) => $l[$k], array_keys(LEAD_FIELDS))]);
        fputcsv($out, $row);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id = (int)($_POST['id'] ?? 0);
    match ($_POST['action'] ?? '') {
        'delete' => db()->prepare('DELETE FROM leads WHERE id = ?')->execute([$id]),
        'unread' => db()->prepare('UPDATE leads SET is_read = 0 WHERE id = ?')->execute([$id]),
        'read_all' => db()->exec('UPDATE leads SET is_read = 1'),
        default => null,
    };
    if (($_POST['action'] ?? '') === 'delete') flash('Message deleted.');
    redirect('leads.php');
}

if ($id = (int)($_GET['id'] ?? 0)) {
    $st = db()->prepare('SELECT * FROM leads WHERE id = ?');
    $st->execute([$id]);
    $l = $st->fetch();
    if (!$l) redirect('leads.php');
    db()->prepare('UPDATE leads SET is_read = 1 WHERE id = ?')->execute([$id]);
    admin_head('Message from ' . $l['name'], 'leads');
    $wa = preg_replace('/\D/', '', $l['phone']);
    if (str_starts_with($wa, '01')) $wa = '88' . $wa;
    ?>
<p><a href="leads.php">← All messages</a></p>
<section class="card lead">
  <p class="muted"><?= e(date('l, d F Y · g:i a', strtotime($l['created_at']))) ?></p>
  <dl>
<?php foreach (LEAD_FIELDS as $k => $label): if ($l[$k] === '' || $l[$k] === null) continue; ?>
    <dt><?= $label ?></dt><dd><?= $k === 'email' ? '<a href="mailto:' . e($l[$k]) . '">' . e($l[$k]) . '</a>' : ($k === 'phone' ? '<a href="' . e(tel_href($l[$k])) . '">' . e($l[$k]) . '</a>' : e($l[$k])) ?></dd>
<?php endforeach; ?>
  </dl>
  <div class="actions">
    <a class="btn btn--main" href="mailto:<?= e($l['email']) ?>?subject=<?= rawurlencode('Re: your enquiry — ' . setting('site_name', 'Dakpion IMC')) ?>">Reply by email</a>
<?php if (strlen($wa) >= 10): ?>    <a class="btn" href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener">WhatsApp</a>
<?php endif; ?>
    <form method="post"><?= csrf() ?><input type="hidden" name="id" value="<?= $id ?>"><button class="btn" name="action" value="unread">Mark unread</button></form>
    <form method="post"><?= csrf() ?><input type="hidden" name="id" value="<?= $id ?>"><button class="btn danger" name="action" value="delete" data-confirm="Message ta delete korben?">Delete</button></form>
  </div>
</section>
<?php
    admin_foot();
    exit;
}

$rows = db()->query('SELECT * FROM leads ORDER BY id DESC')->fetchAll();
admin_head('Messages', 'leads');
?>
<div class="bar">
  <p class="muted">Website er contact form theke asha sob message.</p>
  <div class="row">
<?php if (unread_leads()): ?>    <form method="post"><?= csrf() ?><button class="btn" name="action" value="read_all">Mark all read</button></form>
<?php endif; if ($rows): ?>    <a class="btn" href="leads.php?export=1">Download CSV</a>
<?php endif; ?>
  </div>
</div>
<?php if (!$rows): ?>
<div class="card empty"><p>Ekhono kono message nai.</p></div>
<?php else: ?>
<div class="card table-wrap">
<table class="table leads">
  <thead><tr><th>Name</th><th>Company</th><th>Needs</th><th>Budget</th><th>Date</th></tr></thead>
  <tbody>
<?php foreach ($rows as $l): ?>
    <tr class="<?= $l['is_read'] ? '' : 'new' ?>">
      <td><a class="rowlink" href="leads.php?id=<?= (int)$l['id'] ?>"><?= e($l['name']) ?></a><small><?= e($l['email']) ?></small></td>
      <td><?= e($l['company']) ?></td>
      <td><?= e(mb_strimwidth((string)$l['services'], 0, 50, '…')) ?></td>
      <td><?= e($l['budget']) ?></td>
      <td class="nowrap"><?= e(date('d M Y, g:i a', strtotime($l['created_at']))) ?></td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif;
admin_foot();
