<?php
require __DIR__ . '/config.php';
date_default_timezone_set('Asia/Manila');
define('DATA', __DIR__ . '/data/');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => https()]);
session_start();
require __DIR__ . '/recovery-lib.php';

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function go($url) { header("Location: $url"); exit; }

// ---- storage: one JSON file per login request / per account ----
// ponytail: flat JSON files, fine for a class demo; move to MySQL if many users hit it at once
function load($name) {
    $f = DATA . $name . '.json';
    if (!is_file($f)) return null;
    $handle = fopen($f, 'r');
    if (!$handle || !flock($handle, LOCK_SH)) throw new RuntimeException('Storage unavailable');
    try { return json_decode(stream_get_contents($handle), true, 512, JSON_THROW_ON_ERROR); }
    finally { flock($handle, LOCK_UN); fclose($handle); }
}
function save($name, $d) {
    if (file_put_contents(DATA . $name . '.json', json_encode($d, JSON_THROW_ON_ERROR), LOCK_EX) === false) {
        throw new RuntimeException('Storage unavailable');
    }
}
function acct($email) { return 'acct_' . sha1(strtolower(trim($email))); }

function request($t) {
    if (!is_string($t) || !preg_match('/^[a-f0-9]{32}$/', $t)) return null;
    return load('req_' . $t);
}

// ---- rate limiting is per ACCOUNT, so an attacker can't dodge it by starting new logins ----
function locked_until($email) {
    $a = load(acct($email));
    return ($a['locked_until'] ?? 0) > time() ? $a['locked_until'] : 0;
}
function strike($email, $force_lock = false) {
    return account_lock($email, fn() => record_strike($email, $force_lock));
}
function record_strike($email, $force_lock = false) { // caller holds the account lock
    $a = (load(acct($email)) ?? []) + ['hits' => [], 'locked_until' => 0];
    if (($a['locked_until'] ?? 0) < time() - 86400) $a['locks'] = 0; // a quiet day resets the escalation
    $a['hits'] = array_values(array_filter($a['hits'], fn($t) => $t > time() - WINDOW_SECONDS));
    $a['hits'][] = time();
    if ($force_lock || count($a['hits']) > MAX_ATTEMPTS) {
        $a['locks'] = ($a['locks'] ?? 0) + 1; // escalating lockout: 2 min, then +5 min each time (2, 7, 12, ...)
        $a['locked_until'] = time() + min(60, 2 + 5 * ($a['locks'] - 1)) * 60; // capped at 60 so an attacker can't lock a victim out for hours
        $a['hits'] = [];
    }
    save(acct($email), $a);
    return $a['locked_until'] > time();
}
function clear_strikes($email) {
    account_lock($email, function () use ($email) {
        $a = load(acct($email)) ?? [];
        $a['hits'] = []; $a['locked_until'] = 0; $a['locks'] = 0;
        save(acct($email), $a);
    });
}

// ---- per-network limit: stops one attacker from using the site to email many different people ----
function ip_key() { return 'ip_' . sha1($_SERVER['REMOTE_ADDR'] ?? ''); }
function ip_hits() { return array_values(array_filter(load(ip_key()) ?? [], fn($t) => $t > time() - 3600)); }

// ---- the login is tied to the browser that started it, so a copied link can't be used elsewhere ----
function https() { return ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'; }
function owns($r) { return hash_equals($r['bind'] ?? 'x', (string)($_COOKIE['mfa_bind'] ?? '')); }
function set_bind($v, $ttl = 3600) {
    setcookie('mfa_bind', $v, ['expires' => $v ? time() + $ttl : time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => https()]);
}

// live status of a request, as the login screen sees it
function status($r) {
    if (locked_until($r['email'])) return 'locked';
    if ($r['status'] === 'pending' && time() > $r['created'] + CODE_TTL) return 'expired';
    return $r['status']; // pending | approved | wrong | cancelled
}

// ---- login context shown on screen AND in the email (must match) ----
function device() {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $b = preg_match('/Edg\//', $ua) ? 'Edge' : (preg_match('/OPR\//', $ua) ? 'Opera' : (preg_match('/Firefox\//', $ua) ? 'Firefox'
       : (preg_match('/Chrome\//', $ua) ? 'Chrome' : (preg_match('/Safari\//', $ua) ? 'Safari' : 'Unknown browser'))));
    $o = preg_match('/Android/', $ua) ? 'Android' : (preg_match('/iPhone|iPad/', $ua) ? 'iOS' : (preg_match('/Windows/', $ua) ? 'Windows'
       : (preg_match('/Mac OS X/', $ua) ? 'macOS' : (preg_match('/Linux/', $ua) ? 'Linux' : 'Unknown OS'))));
    return "$b on $o";
}
function location($ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return 'Unknown location';
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $j = json_decode(@file_get_contents("http://ip-api.com/json/$ip?fields=status,city,regionName,country", false, $ctx), true);
    return ($j['status'] ?? '') === 'success' ? "{$j['city']}, {$j['regionName']}, {$j['country']}" : 'Unknown location';
}
function mask($email) { [$u, $d] = explode('@', $email, 2); return substr($u, 0, 1) . '***@' . $d; }

// ---- start a verification: returns token, or 'locked', 'iplimit', or 'mailfail' ----
function start_request($email, $simulate_failure = false) {
    return account_lock($email, fn() => create_email_request($email, $simulate_failure));
}
function create_email_request($email, $simulate_failure) {
    if (locked_until($email)) return 'locked';
    if (count(ip_hits()) >= 10) return 'iplimit'; // max 10 sign-in emails per hour from one network
    $site = strtolower((string)parse_url(SITE_URL, PHP_URL_HOST));
    $here = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    if (!DEV_MODE && $site !== $here) { // wrong SITE_URL = email buttons that lead nowhere
        $GLOBALS['mail_error'] = "SITE_URL in config.php is " . SITE_URL . " but this site is at $here. Set SITE_URL to " . (https() ? 'https' : 'http') . "://$here";
        return failed_email_request($email);
    }
    $choices = [];
    while (count($choices) < 9) $choices[sprintf('%02d', random_int(1, 99))] = 1;
    $choices = array_keys($choices);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $r = [
        'email' => $email, 'status' => 'pending', 'created' => time(), 'method' => 'email',
        'num' => (string)$choices[random_int(0, 8)], 'choices' => array_map('strval', $choices),
        'key' => bin2hex(random_bytes(16)), // secret only the email has: seeing the screen isn't enough to approve
        'bind' => bin2hex(random_bytes(16)), // matches a cookie in the browser that started the login
        'device' => device(), 'location' => location($ip), 'time' => date('g:i A'),
    ];
    $t = bin2hex(random_bytes(16));
    if ((DEV_MODE && $simulate_failure) || !send_mail($r, $t)) {
        return failed_email_request($email); // failed sends do not count toward lockout
    }
    save('req_' . $t, $r);
    set_bind($r['bind']);
    save(ip_key(), array_merge(ip_hits(), [time()]));
    foreach (glob(DATA . 'req_*.json') as $f) if (filemtime($f) < time() - 86400) @unlink($f); // don't keep emails/logins forever
    if (strike($email)) return 'locked';
    return $t;
}

function send_mail($r, $t) {
    ob_start(); include __DIR__ . '/email.php'; $html = ob_get_clean();
    $subject = 'Action Required: Verify Your ' . BRAND . ' Login Attempt';
    if (DEV_MODE) { file_put_contents(DATA . 'last_email.html', $html); return true; }
    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
    $m = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $m->isSMTP();
        $m->Host = 'smtp.gmail.com'; $m->Port = 465; $m->SMTPAuth = true;
        $m->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $user = preg_replace('/[\s\x{200B}\x{FEFF}]+/u', '', SMTP_USER); // strip spaces/invisible chars from copy-paste
        $m->Username = $user; $m->Password = trim(SMTP_PASS);
        $m->setFrom($user, SCHOOL . ' IT Security');
        $m->addAddress($r['email']);
        $m->isHTML(true); $m->CharSet = 'UTF-8';
        $m->Subject = $subject; $m->Body = $html;
        $m->AltBody = "Your sign-in number choices: open this email in an HTML-capable app.";
        $m->send();
        return true;
    } catch (Throwable $e) { error_log('MFA mail: ' . $e->getMessage()); $GLOBALS['mail_error'] = $m->ErrorInfo; return false; }
}

// ---- page shell (matches the Canva prototype) ----
function top($title) {
    header('Cache-Control: no-store');
    header('X-Frame-Options: DENY'); // no framing the confirm page (clickjacking)
    header('Referrer-Policy: no-referrer'); // email links carry a secret; never leak it to other sites
    header('X-Content-Type-Options: nosniff'); ?>
<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?> · <?= h(BRAND) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css"></head><body>
<header class="bar">
  <?php if (is_file(__DIR__ . '/logo.png')): ?><img class="logo" src="logo.png" alt="<?= h(BRAND) ?>">
  <?php else: ?><span class="logo-text"><?= h(strtoupper(BRAND)) ?></span><?php endif; ?>
  <span class="divider" aria-hidden="true"></span><span class="bar-title">INSTITUTION LOGIN</span>
</header>
<main class="card">
<?php }
function bottom() { ?>
</main>
<p class="proto">Academic prototype (Usable Security, IT31S2 Group 2).Use your exisiting email, Do not enter a real password.</p>
</body></html>
<?php }
