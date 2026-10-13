<?php require 'lib.php';
// Sample of the verification email for screenshots/demo. Real emails go to the inbox.
$t = str_repeat('0', 32);
$r = ['key' => str_repeat('0', 32), 'device' => 'Chrome on macOS', 'location' => 'Cainta, Rizal, Philippines',
      'time' => date('g:i A'), 'choices' => ['14', '47', '65', '21', '83', '09', '32', '50', '76']];
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sample Email Concept · <?= h(BRAND) ?></title></head>
<body style="margin:0;background:#f2f2f2;font-family:Roboto,Arial,sans-serif">
<p style="margin:0;padding:12px 16px;background:#1a1a1a;color:#fff;font-size:14px;text-align:center">
  Sample Email Concept: what users receive in their inbox. Number buttons here are display only.</p>
<p style="max-width:480px;margin:16px auto 0;padding:0 12px;font-size:14px;color:#333">
  <strong>Subject:</strong> Action Required: Verify Your <?= h(BRAND) ?> Login Attempt<br>
  <strong>From:</strong> <?= h(SCHOOL) ?> IT Security</p>
<?php include 'email.php'; ?>
</body></html>
