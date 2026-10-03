<?php
/*
 * Local development only: mirrors the .htaccess rewrites for PHP's built-in server.
 *   php -S localhost:8000 tools/dev-router.php
 */
$root = dirname(__DIR__);
$uri = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (preg_match('~^/(data|inc|tools)(/|$)|/\.(?!well-known/)|^/admin/(views|inc)(/|$)~', $uri)) {
    http_response_code(403);
    exit('Forbidden');
}

$run = function (string $script, array $get = []) use ($root): bool {
    $_GET += $get;
    $_SERVER['SCRIPT_NAME'] = '/' . $script;
    $_SERVER['SCRIPT_FILENAME'] = $root . '/' . $script;
    chdir(dirname($_SERVER['SCRIPT_FILENAME']));
    require $_SERVER['SCRIPT_FILENAME'];
    return true;
};

if (preg_match('~^/(about|services|industries|portfolio|contact)\.html$~', $uri, $m)) { header('Location: /' . $m[1], true, 301); return true; }
if ($uri === '/index.html') { header('Location: /', true, 301); return true; }
if (is_file($root . $uri)) {
    if (!str_ends_with($uri, '.php')) return false; // static file
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('~^/(index|about|services|industries|portfolio|contact|blog)\.php$~', $uri, $m)) {
        header('Location: /' . ($m[1] === 'index' ? '' : $m[1]) . (($q = $_SERVER['QUERY_STRING'] ?? '') !== '' ? "?$q" : ''), true, 301);
        return true;
    }
    return $run(ltrim($uri, '/'));
}
if (str_starts_with($uri, '/admin')) return $run('admin/index.php');
if ($uri === '/') return $run('index.php');
if ($uri === '/sitemap.xml') return $run('sitemap.php');
if ($uri === '/robots.txt') return $run('robots.php');
if (preg_match('~^/blog/([A-Za-z0-9-]+)/?$~', $uri, $m)) return $run('blog-post.php', ['slug' => $m[1]]);
if (preg_match('~^/([A-Za-z0-9-]+)/?$~', $uri, $m) && is_file("$root/$m[1].php")) return $run("$m[1].php");
http_response_code(404);
echo 'Not found';
return true;
