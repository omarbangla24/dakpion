<?php
require __DIR__ . '/inc/layout.php';

page_start('about', 'About Us & Team — Dakpion IMC',
    'Dakpion IMC is a 360° integrated marketing communications agency in Dhaka, Bangladesh. Meet the team behind strategy, creative, digital, media and activation.');

page_hero('About', 'About Dakpion IMC', 'Cultivating ideas.<br>Crafting <span class="hl">success.</span>',
    'An integrated marketing communications agency from Dhaka, built on one belief: brands grow fastest when every touchpoint works together.');
?>

<section class="section" style="padding-top:0">
  <div class="container split">
    <div>
      <div class="label" data-reveal>Our story</div>
      <h2 data-split>From studio<br>to <span class="hl">360°.</span></h2>
<?php foreach (['story_1', 'story_2'] as $k): if (setting($k)): ?>
      <p class="lead" data-reveal><?= e(setting($k)) ?></p>
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
    <div class="head"><div><div class="label" data-reveal>What we believe</div><h2 data-split><?= ucfirst(num_word(count($values))) ?> rules<br>we live by.</h2></div></div>
    <div class="values"><?php foreach ($values as $v): ?><div class="value" data-reveal><span class="ic"><?= icon($v['icon'] ?: 'target') ?></span><h4><?= e($v['title']) ?></h4><p><?= e($v['text']) ?></p></div><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<?php $tl = items('timeline'); if ($tl): ?>
<section class="section section--paper round" style="margin:0 clamp(8px,1.4vw,20px)">
  <div class="container">
    <div class="head"><div><div class="label" data-reveal>Journey</div><h2 data-split>How we<br>got here.</h2></div></div>
    <div class="timeline"><span class="bar"></span><?php foreach ($tl as $t): ?><div class="tl" data-reveal><b><?= e($t['year']) ?></b><p><?= e($t['text']) ?></p></div><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<section class="section" id="team">
  <div class="container">
    <div class="head">
      <div><div class="label" data-reveal>Our team</div><h2 data-split>The crew<br>behind the <span class="hl">360°.</span></h2></div>
      <p class="lead" data-reveal>Specialists in every discipline — one team, sitting together, working on your brand.</p>
    </div>
    <?php team_grid(items('team'), true); ?>
    <?php note('Team photos and names to be added.'); ?>
  </div>
</section>

<?php cta_section('Let’s build<br>something <span class="hl">bold.</span>',
    'Launch, rebrand or year-round growth — we’d love to hear about it.', 'Start a<br>project'); ?>

<?php page_end();
