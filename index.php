<?php
require __DIR__ . '/inc/layout.php';

$services = items('services');
$featured = items('projects', 'featured', 5);
$quotes = items('testimonials');
$clients = items('clients');
$process = items('process');
$latest = posts(3);
$words = tags(t('home', 'hero_words')) ?: ['loved'];

page_start('home');
?>

<section class="hero">
  <div class="hero-pin">
    <div class="container hero-center">
  <svg class="hero-ring" viewBox="0 0 600 600" aria-hidden="true"><circle class="hr-track" cx="300" cy="300" r="280"/><circle class="hr-prog" cx="300" cy="300" r="280"/></svg>
  <div class="ring-words" aria-hidden="true"><span class="rw" data-angle="36" style="left:77.4%;top:12.2%">Strategy</span><span class="rw" data-angle="108" style="left:94.4%;top:64.4%">Creative</span><span class="rw" data-angle="180" style="left:50.0%;top:96.7%">Digital</span><span class="rw" data-angle="252" style="left:5.6%;top:64.4%">Media</span><span class="rw" data-angle="324" style="left:22.6%;top:12.2%">Activation</span></div>
      <span class="hero-kicker"><?= e(t('home', 'hero_kicker')) ?></span>
      <h1 class="hero-title">
        <span class="ln"><span class="lni"><?= e(t('home', 'hero_line1')) ?></span></span>
        <span class="ln"><span class="lni"><span class="rotator"><?php foreach ($words as $w): ?><span><?= e($w) ?><i class="dot">.</i></span><?php endforeach; ?></span></span></span>
      </h1>
      <p class="hero-sub"><?= e(t('home', 'hero_sub')) ?></p>
      <div class="hero-cta">
        <a href="<?= url('contact') ?>" class="btn btn--grey btn--lg" data-magnetic=".2"><?= e(t('home', 'hero_cta')) ?> <?= icon('arrow-ur') ?></a>
        <a href="<?= url('services') ?>" class="btn btn--line btn--lg"><?= e(t('home', 'hero_cta2')) ?></a>
      </div>
    </div>
    <div class="hero-deg" aria-hidden="true"><b>0</b>°</div>
    <div class="scroll-hint"><i></i>Scroll</div>
  </div>
</section>

<section class="tapes" aria-hidden="true">
  <div class="tape tape--a"><div class="tape-track"><span>Strategy</span><span>Branding</span><span>Social Media</span><span>Performance</span><span>SEO</span><span>TVC &amp; OVC</span><span>Billboards</span><span>Activation</span></div><div class="tape-track"><span>Strategy</span><span>Branding</span><span>Social Media</span><span>Performance</span><span>SEO</span><span>TVC &amp; OVC</span><span>Billboards</span><span>Activation</span></div></div>
  <div class="tape tape--b"><div class="tape-track"><span>Think 360°</span><span>Create</span><span>Engage</span><span>Measure</span><span>Grow</span><span>Repeat</span><span>Think 360°</span><span>Create</span><span>Engage</span><span>Measure</span><span>Grow</span><span>Repeat</span></div><div class="tape-track"><span>Think 360°</span><span>Create</span><span>Engage</span><span>Measure</span><span>Grow</span><span>Repeat</span><span>Think 360°</span><span>Create</span><span>Engage</span><span>Measure</span><span>Grow</span><span>Repeat</span></div></div>
</section>

<section class="section">
  <div class="container">
    <div class="label" data-reveal>Who we are</div>
    <p class="manifesto"><?= rich(t('home', 'manifesto')) ?></p>
    <div class="manifesto-foot">
      <p data-reveal><?= e(t('home', 'manifesto_foot')) ?></p>
      <a href="<?= url('about') ?>" class="btn-orb" data-magnetic=".4" data-reveal><span>Meet<br>Dakpion<?= icon('arrow-ur') ?></span></a>
    </div>
  </div>
</section>

<?php if ($clients): ?>
<section class="logos" aria-label="Clients">
  <div class="container"><div class="label" data-reveal>Trusted by</div></div>
  <div class="logos-row"><?php for ($k = 0; $k < 2; $k++): ?><div class="logos-track"<?= $k ? ' aria-hidden="true"' : '' ?>><?php foreach ($clients as $c): ?><span class="logo-item"><img src="<?= e(media_url($c['logo'])) ?>" alt="<?= $k ? '' : e($c['name']) ?>" loading="lazy"></span><?php endforeach; ?></div><?php endfor; ?></div>
</section>
<?php endif; ?>

<section class="hs" id="services">
  <div class="hs-pin">
    <div class="hs-track">
      <div class="hs-intro">
        <div class="label">What we do</div>
        <h2 data-split><?= heading(t('home', 'services_title')) ?></h2>
        <p class="lead"><?= e(t('home', 'services_lead')) ?></p>
      </div>
<?php foreach ($services as $i => $s): ?>
      <a class="svc" href="<?= url('services') ?>#<?= e($s['slug']) ?>" data-cursor="Explore">
        <span class="go"><?= icon('arrow-ur') ?></span>
        <span class="n"><?= sprintf('%02d', $i + 1) ?></span>
        <span class="ic"><?= icon($s['icon'] ?: 'target') ?></span>
        <h3><?= e($s['name']) ?></h3>
        <p><?= e($s['short']) ?></p>
        <div class="tags"><?php foreach (tags($s['tags']) as $tg): ?><span class="tag"><?= e($tg) ?></span><?php endforeach; ?></div>
      </a>
<?php endforeach; ?>
      <div class="hs-end"><a href="<?= url('services') ?>" class="btn-orb" data-magnetic=".45"><span>All<br>services<?= icon('arrow-ur') ?></span></a></div>
    </div>
  </div>
</section>

<?php if ($featured): ?>
<section class="section">
  <div class="container">
    <div class="head">
      <div><div class="label" data-reveal>Selected work</div><h2 data-split><?= heading(t('home', 'work_title')) ?></h2></div>
      <a href="<?= url('portfolio') ?>" class="btn btn--line" data-reveal>All work <?= icon('arrow-ur') ?></a>
    </div>
    <div class="wl" data-cursor="View">
<?php foreach ($featured as $i => $p): ?>
      <a class="wl-row" href="<?= url('portfolio') ?>">
        <span class="i"><?= sprintf('%02d', $i + 1) ?></span>
        <h3><?= e($p['title']) ?></h3>
        <span class="meta"><?= e(implode(' · ', array_filter([$p['industry'], $p['kind']]))) ?></span>
        <span class="kpi"><?= e($p['kpi']) ?><small><?= e($p['kpi_label']) ?></small></span>
      </a>
<?php endforeach; ?>
    </div>
    <div class="preview" aria-hidden="true"><?php foreach ($featured as $p) echo thumb($p); ?></div>
  </div>
</section>
<?php endif; ?>

<?php stats_section(); ?>

<?php if ($process): ?>
<section class="section">
  <div class="container">
    <div class="head">
      <div><div class="label" data-reveal>How we work</div><h2 data-split><?= heading(t('home', 'process_title')) ?></h2></div>
      <p class="lead" data-reveal><?= e(t('home', 'process_lead')) ?></p>
    </div>
    <div class="stack">
<?php foreach ($process as $i => $st): ?>
      <div class="stack-card" style="--i:<?= $i ?>">
        <span class="sn"><?= sprintf('%02d', $i + 1) ?></span>
        <h3><?= e($st['title']) ?></h3>
        <div><p><?= e($st['text']) ?></p><div class="tags"><?php foreach (tags($st['tags']) as $tg): ?><span class="tag"><?= e($tg) ?></span><?php endforeach; ?></div></div>
      </div>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php $crew = items('team', 'home', 4); if ($crew): ?>
<section class="section section--paper round" style="margin:0 clamp(8px,1.4vw,20px)">
  <div class="container">
    <div class="head">
      <div><div class="label" data-reveal>The crew</div><h2 data-split><?= heading(t('home', 'team_title')) ?></h2></div>
      <a href="<?= url('about') ?>#team" class="btn btn--line" data-reveal>Meet the full team <?= icon('arrow-ur') ?></a>
    </div>
    <?php team_grid($crew); ?>
  </div>
</section>
<?php endif; ?>

<?php if ($quotes): ?>
<section class="section">
  <div class="container">
    <div class="head">
      <div><div class="label" data-reveal>Client love</div><h2 data-split><?= heading(t('home', 'quotes_title')) ?></h2></div>
    </div>
  </div>
  <div class="tm" data-reveal><?php for ($k = 0; $k < 2; $k++): ?><div class="tm-track"<?= $k ? ' aria-hidden="true"' : '' ?>><?php foreach ($quotes as $q): ?><figure class="quote"><div class="qm">“</div><blockquote><?= e($q['quote']) ?></blockquote><figcaption class="by"><span class="av"><?= $q['photo'] ? '<img src="' . e(media_url($q['photo'])) . '" alt="" loading="lazy">' : e($q['initials'] ?: initials($q['name'])) ?></span><div><b><?= e($q['name']) ?></b><span><?= e($q['role']) ?></span></div></figcaption></figure><?php endforeach; ?></div><?php endfor; ?></div>
  <?php note('Client names, quotes and campaign figures are placeholders — to be replaced with verified case studies.'); ?>
</section>
<?php endif; ?>

<?php if ($latest): ?>
<section class="section">
  <div class="container">
    <div class="head">
      <div><div class="label" data-reveal>Insights</div><h2 data-split><?= heading(t('home', 'blog_title')) ?></h2></div>
      <a href="<?= url('blog') ?>" class="btn btn--line" data-reveal>All posts <?= icon('arrow-ur') ?></a>
    </div>
    <div class="post-grid"><?php foreach ($latest as $p) echo post_card($p); ?></div>
  </div>
</section>
<?php endif; ?>

<?php $faqs = items('faqs'); if ($faqs): ?>
<section class="section">
  <div class="container faq">
    <div>
      <div class="label" data-reveal>FAQ</div>
      <h2 data-split><?= heading(t('home', 'faq_title')) ?></h2>
      <p class="lead" data-reveal><?= e(t('home', 'faq_lead')) ?></p>
    </div>
    <div data-reveal><?php foreach ($faqs as $i => $f): ?><div class="faq-item<?= $i ? '' : ' open' ?>"><button class="faq-q" aria-expanded="<?= $i ? 'false' : 'true' ?>"><?= e($f['question']) ?><span class="pm"><?= icon('plus') ?></span></button><div class="faq-a"><div><p><?= nl2br(e($f['answer'])) ?></p></div></div></div><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<?php cta_section('home'); ?>

<?php page_end();
