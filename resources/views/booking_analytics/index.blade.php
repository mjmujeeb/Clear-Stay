@extends('layouts.app')

@section('title', 'ClearStay 2.0 - Booking Analytics (BA+)')
@section('header_title', 'Advanced Booking Analytics & Historical Pace (BA+)')
@section('header_subtitle', 'TEMAIRAZU JIDO BA+ oriented booking monitor, ADR/RevPAR KPIs, lead-time pace curves, and historical snapshot comparisons.')

@section('content')
<div class="analytics-container">
    <!-- Filter Bar -->
    <div class="card" style="margin-bottom: 24px; padding: 18px 24px; background: #FFFFFF;">
        <form method="GET" action="{{ route('booking_analytics.index') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) 120px; gap: 14px; align-items: flex-end;">
            <div>
                <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 4px;">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;">
            </div>
            <div>
                <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 4px;">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;">
            </div>
            <div>
                <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 4px;">Room Type</label>
                <select name="room_type_id" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;">
                    <option value="">All Room Types</option>
                    @foreach($roomTypes as $rt)
                        <option value="{{ $rt->id }}" {{ $roomTypeId == $rt->id ? 'selected' : '' }}>{{ $rt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 4px;">Channel</label>
                <select name="channel_id" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;">
                    <option value="">All Channels</option>
                    @foreach($channels as $ch)
                        <option value="{{ $ch->id }}" {{ $channelId == $ch->id ? 'selected' : '' }}>{{ $ch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 4px;">Status</label>
                <select name="status" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;">
                    <option value="">Active (Confirmed+Checked In)</option>
                    <option value="confirmed" {{ $status == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="checked_in" {{ $status == 'checked_in' ? 'selected' : '' }}>Checked In</option>
                    <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn" style="width: 100%; background: #183B50; color: white; padding: 9px; font-weight: 600; font-size: 13px;">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Core KPIs Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <!-- Card 1: Occupancy Rate -->
        <div class="card" style="margin-bottom: 0; border-top: 4px solid var(--primary-accent); padding: 20px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Occupancy Rate</div>
            <div style="font-size: 32px; font-weight: 800; color: var(--text-main); margin-top: 4px;">
                {{ $metrics['occupancy_rate'] }}%
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">
                {{ $metrics['rooms_sold'] }} rooms sold / {{ $metrics['total_available_room_nights'] }} available nights
            </div>
        </div>

        <!-- Card 2: ADR (Average Daily Rate) -->
        <div class="card" style="margin-bottom: 0; border-top: 4px solid #10B981; padding: 20px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">ADR (Avg Daily Rate)</div>
            <div style="font-size: 32px; font-weight: 800; color: var(--text-main); margin-top: 4px;">
                ¥{{ number_format($metrics['adr']) }}
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">
                Room Revenue ÷ Rooms Sold
            </div>
        </div>

        <!-- Card 3: RevPAR (Revenue Per Available Room) -->
        <div class="card" style="margin-bottom: 0; border-top: 4px solid #F59E0B; padding: 20px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">RevPAR</div>
            <div style="font-size: 32px; font-weight: 800; color: var(--text-main); margin-top: 4px;">
                ¥{{ number_format($metrics['revpar']) }}
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">
                Room Revenue ÷ Total Room Nights
            </div>
        </div>

        <!-- Card 4: Total Room Revenue -->
        <div class="card" style="margin-bottom: 0; border-top: 4px solid #6366F1; padding: 20px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Room Revenue</div>
            <div style="font-size: 32px; font-weight: 800; color: var(--text-main); margin-top: 4px;">
                ¥{{ number_format($metrics['room_revenue']) }}
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">
                Excluding taxes and incidental charges
            </div>
        </div>

        <!-- Card 5: Cancellation Rate -->
        <div class="card" style="margin-bottom: 0; border-top: 4px solid #EF4444; padding: 20px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Cancellations</div>
            <div style="font-size: 32px; font-weight: 800; color: var(--text-main); margin-top: 4px;">
                {{ $metrics['cancellation_rate'] }}%
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">
                {{ $metrics['cancellations_count'] }} total cancellations
            </div>
        </div>
    </div>

    <!-- Section 2: Historical Booking Comparison (4-Horizon Snapshot Analysis) -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header" style="background: #FFFFFF; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                    <i class="fa-solid fa-clock-rotate-left" style="color: var(--primary-accent);"></i> Historical Booking Position Comparison
                </h3>
                <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                    Comparing reservations for stay date <strong>{{ $targetStayDate }}</strong> against historical intervals (7d ago, 30d ago, 1yr ago)
                </p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <form action="{{ route('booking_analytics.capture') }}" method="POST" style="display: flex; gap: 8px;">
                    @csrf
                    <input type="hidden" name="stay_date" value="{{ $targetStayDate }}">
                    <button type="submit" class="btn btn-sm btn-secondary" style="font-size: 12px;">
                        <i class="fa-solid fa-camera"></i> Capture Snapshot Now
                    </button>
                </form>
                <a href="{{ route('booking_analytics.export_csv') }}" class="btn btn-sm" style="background: #287F79; color: white; font-size: 12px;">
                    <i class="fa-solid fa-file-csv"></i> Export CSV
                </a>
            </div>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); font-size: 12px; color: var(--text-muted); text-transform: uppercase;">
                        <th style="padding: 12px 20px; text-align: left;">Horizon</th>
                        <th style="padding: 12px 16px; text-align: right;">Snapshot Date</th>
                        <th style="padding: 12px 16px; text-align: right;">Bookings</th>
                        <th style="padding: 12px 16px; text-align: right;">Occupancy %</th>
                        <th style="padding: 12px 16px; text-align: right;">Revenue (JPY)</th>
                        <th style="padding: 12px 16px; text-align: right;">ADR</th>
                        <th style="padding: 12px 20px; text-align: right;">Delta vs Today</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Current Position -->
                    <tr style="border-bottom: 1px solid var(--border); font-weight: 700; background: #F0FDF4;">
                        <td style="padding: 14px 20px; color: #166534;">
                            <i class="fa-solid fa-circle" style="font-size: 8px; margin-right: 6px;"></i> Current Position (Today)
                        </td>
                        <td style="padding: 14px 16px; text-align: right; color: #166534;">{{ now()->toDateString() }}</td>
                        <td style="padding: 14px 16px; text-align: right; font-size: 16px;">{{ $historicalComparison['current']?->booking_count ?? 0 }}</td>
                        <td style="padding: 14px 16px; text-align: right;">{{ $historicalComparison['current']?->occupancy_rate ?? 0 }}%</td>
                        <td style="padding: 14px 16px; text-align: right;">¥{{ number_format($historicalComparison['current']?->revenue ?? 0) }}</td>
                        <td style="padding: 14px 16px; text-align: right;">¥{{ number_format($historicalComparison['current']?->adr ?? 0) }}</td>
                        <td style="padding: 14px 20px; text-align: right; color: var(--text-muted);">Baseline</td>
                    </tr>

                    <!-- 7 Days Earlier -->
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 14px 20px; font-weight: 600;">7 Days Earlier</td>
                        <td style="padding: 14px 16px; text-align: right; color: var(--text-muted);">
                            {{ $historicalComparison['seven_days_ago']?->snapshot_date?->toDateString() ?? 'No snapshot' }}
                        </td>
                        <td style="padding: 14px 16px; text-align: right;">{{ $historicalComparison['seven_days_ago']?->booking_count ?? '-' }}</td>
                        <td style="padding: 14px 16px; text-align: right;">
                            {{ $historicalComparison['seven_days_ago'] ? $historicalComparison['seven_days_ago']->occupancy_rate . '%' : '-' }}
                        </td>
                        <td style="padding: 14px 16px; text-align: right;">
                            {{ $historicalComparison['seven_days_ago'] ? '¥' . number_format($historicalComparison['seven_days_ago']->revenue) : '-' }}
                        </td>
                        <td style="padding: 14px 16px; text-align: right;">
                            {{ $historicalComparison['seven_days_ago'] ? '¥' . number_format($historicalComparison['seven_days_ago']->adr) : '-' }}
                        </td>
                        <td style="padding: 14px 20px; text-align: right;">
                            @if($historicalComparison['seven_days_ago'] && $historicalComparison['current'])
                                @php $diff = $historicalComparison['current']->booking_count - $historicalComparison['seven_days_ago']->booking_count; @endphp
                                <span class="badge" style="background: {{ $diff >= 0 ? '#D1FAE5' : '#FEE2E2' }}; color: {{ $diff >= 0 ? '#065F46' : '#991B1B' }};">
                                    {{ $diff >= 0 ? '+' : '' }}{{ $diff }} bookings
                                </span>
                            @else
                                <span style="color: var(--text-muted); font-size: 12px;">No historical comparison available</span>
                            @endif
                        </td>
                    </tr>

                    <!-- 30 Days Earlier -->
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 14px 20px; font-weight: 600;">30 Days Earlier</td>
                        <td style="padding: 14px 16px; text-align: right; color: var(--text-muted);">
                            {{ $historicalComparison['thirty_days_ago']?->snapshot_date?->toDateString() ?? 'No snapshot' }}
                        </td>
                        <td style="padding: 14px 16px; text-align: right;">{{ $historicalComparison['thirty_days_ago']?->booking_count ?? '-' }}</td>
                        <td style="padding: 14px 16px; text-align: right;">
                            {{ $historicalComparison['thirty_days_ago'] ? $historicalComparison['thirty_days_ago']->occupancy_rate . '%' : '-' }}
                        </td>
                        <td style="padding: 14px 16px; text-align: right;">
                            {{ $historicalComparison['thirty_days_ago'] ? '¥' . number_format($historicalComparison['thirty_days_ago']->revenue) : '-' }}
                        </td>
                        <td style="padding: 14px 16px; text-align: right;">
                            {{ $historicalComparison['thirty_days_ago'] ? '¥' . number_format($historicalComparison['thirty_days_ago']->adr) : '-' }}
                        </td>
                        <td style="padding: 14px 20px; text-align: right;">
                            @if($historicalComparison['thirty_days_ago'] && $historicalComparison['current'])
                                @php $diff = $historicalComparison['current']->booking_count - $historicalComparison['thirty_days_ago']->booking_count; @endphp
                                <span class="badge" style="background: {{ $diff >= 0 ? '#D1FAE5' : '#FEE2E2' }}; color: {{ $diff >= 0 ? '#065F46' : '#991B1B' }};">
                                    {{ $diff >= 0 ? '+' : '' }}{{ $diff }} bookings
                                </span>
                            @else
                                <span style="color: var(--text-muted); font-size: 12px;">No historical comparison available</span>
                            @endif
                        </td>
                    </tr>

                    <!-- Previous Year -->
                    <tr>
                        <td style="padding: 14px 20px; font-weight: 600;">Previous Year (YoY)</td>
                        <td style="padding: 14px 16px; text-align: right; color: var(--text-muted);">
                            {{ $historicalComparison['previous_year']?->snapshot_date?->toDateString() ?? 'Same date last year' }}
                        </td>
                        <td style="padding: 14px 16px; text-align: right;">{{ $historicalComparison['previous_year']?->booking_count ?? '-' }}</td>
                        <td style="padding: 14px 16px; text-align: right;">
                            {{ $historicalComparison['previous_year'] ? $historicalComparison['previous_year']->occupancy_rate . '%' : '-' }}
                        </td>
                        <td style="padding: 14px 16px; text-align: right;">
                            {{ $historicalComparison['previous_year'] ? '¥' . number_format($historicalComparison['previous_year']->revenue) : '-' }}
                        </td>
                        <td style="padding: 14px 16px; text-align: right;">
                            {{ $historicalComparison['previous_year'] ? '¥' . number_format($historicalComparison['previous_year']->adr) : '-' }}
                        </td>
                        <td style="padding: 14px 20px; text-align: right;">
                            @if($historicalComparison['previous_year'] && $historicalComparison['current'])
                                @php $diff = $historicalComparison['current']->booking_count - $historicalComparison['previous_year']->booking_count; @endphp
                                <span class="badge" style="background: {{ $diff >= 0 ? '#D1FAE5' : '#FEE2E2' }}; color: {{ $diff >= 0 ? '#065F46' : '#991B1B' }};">
                                    {{ $diff >= 0 ? '+' : '' }}{{ $diff }} bookings
                                </span>
                            @else
                                <span style="color: var(--text-muted); font-size: 12px;">No YoY comparison available</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 3: Booking Pace Curve & Channel Contribution Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
        <!-- Left: Booking Pace Lead-Time Distribution -->
        <div class="card">
            <div class="card-header" style="background: #FFFFFF;">
                <div>
                    <h3 style="margin: 0; font-size: 15px; font-weight: 700;">
                        <i class="fa-solid fa-chart-line" style="color: #6366F1;"></i> Booking Pace Lead-Time Accumulation
                    </h3>
                    <p style="font-size: 11px; color: var(--text-muted); margin: 2px 0 0;">How far in advance guests book prior to arrival date</p>
                </div>
            </div>
            <div class="card-body" style="padding: 20px;">
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @php
                        $maxPace = max(1, max(array_values($bookingPace)));
                    @endphp
                    @foreach($bookingPace as $leadBucket => $count)
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                            <span style="font-weight: 600; text-transform: capitalize;">{{ str_replace('_', ' ', $leadBucket) }}</span>
                            <span style="font-weight: 700; color: var(--text-main);">{{ $count }} bookings ({{ round(($count / max(1, array_sum($bookingPace))) * 100) }}%)</span>
                        </div>
                        <div style="height: 10px; background: #E2E8F0; border-radius: 5px; overflow: hidden;">
                            <div style="height: 100%; width: {{ ($count / $maxPace) * 100 }}%; background: #287F79; border-radius: 5px;"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right: Booking Channel Contribution -->
        <div class="card">
            <div class="card-header" style="background: #FFFFFF;">
                <div>
                    <h3 style="margin: 0; font-size: 15px; font-weight: 700;">
                        <i class="fa-solid fa-chart-pie" style="color: #10B981;"></i> Channel Contribution & Cancellations
                    </h3>
                    <p style="font-size: 11px; color: var(--text-muted); margin: 2px 0 0;">Revenue & cancellation distribution by OTA channel</p>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                            <th style="padding: 10px 16px; text-align: left;">Channel</th>
                            <th style="padding: 10px 14px; text-align: right;">Bookings</th>
                            <th style="padding: 10px 14px; text-align: right;">Revenue</th>
                            <th style="padding: 10px 16px; text-align: right;">Cancellations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($channelDistribution as $ch)
                        <tr style="border-bottom: 1px solid var(--border); font-size: 13px;">
                            <td style="padding: 12px 16px; font-weight: 600;">{{ $ch->channel?->name ?? 'Direct Web' }}</td>
                            <td style="padding: 12px 14px; text-align: right;">{{ $ch->total_bookings }}</td>
                            <td style="padding: 12px 14px; text-align: right; font-weight: 700;">¥{{ number_format($ch->total_revenue) }}</td>
                            <td style="padding: 12px 16px; text-align: right;">
                                <span class="badge" style="background: #FEE2E2; color: #991B1B;">{{ $ch->cancellations }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="padding: 20px; text-align: center; color: var(--text-muted);">
                                No channel bookings recorded for this period.
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
