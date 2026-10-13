<?php require 'lib.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    // The password is never read or stored: this prototype tests the second factor only.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter the email address linked to your account.';
    else {
        $t = start_request($email);
        if ($t === 'locked') go('locked.php?e=' . urlencode($email));
        if ($t === 'iplimit') $err = 'Too many sign-in emails were requested from your network. Try again in an hour.';
        elseif ($t === 'mailfail') $err = "We couldn't send your verification email. Try again in a minute, or contact " . HELPDESK . '.'
            . (empty($GLOBALS['mail_error']) ? '' : ' (Details: ' . $GLOBALS['mail_error'] . ')');
        else go('verify.php?t=' . $t);
    }
}
else set_bind(''); // opening the login page signs out
top('Log in'); ?>
  <h1>LOG IN</h1>
  <form method="post" class="form" novalidate>
    <label for="email">Email</label>
    <input id="email" name="email" type="email" autocomplete="username" required value="<?= h($_POST['email'] ?? '') ?>"
      <?= $err ? 'aria-invalid="true" aria-describedby="err"' : '' ?>>
    <label for="pw">Password</label>
    <input id="pw" name="password" type="password" autocomplete="off" required>
    <?php if ($err): ?><p id="err" class="error" role="alert"><?= h($err) ?></p><?php endif; ?>
    <button class="btn btn-primary" type="submit">Log In</button>
  </form>
<?php bottom();
