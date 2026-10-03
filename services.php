<?php
require __DIR__ . '/inc/layout.php';

$services = items('services');
$jump = '<div class="jump" data-reveal>';
foreach ($services as $s) $jump .= '<a href="#' . e($s['slug']) . '">' . e($s['name']) . '</a>';
$jump .= '</div>';

page_start('services', 'Services — 360° Marketing Solutions | Dakpion IMC',
    'Brand strategy, creative, social media, performance marketing, SEO, video production, web, ATL media, PR and BTL activation — integrated marketing services by Dakpion IMC.');

page_hero('Services', 'Services', 'Every channel.<br>One <span class="hl">plan.</span>',
    'Strategy, creative, digital, production, media and activation — ' . num_word(count($services)) . ' disciplines working as one team, so your brand speaks with one voice everywhere.', $jump);
?>

<section class="section" style="padding-top:0">
  <div class="container">
<?php foreach ($services as $i => $s): ?>
    <div class="srow" id="<?= e($s['slug']) ?>">
      <div class="srow-l">
        <span class="n"><?= sprintf('%02d', $i + 1) ?></span>
        <h2 data-split><?= e($s['name']) ?></h2>
        <span class="ic" data-reveal><?= icon($s['icon'] ?: 'target') ?></span>
      </div>
      <div class="srow-r">
        <p class="lead" data-reveal><?= e($s['lead']) ?></p>
<?php if ($pts = lines($s['points'])): ?>
        <ul class="dlist" data-reveal><?php foreach ($pts as $pt): ?><li><?= e($pt) ?></li><?php endforeach; ?></ul>
<?php endif; if ($s['kpi1'] || $s['kpi2']): ?>
        <div class="kpis" data-reveal><?php foreach ([1, 2] as $n): if ($s["kpi$n"]): ?><div><b><?= e($s["kpi$n"]) ?></b><span><?= e($s["kpi{$n}_label"]) ?></span></div><?php endif; endforeach; ?></div>
<?php endif; ?>
        <a href="contact.php" class="btn btn--grey" data-reveal data-magnetic=".2"><?= e($s['cta'] ?: 'Get in touch') ?> <?= icon('arrow-ur') ?></a>
      </div>
    </div>
<?php endforeach; ?>
  </div>
</section>

<?php $pkgs = items('packages'); if ($pkgs): ?>
<section class="section section--paper round" id="packages" style="margin:0 clamp(8px,1.4vw,20px)">
  <div class="container">
    <div class="head">
      <div><div class="label" data-reveal>Engagement models</div><h2 data-split>Start small.<br>Go <span class="hl">360°.</span></h2></div>
      <p class="lead" data-reveal>Every package is tailored after a free discovery call — these are the most common ways brands start with us.</p>
    </div>
    <div class="pkg-grid">
<?php foreach ($pkgs as $p): $hot = $p['highlight'] === '1'; ?>
      <div class="pkg<?= $hot ? ' pkg--hot' : '' ?>" data-reveal><?php if ($p['badge']): ?><span class="badge"><?= e($p['badge']) ?></span><?php endif; ?><h3><?= e($p['name']) ?></h3><p><?= e($p['summary']) ?></p><div class="price"><?= e($p['price']) ?></div><ul><?php foreach (lines($p['features']) as $f): ?><li><?= icon('check') ?><?= e($f) ?></li><?php endforeach; ?></ul><a href="contact.php" class="btn <?= $hot ? 'btn--grey' : 'btn--line' ?>"><?= e($p['button'] ?: 'Get a quote') ?> <?= icon('arrow-ur') ?></a></div>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php cta_section('Not sure<br>where to <span class="hl">start?</span>',
    'Tell us your goal. We’ll recommend the right channel mix and a realistic plan for your budget — free.', 'Talk to a<br>strategist'); ?>

<?php page_end();
