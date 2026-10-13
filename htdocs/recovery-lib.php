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

function security_account($email) {
    return account_lock($email, function () use ($email) {
        $a = load(acct($email)) ?? [];
        // Existing prototypes used five single-use hashes. Retain the first
        // remaining code as the permanent code without generating a new secret.
        if (empty($a['security_permanent']) && !empty($a['recovery_hashes'])) {
            $a['recovery_hashes'] = [array_values($a['recovery_hashes'])[0]];
            $a['security_permanent'] = true;
            save(acct($email), $a);
        }
        return $a;
    });
}
function has_recovery_codes($email) {
    $a = security_account($email);
    return !empty($a['recovery_ready']) && !empty($a['recovery_hashes']);
}
function start_login_request($email, $simulate_failure = false) {
    return account_lock($email, function () use ($email, $simulate_failure) {
        if (locked_until($email)) return 'locked';
        $a = load(acct($email)) ?? [];
        if (($a['preferred_verification'] ?? 'email') === 'security_code' && has_recovery_codes($email)) {
            return backup_request($email);
        }
        return start_request($email, $simulate_failure);
    });
}
function save_login_preference($t, $method) {
    $r = request($t);
    if (!$r || !owns($r) || !in_array($method, ['email', 'security_code'], true)) return 'invalid';
    return account_lock($r['email'], function () use ($t, $method) {
        $r = request($t);
        if (status($r) === 'locked') return 'locked';
        if (status($r) !== 'approved') return 'invalid';
        if ($method === 'security_code' && !has_recovery_codes($r['email'])) return 'no_codes';
        $a = load(acct($r['email'])) ?? [];
        $a['preferred_verification'] = $method;
        save(acct($r['email']), $a);
        return 'saved';
    });
}
function generate_codes() {
    return [implode('-', str_split(strtoupper(bin2hex(random_bytes(10))), 4))];
}
function code_hash($code) {
    return hash('sha256', strtoupper(preg_replace('/[\s-]/', '', $code)));
}

function backup_request($email, $mail_failed = false) {
    if (locked_until($email)) return 'locked';
    $t = bin2hex(random_bytes(16));
    $r = ['email' => $email, 'status' => 'pending', 'created' => time(),
        'method' => 'recovery', 'bind' => bin2hex(random_bytes(16)),
        'device' => device(), 'location' => location($_SERVER['REMOTE_ADDR'] ?? ''),
        'time' => date('g:i A'), 'mail_failed' => $mail_failed];
    save('req_' . $t, $r);
    set_bind($r['bind']);
    return $t;
}
function failed_email_request($email) {
    $t = backup_request($email, true);
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
        $a = security_account($r['email']);
        $hashes = $a['recovery_hashes'] ?? [];
        $match = array_search(code_hash($code), $hashes, true);
        if (empty($a['recovery_ready']) || $match === false) {
            $r['status'] = 'wrong'; save('req_' . $t, $r);
            strike($r['email'], true);
            return 'locked';
        }
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

function restart_email($t) {
    $r = request($t);
    if (!$r || !owns($r)) return 'invalid';
    return account_lock($r['email'], function () use ($t) {
        $r = request($t);
        if (locked_until($r['email'])) return 'locked';
        if (!in_array(status($r), ['pending', 'expired'], true)) return 'invalid';
        $r['status'] = 'cancelled'; save('req_' . $t, $r);
        return start_request($r['email']);
    });
}
