<?php
defined('ADMIN') || exit;

$tab = $seg[1] ?? 'contact';
if (!isset(SETTINGS[$tab])) redirect(aurl('settings/contact'));
if ($tab !== 'popup') require_admin(); // editors may run offers, not change site settings
$group = SETTINGS[$tab];

$current = [];
foreach ($group['fields'] as $k => $_) $current[$k] = setting($k);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $vals = collect_fields($group['fields']);
        $st = db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value');
        $changed = [];
        foreach ($vals as $k => $v) {
            if ($v !== $current[$k]) $changed[] = $group['fields'][$k]['label'];
            $st->execute([$k, $v]);
        }
        if ($changed) log_activity('Updated settings', $group['label'], implode(', ', $changed));
        flash('Saved.');
        redirect(aurl('settings/' . $tab));
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'err');
        foreach ($_POST['f'] ?? [] as $k => $v) if (isset($current[$k]) && !is_array($v)) $current[$k] = $v;
    }
}

$preview = $tab === 'popup' ? '<a class="btn btn--ghost" href="' . url() . '" target="_blank" rel="noopener">' . aicon('eye', 16) . '<span>Preview</span></a>' : '';
admin_head($group['label'], 'set-' . $tab, $tab === 'popup' ? ['Website' => null] : ['Settings' => null]);
?>
<?php if (is_admin() && $tab !== 'popup'): ?>
<nav class="tabs"><?php foreach (SETTINGS as $k => $g): if ($k === 'popup') continue; ?><a href="<?= aurl('settings/' . $k) ?>"<?= $k === $tab ? ' aria-current="page"' : '' ?>><?= e($g['label']) ?></a><?php endforeach; ?></nav>
<?php endif; ?>
<?php if ($tab === 'popup'): ?>
<p class="muted intro">Top bar ta site er upore shob page e dekhay. Popup ta visitor ke ekta somoy por dekhay — ekbar bondho korle setting onujayi abar dekhabe. Text bodlale shobai notun kore dekhbe.</p>
<?php endif; ?>
<form class="card form form-grid" method="post" enctype="multipart/form-data">
  <?= csrf() ?>
<?php foreach ($group['fields'] as $k => $def) echo render_field($k, $def, $current[$k]); ?>
  <div class="actions wide"><button class="btn btn--main" type="submit">Save changes</button><?= $preview ?></div>
</form>
<?php admin_foot();
