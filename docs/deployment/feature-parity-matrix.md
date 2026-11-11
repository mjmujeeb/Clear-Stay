# ClearStay 4.1 Feature Parity Matrix: Local Development vs. Production Cloud

**Purpose**: Verifies 100% operational fidelity between local workstation engineering and containerized cloud production.  
**Parity Principle**: "Works on my machine" is unacceptable for enterprise hospitality distribution.  

---

| System Capability | Local SQLite Memory | Production MySQL / PostgreSQL | Parity Verification Mechanism | Status |
|:---|:---|:---|:---|:---:|
| **Pessimistic Concurrency Locking** | SQLite Transaction Simulation | `SELECT ... FOR UPDATE` row locks | `ReservationIntegrityAndConcurrencyTest` simulates simultaneous requests; both drivers prevent double-booking. | MATCH |
| **Idempotency Token Deduplication** | In-memory key caching | DB unique token constraints (`idempotency_keys`) | Enforces ULID uniqueness across OTA webhook retries. | MATCH |
| **Bilingual Localization** | `resources/lang/{en,ja}` | `resources/lang/{en,ja}` | `GlobalLocalizationCompletenessTest` checks 100% key match. | MATCH |
| **Easy Mode Operational Hub** | Session-driven toggle | Cookie & DB user profile flag | 1-touch arrivals, departures, room status updates. | MATCH |
| **Responsive Experience Lab** | Inline CSS matrix simulator | Live device viewport rendering | Viewport sweep audit produces identical pass/fail scores. | MATCH |
| **RFC 7233 Video Seeking** | StreamedResponse with byte-range headers | Nginx static sendfile or Laravel range streamer | `Range: bytes=0-1023` returns `206 Partial Content`. | MATCH |
| **Six-Role Security Matrix** | Eloquent role gates | Eloquent role gates + IP firewall | Unauthenticated requests redirect to `/login`; unauthorized to `403`. | MATCH |
| **Automated Healthchecks** | `/up` route returns 200 | Docker HEALTHCHECK & Cloud LB probe | Monitors database connectivity and memory limits. | MATCH |
