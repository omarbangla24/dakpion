<?php
require __DIR__ . '/inc/layout.php';

page_start('industries');
page_hero('Industries', 'Industries', heading(t('industries', 'hero_title')), t('industries', 'hero_lead'));
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

<?php cta_section('industries'); ?>

<?php page_end();
