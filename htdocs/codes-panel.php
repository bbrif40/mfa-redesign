<?php if (!isset($codes) || !is_array($codes)) { http_response_code(404); exit; } ?>
<div class="details"><strong>YOUR PERMANENT SECURITY CODE</strong>
  <ol><?php foreach ($codes as $code): ?><li><code><?= h($code) ?></code></li><?php endforeach; ?></ol>
</div>
<p class="lead small">Save this code privately, somewhere you can access without email. It stays valid until a helpdesk-assisted change.</p>
<div class="actions">
  <button class="btn" id="save-codes" type="button">Save security code</button>
  <button class="btn" type="button" onclick="window.print()">Print security code</button>
</div>
<script>
document.getElementById('save-codes').addEventListener('click', () => {
  const contents = <?= json_encode("Academic MFA prototype permanent security code\nAccount: " . $r['email'] . "\nReusable. Changes require a helpdesk request.\n\n" . implode("\n", $codes), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  const url = URL.createObjectURL(new Blob([contents], {type: 'text/plain'}));
  const link = document.createElement('a'); link.href = url; link.download = 'mfa-security-codes.txt';
  document.body.appendChild(link); link.click(); link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
});
</script>
