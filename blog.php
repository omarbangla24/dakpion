<?php
require __DIR__ . '/inc/layout.php';

const PER_PAGE = 9;
$cat = trim((string)($_GET['category'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$all = posts(0, 0, $cat);
$total = count($all);
$pages = max(1, (int)ceil($total / PER_PAGE));
$list = array_slice($all, ($page - 1) * PER_PAGE, PER_PAGE);
$cats = db()->query("SELECT category, COUNT(*) n FROM posts WHERE status = 'published' AND category <> '' GROUP BY category ORDER BY n DESC")->fetchAll(PDO::FETCH_KEY_PAIR);
$link = fn(array $q) => url('blog') . ($q ? '?' . http_build_query($q) : '');

page_start('blog', $page > 1 || $cat !== '' ? [
    'title' => t('blog', 'seo_title') . ($cat !== '' ? ' — ' . $cat : '') . ($page > 1 ? " (page $page)" : ''),
] : []);
page_hero('Blog', 'Blog', heading(t('blog', 'hero_title')), t('blog', 'hero_lead'));
?>

<section class="section" style="padding-top:0">
  <div class="container">
<?php if ($cats): ?>
    <div class="filter" data-reveal><a class="<?= $cat === '' ? 'active' : '' ?>" href="<?= $link([]) ?>">All posts</a><?php foreach ($cats as $c => $n): ?><a class="<?= $cat === $c ? 'active' : '' ?>" href="<?= e($link(['category' => $c])) ?>"><?= e($c) ?></a><?php endforeach; ?></div>
<?php endif; ?>
<?php if (!$list): ?>
    <p class="lead">No posts yet — check back soon.</p>
<?php else: ?>
    <div class="post-grid"><?php foreach ($list as $i => $p) echo post_card($p, $i === 0 && $page === 1 && $cat === ''); ?></div>
<?php endif; if ($pages > 1): ?>
    <nav class="pager" aria-label="Pages">
<?php for ($n = 1; $n <= $pages; $n++): ?>
      <a href="<?= e($link(array_filter(['category' => $cat, 'page' => $n > 1 ? $n : null]))) ?>"<?= $n === $page ? ' aria-current="page"' : '' ?>><?= $n ?></a>
<?php endfor; ?>
    </nav>
<?php endif; ?>
  </div>
</section>

<?php cta_section('blog'); ?>

<?php page_end();
