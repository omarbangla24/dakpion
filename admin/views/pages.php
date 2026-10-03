<?php
/* Text and SEO of each public page (stored in settings as "<page>.<field>"). */
defined('ADMIN') || exit;

$key = $seg[1] ?? 'home';
if (!isset(PAGES[$key])) redirect(aurl('pages'));
$page = PAGES[$key];

$fields = [];
foreach ($page['fields'] as $f => [$type, $label, $default]) {
    $fields[$f] = ['type' => $type, 'label' => $label, 'default' => $default];
    if ($f === 'seo_title') $fields[$f]['attrs'] = ' maxlength="90" data-seo-title';
    if ($f === 'seo_desc') $fields[$f]['attrs'] = ' maxlength="200" data-seo-desc';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = $_POST['f'] ?? [];
    $set = db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value');
    $del = db()->prepare('DELETE FROM settings WHERE key = ?');
    $changed = [];
    foreach ($fields as $f => $def) {
        $v = trim(str_replace("\r\n", "\n", (string)($in[$f] ?? '')));
        if ($def['type'] === 'tags') $v = implode(', ', tags($v));
        if ($v !== t($key, $f)) $changed[] = $def['label'];
        // Same as the default (or empty) → store nothing, so the page keeps using the default.
        if ($v === '' || $v === $def['default']) $del->execute(["$key.$f"]);
        else $set->execute(["$key.$f", $v]);
    }
    if ($changed) log_activity('Edited page text', $page['label'], implode(', ', array_slice($changed, 0, 6)) . (count($changed) > 6 ? '…' : ''));
    flash($page['label'] . ' saved.');
    redirect(aurl('pages/' . $key));
}

$view = isset($page['path']) ? url($page['path']) : url();
admin_head('Pages & SEO', 'pages', [], '<a class="btn btn--ghost" href="' . e($view) . '" target="_blank" rel="noopener">' . aicon('eye', 16) . '<span>View ' . e($page['label']) . '</span></a>');
?>
<nav class="tabs"><?php foreach (PAGES as $k => $p): ?><a href="<?= aurl('pages/' . $k) ?>"<?= $k === $key ? ' aria-current="page"' : '' ?>><?= e($p['label']) ?></a><?php endforeach; ?></nav>

<form method="post" class="split-form" data-dirty>
  <?= csrf() ?>
  <div class="card form form-grid">
<?php foreach ($fields as $f => $def): if (str_starts_with($f, 'seo_')) continue; ?>
    <?= render_field($f, $def, t($key, $f)) ?>
<?php endforeach; ?>
    <div class="actions wide sticky-actions"><button class="btn btn--main" type="submit">Save <?= e(strtolower($page['label'])) ?></button><span class="muted small">Khali rakhle default text dekhabe.</span></div>
  </div>
<?php if (isset($fields['seo_title'])): ?>
  <aside class="card side-card">
    <h2>Google preview</h2>
    <div class="serp" data-serp>
      <span class="serp-url"><?= e(preg_replace('~^https?://~', '', abs_url($page['path'] ?? ''))) ?></span>
      <b class="serp-title" data-serp-title><?= e(t($key, 'seo_title')) ?></b>
      <span class="serp-desc" data-serp-desc><?= e(t($key, 'seo_desc')) ?></span>
    </div>
    <?= render_field('seo_title', $fields['seo_title'], t($key, 'seo_title')) ?>
    <?= render_field('seo_desc', $fields['seo_desc'], t($key, 'seo_desc')) ?>
    <p class="muted small">Title 50–60 character, description 120–160 character holey Google e pura dekhay.</p>
  </aside>
<?php endif; ?>
</form>
<?php admin_foot();
