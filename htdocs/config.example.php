<?php
// Copy to config.php and set local values. Never commit email credentials.
define('SMTP_USER', getenv('MFA_SMTP_USER') ?: '');
define('SMTP_PASS', getenv('MFA_SMTP_PASS') ?: '');
define('SITE_URL', getenv('MFA_SITE_URL') ?: 'http://127.0.0.1:8080');
define('BRAND', 'Canvas');
define('SCHOOL', 'USHC University');
define('HELPDESK', 'helpdesk@example.test');
define('CODE_TTL', 300);
define('MAX_ATTEMPTS', 3);
define('WINDOW_SECONDS', 900);
define('DEV_MODE', getenv('MFA_DEV') === '1');
