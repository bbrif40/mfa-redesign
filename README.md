# MFA redesign academic prototype

A PHP demonstration of email number-matching verification with a sample institutional dashboard. The password field is illustrative and is not checked. Use dummy passwords only.

## Setup

1. Install PHP and copy `htdocs/config.example.php` to `htdocs/config.php`.
2. Configure the site URL and email settings for your environment.
3. Ensure `htdocs/data` is writable by PHP and is protected against web access. Its `.htaccess` provides this protection on Apache when overrides are enabled; other servers require equivalent rules.
4. Serve `htdocs` as the document root.

For local simulations, set `MFA_DEV=1`. The application saves the verification email to `htdocs/data/last_email.html` instead of sending it. Email links must point to the local site URL. View the saved HTML locally to select and confirm the matching number.

## Repository layout

- `htdocs/`: current PHP prototype and bundled PHPMailer source.
- `htdocs/config.example.php`: configuration template without credentials.
- `htdocs/data/`: runtime verification and rate-limit records, excluded from Git.

The local configuration, runtime records, and portable preview installation are excluded from version control.
