# Dakpion IMC website — setup guide

Plain PHP site with a built-in admin panel. It needs no framework or Composer, and the database is a single SQLite file.

## Requirements

- PHP 8.1 or newer with `pdo_sqlite` (enabled on almost every cPanel host)
- `gd` with WebP support (optional): uploaded images are resized and converted to WebP. Without it, the original image is kept.
- Apache with `.htaccess` (cPanel), or nginx with the rules below

## Upload (cPanel)

1. Upload every file in this repo into `public_html/`, or into a subfolder.
2. Make `data/` and `uploads/` writable (permission 755 or 775).
3. Open `https://your-domain.com/admin/` and create the admin username and password on the first screen.
   **Do this right after uploading.** Until an account exists, anyone who opens `/admin/` can create one.
4. Fill in the dashboard's **Launch checklist**: phone, address, social links, form email, team, portfolio and so on.

The database (`data/site.sqlite`) is created automatically on the first visit and filled with the current site content.

## Admin panel

| Section | What you can edit |
| --- | --- |
| Messages | Contact form submissions: read, reply, mark unread, delete, download CSV |
| Contact info | Email, phone, WhatsApp (shows a floating button), address, map, hours |
| Social links | Facebook, Instagram, LinkedIn, YouTube, TikTok (only filled ones show) |
| Company | Brand and legal name, tagline, About page story, careers link |
| Home hero | Headline, the scroll-changing words, sub text, "Who we are" text |
| Contact form | Notification email, budget options, success message |
| SEO & tracking | Share image, GA4 ID, Meta Pixel ID, Search Console code, placeholder notes |
| Portfolio, Team, Testimonials, Client logos, Stats, FAQ | Add, edit, delete, reorder, hide |
| Services, Packages, Industries, Process, Values, Journey | Page content |

## Email notifications

The form sends mail with PHP `mail()`. Most cPanel hosts deliver it. If emails don't arrive, check spam. Every message is also saved under **Messages**, so nothing is lost.

## Old links

`.htaccess` permanently redirects the old `about.html`, `services.html` and similar URLs to the new `.php` pages.

## nginx

nginx ignores `.htaccess`. Add these rules to the server block:

```nginx
index index.php;
location ~ ^/(data|inc)/ { deny all; }
location ~ /\.(git|ht) { deny all; }
location ~ \.(sqlite|md)$ { deny all; }
location ^~ /uploads/ { location ~ \.php$ { deny all; } }
rewrite ^/(index|about|services|industries|portfolio|contact)\.html$ /$1.php permanent;
```

## Extra safety (optional)

To keep the database outside the web root, create `inc/local.php`:

```php
<?php define('DATA_DIR', '/home/youraccount/dakpion-data');
```

## Backup

Download `data/site.sqlite` and the `uploads/` folder. Those two hold all content and images. The code itself is in git.
