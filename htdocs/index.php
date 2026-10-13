<?php require 'lib.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = trim($_POST['email'] ?? '');
    // The password is never read or stored: this prototype tests the second factor only.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter the email address linked to your account.';
    else {
        $t = start_request($email, DEV_MODE && isset($_POST['simulate_failure']));
        if ($t === 'locked') go('locked.php?e=' . urlencode($email));
        if ($t === 'iplimit') $err = 'Too many sign-in emails were requested from your network. Try again in an hour.';
        elseif ($t === 'mailfail') go('recovery.php?t=' . $GLOBALS['backup_token']);
        else go('verify.php?t=' . $t);
    }
}
else set_bind(''); // opening the login page signs out
top('Log in'); ?>
  <h1>LOG IN</h1>
  <form method="post" class="form" novalidate>
    <?= csrf_field() ?>
    <label for="email">Email</label>
    <input id="email" name="email" type="email" autocomplete="username" required value="<?= h($_POST['email'] ?? (DEV_MODE && isset($_GET['demo']) ? 'classroom-demo@example.test' : '')) ?>"
      <?= $err ? 'aria-invalid="true" aria-describedby="err"' : '' ?>>
    <label for="pw">Password</label>
    <input id="pw" name="password" type="password" autocomplete="off" required>
    <?php if ($err): ?><p id="err" class="error" role="alert"><?= h($err) ?></p><?php endif; ?>
    <button class="btn btn-primary" type="submit">Log In</button>
    <?php if (DEV_MODE): ?><label><input type="checkbox" name="simulate_failure" value="1"> Demo: simulate email sending failure</label><?php endif; ?>
  </form>
  <?php if (DEV_MODE): ?><p class="lead small"><a href="demo.php">Prepare a classroom demo account</a></p><?php endif; ?>
<?php bottom();
