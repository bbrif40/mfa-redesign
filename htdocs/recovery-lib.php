<?php
// Serialize all account and associated request mutations, including code consumption.
function account_lock($email, $operation) {
    static $held = [];
    $name = acct($email);
    if (isset($held[$name])) return $operation();
    $lock = fopen(DATA . $name . '.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('Account storage unavailable');
    $held[$name] = true;
    try { return $operation(); }
    finally { unset($held[$name]); flock($lock, LOCK_UN); fclose($lock); }
}

function csrf_field() {
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
}
function check_csrf() {
    if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403); exit('This form expired. Reload the page and try again.');
    }
}

function has_recovery_codes($email) {
    $a = load(acct($email));
    return !empty($a['recovery_ready']) && !empty($a['recovery_hashes']);
}
function generate_codes() {
    $codes = [];
    for ($i = 0; $i < 5; $i++) $codes[] = implode('-', str_split(strtoupper(bin2hex(random_bytes(10))), 4));
    return $codes;
}
function code_hash($code) {
    return hash('sha256', strtoupper(preg_replace('/[\s-]/', '', $code)));
}

function backup_request($email, $mail_failed = false, $replace_codes = false) {
    if (locked_until($email)) return 'locked';
    $t = bin2hex(random_bytes(16));
    $r = ['email' => $email, 'status' => 'pending', 'created' => time(),
        'method' => 'recovery', 'bind' => bin2hex(random_bytes(16)),
        'device' => device(), 'location' => location($_SERVER['REMOTE_ADDR'] ?? ''),
        'time' => date('g:i A'), 'mail_failed' => $mail_failed, 'replace_codes' => $replace_codes];
    save('req_' . $t, $r);
    set_bind($r['bind']);
    return $t;
}
function failed_email_request($email, $replace_codes) {
    $t = backup_request($email, true, $replace_codes);
    if ($t !== 'locked') $GLOBALS['backup_token'] = $t;
    return $t === 'locked' ? 'locked' : 'mailfail';
}
function switch_to_backup($t) {
    $r = request($t);
    if (!$r || !owns($r)) return 'invalid';
    return account_lock($r['email'], function () use ($t) {
        $r = request($t);
        if (locked_until($r['email'])) return 'locked';
        if (!in_array(status($r), ['pending', 'expired'], true) || ($r['method'] ?? 'email') !== 'email') return 'invalid';
        $r['status'] = 'cancelled';
        save('req_' . $t, $r);
        return backup_request($r['email']);
    });
}
function verify_recovery_code($t, $code) {
    $r = request($t);
    if (!$r || !owns($r)) return 'invalid';
    return account_lock($r['email'], function () use ($t, $code) {
        $r = request($t);
        $s = status($r);
        if ($s !== 'pending' || ($r['method'] ?? '') !== 'recovery') return $s === 'pending' ? 'invalid' : $s;
        $a = load(acct($r['email'])) ?? [];
        $hashes = $a['recovery_hashes'] ?? [];
        $match = array_search(code_hash($code), $hashes, true);
        if (empty($a['recovery_ready']) || $match === false) {
            $r['status'] = 'wrong'; save('req_' . $t, $r);
            strike($r['email'], true);
            return 'locked';
        }
        unset($hashes[$match]);
        $a['recovery_hashes'] = array_values($hashes);
        save(acct($r['email']), $a); // consume before approving; failure cannot allow reuse
        clear_strikes($r['email']);
        $r['status'] = 'approved'; $r['approved_at'] = time();
        save('req_' . $t, $r);
        return 'approved';
    });
}

function confirm_email_number($t, $key, $number) {
    $r = request($t);
    if (!$r) return 'invalid';
    return account_lock($r['email'], function () use ($t, $key, $number) {
        $r = request($t);
        if (($r['method'] ?? 'email') !== 'email' || !hash_equals($r['key'] ?? '', $key)
            || !in_array($number, $r['choices'] ?? [], true)) return 'invalid';
        $s = status($r);
        if ($s !== 'pending') return $s;
        if (hash_equals($r['num'], $number)) {
            $r['status'] = 'approved'; $r['approved_at'] = time(); clear_strikes($r['email']);
        } else { $r['status'] = 'wrong'; strike($r['email'], true); }
        save('req_' . $t, $r);
        return $r['status'];
    });
}

function restart_email($t, $replace_codes = false) {
    $r = request($t);
    if (!$r || !owns($r)) return 'invalid';
    return account_lock($r['email'], function () use ($t, $replace_codes) {
        $r = request($t);
        if (locked_until($r['email'])) return 'locked';
        if (!in_array(status($r), ['pending', 'expired'], true)) return 'invalid';
        $r['status'] = 'cancelled'; save('req_' . $t, $r);
        return start_request($r['email'], false, $replace_codes);
    });
}
