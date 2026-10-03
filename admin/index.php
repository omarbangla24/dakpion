<?php
require __DIR__ . '/_admin.php';
require_login();
require_once __DIR__ . '/../inc/seed.php';

$changed = fn(string $k) => setting($k) !== '' && setting($k) !== (SEED_SETTINGS[$k] ?? '');
$checks = [
    ['Phone number', !is_placeholder(setting('phone')), 'settings.php?tab=contact'],
    ['Office address', $changed('address'), 'settings.php?tab=contact'],
    ['WhatsApp number', !is_placeholder(setting('whatsapp')), 'settings.php?tab=contact'],
    ['Form message kon email e jabe', setting('notify_email') !== '', 'settings.php?tab=form'],
    ['Social media links', (bool)array_filter(array_map('setting', ['facebook', 'instagram', 'linkedin', 'youtube', 'tiktok'])), 'settings.php?tab=social'],
    ['Team er naam ar chobi', (bool)db()->query("SELECT COUNT(*) FROM c_team WHERE name <> '' AND photo <> ''")->fetchColumn(), 'items.php?c=team'],
    ['Ashol testimonials', !db()->query("SELECT COUNT(*) FROM c_testimonials WHERE active = 1 AND name = 'Client name'")->fetchColumn(), 'items.php?c=testimonials'],
    ['Portfolio te chobi', (bool)db()->query("SELECT COUNT(*) FROM c_projects WHERE image <> ''")->fetchColumn(), 'items.php?c=projects'],
    ['Client logos', (bool)db()->query('SELECT COUNT(*) FROM c_clients')->fetchColumn(), 'items.php?c=clients'],
    ['Google Analytics / Meta Pixel', setting('ga_id') !== '' || setting('pixel_id') !== '', 'settings.php?tab=seo'],
    ['"Placeholder" note off', setting('show_notes') !== '1', 'settings.php?tab=seo'],
];
$done = count(array_filter(array_column($checks, 1)));
$leads = db()->query('SELECT * FROM leads ORDER BY id DESC LIMIT 6')->fetchAll();
$total = (int)db()->query('SELECT COUNT(*) FROM leads')->fetchColumn();
$week = db()->prepare('SELECT COUNT(*) FROM leads WHERE created_at > ?');
$week->execute([date('Y-m-d H:i:s', time() - 7 * 86400)]);

admin_head('Dashboard', 'dash');
?>
<div class="cards">
  <a class="card stat" href="leads.php"><b><?= unread_leads() ?></b><span>Unread messages</span></a>
  <div class="card stat"><b><?= $week->fetchColumn() ?></b><span>Messages this week</span></div>
  <div class="card stat"><b><?= $total ?></b><span>Total messages</span></div>
  <div class="card stat"><b><?= $done ?>/<?= count($checks) ?></b><span>Launch checklist</span></div>
</div>

<div class="grid2">
  <section class="card">
    <h2>Launch checklist</h2>
    <ul class="checklist">
<?php foreach ($checks as [$label, $ok, $href]): ?>
      <li class="<?= $ok ? 'ok' : '' ?>"><span class="tick"><?= $ok ? '✓' : '' ?></span><span><?= e($label) ?></span><?php if (!$ok): ?><a href="<?= $href ?>">Add →</a><?php endif; ?></li>
<?php endforeach; ?>
    </ul>
  </section>
  <section class="card">
    <h2>Latest messages</h2>
<?php if (!$leads): ?>
    <p class="muted">Ekhono kono message ashe nai. Contact form theke message ashle ekhane dekhabe.</p>
<?php else: ?>
    <ul class="leadlist">
<?php foreach ($leads as $l): ?>
      <li class="<?= $l['is_read'] ? '' : 'new' ?>"><a href="leads.php?id=<?= (int)$l['id'] ?>"><b><?= e($l['name']) ?></b> · <?= e($l['company']) ?><small><?= e(date('d M, g:i a', strtotime($l['created_at']))) ?></small></a></li>
<?php endforeach; ?>
    </ul>
    <a class="btn" href="leads.php">All messages</a>
<?php endif; ?>
  </section>
</div>

<section class="card">
  <h2>Content</h2>
  <div class="tiles">
<?php foreach (ADMIN_MENU as $keys): foreach ($keys as $k): $n = db()->query("SELECT COUNT(*) FROM c_$k WHERE active = 1")->fetchColumn(); ?>
    <a class="tile" href="items.php?c=<?= $k ?>"><b><?= $n ?></b><span><?= e(COLLECTIONS[$k]['label']) ?></span></a>
<?php endforeach; endforeach; ?>
  </div>
</section>
<?php admin_foot();
