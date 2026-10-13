<?php require 'lib.php';
if (!DEV_MODE) { http_response_code(404); exit('Not found'); }
$t = $_GET['t'] ?? ''; $r = request($t);
if (!$r || !owns($r) || ($r['method'] ?? 'email') !== 'email') {
    http_response_code(403); exit('Open the simulated email from your verification screen.');
}
header('Cache-Control: no-store'); header('Referrer-Policy: no-referrer');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Simulated verification email</title></head>
<body><p style="text-align:center">Simulation only: no real email was sent.</p>
<?php include 'email.php'; ?>
</body></html>
