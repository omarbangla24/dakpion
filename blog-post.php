<?php
require __DIR__ . '/inc/layout.php';

$slug = (string)($_GET['slug'] ?? '');
$st = db()->prepare("SELECT p.*, u.name AS author_name, u.username AS author_user FROM posts p LEFT JOIN users u ON u.id = p.author_id
    WHERE p.slug = ? AND p.status = 'published' AND p.published_at <= ?");
$st->execute([$slug, now()]);
$post = $st->fetch();

if (!$post) {
    http_response_code(404);
    page_start('blog', ['title' => 'Post not found — ' . setting('site_name', 'Dakpion IMC'), 'path' => 'blog']);
    page_hero('Blog', '404', 'Post not <span class="hl">found.</span>', 'This post may have moved or been unpublished.',
        '<a class="btn btn--grey btn--lg" href="' . url('blog') . '">All posts ' . icon('arrow-ur') . '</a>');
    page_end();
    exit;
}

db()->prepare('UPDATE posts SET views = views + 1 WHERE id = ?')->execute([$post['id']]);

$related = array_slice(array_filter(posts(6, 0, (string)$post['category']), fn($p) => $p['id'] !== $post['id']), 0, 3);
if (count($related) < 3) {
    $ids = array_column($related, 'id');
    foreach (posts(6) as $p) if ($p['id'] !== $post['id'] && !in_array($p['id'], $ids, true) && count($related) < 3) $related[] = $p;
}

$author = $post['author_name'] ?: '';
$desc = $post['seo_desc'] ?: ($post['excerpt'] ?: mb_strimwidth(trim(preg_replace('/\s+/', ' ', strip_tags($post['body']))), 0, 160, '…'));
$schema = [
    '@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $post['title'], 'description' => $desc,
    'datePublished' => date('c', strtotime($post['published_at'])), 'dateModified' => date('c', strtotime($post['updated_at'] ?: $post['published_at'])),
    'mainEntityOfPage' => abs_url('blog/' . $post['slug']),
    'publisher' => ['@type' => 'Organization', 'name' => setting('site_name', 'Dakpion IMC'), 'logo' => abs_url('assets/images/logo.png')],
] + ($post['cover'] ? ['image' => abs_url($post['cover'])] : []) + ($author ? ['author' => ['@type' => 'Person', 'name' => $author]] : []);

page_start('blog', [
    'title' => ($post['seo_title'] ?: $post['title']) . ' — ' . setting('site_name', 'Dakpion IMC'),
    'desc' => $desc,
    'image' => $post['cover'] ?: setting('og_image'),
    'type' => 'article',
    'path' => 'blog/' . $post['slug'],
    'head' => '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>',
]);
?>

<article class="article">
  <header class="article-head container">
    <div class="crumbs" data-reveal><a href="<?= url() ?>">Home</a><span>/</span><a href="<?= url('blog') ?>">Blog</a><?php if ($post['category']): ?><span>/</span><a href="<?= e(url('blog') . '?category=' . rawurlencode($post['category'])) ?>"><?= e($post['category']) ?></a><?php endif; ?></div>
    <h1 data-split><?= e($post['title']) ?></h1>
    <div class="article-meta" data-reveal>
      <?php if ($author): ?><span><?= e($author) ?></span><?php endif; ?>
      <span><?= date('d F Y', strtotime($post['published_at'])) ?></span>
      <span><?= reading_time((string)$post['body']) ?> min read</span>
    </div>
  </header>
<?php if ($post['cover']): ?>
  <figure class="article-cover container" data-reveal><img src="<?= e(media_url($post['cover'])) ?>" alt=""></figure>
<?php endif; ?>
  <div class="container">
    <div class="prose"><?= $post['body'] /* cleaned by clean_html() when saved */ ?></div>
    <div class="share">
      <span>Share</span>
      <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode(abs_url('blog/' . $post['slug'])) ?>" target="_blank" rel="noopener" aria-label="Share on Facebook"><?= icon('fb') ?></a>
      <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= rawurlencode(abs_url('blog/' . $post['slug'])) ?>" target="_blank" rel="noopener" aria-label="Share on LinkedIn"><?= icon('in') ?></a>
      <a href="https://wa.me/?text=<?= rawurlencode($post['title'] . ' ' . abs_url('blog/' . $post['slug'])) ?>" target="_blank" rel="noopener" aria-label="Share on WhatsApp"><?= icon('wa') ?></a>
    </div>
  </div>
</article>

<?php if ($related): ?>
<section class="section">
  <div class="container">
    <div class="head"><div><div class="label" data-reveal>Keep reading</div><h2 data-split>More from<br>the <span class="hl">blog.</span></h2></div></div>
    <div class="post-grid"><?php foreach ($related as $p) echo post_card($p); ?></div>
  </div>
</section>
<?php endif; ?>

<?php cta_section('blog'); ?>

<?php page_end();
