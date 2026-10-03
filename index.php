<?php
require __DIR__ . '/inc/layout.php';

$services = items('services');
$featured = items('projects', 'featured', 5);
$quotes = items('testimonials');
$clients = items('clients');
$words = tags(setting('hero_words', 'loved'));

page_start('home', 'Dakpion IMC — 360° Marketing Solutions Agency in Bangladesh',
    'Dakpion IMC is a 360° marketing agency in Dhaka — brand strategy, creative, social media, performance ads, SEO, video production, ATL media, PR and BTL activation under one roof.');
?>

<section class="hero">
  <div class="hero-pin">
    <div class="container hero-center">
  <svg class="hero-ring" viewBox="0 0 600 600" aria-hidden="true"><circle class="hr-track" cx="300" cy="300" r="280"/><circle class="hr-prog" cx="300" cy="300" r="280"/></svg>
  <div class="ring-words" aria-hidden="true"><span class="rw" data-angle="36" style="left:77.4%;top:12.2%">Strategy</span><span class="rw" data-angle="108" style="left:94.4%;top:64.4%">Creative</span><span class="rw" data-angle="180" style="left:50.0%;top:96.7%">Digital</span><span class="rw" data-angle="252" style="left:5.6%;top:64.4%">Media</span><span class="rw" data-angle="324" style="left:22.6%;top:12.2%">Activation</span></div>
      <span class="hero-kicker"><?= e(setting('hero_kicker')) ?></span>
      <h1 class="hero-title">
        <span class="ln"><span class="lni"><?= e(setting('hero_line1', 'We make brands')) ?></span></span>
        <span class="ln"><span class="lni"><span class="rotator"><?php foreach ($words as $w): ?><span><?= e($w) ?><i class="dot">.</i></span><?php endforeach; ?></span></span></span>
      </h1>
      <p class="hero-sub"><?= e(setting('hero_sub')) ?></p>
      <div class="hero-cta">
        <a href="contact.php" class="btn btn--grey btn--lg" data-magnetic=".2">Start a project <?= icon('arrow-ur') ?></a>
        <a href="services.php" class="btn btn--line btn--lg">Our services</a>
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
    <p class="manifesto"><?= rich(setting('manifesto')) ?></p>
    <div class="manifesto-foot">
      <p data-reveal>Strategists, designers, writers, filmmakers, media planners, developers and activation crews — under one roof, working from one plan.</p>
      <a href="about.php" class="btn-orb" data-magnetic=".4" data-reveal><span>Meet<br>Dakpion<?= icon('arrow-ur') ?></span></a>
    </div>
  </div>
</section>

<?php if ($clients): ?>
<section class="logos" aria-label="Clients">
  <div class="container"><div class="label" data-reveal>Trusted by</div></div>
  <div class="logos-row"><?php for ($k = 0; $k < 2; $k++): ?><div class="logos-track"<?= $k ? ' aria-hidden="true"' : '' ?>><?php foreach ($clients as $c): ?><span class="logo-item"><img src="<?= e($c['logo']) ?>" alt="<?= $k ? '' : e($c['name']) ?>" loading="lazy"></span><?php endforeach; ?></div><?php endfor; ?></div>
</section>
<?php endif; ?>

<section class="hs" id="services">
  <div class="hs-pin">
    <div class="hs-track">
      <div class="hs-intro">
        <div class="label">What we do</div>
        <h2 data-split><?= ucfirst(num_word(count($services))) ?> ways<br>we grow <span class="hl">brands.</span></h2>
        <p class="lead">Use one, or plug them all together. Every service is built to work as part of a single 360° plan.</p>
      </div>
<?php foreach ($services as $i => $s): ?>
      <a class="svc" href="services.php#<?= e($s['slug']) ?>" data-cursor="Explore">
        <span class="go"><?= icon('arrow-ur') ?></span>
        <span class="n"><?= sprintf('%02d', $i + 1) ?></span>
        <span class="ic"><?= icon($s['icon'] ?: 'target') ?></span>
        <h3><?= e($s['name']) ?></h3>
        <p><?= e($s['short']) ?></p>
        <div class="tags"><?php foreach (tags($s['tags']) as $t): ?><span class="tag"><?= e($t) ?></span><?php endforeach; ?></div>
      </a>
<?php endforeach; ?>
      <div class="hs-end"><a href="services.php" class="btn-orb" data-magnetic=".45"><span>All<br>services<?= icon('arrow-ur') ?></span></a></div>
    </div>
  </div>
</section>

<?php if ($featured): ?>
<section class="section">
  <div class="container">
    <div class="head">
      <div><div class="label" data-reveal>Selected work</div><h2 data-split>Campaigns that<br><span class="hl">moved</span> numbers.</h2></div>
      <a href="portfolio.php" class="btn btn--line" data-reveal>All work <?= icon('arrow-ur') ?></a>
    </div>
    <div class="wl" data-cursor="View">
<?php foreach ($featured as $i => $p): ?>
      <a class="wl-row" href="portfolio.php">
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

<section class="section">
  <div class="container">
    <div class="head">
      <div><div class="label" data-reveal>How we work</div><h2 data-split>Brief to<br>results in <?= num_word(count(items('process'))) ?>.</h2></div>
      <p class="lead" data-reveal>A clear, accountable process — you always know what's happening, why, and what it's delivering.</p>
    </div>
    <div class="stack">
<?php foreach (items('process') as $i => $st): ?>
      <div class="stack-card" style="--i:<?= $i ?>">
        <span class="sn"><?= sprintf('%02d', $i + 1) ?></span>
        <h3><?= e($st['title']) ?></h3>
        <div><p><?= e($st['text']) ?></p><div class="tags"><?php foreach (tags($st['tags']) as $t): ?><span class="tag"><?= e($t) ?></span><?php endforeach; ?></div></div>
      </div>
<?php endforeach; ?>
    </div>
  </div>
</section>

<?php $crew = items('team', 'home', 4); if ($crew): ?>
<section class="section section--paper round" style="margin:0 clamp(8px,1.4vw,20px)">
  <div class="container">
    <div class="head">
      <div><div class="label" data-reveal>The crew</div><h2 data-split>People behind<br>the <span class="hl">360°.</span></h2></div>
      <a href="about.php#team" class="btn btn--line" data-reveal>Meet the full team <?= icon('arrow-ur') ?></a>
    </div>
    <?php team_grid($crew); ?>
  </div>
</section>
<?php endif; ?>

<?php if ($quotes): ?>
<section class="section">
  <div class="container">
    <div class="head">
      <div><div class="label" data-reveal>Client love</div><h2 data-split>Don't take<br>our word for it.</h2></div>
    </div>
  </div>
  <div class="tm" data-reveal><?php for ($k = 0; $k < 2; $k++): ?><div class="tm-track"<?= $k ? ' aria-hidden="true"' : '' ?>><?php foreach ($quotes as $q): ?><figure class="quote"><div class="qm">“</div><blockquote><?= e($q['quote']) ?></blockquote><figcaption class="by"><span class="av"><?= $q['photo'] ? '<img src="' . e($q['photo']) . '" alt="" loading="lazy">' : e($q['initials'] ?: initials($q['name'])) ?></span><div><b><?= e($q['name']) ?></b><span><?= e($q['role']) ?></span></div></figcaption></figure><?php endforeach; ?></div><?php endfor; ?></div>
  <?php note('Client names, quotes and campaign figures are placeholders — to be replaced with verified case studies.'); ?>
</section>
<?php endif; ?>

<?php $faqs = items('faqs'); if ($faqs): ?>
<section class="section">
  <div class="container faq">
    <div>
      <div class="label" data-reveal>FAQ</div>
      <h2 data-split>Good<br>questions.</h2>
      <p class="lead" data-reveal>Can't find your answer? The first consultation is free.</p>
    </div>
    <div data-reveal><?php foreach ($faqs as $i => $f): ?><div class="faq-item<?= $i ? '' : ' open' ?>"><button class="faq-q" aria-expanded="<?= $i ? 'false' : 'true' ?>"><?= e($f['question']) ?><span class="pm"><?= icon('plus') ?></span></button><div class="faq-a"><div><p><?= nl2br(e($f['answer'])) ?></p></div></div></div><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<?php cta_section('Let’s make<br>some <span class="hl">noise.</span>',
    'Book a free marketing audit. We’ll review your brand, channels and numbers — and show you exactly where the growth is.', 'Start a<br>project'); ?>

<?php page_end();
