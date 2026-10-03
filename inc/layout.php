<?php
/* Shared markup for the public pages: head, header, footer and repeated sections. */

require_once __DIR__ . '/bootstrap.php';

function nav_items(): array
{
    $nav = ['home' => ['', 'Home'], 'services' => ['services', 'Services'], 'industries' => ['industries', 'Industries'],
        'work' => ['portfolio', 'Work'], 'about' => ['about', 'About']];
    if (has_posts()) $nav['blog'] = ['blog', 'Blog'];
    $nav['contact'] = ['contact', 'Contact'];
    return $nav;
}

function icon(string $name, string $class = 'icon'): string
{
    return '<svg class="' . $class . '" aria-hidden="true"><use href="' . url('assets/icons/sprite.svg') . '#i-' . e($name) . '"/></svg>';
}

/**
 * Open a page. $page is a key of PAGES (title/description come from its SEO fields unless $meta overrides them).
 * $meta: title, desc, image, type, path (for canonical/tracking).
 */
function page_start(string $page, array $meta = []): void
{
    $title = $meta['title'] ?? t($page, 'seo_title');
    $desc = $meta['desc'] ?? t($page, 'seo_desc');
    $og = $meta['image'] ?? setting('og_image');
    $path = $meta['path'] ?? (PAGES[$page]['path'] ?? '');
    $email = setting('email');
    $phone = setting('phone');
    $ga = preg_replace('/[^\w-]/', '', setting('ga_id'));
    $px = preg_replace('/\D/', '', setting('pixel_id'));
    $bar = setting('bar_on') === '1' && setting('bar_text') !== '';
    $brand = setting('site_name', 'Dakpion IMC');
    track_view('/' . $path);
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<link rel="canonical" href="<?= e(abs_url($path)) ?>">
<meta name="theme-color" content="#9BCB3C">
<meta property="og:site_name" content="<?= e($brand) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:type" content="<?= e($meta['type'] ?? 'website') ?>">
<meta property="og:url" content="<?= e(abs_url($path)) ?>">
<?php if ($og): ?><meta property="og:image" content="<?= e(abs_url($og)) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php endif; if (setting('gsc_verify')): ?><meta name="google-site-verification" content="<?= e(setting('gsc_verify')) ?>">
<?php endif; ?>
<link rel="icon" href="<?= url('assets/images/favicon.png') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="image" href="<?= url('assets/images/logo-dark.webp') ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
<script>
(function(r){r.classList.add('js');try{if(!sessionStorage.getItem('dk-seen')&&!matchMedia('(prefers-reduced-motion: reduce)').matches)r.classList.add('show-loader');if(sessionStorage.getItem('dk-pt'))r.classList.add('pt-enter');}catch(e){}
setTimeout(function(){if(!window.__animReady){r.classList.add('no-anim');r.classList.remove('show-loader','pt-enter');}},3500);})(document.documentElement);
</script>
<?php if ($ga): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($ga) ?>');</script>
<?php endif; if ($px): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?= e($px) ?>');fbq('track','PageView');</script>
<?php endif; ?>
<script src="<?= url('assets/js/vendor/gsap.min.js') ?>" defer></script>
<script src="<?= url('assets/js/vendor/ScrollTrigger.min.js') ?>" defer></script>
<script src="<?= url('assets/js/vendor/SplitText.min.js') ?>" defer></script>
<script src="<?= url('assets/js/vendor/lenis.min.js') ?>" defer></script>
<script src="<?= asset('assets/js/main.js') ?>" defer></script>
<?= $meta['head'] ?? '' ?>
</head>
<body data-page="<?= e($page) ?>"<?= $bar ? ' class="has-bar"' : '' ?>>
<div class="progress" aria-hidden="true"></div>
<div class="cursor" aria-hidden="true"><span></span></div><div class="cursor-dot" aria-hidden="true"></div>
<div class="loader" aria-hidden="true"><span><?= e($brand) ?> — 360° Marketing</span><b>0°</b></div>
<div class="pt" aria-hidden="true"></div>
<header class="site-header">
<?php if ($bar): $bl = link_url(setting('bar_link')); ?>
  <div class="offer-bar"><p><?= e(setting('bar_text')) ?><?php if ($bl): ?> <a href="<?= e($bl) ?>"><?= e(setting('bar_link_text', 'Learn more')) ?> →</a><?php endif; ?></p></div>
<?php endif; ?>
  <div class="container header-inner">
    <a href="<?= url() ?>" class="brand" aria-label="<?= e($brand) ?> home"><img src="<?= url('assets/images/logo-dark.webp') ?>" width="420" height="116" alt="<?= e($brand) ?>"></a>
    <nav aria-label="Main"><ul class="nav"><?php foreach (nav_items() as $k => [$href, $label]): ?><li><a href="<?= url($href) ?>" data-nav="<?= $k ?>" data-text="<?= $label ?>"><span><?= $label ?></span></a></li><?php endforeach; ?></ul></nav>
    <div class="header-right">
      <a href="<?= url('contact') ?>" class="btn btn--grey" data-magnetic=".2"><?= e(t('global', 'header_button')) ?> <?= icon('arrow-ur') ?></a>
      <button class="menu-btn" aria-expanded="false" aria-controls="menu"><span>Menu</span><i><b></b><b></b></i></button>
    </div>
  </div>
</header>
<div class="menu" id="menu">
  <ul><?php $i = 0; foreach (nav_items() as [$href, $label]): ?><li><a href="<?= url($href) ?>" style="--i:<?= $i++ ?>"><?= $label ?></a></li><?php endforeach; ?></ul>
  <div class="menu-foot"><?php if ($email): ?><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif; ?><span><?= e(implode(' · ', array_filter([$phone, setting('address_short')]))) ?></span></div>
</div>

<main>
<?php
}

function page_end(): void
{
    $email = setting('email');
    $phone = setting('phone');
    $social = array_filter([
        'Facebook' => ['fb', setting('facebook')], 'Instagram' => ['ig', setting('instagram')],
        'LinkedIn' => ['in', setting('linkedin')], 'YouTube' => ['yt', setting('youtube')], 'TikTok' => ['tt', setting('tiktok')],
    ], fn($s) => safe_url($s[1]) !== '');
    $wa = setting('whatsapp');
    ?>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="f-top">
      <div>
        <h2><?= heading(t('global', 'footer_title')) ?></h2>
        <a href="<?= url('contact') ?>" class="btn btn--grey btn--lg" data-magnetic=".25"><?= e(t('global', 'footer_button')) ?> <?= icon('arrow-ur') ?></a>
<?php if ($social): ?>
        <div class="social">
<?php foreach ($social as $label => [$ic, $link]): ?>
          <a href="<?= e($link) ?>" target="_blank" rel="noopener" aria-label="<?= $label ?>"><?= icon($ic) ?></a>
<?php endforeach; ?>
        </div>
<?php endif; ?>
      </div>
      <div>
        <h5>Services</h5>
        <ul>
<?php foreach (items('services') as $s): ?>
          <li><a class="link" href="<?= url('services') ?>#<?= e($s['slug']) ?>"><?= e($s['name']) ?></a></li>
<?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h5>Company</h5>
        <ul>
          <li><a class="link" href="<?= url('about') ?>">About us</a></li>
          <li><a class="link" href="<?= url('about') ?>#team">Our team</a></li>
          <li><a class="link" href="<?= url('industries') ?>">Industries</a></li>
          <li><a class="link" href="<?= url('portfolio') ?>">Work</a></li>
<?php if (has_posts()): ?>          <li><a class="link" href="<?= url('blog') ?>">Blog</a></li>
<?php endif; ?>
          <li><a class="link" href="<?= url('contact') ?>">Contact</a></li>
        </ul>
      </div>
      <div>
        <h5>Say hello</h5>
        <ul class="f-contact">
<?php if ($email): ?>          <li><?= icon('mail') ?><a class="link" href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li>
<?php endif; if ($phone): ?>          <li><?= icon('phone') ?><?= is_placeholder($phone) ? '<span>' . e($phone) . '</span>' : '<a class="link" href="' . e(tel_href($phone)) . '">' . e($phone) . '</a>' ?></li>
<?php endif; if (setting('address')): ?>          <li><?= icon('pin') ?><?= safe_url(setting('map_url')) ? '<a class="link" href="' . e(setting('map_url')) . '" target="_blank" rel="noopener">' . e(setting('address')) . '</a>' : '<span>' . e(setting('address')) . '</span>' ?></li>
<?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="f-word" aria-hidden="true"><span>D</span><span>A</span><span>K</span><span>P</span><span>I</span><span>O</span><span>N</span></div>
    <div class="f-bottom">
      <span>&copy; <span id="year"><?= date('Y') ?></span> <?= e(setting('legal_name', 'Dakpion IMC')) ?> — <?= e(setting('tagline')) ?></span>
      <div class="links"><a href="#" class="to-top">Back to top <?= icon('arrow') ?></a></div>
    </div>
  </div>
</footer>
<?php if ($wa && !is_placeholder($wa)): ?>
<a class="wa-float" href="<?= e(wa_href($wa)) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"><?= icon('wa') ?><span>WhatsApp</span></a>
<?php endif;
    popup();
    ?>

</body>
</html>
<?php
}

/** Offer popup set in the admin. Shown by main.js after the delay, at most as often as configured. */
function popup(): void
{
    if (setting('popup_on') !== '1' || setting('popup_title') === '') return;
    $img = setting('popup_image');
    $link = link_url(setting('popup_link')) ?: url('contact');
    // The key changes whenever the popup is edited, so visitors see a new offer even if they closed the old one.
    $key = substr(md5(setting('popup_title') . setting('popup_text') . $img), 0, 8);
    ?>
<div class="popup" id="offer-popup" role="dialog" aria-modal="true" aria-labelledby="popup-title" hidden
     data-delay="<?= (int)setting('popup_delay', '6') ?>" data-every="<?= e(setting('popup_every', 'day')) ?>" data-key="<?= $key ?>">
  <div class="popup-card" tabindex="-1">
    <button class="popup-x" type="button" aria-label="Close" data-popup-close>×</button>
<?php if ($img): ?>    <img src="<?= e(media_url($img)) ?>" alt="" loading="lazy">
<?php endif; ?>
    <div class="popup-body">
      <h3 id="popup-title"><?= e(setting('popup_title')) ?></h3>
<?php if (setting('popup_text')): ?>      <p><?= nl2br(e(setting('popup_text'))) ?></p>
<?php endif; ?>
      <a class="btn btn--grey btn--lg" href="<?= e($link) ?>" data-popup-cta><?= e(setting('popup_button', 'Get started')) ?> <?= icon('arrow-ur') ?></a>
    </div>
  </div>
</div>
<?php
}

function page_hero(string $crumb, string $label, string $h1Html, string $lead, string $extra = ''): void
{ ?>
<section class="page-hero">
  <div class="hero-bg"><div class="blob blob--1"></div></div>
  <div class="ring" aria-hidden="true">
  <svg viewBox="0 0 200 200"><defs><path id="rp-page" d="M100,100 m-84,0 a84,84 0 1,1 168,0 a84,84 0 1,1 -168,0"/></defs><text><textPath href="#rp-page" textLength="524">Strategy ✺ Creative ✺ Digital ✺ Media ✺ Activation ✺ </textPath></text></svg>
  <div class="ring-core"><b>360<sup>°</sup></b></div>
</div>
  <div class="container">
    <div class="crumbs" data-reveal><a href="<?= url() ?>">Home</a><span>/</span><span><?= e($crumb) ?></span></div>
    <div class="label" data-reveal><?= e($label) ?></div>
    <h1 data-split><?= $h1Html ?></h1>
<?php if ($lead !== ''): ?>    <p class="lead" data-reveal><?= e($lead) ?></p>
<?php endif; ?>
<?= $extra ?>
  </div>
</section>
<?php }

function stats_section(): void
{
    $stats = items('stats');
    if (!$stats) return; ?>
<section class="section section--lime round" style="margin:0 clamp(8px,1.4vw,20px);overflow:hidden">
  <div class="big-360" aria-hidden="true">360°</div>
  <div class="container" style="position:relative">
    <div class="head">
      <div><div class="label" data-reveal>By the numbers</div><h2 data-split><?= heading(t('global', 'stats_title')) ?></h2></div>
    </div>
    <div class="stats">
<?php foreach ($stats as $s): $v = is_numeric($s['value']) ? $s['value'] : '0'; ?>
      <div class="stat" data-reveal><b data-count="<?= e($v) ?>" data-suffix="<?= e($s['suffix']) ?>"><?= e($v . $s['suffix']) ?></b><span><?= e($s['label']) ?></span></div>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php }

function team_grid(array $members, bool $join = false): void
{
    echo '<div class="team-grid">';
    foreach ($members as $i => $m) {
        $name = trim((string)$m['name']);
        $ini = $m['initials'] ?: ($name ? initials($name) : initials((string)$m['role']));
        $soc = '';
        foreach (['linkedin' => ['in', 'LinkedIn'], 'instagram' => ['ig', 'Instagram']] as $k => [$ic, $lbl]) {
            if (safe_url($m[$k])) $soc .= '<a href="' . e($m[$k]) . '" target="_blank" rel="noopener" aria-label="' . $lbl . '">' . icon($ic) . '</a>';
        }
        printf('<div class="member m%d" data-tilt data-reveal style="transition-delay:%.2fs"><div class="card"><div class="art">%s</div>'
            . '<div class="who"><div><b>%s</b><span>%s</span></div>%s</div></div></div>',
            $i % 7 + 1, $i * .05,
            $m['photo'] ? '<img src="' . e(media_url($m['photo'])) . '" alt="' . e($name ?: $m['role']) . '" loading="lazy">' : '<span class="ini">' . e($ini) . '</span>',
            e($name ?: $m['role']), e($name ? $m['role'] : $m['dept']),
            $soc ? '<div class="soc">' . $soc . '</div>' : '');
    }
    if ($join) {
        $link = link_url(setting('careers_link')) ?: url('contact');
        echo '<a class="member member--join" href="' . e($link) . '" data-reveal data-cursor="Apply"><div class="card"><div><span class="plus">'
            . icon('plus') . '</span><h3>Join the crew</h3><p>We\'re always looking for bold strategists, creators and makers.</p></div></div></a>';
    }
    echo '</div>';
}

function thumb(array $p): string
{
    $img = $p['image'] ? '<img src="' . e(media_url($p['image'])) . '" alt="" loading="lazy">' : '';
    return sprintf('<div class="thumb thumb--%s%s">%s<div class="tt"><span>%s</span></div><div class="kb"><b>%s</b><small>%s</small></div></div>',
        e($p['theme'] ?: '1'), $img ? ' thumb--img' : '', $img, e($p['industry']), e($p['kpi']), e($p['kpi_label']));
}

function post_card(array $p, bool $big = false): string
{
    $date = date('d M Y', strtotime($p['published_at']));
    $img = $p['cover'] ? '<img src="' . e(media_url($p['cover'])) . '" alt="" loading="lazy">' : '<span class="post-ph" aria-hidden="true">' . e(mb_substr($p['title'], 0, 1)) . '</span>';
    return '<a class="post-card' . ($big ? ' post-card--big' : '') . '" href="' . url('blog/' . $p['slug']) . '" data-reveal data-cursor="Read">'
        . '<div class="post-img">' . $img . '</div><div class="post-body"><div class="post-meta">' . ($p['category'] ? '<span class="tag">' . e($p['category']) . '</span>' : '')
        . '<span>' . $date . ' · ' . reading_time((string)$p['body']) . ' min read</span></div>'
        . '<h3>' . e($p['title']) . '</h3>' . ($p['excerpt'] ? '<p>' . e($p['excerpt']) . '</p>' : '') . '</div></a>';
}

function note(string $text): void
{
    if (setting('show_notes') === '1') echo '<p class="note">' . e($text) . '</p>';
}

function cta_section(string $page): void
{ ?>
<section class="section cta">
  <div class="ring" aria-hidden="true">
  <svg viewBox="0 0 200 200"><defs><path id="rp-cta" d="M100,100 m-84,0 a84,84 0 1,1 168,0 a84,84 0 1,1 -168,0"/></defs><text><textPath href="#rp-cta" textLength="524">Let’s talk ✺ Let’s create ✺ Let’s grow ✺ </textPath></text></svg>
</div>
  <div class="container cta-inner">
    <div>
      <div class="label" data-reveal>Next step</div>
      <h2 data-split><?= heading(t($page, 'cta_title')) ?></h2>
      <p data-reveal><?= e(t($page, 'cta_text')) ?></p>
    </div>
    <a href="<?= url('contact') ?>" class="btn-orb" data-magnetic=".45" data-reveal><span><?= heading(t($page, 'cta_button')) ?><?= icon('arrow-ur') ?></span></a>
  </div>
</section>
<?php }
