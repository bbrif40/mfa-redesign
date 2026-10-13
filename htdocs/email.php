<?php // Email body. Uses $r (request) and $t (token). Inline styles only: email apps strip <style>.
$link = fn($n) => SITE_URL . '/pick.php?t=' . $t . '&k=' . $r['key'] . '&n=' . $n;
$rows = array_chunk($r['choices'], 3);
?>
<div style="background:#f2f2f2;padding:24px 12px;font-family:Roboto,Arial,sans-serif;color:#1a1a1a">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;margin:0 auto;background:#fff;border:1px solid #d0d0d0;border-radius:6px">
<tr><td style="padding:24px 28px 8px;text-align:center;font-size:22px;letter-spacing:2px;color:#444"><?= h(strtoupper(SCHOOL)) ?></td></tr>
<tr><td style="padding:8px 28px;font-size:15px;line-height:1.5">
  <p style="margin:0 0 12px">Hello Student,</p>
  <p style="margin:0 0 16px">We detected a sign-in attempt for your <?= h(BRAND) ?> account. Check that these details are yours:</p>
  <table role="presentation" width="100%" style="border:1px solid #cfcfcf;border-radius:4px;font-size:14px"><tr><td style="padding:12px 14px;line-height:1.7">
    <strong>Device:</strong> <?= h($r['device']) ?><br>
    <strong>Location:</strong> <?= h($r['location']) ?><br>
    <strong>Time:</strong> <?= h($r['time']) ?> (Philippine time)
  </td></tr></table>
  <p style="margin:18px 0 12px">To finish signing in, select the number shown on your sign-in screen. This expires in <strong><?= CODE_TTL / 60 ?> minutes</strong>.</p>
</td></tr>
<tr><td style="padding:0 22px">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="6">
  <?php foreach ($rows as $row): ?><tr><?php foreach ($row as $n): ?>
    <td width="33%" align="center" style="border:1px solid #cfcfcf;border-radius:4px">
      <a href="<?= h($link($n)) ?>" style="display:block;padding:16px 0;font-size:26px;font-weight:700;color:#1a1a1a;text-decoration:none"><?= h($n) ?></a>
    </td><?php endforeach; ?></tr><?php endforeach; ?>
  </table>
</td></tr>
<tr><td style="padding:14px 28px">
  <div style="background:#000;color:#fff;border-radius:4px;padding:14px;text-align:center;font-size:14px;line-height:1.5">
    If you did not start this sign-in, <strong>DO NOT select any number</strong>. Change your password and report it.
  </div>
</td></tr>
<tr><td style="padding:4px 28px 24px;text-align:center;font-size:13px;color:#555">
  Contact the IT Helpdesk at <a href="mailto:<?= h(HELPDESK) ?>" style="color:#0b57d0"><?= h(HELPDESK) ?></a><br>
  <?= h(BRAND) ?> staff will never ask you for this number by phone, text, or email.
</td></tr>
</table></div>
