<?php require 'lib.php';
$until = locked_until($_GET['e'] ?? '');
$mins = $until ? max(1, (int)ceil(($until - time()) / 60)) : 15;
top('Account protection locked'); ?>
  <h1>ACCOUNT PROTECTION LOCKED</h1>
  <svg class="shield" viewBox="0 0 90 110" aria-hidden="true"><path d="M5 12 Q45 0 85 12 L85 62 Q82 90 45 106 Q8 90 5 62 Z" fill="#f00" stroke="#1b1464" stroke-width="3"/></svg>
  <h2>Verification Attempts Temporarily Locked</h2>
  <p class="lead">Too many unverified or failed login attempts were detected for this account. For your security, verification is temporarily paused and no new emails will be sent.</p>
  <div class="next"><p>What to do next:</p>
    <ul><li>Wait <?= $mins ?> minute<?= $mins === 1 ? '' : 's' ?> before attempting to log in again.</li>
    <li>If you did not attempt to sign in, contact your IT Helpdesk at <a href="mailto:<?= h(HELPDESK) ?>"><?= h(HELPDESK) ?></a>.</li></ul></div>
  <div class="actions"><a class="btn" href="index.php">Return to Login Page</a></div>
<?php bottom();
