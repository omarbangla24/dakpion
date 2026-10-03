<?php
defined('ADMIN') || exit;

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $new = (string)($_POST['new'] ?? '');
    $st = db()->prepare('SELECT pass_hash FROM users WHERE id = ?');
    $st->execute([$user['id']]);
    $hash = (string)$st->fetchColumn();

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) flash('Email ta thik na.', 'err');
    elseif ($new !== '' && !password_verify((string)($_POST['current'] ?? ''), $hash)) flash('Current password vul.', 'err');
    elseif ($new !== '' && strlen($new) < 8) flash('Notun password kom kore 8 character.', 'err');
    elseif ($new !== '' && $new !== ($_POST['new2'] ?? '')) flash('Duita notun password mile nai.', 'err');
    else {
        db()->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?')->execute([$name, $email, $user['id']]);
        if ($new !== '') {
            db()->prepare('UPDATE users SET pass_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            session_regenerate_id(true);
            log_activity('Changed own password');
        }
        flash('Saved.');
    }
    redirect(aurl('account'));
}

admin_head('My account', 'account');
?>
<form class="card form narrow" method="post">
  <?= csrf() ?>
  <h2>Profile</h2>
  <p class="muted">Username: <b><?= e($user['username']) ?></b> · Role: <b><?= e(ROLES[$user['role']] ?? $user['role']) ?></b></p>
  <div class="field"><label for="nm">Name</label><input id="nm" name="name" value="<?= e($user['name']) ?>" autocomplete="name"></div>
  <div class="field"><label for="em">Email</label><input id="em" type="email" name="email" value="<?= e($user['email']) ?>" autocomplete="email"><small>Email diyeo login kora jabe.</small></div>
  <h2 class="mt">Change password</h2>
  <p class="muted">Password na bodlate chaile khali rakhun.</p>
  <div class="field"><label for="c">Current password</label><input id="c" type="password" name="current" autocomplete="current-password"></div>
  <div class="field"><label for="n">New password</label><input id="n" type="password" name="new" minlength="8" autocomplete="new-password"></div>
  <div class="field"><label for="n2">New password again</label><input id="n2" type="password" name="new2" minlength="8" autocomplete="new-password"></div>
  <div class="actions"><button class="btn btn--main" type="submit">Save</button></div>
</form>
<?php admin_foot();
