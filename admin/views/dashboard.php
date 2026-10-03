<?php
defined('ADMIN') || exit;
require_once ROOT_DIR . '/inc/seed.php';

/** Column chart (one series) as inline SVG; hover tooltips come from data-tip (admin.js). */
function column_chart(array $series, string $unit, string $label): string
{
    $w = 640; $h = 180; $padL = 30; $padB = 22; $padT = 8;
    $max = max(1, max($series));
    $step = (int)ceil($max / 4);
    $top = $step * 4;
    $n = count($series);
    $slot = ($w - $padL) / $n;
    $bw = max(3, min(18, $slot - 2));
    $svg = '<svg class="chart" viewBox="0 0 ' . $w . ' ' . ($h + $padB) . '" role="img" aria-label="' . e($label) . '">';
    for ($g = 0; $g <= 4; $g++) {
        $y = $padT + ($h - $padT) * (1 - $g / 4);
        $svg .= '<line x1="' . $padL . '" x2="' . $w . '" y1="' . $y . '" y2="' . $y . '" class="grid' . ($g ? '' : ' base') . '"/>'
            . '<text x="' . ($padL - 6) . '" y="' . ($y + 4) . '" class="ytick">' . ($step * $g) . '</text>';
    }
    $i = 0;
    foreach ($series as $day => $v) {
        $x = $padL + $slot * $i + ($slot - $bw) / 2;
        $bh = ($h - $padT) * $v / $top;
        $tip = date('D, d M', strtotime($day)) . ' — ' . $v . ' ' . $unit;
        if ($v > 0) {
            $r = min(4, $bw / 2, $bh);
            $y = $h - $bh;
            // rounded data end, square at the baseline
            $svg .= '<path class="bar" d="M' . round($x, 1) . ' ' . $h . 'V' . round($y + $r, 1) . 'q0 -' . $r . ' ' . $r . ' -' . $r . 'h' . round($bw - 2 * $r, 1) . 'q' . $r . ' 0 ' . $r . ' ' . $r . 'V' . $h . 'z"/>';
        }
        $svg .= '<rect class="hit" x="' . round($padL + $slot * $i, 1) . '" y="0" width="' . round($slot, 1) . '" height="' . $h . '" data-tip="' . e($tip) . '"/>';
        if (($n - 1 - $i) % 7 === 0) $svg .= '<text x="' . round($x + $bw / 2, 1) . '" y="' . ($h + 16) . '" class="xtick">' . date('d M', strtotime($day)) . '</text>';
        $i++;
    }
    return $svg . '</svg>';
}

/** Horizontal bars with the value written at the end (identity is the label, not color). */
function hbars(array $rows, string $empty): string
{
    if (!$rows) return '<p class="muted small">' . e($empty) . '</p>';
    $max = max(1, max($rows));
    $out = '<ul class="hbars">';
    foreach ($rows as $label => $v) {
        $out .= '<li><span class="hb-label">' . e((string)$label) . '</span><span class="hb-track"><i style="width:' . round($v / $max * 100, 1) . '%"></i></span><b>' . $v . '</b></li>';
    }
    return $out . '</ul>';
}

function change(int $now, int $before): string
{
    if (!$before) return $now ? '<span class="delta up">new</span>' : '';
    $pct = (int)round(($now - $before) / $before * 100);
    return '<span class="delta ' . ($pct >= 0 ? 'up' : 'down') . '">' . ($pct >= 0 ? '▲ ' : '▼ ') . abs($pct) . '%</span>';
}

$days = 30;
$from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
$prevFrom = date('Y-m-d', strtotime('-' . (2 * $days - 1) . ' days'));
$visits = $leads = array_fill_keys(array_map(fn($i) => date('Y-m-d', strtotime("-$i days")), range($days - 1, 0)), 0);

$st = db()->prepare('SELECT day, SUM(count) FROM views WHERE day >= ? GROUP BY day');
$st->execute([$from]);
foreach ($st->fetchAll(PDO::FETCH_KEY_PAIR) as $d => $n) if (isset($visits[$d])) $visits[$d] = (int)$n;
$st = db()->prepare('SELECT substr(created_at, 1, 10) d, COUNT(*) FROM leads WHERE created_at >= ? GROUP BY d');
$st->execute([$from]);
foreach ($st->fetchAll(PDO::FETCH_KEY_PAIR) as $d => $n) if (isset($leads[$d])) $leads[$d] = (int)$n;

$q = fn(string $sql, array $a) => (int)(($s = db()->prepare($sql)) && $s->execute($a) ? $s->fetchColumn() : 0);
$visitsNow = array_sum($visits);
$visitsPrev = $q('SELECT COALESCE(SUM(count), 0) FROM views WHERE day >= ? AND day < ?', [$prevFrom, $from]);
$leadsNow = array_sum($leads);
$leadsPrev = $q('SELECT COUNT(*) FROM leads WHERE created_at >= ? AND created_at < ?', [$prevFrom, $from]);
$conv = $visitsNow ? round($leadsNow / $visitsNow * 100, 1) : 0;
$pipeline = db()->query("SELECT COALESCE(NULLIF(status, ''), 'new') s, COUNT(*) n FROM leads GROUP BY s")->fetchAll(PDO::FETCH_KEY_PAIR);
$open = ($pipeline['new'] ?? 0) + ($pipeline['contacted'] ?? 0) + ($pipeline['proposal'] ?? 0);

$interest = [];
$st = db()->prepare('SELECT services FROM leads WHERE created_at >= ?');
$st->execute([date('Y-m-d', strtotime('-90 days'))]);
foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $s) foreach (array_filter(array_map('trim', explode(',', (string)$s))) as $svc) $interest[$svc] = ($interest[$svc] ?? 0) + 1;
arsort($interest);
$interest = array_slice($interest, 0, 8, true);

$stages = [];
foreach (LEAD_STATUSES as $k => $label) $stages[$label] = (int)($pipeline[$k] ?? 0);

$st = db()->prepare('SELECT path, SUM(count) n FROM views WHERE day >= ? GROUP BY path ORDER BY n DESC LIMIT 7');
$st->execute([$from]);
$top = $st->fetchAll(PDO::FETCH_KEY_PAIR);

$st = db()->prepare("SELECT * FROM leads WHERE follow_up IS NOT NULL AND follow_up <> '' AND follow_up <= ? AND COALESCE(status, 'new') NOT IN ('won', 'lost') ORDER BY follow_up LIMIT 6");
$st->execute([date('Y-m-d', strtotime('+2 days'))]);
$followups = $st->fetchAll();
$latest = db()->query('SELECT * FROM leads ORDER BY id DESC LIMIT 6')->fetchAll();

$changed = fn(string $k) => setting($k) !== '' && setting($k) !== (SEED_SETTINGS[$k] ?? '');
$checks = [
    ['Phone number', !is_placeholder(setting('phone')), 'settings/contact'],
    ['Office address', $changed('address'), 'settings/contact'],
    ['WhatsApp number', !is_placeholder(setting('whatsapp')), 'settings/contact'],
    ['Form message kon email e jabe', setting('notify_email') !== '', 'settings/form'],
    ['Social media links', (bool)array_filter(array_map('setting', ['facebook', 'instagram', 'linkedin', 'youtube', 'tiktok'])), 'settings/social'],
    ['Team er naam ar chobi', (bool)$q("SELECT COUNT(*) FROM c_team WHERE name <> '' AND photo <> ''", []), 'team'],
    ['Ashol testimonials', !$q("SELECT COUNT(*) FROM c_testimonials WHERE active = 1 AND name = 'Client name'", []), 'testimonials'],
    ['Portfolio te chobi', (bool)$q("SELECT COUNT(*) FROM c_projects WHERE image <> ''", []), 'projects'],
    ['Client logos', (bool)$q('SELECT COUNT(*) FROM c_clients', []), 'clients'],
    ['Prothom blog post', has_posts(), 'blog/new'],
    ['Google Analytics / Meta Pixel', setting('ga_id') !== '' || setting('pixel_id') !== '', 'settings/seo'],
    ['"Placeholder" note off', setting('show_notes') !== '1', 'settings/seo'],
];
$done = count(array_filter(array_column($checks, 1)));
$user = current_user();

admin_head('Dashboard', 'dash');
?>
<p class="hello">Hi <?= e(explode(' ', $user['name'] ?: $user['username'])[0]) ?> — last <?= $days ?> days at a glance.</p>

<div class="kpis">
  <div class="card kpi"><span>Website visits</span><b><?= number_format($visitsNow) ?></b><?= change($visitsNow, $visitsPrev) ?></div>
  <a class="card kpi" href="<?= aurl('messages') ?>"><span>New messages</span><b><?= $leadsNow ?></b><?= change($leadsNow, $leadsPrev) ?></a>
  <div class="card kpi"><span>Conversion rate</span><b><?= $conv ?>%</b><small>visit → message</small></div>
  <a class="card kpi" href="<?= aurl('messages') ?>"><span>Open pipeline</span><b><?= $open ?></b><small><?= (int)($pipeline['won'] ?? 0) ?> won · <?= (int)($pipeline['lost'] ?? 0) ?> lost</small></a>
</div>

<div class="grid2">
  <section class="card chart-card"><h2>Visits per day</h2><?= column_chart($visits, 'visits', 'Website visits per day, last 30 days') ?></section>
  <section class="card chart-card"><h2>Messages per day</h2><?= column_chart($leads, 'messages', 'Contact form messages per day, last 30 days') ?></section>
</div>

<div class="grid3">
  <section class="card"><h2>What people ask for <small>last 90 days</small></h2><?= hbars($interest, 'Form e service select korle ekhane dekhabe.') ?></section>
  <section class="card"><h2>Pipeline</h2><?= hbars(array_filter($stages) ?: [], 'Ekhono kono lead nai.') ?></section>
  <section class="card"><h2>Top pages <small>visits</small></h2><?= hbars($top, 'Visit count shuru hoyeche — kichu din por dekhabe.') ?></section>
</div>

<div class="grid2">
  <section class="card">
    <h2>Latest messages</h2>
<?php if (!$latest): ?>
    <p class="muted">Ekhono kono message ashe nai.</p>
<?php else: ?>
    <ul class="leadlist">
<?php foreach ($latest as $l): $s = $l['status'] ?: 'new'; ?>
      <li class="<?= $l['is_read'] ? '' : 'new' ?>"><a href="<?= aurl('messages/' . $l['id']) ?>"><span><b><?= e($l['name']) ?></b> · <?= e($l['company']) ?><small><?= e(ago($l['created_at'])) ?></small></span><span class="badge badge--<?= e($s) ?>"><?= e(LEAD_STATUSES[$s] ?? $s) ?></span></a></li>
<?php endforeach; ?>
    </ul>
<?php endif; if ($followups): ?>
    <h2 class="mt">Follow-ups due</h2>
    <ul class="leadlist">
<?php foreach ($followups as $l): ?>
      <li><a href="<?= aurl('messages/' . $l['id']) ?>"><span><b><?= e($l['name']) ?></b> · <?= e($l['company']) ?></span><span class="<?= $l['follow_up'] <= date('Y-m-d') ? 'overdue' : 'muted' ?>"><?= e(date('d M', strtotime($l['follow_up']))) ?></span></a></li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
  </section>
  <section class="card">
    <h2>Launch checklist <small><?= $done ?>/<?= count($checks) ?></small></h2>
    <div class="progressbar"><i style="width:<?= round($done / count($checks) * 100) ?>%"></i></div>
    <ul class="checklist">
<?php foreach ($checks as [$label, $ok, $href]): ?>
      <li class="<?= $ok ? 'ok' : '' ?>"><span class="tick"><?= $ok ? aicon('check', 13) : '' ?></span><span><?= e($label) ?></span><?php if (!$ok && (is_admin() || !str_starts_with($href, 'settings/') || $href === 'settings/popup')): ?><a href="<?= aurl($href) ?>">Add →</a><?php endif; ?></li>
<?php endforeach; ?>
    </ul>
  </section>
</div>
<div class="chart-tip" role="tooltip" hidden></div>
<?php admin_foot();
