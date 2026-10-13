# MFA redesign academic prototype

A PHP demonstration of email number-matching verification with a sample institutional dashboard. The password field is illustrative and is not checked. Use dummy passwords only.

## Setup

1. Install PHP and copy `htdocs/config.example.php` to `htdocs/config.php`.
2. Configure the site URL and email settings for your environment.
3. Ensure `htdocs/data` is writable by PHP and is protected against web access. Its `.htaccess` provides this protection on Apache when overrides are enabled; other servers require equivalent rules.
4. Serve `htdocs` as the document root.

For local simulations, set `MFA_DEV=1`. The application saves the verification email to `htdocs/data/last_email.html` instead of sending it. Email links must point to the local site URL. View the saved HTML locally to select and confirm the matching number.

Start the local server from the project root with `php -S 127.0.0.1:8080 -t htdocs router.php`. The router blocks direct access to runtime data and returns 404 for missing files instead of sending missing-icon requests to the login page. In demo mode, use **Open simulated email** on the verification screen.

## Repository layout

- `htdocs/`: current PHP prototype and bundled PHPMailer source.
- `htdocs/config.example.php`: configuration template without credentials.
- `htdocs/data/`: runtime verification and rate-limit records, excluded from Git.

The local configuration, runtime records, and portable preview installation are excluded from version control.

## Security-code simulation

With `MFA_DEV=1`, open `demo.php`, prepare the fictional classroom account, and save its five security codes. Select **Start demo sign-in**, enter any dummy password, and check **Demo: simulate email sending failure**. Enter a saved code to reach the dashboard.

For first-time setup, sign in with another email address, open **Open simulated email** from the verification screen, and confirm the matching number. Generate five codes and confirm **I saved my codes** before continuing.

Selecting **Use a security code** cancels the pending email link. Invalid or used security codes trigger the same account lockout as an incorrect email number. Lost-code options include email-verified replacement and a simulated helpdesk receipt popup; the popup does not send a real request or approve sign-in.

Account changes and code consumption use file locks. Security codes are stored as hashes; plaintext codes are kept temporarily in the setup browser session for saving or printing.

After signing in with either method, select **Use security code in your next login** on the dashboard to make security-code entry the preferred method. Subsequent sign-ins skip the email send when unused codes are available. Each code still works once; email remains available and is used automatically when the saved set is exhausted. The dashboard also lets you switch your preference back to email.

## Verification

Run `python tests/recovery_flow.py PATH_TO_PHP_EXECUTABLE`. The standard-library integration checks create isolated temporary data and cover setup, replacement, single use, concurrent submissions, lockouts, browser ownership, and demo-mode restrictions.
