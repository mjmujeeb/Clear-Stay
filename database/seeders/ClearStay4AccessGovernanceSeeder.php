<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Permission;
use App\Models\PermissionRequest;
use App\Models\PrivacyAccessLog;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClearStay4AccessGovernanceSeeder extends Seeder
{
    public function run(): void
    {
        $hotelTokyo = Hotel::where('slug', 'clearstay-premier-tokyo-ginza')->first() ?? Hotel::first();

        // 1. Ensure Standard Granular Permissions Exist (resource.action.scope format)
        $permissions = [
            // Core Operational Permissions
            ['name' => 'reservation.view.property', 'category' => 'reservations', 'description' => 'View reservations scoped to assigned property.', 'description_ja' => '所属ホテルの予約閲覧'],
            ['name' => 'reservation.create.property', 'category' => 'reservations', 'description' => 'Create reservations for property guests.', 'description_ja' => '宿泊予約の新規作成'],
            ['name' => 'reservation.modify.property', 'category' => 'reservations', 'description' => 'Modify stay dates and room assignment.', 'description_ja' => '宿泊日程および客室の変更'],
            ['name' => 'guest.view_sensitive.property', 'category' => 'privacy', 'description' => 'View masked guest profile data.', 'description_ja' => 'マスキング済み顧客情報の閲覧'],
            ['name' => 'guest.unmask_pii.property', 'category' => 'privacy', 'description' => 'Unmask guest phone, email, and payment tokens with audit justification.', 'description_ja' => '顧客個人情報（電話・メール）の正当理由付き閲覧解除'],
            ['name' => 'rates.modify.property', 'category' => 'rates', 'description' => 'Standard rate plan updates and weekend pricing.', 'description_ja' => '通常料金プランおよび週末価格の設定'],
            ['name' => 'financial.rate_override', 'category' => 'rates', 'description' => 'High-impact rate override exceeding manager margin limits (Requires Two-Person Approval).', 'description_ja' => '限度額超過の緊急料金オーバーライド（要2名承認）'],
            ['name' => 'housekeeping.queue.mobile', 'category' => 'housekeeping', 'description' => 'Mobile cleaning task queue inspection and status toggle.', 'description_ja' => '清掃タスク一覧の確認およびステータス更新'],
            ['name' => 'security.elevation.approve', 'category' => 'security', 'description' => 'Two-person approval authority for high-risk access elevation.', 'description_ja' => '高リスク権限昇格の2名承認権限'],
            ['name' => 'security.audit.immutable_read', 'category' => 'security', 'description' => 'Read-only access to immutable security audit trails and telemetry.', 'description_ja' => '改ざん防止セキュリティ監査ログの閲覧'],
            ['name' => 'qa.sandbox.execute', 'category' => 'qa', 'description' => 'Run Experience Lab responsive audits and test suites on synthetic data.', 'description_ja' => '体験ラボのレスポンシブ検証およびテスト実行'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p['name']], $p);
        }

        // 2. Six-Role Demonstration Accounts
        $accounts = [
            [
                'email' => 'manager@clearstay.test',
                'name' => 'Kenji Sato (Hotel Manager)',
                'role' => 'manager',
                'department' => 'Hotel Operations',
                'hotel_id' => $hotelTokyo->id,
            ],
            [
                'email' => 'receptionist@clearstay.test',
                'name' => 'Aiko Tanaka (Front Desk Lead)',
                'role' => 'frontdesk',
                'department' => 'Front Office',
                'hotel_id' => $hotelTokyo->id,
            ],
            [
                'email' => 'housekeeping@clearstay.test',
                'name' => 'Haruto Suzuki (Housekeeping Lead)',
                'role' => 'housekeeping',
                'department' => 'Environmental Care',
                'hotel_id' => $hotelTokyo->id,
            ],
            [
                'email' => 'admin@clearstay.test',
                'name' => 'ClearStay Platform Administrator',
                'role' => 'superadmin',
                'department' => 'Platform Engineering',
                'hotel_id' => null,
            ],
            [
                'email' => 'secadmin@clearstay.test',
                'name' => 'Security Administrator (Policy & Elevation)',
                'role' => 'security_admin',
                'department' => 'Information Security',
                'hotel_id' => $hotelTokyo->id,
            ],
            [
                'email' => 'auditor@clearstay.test',
                'name' => 'Daisuke Mori (Security Auditor - Read Only)',
                'role' => 'auditor',
                'department' => 'Independent Trust & Audit',
                'hotel_id' => $hotelTokyo->id,
            ],
            [
                'email' => 'qa@clearstay.test',
                'name' => 'Alex Mercer (QA & Usability Tester)',
                'role' => 'qa',
                'department' => 'Quality Assurance & Experience Lab',
                'hotel_id' => $hotelTokyo->id,
            ],
        ];

        $createdUsers = [];
        foreach ($accounts as $acc) {
            $user = User::updateOrCreate(
                ['email' => $acc['email']],
                [
                    'name' => $acc['name'],
                    'password' => Hash::make('password'),
                    'role' => $acc['role'],
                    'department' => $acc['department'],
                    'hotel_id' => $acc['hotel_id'],
                    'locale' => 'ja',
                    'is_active' => true,
                    'contrast_theme' => 'standard',
                    'font_scale' => 100,
                    'reduced_motion' => false,
                ]
            );
            $createdUsers[$acc['role']] = $user;
        }

        // Also ensure secadmin@clearstay.jp exists for consistency
        User::firstOrCreate(
            ['email' => 'secadmin@clearstay.jp'],
            [
                'name' => 'Security Administrator (Policy & Elevation)',
                'password' => Hash::make('password'),
                'role' => 'security_admin',
                'department' => 'Information Security',
                'hotel_id' => $hotelTokyo->id,
                'locale' => 'ja',
                'is_active' => true,
            ]
        );

        // 3. Seed an active pending two-person elevation request
        $overridePerm = Permission::where('name', 'financial.rate_override')->first();
        if ($overridePerm && isset($createdUsers['manager'])) {
            PermissionRequest::firstOrCreate(
                [
                    'user_id' => $createdUsers['manager']->id,
                    'permission_id' => $overridePerm->id,
                    'status' => 'pending',
                ],
                [
                    'hotel_id' => $hotelTokyo->id,
                    'requested_by' => $createdUsers['manager']->id,
                    'risk_level' => 'high',
                    'justification' => 'Emergency rate discount of 40% for Golden Week group booking requiring immediate PMS override.',
                    'duration_hours' => 24,
                    'expires_at' => now()->addHours(24),
                ]
            );
        }

        // 4. Seed initial Privacy Access Logs (Demonstrating DPDP Act 2023 compliance)
        if (isset($createdUsers['frontdesk'])) {
            PrivacyAccessLog::firstOrCreate(
                [
                    'hotel_id' => $hotelTokyo->id,
                    'user_id' => $createdUsers['frontdesk']->id,
                    'action' => 'unmask',
                    'field_accessed' => 'phone_and_email',
                ],
                [
                    'justification' => 'Guest check-in identity verification at front desk per Japanese Ryokan Law / DPDP compliance.',
                    'ip_address' => '192.168.1.105',
                    'user_agent' => 'ClearStay Terminal Ginza-01',
                    'correlation_id' => 'PRV-'.Str::random(12),
                    'created_at' => now()->subHours(2),
                ]
            );
        }
    }
}
