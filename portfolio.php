<?php
require __DIR__ . '/inc/layout.php';

$projects = items('projects');
$used = [];
foreach ($projects as $p) foreach (explode(',', (string)$p['cats']) as $c) $used[trim($c)] = true;

page_start('work');
page_hero('Work', 'Our work', heading(t('work', 'hero_title')), t('work', 'hero_lead'));
?>

<section class="section" style="padding-top:0">
  <div class="container">
    <div class="filter" role="group" aria-label="Filter work" data-reveal><button data-filter="all" aria-pressed="true" class="active">All work</button><?php foreach (WORK_CATEGORIES as $k => $label): if (isset($used[$k])): ?><button data-filter="<?= $k ?>" aria-pressed="false"><?= e($label) ?></button><?php endif; endforeach; ?></div>
    <div class="work-grid">
<?php foreach ($projects as $p): $link = safe_url($p['video']); ?>
      <a class="work-card" href="<?= e($link ?: url('contact')) ?>"<?= $link ? ' target="_blank" rel="noopener"' : '' ?> data-cat="<?= e(str_replace(',', ' ', (string)$p['cats'])) ?>" data-cursor="View" data-reveal>
        <?= thumb($p) ?>
        <div class="work-info"><span class="m"><?= e($p['kind']) ?></span><h3><?= e($p['title']) ?></h3><p><?= e($p['summary']) ?></p><div class="tags"><?php foreach (tags($p['tags']) as $t): ?><span class="tag"><?= e($t) ?></span><?php endforeach; ?></div></div>
      </a>
<?php endforeach; ?>
    </div>
    <?php note('Project names and results shown are illustrative placeholders — to be replaced with verified client case studies.'); ?>
  </div>
</section>

<?php cta_section('work'); ?>

<?php page_end();
