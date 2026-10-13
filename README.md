# MFA redesign academic prototype

A PHP demonstration of email number-matching verification and a permanent security code, with a fictional institutional dashboard. The password field is illustrative and is not checked. Use dummy passwords only.

## Setup

1. Install PHP and copy `htdocs/config.example.php` to `htdocs/config.php`.
2. Configure the site URL and email settings for your environment.
3. Ensure `htdocs/data` is writable by PHP and protected against web access. Its `.htaccess` supports Apache when overrides are enabled; other servers need equivalent rules.
4. Serve `htdocs` as the document root.

For local simulation, set `MFA_DEV=1` and run `php -S 127.0.0.1:8080 -t htdocs router.php` from the project root. Match the configured site URL to your local port. The router protects runtime records and rejects missing files. **Open simulated email** on the verification screen lets you confirm the matching number without sending real email.

## Permanent security code

After first email verification, generate one security code, save or print it, and confirm **I saved my security code**. The same code works for future sign-ins and does not run out. Only its hash is stored with the account; plaintext is shown temporarily during setup.

The dashboard offers **Use security code in your next login** after either sign-in method. Selecting it skips the email send on future sign-ins. Users can switch their preference back to email from the dashboard. The security-code page offers code entry, helpdesk assistance, and cancellation; it has no email-verification action.

Code changes require **Request security code change through helpdesk**. This prototype shows a simulated receipt popup only: it does not send a real request, approve sign-in, or change the code. Self-service replacement is disabled even after email verification.

Switching verification methods cancels the pending email link. Invalid codes retain the current escalating account lockout. Account data and request updates use file locks.

Existing accounts from the five-code version keep their first remaining saved code as the permanent code. Other codes from that old set are retired.

## Classroom simulation

With demo mode enabled, open `demo.php` to prepare a fictional account with one permanent security code. Preparation cannot regenerate an existing code. Save the displayed code, select **Start demo sign-in**, enter a dummy password, and check **Demo: simulate email sending failure** to try the backup flow. If security code is already the preferred method, login goes directly to code entry.

Configuration credentials, runtime records, and the portable `.local-preview` installation are excluded from Git. `htdocs/config.example.php` contains a safe configuration template; `htdocs/PHPMailer` contains the bundled mail library.

## Verification

Run `python tests/recovery_flow.py PATH_TO_PHP_EXECUTABLE`. Tests use isolated temporary data and cover setup, permanent reuse, concurrent logins, preferences, account protection, browser binding, helpdesk acknowledgements, blocked self-service changes, legacy migration, and demo-mode restrictions.
