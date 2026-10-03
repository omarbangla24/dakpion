<?php
defined('ADMIN') || exit;

$sub = $seg[1] ?? '';
$base = aurl('blog');

function post_state(array $p): string
{
    if ($p['status'] !== 'published') return 'draft';
    return $p['published_at'] > now() ? 'scheduled' : 'published';
}

function unique_slug(string $slug, int $id): string
{
    $try = $slug;
    for ($n = 2; ; $n++) {
        $st = db()->prepare('SELECT COUNT(*) FROM posts WHERE slug = ? AND id <> ?');
        $st->execute([$try, $id]);
        if (!$st->fetchColumn()) return $try;
        $try = "$slug-$n";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $st = db()->prepare('SELECT * FROM posts WHERE id = ?');
    $st->execute([$id]);
    $old = $st->fetch() ?: null;

    if (($_POST['action'] ?? '') === 'delete') {
        if ($old) {
            db()->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
            log_activity('Deleted blog post', $old['title']);
            flash('Post deleted.');
        }
        redirect($base);
    }

    $in = $_POST['f'] ?? [];
    $title = trim((string)($in['title'] ?? ''));
    $status = ($_POST['publish'] ?? '') === '1' ? 'published' : (($_POST['unpublish'] ?? '') === '1' ? 'draft' : ($old['status'] ?? 'draft'));
    $when = trim((string)($in['published_at'] ?? ''));
    $when = $when !== '' && strtotime($when) ? date('Y-m-d H:i:s', strtotime($when)) : ($old['published_at'] ?? null);
    if ($status === 'published' && !$when) $when = now();
    $data = [
        'title' => $title,
        'slug' => unique_slug(slugify(trim((string)($in['slug'] ?? '')) ?: $title), $id),
        'excerpt' => mb_substr(trim((string)($in['excerpt'] ?? '')), 0, 400),
        'body' => clean_html((string)($in['body'] ?? '')),
        'category' => mb_substr(trim((string)($in['category'] ?? '')), 0, 60),
        'status' => $status,
        'published_at' => $when,
        'seo_title' => mb_substr(trim((string)($in['seo_title'] ?? '')), 0, 90),
        'seo_desc' => mb_substr(trim((string)($in['seo_desc'] ?? '')), 0, 200),
        'updated_at' => now(),
    ];
    try {
        if ($title === '') throw new RuntimeException('Title lagbe.');
        $data += collect_fields(['cover' => ['type' => 'image', 'label' => 'Cover']]);
        if ($id && $old) {
            $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
            db()->prepare("UPDATE posts SET $set WHERE id = ?")->execute([...array_values($data), $id]);
        } else {
            $id = insert_row(db(), 'posts', $data + ['author_id' => current_user()['id'], 'created_at' => now()]);
        }
        $verb = $status === 'published' && ($old['status'] ?? '') !== 'published' ? 'Published blog post' : ($status === 'draft' && ($old['status'] ?? '') === 'published' ? 'Unpublished blog post' : 'Saved blog post');
        log_activity($verb, $title);
        flash($status === 'published' ? ($when > now() ? 'Scheduled — ' . date('d M, g:i a', strtotime($when)) . ' e publish hobe.' : 'Published!') : 'Draft saved.');
        redirect("$base/$id");
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'err');
        $draft = $data + ['cover' => (string)($in['cover'] ?? '')];
        $sub = $id ? (string)$id : 'new';
    }
}

/* ── Editor ── */
if ($sub !== '') {
    $id = $sub === 'new' ? 0 : (int)$sub;
    $st = db()->prepare('SELECT * FROM posts WHERE id = ?');
    $st->execute([$id]);
    $p = $id ? $st->fetch() : null;
    if ($id && !$p) redirect($base);
    $p = array_merge(['title' => '', 'slug' => '', 'excerpt' => '', 'body' => '', 'cover' => '', 'category' => '', 'status' => 'draft',
        'published_at' => '', 'seo_title' => '', 'seo_desc' => '', 'views' => 0], $p ?: [], $draft ?? []);
    $state = $id ? post_state($p) : 'draft';
    $cats = db()->query("SELECT DISTINCT category FROM posts WHERE category <> '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
    $live = $state === 'published' ? '<a class="btn btn--ghost" href="' . url('blog/' . $p['slug']) . '" target="_blank" rel="noopener">' . aicon('eye', 16) . '<span>View post</span></a>' : '';
    admin_head($id ? 'Edit post' : 'New post', 'blog', ['Blog' => $base], $live);
    ?>
<form method="post" enctype="multipart/form-data" class="split-form editor-form" data-dirty>
  <?= csrf() ?>
  <input type="hidden" name="id" value="<?= $id ?>">
  <div class="card form">
    <div class="field"><input class="title-input" name="f[title]" value="<?= e($p['title']) ?>" placeholder="Post title" required aria-label="Title" data-slug-source></div>
    <div class="field slug-field"><label for="slug"><?= e(preg_replace('~^https?://~', '', abs_url('blog/'))) ?></label><input id="slug" name="f[slug]" value="<?= e($p['slug']) ?>" placeholder="auto-from-title" data-slug></div>
    <div class="field">
      <div class="rte" data-rte>
        <div class="rte-bar" role="toolbar" aria-label="Formatting">
          <select data-block aria-label="Text style"><option value="p">Paragraph</option><option value="h2">Heading</option><option value="h3">Sub-heading</option></select>
          <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
          <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
          <span class="sep"></span>
          <button type="button" data-cmd="insertUnorderedList" title="Bullet list">• List</button>
          <button type="button" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
          <button type="button" data-cmd="formatBlock" data-arg="blockquote" title="Quote">“ Quote</button>
          <span class="sep"></span>
          <button type="button" data-link title="Link">Link</button>
          <button type="button" data-image title="Image"><?= aicon('image', 15) ?> Image</button>
          <button type="button" data-cmd="insertHorizontalRule" title="Divider">—</button>
          <span class="sep"></span>
          <button type="button" data-cmd="removeFormat" title="Clear formatting">Clear</button>
          <button type="button" data-cmd="undo" title="Undo">↶</button>
          <button type="button" data-cmd="redo" title="Redo">↷</button>
          <button type="button" data-source title="Edit HTML" class="push">&lt;/&gt;</button>
        </div>
        <div class="rte-area prose-admin" contenteditable="true" data-placeholder="Ekhane likhun…"><?= $p['body'] ?></div>
        <textarea name="f[body]" class="rte-src" hidden><?= e($p['body']) ?></textarea>
      </div>
    </div>
    <?= render_field('excerpt', ['type' => 'textarea', 'label' => 'Short summary (blog list e dekhabe)'], $p['excerpt']) ?>
  </div>

  <aside class="side-stack">
    <section class="card side-card">
      <h2>Publish</h2>
      <p class="state state--<?= $state ?>"><?= ['draft' => 'Draft — site e dekhay na', 'published' => 'Published — site e live', 'scheduled' => 'Scheduled'][$state] ?><?= $id ? ' · ' . (int)$p['views'] . ' views' : '' ?></p>
      <div class="field"><label for="pa">Publish date</label><input id="pa" type="datetime-local" name="f[published_at]" value="<?= $p['published_at'] ? date('Y-m-d\TH:i', strtotime($p['published_at'])) : '' ?>"><small>Future date dile oi shomoy auto publish hobe.</small></div>
      <div class="actions">
<?php if ($p['status'] === 'published'): ?>
        <button class="btn btn--main" type="submit">Update</button>
        <button class="btn" type="submit" name="unpublish" value="1">Unpublish</button>
<?php else: ?>
        <button class="btn btn--main" type="submit" name="publish" value="1">Publish</button>
        <button class="btn" type="submit">Save draft</button>
<?php endif; ?>
      </div>
    </section>
    <section class="card side-card">
      <h2>Cover image</h2>
      <?= render_field('cover', ['type' => 'image', 'label' => 'Cover (16:9 best)'], $p['cover']) ?>
      <div class="field"><label for="cat">Category</label><input id="cat" name="f[category]" value="<?= e($p['category']) ?>" list="cats" placeholder="e.g. Social media"><datalist id="cats"><?php foreach ($cats as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist></div>
    </section>
    <section class="card side-card">
      <h2>SEO</h2>
      <div class="serp" data-serp>
        <span class="serp-url"><?= e(preg_replace('~^https?://~', '', abs_url('blog/'))) ?><span data-serp-slug><?= e($p['slug']) ?></span></span>
        <b class="serp-title" data-serp-title><?= e($p['seo_title'] ?: $p['title']) ?></b>
        <span class="serp-desc" data-serp-desc><?= e($p['seo_desc'] ?: $p['excerpt']) ?></span>
      </div>
      <div class="field"><label for="st">SEO title</label><input id="st" name="f[seo_title]" value="<?= e($p['seo_title']) ?>" maxlength="90" placeholder="Khali = post title" data-seo-title></div>
      <div class="field"><label for="sd">SEO description</label><textarea id="sd" name="f[seo_desc]" rows="3" maxlength="200" placeholder="Khali = summary" data-seo-desc><?= e($p['seo_desc']) ?></textarea></div>
    </section>
<?php if ($id): ?>
    <button class="btn btn--danger-text" type="submit" name="action" value="delete" form="del-post" data-confirm="Post ta delete korben?"><?= aicon('trash', 15) ?>Delete post</button>
<?php endif; ?>
  </aside>
</form>
<?php if ($id): ?><form id="del-post" method="post"><?= csrf() ?><input type="hidden" name="id" value="<?= $id ?>"></form><?php endif; ?>
<?php
    admin_foot();
    return;
}

/* ── List ── */
$filter = $_GET['status'] ?? 'all';
$all = db()->query('SELECT p.*, u.username FROM posts p LEFT JOIN users u ON u.id = p.author_id ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC')->fetchAll();
$counts = ['all' => count($all), 'published' => 0, 'scheduled' => 0, 'draft' => 0];
foreach ($all as $p) $counts[post_state($p)]++;
$rows = $filter === 'all' ? $all : array_filter($all, fn($p) => post_state($p) === $filter);

admin_head('Blog', 'blog', [], '<a class="btn btn--main" href="' . $base . '/new">' . aicon('plus', 16) . '<span>New post</span></a>');
?>
<div class="toolbar">
  <nav class="tabs tabs--inline"><?php foreach (['all' => 'All', 'published' => 'Published', 'scheduled' => 'Scheduled', 'draft' => 'Drafts'] as $k => $l): ?><a href="<?= $base . ($k === 'all' ? '' : '?status=' . $k) ?>"<?= $filter === $k ? ' aria-current="page"' : '' ?>><?= $l ?> <span><?= $counts[$k] ?></span></a><?php endforeach; ?></nav>
  <label class="search"><?= aicon('search', 16) ?><input type="search" placeholder="Search posts…" data-filter-rows="#posts" aria-label="Search"></label>
</div>
<?php if (!$all): ?>
<div class="card empty"><h2>Prothom blog post likhun</h2><p class="muted">Post publish korle site er menu te "Blog" ashbe, ar Home page e latest post dekhabe.</p><a class="btn btn--main" href="<?= $base ?>/new"><?= aicon('plus', 16) ?>New post</a></div>
<?php else: ?>
<div class="card table-wrap">
<table class="table">
  <thead><tr><th></th><th>Title</th><th>Category</th><th>Status</th><th>Date</th><th>Views</th><th class="act"></th></tr></thead>
  <tbody id="posts">
<?php foreach ($rows as $p): $s = post_state($p); ?>
    <tr>
      <td class="thumb-col"><?= $p['cover'] ? '<img class="thumbnail" src="' . e(media_url($p['cover'])) . '" alt="" loading="lazy">' : '<span class="thumbnail thumbnail--empty"></span>' ?></td>
      <td><a class="rowlink" href="<?= $base . '/' . $p['id'] ?>"><?= e($p['title']) ?></a><small><?= e($p['username'] ?? '') ?></small></td>
      <td><?= e($p['category']) ?></td>
      <td><span class="badge badge--<?= $s ?>"><?= ucfirst($s) ?></span></td>
      <td class="nowrap muted"><?= $p['published_at'] ? date('d M Y', strtotime($p['published_at'])) : '—' ?></td>
      <td><?= (int)$p['views'] ?></td>
      <td class="act">
<?php if ($s === 'published'): ?>        <a class="ib" href="<?= url('blog/' . $p['slug']) ?>" target="_blank" rel="noopener" title="View" aria-label="View"><?= aicon('eye', 17) ?></a>
<?php endif; ?>
        <a class="ib" href="<?= $base . '/' . $p['id'] ?>" title="Edit" aria-label="Edit"><?= aicon('edit', 17) ?></a>
      </td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif;
admin_foot();
