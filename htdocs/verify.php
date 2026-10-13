<?php require 'lib.php';
$t = $_GET['t'] ?? ''; $r = request($t);
if (!$r || !owns($r)) go('index.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r['status'] = 'cancelled'; save('req_' . $t, $r);
    if (($_POST['action'] ?? '') === 'resend') { // a resend counts toward the rate limit
        $n = start_request($r['email']);
        if ($n === 'locked') go('locked.php?e=' . urlencode($r['email']));
        if (strlen($n) === 32) go('verify.php?t=' . $n);
    }
    go('index.php');
}
$s = status($r);
if ($s === 'locked') go('locked.php?e=' . urlencode($r['email']));
if ($s === 'approved') go('done.php?t=' . $t);
$msg = ['expired' => 'This number expired. Select Resend Number to get a new one.',
        'wrong' => 'A different number was selected in the email, so this attempt was blocked and sign-ins are paused for a short time.',
        'cancelled' => 'This sign-in was cancelled.'][$s] ?? '';
top('Verify your identity'); ?>
  <h1>VERIFY YOUR IDENTITY</h1>
  <p class="notice"><svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 2 21h20L12 3z" fill="#f5b800" stroke="#7a5a00" stroke-width="1.5" stroke-linejoin="round"/><path d="M12 10v5M12 17.6v.4" stroke="#1a1a1a" stroke-width="2" stroke-linecap="round"/></svg>
    <span><strong>SECURITY NOTICE:</strong> <?= h(BRAND) ?> Staff will NEVER call, text, or email you for this number. Do not share it with anyone.</span></p>
  <p class="lead">An authorization email was sent to <?= h(mask($r['email'])) ?>.<br>Open your email and select the number that matches this one:</p>
  <div class="num" aria-label="Your sign-in number"><?= h($r['num']) ?></div>
  <p id="state" class="state" role="status"><?= $msg ? h($msg) : 'Waiting for you to choose a number in the email…' ?></p>
  <div class="details">
    <strong>CURRENT LOGIN DETAILS:</strong>
    <ul><li>Device: <?= h($r['device']) ?></li><li>Location: <?= h($r['location']) ?></li><li>Time: <?= h($r['time']) ?></li></ul>
  </div>
  <form method="post" class="actions">
    <button class="btn" name="action" value="resend">Resend Number</button>
    <button class="btn" name="action" value="cancel">Cancel Login</button>
  </form>
<?php if ($s === 'pending'): ?>
<script>
// check every 3s whether the user picked a number in the email
const msgs = <?= json_encode(['expired' => 'This number expired. Select Resend Number to get a new one.',
  'wrong' => 'A different number was selected in the email, so this attempt was blocked and sign-ins are paused for a short time.']) ?>;
const timer = setInterval(async () => {
  try {
    const s = (await (await fetch('status.php?t=<?= $t ?>', {cache: 'no-store'})).json()).status;
    if (s === 'approved') location = 'done.php?t=<?= $t ?>';
    else if (s === 'locked') location = 'locked.php?e=<?= urlencode($r['email']) ?>';
    else if (msgs[s]) { document.getElementById('state').textContent = msgs[s]; clearInterval(timer); }
  } catch (e) {}
}, 3000);
</script>
<?php endif; bottom();
