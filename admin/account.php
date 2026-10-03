<?php
require __DIR__ . '/_admin.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $st = db()->prepare('SELECT pass_hash FROM users WHERE id = ?');
    $st->execute([$user['id']]);
    $new = (string)($_POST['new'] ?? '');
    if (!password_verify((string)($_POST['current'] ?? ''), (string)$st->fetchColumn())) flash('Current password vul.', 'err');
    elseif (strlen($new) < 8) flash('Notun password kom kore 8 character.', 'err');
    elseif ($new !== ($_POST['new2'] ?? '')) flash('Duita notun password mile nai.', 'err');
    else {
        db()->prepare('UPDATE users SET pass_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        session_regenerate_id(true);
        flash('Password change hoyeche.');
    }
    redirect('account.php');
}

admin_head('Change password', 'account');
?>
<form class="card form narrow" method="post">
  <?= csrf() ?>
  <p class="muted">Logged in as <b><?= e($user['username']) ?></b></p>
  <div class="field"><label for="c">Current password</label><input id="c" type="password" name="current" required autocomplete="current-password"></div>
  <div class="field"><label for="n">New password</label><input id="n" type="password" name="new" required minlength="8" autocomplete="new-password"></div>
  <div class="field"><label for="n2">New password again</label><input id="n2" type="password" name="new2" required minlength="8" autocomplete="new-password"></div>
  <div class="actions"><button class="btn btn--main" type="submit">Change password</button></div>
</form>
<?php admin_foot();
