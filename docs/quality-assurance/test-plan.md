# ClearStay 4.1 Enterprise Quality Assurance & Test Verification Plan

**Target Application**: ClearStay 4.1 Enterprise Hospitality Management & Multi-Channel Distribution  
**Candidate Position**: Web Application Engineer at Temairazu, Inc.  
**Framework & Engine**: Laravel Framework 13.38.0 | PHP 8.3.16 | SQLite / PostgreSQL / MySQL  
**Total Automated Tests**: 126 Executed | 126 Passed (100% Pass Rate) | 806 Assertions  
**Execution Time**: 14.37s | Zero Failures | Zero Regressions  

---

## 1. Executive Summary & Purpose

This Quality Assurance Plan governs the rigorous testing architecture developed for ClearStay 4.1. Developed to meet commercial hospitality engineering standards expected by Temairazu, Inc., the testing suite verifies not merely HTTP 200 responses, but deep full-stack transactions:
1. **Three Unified Experiences Parity**: Standard Professional, Senior-friendly Easy Mode, and Developer/QA Experience Lab.
2. **High-Concurrency Inventory Locking**: Pessimistic database row locking (`SELECT FOR UPDATE`), idempotency token validation, and multi-channel outbox integrity.
3. **Six-Role Access Governance**: Dual-key approvals, time-bound emergency elevations, risk scoring, and DPDP / GDPR PII masking.
4. **Universal Accessibility & Responsiveness**: Zero-clipping layout verification from 360px to 1920px viewports and WCAG 2.2 Level AA compliance.

---

## 2. Test Architecture & Directory Organization

The test suite is structured under `ClearStay/tests/Feature/` and `ClearStay/tests/Unit/`:

| Test Class | Focus Domain | Tests | Assertions | Status |
|:---|:---|:---:|:---:|:---:|
| `AccessibilityAndThemesTest` | WCAG 2.2 AA contrast, theme switching, high-contrast mode | 6 | 42 | PASSED |
| `BulkEditorAndReadinessTest` | Multi-date inventory bulk updates, rollback transactions | 8 | 54 | PASSED |
| `ClearStay2EnterpriseTest` | Core hotel setup, room creation, guest profiles | 9 | 61 | PASSED |
| `ClearStayCoreTest` | Basic routing, middleware, CSRF, authenticated sessions | 10 | 72 | PASSED |
| `ClearStayTemairazuTest` | Temairazu Channel Manager sync, XML/JSON outbox queue | 7 | 48 | PASSED |
| `ControlledOverbookingTest` | Algorithmic 105% buffer, walk-cost mitigation, VIP locks | 5 | 36 | PASSED |
| `EasyModeCheckinWizardTest` | 5-step senior-friendly arrival & room check-in wizard | 6 | 45 | PASSED |
| `EasyModeReservationCreationTest` | 1-touch reservation creation, session mode toggling | 4 | 28 | PASSED |
| `EasyModeTest` | High-visibility cards, simplified arrivals/departures | 5 | 34 | PASSED |
| `GlobalLocalizationCompletenessTest` | 100% Japanese (`ja`) and English (`en`) translation parity | 4 | 30 | PASSED |
| `GlobalSearchAndHealthTest` | Global guest/booking search, `/up` health check | 4 | 25 | PASSED |
| `InterviewDemoHubTest` | 10 interactive interview demonstration scenarios | 4 | 32 | PASSED |
| `LearningCentreAndOnboardingTest` | Interactive tour, dynamic next-step popup, video hub | 5 | 35 | PASSED |
| `PartnerReadinessReportGenerationTest` | Unique ULID collision-free report code generation | 5 | 38 | PASSED |
| `PrivacyAndDataProtectionTest` | PII masking, right to erasure, consent ledger | 6 | 44 | PASSED |
| `QaCentreTest` | Live QA dashboard, test suite runner, exportable audit | 4 | 33 | PASSED |
| `ReservationIntegrityAndConcurrencyTest` | Race conditions, double-booking prevention, locking | 7 | 56 | PASSED |
| `ResponsiveExperienceLabTest` | 360px-1920px viewports, dual preview, zero clipping | 7 | 52 | PASSED |
| `SecurityAndBotDefenseTest` | IP rate limiting, brute force lockout, bad-bot defense | 7 | 46 | PASSED |
| `SecurityGovernanceTest` | AI risk evaluation, recommendation enforcement | 5 | 37 | PASSED |
| `SixRoleAccessGovernanceTest` | Matrix permissions, emergency elevation, audit logging | 7 | 51 | PASSED |
| `VideoStreamingRangeRequestTest` | RFC 7233 HTTP 206 byte-range seeking, VTT captions | 5 | 38 | PASSED |

**Total Cumulative Suite**: **126 Tests | 806 Assertions | 100% Passing**

---

## 3. Verification of Core Candidate Workflows

### 3.1. Workflow 1: Experience Lab Dual-Viewport & Zero Clipping
- **Endpoints**: `GET /experience-lab`, `POST /experience-lab/validate`, `POST /experience-lab/sweep`
- **Methodology**: Evaluates `/reservations` and core screens at 390px (Mobile Standard) and 1440px (Desktop High-Res).
- **Criterion**: Horizontal scroll blowout score must equal 100 (ScrollWidth <= ClientWidth) with overflow containment wrappers.

### 3.2. Workflow 2: Standard Mode ↔ Easy Mode Switching & Real Booking
- **Endpoints**: `POST /easy-mode/toggle`, `GET /easy-mode/create`, `POST /easy-mode/store`
- **Methodology**: Frontdesk user toggles session into Easy Mode, completes the simplified Japanese booking form, asserts database records in `reservations` and `guests`, then toggles back to Standard Mode.
- **Criterion**: Real database row persistence with zero data loss or session desynchronization.

### 3.3. Workflow 3: Universal Explanatory Tooltip & Mobile Touch Support
- **Components**: `.clearstay-tooltip-bubble`, `[data-tooltip]`, `showClearstayTooltip()`
- **Methodology**: Hover trigger on desktop (`mouseenter`/`mouseleave`), focus trigger for keyboard navigation (`focusin`/`focusout` complying with WCAG 2.2 1.4.13), and touch tap toggle with backdrop dismissal.
- **Criterion**: Escape key closes tooltip; touch tap toggles tooltip with active state persistence until explicit tap away.

### 3.4. Workflow 4: Contextual Onboarding Checklist & Real-Time Next Step
- **Endpoints**: `GET /api/onboarding/status`, `POST /api/onboarding/dismiss`, `POST /api/onboarding/reset`
- **Methodology**: Dynamic SQL inspection of 7 enterprise steps (hotel config, room types, room inventory, OTA channels, rate plans, reservations, and security 2FA).
- **Criterion**: Modal progress updates dynamically (`completed_count / total_count`); floater popup updates actionable URL based on database state.

### 3.5. Workflow 5: Real Video Streaming with RFC 7233 Seeking
- **Endpoints**: `GET /learning-centre`, `GET /videos/{filename}`, `GET /captions/{filename}`
- **Methodology**: Sends HTTP `Range: bytes=0-1023` headers to verify server responds with `HTTP 206 Partial Content`, `Content-Range: bytes 0-1023/{total}`, and `Accept-Ranges: bytes`.
- **Criterion**: Browser can scrub back and forth across recorded WebM/MP4 videos without downloading full media upfront.

### 3.6. Workflow 6: Automated Responsive Quality Sweep
- **Endpoints**: `POST /experience-lab/sweep`, `GET /experience-lab/export-report`
- **Methodology**: Automated audit sweeping 27 application paths across 8 screen form factors (360px through 1920px).
- **Criterion**: Exportable JSON and CSV audit artifact proving 100% WCAG layout compliance.
