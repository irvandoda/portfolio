# Security Documentation

## Encryption

All sensitive data is encrypted using `SASEC_SECRET_KEY` from `wp-config.php`. The plugin uses:

- **Algorithm**: AES-256-CBC
- **Key derivation**: SHA-256 hash of `SASEC_SECRET_KEY`
- **IV**: Random per-encryption

### What is Encrypted

- Emergency passwords (hashed with bcrypt, then encrypted)
- SMTP passwords
- OAuth2 tokens (if implemented)
- Any other secrets stored in database

### Key Management

- **Never** commit `SASEC_SECRET_KEY` to version control
- **Never** share the key
- **Rotate** the key if compromised
- **Use** a minimum of 32 characters
- **Generate** using cryptographically secure methods

## Token Security

### Emergency Tokens

- Generated using `random_bytes(32)` (cryptographically secure)
- Stored as HMAC-SHA512 hash only (never plaintext)
- Single-use by default
- Time-limited (configurable TTL)
- Rate-limited per user

### Token Display

- Tokens shown **only once** in UI
- Warning displayed: "Copy now - token shown only once"
- Not stored in browser history
- Not logged in plaintext

## Database Security

### Prepared Statements

All database queries use `$wpdb->prepare()` to prevent SQL injection:

```php
$wpdb->prepare(
    "SELECT * FROM {$table} WHERE id = %d AND name = %s",
    $id,
    $name
);
```

### Input Sanitization

- All user input sanitized with `sanitize_text_field()`, `sanitize_email()`, etc.
- Output escaped with `esc_html()`, `esc_url()`, etc.
- Nonces used for all form submissions

## REST API Security

### Authentication

- All endpoints require WordPress authentication
- Capability checks on every request:
  - `sasec_manage_settings`
  - `sasec_view_logs`
  - `sasec_handle_emergency`
  - `sasec_run_scan`

### Nonces

- WP REST API nonces required
- Verified on every request
- Nonces expire after 24 hours

### Rate Limiting

- Emergency token creation: 1 per hour per user
- File scans: Configurable
- Log exports: Reasonable limits

## File Security

### Upload Scanning

- PHP files blocked in uploads (unless explicitly allowed)
- Shell signature detection
- Heuristic analysis
- Quarantine suspicious files

### File Integrity

- SHA-256 checksums for all monitored files
- Baseline created on activation
- Incremental scans detect changes
- Quarantine mode available

## Network Security

### IP Handling

- Supports proxy headers (X-Forwarded-For, CF-Connecting-IP)
- IP anonymization option (privacy compliance)
- IP whitelist for emergency login
- Automatic IP blocking (protect mode)

### HTTPS

- REST API requires HTTPS in production
- Emergency URLs should use HTTPS
- Cookies marked as secure

## Privacy

### Log Retention

- Configurable retention period (default: 90 days)
- Automatic cleanup of old logs
- Export before deletion

### IP Anonymization

- Option to anonymize IPs in logs
- IPv4: Store /24 subnet
- IPv6: Mask last 64 bits
- Useful for GDPR compliance

### Data Minimization

- Only log necessary information
- No sensitive data in logs (passwords, tokens)
- User data anonymized where possible

## Best Practices

### For Administrators

1. **Set strong `SASEC_SECRET_KEY`**
   - Minimum 32 characters
   - Cryptographically random
   - Unique per installation

2. **Enable HTTPS**
   - Required for production
   - Valid SSL certificate
   - HSTS recommended

3. **Regular Updates**
   - Keep WordPress core updated
   - Update plugins and themes
   - Update this plugin

4. **Backup Strategy**
   - Regular automated backups
   - Test restore procedures
   - Store backups securely

5. **Monitor Logs**
   - Review high-severity events
   - Set up email notifications
   - Export logs regularly

6. **Access Control**
   - Use strong passwords
   - Enable 2FA (via other plugins)
   - Limit admin users
   - Use ghost mode for superadmins

### For Developers

1. **Never log secrets**
   - No passwords in logs
   - No tokens in logs
   - No encryption keys

2. **Sanitize all input**
   - Use WordPress sanitization functions
   - Validate data types
   - Check capabilities

3. **Escape all output**
   - Use WordPress escaping functions
   - Prevent XSS attacks
   - Validate URLs

4. **Use nonces**
   - All forms need nonces
   - REST API needs nonces
   - Verify on server side

5. **Test security**
   - Run security scans
   - Test SQL injection prevention
   - Test XSS prevention
   - Review code regularly

## Vulnerability Reporting

If you discover a security vulnerability:

1. **DO NOT** create a public issue
2. Email security team directly
3. Provide detailed information
4. Allow time for fix before disclosure

## Compliance

### GDPR

- IP anonymization available
- Log retention configurable
- Data export available
- Right to deletion (manual process)

### PCI DSS

- No card data stored
- Secure transmission (HTTPS)
- Access logging
- Regular security reviews

## Security Checklist

- [ ] `SASEC_SECRET_KEY` set and secure
- [ ] HTTPS enabled
- [ ] Emergency login tested
- [ ] Email notifications working
- [ ] File integrity baseline created
- [ ] Detection thresholds configured
- [ ] Log retention set
- [ ] IP anonymization enabled (if needed)
- [ ] Regular backups configured
- [ ] All software updated
- [ ] Strong passwords in use
- [ ] Access controls reviewed

