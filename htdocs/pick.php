<?php require 'lib.php';
// Opened from the number buttons in the email. GET only shows a confirm step,
// so email link scanners that "pre-click" links can't approve or burn attempts.
$t = $_GET['t'] ?? ''; $r = request($t); $n = (string)($_GET['n'] ?? '');
$ok = $r && hash_equals($r['key'], (string)($_GET['k'] ?? '')) && in_array($n, $r['choices'], true);
$s = $ok ? status($r) : 'invalid';
$result = null;
if ($s === 'pending' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (hash_equals($r['num'], $n)) { $r['status'] = 'approved'; clear_strikes($r['email']); $result = 'approved'; }
    else { $r['status'] = 'wrong'; strike($r['email'], true); $result = 'wrong'; } // one wrong pick locks: blind guessing (1 in 9) gets one try per lockout
    save('req_' . $t, $r);
}
top('Confirm sign-in'); ?>
<?php if ($result === 'approved'): ?>
  <h1>SIGN-IN APPROVED</h1>
  <p class="lead">You can go back to your login screen. It will continue automatically.</p>
<?php elseif ($result === 'wrong'): ?>
  <h1>NUMBER DIDN'T MATCH</h1>
  <p class="lead">That sign-in was blocked, and new sign-ins are paused for <?= max(1, (int)ceil((locked_until($r['email']) - time()) / 60)) ?> minute(s).<br>If you weren't signing in, someone may know your password. Change it and contact <a href="mailto:<?= h(HELPDESK) ?>"><?= h(HELPDESK) ?></a>.</p>
<?php elseif ($result === 'locked' || $s === 'locked'): ?>
  <h1>ACCOUNT PROTECTION LOCKED</h1>
  <p class="lead">Too many attempts. Verification is paused for <?= max(1, (int)ceil((locked_until($r['email']) - time()) / 60)) ?> minute(s). If you didn't try to sign in, contact <a href="mailto:<?= h(HELPDESK) ?>"><?= h(HELPDESK) ?></a>.</p>
<?php elseif ($s !== 'pending'): ?>
  <h1>LINK NO LONGER ACTIVE</h1>
  <p class="lead">This sign-in request has expired, was already used, or was cancelled. No action is needed.</p>
<?php else: ?>
  <h1>CONFIRM YOUR NUMBER</h1>
  <p class="lead">You selected:</p>
  <div class="num"><?= h($n) ?></div>
  <p class="lead">Confirm only if this is the number on <strong>your</strong> login screen right now.</p>
  <div class="details"><strong>LOGIN BEING APPROVED:</strong>
    <ul><li>Device: <?= h($r['device']) ?></li><li>Location: <?= h($r['location']) ?></li><li>Time: <?= h($r['time']) ?></li></ul></div>
  <form method="post" class="actions"><button class="btn btn-primary">Confirm <?= h($n) ?></button></form>
  <p class="lead small">Not signing in? Close this page. Nothing will be approved.</p>
<?php endif; bottom();
