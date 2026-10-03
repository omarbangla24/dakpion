<?php
require __DIR__ . '/inc/layout.php';

page_start('about');
page_hero('About', 'About Dakpion IMC', heading(t('about', 'hero_title')), t('about', 'hero_lead'));
?>

<section class="section" style="padding-top:0">
  <div class="container split">
    <div>
      <div class="label" data-reveal>Our story</div>
      <h2 data-split><?= heading(t('about', 'story_title')) ?></h2>
<?php foreach (['story_1', 'story_2'] as $k): if (t('about', $k) !== ''): ?>
      <p class="lead" data-reveal><?= e(t('about', $k)) ?></p>
<?php endif; endforeach; ?>
    </div>
    <div class="orbit-art" data-reveal aria-hidden="true">
      <span class="o o1"></span><span class="o o2"></span>
      <span class="o o3"><span><b>IMC</b><span>Integrated Marketing<br>Communications</span></span></span>
      <span class="dot"><i></i></span><span class="dot dot--2"><i></i></span>
    </div>
  </div>
</section>

<?php stats_section(); ?>

<?php $values = items('values'); if ($values): ?>
<section class="section">
  <div class="container">
    <div class="head"><div><div class="label" data-reveal>What we believe</div><h2 data-split><?= heading(t('about', 'values_title')) ?></h2></div></div>
    <div class="values"><?php foreach ($values as $v): ?><div class="value" data-reveal><span class="ic"><?= icon($v['icon'] ?: 'target') ?></span><h4><?= e($v['title']) ?></h4><p><?= e($v['text']) ?></p></div><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<?php $tl = items('timeline'); if ($tl): ?>
<section class="section section--paper round" style="margin:0 clamp(8px,1.4vw,20px)">
  <div class="container">
    <div class="head"><div><div class="label" data-reveal>Journey</div><h2 data-split><?= heading(t('about', 'journey_title')) ?></h2></div></div>
    <div class="timeline"><span class="bar"></span><?php foreach ($tl as $t): ?><div class="tl" data-reveal><b><?= e($t['year']) ?></b><p><?= e($t['text']) ?></p></div><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<section class="section" id="team">
  <div class="container">
    <div class="head">
      <div><div class="label" data-reveal>Our team</div><h2 data-split><?= heading(t('about', 'team_title')) ?></h2></div>
      <p class="lead" data-reveal><?= e(t('about', 'team_lead')) ?></p>
    </div>
    <?php team_grid(items('team'), true); ?>
    <?php note('Team photos and names to be added.'); ?>
  </div>
</section>

<?php cta_section('about'); ?>

<?php page_end();
