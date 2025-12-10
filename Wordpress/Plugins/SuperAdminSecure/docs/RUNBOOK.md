# Incident Response Runbook

This document provides step-by-step procedures for responding to security incidents detected by SuperAdmin Secure.

## Severity Levels

- **Critical (9-10)**: Immediate threat, site compromise suspected
- **High (7-8)**: Active attack, potential data breach
- **Medium (5-6)**: Suspicious activity, requires investigation
- **Low (1-4)**: Informational, routine monitoring

## Common Incident Types

### 1. Multiple Failed Login Attempts

**Symptoms:**
- Multiple `login_failed` events in logs
- Same IP or username attempting repeatedly
- Threshold exceeded alert

**Response:**
1. Check logs in **SASEC > Logs** for pattern
2. Identify attacking IP address
3. If in "protect" mode, IP should be auto-blocked
4. If in "log" mode, manually block IP:
   ```bash
   wp sasec quarantine --ip=ATTACKING_IP
   ```
5. Review if legitimate user locked out
6. If legitimate, whitelist IP in emergency settings

### 2. File Integrity Changes Detected

**Symptoms:**
- `file_integrity_change` event in logs
- Files modified outside normal updates
- New suspicious files detected

**Response:**
1. Review file changes in logs
2. Identify modified/new files
3. Check if changes are legitimate (plugin updates, etc.)
4. If suspicious:
   ```bash
   wp sasec quarantine /path/to/suspicious/file.php
   ```
5. Restore from backup if needed
6. Investigate how file was modified
7. Check for backdoors or shell scripts

### 3. Emergency Login Used

**Symptoms:**
- `emergency_login_success` event in logs
- Emergency token or password used

**Response:**
1. **IMMEDIATELY** verify who used emergency access
2. Check IP address and user agent
3. Review all actions taken during emergency session
4. Rotate emergency password/token
5. If unauthorized:
   - Change all admin passwords
   - Review user accounts
   - Check for new admin users
   - Review recent post/page changes

### 4. Shell Signature Detected

**Symptoms:**
- `shell_signature_detected` event
- Upload blocked due to suspicious content

**Response:**
1. Review upload attempt details
2. Identify source IP and user
3. Check if legitimate file (false positive)
4. If malicious:
   - Block user/IP
   - Review all recent uploads from that user
   - Scan for other suspicious files
   - Check database for injected content

### 5. Database Configuration Changed

**Symptoms:**
- `db_config_tamper` event
- Database credentials may be compromised

**Response:**
1. **CRITICAL**: Assume database may be compromised
2. Immediately rotate database credentials
3. Review all database changes
4. Check for unauthorized admin users
5. Review recent content changes
6. Consider full security audit

## Emergency Procedures

### Site Compromise Suspected

1. **Immediate Actions:**
   - Enable maintenance mode
   - Change all admin passwords
   - Rotate emergency tokens/passwords
   - Review all admin users
   - Check for new plugins/themes

2. **Investigation:**
   - Export all logs: `wp sasec logs export --format=json --output=/tmp/logs.json`
   - Review file integrity scan results
   - Check for backdoors in common locations
   - Review database for injected content

3. **Recovery:**
   - Restore from known-good backup
   - Update all plugins/themes/core
   - Change all passwords
   - Review and harden security settings

### Locked Out of Admin

1. Use emergency login:
   - Create token via WP-CLI: `wp sasec emergency create --user=1`
   - Or use emergency password if configured
   - Access via custom URL

2. Once logged in:
   - Review why you were locked out
   - Adjust detection thresholds if needed
   - Whitelist your IP if necessary

## Prevention Checklist

- [ ] Emergency login configured and tested
- [ ] File integrity baseline created
- [ ] Email notifications working
- [ ] Detection thresholds tuned
- [ ] Regular backups configured
- [ ] Log retention policy set
- [ ] Ghost mode enabled (if needed)
- [ ] IP whitelist configured (if needed)

## Post-Incident

After resolving an incident:

1. Document what happened
2. Review logs for full timeline
3. Update security settings if needed
4. Review and update this runbook
5. Consider security audit
6. Update all software
7. Review access controls

## Contact Information

- Security Team: [Your contact]
- Hosting Provider: [Provider contact]
- WordPress Security Team: security@wordpress.org

## Resources

- WordPress Security: https://wordpress.org/support/article/hardening-wordpress/
- Sucuri Security Blog: https://blog.sucuri.net/
- OWASP Top 10: https://owasp.org/www-project-top-ten/

