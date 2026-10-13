<?php require 'lib.php';
$t = $_GET['t'] ?? ''; $r = request($t);
if (!$r || !owns($r) || ($r['method'] ?? '') !== 'recovery') go('index.php');
$s = status($r);
if ($s === 'locked') go('locked.php?e=' . urlencode($r['email']));
if ($s === 'approved') go('done.php?t=' . $t);
$err = ''; $receipt = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'code') {
        $result = verify_recovery_code($t, (string)($_POST['code'] ?? ''));
        if ($result === 'approved') go('done.php?t=' . $t);
        if ($result === 'locked') go('locked.php?e=' . urlencode($r['email']));
        $err = 'This verification request is no longer active. Start a new sign-in.';
    } elseif ($action === 'email') {
        $n = restart_email($t);
        if ($n === 'locked') go('locked.php?e=' . urlencode($r['email']));
        if ($n === 'mailfail') go('recovery.php?t=' . $GLOBALS['backup_token']);
        if (preg_match('/^[a-f0-9]{32}$/', $n)) go('verify.php?t=' . $n);
        $err = $n === 'iplimit' ? 'Too many sign-in emails were requested from your network. Try again in an hour.' : 'Start a new sign-in to verify by email.';
    } elseif ($action === 'helpdesk') {
        $receipt = true;
    } elseif ($action === 'cancel') {
        account_lock($r['email'], function () use ($t) {
            $current = request($t); $current['status'] = 'cancelled'; save('req_' . $t, $current);
        });
        go('index.php');
    }
}
top('Security code verification'); ?>
  <h1>USE A SECURITY CODE</h1>
  <?php if (!empty($r['mail_failed'])): ?><p class="error" role="alert">We couldn't send your verification email. Use a saved security code or choose an option below.</p><?php endif; ?>
  <p class="lead">Enter your permanent security code for <?= h(mask($r['email'])) ?>. The same code works for future sign-ins.</p>
  <?php if ($s === 'pending' && has_recovery_codes($r['email'])): ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <label for="code">Security code</label>
    <input id="code" name="code" autocomplete="off" spellcheck="false" required maxlength="64">
    <button class="btn btn-primary" name="action" value="code">Verify security code</button>
  </form>
  <?php elseif ($s !== 'pending'): ?><p class="error">This request expired or was cancelled. Start a new sign-in.</p>
  <?php else: ?><p class="state">Your security code is not set up. Verify by email to complete first-time setup.</p><?php endif; ?>
  <?php if ($err): ?><p class="error" role="alert"><?= h($err) ?></p><?php endif; ?>
  <details class="next"><summary>Forgot your security code or need to change it?</summary>
    <p>Security code changes require a helpdesk request. You can still sign in by email if your inbox is accessible.</p>
    <form method="post" class="actions"><?= csrf_field() ?>
      <button class="btn" name="action" value="helpdesk">Request security code change through helpdesk</button>
    </form>
  </details>
  <form method="post" class="actions"><?= csrf_field() ?>
    <button class="btn" name="action" value="email">Try email verification</button>
    <button class="btn" name="action" value="cancel">Cancel Login</button>
  </form>
  <?php if ($receipt): ?>
  <p class="state" role="status">Simulation: the helpdesk has received your security code change request. Your code remains unchanged. No real request was sent.</p>
  <script>alert('Simulation: the helpdesk has received your security code change request. Your code remains unchanged. No real request was sent.');</script>
  <?php endif; ?>
<?php bottom();
