<?php
require __DIR__ . '/inc/layout.php';

page_start('industries', 'Industries We Serve — Dakpion IMC',
    'Integrated marketing for healthcare, FMCG, real estate, e-commerce, education, corporate, hospitality and fashion brands in Bangladesh.');

page_hero('Industries', 'Industries', 'Different<br>markets. One <span class="hl">360°.</span>',
    'Every industry buys differently. We bring category know-how, proven channel mixes and compliant creative to the sectors we know best.');
?>

<section class="section" style="padding-top:0">
  <div class="container"><div class="ind-grid">
<?php foreach (items('industries') as $i => $ind): ?>
      <div class="ind"<?= $ind['slug'] ? ' id="' . e($ind['slug']) . '"' : '' ?> data-reveal>
        <span class="ic"><?= icon($ind['icon'] ?: 'briefcase') ?></span>
        <div><h3><?= e($ind['name']) ?></h3><p><?= e($ind['text']) ?></p><div class="tags"><?php foreach (tags($ind['tags']) as $t): ?><span class="tag"><?= e($t) ?></span><?php endforeach; ?></div></div>
        <span class="n"><?= sprintf('%02d', $i + 1) ?></span>
      </div>
<?php endforeach; ?>
  </div></div>
</section>

<?php stats_section(); ?>

<?php cta_section('Don’t see<br>your <span class="hl">industry?</span>',
    'Our 360° approach adapts to any category. Tell us about your business and we’ll show you what’s possible.', 'Start a<br>conversation'); ?>

<?php page_end();
