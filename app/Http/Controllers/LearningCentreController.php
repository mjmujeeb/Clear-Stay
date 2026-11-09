<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LearningCentreController extends Controller
{
    /**
     * ClearStay Demonstration & Learning Centre.
     */
    public function index(Request $request)
    {
        $videos = $this->getVideoCatalog();
        $selectedVideoId = $request->query('video', '01');

        $activeVideo = collect($videos)->firstWhere('id', $selectedVideoId) ?? $videos[0];

        // Check if physical video exists on disk (check webm first, then mp4)
        $webmPath = base_path("docs/demonstrations/videos/{$activeVideo['filename_webm']}");
        $mp4Path = base_path("docs/demonstrations/videos/{$activeVideo['filename_mp4']}");
        $hasWebm = file_exists($webmPath);
        $hasMp4 = file_exists($mp4Path);
        $hasPhysicalVideo = $hasWebm || $hasMp4;

        // Transcript contents if file exists
        $transcriptContent = '';
        $transcriptPath = base_path("docs/demonstrations/transcripts/{$activeVideo['transcript_file']}");
        if (file_exists($transcriptPath)) {
            $transcriptContent = file_get_contents($transcriptPath);
        }

        return view('learning_centre.index', compact(
            'videos',
            'activeVideo',
            'hasPhysicalVideo',
            'hasWebm',
            'hasMp4',
            'transcriptContent'
        ));
    }

    /**
     * Stream demonstration video file with full RFC 7233 HTTP Range Request support.
     * This enables browser video seeking, scrubbing, and prevents 0:00 playback hang.
     */
    public function streamVideo(Request $request, string $filename)
    {
        $filename = basename($filename);
        $path = base_path("docs/demonstrations/videos/{$filename}");
        $realPath = realpath($path);

        if (!$realPath || !file_exists($realPath)) {
            abort(404, 'Video demonstration not found.');
        }

        $fileSize = filesize($realPath);
        $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'webm' => 'video/webm',
            'mp4' => 'video/mp4',
            'ogg' => 'video/ogg',
            default => 'application/octet-stream',
        };

        $start = 0;
        $end = $fileSize - 1;
        $status = 200;

        $rangeHeader = $request->header('Range');
        if ($rangeHeader && preg_match('/bytes=(\d+)-(\d*)/', $rangeHeader, $matches)) {
            $start = (int) $matches[1];
            if (!empty($matches[2])) {
                $end = min((int) $matches[2], $fileSize - 1);
            }
            $status = 206; // Partial Content
        }

        if ($start > $end || $start >= $fileSize) {
            return response('', 416, [
                'Content-Range' => "bytes */{$fileSize}",
            ]);
        }

        $length = $end - $start + 1;

        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => (string) $length,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=86400',
        ];

        if ($status === 206) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$fileSize}";
        }

        return response()->stream(function () use ($realPath, $start, $length) {
            $fp = fopen($realPath, 'rb');
            if ($fp) {
                if ($start > 0) {
                    fseek($fp, $start);
                }

                $bufferSize = 65536; // 64 KB chunks
                $bytesRemaining = $length;

                while (!feof($fp) && $bytesRemaining > 0 && connection_status() === 0) {
                    $readSize = min($bufferSize, $bytesRemaining);
                    $data = fread($fp, $readSize);
                    if ($data === false) {
                        break;
                    }
                    echo $data;
                    flush();
                    $bytesRemaining -= strlen($data);
                }
                fclose($fp);
            }
        }, $status, $headers);
    }

    /**
     * Stream WebVTT video captions.
     */
    public function streamCaption(string $filename)
    {
        $filename = basename($filename);
        $path = base_path("docs/demonstrations/captions/{$filename}");
        $realPath = realpath($path);

        if (!$realPath || !file_exists($realPath)) {
            return response("WEBVTT\n\n1\n00:00:01.000 --> 00:00:05.000\nClearStay Demonstration Video\n", 200, [
                'Content-Type' => 'text/vtt; charset=utf-8',
            ]);
        }

        $content = file_get_contents($realPath);
        return response($content, 200, [
            'Content-Type' => 'text/vtt; charset=utf-8',
            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Stream video poster thumbnail.
     */
    public function streamPoster(string $filename)
    {
        $filename = basename($filename);
        $path = base_path("docs/demonstrations/posters/{$filename}");
        $realPath = realpath($path);

        if (!$realPath || !file_exists($realPath)) {
            abort(404, 'Poster image not found.');
        }

        $content = file_get_contents($realPath);
        return response($content, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Complete video demonstration catalog with 25 topics covering all core ClearStay features.
     */
    public function getVideoCatalog(): array
    {
        return [
            [
                'id' => '01',
                'title' => '01 — ClearStay Complete Introduction',
                'title_ja' => 'ClearStay 全体概要とアーキテクチャ',
                'filename_webm' => '01-clearstay-introduction.webm',
                'filename_mp4' => '01-clearstay-introduction.mp4',
                'poster' => '01-clearstay-introduction.jpg',
                'caption_file' => '01-clearstay-introduction.vtt',
                'transcript_file' => '01-clearstay-introduction.md',
                'duration' => '02:45',
                'category' => 'Getting Started',
                'badge' => 'Platform Tour',
                'description' => 'Comprehensive walkthrough of the ClearStay enterprise architecture: Unified Overview, multi-tenant hotel switcher, bilingual Japanese/English interface, Command Palette (Ctrl+K), and live telemetry health hub.',
                'key_takeaways' => [
                    'Multi-tenant hotel context isolation',
                    'Spotlight Command Palette navigation (Ctrl+K)',
                    'Unified Overview operational KPIs',
                ],
            ],
            [
                'id' => '02',
                'title' => '02 — Login, User Roles and Navigation',
                'title_ja' => 'ログイン・ユーザー権限ロールとナビゲーション',
                'filename_webm' => '02-login-and-roles.webm',
                'filename_mp4' => '02-login-and-roles.mp4',
                'poster' => '02-login-and-roles.jpg',
                'caption_file' => '02-login-and-roles.vtt',
                'transcript_file' => '02-login-and-roles.md',
                'duration' => '02:30',
                'category' => 'Security & Auth',
                'badge' => 'Roles & RBAC',
                'description' => 'Role-based access model across 6 distinct personas: Platform Admin, Security Admin, Hotel Manager, Front Desk Receptionist, Housekeeping Lead, and Independent Security Auditor.',
                'key_takeaways' => [
                    '6-role separation of duties',
                    'Contextual navigation permissions',
                    'Session security and audit logging',
                ],
            ],
            [
                'id' => '03',
                'title' => '03 — Manager Dashboard',
                'title_ja' => '支配人マネジメントダッシュボード',
                'filename_webm' => '03-inventory-bulk-update.webm',
                'filename_mp4' => '03-inventory-bulk-update.mp4',
                'poster' => '03-inventory-bulk-update.jpg',
                'caption_file' => '03-inventory-bulk-update.vtt',
                'transcript_file' => '03-inventory-bulk-update.md',
                'duration' => '02:50',
                'category' => 'Operations & Management',
                'badge' => 'Manager Hub',
                'description' => 'Operational command center: live RevPAR, ADR, occupancy rates, room readiness progress, pending staff privilege elevations, and multi-channel synchronization status.',
                'key_takeaways' => [
                    'Real-time RevPAR and ADR metrics',
                    'Room turnaround readiness overview',
                    'Pending privilege approval queue',
                ],
            ],
            [
                'id' => '04',
                'title' => '04 — Receptionist Reservation Workflow',
                'title_ja' => 'フロントデスク予約・チェックイン実務',
                'filename_webm' => '02-reservation-workflow.webm',
                'filename_mp4' => '02-reservation-workflow.mp4',
                'poster' => '02-reservation-workflow.jpg',
                'caption_file' => '04-receptionist-workflow.vtt',
                'transcript_file' => '04-receptionist-workflow.md',
                'duration' => '03:15',
                'category' => 'Front Desk',
                'badge' => 'Daily Operations',
                'description' => 'Step-by-step front-desk workflows: reservation search, check-in registration, room assignment, key card issuance, and guest payment reconciliation.',
                'key_takeaways' => [
                    'Fast guest lookup by name or confirmation code',
                    'Room allocation with cleaning state verification',
                    'Check-in status transition with audit record',
                ],
            ],
            [
                'id' => '05',
                'title' => '05 — Housekeeping Workflow',
                'title_ja' => '客室清掃・点検ステータス管理',
                'filename_webm' => '05-housekeeping-workflow.webm',
                'filename_mp4' => '05-housekeeping-workflow.mp4',
                'poster' => '05-housekeeping-workflow.jpg',
                'caption_file' => '05-housekeeping-workflow.vtt',
                'transcript_file' => '05-housekeeping-workflow.md',
                'duration' => '02:15',
                'category' => 'Housekeeping',
                'badge' => 'Turnaround',
                'description' => 'Floor-by-floor cleaning dispatch: real-time dirty room alerts, cleaning timer start, inspection sign-off, and automatic room availability release.',
                'key_takeaways' => [
                    'One-touch cleaning state transitions',
                    'Priority room turnover flagging for arrivals',
                    'Zero access to guest PII or rate details',
                ],
            ],
            [
                'id' => '06',
                'title' => '06 — Creating and Managing Reservations',
                'title_ja' => '新規予約作成と予約変更管理',
                'filename_webm' => '06-reservation-management.webm',
                'filename_mp4' => '06-reservation-management.mp4',
                'poster' => '06-reservation-management.jpg',
                'caption_file' => '06-reservation-management.vtt',
                'transcript_file' => '06-reservation-management.md',
                'duration' => '03:00',
                'category' => 'Core Operations',
                'badge' => 'Reservations',
                'description' => 'Detailed reservation life-cycle: multi-night stay creation, room category selection, rate code calculation, date modification, and cancellation handling.',
                'key_takeaways' => [
                    'Transactional multi-night reservation creation',
                    'Dynamic room rate calculation by season',
                    'Automated confirmation email trigger',
                ],
            ],
            [
                'id' => '07',
                'title' => '07 — Double-Booking Prevention',
                'title_ja' => '行ロックによる二重予約完全防止',
                'filename_webm' => '07-double-booking-prevention.webm',
                'filename_mp4' => '07-double-booking-prevention.mp4',
                'poster' => '07-double-booking-prevention.jpg',
                'caption_file' => '07-double-booking-prevention.vtt',
                'transcript_file' => '07-double-booking-prevention.md',
                'duration' => '03:10',
                'category' => 'Database Architecture',
                'badge' => 'ACID Locks',
                'description' => 'How ClearStay uses database pessimistic row locking (SELECT ... FOR UPDATE) and atomic date-overlap evaluation to physically prevent double-booking race conditions.',
                'key_takeaways' => [
                    'Pessimistic row locking on Room and Reservation tables',
                    'Atomic date-range overlap SQL predicates',
                    'Zero reliance on frontend-only validation',
                ],
            ],
            [
                'id' => '08',
                'title' => '08 — Concurrent Reservation Simulation',
                'title_ja' => '同時アクセス並行予約シミュレーション',
                'filename_webm' => '08-concurrency-simulation.webm',
                'filename_mp4' => '08-concurrency-simulation.mp4',
                'poster' => '08-concurrency-simulation.jpg',
                'caption_file' => '08-concurrency-simulation.vtt',
                'transcript_file' => '08-concurrency-simulation.md',
                'duration' => '03:30',
                'category' => 'Integrity Lab',
                'badge' => 'Stress Test',
                'description' => 'Stress-testing the reservation engine: 10 simulated concurrent guests attempting to book the single remaining executive room simultaneously with zero over-allocation.',
                'key_takeaways' => [
                    'Exactly 1 successful booking accepted',
                    '9 graceful 409 Conflict rejection responses',
                    'Idempotent webhook deduplication verification',
                ],
            ],
            [
                'id' => '09',
                'title' => '09 — Controlled Overbooking and Revenue Management',
                'title_ja' => '確率モデルに基づく安全なコントロールオーバーブッキング',
                'filename_webm' => '09-controlled-overbooking.webm',
                'filename_mp4' => '09-controlled-overbooking.mp4',
                'poster' => '09-controlled-overbooking.jpg',
                'caption_file' => '09-controlled-overbooking.vtt',
                'transcript_file' => '09-controlled-overbooking.md',
                'duration' => '03:45',
                'category' => 'Revenue Management',
                'badge' => 'Stochastic RMS',
                'description' => 'Mathematical overbooking decision engine based on no-show probabilities, cancellation distributions, and relocation costs with hard manager safety stop limits.',
                'key_takeaways' => [
                    'Disabled by default; manager authorization required',
                    'Comparison of 3 strategies: Zero, Fixed, and Risk-Aware',
                    'Automatic stop condition preventing excess walk risk',
                ],
            ],
            [
                'id' => '10',
                'title' => '10 — Inventory and Channel Synchronization',
                'title_ja' => '在庫・販売チャネル同期 (OTA連携)',
                'filename_webm' => '04-channel-synchronization.webm',
                'filename_mp4' => '04-channel-synchronization.mp4',
                'poster' => '04-channel-synchronization.jpg',
                'caption_file' => '10-channel-sync.vtt',
                'transcript_file' => '10-channel-sync.md',
                'duration' => '03:10',
                'category' => 'Channel Distribution',
                'badge' => 'Outbox Engine',
                'description' => 'Transactional Outbox pattern distributing inventory updates to Booking.com, Agoda, Expedia, Rakuten, and Jalan with exponential backoff and dead-letter queues.',
                'key_takeaways' => [
                    'Transactional Outbox guarantees zero message loss',
                    'Idempotency keys on all channel payloads',
                    'Automated exponential backoff retries',
                ],
            ],
            [
                'id' => '11',
                'title' => '11 — Bulk Change Studio and Rollback',
                'title_ja' => '一括更新スタジオと即時ロールバック',
                'filename_webm' => '03-inventory-bulk-update.webm',
                'filename_mp4' => '03-inventory-bulk-update.mp4',
                'poster' => '03-inventory-bulk-update.jpg',
                'caption_file' => '11-bulk-editor.vtt',
                'transcript_file' => '11-bulk-editor.md',
                'duration' => '02:50',
                'category' => 'Inventory Management',
                'badge' => 'Batch Studio',
                'description' => 'Visual 2-step before/after diff confirmation modal, atomic multi-date inventory allotments, and 1-click snapshot rollback in 42ms.',
                'key_takeaways' => [
                    'Multi-date, multi-room batch modifications',
                    'Pre-commit visual diff table',
                    '1-click instant snapshot rollback',
                ],
            ],
            [
                'id' => '12',
                'title' => '12 — Smart Rate Management',
                'title_ja' => 'スマート料金プラン・ダイナミックプライシング',
                'filename_webm' => '12-rate-management.webm',
                'filename_mp4' => '12-rate-management.mp4',
                'poster' => '12-rate-management.jpg',
                'caption_file' => '12-rate-management.vtt',
                'transcript_file' => '12-rate-management.md',
                'duration' => '02:40',
                'category' => 'Revenue & Rates',
                'badge' => 'Pricing Engine',
                'description' => 'Rate plan hierarchy: rack rates, promotional non-refundable tiers, seasonal surcharges, and OTA price parity monitoring.',
                'key_takeaways' => [
                    'Dynamic weekend and seasonal pricing multipliers',
                    'Direct booking best rate guarantee verification',
                    'Parity drift detection against OTAs',
                ],
            ],
            [
                'id' => '13',
                'title' => '13 — Partner Assurance and Integration Testing',
                'title_ja' => 'パートナー接続検証・EventTraceレポート',
                'filename_webm' => '06-partner-assurance.webm',
                'filename_mp4' => '06-partner-assurance.mp4',
                'poster' => '06-partner-assurance.jpg',
                'caption_file' => '13-partner-assurance.vtt',
                'transcript_file' => '13-partner-assurance.md',
                'duration' => '03:40',
                'category' => 'Integration Engineering',
                'badge' => 'Quality Lab',
                'description' => 'Automated partner certification lab: JSON schema validation, live latency measurement, and ULID collision-resistant Partner Readiness Reports.',
                'key_takeaways' => [
                    'Contract-first JSON schema validation',
                    'EventTrace chronological message telemetry',
                    'ULID collision-resistant certification codes',
                ],
            ],
            [
                'id' => '14',
                'title' => '14 — Staff Permission Management',
                'title_ja' => '従業員権限管理とロール割り当て',
                'filename_webm' => '14-permission-management.webm',
                'filename_mp4' => '14-permission-management.mp4',
                'poster' => '14-permission-management.jpg',
                'caption_file' => '14-permission-management.vtt',
                'transcript_file' => '14-permission-management.md',
                'duration' => '02:30',
                'category' => 'Access Governance',
                'badge' => 'RBAC Matrix',
                'description' => 'Granular permissions roster, role templates, least-privilege scoping, and tenant-isolated user management for hotel staff.',
                'key_takeaways' => [
                    'Tenant isolation per hotel property',
                    'Default least-privilege baseline',
                    'Immutable permission assignment logs',
                ],
            ],
            [
                'id' => '15',
                'title' => '15 — Permission Elevation Warnings',
                'title_ja' => '権限昇格リスク評価と警告インターフェイス',
                'filename_webm' => '15-elevation-warnings.webm',
                'filename_mp4' => '15-elevation-warnings.mp4',
                'poster' => '15-elevation-warnings.jpg',
                'caption_file' => '15-elevation-warnings.vtt',
                'transcript_file' => '15-elevation-warnings.md',
                'duration' => '02:20',
                'category' => 'Security Governance',
                'badge' => 'Risk Engine',
                'description' => 'Real-time AJAX risk evaluation modal displaying Low, Medium, High, and Critical badges with explanatory warnings before elevation submission.',
                'key_takeaways' => [
                    'Deterministic risk scoring (Low to Critical)',
                    'Justification requirement for high-risk permissions',
                    'Exploit prevention for privilege self-escalation',
                ],
            ],
            [
                'id' => '16',
                'title' => '16 — Security Administrator Approval',
                'title_ja' => 'セキュリティ管理者による2名承認プロセス',
                'filename_webm' => '16-secadmin-approval.webm',
                'filename_mp4' => '16-secadmin-approval.mp4',
                'poster' => '16-secadmin-approval.jpg',
                'caption_file' => '16-secadmin-approval.vtt',
                'transcript_file' => '16-secadmin-approval.md',
                'duration' => '02:45',
                'category' => 'Access Governance',
                'badge' => '2-Person Approval',
                'description' => 'Independent Security Administrator review queue: inspection of justification, risk level, auto-expiry timer, and approval decision.',
                'key_takeaways' => [
                    'Segregation of duties between Hotel Manager and SecAdmin',
                    'Configurable temporary elevation duration (1 to 24 hours)',
                    'Automatic privilege revocation upon expiry',
                ],
            ],
            [
                'id' => '17',
                'title' => '17 — Security Auditor Review',
                'title_ja' => '監査人によるコンプライアンス閲覧機能',
                'filename_webm' => '17-auditor-review.webm',
                'filename_mp4' => '17-auditor-review.mp4',
                'poster' => '17-auditor-review.jpg',
                'caption_file' => '17-auditor-review.vtt',
                'transcript_file' => '17-auditor-review.md',
                'duration' => '02:15',
                'category' => 'Security & Audit',
                'badge' => 'Read-Only Audit',
                'description' => 'Read-only security auditor interface: chronological audit trails, access logs, cryptographic correlation IDs, and unalterable event history.',
                'key_takeaways' => [
                    'Strict read-only access (no edit or grant capabilities)',
                    'Searchable audit trail by actor, IP, and timestamp',
                    'Exportable compliance audit reports',
                ],
            ],
            [
                'id' => '18',
                'title' => '18 — Privacy and Guest Data Protection',
                'title_ja' => 'DPDP個人情報保護とマスキング解除監査',
                'filename_webm' => '18-privacy-protection.webm',
                'filename_mp4' => '18-privacy-protection.mp4',
                'poster' => '18-privacy-protection.jpg',
                'caption_file' => '18-privacy-protection.vtt',
                'transcript_file' => '18-privacy-protection.md',
                'duration' => '03:10',
                'category' => 'Privacy Engineering',
                'badge' => 'DPDP Act',
                'description' => 'Default masking of guest phone/email, regulatory justification prompt for unmasking, and immutable audit logging aligned with DPDP Act 2023 / Rules 2025.',
                'key_takeaways' => [
                    'Default masking: +81 90-****-5678, t****a@example.com',
                    'Just-in-time audited unmasking with correlation ID',
                    'Tenant isolation: zero cross-hotel data visibility',
                ],
            ],
            [
                'id' => '19',
                'title' => '19 — Responsive Experience Lab',
                'title_ja' => 'レスポンシブ体験ラボ・実機エミュレーション',
                'filename_webm' => '08-responsive-experience.webm',
                'filename_mp4' => '08-responsive-experience.mp4',
                'poster' => '08-responsive-experience.jpg',
                'caption_file' => '19-experience-lab.vtt',
                'transcript_file' => '19-experience-lab.md',
                'duration' => '03:00',
                'category' => 'QA Engineering',
                'badge' => 'Experience Lab',
                'description' => 'Interactive developer and QA lab: 8 hardware presets from iPhone SE (375px) to 1920px Full HD, zero-clipping scaler, and automated responsive audits.',
                'key_takeaways' => [
                    'Genuine iframe rendering of actual application routes',
                    'Zero horizontal clipping across all device breakpoints',
                    'Automated WCAG 2.2 AA tap target compliance scan',
                ],
            ],
            [
                'id' => '20',
                'title' => '20 — Desktop versus Smartphone Demonstration',
                'title_ja' => 'PC・スマホ画面の並行表示・比較実演',
                'filename_webm' => '20-dual-preview.webm',
                'filename_mp4' => '20-dual-preview.mp4',
                'poster' => '20-dual-preview.jpg',
                'caption_file' => '20-dual-preview.vtt',
                'transcript_file' => '20-dual-preview.md',
                'duration' => '02:30',
                'category' => 'QA & UX',
                'badge' => 'Dual Viewports',
                'description' => 'Side-by-side comparison of 1440px desktop view and 390px smartphone view demonstrating reflow, touch buttons, and responsive table adaptation.',
                'key_takeaways' => [
                    'Simultaneous side-by-side inspection',
                    'Table swipe wrappers preventing container blowout',
                    'Mobile header action collapse into slide-out drawer',
                ],
            ],
            [
                'id' => '21',
                'title' => '21 — Easy Mode for Older and Less Technical Users',
                'title_ja' => 'シニア・非技術者向けかんたん安心操作モード',
                'filename_webm' => '07-easy-mode-guide.webm',
                'filename_mp4' => '07-easy-mode-guide.mp4',
                'poster' => '07-easy-mode-guide.jpg',
                'caption_file' => '21-easy-mode.vtt',
                'transcript_file' => '21-easy-mode.md',
                'duration' => '02:20',
                'category' => 'Accessibility & UX',
                'badge' => 'Easy Mode',
                'description' => '18px+ typography, 52px+ touch targets, 1-touch arrivals/departures, simplified room cleaning tiles, and 1-page booking wizard for everyday staff.',
                'key_takeaways' => [
                    '18px+ high-contrast readable typography',
                    '52px+ large tactile touch buttons',
                    '1-page simplified booking wizard with sensible defaults',
                ],
            ],
            [
                'id' => '22',
                'title' => '22 — Accessibility Centre',
                'title_ja' => 'アクセシビリティセンター (WCAG 2.2 AA)',
                'filename_webm' => '22-accessibility-centre.webm',
                'filename_mp4' => '22-accessibility-centre.mp4',
                'poster' => '22-accessibility-centre.jpg',
                'caption_file' => '22-accessibility-centre.vtt',
                'transcript_file' => '22-accessibility-centre.md',
                'duration' => '02:40',
                'category' => 'Universal Access',
                'badge' => 'WCAG 2.2 AA',
                'description' => '5 contrast profiles (Standard, High Contrast Light/Dark, Monochrome, Colorblind), 100%–300% fluid font scaling, reduced motion toggle, and skip links.',
                'key_takeaways' => [
                    '5 custom contrast profiles meeting 7:1 AAA contrast',
                    'Zero-blowout font scaler up to 300%',
                    'Full keyboard navigation with visible focus indicators',
                ],
            ],
            [
                'id' => '23',
                'title' => '23 — English/Japanese Language Switching',
                'title_ja' => '日英バイリンガル言語切り替え機能',
                'filename_webm' => '23-language-switching.webm',
                'filename_mp4' => '23-language-switching.mp4',
                'poster' => '23-language-switching.jpg',
                'caption_file' => '23-language-switching.vtt',
                'transcript_file' => '23-language-switching.md',
                'duration' => '02:00',
                'category' => 'Localization',
                'badge' => 'EN / JA',
                'description' => 'Complete bilingual interface: persistent language preference across sessions, zero untranslated mixed-language strings, and Japanese typography support.',
                'key_takeaways' => [
                    '100% translation coverage for all views and dialogs',
                    'Persistent session and user profile locale memory',
                    'Zero mixed-language layouts',
                ],
            ],
            [
                'id' => '24',
                'title' => '24 — Analytics and Reporting',
                'title_ja' => '経営分析・予約ペース・帳票CSV出力',
                'filename_webm' => '05-analytics-and-reporting.webm',
                'filename_mp4' => '05-analytics-and-reporting.mp4',
                'poster' => '05-analytics-and-reporting.jpg',
                'caption_file' => '24-analytics-reporting.vtt',
                'transcript_file' => '24-analytics-reporting.md',
                'duration' => '02:30',
                'category' => 'Executive Analytics',
                'badge' => 'BA+ Pace',
                'description' => 'Temairazu BA+ style booking analytics, historical booking pace curves, ADR/RevPAR/Occupancy KPIs, date filtering, and streamed CSV exports.',
                'key_takeaways' => [
                    'Historical pace vs same-time-last-year comparisons',
                    'Real-time RevPAR, ADR, and Occupancy calculations',
                    'Streamed CSV executive report exports',
                ],
            ],
            [
                'id' => '25',
                'title' => '25 — Security and System Administration',
                'title_ja' => 'システム管理・セキュリティ監査ログ',
                'filename_webm' => '25-system-administration.webm',
                'filename_mp4' => '25-system-administration.mp4',
                'poster' => '25-system-administration.jpg',
                'caption_file' => '25-system-administration.vtt',
                'transcript_file' => '25-system-administration.md',
                'duration' => '02:50',
                'category' => 'System Administration',
                'badge' => 'Infrastructure',
                'description' => 'Comprehensive administration: IEEE AI security sweeps, rate limiting, bot defense, health check endpoints, and immutable system audit logs.',
                'key_takeaways' => [
                    'Active WAF and bot mitigation rules',
                    'Sub-50ms database and cache health probes',
                    'Immutable system administration audit records',
                ],
            ],
        ];
    }
}
