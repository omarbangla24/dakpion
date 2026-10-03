<?php
defined('ADMIN') || exit;

if (current_user()) redirect(aurl());

$setup = !has_users();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $user = trim((string)($_POST['username'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');

    if ($setup) {
        if (!preg_match('/^[\w.@-]{3,40}$/', $user)) $error = 'Username 3–40 character er hote hobe (letter, number, . _ - @).';
        elseif (strlen($pass) < 8) $error = 'Password kom kore 8 character.';
        elseif ($pass !== (string)($_POST['password2'] ?? '')) $error = 'Duita password mile nai.';
        elseif (has_users()) $error = 'Admin already toiri kora ache — login korun.';
        else {
            $id = insert_row(db(), 'users', ['username' => $user, 'name' => trim((string)($_POST['name'] ?? '')), 'role' => 'admin', 'active' => 1,
                'pass_hash' => password_hash($pass, PASSWORD_DEFAULT), 'created_at' => now()]);
            log_in(['id' => $id, 'username' => $user]);
            flash('Admin account toiri hoyeche. Shagotom!');
            redirect(aurl());
        }
    } elseif (too_many_attempts()) {
        $error = 'Onek bar vul hoyeche. 15 minute por abar try korun.';
    } else {
        $st = db()->prepare('SELECT id, username, pass_hash, active FROM users WHERE username = ? OR (email = ? AND email <> \'\')');
        $st->execute([$user, $user]);
        $row = $st->fetch();
        if ($row && password_verify($pass, $row['pass_hash'])) {
            if (!(int)$row['active']) {
                $error = 'Ei account ta bondho kora ache. Admin er shathe kotha bolun.';
            } else {
                if (password_needs_rehash($row['pass_hash'], PASSWORD_DEFAULT)) {
                    db()->prepare('UPDATE users SET pass_hash = ? WHERE id = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), $row['id']]);
                }
                log_in($row);
                $next = (string)($_GET['next'] ?? '');
                redirect(str_starts_with($next, aurl()) ? $next : aurl());
            }
        } else {
            db()->prepare('INSERT INTO login_attempts (ip, at) VALUES (?, ?)')->execute([$_SERVER['REMOTE_ADDR'] ?? '', time()]);
            $error = 'Username ba password vul.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= $setup ? 'Setup' : 'Login' ?> — Admin</title>
<link rel="icon" href="<?= url('assets/images/favicon.png') ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('admin/admin.css') ?>">
</head>
<body class="auth">
<div class="auth-wrap">
  <div class="auth-side" aria-hidden="true">
    <div class="auth-ring"><b>360°</b></div>
    <p>Website, leads, blog — sob ek jaygay.</p>
  </div>
  <form class="auth-card" method="post">
    <img src="<?= url('assets/images/logo-dark.webp') ?>" alt="Dakpion IMC">
    <h1><?= $setup ? 'Admin account toiri korun' : 'Welcome back' ?></h1>
    <p class="muted"><?= $setup ? 'Prothom bar — ekhane je username ar password diben, sheta diyei pore login korben.' : 'Admin panel e login korun.' ?></p>
    <?php if ($error): ?><div class="flash flash--err" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?= csrf() ?>
    <?php if ($setup): ?><div class="field"><label for="n">Your name</label><input id="n" name="name" autocomplete="name" value="<?= e($_POST['name'] ?? '') ?>"></div><?php endif; ?>
    <div class="field"><label for="u">Username<?= $setup ? '' : ' or email' ?></label><input id="u" name="username" required autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>" autofocus></div>
    <div class="field"><label for="p">Password</label><input id="p" type="password" name="password" required autocomplete="<?= $setup ? 'new-password' : 'current-password' ?>"<?= $setup ? ' minlength="8"' : '' ?>></div>
    <?php if ($setup): ?><div class="field"><label for="p2">Password abar</label><input id="p2" type="password" name="password2" required minlength="8" autocomplete="new-password"></div><?php endif; ?>
    <button class="btn btn--main btn--block" type="submit"><?= $setup ? 'Create account' : 'Log in' ?></button>
    <a class="auth-back" href="<?= url() ?>">← Back to website</a>
  </form>
</div>
</body>
</html>
