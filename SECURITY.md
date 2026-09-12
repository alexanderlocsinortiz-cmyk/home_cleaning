# Security Policy

## Reporting Security Vulnerabilities

Before launch, configure a monitored security contact for vulnerability reports. Do not publish a placeholder address. Reports should include:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

**Do not** open public GitHub issues for security vulnerabilities.

---

## Security Standards and Current Controls

This document records implemented controls and deployment requirements. It is
not a certification, audit opinion, or legal determination that CleanFlow
complies with any regulation. Compliance depends on the real business entity,
users, vendors, jurisdictions, contracts, and operating procedures.

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
- Device credentials expire after 30 days and can be created or rotated with `php artisan attendance:register-device <serial> <name> --rotate-token`
- Mobile API tokens use Bearer authentication and are stored as hashes
- IoT requests use a device serial, timestamp, nonce, and HMAC signature; token-only fallback is not allowed in production
- Security-sensitive authentication and device events are recorded in `security_events`

### Password Security
- Passwords hashed using bcrypt with 12 rounds
- Password reset tokens expire after 60 minutes
- Failed password reset attempts rate-limited

---

## Data Protection

### Data Encryption

**Transit (In-Motion):**
- Production deployment must terminate HTTPS using TLS 1.2+
- Release Android builds reject cleartext traffic and require an HTTPS URL; debug builds may use local emulator HTTP
- Laravel responses include:
  ```
  X-Content-Type-Options: nosniff
  X-Frame-Options: SAMEORIGIN
  Strict-Transport-Security: max-age=31536000
  Permissions-Policy: camera=(self "https://*.daily.co"), geolocation=(self), microphone=(self "https://*.daily.co")
  ```

**At-Rest (Stored):**
- The application does not store payment-card numbers or CVV/CVC; it stores payment method, status, and provider references
- Government ID numbers are encrypted with Laravel application-key encryption, and uploaded identity documents use the private upload disk
- IoT device HMAC secrets are encrypted at rest; mobile bearer tokens and device access tokens are stored as one-way hashes where applicable
- Production startup rejects local/private-storage misconfiguration; object-storage encryption, backup key separation, and restore testing remain deployment responsibilities

### Personally Identifiable Information (PII)

**Collected:**
- First/Last Name
- Email Address
- Phone Number
- Date of Birth
- Address (Street, Barangay, City, Zip Code)
- Proof of Service Photos

**Protection:**
- Access is restricted by authentication, role checks, and ownership checks
- Sensitive government identity documents are encrypted through Laravel encrypted casts and stored on the private upload disk
- Retention and deletion schedules require business-owner and legal approval; this repository does not establish GDPR or Philippine DPA compliance

### Payment Data

**Never Stored:**
- Credit card numbers (PCI DSS compliance)
- Credit card CVV/CVC
- Bank account details

**Handled By Third Parties:**
- Payment processing is delegated to configured providers; verify each provider, contract, data location, and PCI responsibility before launch

**Stored Safely:**
- Payment method preference (GCash/Maya/Cash)
- Payment status & reference ID
- Transaction timestamps

---

## Data Retention Policy

| Data Type | Retention Period | Purpose |
| --- | --- | --- |
| Booking Records | Application/business setting | Service, dispute, and accounting needs |
| Attendance Logs | Application/business setting | Payroll and operations |
| Customer Ratings | Application/business setting | Quality metrics |
| Security Events | Configured by `SECURITY_EVENT_RETENTION_DAYS` (minimum 30 days) | Security audit |
| Photos and identity documents | Application/business setting | Service and application review |
| Personal data | Delete or restrict when legally required and operationally possible | Privacy requests |

The periods above are not legal advice. The owner must approve a documented
retention schedule after confirming tax, employment, consumer, privacy, and
contractual obligations.

---

## IoT Device Security

### Device Token Management
- Tokens generated with 256-bit cryptographic randomness
- Tokens stored hashed in database (never transmitted)
- Tokens expire every 30 days and can be rotated from the admin panel or registration command
- Lost or compromised devices can be deactivated and issued new credentials

### Device Enrollment Workflow
```
1. Generate enrollment request in admin panel
2. Display QR code or manual PIN on device
3. Device confirms enrollment with PIN + local fingerprint capture
4. Server sends the approved template slot and enrollment state
5. Device stores and matches the fingerprint template locally
6. Device confirms activation; the server stores the slot identifier, not the template
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
CSRF Tokens: Required for state-changing web operations
SameSite Cookies: Strict by default in production; confirm any cross-site integration before changing it
Bearer API routes: Authenticate with mobile or signed IoT credentials, not browser cookies
```

### Input Validation & Sanitization
- All user inputs validated against whitelist
- SQL injection prevention via prepared statements
- XSS protection via output encoding
- File uploads: Type validation, size limits, and private-storage controls; add malware scanning before accepting untrusted files at scale

---

## Audit Logging

### Events Logged
- User authentication (login, logout, failed attempts)
- Authorization changes (role/permission updates)
- Selected data modifications and admin actions
- Mobile authentication, logout, password reset, and device authentication events
- Security events such as rate-limit violations and token misuse

### Log Storage
- Security events are stored append-only in the `security_events` table and pruned by the scheduled retention command
- Application logs use the configured Laravel logging channel and require infrastructure-level rotation and access control
- Do not put passwords, bearer tokens, device secrets, or government ID contents in logs

---

## Vulnerability Scanning

### Automated Scans
```bash
# Check dependencies for vulnerabilities
composer audit

# Production PHP dependencies only
composer install --no-dev --no-interaction --prefer-dist

# JavaScript production dependencies
npm audit --omit=dev --audit-level=moderate

# Application verification
php artisan test
php artisan cleanflow:verify --probe
php artisan storage:verify --probe
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
   - Notify the monitored security contact configured by the owner

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
Support: [published business support contact]
```

---

## Compliance

### Standards & Regulations
- **OWASP:** The codebase uses common OWASP-aligned controls such as validation, authorization, rate limiting, hashing, encryption, and secure headers.
- **GDPR / Philippine Data Privacy Act:** Applicability and compliance require a real legal assessment, data inventory, lawful-basis analysis, notices, contracts, retention rules, and operating procedures.
- **PCI DSS:** Keep payment-card data out of this application and obtain written scope/responsibility confirmation from each payment provider.

### Privacy Policy Compliance
The owner must publish and operationalize notices covering data collection, cookies,
third-party integrations, photos/evidence, retention, data-subject requests,
and incident contacts. See `docs/PRIVACY_COMPLIANCE_CHECKLIST.md`.

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

# DO rotate device secrets regularly
php artisan attendance:register-device <serial> <name> --rotate-token

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
- [ ] CORS requirements reviewed for every browser client and API integration
- [ ] Environment variables documented
- [ ] Secrets rotated if needed
- [ ] Backup created and tested
- [ ] Database encryption enabled
- [ ] Audit logging verified

---

## Support & Reporting

- **Security Issues:** Use the monitored contact configured by the owner before launch
- **General Support:** Use the published business support contact
- **Bug Reports:** GitHub Issues (after security review)
- **Questions?** See SECURITY.md or contact team

---

## Version History

| Version | Date | Changes |
| --- | --- | --- |
| 1.1 | 2026-09-13 | Align policy with implemented controls and add compliance caveats |

