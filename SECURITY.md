# Security Policy

## 📣 Reporting a Vulnerability

If you discover a security vulnerability in **XPanel**, please report it responsibly.

❌ Do NOT open a public issue.

✅ Contact us privately:

- Email: security@xpanel.sh
- Subject: [XPanel Security]

---

## 🔒 Supported Versions

| Version | Supported |
|-------|-----------|
| 1.x   | ✅ Yes    |
| <1.0 | ❌ No     |

---

## 🛡 Security Practices

XPanel follows best practices including:

- Docker isolation
- Least-privilege access
- CLI-based system control
- Secure credential storage
- Regular updates
- Real Let's Encrypt production certificates by default through HTTP Challenge, with optional Cloudflare DNS Challenge configuration
- No default demo credentials or hardcoded daemon tokens in production seed data
- No plaintext generated passwords in session flash messages
- Traefik uses a restricted Docker socket proxy instead of mounting the Docker socket directly

---

## 🙏 Acknowledgements

We appreciate responsible disclosure and will credit contributors if desired.

Thank you for helping keep **XPanel** secure.
