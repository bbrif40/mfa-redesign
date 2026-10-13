<?php if (!isset($codes) || !is_array($codes)) { http_response_code(404); exit; } ?>
<div class="details"><strong>YOUR SINGLE-USE RECOVERY CODES</strong>
  <ol><?php foreach ($codes as $code): ?><li><code><?= h($code) ?></code></li><?php endforeach; ?></ol>
</div>
<p class="lead small">Keep these privately, somewhere you can access without your email. They won't be shown again after you finish setup.</p>
<div class="actions">
  <button class="btn" id="save-codes" type="button">Save codes</button>
  <button class="btn" type="button" onclick="window.print()">Print codes</button>
</div>
<script>
document.getElementById('save-codes').addEventListener('click', () => {
  const contents = <?= json_encode("Academic MFA prototype recovery codes\nAccount: " . $r['email'] . "\nEach code works once.\n\n" . implode("\n", $codes), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  const url = URL.createObjectURL(new Blob([contents], {type: 'text/plain'}));
  const link = document.createElement('a'); link.href = url; link.download = 'mfa-recovery-codes.txt';
  document.body.appendChild(link); link.click(); link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
});
</script>
