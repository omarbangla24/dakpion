<?php
defined('ADMIN') || exit;
require_admin();

$me = current_user();
$sub = $seg[1] ?? '';

function active_admins(int $exceptId = 0): int
{
    $st = db()->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1 AND id <> ?");
    $st->execute([$exceptId]);
    return (int)$st->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $st = db()->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$id]);
    $old = $st->fetch() ?: null;

    if (($_POST['action'] ?? '') === 'delete') {
        if (!$old) redirect(aurl('users'));
        if ($id === (int)$me['id']) flash('Nijer account delete kora jabe na.', 'err');
        elseif ($old['role'] === 'admin' && !active_admins($id)) flash('Kom kore ekjon Admin thakte hobe.', 'err');
        else {
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            log_activity('Deleted user', $old['username']);
            flash('User deleted.');
        }
        redirect(aurl('users'));
    }

    $data = [
        'name' => trim((string)($_POST['name'] ?? '')),
        'username' => trim((string)($_POST['username'] ?? '')),
        'email' => trim((string)($_POST['email'] ?? '')),
        'role' => isset(ROLES[$_POST['role'] ?? '']) ? $_POST['role'] : 'editor',
        'active' => !empty($_POST['active']) ? 1 : 0,
    ];
    $pass = (string)($_POST['password'] ?? '');
    $dupe = db()->prepare('SELECT COUNT(*) FROM users WHERE (username = ? OR (email = ? AND email <> \'\')) AND id <> ?');
    $dupe->execute([$data['username'], $data['email'], $id]);

    $error = match (true) {
        !preg_match('/^[\w.@-]{3,40}$/', $data['username']) => 'Username 3–40 character (letter, number, . _ - @).',
        $data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL) => 'Email ta thik na.',
        (bool)$dupe->fetchColumn() => 'Ei username ba email already ache.',
        !$id && strlen($pass) < 8, $pass !== '' && strlen($pass) < 8 => 'Password kom kore 8 character.',
        $id === (int)$me['id'] && ($data['role'] !== 'admin' || !$data['active']) => 'Nijer Admin role ba access bondho kora jabe na.',
        $old && $old['role'] === 'admin' && ($data['role'] !== 'admin' || !$data['active']) && !active_admins($id) => 'Kom kore ekjon active Admin thakte hobe.',
        default => '',
    };
    if ($error) {
        flash($error, 'err');
        $draft = $data;
        $sub = $id ?: 'new';
    } else {
        if ($pass !== '') $data['pass_hash'] = password_hash($pass, PASSWORD_DEFAULT);
        if ($id) {
            $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
            db()->prepare("UPDATE users SET $set WHERE id = ?")->execute([...array_values($data), $id]);
            log_activity('Edited user', $data['username'], ($pass !== '' ? 'password changed · ' : '') . 'role: ' . $data['role'] . ($data['active'] ? '' : ' · disabled'));
        } else {
            insert_row(db(), 'users', $data + ['created_at' => now()]);
            log_activity('Added user', $data['username'], 'role: ' . $data['role']);
        }
        flash('User saved.');
        redirect(aurl('users'));
    }
}

if ($sub !== '') {
    $id = $sub === 'new' ? 0 : (int)$sub;
    $st = db()->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$id]);
    $u = $id ? $st->fetch() : ['name' => '', 'username' => '', 'email' => '', 'role' => 'editor', 'active' => 1];
    if (!$u) redirect(aurl('users'));
    $u = array_merge($u, $draft ?? []);
    admin_head($id ? 'Edit user' : 'New user', 'users', ['Users' => aurl('users')]);
    ?>
<form class="card form form-grid" method="post">
  <?= csrf() ?>
  <input type="hidden" name="id" value="<?= $id ?>">
  <div class="field"><label for="nm">Name</label><input id="nm" name="name" value="<?= e($u['name']) ?>"></div>
  <div class="field"><label for="un">Username <em>*</em></label><input id="un" name="username" required value="<?= e($u['username']) ?>" autocomplete="off"></div>
  <div class="field"><label for="em">Email</label><input id="em" type="email" name="email" value="<?= e($u['email']) ?>"></div>
  <div class="field"><label for="pw">Password<?= $id ? '' : ' <em>*</em>' ?></label><input id="pw" type="password" name="password" minlength="8" autocomplete="new-password"<?= $id ? '' : ' required' ?>><small><?= $id ? 'Bodlate na chaile khali rakhun.' : 'Kom kore 8 character. User ke eta janiye din.' ?></small></div>
  <div class="field wide"><label>Role</label>
    <div class="radio-cards">
      <label><input type="radio" name="role" value="admin"<?= $u['role'] === 'admin' ? ' checked' : '' ?>><span><b>Admin</b><small>Shob kichu — settings, users, backup soho</small></span></label>
      <label><input type="radio" name="role" value="editor"<?= $u['role'] !== 'admin' ? ' checked' : '' ?>><span><b>Editor</b><small>Content, pages, blog, media, messages, offer — settings/users/backup chara</small></span></label>
    </div>
  </div>
  <div class="field field--check wide"><label class="switch"><input type="checkbox" name="active" value="1"<?= $u['active'] ? ' checked' : '' ?>><i></i><span>Login korte parbe</span></label><small>Off korle account thakbe, kintu login kora jabe na.</small></div>
  <div class="actions wide"><button class="btn btn--main" type="submit">Save user</button><a class="btn btn--ghost" href="<?= aurl('users') ?>">Cancel</a></div>
</form>
<?php
    admin_foot();
    return;
}

$users = db()->query('SELECT * FROM users ORDER BY role, username')->fetchAll();
admin_head('Users', 'users', [], '<a class="btn btn--main" href="' . aurl('users/new') . '">' . aicon('plus', 16) . '<span>Add user</span></a>');
?>
<div class="card table-wrap">
<table class="table">
  <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Last login</th><th class="act"></th></tr></thead>
  <tbody>
<?php foreach ($users as $u): ?>
    <tr class="<?= $u['active'] ? '' : 'off' ?>">
      <td><div class="user-cell"><span class="av"><?= e(initials($u['name'] ?: $u['username'])) ?></span><span><a class="rowlink" href="<?= aurl('users/' . $u['id']) ?>"><?= e($u['name'] ?: $u['username']) ?></a><small>@<?= e($u['username']) ?><?= (int)$u['id'] === (int)$me['id'] ? ' · you' : '' ?></small></span></div></td>
      <td><?= e($u['email']) ?: '<span class="muted">—</span>' ?></td>
      <td><span class="badge badge--<?= e($u['role'] ?: 'admin') ?>"><?= e(ROLES[$u['role'] ?: 'admin'] ?? $u['role']) ?></span><?= $u['active'] ? '' : ' <span class="badge">Disabled</span>' ?></td>
      <td class="nowrap muted"><?= e(ago($u['last_login'])) ?></td>
      <td class="act">
        <a class="ib" href="<?= aurl('users/' . $u['id']) ?>" title="Edit" aria-label="Edit"><?= aicon('edit', 17) ?></a>
<?php if ((int)$u['id'] !== (int)$me['id']): ?>
        <form method="post"><?= csrf() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="ib danger" title="Delete" aria-label="Delete" data-confirm="<?= e($u['username']) ?> ke delete korben?"><?= aicon('trash', 17) ?></button></form>
<?php endif; ?>
      </td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
</div>
<p class="muted small">Admin: shob kichu. Editor: content, pages, blog, media, messages ar offer — settings, users ar backup chara.</p>
<?php admin_foot();
