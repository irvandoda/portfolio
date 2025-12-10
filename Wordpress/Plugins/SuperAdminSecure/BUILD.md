# Build Instructions

## Prerequisites

- Node.js 18+ and npm
- PHP 7.4+
- Composer (for PHP dependencies)

## Building the Admin UI

1. Navigate to the admin directory:
```bash
cd admin
```

2. Install dependencies:
```bash
npm install
```

3. Build for production:
```bash
npm run build
```

This will create the bundled files in `admin/build/` directory.

## Development Mode

For development with hot reload:
```bash
npm run dev
```

Note: In development, you'll need to configure Vite to proxy WordPress admin requests.

## PHP Dependencies

Install PHP dependencies (for testing):
```bash
composer install
```

## Testing

Run PHPUnit tests:
```bash
composer test
```

Or directly:
```bash
vendor/bin/phpunit
```

## File Structure

```
SuperAdminSecure/
├── superadmin-secure.php          # Main plugin file
├── includes/                      # Core PHP classes
│   ├── class-sasec.php
│   ├── class-sasec-activator.php
│   ├── class-sasec-deactivator.php
│   ├── class-sasec-migrations.php
│   ├── class-sasec-logger.php
│   ├── class-sasec-detection.php
│   ├── class-sasec-emergency.php
│   ├── class-sasec-file-scan.php
│   ├── class-sasec-notifier.php
│   ├── class-sasec-htaccess.php
│   ├── class-sasec-ghost-mode.php
│   └── rest/
│       └── routes.php
├── admin/                         # React admin UI
│   ├── src/                       # React source files
│   ├── build/                     # Built files (generated)
│   └── package.json
├── tests/                         # PHPUnit tests
├── docs/                          # Documentation
├── wp-cli.php                     # WP-CLI commands
└── composer.json                  # PHP dependencies
```

## Production Deployment

1. Build the React app:
```bash
cd admin && npm run build
```

2. Ensure all PHP files are in place

3. Test the plugin activation:
```bash
wp plugin activate superadmin-secure
```

4. Verify database tables are created:
```bash
wp db query "SHOW TABLES LIKE 'wp_sasec_%'"
```

5. Create file integrity baseline:
```bash
wp sasec scan --type=baseline
```

## Troubleshooting

### React build fails
- Ensure Node.js 18+ is installed
- Delete `node_modules` and `package-lock.json`, then reinstall
- Check for syntax errors in React components

### PHP errors
- Verify PHP version is 7.4+
- Check that all required PHP extensions are installed
- Review error logs

### Database issues
- Ensure database user has CREATE TABLE permissions
- Check table prefix is correct
- Verify migrations ran successfully

