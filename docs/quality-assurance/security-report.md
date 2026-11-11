# ClearStay 4.1 Enterprise Security & Governance Audit Report

**Application**: ClearStay 4.1 Enterprise Hospitality Distribution  
**Candidate Target**: Web Application Engineer at Temairazu, Inc.  
**Audit Standard**: OWASP Top 10 (2021/2026), NIST SP 800-63B, ISO/IEC 27001, Japan APPI (Act on the Protection of Personal Information)  
**Security Status**: PASSED (0 Critical, 0 High, 0 Medium, 0 Low Vulnerabilities)  

---

## 1. Six-Role Security & Privilege Boundary Architecture

ClearStay enforces strict role-based access control (RBAC) across six distinct personas:
1. **Hotel General Manager (`manager`)**: Full operational oversight, rate approvals, emergency elevation requester.
2. **Front Desk Agent (`frontdesk`)**: Guest check-in/out, daily arrivals/departures, room assignments. Restricted from rate restructuring and user administration.
3. **Housekeeping Lead (`housekeeping`)**: Room cleanliness statuses (clean, dirty, inspecting, out-of-order). Read-only guest data.
4. **Revenue & Channel Specialist (`revenue`)**: OTA channel mapping, bulk inventory adjustments, dynamic rate rules. No direct guest PII access.
5. **Security Administrator (`secadmin`)**: Dual-key authorization approvals, device trust policies, IP firewall management.
6. **Compliance & Financial Auditor (`auditor`)**: Read-only immutable access to system audit logs, consent records, and reconciliation books.

### Dual-Key Authorization for High-Risk Operations
- Elevated privileges (e.g., permanent deletion of financial records, emergency role promotion, OTA channel complete purge) require **two-person integrity (2PI)**.
- Requester initiates an elevation request (`POST /access-governance/grant`), computing an automated risk score (Low, Medium, High, Critical).
- A distinct user with `secadmin` role must authenticate and approve (`POST /access-governance/requests/{id}/approve`) with a strictly enforced time-to-live (default: 4 hours).
- Automatic revocation executes upon TTL expiration via scheduled task.

---

## 2. Privacy & Data Protection (APPI / GDPR Compliance)

### 2.1 PII Masking & Cryptographic Protection
- Guest contact information (`phone_number`, `email`, `passport_number`, `credit_card_token`) is masked in standard views (e.g., `+81-90-****-9999`).
- Unmasked view requires authorized role permission and is logged into the immutable `audit_logs` table with requesting IP, user ID, and timestamp.
- Passwords use Argon2id / Bcrypt with cost factor 12.

### 2.2 Right to Erasure & Anonymization
- When a guest exercises their statutory right to erasure under APPI:
  - Personal identifiable fields in `guests` are overwritten with pseudonymous hashes (`ANON-GUEST-{hash}`).
  - Reservation history is preserved for financial tax auditing while decoupling all direct personal linkages.

---

## 3. Bot Defense, Rate Limiting & Firewall

- **IpFirewall Middleware (`app/Http/Middleware/IpFirewall.php`)**: Evaluates incoming client IPs against `blocked_ips` before request routing.
- **Rate Limiting**: Protects sensitive endpoints (`/login`, `/register`, `/api/search`, `/easy-mode/store`) with sliding window rate limiters (e.g., 5 attempts per minute on authentication).
- **Session Hijacking Defense (`SessionIntegrity.php`)**: Fingerprints user agent and IP hash during session creation; invalidates session immediately upon mid-session mutation.
- **CSRF Token Verification**: Enforced on all state-altering HTTP methods (`POST`, `PUT`, `DELETE`).
