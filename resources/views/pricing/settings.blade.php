@extends('layouts.app')

@section('title', 'Hotel Pricing Rules & Calendar Management - ClearStay 5.0')
@section('header_title', app()->getLocale() === 'ja' ? 'ホテル料金ルール・カレンダー管理' : 'Hotel Pricing Rules & Calendar Management')
@section('header_subtitle', app()->getLocale() === 'ja' ? '客室基本料金、週末割増、祝日・特別イベント、季節料金および計算スタッキング設定' : 'Dynamic base room rates, weekend surcharges, Japanese holiday calendar, and stacking calculation engine.')

@section('content')
<div class="pricing-settings-container" style="max-width: 1400px; margin: 0 auto; padding-bottom: 60px;">

    <!-- Alert Notifications -->
    @if(session('success'))
        <div style="background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 14px 18px; border-radius: 10px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; font-weight: 600;">
            <i class="fa-solid fa-circle-check" style="color: #10B981; font-size: 18px;"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 14px 18px; border-radius: 10px; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 10px; font-weight: 700; margin-bottom: 6px;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ app()->getLocale() === 'ja' ? '入力エラーが発生しました' : 'Please correct the errors below:' }}</span>
            </div>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Top KPI Status Bar -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 28px;">
        <div class="card" style="margin: 0; padding: 18px; background: white; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">
                {{ app()->getLocale() === 'ja' ? 'スタッキング計算方式' : 'Active Stacking Policy' }}
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #1E293B; margin-top: 6px; display: flex; align-items: center; gap: 8px;">
                <span class="badge" style="background: #EEF2FF; color: #4338CA; font-size: 14px; padding: 4px 10px; border-radius: 6px;">
                    {{ strtoupper($settings->stacking_policy) }}
                </span>
            </div>
            <div style="font-size: 11px; color: #94A3B8; margin-top: 4px;">
                {{ $settings->stacking_policy === 'additive' ? 'Base * (1 + sum%) + fixed' : ($settings->stacking_policy === 'multiplicative' ? 'Base * prod(1+%) + fixed' : 'Base * (1 + max%) + fixed') }}
            </div>
        </div>

        <div class="card" style="margin: 0; padding: 18px; background: white; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">
                {{ app()->getLocale() === 'ja' ? '週末割増ルール' : 'Weekend Surcharge' }}
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #D97706; margin-top: 6px;">
                +{{ $settings->weekend_increase_percentage }}%
            </div>
            <div style="font-size: 11px; color: #94A3B8; margin-top: 4px;">
                @php
                    $dayLabels = [0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];
                    $activeDays = array_map(fn($d) => $dayLabels[$d] ?? $d, $settings->weekend_days ?? [5, 6]);
                @endphp
                {{ implode(' & ', $activeDays) }}
            </div>
        </div>

        <div class="card" style="margin: 0; padding: 18px; background: white; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">
                {{ app()->getLocale() === 'ja' ? '登録祝日・イベント' : 'Japanese Holidays & Events' }}
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #059669; margin-top: 6px;">
                {{ $events->count() }} <span style="font-size: 14px; font-weight: 600; color: #64748B;">{{ app()->getLocale() === 'ja' ? '件登録中' : 'Events (2025-2027)' }}</span>
            </div>
            <div style="font-size: 11px; color: #94A3B8; margin-top: 4px;">
                {{ app()->getLocale() === 'ja' ? 'GW・お盆・年末年始含む' : 'Includes Golden Week, Obon, New Year' }}
            </div>
        </div>

        <div class="card" style="margin: 0; padding: 18px; background: white; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">
                {{ app()->getLocale() === 'ja' ? '季節・特別ルール' : 'Seasonal Rules' }}
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #2563EB; margin-top: 6px;">
                {{ $rules->count() }} <span style="font-size: 14px; font-weight: 600; color: #64748B;">{{ app()->getLocale() === 'ja' ? 'アクティブ' : 'Active Rules' }}</span>
            </div>
            <div style="font-size: 11px; color: #94A3B8; margin-top: 4px;">
                {{ app()->getLocale() === 'ja' ? '桜・夏休み・紅葉シーズン等' : 'Sakura, Summer Vacation, Foliage' }}
            </div>
        </div>
    </div>

    <!-- Section A & B: Base Rates and Weekend Rules Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px;">
        <!-- Section A: Base Room Rates -->
        <div class="card" style="margin: 0; background: white; border-radius: 14px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
            <div style="padding: 18px 22px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #F8FAFC;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-bed" style="color: #0284C7; font-size: 18px;"></i>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">
                        {{ app()->getLocale() === 'ja' ? '客室タイプ別 基本料金設定' : 'Base Room Rates by Type' }}
                    </h3>
                </div>
                <span class="badge" style="background: #E0F2FE; color: #0369A1; font-weight: 700;">{{ $settings->currency }}</span>
            </div>
            <div style="padding: 16px 22px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="border-bottom: 2px solid #E2E8F0; text-align: left; color: #64748B;">
                            <th style="padding: 10px 6px;">{{ app()->getLocale() === 'ja' ? '客室タイプ' : 'Room Type' }}</th>
                            <th style="padding: 10px 6px;">{{ app()->getLocale() === 'ja' ? '定員' : 'Capacity' }}</th>
                            <th style="padding: 10px 6px;">{{ app()->getLocale() === 'ja' ? '現行基本料金' : 'Base Price' }}</th>
                            <th style="padding: 10px 6px; text-align: right;">{{ app()->getLocale() === 'ja' ? '更新' : 'Action' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roomTypes as $rt)
                        <tr style="border-bottom: 1px solid #F1F5F9;">
                            <td style="padding: 12px 6px;">
                                <strong style="color: #1E293B;">{{ $rt->name }}</strong>
                                @if($rt->name_ja)
                                    <div style="font-size: 11px; color: #64748B;">{{ $rt->name_ja }}</div>
                                @endif
                            </td>
                            <td style="padding: 12px 6px; color: #475569;">
                                {{ $rt->capacity }} {{ app()->getLocale() === 'ja' ? '名' : 'Guests' }}
                            </td>
                            <td style="padding: 12px 6px;">
                                <span style="font-size: 15px; font-weight: 800; color: #0F172A;">
                                    ¥{{ number_format($rt->base_price) }}
                                </span>
                            </td>
                            <td style="padding: 12px 6px; text-align: right;">
                                <form action="{{ route('pricing_settings.update_base_price') }}" method="POST" style="display: inline-flex; align-items: center; gap: 6px;">
                                    @csrf
                                    <input type="hidden" name="room_type_id" value="{{ $rt->id }}">
                                    <input type="number" name="base_price" value="{{ (int)$rt->base_price }}" step="100" min="0" style="width: 100px; padding: 6px 8px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 12px; font-weight: 700;">
                                    <button type="submit" class="btn" style="padding: 6px 12px; font-size: 12px; background: #0F172A; color: white; border-radius: 6px; font-weight: 700;">
                                        {{ app()->getLocale() === 'ja' ? '保存' : 'Save' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section B: Configurable Weekend Pricing -->
        <div class="card" style="margin: 0; background: white; border-radius: 14px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
            <div style="padding: 18px 22px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #F8FAFC;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-calendar-week" style="color: #D97706; font-size: 18px;"></i>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">
                        {{ app()->getLocale() === 'ja' ? '週末料金・割増ルール設定' : 'Weekend Surcharge Rules' }}
                    </h3>
                </div>
                <span class="badge" style="background: #FEF3C7; color: #92400E; font-weight: 700;">+{{ $settings->weekend_increase_percentage }}%</span>
            </div>
            <div style="padding: 20px 22px;">
                <form action="{{ route('pricing_settings.update_weekend_rules') }}" method="POST">
                    @csrf
                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px;">
                            {{ app()->getLocale() === 'ja' ? '週末と判定する曜日を選択' : 'Select Days Designated as Weekend' }}
                        </label>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            @php
                                $days = [
                                    0 => ['en' => 'Sun', 'ja' => '日'],
                                    1 => ['en' => 'Mon', 'ja' => '月'],
                                    2 => ['en' => 'Tue', 'ja' => '火'],
                                    3 => ['en' => 'Wed', 'ja' => '水'],
                                    4 => ['en' => 'Thu', 'ja' => '木'],
                                    5 => ['en' => 'Fri', 'ja' => '金'],
                                    6 => ['en' => 'Sat', 'ja' => '土'],
                                ];
                                $currentWeekendDays = $settings->weekend_days ?? [5, 6];
                            @endphp
                            @foreach($days as $num => $d)
                                <label style="display: flex; align-items: center; gap: 6px; padding: 8px 12px; background: #F1F5F9; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600;">
                                    <input type="checkbox" name="weekend_days[]" value="{{ $num }}" {{ in_array($num, $currentWeekendDays) ? 'checked' : '' }}>
                                    <span>{{ app()->getLocale() === 'ja' ? $d['ja'].'曜日' : $d['en'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                                {{ app()->getLocale() === 'ja' ? '週末割増率 (%)' : 'Weekend Surcharge Percentage (%)' }}
                            </label>
                            <div style="position: relative;">
                                <input type="number" name="weekend_increase_percentage" value="{{ $settings->weekend_increase_percentage }}" step="0.5" min="0" max="100" required style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-weight: 700;">
                                <span style="position: absolute; right: 12px; top: 8px; color: #94A3B8; font-weight: 700;">%</span>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                                {{ app()->getLocale() === 'ja' ? '変更理由（監査記録用）' : 'Audit Reason' }}
                            </label>
                            <input type="text" name="reason" placeholder="{{ app()->getLocale() === 'ja' ? '例: 繁忙期週末の料金調整' : 'e.g. Seasonal weekend rate revision' }}" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px;">
                        </div>
                    </div>

                    <button type="submit" class="btn" style="width: 100%; background: #D97706; color: white; padding: 10px; border-radius: 8px; font-weight: 700; border: none; cursor: pointer;">
                        <i class="fa-solid fa-floppy-disk"></i> {{ app()->getLocale() === 'ja' ? '週末料金設定を保存' : 'Save Weekend Rules' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Section C: Japanese Holidays & Special Events Calendar -->
    <div class="card" style="margin-bottom: 28px; background: white; border-radius: 14px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
        <div style="padding: 18px 22px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #F8FAFC;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-flag-checkered" style="color: #DC2626; font-size: 18px;"></i>
                <div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">
                        {{ app()->getLocale() === 'ja' ? '日本の祝日カレンダー・特別イベント' : 'Japanese National Holidays & Special Events' }}
                    </h3>
                    <p style="font-size: 12px; color: #64748B; margin: 2px 0 0;">
                        {{ app()->getLocale() === 'ja' ? '2025年〜2027年の公式国民の祝日およびゴールデンウィーク・お盆・年末年始の需要割増' : 'Official statutory public holidays and major high-demand periods (GW, Obon, New Year).' }}
                    </p>
                </div>
            </div>
            <button onclick="document.getElementById('addEventModal').style.display='block'" class="btn" style="background: #0F172A; color: white; border-radius: 8px; padding: 8px 14px; font-size: 13px; font-weight: 700;">
                <i class="fa-solid fa-plus"></i> {{ app()->getLocale() === 'ja' ? 'イベント追加' : 'Add Event' }}
            </button>
        </div>
        <div style="padding: 16px 22px; max-height: 420px; overflow-y: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 2px solid #E2E8F0; text-align: left; color: #64748B;">
                        <th style="padding: 10px 8px;">{{ app()->getLocale() === 'ja' ? '名称' : 'Event Title' }}</th>
                        <th style="padding: 10px 8px;">{{ app()->getLocale() === 'ja' ? '区分' : 'Type' }}</th>
                        <th style="padding: 10px 8px;">{{ app()->getLocale() === 'ja' ? '期間' : 'Date Range' }}</th>
                        <th style="padding: 10px 8px;">{{ app()->getLocale() === 'ja' ? '割増率 / 追加料金' : 'Adjustment' }}</th>
                        <th style="padding: 10px 8px;">{{ app()->getLocale() === 'ja' ? '状態' : 'Status' }}</th>
                        <th style="padding: 10px 8px; text-align: right;">{{ app()->getLocale() === 'ja' ? '操作' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($events as $ev)
                    <tr style="border-bottom: 1px solid #F1F5F9;">
                        <td style="padding: 12px 8px;">
                            <strong style="color: #1E293B;">{{ $ev->title }}</strong>
                            @if($ev->title_ja)
                                <span style="margin-left: 6px; font-size: 12px; color: #64748B; background: #F1F5F9; padding: 2px 6px; border-radius: 4px;">{{ $ev->title_ja }}</span>
                            @endif
                        </td>
                        <td style="padding: 12px 8px;">
                            <span class="badge" style="background: {{ $ev->event_type === 'holiday' ? '#FEE2E2' : '#EDE9FE' }}; color: {{ $ev->event_type === 'holiday' ? '#991B1B' : '#5B21B6' }};">
                                {{ ucfirst($ev->event_type) }}
                            </span>
                        </td>
                        <td style="padding: 12px 8px; font-family: monospace; color: #334155;">
                            {{ $ev->start_date->format('Y-m-d') }}
                            @if($ev->start_date != $ev->end_date)
                                ~ {{ $ev->end_date->format('Y-m-d') }}
                            @endif
                        </td>
                        <td style="padding: 12px 8px;">
                            <span style="font-weight: 800; color: #DC2626;">
                                +{{ $ev->percentage_adjustment }}%
                            </span>
                            @if($ev->fixed_surcharge > 0)
                                <span style="font-size: 11px; color: #64748B; margin-left: 4px;">
                                    (+¥{{ number_format($ev->fixed_surcharge) }})
                                </span>
                            @endif
                        </td>
                        <td style="padding: 12px 8px;">
                            @if($ev->is_active)
                                <span class="badge" style="background: #D1FAE5; color: #065F46;">{{ app()->getLocale() === 'ja' ? '適用中' : 'Active' }}</span>
                            @else
                                <span class="badge" style="background: #F1F5F9; color: #94A3B8;">{{ app()->getLocale() === 'ja' ? '無効' : 'Disabled' }}</span>
                            @endif
                        </td>
                        <td style="padding: 12px 8px; text-align: right;">
                            <form action="{{ route('pricing_settings.delete_event', $ev->id) }}" method="POST" onsubmit="return confirm('Delete this calendar event?');" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn" style="padding: 4px 8px; font-size: 12px; background: transparent; color: #EF4444; border: 1px solid #FECACA; border-radius: 6px;">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section D: Seasonal Pricing Rules -->
    <div class="card" style="margin-bottom: 28px; background: white; border-radius: 14px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
        <div style="padding: 18px 22px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #F8FAFC;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-chart-line" style="color: #2563EB; font-size: 18px;"></i>
                <div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">
                        {{ app()->getLocale() === 'ja' ? '季節料金・特別期間ルール' : 'Seasonal & Custom Pricing Rules' }}
                    </h3>
                    <p style="font-size: 12px; color: #64748B; margin: 2px 0 0;">
                        {{ app()->getLocale() === 'ja' ? '桜開花・夏休み・スキーシーズンなどの長期間料金調整' : 'Multi-week demand adjustments, seasonal promotions, and room-specific rate rules.' }}
                    </p>
                </div>
            </div>
            <button onclick="document.getElementById('addRuleModal').style.display='block'" class="btn" style="background: #2563EB; color: white; border-radius: 8px; padding: 8px 14px; font-size: 13px; font-weight: 700;">
                <i class="fa-solid fa-plus"></i> {{ app()->getLocale() === 'ja' ? '季節ルール追加' : 'Add Seasonal Rule' }}
            </button>
        </div>
        <div style="padding: 16px 22px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 2px solid #E2E8F0; text-align: left; color: #64748B;">
                        <th style="padding: 10px 8px;">{{ app()->getLocale() === 'ja' ? 'ルール名称' : 'Rule Name' }}</th>
                        <th style="padding: 10px 8px;">{{ app()->getLocale() === 'ja' ? '対象客室' : 'Target Room' }}</th>
                        <th style="padding: 10px 8px;">{{ app()->getLocale() === 'ja' ? '適用期間' : 'Period' }}</th>
                        <th style="padding: 10px 8px;">{{ app()->getLocale() === 'ja' ? '料金調整' : 'Adjustment' }}</th>
                        <th style="padding: 10px 8px;">{{ app()->getLocale() === 'ja' ? '優先順位' : 'Priority' }}</th>
                        <th style="padding: 10px 8px;">{{ app()->getLocale() === 'ja' ? '状態' : 'Status' }}</th>
                        <th style="padding: 10px 8px; text-align: right;">{{ app()->getLocale() === 'ja' ? '操作' : 'Action' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $r)
                    <tr style="border-bottom: 1px solid #F1F5F9;">
                        <td style="padding: 12px 8px;">
                            <strong style="color: #1E293B;">{{ $r->rule_name }}</strong>
                            @if($r->rule_name_ja)
                                <div style="font-size: 11px; color: #64748B;">{{ $r->rule_name_ja }}</div>
                            @endif
                        </td>
                        <td style="padding: 12px 8px; color: #475569;">
                            {{ $r->roomType ? $r->roomType->name : (app()->getLocale() === 'ja' ? '全客室タイプ' : 'All Room Types') }}
                        </td>
                        <td style="padding: 12px 8px; font-family: monospace; color: #334155;">
                            {{ $r->start_date->format('Y-m-d') }} ~ {{ $r->end_date->format('Y-m-d') }}
                        </td>
                        <td style="padding: 12px 8px;">
                            @if($r->percentage_adjustment >= 0)
                                <span style="font-weight: 800; color: #2563EB;">+{{ $r->percentage_adjustment }}%</span>
                            @else
                                <span style="font-weight: 800; color: #059669;">{{ $r->percentage_adjustment }}%</span>
                            @endif
                            @if($r->fixed_adjustment != 0)
                                <span style="font-size: 11px; color: #64748B; margin-left: 4px;">
                                    ({{ $r->fixed_adjustment >= 0 ? '+' : '' }}¥{{ number_format($r->fixed_adjustment) }})
                                </span>
                            @endif
                        </td>
                        <td style="padding: 12px 8px; font-weight: 700; color: #64748B;">
                            {{ $r->priority }}
                        </td>
                        <td style="padding: 12px 8px;">
                            @if($r->is_active)
                                <span class="badge" style="background: #D1FAE5; color: #065F46;">Active</span>
                            @else
                                <span class="badge" style="background: #F1F5F9; color: #94A3B8;">Disabled</span>
                            @endif
                        </td>
                        <td style="padding: 12px 8px; text-align: right;">
                            <form action="{{ route('pricing_settings.delete_seasonal_rule', $r->id) }}" method="POST" onsubmit="return confirm('Delete this seasonal rule?');" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn" style="padding: 4px 8px; font-size: 12px; background: transparent; color: #EF4444; border: 1px solid #FECACA; border-radius: 6px;">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding: 24px; text-align: center; color: #94A3B8;">
                            {{ app()->getLocale() === 'ja' ? '季節ルールはまだ設定されていません。' : 'No seasonal pricing rules configured yet.' }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section E: Stacking Policy & Rounding Configuration -->
    <div class="card" style="margin-bottom: 28px; background: white; border-radius: 14px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
        <div style="padding: 18px 22px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; background: #F8FAFC;">
            <i class="fa-solid fa-layer-group" style="color: #4F46E5; font-size: 18px;"></i>
            <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">
                {{ app()->getLocale() === 'ja' ? '料金計算スタッキング・端数処理設定' : 'Rule Stacking Policy & Rounding Engine' }}
            </h3>
        </div>
        <div style="padding: 24px 22px;">
            <form action="{{ route('pricing_settings.update_policy') }}" method="POST">
                @csrf
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
                    <!-- Additive -->
                    <label style="border: 2px solid {{ $settings->stacking_policy === 'additive' ? '#4F46E5' : '#E2E8F0' }}; border-radius: 10px; padding: 16px; cursor: pointer; background: {{ $settings->stacking_policy === 'additive' ? '#EEF2FF' : '#FAFAFA' }};">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <input type="radio" name="stacking_policy" value="additive" {{ $settings->stacking_policy === 'additive' ? 'checked' : '' }}>
                            <strong style="color: #1E293B; font-size: 14px;">{{ app()->getLocale() === 'ja' ? '加算方式 (Additive - デフォルト)' : 'Additive (Standard Default)' }}</strong>
                        </div>
                        <p style="font-size: 12px; color: #475569; margin: 0;">
                            Base × (1 + (週末% + 祝日% + 季節%)) + 固定追加額。<br>
                            <em>例: Base ¥10,000 + 週末15% + GW35% = ¥15,000</em>
                        </p>
                    </label>

                    <!-- Multiplicative -->
                    <label style="border: 2px solid {{ $settings->stacking_policy === 'multiplicative' ? '#4F46E5' : '#E2E8F0' }}; border-radius: 10px; padding: 16px; cursor: pointer; background: {{ $settings->stacking_policy === 'multiplicative' ? '#EEF2FF' : '#FAFAFA' }};">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <input type="radio" name="stacking_policy" value="multiplicative" {{ $settings->stacking_policy === 'multiplicative' ? 'checked' : '' }}>
                            <strong style="color: #1E293B; font-size: 14px;">{{ app()->getLocale() === 'ja' ? '乗算方式 (Multiplicative)' : 'Multiplicative (Compound)' }}</strong>
                        </div>
                        <p style="font-size: 12px; color: #475569; margin: 0;">
                            Base × (1 + 週末%) × (1 + 祝日%) × (1 + 季節%) + 固定額。<br>
                            <em>例: Base ¥10,000 × 1.15 × 1.35 = ¥15,525</em>
                        </p>
                    </label>

                    <!-- Highest Only -->
                    <label style="border: 2px solid {{ $settings->stacking_policy === 'highest_only' ? '#4F46E5' : '#E2E8F0' }}; border-radius: 10px; padding: 16px; cursor: pointer; background: {{ $settings->stacking_policy === 'highest_only' ? '#EEF2FF' : '#FAFAFA' }};">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <input type="radio" name="stacking_policy" value="highest_only" {{ $settings->stacking_policy === 'highest_only' ? 'checked' : '' }}>
                            <strong style="color: #1E293B; font-size: 14px;">{{ app()->getLocale() === 'ja' ? '最高料率優先 (Highest Only)' : 'Highest Rule Only' }}</strong>
                        </div>
                        <p style="font-size: 12px; color: #475569; margin: 0;">
                            該当するルールの中で最も高い料率のみを適用。<br>
                            <em>例: max(週末15%, GW35%) → 35%のみ適用 = ¥13,500</em>
                        </p>
                    </label>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            {{ app()->getLocale() === 'ja' ? '端数処理ポリシー' : 'Rounding Policy' }}
                        </label>
                        <select name="rounding_policy" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-weight: 700;">
                            <option value="whole_yen" {{ $settings->rounding_policy === 'whole_yen' ? 'selected' : '' }}>{{ app()->getLocale() === 'ja' ? '1円単位 (四捨五入: ¥15,420)' : 'Whole Yen (round to nearest ¥1)' }}</option>
                            <option value="nearest_100" {{ $settings->rounding_policy === 'nearest_100' ? 'selected' : '' }}>{{ app()->getLocale() === 'ja' ? '100円単位 (四捨五入: ¥15,400)' : 'Nearest 100 JPY (round to nearest ¥100)' }}</option>
                            <option value="none" {{ $settings->rounding_policy === 'none' ? 'selected' : '' }}>{{ app()->getLocale() === 'ja' ? '端数処理なし (小数点第2位まで)' : 'No Rounding (exact decimals)' }}</option>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            {{ app()->getLocale() === 'ja' ? '税率 (%)' : 'Tax Percentage (%)' }}
                        </label>
                        <input type="number" name="tax_percentage" value="{{ $settings->tax_percentage }}" step="0.5" min="0" max="50" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-weight: 700;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            {{ app()->getLocale() === 'ja' ? '内税・外税設定' : 'Tax Presentation' }}
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; padding-top: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                            <input type="checkbox" name="tax_included" value="1" {{ $settings->tax_included ? 'checked' : '' }}>
                            <span>{{ app()->getLocale() === 'ja' ? '総額表示 (税込価格で表示・請求)' : 'Tax Included (Japanese Total Price Display)' }}</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn" style="background: #4F46E5; color: white; padding: 10px 24px; border-radius: 8px; font-weight: 700; border: none; cursor: pointer;">
                    <i class="fa-solid fa-check"></i> {{ app()->getLocale() === 'ja' ? '計算ポリシーを保存' : 'Save Calculation Policy' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Section F: Interactive Pricing Simulation Sandbox -->
    <div class="card" style="margin-bottom: 28px; background: white; border-radius: 14px; border: 1px solid #93C5FD; overflow: hidden; box-shadow: 0 4px 12px rgba(37,99,235,0.06);">
        <div style="padding: 18px 22px; border-bottom: 1px solid #BFDBFE; display: flex; align-items: center; gap: 10px; background: #EFF6FF;">
            <i class="fa-solid fa-flask" style="color: #2563EB; font-size: 18px;"></i>
            <div>
                <h3 style="font-size: 16px; font-weight: 800; color: #1E3A8A; margin: 0;">
                    {{ app()->getLocale() === 'ja' ? 'インタラクティブ料金シミュレータ' : 'Interactive Rate Calculation Sandbox' }}
                </h3>
                <p style="font-size: 12px; color: #3B82F6; margin: 2px 0 0;">
                    {{ app()->getLocale() === 'ja' ? '宿泊日・客室タイプ・スタッキング方式を指定して、日別の料金計算内訳と合計金額を即座にシミュレーションします。' : 'Test dynamic nightly quotes in real-time across dates, room types, and hypothetical stacking rules.' }}
                </p>
            </div>
        </div>
        <div style="padding: 22px;">
            <div style="display: grid; grid-template-columns: 2fr 1.5fr 1.5fr 1.5fr auto; gap: 14px; align-items: flex-end; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? '客室タイプ' : 'Room Type' }}</label>
                    <select id="simRoomType" style="width: 100%; padding: 8px 10px; border: 1px solid #CBD5E1; border-radius: 8px; font-weight: 700;">
                        @foreach($roomTypes as $rt)
                            <option value="{{ $rt->id }}" data-base="{{ $rt->base_price }}">{{ $rt->name }} (¥{{ number_format($rt->base_price) }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? 'チェックイン日' : 'Check-In' }}</label>
                    <input type="date" id="simCheckIn" value="{{ now()->addDays(2)->format('Y-m-d') }}" style="width: 100%; padding: 8px 10px; border: 1px solid #CBD5E1; border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? 'チェックアウト日' : 'Check-Out' }}</label>
                    <input type="date" id="simCheckOut" value="{{ now()->addDays(5)->format('Y-m-d') }}" style="width: 100%; padding: 8px 10px; border: 1px solid #CBD5E1; border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? 'スタッキング方式' : 'Stacking Policy' }}</label>
                    <select id="simPolicy" style="width: 100%; padding: 8px 10px; border: 1px solid #CBD5E1; border-radius: 8px;">
                        <option value="">{{ app()->getLocale() === 'ja' ? '現在の設定 (' . strtoupper($settings->stacking_policy) . ')' : 'Current (' . strtoupper($settings->stacking_policy) . ')' }}</option>
                        <option value="additive">Additive (加算)</option>
                        <option value="multiplicative">Multiplicative (乗算)</option>
                        <option value="highest_only">Highest Only (最高優先)</option>
                    </select>
                </div>
                <div>
                    <button type="button" id="btnRunSim" class="btn" style="background: #2563EB; color: white; padding: 10px 18px; border-radius: 8px; font-weight: 700; border: none; cursor: pointer; white-space: nowrap;">
                        <i class="fa-solid fa-play"></i> {{ app()->getLocale() === 'ja' ? '試算実行' : 'Simulate Quote' }}
                    </button>
                </div>
            </div>

            <!-- Simulation Result Card -->
            <div id="simResultContainer" style="display: none; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 18px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #E2E8F0; padding-bottom: 10px;">
                    <div>
                        <span id="simNightsBadge" class="badge" style="background: #E0E7FF; color: #3730A3; font-size: 13px; font-weight: 800;">5 Nights</span>
                        <span id="simPolicyBadge" class="badge" style="background: #F1F5F9; color: #475569; font-size: 12px; margin-left: 6px;">Additive</span>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 12px; color: #64748B;">{{ app()->getLocale() === 'ja' ? '合計請求額 (税込)' : 'Grand Total (Tax Included)' }}</div>
                        <div id="simGrandTotal" style="font-size: 24px; font-weight: 900; color: #0F172A;">¥0</div>
                    </div>
                </div>

                <!-- Nightly Table -->
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px; background: white; border-radius: 8px; overflow: hidden; border: 1px solid #E2E8F0;">
                        <thead>
                            <tr style="background: #F1F5F9; text-align: left; color: #475569;">
                                <th style="padding: 10px 12px;">{{ app()->getLocale() === 'ja' ? '宿泊日' : 'Stay Date' }}</th>
                                <th style="padding: 10px 12px;">{{ app()->getLocale() === 'ja' ? '曜日' : 'Day' }}</th>
                                <th style="padding: 10px 12px;">{{ app()->getLocale() === 'ja' ? '基準室料' : 'Base Rate' }}</th>
                                <th style="padding: 10px 12px;">{{ app()->getLocale() === 'ja' ? '適用された料金ルール・理由' : 'Applied Rules & Badges' }}</th>
                                <th style="padding: 10px 12px; text-align: right;">{{ app()->getLocale() === 'ja' ? '日毎室料' : 'Nightly Rate' }}</th>
                            </tr>
                        </thead>
                        <tbody id="simBreakdownBody">
                            <!-- Populated via JS -->
                        </tbody>
                        <tfoot style="background: #F8FAFC; font-weight: 800; border-top: 2px solid #CBD5E1;">
                            <tr>
                                <td colspan="3" style="padding: 12px;">{{ app()->getLocale() === 'ja' ? '合計内訳' : 'Totals Breakdown' }}</td>
                                <td style="padding: 12px; font-size: 12px; color: #64748B;">
                                    <span id="simBaseTotal">Base: ¥0</span> | 
                                    <span id="simSurchargeTotal" style="color: #DC2626;">Surcharges: +¥0</span> | 
                                    <span id="simTaxTotal">Tax: ¥0</span>
                                </td>
                                <td id="simFooterGrandTotal" style="padding: 12px; text-align: right; font-size: 16px; color: #0F172A;">¥0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Section G: Pricing Audit History -->
    <div class="card" style="background: white; border-radius: 14px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
        <div style="padding: 18px 22px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; background: #F8FAFC;">
            <i class="fa-solid fa-clock-rotate-left" style="color: #64748B; font-size: 18px;"></i>
            <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">
                {{ app()->getLocale() === 'ja' ? '料金変更 監査ログ' : 'Pricing Audit History' }}
            </h3>
        </div>
        <div style="padding: 16px 22px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                <thead>
                    <tr style="border-bottom: 2px solid #E2E8F0; text-align: left; color: #64748B;">
                        <th style="padding: 8px;">{{ app()->getLocale() === 'ja' ? '日時' : 'Timestamp' }}</th>
                        <th style="padding: 8px;">{{ app()->getLocale() === 'ja' ? '実行ユーザー' : 'User' }}</th>
                        <th style="padding: 8px;">{{ app()->getLocale() === 'ja' ? '操作' : 'Action' }}</th>
                        <th style="padding: 8px;">{{ app()->getLocale() === 'ja' ? '変更内容' : 'Values' }}</th>
                        <th style="padding: 8px;">{{ app()->getLocale() === 'ja' ? '変更理由' : 'Reason' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditLogs as $log)
                    <tr style="border-bottom: 1px solid #F1F5F9;">
                        <td style="padding: 10px 8px; font-family: monospace; color: #64748B;">
                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td style="padding: 10px 8px; font-weight: 600; color: #1E293B;">
                            {{ $log->user ? $log->user->name : 'System' }}
                        </td>
                        <td style="padding: 10px 8px;">
                            <span class="badge" style="background: #E2E8F0; color: #334155;">{{ $log->action }}</span>
                        </td>
                        <td style="padding: 10px 8px; font-family: monospace; color: #475569; font-size: 11px;">
                            @if($log->new_values)
                                {{ json_encode($log->new_values, JSON_UNESCAPED_UNICODE) }}
                            @endif
                        </td>
                        <td style="padding: 10px 8px; color: #64748B;">
                            {{ $log->reason ?? 'N/A' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding: 20px; text-align: center; color: #94A3B8;">
                            {{ app()->getLocale() === 'ja' ? '監査ログはまだ記録されていません。' : 'No audit records found.' }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Event Modal -->
<div id="addEventModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
    <div style="background: white; max-width: 500px; margin: 60px auto; border-radius: 14px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="font-size: 18px; font-weight: 800; color: #0F172A; margin: 0;">
                {{ app()->getLocale() === 'ja' ? '祝日・特別イベント追加' : 'Add Calendar Event' }}
            </h3>
            <button onclick="document.getElementById('addEventModal').style.display='none'" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #94A3B8;">&times;</button>
        </div>
        <form action="{{ route('pricing_settings.store_event') }}" method="POST">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? 'イベント名 (英語)' : 'Event Title (EN)' }}</label>
                <input type="text" name="title" required style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px;">
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? 'イベント名 (日本語)' : 'Event Title (JA)' }}</label>
                <input type="text" name="title_ja" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px;">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? '区分' : 'Event Type' }}</label>
                    <select name="event_type" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px;">
                        <option value="holiday">{{ app()->getLocale() === 'ja' ? '国民の祝日' : 'National Holiday' }}</option>
                        <option value="event">{{ app()->getLocale() === 'ja' ? '特別イベント' : 'Special Event' }}</option>
                        <option value="festival">{{ app()->getLocale() === 'ja' ? '祭礼・フェスティバル' : 'Festival' }}</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? '割増率 (%)' : 'Surcharge %' }}</label>
                    <input type="number" name="percentage_adjustment" value="20" step="0.5" required style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px;">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? '開始日' : 'Start Date' }}</label>
                    <input type="date" name="start_date" required style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? '終了日' : 'End Date' }}</label>
                    <input type="date" name="end_date" required style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px;">
                </div>
            </div>
            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? '固定加算額 (任意)' : 'Fixed Surcharge (Optional JPY)' }}</label>
                <input type="number" name="fixed_surcharge" value="0" step="100" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px;">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="document.getElementById('addEventModal').style.display='none'" class="btn" style="background: #E2E8F0; color: #334155; padding: 8px 16px; border-radius: 8px; font-weight: 700;">Cancel</button>
                <button type="submit" class="btn" style="background: #0F172A; color: white; padding: 8px 16px; border-radius: 8px; font-weight: 700;">Save Event</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Seasonal Rule Modal -->
<div id="addRuleModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
    <div style="background: white; max-width: 540px; margin: 60px auto; border-radius: 14px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="font-size: 18px; font-weight: 800; color: #0F172A; margin: 0;">
                {{ app()->getLocale() === 'ja' ? '季節・特別料金ルール追加' : 'Add Seasonal Pricing Rule' }}
            </h3>
            <button onclick="document.getElementById('addRuleModal').style.display='none'" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #94A3B8;">&times;</button>
        </div>
        <form action="{{ route('pricing_settings.store_seasonal_rule') }}" method="POST">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? 'ルール名称' : 'Rule Name' }}</label>
                <input type="text" name="rule_name" required placeholder="e.g. Summer Vacation Peak" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px;">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? '対象客室タイプ' : 'Target Room Type' }}</label>
                    <select name="room_type_id" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px;">
                        <option value="">{{ app()->getLocale() === 'ja' ? '全客室タイプ共通' : 'All Room Types' }}</option>
                        @foreach($roomTypes as $rt)
                            <option value="{{ $rt->id }}">{{ $rt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? '区分' : 'Rule Type' }}</label>
                    <select name="rule_type" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px;">
                        <option value="seasonal">{{ app()->getLocale() === 'ja' ? '季節料金' : 'Seasonal' }}</option>
                        <option value="promotion">{{ app()->getLocale() === 'ja' ? '特別プロモーション' : 'Promotion' }}</option>
                        <option value="custom_discount">{{ app()->getLocale() === 'ja' ? '特別割引' : 'Custom Discount' }}</option>
                    </select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? '料率調整 (%)' : 'Percentage Adjustment (%)' }}</label>
                    <input type="number" name="percentage_adjustment" value="15" step="0.5" required style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? '優先順位 (高い順)' : 'Priority' }}</label>
                    <input type="number" name="priority" value="5" min="0" max="100" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px;">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? '開始日' : 'Start Date' }}</label>
                    <input type="date" name="start_date" required style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? '終了日' : 'End Date' }}</label>
                    <input type="date" name="end_date" required style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px;">
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="document.getElementById('addRuleModal').style.display='none'" class="btn" style="background: #E2E8F0; color: #334155; padding: 8px 16px; border-radius: 8px; font-weight: 700;">Cancel</button>
                <button type="submit" class="btn" style="background: #2563EB; color: white; padding: 8px 16px; border-radius: 8px; font-weight: 700;">Save Rule</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnSim = document.getElementById('btnRunSim');
    const simResult = document.getElementById('simResultContainer');
    const simBreakdownBody = document.getElementById('simBreakdownBody');

    btnSim.addEventListener('click', function () {
        const roomTypeId = document.getElementById('simRoomType').value;
        const checkIn = document.getElementById('simCheckIn').value;
        const checkOut = document.getElementById('simCheckOut').value;
        const policy = document.getElementById('simPolicy').value;

        if (!checkIn || !checkOut || checkIn >= checkOut) {
            alert('Please select a valid check-out date after the check-in date.');
            return;
        }

        btnSim.disabled = true;
        btnSim.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Calculating...';

        fetch("{{ route('pricing_settings.simulate') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                room_type_id: roomTypeId,
                check_in_date: checkIn,
                check_out_date: checkOut,
                override_stacking_policy: policy || null
            })
        })
        .then(res => res.json())
        .then(data => {
            btnSim.disabled = false;
            btnSim.innerHTML = '<i class="fa-solid fa-play"></i> Simulate Quote';

            if (!data.success) {
                alert(data.message || 'Simulation error');
                return;
            }

            const q = data.quote;
            document.getElementById('simNightsBadge').textContent = q.nights_count + ' Nights (' + q.check_in_date + ' ~ ' + q.check_out_date + ')';
            document.getElementById('simPolicyBadge').textContent = 'Policy: ' + q.stacking_policy.toUpperCase();
            document.getElementById('simGrandTotal').textContent = '¥' + Number(q.grand_total).toLocaleString();
            document.getElementById('simBaseTotal').textContent = 'Base: ¥' + Number(q.base_total).toLocaleString();
            document.getElementById('simSurchargeTotal').textContent = 'Surcharges: +¥' + Number(q.surcharge_total).toLocaleString();
            document.getElementById('simTaxTotal').textContent = 'Tax: ¥' + Number(q.tax_total).toLocaleString();
            document.getElementById('simFooterGrandTotal').textContent = '¥' + Number(q.grand_total).toLocaleString();

            simBreakdownBody.innerHTML = '';
            q.nightly_breakdown.forEach(night => {
                let badgeHtml = '';
                if (night.applied_rules && night.applied_rules.length > 0) {
                    night.applied_rules.forEach(r => {
                        let colorBg = r.badge_color === 'red' ? '#FEE2E2' : (r.badge_color === 'amber' ? '#FEF3C7' : (r.badge_color === 'purple' ? '#EDE9FE' : '#DBEAFE'));
                        let colorText = r.badge_color === 'red' ? '#991B1B' : (r.badge_color === 'amber' ? '#92400E' : (r.badge_color === 'purple' ? '#5B21B6' : '#1E40AF'));
                        badgeHtml += `<span class="badge" style="background: ${colorBg}; color: ${colorText}; margin-right: 4px; font-size: 11px; padding: 2px 8px; border-radius: 4px;">${r.badge}</span>`;
                    });
                } else {
                    badgeHtml = '<span style="color: #94A3B8; font-size: 12px;">Standard Base Rate</span>';
                }

                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #F1F5F9';
                tr.innerHTML = `
                    <td style="padding: 10px 12px; font-weight: 700; color: #1E293B;">${night.date}</td>
                    <td style="padding: 10px 12px; color: ${night.is_weekend ? '#D97706' : '#64748B'}; font-weight: ${night.is_weekend ? '800' : '600'};">
                        ${night.day_of_week} (${night.day_name_ja})
                    </td>
                    <td style="padding: 10px 12px; color: #64748B;">¥${Number(night.base_rate).toLocaleString()}</td>
                    <td style="padding: 10px 12px;">${badgeHtml}</td>
                    <td style="padding: 10px 12px; text-align: right; font-weight: 800; font-size: 14px; color: #0F172A;">
                        ¥${Number(night.nightly_rate).toLocaleString()}
                    </td>
                `;
                simBreakdownBody.appendChild(tr);
            });

            simResult.style.display = 'block';
        })
        .catch(err => {
            btnSim.disabled = false;
            btnSim.innerHTML = '<i class="fa-solid fa-play"></i> Simulate Quote';
            alert('Failed to connect to simulation server.');
        });
    });
});
</script>
@endsection
