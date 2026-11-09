<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        // Check if physical video exists on disk
        $videoFilePath = base_path("docs/demonstrations/videos/{$activeVideo['filename']}");
        $hasPhysicalVideo = file_exists($videoFilePath);

        return view('learning_centre.index', compact('videos', 'activeVideo', 'hasPhysicalVideo'));
    }

    /**
     * Stream demonstration video file securely.
     */
    public function streamVideo(Request $request, string $filename)
    {
        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);
        $path = base_path("docs/demonstrations/videos/{$filename}");
        $realPath = realpath($path);

        if (!$realPath || !file_exists($realPath)) {
            abort(404, 'Video demonstration not found.');
        }

        $fileSize = filesize($realPath);
        return response()->stream(function () use ($realPath) {
            $fp = fopen($realPath, 'rb');
            if ($fp) {
                fpassthru($fp);
                fclose($fp);
            }
        }, 200, [
            'Content-Type' => 'video/mp4',
            'Content-Length' => $fileSize,
            'Accept-Ranges' => 'bytes',
        ]);
    }

    /**
     * Complete video demonstration catalog with transcripts and metadata.
     */
    public function getVideoCatalog(): array
    {
        return [
            [
                'id' => '01',
                'title' => '01 — Complete ClearStay 3.0 Platform Overview',
                'title_ja' => 'ClearStay 3.0 全体概要とナビゲーション',
                'filename' => '01-clearstay-introduction.mp4',
                'duration' => '02:45',
                'category' => 'Getting Started',
                'badge' => 'Platform Tour',
                'description' => 'Comprehensive walkthrough of the ClearStay 3.0 enterprise architecture: Unified Overview, multi-tenant hotel switcher, bilingual Japanese/English interface, Command Palette (Ctrl+K), and live telemetry health hub.',
                'script_file' => '01-clearstay-introduction.md',
                'key_takeaways' => [
                    'Multi-tenant hotel context isolation',
                    'Spotlight Command Palette navigation',
                    'Unified Overview operational KPIs',
                ],
            ],
            [
                'id' => '02',
                'title' => '02 — Concurrency-Safe Hotel Reservation Workflow',
                'title_ja' => '行ロック・二重予約防止と予約管理ワークフロー',
                'filename' => '02-reservation-workflow.mp4',
                'duration' => '03:15',
                'category' => 'Core Operations',
                'badge' => 'ACID Transactions',
                'description' => 'Demonstration of reservation creation, concurrency protection using SELECT ... FOR UPDATE, room reallocation, transactional date modifications, and guest self-service token portal access.',
                'script_file' => '02-reservation-workflow.md',
                'key_takeaways' => [
                    'Pessimistic row locking for zero overbooking',
                    'Interactive date modification modal',
                    'Tokenized guest self-service portal link',
                ],
            ],
            [
                'id' => '03',
                'title' => '03 — Bulk Change Studio & 1-Click Rollback',
                'title_ja' => '一括更新スタジオと1クリック即時ロールバック',
                'filename' => '03-inventory-bulk-update.mp4',
                'duration' => '02:50',
                'category' => 'Inventory & Distribution',
                'badge' => 'Audit & Rollback',
                'description' => 'Step-by-step demonstration of bulk room inventory and rate modifications, visual before-and-after diff inspection modal, transactional batch commits, and instant 1-click snapshot rollback.',
                'script_file' => '03-inventory-bulk-update.md',
                'key_takeaways' => [
                    'Multi-date, multi-room category bulk editing',
                    '2-step visual before/after diff confirmation',
                    'Audit snapshot batch history & rollback',
                ],
            ],
            [
                'id' => '04',
                'title' => '04 — Multi-Channel Synchronization & Outbox Engine',
                'title_ja' => 'OTAチャネル連携とTransactional Outbox配信',
                'filename' => '04-channel-synchronization.mp4',
                'duration' => '03:10',
                'category' => 'Integrations',
                'badge' => 'OTA Connect',
                'description' => 'How ClearStay distributes inventory changes to Booking.com, Agoda, and Expedia using the Transactional Outbox pattern, automated exponential backoff retry, and simulated network error handling.',
                'script_file' => '04-channel-synchronization.md',
                'key_takeaways' => [
                    'Central Outbox event pipeline',
                    'Idempotency key enforcement per event',
                    'Channel error injection and dead-letter retry',
                ],
            ],
            [
                'id' => '05',
                'title' => '05 — Hospitality Analytics & Booking Pace (BA+)',
                'title_ja' => '経営分析・予約ペース・帳票CSV出力',
                'filename' => '05-analytics-and-reporting.mp4',
                'duration' => '02:30',
                'category' => 'Executive Analytics',
                'badge' => 'BA+ Pace',
                'description' => 'In-depth exploration of Temairazu BA+ style booking analytics, historical booking pace curves, RevPAR/ADR/Occupancy tracking, date range filtering, and one-click CSV export generation.',
                'script_file' => '05-analytics-and-reporting.md',
                'key_takeaways' => [
                    'Historical pace vs same-time-last-year comparisons',
                    'Real-time RevPAR, ADR, and Occupancy calculations',
                    'Streamed CSV executive report exports',
                ],
            ],
            [
                'id' => '06',
                'title' => '06 — Partner Assurance & Integration Quality Lab',
                'title_ja' => 'パートナー接続検証・EventTrace・準備レポート',
                'filename' => '06-partner-assurance.mp4',
                'duration' => '03:40',
                'category' => 'Partner Quality',
                'badge' => 'Certification Lab',
                'description' => 'Contract-first integration engineering: JSON schema validation, automated scenario test runner with live latency measurement, EventTrace timeline inspection, and ULID-based collision-resistant Partner Readiness Reports.',
                'script_file' => '06-partner-assurance.md',
                'key_takeaways' => [
                    'OpenAPI / JSON Schema contract validation',
                    'Automated 7-step partner certification suite',
                    'ULID collision-resistant readiness reports',
                ],
            ],
            [
                'id' => '07',
                'title' => '07 — Easy Mode for Everyday Hotel Operations',
                'title_ja' => 'シニア・非技術者向けかんたん安心操作モード',
                'filename' => '07-easy-mode-guide.mp4',
                'duration' => '02:20',
                'category' => 'Accessibility & UX',
                'badge' => 'Easy Mode',
                'description' => 'The senior-friendly operational interface: 18px+ high contrast typography, 5 core guided operations (Check-In, Check-Out, Room Cleaning, Reservation Lookup), mistake-prevention dialogs, and instant text scaler.',
                'script_file' => '07-easy-mode-guide.md',
                'key_takeaways' => [
                    'High-contrast, large-button tactile design (WCAG AA)',
                    'Guided step-by-step guest check-in & check-out',
                    '1-touch visual room cleaning status toggles',
                ],
            ],
            [
                'id' => '08',
                'title' => '08 — Responsive Experience Lab & Dual Emulation',
                'title_ja' => 'レスポンシブ体験ラボと実機ビューポート比較',
                'filename' => '08-responsive-experience.mp4',
                'duration' => '03:00',
                'category' => 'QA Engineering',
                'badge' => 'Experience Lab',
                'description' => 'The developer and QA verification hub: 8 calibrated hardware presets (360px up to 1920px), zero-clipping container-fit visual scaler, dual side-by-side synchronized comparison, and automated responsive audits.',
                'script_file' => '08-responsive-experience.md',
                'key_takeaways' => [
                    'Genuine CSS viewports with zero horizontal clipping',
                    'Dual desktop vs smartphone synchronized comparison',
                    'Automated WCAG 2.1 AA tap target and overflow audit',
                ],
            ],
        ];
    }
}
