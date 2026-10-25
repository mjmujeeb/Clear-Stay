# ClearStay - Interview Portfolio & Technical Architecture Guide

This comprehensive guide is prepared specifically for technical interviews (including Japanese tech companies and global engineering teams). Every claim in this document is fully built, runnable, and verified in the ClearStay codebase.

---

## 1. The 30-Second Interview Elevator Pitch

### In English:
> *"ClearStay is an enterprise-grade hotel stay and operations management system built with PHP 8.3, Laravel, MySQL, HTML5, mobile-first CSS, JavaScript, and jQuery. It implements multi-tenant architecture with `hotel_id` data isolation, concurrency-safe reservation date changes using pessimistic database row locking (`SELECT ... FOR UPDATE`), a real-time room status board with live jQuery AJAX polling, and an analytics dashboard powered by Chart.js. I configured automated deployment and backup bash scripts for Ubuntu Linux on AWS with Nginx, PHP-FPM, Supervisor queue workers, and cron schedulers, with full Japanese and English bilingual support and structured JSON-LD SEO."*

### 日本語面接用 (In Natural Japanese for Japanese Interviews):
> 「私が開発した『ClearStay（クリアステイ）』は、ホテル・宿泊施設向けの運営・予約管理システムです。貴社の開発スタックと合致する**PHP 8.3、Laravel、MySQL、HTML、CSS、JavaScript、jQuery**を用いて設計・実装いたしました。
>
> 技術的な特徴として、**『hotel_id』による完全なマルチテナント分離設計**、宿泊日程の変更時にダブルブッキング（重複予約）を物理的に防止する**データベーストランザクションと行レベルロック（`SELECT ... FOR UPDATE`による排他制御）**を実装しています。
>
> フロントエンドでは、jQueryの非同期AJAX通信による**客室ステータスボードの10秒間隔リアルタイム更新**や詳細プレビューモーダル、**Chart.jsを用いた客室稼働率（Occupancy）やRevPARの経営分析ダッシュボード**を構築しました。
>
> インフラ面では、**AWS（EC2/Lightsail、RDS、S3、SES）環境のUbuntu Linux**を想定し、**Nginx + PHP-FPM**の設定、キューワーカーを常駐させる**Supervisor**、定期タスクや日次バックアップを実行する**Cron**、デプロイやバックアップの**Bashスクリプト**、不正ログイン対策の**Fail2Ban**まで実運用レベルで整備しています。」

---

## 2. One-Page "Tech Stack" Slide (Interview Summary Sheet)

```
====================================================================================================
                        CLEARSTAY - HOTEL OPERATIONS & CHANGE MANAGEMENT SYSTEM
====================================================================================================

[ BACKEND CORE ]
  • Runtime: PHP 8.3 (Strict types, match expressions, optimized JIT)
  • Framework: Laravel 11/12/13 (MVC, Eloquent, Global Tenant Scopes, Form Requests, Policies)
  • Database: MySQL 8.0 (InnoDB engine, Foreign keys, ACID transactions, Row-Level Locking)
  • Architecture: Multi-Tenant Data Isolation (hotel_id on every entity table)

[ CONCURRENCY & INTEGRITY ]
  • Technique: Pessimistic Row Locking via `Room::where('id', $id)->lockForUpdate()`
  • Guarantee: Serializes competing date-change / room-booking requests, preventing race conditions
  • Audit Trail: `reservation_changes` and `audit_logs` capturing user, timestamps, and old/new values

[ FRONTEND & INTERACTIVITY ]
  • Markup & Styling: Semantic HTML5, Mobile-First CSS (responsive layout, badge status styling)
  • Scripting & DOM: JavaScript (ES6+) and jQuery 3.7
  • Live Room Board: 10-second automatic polling AJAX endpoint (`/board/status`) with pulse indicators
  • Pop-up Preview: Asynchronous modal fetching guest info, room specs, and live date adjustment form
  • Data Visualization: Chart.js 4.4 (14-day Occupancy Trend, Monthly Revenue, Room Type Share)

[ BULK DATA & GUEST SERVICES ]
  • CSV Import: Transactional batch import with line-by-line validation and error reporting
  • Guest Portal: Token-based zero-friction guest portal (`/guest/portal/{token}`) for amenity requests
  • Internationalization: 100% Bilingual localization (English & Japanese `en` / `ja`)

[ DEVOPS & INFRASTRUCTURE (Ubuntu / AWS) ]
  • Cloud Hosting: AWS EC2 / Lightsail (Ubuntu 22.04 LTS), RDS MySQL, S3 Backups, SES Email
  • Web Server: Nginx (Reverse proxy, HTTP/2, Gzip compression, SSL via Certbot Let's Encrypt)
  • Process Management: PHP 8.3-FPM + Supervisor daemon (`clearstay-worker`) for background jobs
  • Automation: Bash scripts (`deploy.sh` zero-downtime deploy, `backup.sh` with 7-day retention)
  • Security: UFW firewall, Fail2ban brute-force login blocking, Rate Limiting (5 attempts/min)

[ SEO & WEB STANDARDS ]
  • Metadata: Open Graph, Twitter Cards, Canonical tags, hreflang (`en`, `ja`)
  • Structured Data: JSON-LD Schema.org (`LodgingBusiness`, `Hotel`)
  • Crawling: Dynamic `sitemap.xml` and `robots.txt`
====================================================================================================
```

---

## 3. Top Interview Questions & How to Answer Them

### Q1: "How did you prevent double-booking race conditions during date changes?"
**Answer:**
> *"In hotel management systems, two front desk staff or OTA webhooks might attempt to book or shift a reservation to the same room for overlapping dates at the exact same millisecond. If we simply read the room availability with a standard `SELECT` query, both requests will read the room as free, resulting in a double-booking.
>
> In ClearStay, I solved this inside `ReservationService::changeDatesWithRowLock()` by wrapping the operation in an atomic `DB::transaction()`. Inside the transaction, we call `Room::where('id', $roomId)->lockForUpdate()`. This issues an exclusive row-level lock (`SELECT ... FOR UPDATE`) in MySQL.
>
> Any concurrent request targeting that room is forced to wait until the first transaction commits or rolls back. When the second transaction proceeds, it queries overlapping dates with the lock held, detects the newly saved booking, and throws a validation exception with the conflicting reservation code. We also log the before-and-after values in a dedicated `reservation_changes` table for full auditability."*

---

### Q2: "How is Multi-Tenancy implemented in ClearStay?"
**Answer:**
> *"ClearStay uses a single-database, shared-schema multi-tenant model. Every core table (`rooms`, `room_types`, `guests`, `reservations`, `housekeeping_tasks`, `audit_logs`) has a `hotel_id` foreign key referencing the `hotels` table.
>
> To make data isolation seamless and foolproof, I built a custom Eloquent Global Scope called `TenantScope` and a model trait called `BelongsToHotel`. Whenever a staff member is authenticated, the `TenantScope` automatically intercepts all Eloquent queries and appends `WHERE hotel_id = ?` based on the user's assigned property or session.
>
> When creating new models, the trait's `creating` hook automatically injects the tenant `hotel_id`. SuperAdmins have elevated privileges to switch hotel contexts via a topbar selector, while property managers and front desk staff are strictly confined to their own hotel's records."*

---

### Q3: "How does the Live Room Board and Pop-up Preview work?"
**Answer:**
> *"On the front desk operations screen, the room board displays a grid of all rooms categorized by floor, with visual color-coded badges for clean, dirty, inspecting, maintenance, and occupied statuses.
>
> I implemented two key interactive features using JavaScript and jQuery:
> 1. **Live Board Refresh**: A background interval calls `/board/status` via jQuery AJAX every 10 seconds. The server returns JSON with updated counts and room states. jQuery traverses the DOM and smoothly updates room cards, badges, and guest names without triggering a full-page reload or flickering.
> 2. **Pop-up Preview Modal**: Clicking any room card triggers `openReservationModal(reservationId)`. jQuery fires an AJAX GET request to `/reservations/{id}` and populates an accessible modal with guest contact info, stay dates, pricing, VIP status, and a direct form to execute row-locked date modifications or room reassignments."*

---

### Q4: "How do you handle bulk CSV reservation imports?"
**Answer:**
> *"The CSV import feature allows hotel managers to upload reservation batches (such as migration from an old PMS or group bookings).
>
> In `CsvImportService`, the uploaded file is processed line-by-line. We validate the headers, check individual fields (dates, email formats, pax counts), and verify room existence. Each row is wrapped in a nested database transaction with availability checks. If a specific row has conflicting dates or an invalid room number, that error is recorded with its line number, while other valid rows are safely committed.
>
> The controller returns a detailed summary showing total imported records, failed rows, and specific line error messages so the manager can rectify only the failed entries."*

---

### Q5: "What is your server setup and deployment process on AWS / Ubuntu?"
**Answer:**
> *"For production, ClearStay runs on an AWS Ubuntu EC2 instance with Nginx and PHP 8.3-FPM connected to an AWS RDS MySQL database.
>
> I authored three production-grade Bash scripts:
> - `deploy.sh`: Puts the app in maintenance mode (`php artisan down`), pulls the Git release, runs `composer install --no-dev --optimize-autoloader`, executes `php artisan migrate --force`, caches routes/configs/views, restarts queue workers via Supervisor, and reloads PHP-FPM without terminating active connections.
> - `backup.sh`: Executes a daily `mysqldump` with `--single-transaction`, gzips the output, copies uploads, pushes the archive to an AWS S3 bucket (`aws s3 cp`), and prunes local backups older than 7 days.
> - `server-setup.sh`: Automates Ubuntu provisioning, configuring UFW firewall (allowing only SSH and Nginx), Let's Encrypt SSL via Certbot, and Fail2Ban to block brute-force login attempts."*

---

## 4. Key Japanese Technical Terms Cheat Sheet (日本語面接対策)

| English Term | 日本語 | 読み (Reading) | 使い方・文脈 |
|---|---|---|---|
| Multi-tenant | マルチテナント | まるちてなんと | 「`hotel_id`によるマルチテナントデータ分離」 |
| Concurrency Control | 並行性制御 / 排他制御 | へいこうせいせいぎょ / はいたせいぎょ | 「複数リクエストの同時実行に対する排他制御」 |
| Row-Level Lock | 行レベルロック | ぎょうれべるろっく | 「`SELECT ... FOR UPDATE`による行ロック」 |
| Double Booking | 重複予約 | ちょうふくよやく | 「重複予約（ダブルブッキング）を防止する」 |
| Database Transaction | データベーストランザクション | でーたべーすとらんざくしょん | 「アトミックなトランザクション処理」 |
| Front Desk / Reception | フロントデスク / 帳場 | ふろんとですく / ちょうば | 「フロント業務の効率化」 |
| Room Status | 客室ステータス | きゃくしつすてーたす | 「清掃済、清掃待ち、点検中、利用中」 |
| Occupancy Rate | 客室稼働率 | きゃくしつかどうりつ | 「当月の客室稼働率をChart.jsで可視化」 |
| Average Daily Rate (ADR) | 平均客室単価 | へいきんきゃくしつたんか | 「ADRおよびRevPARの集計クエリ」 |
| Zero-Downtime Deployment | ゼロダウンタイムデプロイ | ぜろだうんたいむでぷろい | 「Bashスクリプトによるデプロイ自動化」 |
