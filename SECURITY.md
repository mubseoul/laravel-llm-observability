# Security Policy

## Supported Versions

We release patches for security vulnerabilities for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability within Laravel LLM Observability, please send an email to [your.email@example.com](mailto:your.email@example.com). All security vulnerabilities will be promptly addressed.

**Please do not open public issues for security vulnerabilities.**

### What to Include

When reporting a vulnerability, please include:

1. **Description** of the vulnerability
2. **Steps to reproduce** the issue
3. **Potential impact** of the vulnerability
4. **Suggested fix** (if you have one)
5. **Your contact information** for follow-up

### Response Timeline

- **Initial Response**: Within 48 hours
- **Status Update**: Within 7 days
- **Fix Release**: Depends on severity and complexity

## Security Best Practices

When using this package, please follow these security best practices:

### 1. Protect Sensitive Data

- **Keep `store_bodies` disabled** in production to prevent storing raw prompts/responses
- Configure appropriate redaction patterns for your use case
- Review metadata captured to ensure no PII is accidentally logged

```php
'recording' => [
    'store_bodies' => false, // Never enable in production
    'redact_patterns' => [
        // Add custom patterns for your sensitive data
    ],
],
```

### 2. Secure Dashboard Access

- Use appropriate middleware to restrict dashboard access
- Consider IP whitelisting for admin interfaces
- Enable 2FA for users with dashboard access

```php
'dashboard' => [
    'middleware' => ['web', 'auth', 'admin', 'ip-whitelist'],
],
```

### 3. Webhook Security

- Always use HTTPS for webhook URLs
- Implement webhook signature validation
- Use dedicated webhook endpoints with authentication
- Monitor webhook delivery logs for anomalies

### 4. Database Security

- Use separate database connection with limited privileges
- Enable encryption at rest for sensitive data
- Regularly audit database access logs
- Configure appropriate retention periods

### 5. API Key Management

- Never commit API keys to version control
- Use environment variables for all credentials
- Rotate API keys regularly
- Monitor API key usage for anomalies

### 6. Quota Configuration

- Set conservative quotas initially
- Monitor usage patterns before increasing limits
- Implement alerts for unusual usage spikes
- Review quota violations regularly

### 7. Dependency Management

- Keep package dependencies up to date
- Monitor security advisories for dependencies
- Use `composer audit` regularly
- Pin versions in production

## Known Security Considerations

### Data Retention

This package stores LLM request metadata by default. Consider:

- Configuring appropriate retention periods
- Implementing data anonymization for long-term storage
- Regular pruning of old data
- Compliance with data protection regulations (GDPR, CCPA, etc.)

### Metadata Leakage

Metadata can contain sensitive information:

- IP addresses
- User agents
- Route names
- Session IDs

Review the metadata captured and disable/redact as needed.

### Cost Tracking

Cost calculations are estimates based on configured pricing:

- Verify pricing accuracy regularly
- Monitor for pricing changes from providers
- Don't rely solely on estimates for billing
- Cross-reference with provider invoices

## Disclosure Policy

When we receive a security vulnerability report, we will:

1. Confirm receipt within 48 hours
2. Provide an initial assessment within 7 days
3. Work on a fix with priority based on severity
4. Release a security patch as soon as ready
5. Publicly disclose after fix is available (with credit to reporter if desired)
6. Update this security policy if needed

## Security Updates

Subscribe to security updates:

- Watch this repository for security advisories
- Follow our security announcements
- Check CHANGELOG.md for security-related updates

## Credits

We appreciate responsible disclosure. Security researchers who report valid vulnerabilities will be credited in:

- CHANGELOG.md
- Security advisories
- Project README (if desired)

## Contact

For security concerns, contact: [your.email@example.com](mailto:your.email@example.com)

For general questions, use GitHub Issues or Discussions.
