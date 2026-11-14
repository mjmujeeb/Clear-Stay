<?php

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\Hotel;
use App\Models\HotelPricingSetting;
use App\Models\PricingRule;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class JapaneseHolidayAndPricingSeeder extends Seeder
{
    public function run(): void
    {
        $hotels = Hotel::all();

        foreach ($hotels as $hotel) {
            // 1. Hotel pricing settings
            HotelPricingSetting::firstOrCreate(
                ['hotel_id' => $hotel->id],
                [
                    'stacking_policy' => 'additive',
                    'weekend_days' => [5, 6], // Friday and Saturday
                    'weekend_increase_percentage' => 15.00,
                    'rounding_policy' => 'whole_yen',
                    'currency' => 'JPY',
                    'tax_percentage' => 10.00,
                    'tax_included' => true,
                ]
            );

            // 2. Verified Japanese National Holidays & Major Periods (2025, 2026, 2027)
            $holidays = [
                // 2025
                ['title' => "New Year's Day 2025", 'title_ja' => '元日', 'type' => 'holiday', 'start' => '2025-01-01', 'end' => '2025-01-01', 'pct' => 30.0],
                ['title' => 'Coming of Age Day 2025', 'title_ja' => '成人の日', 'type' => 'holiday', 'start' => '2025-01-13', 'end' => '2025-01-13', 'pct' => 20.0],
                ['title' => 'National Foundation Day 2025', 'title_ja' => '建国記念の日', 'type' => 'holiday', 'start' => '2025-02-11', 'end' => '2025-02-11', 'pct' => 20.0],
                ['title' => "Emperor's Birthday 2025", 'title_ja' => '天皇誕生日', 'type' => 'holiday', 'start' => '2025-02-23', 'end' => '2025-02-24', 'pct' => 25.0],
                ['title' => 'Vernal Equinox Day 2025', 'title_ja' => '春分の日', 'type' => 'holiday', 'start' => '2025-03-20', 'end' => '2025-03-20', 'pct' => 20.0],
                ['title' => 'Golden Week 2025', 'title_ja' => 'ゴールデンウィーク2025', 'type' => 'holiday', 'start' => '2025-04-29', 'end' => '2025-05-06', 'pct' => 35.0],
                ['title' => 'Marine Day 2025', 'title_ja' => '海の日', 'type' => 'holiday', 'start' => '2025-07-21', 'end' => '2025-07-21', 'pct' => 20.0],
                ['title' => 'Mountain Day 2025', 'title_ja' => '山の日', 'type' => 'holiday', 'start' => '2025-08-11', 'end' => '2025-08-11', 'pct' => 25.0],
                ['title' => 'Obon Festival 2025', 'title_ja' => 'お盆特別期間2025', 'type' => 'event', 'start' => '2025-08-12', 'end' => '2025-08-16', 'pct' => 40.0],
                ['title' => 'Respect for the Aged Day 2025', 'title_ja' => '敬老の日', 'type' => 'holiday', 'start' => '2025-09-15', 'end' => '2025-09-15', 'pct' => 20.0],
                ['title' => 'Autumnal Equinox Day 2025', 'title_ja' => '秋分の日', 'type' => 'holiday', 'start' => '2025-09-23', 'end' => '2025-09-23', 'pct' => 20.0],
                ['title' => 'Sports Day 2025', 'title_ja' => 'スポーツの日', 'type' => 'holiday', 'start' => '2025-10-13', 'end' => '2025-10-13', 'pct' => 20.0],
                ['title' => 'Culture Day 2025', 'title_ja' => '文化の日', 'type' => 'holiday', 'start' => '2025-11-03', 'end' => '2025-11-03', 'pct' => 20.0],
                ['title' => 'Labor Thanksgiving Day 2025', 'title_ja' => '勤労感謝の日', 'type' => 'holiday', 'start' => '2025-11-23', 'end' => '2025-11-24', 'pct' => 20.0],
                ['title' => 'Year-End Peak 2025-2026', 'title_ja' => '年末年始ピーク期間', 'type' => 'holiday', 'start' => '2025-12-28', 'end' => '2026-01-04', 'pct' => 45.0],

                // 2026
                ['title' => "New Year's Day 2026", 'title_ja' => '元日', 'type' => 'holiday', 'start' => '2026-01-01', 'end' => '2026-01-01', 'pct' => 30.0],
                ['title' => 'Coming of Age Day 2026', 'title_ja' => '成人の日', 'type' => 'holiday', 'start' => '2026-01-12', 'end' => '2026-01-12', 'pct' => 20.0],
                ['title' => 'National Foundation Day 2026', 'title_ja' => '建国記念の日', 'type' => 'holiday', 'start' => '2026-02-11', 'end' => '2026-02-11', 'pct' => 20.0],
                ['title' => "Emperor's Birthday 2026", 'title_ja' => '天皇誕生日', 'type' => 'holiday', 'start' => '2026-02-23', 'end' => '2026-02-23', 'pct' => 25.0],
                ['title' => 'Vernal Equinox Day 2026', 'title_ja' => '春分の日', 'type' => 'holiday', 'start' => '2026-03-20', 'end' => '2026-03-20', 'pct' => 20.0],
                ['title' => 'Golden Week 2026', 'title_ja' => 'ゴールデンウィーク2026', 'type' => 'holiday', 'start' => '2026-04-29', 'end' => '2026-05-06', 'pct' => 35.0],
                ['title' => 'Marine Day 2026', 'title_ja' => '海の日', 'type' => 'holiday', 'start' => '2026-07-20', 'end' => '2026-07-20', 'pct' => 20.0],
                ['title' => 'Mountain Day 2026', 'title_ja' => '山の日', 'type' => 'holiday', 'start' => '2026-08-11', 'end' => '2026-08-11', 'pct' => 25.0],
                ['title' => 'Obon Festival 2026', 'title_ja' => 'お盆特別期間2026', 'type' => 'event', 'start' => '2026-08-12', 'end' => '2026-08-16', 'pct' => 40.0],
                ['title' => 'Respect for the Aged Day 2026', 'title_ja' => '敬老の日', 'type' => 'holiday', 'start' => '2026-09-21', 'end' => '2026-09-21', 'pct' => 20.0],
                ['title' => 'Autumnal Equinox Day 2026', 'title_ja' => '秋分の日', 'type' => 'holiday', 'start' => '2026-09-23', 'end' => '2026-09-23', 'pct' => 20.0],
                ['title' => 'Sports Day 2026', 'title_ja' => 'スポーツの日', 'type' => 'holiday', 'start' => '2026-10-12', 'end' => '2026-10-12', 'pct' => 20.0],
                ['title' => 'Culture Day 2026', 'title_ja' => '文化の日', 'type' => 'holiday', 'start' => '2026-11-03', 'end' => '2026-11-03', 'pct' => 20.0],
                ['title' => 'Labor Thanksgiving Day 2026', 'title_ja' => '勤労感謝の日', 'type' => 'holiday', 'start' => '2026-11-23', 'end' => '2026-11-23', 'pct' => 20.0],
                ['title' => 'Year-End Peak 2026-2027', 'title_ja' => '年末年始ピーク期間', 'type' => 'holiday', 'start' => '2026-12-28', 'end' => '2027-01-04', 'pct' => 45.0],

                // 2027
                ['title' => "New Year's Day 2027", 'title_ja' => '元日', 'type' => 'holiday', 'start' => '2027-01-01', 'end' => '2027-01-01', 'pct' => 30.0],
                ['title' => 'Coming of Age Day 2027', 'title_ja' => '成人の日', 'type' => 'holiday', 'start' => '2027-01-11', 'end' => '2027-01-11', 'pct' => 20.0],
                ['title' => 'National Foundation Day 2027', 'title_ja' => '建国記念の日', 'type' => 'holiday', 'start' => '2027-02-11', 'end' => '2027-02-11', 'pct' => 20.0],
                ['title' => "Emperor's Birthday 2027", 'title_ja' => '天皇誕生日', 'type' => 'holiday', 'start' => '2027-02-23', 'end' => '2027-02-23', 'pct' => 25.0],
                ['title' => 'Vernal Equinox Day 2027', 'title_ja' => '春分の日', 'type' => 'holiday', 'start' => '2027-03-21', 'end' => '2027-03-22', 'pct' => 20.0],
                ['title' => 'Golden Week 2027', 'title_ja' => 'ゴールデンウィーク2027', 'type' => 'holiday', 'start' => '2027-04-29', 'end' => '2027-05-06', 'pct' => 35.0],
            ];

            foreach ($holidays as $h) {
                CalendarEvent::updateOrCreate(
                    [
                        'hotel_id' => $hotel->id,
                        'title' => $h['title'],
                        'start_date' => $h['start'],
                    ],
                    [
                        'title_ja' => $h['title_ja'],
                        'event_type' => $h['type'],
                        'end_date' => $h['end'],
                        'demand_multiplier' => round(1.0 + ($h['pct'] / 100), 2),
                        'percentage_adjustment' => $h['pct'],
                        'fixed_surcharge' => 0.00,
                        'is_active' => true,
                        'recommended_min_stay' => $h['type'] === 'event' ? 2 : 1,
                        'notes' => 'Japanese official calendar event & holiday',
                    ]
                );
            }

            // 3. Manager Seasonal Pricing Rules
            $seasonalRules = [
                [
                    'rule_name' => 'Sakura Peak Season 2026',
                    'rule_name_ja' => '桜開花・春季ピーク 2026',
                    'rule_type' => 'seasonal',
                    'percentage_adjustment' => 25.0,
                    'fixed_adjustment' => 0.0,
                    'start_date' => '2026-03-25',
                    'end_date' => '2026-04-15',
                    'priority' => 10,
                    'description' => 'Spring cherry blossom high demand season',
                ],
                [
                    'rule_name' => 'Summer Holiday Season 2026',
                    'rule_name_ja' => '夏休みバケーション 2026',
                    'rule_type' => 'seasonal',
                    'percentage_adjustment' => 18.0,
                    'fixed_adjustment' => 0.0,
                    'start_date' => '2026-07-15',
                    'end_date' => '2026-08-31',
                    'priority' => 5,
                    'description' => 'Summer vacation period rate adjustment',
                ],
                [
                    'rule_name' => 'Autumn Foliage Peak 2026',
                    'rule_name_ja' => '紅葉観光ピーク 2026',
                    'rule_type' => 'seasonal',
                    'percentage_adjustment' => 20.0,
                    'fixed_adjustment' => 0.0,
                    'start_date' => '2026-10-20',
                    'end_date' => '2026-11-25',
                    'priority' => 8,
                    'description' => 'Fall autumn foliage travel surge',
                ],
            ];

            foreach ($seasonalRules as $sr) {
                PricingRule::updateOrCreate(
                    [
                        'hotel_id' => $hotel->id,
                        'rule_name' => $sr['rule_name'],
                        'start_date' => $sr['start_date'],
                    ],
                    array_merge($sr, [
                        'hotel_id' => $hotel->id,
                        'is_active' => true,
                    ])
                );
            }
        }
    }
}
