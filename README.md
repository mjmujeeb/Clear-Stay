# ClearStay (クリアステイ) - Hotel & Stay Management System

An enterprise-grade, high-concurrency Hotel Operations and Reservation Management System built with **PHP 8.3, Laravel, MySQL, HTML5, Mobile-First CSS, JavaScript, and jQuery**.

Designed specifically for modern Japanese and international hotel chains, ClearStay combines real-time room dispatch, multi-tenancy, pessimistic row-locked date modifications to eliminate double-bookings, Chart.js hospitality analytics, and production-ready AWS/Ubuntu DevOps configurations.

---

## 🌟 Key Features

### 1. Concurrency-Safe Date Modifications (Row-Level Locking)
- High-concurrency room allocation and date changes powered by `DB::transaction()` and `Room::where('id', $id)->lockForUpdate()`.
- Serializes competing bookings at the database engine level, preventing double-booking race conditions.
- Every modification creates an immutable audit record in `reservation_changes` tracking old/new dates, prices, staff member, and IP.

### 2. Multi-Tenant Architecture (`hotel_id`)
- Every entity (`rooms`, `room_types`, `guests`, `reservations`, `housekeeping_tasks`, `audit_logs`) is partitioned by `hotel_id`.
- Eloquent `TenantScope` and `BelongsToHotel` trait automatically scope queries to the authenticated hotel.
- Role-Based Access Control (RBAC):
  - **Super Admin**: Enterprise headquarters access, cross-property overview, hotel context switching.
  - **Hotel Manager**: Property analytics, staff management, room pricing, CSV imports.
  - **Front Desk**: Check-in, check-out, date adjustments, pop-up previews.
  - **Housekeeping**: Live cleaning dispatch, turnover tasks, room inspection status.

### 3. Live Room Board (jQuery & AJAX)
- Visual room status grid grouped by floors with real-time badges (Clean, Dirty, Inspecting, Maintenance, Occupied).
- **Live Background Refresh**: Polling every 10 seconds via `/board/status` updates the DOM asynchronously without page reloads.
- **Quick Status Toggle**: Dropdown on each card immediately dispatches AJAX status updates.

### 4. Interactive Pop-up Preview Modal
- Clicking any room or reservation triggers an asynchronous preview modal.
- Inspects guest details, phone, VIP tier, stay duration, and special requests.
- Direct inline date modification form with room reassignments and error conflict detection.

### 5. Hospitality Analytics (Chart.js)
- **Occupancy Forecast**: 14-day and 28-day occupancy trend line chart.
- **Monthly Revenue & Volume**: Dual-axis bar and line chart of monthly revenue and bookings.
- **Room Share Distribution**: Doughnut chart illustrating popularity and bookings per room type.
- **KPI Metrics**: Real-time calculations of ADR (Average Daily Rate) and RevPAR (Revenue Per Available Room).

### 6. Bulk CSV Reservations Import
- Fast transactional bulk import of guest and reservation records.
- Line-by-line validation, duplicate prevention, and availability checks with error accumulation.
- Downloadable sample CSV template provided in the UI.

### 7. Token-Based Guest Self-Service Portal
- Secure public URL at `/guest/portal/{token}` requiring no password or registration.
- Guests can view stay countdowns, check-in instructions, hotel location, and request extra towels, pillows, or luggage drop-offs.

### 8. Full Bilingual Support (English & 日本語)
- Dynamic one-click language switcher (`/lang/{locale}`) with session persistence.
- Authentic Japanese hospitality terminology (おもてなし / 宿泊施設管理 / 客室ステータスボード / 予約台帳).

### 9. SEO & Web Standards Suite
- Optimized public landing page (`/`), rooms showcase (`/rooms`), and feature architecture (`/features`).
- Open Graph, Twitter Cards, Canonical links, and `hreflang` alternate tags.
- Google structured data schema: JSON-LD for `LodgingBusiness` and `Hotel`.
- Auto-generated `sitemap.xml` and `robots.txt`.

### 10. Production DevOps & AWS Infrastructure Kit
- `devops/scripts/deploy.sh`: Zero-downtime deployment script.
- `devops/scripts/backup.sh`: MySQL dump, upload archiving, and AWS S3 sync with 7-day retention.
- `devops/scripts/server-setup.sh`: Automated Ubuntu 22.04 / 24.04 server provisioning.
- `devops/nginx/clearstay.conf`: Nginx server block with PHP-FPM, HTTP/2, Gzip, and SSL headers.
- `devops/supervisor/clearstay-worker.conf`: Supervisor daemon configuration for queue workers.
- `devops/cron/crontab`: Scheduled tasks and nightly backup jobs.
- `devops/fail2ban/clearstay.conf`: Jail and filter blocking brute-force login attempts.
- `devops/logrotate/clearstay`: Automatic log rotation for storage logs.

---

## 🚀 Quick Start / Local Setup

### Prerequisites
- PHP 8.2 or 8.3 with extensions (`pdo_sqlite`, `pdo_mysql`, `mbstring`, `curl`, `openssl`, `fileinfo`, `gd`, `intl`, `zip`)
- Composer 2.x
- Node.js 18+ (optional, frontend assets are pre-bundled and CDN-ready)

### Step 1: Install Dependencies
```bash
composer install
```

### Step 2: Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

### Step 3: Run Database Migrations & Seeds
```bash
php artisan migrate --seed
```

### Step 4: Start Development Server
```bash
php artisan serve
```
Open **[http://127.0.0.1:8000](http://127.0.0.1:8000)** in your browser!

---

## 🔑 Demo Login Credentials

| Role | Email | Password | Access / Notes |
|---|---|---|---|
| **Super Admin** | `admin@clearstay.jp` | `password` | HQ Full Access, Hotel Context Switcher |
| **Hotel Manager** | `manager.tokyo@clearstay.jp` | `password` | Tokyo Ginza: Analytics, Staff, CSV Import |
| **Front Desk** | `frontdesk.tokyo@clearstay.jp` | `password` | Check-in, Check-out, Live Room Board |
| **Housekeeping** | `cleaner.tokyo@clearstay.jp` | `password` | Cleaning tasks, Room status toggling |

---

## 📁 Project Directory Structure

```
ClearStay/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AnalyticsController.php      # Chart.js JSON endpoints & analytics view
│   │   │   ├── AuthController.php           # Rate-limited auth & hotel context switcher
│   │   │   ├── CsvImportController.php      # CSV upload & template download
│   │   │   ├── DashboardController.php      # Operational KPIs, today's arrivals
│   │   │   ├── GuestPortalController.php    # Token-based guest self-service
│   │   │   ├── HousekeepingController.php   # Task queue & room cleaning dispatch
│   │   │   ├── LocaleController.php         # English / Japanese language switcher
│   │   │   ├── PublicController.php         # Landing, rooms, sitemap.xml, robots.txt
│   │   │   ├── ReservationController.php    # Reservation CRUD, pop-up preview, check-in
│   │   │   └── RoomBoardController.php      # Live room board, 10s AJAX polling
│   │   └── Middleware/
│   │       ├── EnsureUserHasRole.php        # Role-based route protection
│   │       └── SetLocale.php                # Session-based localization
│   ├── Models/
│   │   ├── Scopes/TenantScope.php           # Global scope enforcing hotel_id isolation
│   │   ├── AuditLog.php                     # Immutable security & operation audit log
│   │   ├── Guest.php                        # Guest profile & VIP tiers
│   │   ├── Hotel.php                        # Multi-tenant hotel property entity
│   │   ├── HousekeepingTask.php             # Cleaning & maintenance tasks
│   │   ├── Reservation.php                  # Bookings with status and unique tokens
│   │   ├── ReservationChange.php            # Date modification history
│   │   ├── Room.php                         # Rooms with floor, status, availability
│   │   ├── RoomType.php                     # Pricing, capacity, amenities
│   │   └── User.php                         # Staff members with roles
│   ├── Services/
│   │   ├── AnalyticsService.php             # SQL aggregations (ADR, RevPAR, Occupancy)
│   │   ├── CsvImportService.php             # Batch CSV processor with error reporting
│   │   ├── HousekeepingService.php          # Status transitions and task dispatch
│   │   └── ReservationService.php           # PESSIMISTIC ROW-LOCKING DATE MODIFICATIONS
│   └── Traits/
│       └── BelongsToHotel.php               # Trait attaching TenantScope & hotel_id
├── database/
│   ├── migrations/                          # Core schema migrations
│   └── seeders/ClearStaySeeder.php          # Realistic demo dataset (Tokyo, Kyoto, Osaka)
├── devops/
│   ├── aws/README_AWS_ARCHITECTURE.md       # Complete AWS cloud infrastructure guide
│   ├── cron/crontab                         # Crontab schedule for scheduler and backups
│   ├── fail2ban/                            # Jail and filter blocking brute-force logins
│   ├── logrotate/clearstay                  # Storage logs rotation
│   ├── nginx/clearstay.conf                 # Production Nginx server block with SSL
│   ├── scripts/
│   │   ├── backup.sh                        # MySQL dump and S3 backup with 7-day prune
│   │   ├── deploy.sh                        # Zero-downtime deployment script
│   │   └── server-setup.sh                  # Ubuntu server provisioning
│   └── supervisor/clearstay-worker.conf     # Supervisor background queue daemon
├── lang/
│   ├── en/clearstay.php                     # English hospitality translations
│   └── ja/clearstay.php                     # Japanese hospitality translations (日本語)
├── resources/
│   └── views/
│       ├── layouts/                         # App management and Public layouts
│       ├── auth/                            # Sign in and staff register
│       ├── board/index.blade.php            # Live room board with AJAX polling
│       ├── reservations/                    # List with preview and create forms
│       ├── housekeeping/index.blade.php     # Cleaning queue & task dispatch
│       ├── analytics/index.blade.php        # Chart.js graphs (Occupancy, Revenue, Share)
│       ├── csv/index.blade.php              # CSV bulk import
│       ├── guest/portal.blade.php           # Token guest self-service
│       └── public/                          # Landing, Rooms, Features, Contact
└── INTERVIEW_TECH_STACK_GUIDE.md            # Interview pitch, Q&A, and technical cheat sheet
```

---

## 🧪 Testing

Run PHPUnit automated test suite:
```bash
php artisan test
```

---

## 📄 License
ClearStay is open-source software licensed under the [MIT License](LICENSE).
