<?php
/* /robots.txt */
require __DIR__ . '/inc/bootstrap.php';

header('Content-Type: text/plain; charset=UTF-8');
echo "User-agent: *\nDisallow: " . url('admin/') . "\n\nSitemap: " . abs_url('sitemap.xml') . "\n";
