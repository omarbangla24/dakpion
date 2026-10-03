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

## Admin panel (`/admin`)

| Section | What you can do |
| --- | --- |
| Dashboard | Visits and messages per day, conversion rate, pipeline, top pages, follow-ups due, launch checklist |
| Messages | Leads from the contact form: status (New → Contacted → Proposal → Won/Lost), notes, follow-up date, search, CSV |
| Pages & SEO | Every page's headings, text and buttons, plus SEO title and description with a Google preview |
| Blog | Write posts in a visual editor (headings, lists, links, images), categories, cover, schedule, SEO |
| Media library | Drag-and-drop upload, search, copy link, delete (warns where an image is used); every image field can pick from it |
| Offer bar & popup | Announcement bar on top of every page, and a timed popup with "show again" frequency |
| Portfolio, Team, Testimonials, Client logos, Stats, FAQ, Services, Packages, Industries, Process, Values, Journey | Add, edit, duplicate, delete, hide, drag to reorder |
| Settings (admin only) | Contact info, social links, company, contact form, SEO & tracking IDs |
| Users (admin only) | Staff accounts. **Admin** has full access. **Editor** can manage content, pages, blog, media, messages and offers, but not settings, users or backup |
| Activity log (admin only) | Who changed what, and when |
| Backup (admin only) | One-click download of the database plus images (.zip), or the database alone |

## Clean URLs

Pages live at `/about`, `/services`, `/blog/post-name` and so on. `.htaccess` sends old `.html` and `.php` links to these addresses with permanent redirects. `/sitemap.xml` and `/robots.txt` are generated automatically. For local testing without Apache, run `php -S localhost:8000 tools/dev-router.php`.

## Email notifications

The form sends mail with PHP `mail()`. Most cPanel hosts deliver it. If emails don't arrive, check spam. Every message is also saved under **Messages**, so nothing is lost.

## nginx

nginx ignores `.htaccess`. Add these rules to the server block:

```nginx
index index.php;
location ~ ^/(data|inc|tools|admin/views|admin/inc)/ { deny all; }
location ~ /\.(?!well-known) { deny all; }
location ~ \.(sqlite|md)$ { deny all; }
location ^~ /uploads/ { location ~ \.php$ { deny all; } }
rewrite ^/(about|services|industries|portfolio|contact)\.(html|php)$ /$1 permanent;
location = /sitemap.xml { rewrite ^ /sitemap.php last; }
location = /robots.txt  { rewrite ^ /robots.php last; }
location ~ ^/blog/([A-Za-z0-9-]+)/?$ { rewrite ^/blog/([A-Za-z0-9-]+)/?$ /blog-post.php?slug=$1 last; }
location /admin/ { try_files $uri /admin/index.php?$query_string; }
location / { try_files $uri $uri/ $uri.php?$query_string; }
```

## Extra safety (optional)

To keep the database outside the web root, create `inc/local.php`:

```php
<?php define('DATA_DIR', '/home/youraccount/dakpion-data');
```

## Backup

Download `data/site.sqlite` and the `uploads/` folder. Those two hold all content and images. The code itself is in git.
