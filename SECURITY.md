# Security Policy

## Reporting Security Vulnerabilities

If you discover a security vulnerability in Clean Flow, please email **security@cleanflow.local** with:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

**Do not** open public GitHub issues for security vulnerabilities.

---

## Security Standards

### Authentication & Authorization

#### User Authentication
- All user accounts require strong passwords (minimum 8 characters, mixed case, numbers)
- Email verification required for client registration
- Minimum age requirement: 18 years old
- Sessions expire after 120 minutes of inactivity
- Failed login attempts logged and monitored

#### Role-Based Access Control
```
Admin        - Full system access, user/staff/booking management, reports
Staff        - Assigned jobs, schedule, ratings, proof uploads
Client       - Booking creation, tracking, ratings, profile management
```

#### API Authentication
- Device tokens rotated every 30 days: `php artisan attendance:rotate-tokens`
- API keys use Bearer authentication
- All API calls logged for audit trail

### Password Security
- Passwords hashed using bcrypt with 12 rounds
- Password reset tokens expire after 60 minutes
- Failed password reset attempts rate-limited
- Passwords cannot be reused (last 5 passwords tracked)

---

## Data Protection

### Data Encryption

**Transit (In-Motion):**
- All API communication uses HTTPS/TLS 1.2+
- Certificate pinning recommended for mobile apps
- API responses include security headers:
  ```
  X-Content-Type-Options: nosniff
  X-Frame-Options: SAMEORIGIN
  X-XSS-Protection: 1; mode=block
  Strict-Transport-Security: max-age=31536000
  ```

**At-Rest (Stored):**
- Sensitive payment information encrypted in database
- Personal data (DOB, phone) encrypted using database-level encryption
- Fingerprint templates stored only on IoT devices
- Encrypted backups stored offsite with key separation

### Personally Identifiable Information (PII)

**Collected:**
- First/Last Name
- Email Address
- Phone Number
- Date of Birth
- Address (Street, Barangay, City, Zip Code)
- Proof of Service Photos

**Protection:**
- Only visible to authorized personnel
- Automatic redaction in logs and analytics
- GDPR-compliant data retention (see Data Retention Policy)
- Data deletion on account removal

### Payment Data

**Never Stored:**
- Credit card numbers (PCI DSS compliance)
- Credit card CVV/CVC
- Bank account details

**Handled By Third Parties:**
- GCash integration (PCI compliant)
- Maya integration (PCI compliant)
- Bank transfers via verified providers

**Stored Safely:**
- Payment method preference (GCash/Maya/Cash)
- Payment status & reference ID
- Transaction timestamps

---

## Data Retention Policy

| Data Type | Retention Period | Purpose |
| --- | --- | --- |
| Booking Records | 7 years | Legal/Tax compliance |
| Attendance Logs | 2 years | Payroll audit |
| Customer Ratings | Indefinite | Quality metrics |
| Login/API Logs | 90 days | Security audit |
| Failed Login Attempts | 30 days | Intrusion detection |
| Photos (Service Proof) | Until booking archived (7 years) | Service documentation |
| Personal GDPR Data | On request deletion | GDPR compliance |

---

## IoT Device Security

### Device Token Management
- Tokens generated with 256-bit cryptographic randomness
- Tokens stored hashed in database (never transmitted)
- Tokens rotated every 30 days automatically
- Lost/compromised tokens can be revoked: `php artisan attendance:revoke-token <device_id>`

### Device Enrollment Workflow
```
1. Generate enrollment request in admin panel
2. Display QR code or manual PIN on device
3. Device confirms enrollment with PIN + fingerprint
4. Template sent encrypted to server
5. Server verifies and activates template
6. Device confirms activation
```

### Biometric Data
- Fingerprint templates never leave device (stored locally)
- Template matching done on device (server-side verification only)
- No biometric data transmitted over network
- Device has 1000 template storage capacity

### Physical Security
- Devices should be wall-mounted or physically secured
- Tamper detection capability (optional)
- Regular battery/connectivity checks via heartbeat
- Unauthorized tampering logged immediately

---

## API Security

### Rate Limiting

| Endpoint | Limit | Window |
| --- | --- | --- |
| `/iot/attendance/punch` | 6 per minute | Per device |
| `/iot/device/heartbeat` | 12 per minute | Per device |
| `/api/attendance/today` | 60 per hour | Per user |
| `/login` | 5 attempts per 15 min | Per IP |
| `/register` | 3 per hour | Per IP |

### CORS & CSRF Configuration
```
CORS Origins: Configured for approved frontend domains
CSRF Tokens: Required for all state-changing operations
SameSite Cookies: Strict mode enabled
```

### Input Validation & Sanitization
- All user inputs validated against whitelist
- SQL injection prevention via prepared statements
- XSS protection via output encoding
- File uploads: Type validation, size limits, scan for malware

---

## Audit Logging

### Events Logged
- User authentication (login, logout, failed attempts)
- Authorization changes (role/permission updates)
- Data modifications (bookings, staff assignments, settings)
- Admin actions (user management, system configuration)
- API access (device endpoints, admin endpoints)
- Failed validations and errors
- Security events (rate limit violations, token misuse)

### Log Storage
- Logs written to dedicated secure log file
- Logs rotated daily, kept for 90 days
- Access to logs restricted to admins
- Log integrity verified using checksums
- Logs indexed for searchability: `php artisan logs:search --term="failed"`

---

## Vulnerability Scanning

### Automated Scans
```bash
# Check dependencies for vulnerabilities
composer audit

# PHP security check
php vendor/bin/security-checker security:check

# Database migration security
php artisan schema:audit
```

### Regular Security Updates
- Check for updates monthly: `composer update --dry-run`
- Apply security patches within 48 hours of release
- Document all security-related changes

---

## Incident Response

### Breach Discovery Process

1. **Immediately:**
   - Isolate affected systems
   - Preserve logs and evidence
   - Notify security team: security@cleanflow.local

2. **Within 24 Hours:**
   - Assess impact scope
   - Notify affected users
   - Begin forensic analysis

3. **Within 48 Hours:**
   - Deploy fix or mitigations
   - Reset affected credentials
   - Restore from backup if necessary

4. **Within 72 Hours:**
   - Post-incident report generated
   - Root cause analysis completed
   - Preventive measures implemented

### Communication Template
```
Subject: Security Incident - User Data Protection

We detected unauthorized access to [AFFECTED_DATA].
Your [ACCOUNT/DATA] may have been accessed.

Actions Taken:
- Isolated affected systems
- Reset credentials
- Enhanced monitoring activated

Recommended Actions:
- Change your password
- Monitor account activity
- Contact support with questions

Timeline: [DATES]
Incident ID: [ID]
Support: support@cleanflow.local
```

---

## Compliance

### Standards & Regulations
- **GDPR:** European data protection compliance
- **Data Privacy Act (DPA):** Philippine data protection
- **PCI DSS:** Payment Card Industry compliance (via third-party processors)
- **OWASP Top 10:** Security best practices

### Privacy Policy Compliance
Users must accept:
- Data collection policy
- Cookie usage
- Third-party integrations (payment processors)
- Photo/evidence retention

---

## Developer Security Guidelines

### Code Review Checklist
- [ ] No hardcoded secrets, API keys, or credentials
- [ ] SQL queries use prepared statements
- [ ] User input validated and sanitized
- [ ] Authentication/authorization verified
- [ ] Error messages don't reveal system details
- [ ] Sensitive operations logged
- [ ] No debug code in production

### Secure Coding Practices
```php
// ✅ GOOD: Use prepared statements
User::query()->where('email', $email)->first();

// ❌ BAD: SQL injection risk
DB::raw("SELECT * FROM users WHERE email = '$email'");

// ✅ GOOD: Validate input
$request->validate(['email' => 'required|email']);

// ❌ BAD: No validation
$email = $request->input('email');

// ✅ GOOD: Check authorization
$this->authorize('update', $booking);

// ❌ BAD: No authorization check
$booking->update($request->validated());
```

### Secret Management
```bash
# DO use environment variables
$secret = env('API_SECRET');

# DO NOT hardcode secrets
$secret = 'abc123secret';

# DO rotate secrets regularly
php artisan attendance:rotate-tokens

# DO use Laravel's encryption
decrypt(Crypt::encrypt($sensitiveData));
```

---

## Security Checklist for Releases

Before each production release:

- [ ] Run `composer audit` - no vulnerabilities
- [ ] Run security tests: `php vendor/bin/phpunit --filter Security`
- [ ] Code review completed and approved
- [ ] No debug output or console.logs in production
- [ ] API rate limiting verified
- [ ] HTTPS/SSL configured
- [ ] Security headers set
- [ ] CORS origins whitelisted
- [ ] Environment variables documented
- [ ] Secrets rotated if needed
- [ ] Backup created and tested
- [ ] Database encryption enabled
- [ ] Audit logging verified

---

## Support & Reporting

- **Security Issues:** security@cleanflow.local
- **General Support:** support@cleanflow.local
- **Bug Reports:** GitHub Issues (after security review)
- **Questions?** See SECURITY.md or contact team

---

## Version History

| Version | Date | Changes |
| --- | --- | --- |
| 1.0 | 2026-04-15 | Initial security policy |

