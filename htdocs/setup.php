<?php require 'lib.php';
$t = $_GET['t'] ?? ''; $r = request($t);
if (!$r || !owns($r) || ($r['method'] ?? 'email') !== 'email' || status($r) !== 'approved'
    || time() > ($r['approved_at'] ?? $r['created']) + 3600) go('index.php');
if (!empty($r['setup_done'])) go('done.php?t=' . $t);
$account = load(acct($r['email'])) ?? [];
if (!empty($account['recovery_ready']) && empty($r['replace_codes'])) go('done.php?t=' . $t);
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $result = account_lock($r['email'], function () use ($t) {
        $r = request($t);
        if (!owns($r) || status($r) !== 'approved' || !empty($r['setup_done'])) return 'invalid';
        $a = load(acct($r['email'])) ?? [];
        if (($_POST['action'] ?? '') === 'generate') {
            // Plaintext lives only in this browser's temporary setup session.
            $codes = generate_codes(); $version = bin2hex(random_bytes(16));
            $a['recovery_hashes'] = array_map('code_hash', $codes);
            $a['recovery_version'] = $version; $a['recovery_ready'] = false;
            save(acct($r['email']), $a);
            $_SESSION['setup'][$t] = ['codes' => $codes, 'version' => $version];
            return 'generated';
        }
        $setup = $_SESSION['setup'][$t] ?? null;
        if (($_POST['action'] ?? '') === 'acknowledge' && isset($_POST['saved']) && $setup
            && hash_equals($a['recovery_version'] ?? '', $setup['version'])) {
            $a['recovery_ready'] = true; save(acct($r['email']), $a);
            $r['setup_done'] = true; $r['replace_codes'] = false; save('req_' . $t, $r);
            unset($_SESSION['setup'][$t]);
            return 'ready';
        }
        return 'invalid';
    });
    if ($result === 'ready') go('done.php?t=' . $t);
    if ($result === 'invalid') $err = 'Confirm that you saved the current code set. If it was replaced in another tab, generate a fresh set.';
}
$account = load(acct($r['email'])) ?? [];
$setup = $_SESSION['setup'][$t] ?? null;
$codes = $setup && hash_equals($account['recovery_version'] ?? '', $setup['version']) ? $setup['codes'] : [];
top('Set up recovery codes'); ?>
  <h1>SET UP RECOVERY CODES</h1>
  <p class="lead">Save five single-use codes for <?= h(mask($r['email'])) ?>. Use them if email verification is unavailable.</p>
  <?php if (!empty($r['replace_codes'])): ?><p class="error">Generating a replacement set invalidates all your previous recovery codes.</p><?php endif; ?>
  <?php if ($codes): ?>
    <?php include 'codes-panel.php'; ?>
    <form method="post" class="form"><?= csrf_field() ?>
      <label><input type="checkbox" name="saved" value="1" required> I saved my codes</label>
      <button class="btn btn-primary" name="action" value="acknowledge">Continue to dashboard</button>
    </form>
  <?php else: ?>
    <form method="post" class="actions"><?= csrf_field() ?><button class="btn btn-primary" name="action" value="generate">Generate five recovery codes</button></form>
  <?php endif; ?>
  <?php if ($err): ?><p class="error" role="alert"><?= h($err) ?></p><?php endif; ?>
<?php bottom();
