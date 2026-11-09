@extends('layouts.app')

@section('title', 'Smart Rate Studio & Calendar Intelligence - ClearStay 2.0')
@section('header_title', 'Smart Rate Studio & Demand Intelligence')
@section('header_subtitle', 'Dynamic yield strategies, Japanese holiday calendar intelligence, and real-time rate parity surveillance.')

@section('content')
<div class="rates-container">
    <!-- Top Action Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--text-main); margin: 0;">Rate Strategy & Demand Engine</h2>
            <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 0;">
                Automated seasonal multipliers, holiday surges (Golden Week, Obon, New Year) and rate parity enforcement.
            </p>
        </div>
        <form action="{{ route('rates.scan_parity') }}" method="POST">
            @csrf
            <button type="submit" class="btn" style="background: #287F79; color: white; border: none; font-weight: 700;">
                <i class="fa-solid fa-radar"></i> Run Parity Crawler & Scan
            </button>
        </form>
    </div>

    <!-- Active Rate Plans Grid -->
    <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 14px;">
        <i class="fa-solid fa-tags" style="color: #F472B6;"></i> Active Tariff & Pricing Plans
    </h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px; margin-bottom: 30px;">
        @foreach($ratePlans as $plan)
        <div class="card" style="margin-bottom: 0;">
            <div class="card-body" style="padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                    <div style="font-size: 11px; font-family: monospace; color: var(--text-muted); font-weight: 700;">
                        {{ $plan->code }}
                    </div>
                    @if($plan->is_active)
                        <span class="badge" style="background: #D1FAE5; color: #065F46;">Active</span>
                    @else
                        <span class="badge" style="background: #F1F5F9; color: #64748B;">Inactive</span>
                    @endif
                </div>

                <h4 style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 6px;">
                    {{ $plan->name }}
                </h4>
                
                <div style="font-size: 24px; font-weight: 800; color: #183B50; margin-bottom: 12px;">
                    ¥{{ number_format($plan->base_price) }} <span style="font-size: 12px; font-weight: 500; color: var(--text-muted);">/ night</span>
                </div>

                <div style="border-top: 1px solid var(--border); padding-top: 12px; font-size: 12px; color: var(--text-muted);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Cancellation:</span>
                        <strong style="color: var(--text-main);">{{ ucfirst(str_replace('_', ' ', $plan->cancellation_policy)) }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Meal Plan:</span>
                        <strong style="color: var(--text-main);">{{ ucfirst(str_replace('_', ' ', $plan->meal_plan)) }}</strong>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- 2-Panel Section: Japanese Holiday Demand Drivers & Parity Surveillance -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px;">
        <!-- Japanese Holiday Calendar Demand Intelligence -->
        <div class="card">
            <div class="card-header" style="background: white;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                        <i class="fa-solid fa-calendar-star" style="color: #E8A64A;"></i> Japanese Holiday Calendar Intelligence
                    </h3>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                        Pre-configured demand drivers and surge multipliers (ゴールデンウィーク・お盆・年末年始)
                    </p>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                            <th style="padding: 12px 16px;">Holiday / Event</th>
                            <th style="padding: 12px 10px;">Dates</th>
                            <th style="padding: 12px 10px;">Demand Multiplier</th>
                            <th style="padding: 12px 16px; text-align: right;">Min Stay</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($calendarEvents as $event)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 14px 16px;">
                                <div style="font-weight: 700; color: var(--text-main);">{{ $event->title }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ $event->title_ja }}</div>
                            </td>
                            <td style="padding: 14px 10px; font-size: 12px;">
                                {{ $event->start_date->format('M d') }} - {{ $event->end_date->format('M d, Y') }}
                            </td>
                            <td style="padding: 14px 10px;">
                                <span class="badge" style="background: #FEF3C7; color: #92400E; font-weight: 700;">
                                    +{{ round(($event->demand_multiplier - 1.0) * 100) }}% Surge
                                </span>
                            </td>
                            <td style="padding: 14px 16px; text-align: right; font-weight: 600;">
                                {{ $event->recommended_min_stay ?? 1 }} nights
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="padding: 20px; text-align: center; color: var(--text-muted);">
                                No calendar events loaded.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Real-Time Rate Parity Surveillance -->
        <div class="card">
            <div class="card-header" style="background: white;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                        <i class="fa-solid fa-scale-balanced" style="color: #287F79;"></i> Real-Time Rate Parity Monitor
                    </h3>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                        Guarantees brand.com remains best-rate compliant against OTA scrapers
                    </p>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                            <th style="padding: 12px 16px;">Channel</th>
                            <th style="padding: 12px 10px;">Direct Web</th>
                            <th style="padding: 12px 10px;">OTA Price</th>
                            <th style="padding: 12px 10px;">Variance</th>
                            <th style="padding: 12px 16px; text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($parityObservations as $parity)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 14px 16px; font-weight: 600;">
                                {{ $parity->channel?->name ?? 'OTA Channel' }}
                            </td>
                            <td style="padding: 14px 10px; font-weight: 700;">
                                ¥{{ number_format($parity->expected_rate) }}
                            </td>
                            <td style="padding: 14px 10px;">
                                ¥{{ number_format($parity->observed_rate) }}
                            </td>
                            <td style="padding: 14px 10px; font-weight: 700; color: {{ $parity->difference_amount < 0 ? '#991B1B' : '#065F46' }};">
                                {{ $parity->difference_amount > 0 ? '+' : '' }}¥{{ number_format($parity->difference_amount) }}
                            </td>
                            <td style="padding: 14px 16px; text-align: right;">
                                @if(!$parity->is_discrepancy)
                                    <span class="badge" style="background: #D1FAE5; color: #065F46;">
                                        <i class="fa-solid fa-check"></i> Parity OK
                                    </span>
                                @else
                                    <span class="badge" style="background: #FEE2E2; color: #991B1B;">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Discrepancy
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);">
                                No parity observations recorded. Click "Run Parity Crawler & Scan" above.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
