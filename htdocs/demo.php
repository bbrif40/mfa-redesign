<?php require 'lib.php';
if (!DEV_MODE) { http_response_code(404); exit('Not found'); }
$email = 'classroom-demo@example.test'; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $result = account_lock($email, function () use ($email) {
        if (locked_until($email)) return false;
        $a = security_account($email);
        if (!empty($a['recovery_hashes'])) return true;
        $codes = generate_codes();
        $a['recovery_hashes'] = array_map('code_hash', $codes);
        $a['recovery_version'] = bin2hex(random_bytes(16)); $a['recovery_ready'] = true;
        $a['security_permanent'] = true;
        save(acct($email), $a);
        $_SESSION['demo_codes'] = ['codes' => $codes, 'version' => $a['recovery_version']];
        return true;
    });
    if (!$result) $err = 'The demo account is locked. Wait for the current lockout to expire before preparing it again.';
}
$a = security_account($email);
$demoCodes = $_SESSION['demo_codes'] ?? null;
$codes = $demoCodes && hash_equals($a['recovery_version'] ?? '', $demoCodes['version'])
    ? array_values(array_filter($demoCodes['codes'], fn($code) => in_array(code_hash($code), $a['recovery_hashes'] ?? [], true))) : [];
$r = ['email' => $email];
top('Classroom demo setup'); ?>
  <h1>CLASSROOM DEMO SETUP</h1>
  <p class="lead">Demo only: prepare the fictional account <strong><?= h($email) ?></strong> with one permanent security code.</p>
  <p class="lead small">The code is reusable. It cannot be regenerated here. Changes require a helpdesk request.</p>
  <p class="lead small">The first sign-in must complete email number matching before this code can be used.</p>
  <?php if ($err): ?><p class="error" role="alert"><?= h($err) ?></p><?php endif; ?>
  <?php if ($codes): ?>
    <?php include 'codes-panel.php'; ?>
  <?php endif; ?>
  <?php if (has_recovery_codes($email)): ?>
  <?php if (!$codes): ?><p class="state">This demo account already has a security code. Use the code saved during preparation.</p><?php endif; ?>
  <div class="actions"><a class="btn btn-primary" href="index.php?demo=1">Start demo sign-in</a></div>
  <?php else: ?><form method="post" class="actions"><?= csrf_field() ?><button class="btn btn-primary">Prepare demo account</button></form><?php endif; ?>
  <p class="lead small"><a href="index.php">Return to Login Page</a></p>
<?php bottom();
