# Installation Guide

## Prerequisites

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- Administrator access to WordPress

## Step 1: Install Plugin

1. Download or clone the plugin to `/wp-content/plugins/superadmin-secure/`
2. Activate the plugin via WordPress admin panel

## Step 2: Configure Secret Key

**CRITICAL**: The plugin requires a secret key for encryption. Without it, security features will be disabled.

1. Open your `wp-config.php` file
2. Add the following line (before "That's all, stop editing!"):

```php
define('SASEC_SECRET_KEY', 'your-secret-key-here-minimum-32-characters-long');
```

3. Generate a secure random key using one of these methods:

**Using OpenSSL (recommended):**
```bash
openssl rand -hex 32
```

**Using PHP:**
```php
<?php
echo bin2hex(random_bytes(32));
?>
```

**Using online generator:**
Visit a secure random string generator and generate at least 32 characters.

4. Save `wp-config.php`

## Step 3: Configure Gmail SMTP (Optional)

If you want to receive email notifications via Gmail:

### Option A: Gmail App Password (Recommended)

1. Go to your Google Account settings
2. Navigate to **Security** > **2-Step Verification**
3. Scroll down to **App passwords**
4. Generate a new app password for "Mail"
5. Copy the 16-character password
6. In WordPress admin, go to **SASEC > Settings > Notifications**
7. Enter:
   - SMTP Host: `smtp.gmail.com`
   - SMTP Port: `587`
   - Encryption: `TLS`
   - Username: Your Gmail address
   - Password: The 16-character app password

### Option B: Gmail OAuth2 (Advanced)

OAuth2 support requires additional configuration. See SECURITY.md for details.

## Step 4: Initial Configuration

1. Go to **SASEC > Dashboard** in WordPress admin
2. Review the default settings
3. Configure emergency login (if needed)
4. Set up file integrity baseline:
   - Go to **SASEC > Settings**
   - Enable "File Integrity Scanner"
   - Run baseline scan via WP-CLI: `wp sasec scan --type=baseline`

## Step 5: Test Emergency Login

1. Create an emergency token:
   - Go to **SASEC > Settings > Emergency Login**
   - Click "Create Emergency Token"
   - **Copy the token immediately** (it won't be shown again)
2. Test the emergency URL:
   - Visit: `https://yoursite.com/{your-custom-slug}?t={token}`
   - You should be logged in automatically

## Troubleshooting

### Plugin shows "SASEC_SECRET_KEY missing" notice

- Ensure the constant is defined in `wp-config.php`
- Check for typos in the constant name
- Verify the file was saved correctly
- Clear any caching plugins

### Emergency login not working

- Verify the custom URL slug is correct
- Check that emergency login is enabled in settings
- Ensure token hasn't expired or been used (if one-time)
- Check IP whitelist settings (if configured)

### Email notifications not sending

- Verify SMTP settings are correct
- Test with "Send Test Email" button
- Check server logs for errors
- Ensure Gmail app password is correct (not regular password)

## Next Steps

- Review RUNBOOK.md for incident response procedures
- Read SECURITY.md for security best practices
- Configure log retention settings
- Set up file integrity baseline

