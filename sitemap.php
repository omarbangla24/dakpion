<?php
/* /sitemap.xml — every public page and published post. */
require __DIR__ . '/inc/bootstrap.php';

header('Content-Type: application/xml; charset=UTF-8');
$urls = [];
foreach (PAGES as $key => $p) {
    if (!isset($p['path']) || ($key === 'blog' && !has_posts())) continue;
    $urls[] = [abs_url($p['path']), null];
}
foreach (posts() as $post) $urls[] = [abs_url('blog/' . $post['slug']), $post['updated_at'] ?: $post['published_at']];

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n", '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($urls as [$loc, $mod]) {
    echo '  <url><loc>', e($loc), '</loc>', $mod ? '<lastmod>' . date('Y-m-d', strtotime($mod)) . '</lastmod>' : '', "</url>\n";
}
echo "</urlset>\n";
