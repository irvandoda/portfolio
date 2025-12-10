# SuperAdmin Secure

A comprehensive WordPress security plugin for superadmin protection, login detection, emergency access, file integrity scanning, and ghost mode.

## Features

- **Emergency Login**: Custom URL and password/token-based emergency access
- **Login Detection**: Monitor and protect against brute force attacks
- **File Integrity Scanner**: Detect unauthorized file changes
- **Ghost Mode**: Hide superadmin activity from user lists
- **Security Logging**: Comprehensive audit trail
- **SMTP Notifications**: Email alerts via Gmail OAuth2 or App Password
- **Modern Admin UI**: React-based interface with Tailwind CSS
- **WP-CLI Support**: Command-line tools for management

## Installation

1. Upload the plugin to `/wp-content/plugins/superadmin-secure/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. **IMPORTANT**: Add the following to your `wp-config.php`:

```php
define('SASEC_SECRET_KEY', 'your-secret-key-here-min-32-chars');
```

Generate a secure random key (minimum 32 characters). You can use:

```bash
openssl rand -hex 32
```

4. Configure the plugin settings in WordPress admin under "SASEC"

## Configuration

### Emergency Login

1. Navigate to **SASEC > Settings**
2. Enable "Emergency Login"
3. Set a custom URL slug (e.g., `sasec-emergency`)
4. Choose between password or token mode
5. Create emergency tokens as needed

### SMTP Email (Gmail)

1. Go to **SASEC > Settings > Notifications**
2. Enable SMTP
3. Configure Gmail SMTP settings:
   - Host: `smtp.gmail.com`
   - Port: `587`
   - Encryption: `TLS`
   - Username: Your Gmail address
   - Password: Gmail App Password (not your regular password)

To create a Gmail App Password:
1. Go to Google Account settings
2. Security > 2-Step Verification > App passwords
3. Generate a new app password for "Mail"

## WP-CLI Commands

```bash
# Run file integrity scan
wp sasec scan --type=incremental

# Create baseline checksums
wp sasec scan --type=baseline

# Quarantine a suspicious file
wp sasec quarantine /path/to/suspicious.php

# Create emergency token
wp sasec emergency create --user=1 --ttl=30

# Export logs
wp sasec logs export --since=2024-01-01 --format=json
```

## Development

### Building Admin UI

```bash
cd admin
npm install
npm run build
```

### Running Tests

```bash
composer install
vendor/bin/phpunit
```

## Security Notes

- All secrets are encrypted using `SASEC_SECRET_KEY`
- Tokens are shown only once - copy immediately
- Emergency passwords can be set as one-time use
- All actions are logged for audit purposes
- IP addresses can be anonymized for privacy compliance

## Requirements

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.6+

## License

GPL-2.0+

## Support

For issues and feature requests, please visit the plugin repository.

