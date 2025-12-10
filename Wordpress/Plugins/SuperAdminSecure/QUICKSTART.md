# Quick Start Guide

## Installation in 5 Minutes

### Step 1: Add Secret Key (REQUIRED)

Add to `wp-config.php`:
```php
define('SASEC_SECRET_KEY', 'generate-a-32-char-random-key-here');
```

Generate key:
```bash
openssl rand -hex 32
```

### Step 2: Build Admin UI

```bash
cd admin
npm install
npm run build
cd ..
```

### Step 3: Activate Plugin

1. Go to WordPress Admin > Plugins
2. Activate "SuperAdmin Secure"
3. Go to **SASEC > Dashboard**

### Step 4: Configure Emergency Login

1. Go to **SASEC > Settings**
2. Enable "Emergency Login"
3. Set custom URL slug (e.g., `sasec-emergency`)
4. Click "Create Emergency Token"
5. **Copy the token immediately** - it won't be shown again!

### Step 5: Test Emergency Login

Visit: `https://yoursite.com/sasec-emergency?t=YOUR_TOKEN`

You should be automatically logged in.

## Essential Commands

```bash
# Create file integrity baseline
wp sasec scan --type=baseline

# Run file scan
wp sasec scan --type=incremental

# Create emergency token
wp sasec emergency create --user=1 --ttl=30

# Export logs
wp sasec logs export --format=json
```

## Next Steps

1. ✅ Set up email notifications (Gmail App Password)
2. ✅ Configure detection thresholds
3. ✅ Create file integrity baseline
4. ✅ Review security logs
5. ✅ Enable ghost mode (if needed)

## Troubleshooting

**"SASEC_SECRET_KEY missing" notice?**
- Add the constant to `wp-config.php`
- Clear cache if using caching plugin

**Emergency login not working?**
- Check custom URL slug is correct
- Verify token hasn't expired
- Check IP whitelist (if configured)

**Admin UI not loading?**
- Run `npm run build` in `admin/` directory
- Check browser console for errors
- Verify REST API is accessible

## Support

- Full documentation: See `docs/` directory
- Installation guide: `docs/INSTALL.md`
- Security guide: `docs/SECURITY.md`
- Runbook: `docs/RUNBOOK.md`

