<?php

namespace Database\Seeders;

use App\Models\AiGovernanceRecommendation;
use App\Models\AuthorizationDecision;
use App\Models\CalendarEvent;
use App\Models\CentralInventory;
use App\Models\Channel;
use App\Models\ChannelConnection;
use App\Models\ChannelRoomMapping;
use App\Models\EventTrace;
use App\Models\Hotel;
use App\Models\IntegrationPartner;
use App\Models\IntegrationTestCase;
use App\Models\IntegrationTestSuite;
use App\Models\PartnerContract;
use App\Models\PartnerReadinessReport;
use App\Models\Permission;
use App\Models\RateParityObservation;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\SecurityRiskEvent;
use App\Models\SyncAttemptLog;
use App\Models\SyncOutboxEvent;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Models\UserPermission;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClearStay2EnterpriseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Granular Permissions Registry (IEEE Hybrid Access Control)
        $permissions = [
            // Reservations
            ['name' => 'reservations.view', 'category' => 'reservations', 'description' => 'Browse and inspect guest reservations and room boards.', 'description_ja' => '予約一覧および部屋状況ボードの閲覧'],
            ['name' => 'reservations.create', 'category' => 'reservations', 'description' => 'Book new guest stays.', 'description_ja' => '新規宿泊予約の登録'],
            ['name' => 'reservations.update', 'category' => 'reservations', 'description' => 'Change dates, room assignments, and guest details.', 'description_ja' => '日程・客室割当・宿泊者情報の変更'],
            ['name' => 'reservations.cancel', 'category' => 'reservations', 'description' => 'Process cancellation and apply cancellation fees.', 'description_ja' => '予約取消およびキャンセル料処理'],

            // Central Inventory & Bulk Operations
            ['name' => 'inventory.view', 'category' => 'inventory', 'description' => 'Inspect universal inventory matrix across room types.', 'description_ja' => '客室タイプ別在庫マトリクスの閲覧'],
            ['name' => 'inventory.update', 'category' => 'inventory', 'description' => 'Adjust allotments, minimum stays, and stop-sells.', 'description_ja' => '販売室数・最低泊数・売止の設定'],
            ['name' => 'inventory.bulk_edit', 'category' => 'inventory', 'description' => 'Execute date-range bulk commits and 1-click rollbacks.', 'description_ja' => '期間一括変更および即時ロールバック操作'],

            // Pricing & Revenue
            ['name' => 'rates.view', 'category' => 'rates', 'description' => 'Inspect rate plans, seasonality, and parity status.', 'description_ja' => '料金プラン・季節変動・パリティ状況の閲覧'],
            ['name' => 'rates.manage', 'category' => 'rates', 'description' => 'Create and modify standard, promo, and seasonal rate plans.', 'description_ja' => '標準・特別料金プランの作成と変更'],
            ['name' => 'rates.override', 'category' => 'rates', 'description' => 'Apply high-impact rate modifications and event multipliers.', 'description_ja' => '大型イベント乗数および緊急料金オーバーライド'],

            // Channels & Multi-Distribution
            ['name' => 'channels.view', 'category' => 'channels', 'description' => 'Monitor connected OTAs and outbox synchronization queue.', 'description_ja' => '接続OTAおよび送信キュー状況の監視'],
            ['name' => 'channels.manage', 'category' => 'channels', 'description' => 'Connect, disconnect, and map channel room types and credentials.', 'description_ja' => '販売先チャネル接続および客室マッピング設定'],
            ['name' => 'channels.sync', 'category' => 'channels', 'description' => 'Initiate on-demand universal broadcast and reconciliation.', 'description_ja' => '全チャネル一括配信および在庫再照合の実行'],

            // Partner Assurance & Contract Lab
            ['name' => 'partner.read', 'category' => 'partners', 'description' => 'View integration partners, contract specs, and readiness scores.', 'description_ja' => '連携パートナー・API契約仕様・認証スコアの閲覧'],
            ['name' => 'partner.manage', 'category' => 'partners', 'description' => 'Upload schemas, edit endpoints, and update contracts.', 'description_ja' => 'APIスキーマ更新および接続先設定管理'],
            ['name' => 'partner.test_execute', 'category' => 'integration', 'description' => 'Run automated sandbox scenario validation against partners.', 'description_ja' => 'シナリオ自動テストおよび品質検証の実行'],

            // Observability & EventTrace
            ['name' => 'event_trace.view', 'category' => 'integration', 'description' => 'Inspect raw incoming and outgoing payloads and logs.', 'description_ja' => '電文トレース（送受信ペイロード・通信ログ）の調査'],
            ['name' => 'event_trace.replay', 'category' => 'integration', 'description' => 'Resend webhook and outbox payloads for retry verification.', 'description_ja' => '失敗電文のリプレイ再送信・検証'],

            // Staff Governance & Security (IEEE 802)
            ['name' => 'governance.staff_view', 'category' => 'staff', 'description' => 'View staff roles, device trust, and risk scores.', 'description_ja' => '従業員権限・端末信頼度・リスクスコアの閲覧'],
            ['name' => 'governance.staff_manage', 'category' => 'staff', 'description' => 'Change department and primary role assignment.', 'description_ja' => '所属部署および基本ロールの変更'],
            ['name' => 'governance.permissions_grant', 'category' => 'staff', 'description' => 'Grant or deny granular permissions explicitly to an employee.', 'description_ja' => '個別権限の明示的付与（Allow）および拒否（Deny）'],
            ['name' => 'governance.access_suspend', 'category' => 'security', 'description' => 'Instantly freeze user account and kill active sessions.', 'description_ja' => 'アカウント即時凍結およびアクティブセッション強制終了'],
            ['name' => 'governance.access_revoke', 'category' => 'security', 'description' => 'Permanently terminate credentials, tokens, and device trust.', 'description_ja' => '権限完全抹消および端末認証無効化'],
            ['name' => 'device.manage', 'category' => 'security', 'description' => 'Register and revoke hardware fingerprint trust certificates.', 'description_ja' => '信頼端末ハードウェア登録および認証取消'],

            // Executive Reports & Analytics
            ['name' => 'reports.view', 'category' => 'reports', 'description' => 'View multi-channel revenue, occupancy, and audit metrics.', 'description_ja' => 'チャネル別売上・稼働率・監査メトリクスの閲覧'],
            ['name' => 'reports.export', 'category' => 'reports', 'description' => 'Download raw sync, inventory, and compliance audit datasets.', 'description_ja' => '同期ログ・在庫・監査データのCSVダウンロード'],
        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(['name' => $perm['name']], $perm);
        }

        // 2. Hotels retrieval
        $hotelTokyo = Hotel::where('slug', 'clearstay-premier-tokyo-ginza')->first() ?? Hotel::first();
        $hotelKyoto = Hotel::where('slug', 'clearstay-kyoto-gion-heritage')->first();
        $hotelOsaka = Hotel::where('slug', 'clearstay-urban-osaka-namba')->first();

        // 3. Enterprise Staff Accounts
        $staffMembers = [
            [
                'name' => 'Kenji Takahashi (Group Owner & CEO)',
                'email' => 'owner@clearstay.jp',
                'role' => 'owner',
                'hotel_id' => $hotelTokyo->id,
                'department' => 'Executive Leadership',
                'phone' => '+81-3-9999-0001',
                'locale' => 'ja',
            ],
            [
                'name' => 'Kenji Sato (General Manager)',
                'email' => 'manager.tokyo@clearstay.jp',
                'role' => 'manager',
                'hotel_id' => $hotelTokyo->id,
                'department' => 'Property Operations',
                'phone' => '+81-90-1111-2222',
                'locale' => 'ja',
            ],
            [
                'name' => 'Hiroshi Tanaka (Revenue Director)',
                'email' => 'revenue.tokyo@clearstay.jp',
                'role' => 'revenue',
                'hotel_id' => $hotelTokyo->id,
                'department' => 'Commercial & Revenue Strategy',
                'phone' => '+81-90-2222-3333',
                'locale' => 'en',
            ],
            [
                'name' => 'Alex Mercer (Integration & QA Engineer)',
                'email' => 'integration.qa@clearstay.jp',
                'role' => 'qa',
                'hotel_id' => $hotelTokyo->id,
                'department' => 'Hospitality Systems Architecture',
                'phone' => '+81-90-4444-5555',
                'locale' => 'en',
            ],
            [
                'name' => 'Yuki Tanaka (Senior Front Desk Lead)',
                'email' => 'frontdesk.tokyo@clearstay.jp',
                'role' => 'frontdesk',
                'hotel_id' => $hotelTokyo->id,
                'department' => 'Front Office & Guest Services',
                'phone' => '+81-90-3333-4444',
                'locale' => 'en',
            ],
            [
                'name' => 'Hiroshi Suzuki (Housekeeping Lead)',
                'email' => 'cleaner.tokyo@clearstay.jp',
                'role' => 'housekeeping',
                'hotel_id' => $hotelTokyo->id,
                'department' => 'Facilities & Environmental Care',
                'phone' => '+81-90-5555-6666',
                'locale' => 'ja',
            ],
            [
                'name' => 'Daisuke Mori (Security Compliance Auditor)',
                'email' => 'auditor@clearstay.jp',
                'role' => 'auditor',
                'hotel_id' => $hotelTokyo->id,
                'department' => 'Independent Trust & Security Audit',
                'phone' => '+81-3-8888-0007',
                'locale' => 'ja',
            ],
        ];

        $usersByEmail = [];
        foreach ($staffMembers as $s) {
            $user = User::updateOrCreate(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'password' => Hash::make('password'),
                    'role' => $s['role'],
                    'hotel_id' => $s['hotel_id'],
                    'department' => $s['department'],
                    'phone' => $s['phone'],
                    'locale' => $s['locale'],
                    'is_active' => true,
                    'is_suspended' => false,
                ]
            );
            $usersByEmail[$s['email']] = $user;
        }

        // 4. OTA Channels
        $channelsData = [
            [
                'name' => 'Booking.com',
                'code' => 'booking_com',
                'type' => 'ota',
                'adapter_class' => 'App\\Services\\Channel\\Adapters\\BookingComAdapter',
                'api_endpoint' => 'https://distribution-xml.booking.com/2.4/json',
                'settings' => ['timeout' => 10, 'retry_limit' => 3],
                'status' => 'connected',
                'is_active' => true,
            ],
            [
                'name' => 'Agoda',
                'code' => 'agoda',
                'type' => 'ota',
                'adapter_class' => 'App\\Services\\Channel\\Adapters\\AgodaAdapter',
                'api_endpoint' => 'https://api.agoda.com/affiliateservice/v1',
                'settings' => ['timeout' => 10, 'retry_limit' => 3],
                'status' => 'connected',
                'is_active' => true,
            ],
            [
                'name' => 'Expedia Partner Central',
                'code' => 'expedia',
                'type' => 'ota',
                'adapter_class' => 'App\\Services\\Channel\\Adapters\\ExpediaAdapter',
                'api_endpoint' => 'https://services.expediapartnercentral.com/services/v2',
                'settings' => ['timeout' => 10, 'retry_limit' => 3],
                'status' => 'connected',
                'is_active' => true,
            ],
            [
                'name' => 'Rakuten Travel (楽天トラベル)',
                'code' => 'rakuten',
                'type' => 'ota',
                'adapter_class' => 'App\\Services\\Channel\\Adapters\\RakutenAdapter',
                'api_endpoint' => 'https://api.travel.rakuten.co.jp/hotel/v2/inventory',
                'settings' => ['timeout' => 8, 'retry_limit' => 3],
                'status' => 'connected',
                'is_active' => true,
            ],
            [
                'name' => 'Jalan.net (じゃらんnet)',
                'code' => 'jalan',
                'type' => 'ota',
                'adapter_class' => 'App\\Services\\Channel\\Adapters\\JalanAdapter',
                'api_endpoint' => 'https://api.jalan.net/channel/v1/stock',
                'settings' => ['timeout' => 8, 'retry_limit' => 3],
                'status' => 'connected',
                'is_active' => true,
            ],
            [
                'name' => 'ClearStay Direct Booking Web',
                'code' => 'direct_web',
                'type' => 'direct',
                'adapter_class' => 'App\\Services\\Channel\\Adapters\\DirectWebAdapter',
                'api_endpoint' => 'http://localhost:8000/api/direct-web',
                'settings' => ['timeout' => 5, 'retry_limit' => 2],
                'status' => 'connected',
                'is_active' => true,
            ],
            [
                'name' => 'Demo Channel Partner Simulator',
                'code' => 'ota_simulator',
                'type' => 'simulator',
                'adapter_class' => 'App\\Services\\Channel\\Adapters\\MockSimulatorAdapter',
                'api_endpoint' => 'http://localhost:8000/api/mock-ota',
                'settings' => ['timeout' => 5, 'retry_limit' => 3],
                'status' => 'connected',
                'is_active' => true,
            ],
        ];

        $channels = [];
        foreach ($channelsData as $c) {
            $channels[$c['code']] = Channel::updateOrCreate(['code' => $c['code']], $c);
        }

        // 5. Connect Channels for Hotels & Map Rooms
        $hotels = array_filter([$hotelTokyo, $hotelKyoto, $hotelOsaka]);
        foreach ($hotels as $hotel) {
            $roomTypes = RoomType::where('hotel_id', $hotel->id)->get();

            foreach ($channels as $code => $channel) {
                $conn = ChannelConnection::updateOrCreate(
                    ['hotel_id' => $hotel->id, 'channel_id' => $channel->id],
                    [
                        'is_enabled' => true,
                        'credentials' => json_encode([
                            'hotel_channel_code' => strtoupper($channel->code) . '-' . $hotel->code,
                            'api_key' => 'cs_live_' . Str::random(24),
                        ]),
                        'sync_interval_mins' => 15,
                        'last_successful_sync' => now()->subMinutes(rand(5, 45)),
                        'sync_status' => 'ok',
                        'error_count' => 0,
                    ]
                );

                foreach ($roomTypes as $idx => $rt) {
                    ChannelRoomMapping::updateOrCreate(
                        ['channel_connection_id' => $conn->id, 'room_type_id' => $rt->id],
                        [
                            'channel_room_code' => 'CH_RT_' . $rt->code . '_' . ($idx + 1),
                            'channel_rate_plan_code' => 'RATE_STANDARD_FLEX',
                            'rate_multiplier' => $code === 'direct_web' ? 1.00 : 1.05,
                            'is_active' => true,
                        ]
                    );
                }
            }

            // 6. Central Inventory for Next 30 Days
            $today = Carbon::today();
            foreach ($roomTypes as $rt) {
                for ($d = 0; $d < 30; $d++) {
                    $date = $today->copy()->addDays($d);
                    $total = 10;
                    $booked = rand(1, 4);
                    $maintenance = ($d % 10 === 0) ? 1 : 0;
                    $available = max(0, $total - $booked - $maintenance);

                    $rate = $rt->base_price;
                    if ($date->isWeekend()) {
                        $rate = round($rate * 1.25);
                    }

                    CentralInventory::updateOrCreate(
                        ['hotel_id' => $hotel->id, 'room_type_id' => $rt->id, 'date' => $date->toDateString()],
                        [
                            'total_units' => $total,
                            'available_units' => $available,
                            'reserved_units' => $booked,
                            'maintenance_units' => $maintenance,
                            'base_rate' => $rate,
                            'currency' => 'JPY',
                            'min_stay' => $date->isWeekend() ? 2 : 1,
                            'max_stay' => 30,
                            'closed_to_arrival' => false,
                            'closed_to_departure' => false,
                            'stop_sell' => false,
                            'version' => 1,
                        ]
                    );
                }
            }
        }

        // 7. Rate Plans
        $ratePlans = [
            [
                'hotel_id' => $hotelTokyo->id,
                'name' => 'Standard Flexible Rate (Free Cancellation)',
                'code' => 'RATE_STANDARD_FLEX',
                'base_price' => 14000.00,
                'pricing_model' => 'flat',
                'cancellation_policy' => 'free_24h',
                'meal_plan' => 'room_only',
                'is_active' => true,
            ],
            [
                'hotel_id' => $hotelTokyo->id,
                'name' => 'Non-Refundable Early Saver (-15%)',
                'code' => 'RATE_NON_REF_SAVER',
                'base_price' => 11900.00,
                'pricing_model' => 'flat',
                'cancellation_policy' => 'non_refundable',
                'meal_plan' => 'room_only',
                'is_active' => true,
            ],
            [
                'hotel_id' => $hotelTokyo->id,
                'name' => 'Corporate Executive (-10% + Gourmet Breakfast)',
                'code' => 'RATE_CORP_EXEC',
                'base_price' => 15500.00,
                'pricing_model' => 'flat',
                'cancellation_policy' => 'free_same_day',
                'meal_plan' => 'breakfast',
                'is_active' => true,
            ],
            [
                'hotel_id' => $hotelTokyo->id,
                'name' => 'Long Stay Weekly Tariff (-20%)',
                'code' => 'RATE_LONG_STAY_7N',
                'base_price' => 11200.00,
                'pricing_model' => 'flat',
                'cancellation_policy' => 'free_7d',
                'meal_plan' => 'room_only',
                'is_active' => true,
            ],
        ];

        foreach ($ratePlans as $rp) {
            RatePlan::updateOrCreate(['code' => $rp['code']], $rp);
        }

        // 8. Calendar Events (Japanese Holidays & Demand Drivers)
        $events = [
            [
                'title' => 'Golden Week Holidays',
                'title_ja' => 'ゴールデンウィーク',
                'event_type' => 'golden_week',
                'start_date' => '2026-04-29',
                'end_date' => '2026-05-06',
                'demand_multiplier' => 1.45,
                'recommended_min_stay' => 2,
                'notes' => 'Major nationwide consecutive holiday season. Peak domestic travel demand.',
            ],
            [
                'title' => 'Obon Summer Holidays',
                'title_ja' => 'お盆休み',
                'event_type' => 'obon',
                'start_date' => '2026-08-11',
                'end_date' => '2026-08-16',
                'demand_multiplier' => 1.40,
                'recommended_min_stay' => 2,
                'notes' => 'Traditional Japanese mid-August holiday surge. High occupancy expected.',
            ],
            [
                'title' => 'New Year Holiday Season',
                'title_ja' => '年末年始',
                'event_type' => 'new_year',
                'start_date' => '2026-12-29',
                'end_date' => '2027-01-03',
                'demand_multiplier' => 1.50,
                'recommended_min_stay' => 3,
                'notes' => 'Premium holiday travel window. Highest RevPAR period of the year in Ginza.',
            ],
            [
                'title' => 'Tokyo Grand Sumo Tournament',
                'title_ja' => '大相撲五月場所',
                'event_type' => 'festival',
                'start_date' => '2026-05-10',
                'end_date' => '2026-05-24',
                'demand_multiplier' => 1.25,
                'recommended_min_stay' => 1,
                'notes' => 'Ryogoku Kokugikan tournament bringing thousands of sports visitors.',
            ],
            [
                'title' => 'Kyoto Gion Festival',
                'title_ja' => '祇園祭',
                'event_type' => 'festival',
                'start_date' => '2026-07-14',
                'end_date' => '2026-07-17',
                'demand_multiplier' => 1.45,
                'recommended_min_stay' => 2,
                'notes' => 'Iconic cultural festival in Gion district. Premium street procession dates.',
            ],
        ];

        foreach ($events as $ev) {
            CalendarEvent::updateOrCreate(['title' => $ev['title']], $ev);
        }

        // 9. Rate Parity Observations
        $tokyoRooms = RoomType::where('hotel_id', $hotelTokyo->id)->get();
        if ($tokyoRooms->isNotEmpty()) {
            $room1 = $tokyoRooms->first();
            RateParityObservation::create([
                'hotel_id' => $hotelTokyo->id,
                'room_type_id' => $room1->id,
                'channel_id' => $channels['booking_com']->id,
                'date' => Carbon::tomorrow()->toDateString(),
                'expected_rate' => $room1->base_price,
                'observed_rate' => $room1->base_price + 700,
                'difference_amount' => 700,
                'is_discrepancy' => false,
                'observed_at' => now(),
                'details' => ['source' => 'live_crawler', 'currency' => 'JPY'],
            ]);

            RateParityObservation::create([
                'hotel_id' => $hotelTokyo->id,
                'room_type_id' => $room1->id,
                'channel_id' => $channels['expedia']->id,
                'date' => Carbon::tomorrow()->toDateString(),
                'expected_rate' => $room1->base_price,
                'observed_rate' => $room1->base_price - 1200,
                'difference_amount' => -1200,
                'is_discrepancy' => true,
                'observed_at' => now(),
                'details' => ['source' => 'live_crawler', 'currency' => 'JPY', 'alert' => 'OTA price lower than direct rate!'],
            ]);
        }

        // 10. Integration Partners (TEMAIRAZU-style Partner Registry)
        $partnersData = [
            [
                'name' => 'Sakura PMS Connector',
                'company' => 'Sakura Hospitality Technologies Inc.',
                'category' => 'pms',
                'description' => 'Legacy on-premise PMS gateway with XML & JSON bi-directional bridge.',
                'api_base_url' => 'https://api.sakura-hospitality.jp/v2',
                'api_version' => 'v2.4',
                'auth_type' => 'bearer',
                'supported_capabilities' => ['inventory.sync', 'rates.push', 'booking.inbound'],
                'status' => 'certified',
                'contact_email' => 'tech-support@sakura-hospitality.jp',
            ],
            [
                'name' => 'TravelConnect Central Reservation System',
                'company' => 'TravelConnect Global Ltd.',
                'category' => 'crs',
                'description' => 'Global enterprise CRS handling high-volume GDS and wholesaler distribution.',
                'api_base_url' => 'https://gateway.travelconnect-global.com/api/v3',
                'api_version' => 'v3.1',
                'auth_type' => 'oauth2',
                'supported_capabilities' => ['inventory.bulk', 'reservation.two_way', 'ari.delta'],
                'status' => 'certified',
                'contact_email' => 'api@travelconnect-global.com',
            ],
            [
                'name' => 'Horizon Direct Booking Engine',
                'company' => 'Horizon Web Hospitality',
                'category' => 'booking_engine',
                'description' => 'High-conversion direct web booking widget with instant payment authorization.',
                'api_base_url' => 'https://engine.horizon-booking.com/v1',
                'api_version' => 'v1.0',
                'auth_type' => 'api_key',
                'supported_capabilities' => ['availability.realtime', 'reservation.instant'],
                'status' => 'certified',
                'contact_email' => 'integrations@horizon-booking.com',
            ],
            [
                'name' => 'NorthStar Revenue Optimizer RMS',
                'company' => 'NorthStar Analytics Corp.',
                'category' => 'rms',
                'description' => 'AI dynamic yield pricing algorithm pushing optimal rate recommendations.',
                'api_base_url' => 'https://api.northstar-revenue.ai/v2',
                'api_version' => 'v2.0',
                'auth_type' => 'bearer',
                'supported_capabilities' => ['rates.recommend', 'demand.forecast', 'price.bulk_push'],
                'status' => 'testing',
                'contact_email' => 'algorithms@northstar-revenue.ai',
            ],
        ];

        foreach ($partnersData as $pd) {
            $partner = IntegrationPartner::updateOrCreate(['name' => $pd['name']], $pd);

            // Partner Contract
            PartnerContract::updateOrCreate(
                ['partner_id' => $partner->id, 'contract_name' => 'OpenAPI 3.0 Core Specification'],
                [
                    'schema_format' => 'openapi3',
                    'spec_content' => json_encode([
                        'openapi' => '3.0.3',
                        'info' => ['title' => $partner->name, 'version' => '2.4.0'],
                        'paths' => [
                            '/inventory/allotment' => [
                                'post' => [
                                    'summary' => 'Sync room allotment',
                                    'requestBody' => [
                                        'required' => true,
                                        'content' => ['application/json' => ['schema' => ['type' => 'object', 'required' => ['date', 'allotment']]]]
                                    ],
                                    'responses' => ['200' => ['description' => 'Success']]
                                ]
                            ]
                        ]
                    ], JSON_PRETTY_PRINT),
                    'version' => '2.4.0',
                    'is_active' => true,
                    'validation_rules' => ['strict_types' => true, 'require_idempotency_key' => true],
                ]
            );

            // Test Suite & Cases
            $suite = IntegrationTestSuite::updateOrCreate(
                ['partner_id' => $partner->id, 'suite_name' => 'Core Integration & Resilience Suite'],
                [
                    'category' => 'reservations',
                    'description' => 'Automated test suite covering CRUD operations, error recovery, network latency, and idempotency.',
                ]
            );

            $cases = [
                [
                    'case_code' => 'TC-INV-01',
                    'name' => 'Daily Allotment Synchronization Test',
                    'description' => 'Validates real-time inventory adjustment payload transmission.',
                    'request_payload' => ['hotel_code' => 'TYO-GNZ', 'date' => Carbon::tomorrow()->toDateString(), 'allotment' => 8],
                    'expected_status_code' => 200,
                    'expected_assertions' => ['status' => 'acknowledged'],
                    'is_simulation_safe' => true,
                ],
                [
                    'case_code' => 'TC-ERR-500',
                    'name' => 'Upstream 500 Failure Resilience Scenario',
                    'description' => 'Verifies circuit breaker handling and outbox retry backoff on upstream server failure.',
                    'request_payload' => ['hotel_code' => 'TYO-GNZ', 'date' => Carbon::tomorrow()->toDateString(), 'allotment' => 8],
                    'expected_status_code' => 500,
                    'expected_assertions' => ['retry_scheduled' => true],
                    'is_simulation_safe' => true,
                ],
                [
                    'case_code' => 'TC-IDEMP-01',
                    'name' => 'Idempotent Duplicate Message Replay Test',
                    'description' => 'Confirms that replaying the identical transaction id does not double-count inventory.',
                    'request_payload' => ['hotel_code' => 'TYO-GNZ', 'idempotency_key' => 'IDEMP_TEST_123', 'allotment' => 8],
                    'expected_status_code' => 200,
                    'expected_assertions' => ['duplicate_ignored' => true],
                    'is_simulation_safe' => true,
                ],
            ];

            foreach ($cases as $c) {
                IntegrationTestCase::updateOrCreate(
                    ['test_suite_id' => $suite->id, 'case_code' => $c['case_code']],
                    $c
                );
            }

            // Readiness Report
            PartnerReadinessReport::create([
                'partner_id' => $partner->id,
                'report_code' => 'RPT-' . strtoupper(Str::random(10)),
                'evaluated_by' => $usersByEmail['integration.qa@clearstay.jp']->id,
                'overall_status' => 'passed',
                'score_percentage' => rand(92, 98),
                'summary' => "Automated validation complete for {$partner->name}. All critical hotel distribution interfaces passed specification check.",
                'category_breakdown' => [
                    'contract_validity' => 100,
                    'latency_p95' => 95,
                    'retry_resilience' => 90,
                    'idempotency' => 98,
                    'failure_containment' => 95,
                ],
                'outstanding_issues' => [],
                'evidence_data' => ['automated_suite_runs' => 3, 'average_latency_ms' => 184],
            ]);
        }

        // 11. Initial EventTraces & Outbox Sync Logs
        foreach ($channels as $code => $channel) {
            $outbox = SyncOutboxEvent::create([
                'hotel_id' => $hotelTokyo->id,
                'channel_connection_id' => ChannelConnection::where('hotel_id', $hotelTokyo->id)->where('channel_id', $channel->id)->value('id'),
                'correlation_id' => 'CORR-' . strtoupper(Str::random(10)),
                'event_type' => 'inventory_sync',
                'payload' => ['hotel' => 'TYO-GNZ', 'updated_rooms' => 18, 'timestamp' => now()->toIso8601String()],
                'status' => 'confirmed',
                'attempts' => 1,
                'scheduled_at' => now()->subMinutes(10),
                'executed_at' => now()->subMinutes(9),
            ]);

            SyncAttemptLog::create([
                'sync_outbox_event_id' => $outbox->id,
                'attempt_number' => 1,
                'http_status' => 200,
                'request_payload' => ['hotel' => 'TYO-GNZ'],
                'response_payload' => ['status' => 'success'],
                'execution_time_ms' => rand(120, 380),
                'error_message' => null,
            ]);

            EventTrace::create([
                'trace_id' => 'TRC-' . strtoupper(Str::random(12)),
                'hotel_id' => $hotelTokyo->id,
                'operation' => 'inventory_push',
                'stage' => 'completed',
                'status' => 'success',
                'duration_ms' => rand(120, 380),
                'request_headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ***'],
                'request_body' => ['channel' => $channel->name, 'hotel_code' => 'TYO-GNZ'],
                'response_headers' => ['Content-Type' => 'application/json'],
                'response_body' => ['acknowledged' => true, 'timestamp' => now()->toIso8601String()],
                'replayable' => true,
            ]);
        }

        // 12. Security Governance Recommendations & Device Trust
        $managerUser = $usersByEmail['manager.tokyo@clearstay.jp'];

        TrustedDevice::updateOrCreate(
            ['user_id' => $managerUser->id, 'device_identifier' => hash('sha256', 'OfficeManagerMacBookPro2026')],
            [
                'device_name' => 'Manager Workstation (MacBook Pro M3 - Hotel GM Office)',
                'browser' => 'Chrome 130.0',
                'os' => 'macOS 15 Sequoia',
                'ip_address' => '192.168.1.50',
                'trust_status' => 'trusted',
                'first_seen_at' => now()->subDays(60),
                'last_seen_at' => now(),
            ]
        );

        AiGovernanceRecommendation::create([
            'hotel_id' => $hotelTokyo->id,
            'user_id' => $usersByEmail['frontdesk.tokyo@clearstay.jp']->id,
            'category' => 'excessive_permissions',
            'severity' => 'info',
            'title' => 'Excessive Bulk Override Capability Detected',
            'recommendation_text' => 'Front desk staff account has infrequently executed bulk inventory overrides over the past 45 days. In accordance with IEEE 802 least-privilege principles, consider restricting bulk operations to property management roles.',
            'status' => 'pending_review',
            'metadata' => [
                'suggested_actions' => [
                    'Revoke explicit permission `inventory.bulk_edit` from standard front desk profiles.',
                    'Enforce manager step-up authorization for rate adjustments exceeding ¥10,000.',
                ],
                'confidence_score' => 0.94,
            ],
        ]);

        SecurityRiskEvent::create([
            'hotel_id' => $hotelTokyo->id,
            'user_id' => $usersByEmail['cleaner.tokyo@clearstay.jp']->id,
            'event_type' => 'off_hours_access',
            'risk_score' => 25,
            'risk_band' => 'low',
            'contributing_factors' => ['hour' => 23, 'reason' => 'Housekeeping staff logged in outside typical duty shift window.'],
            'action_taken' => 'allow',
            'is_reviewed' => false,
            'investigation_notes' => 'Routine night inspection verification.',
        ]);

        AuthorizationDecision::create([
            'hotel_id' => $hotelTokyo->id,
            'user_id' => $managerUser->id,
            'action' => 'channels.sync_all',
            'resource_type' => 'channel',
            'resource_id' => 'all',
            'decision' => 'ALLOW',
            'explanation' => 'User possesses manager role and active trusted device certificate.',
            'evaluated_context' => ['risk_score' => 5, 'ip' => '192.168.1.50'],
        ]);
    }
}
