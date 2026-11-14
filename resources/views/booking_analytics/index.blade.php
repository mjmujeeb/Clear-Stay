@extends('layouts.app')

@section('title', 'ClearStay 4.2 - Booking Analytics Plus (BA+)')
@section('header_title', '予約分析 & リードタイム・予約ペース分析 (Booking Analytics Plus)')
@section('header_subtitle', 'TEMAIRAZU JIDO BA+ oriented booking monitor, multi-horizon pace curves, lead-time distribution, and forward demand pickup.')

@section('content')
<div class="analytics-container">
    <!-- Filter Bar -->
    <div class="card" style="margin-bottom: 24px; padding: 18px 24px; background: #FFFFFF;">
        <form method="GET" action="{{ route('booking_analytics.index') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)) 120px auto; gap: 14px; align-items: flex-end;">
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
            <div>
                <a href="{{ route('booking_analytics.export_csv') }}" class="btn btn-secondary" style="padding: 9px 14px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-file-csv text-success"></i> Export CSV
                </a>
            </div>
        </form>
    </div>

    <!-- Booking & Pickup KPIs Strip -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <!-- KPI 1: Rooms Sold & Bookings -->
        <div class="card" style="margin-bottom: 0; border-top: 4px solid #3B82F6; padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Confirmed Bookings</div>
            <div style="font-size: 28px; font-weight: 800; color: var(--text-main); margin-top: 4px;">
                {{ $metrics['rooms_sold'] }} <span style="font-size: 14px; color: var(--text-muted);">stays</span>
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                {{ $metrics['total_available_room_nights'] }} available room nights
            </div>
        </div>

        <!-- KPI 2: Net Booking Pickup (Trailing 7d) -->
        <div class="card" style="margin-bottom: 0; border-top: 4px solid #10B981; padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Net Pickup (7 Days)</div>
            <div style="font-size: 28px; font-weight: 800; color: {{ $metrics['net_pickup_7d'] >= 0 ? '#10B981' : '#EF4444' }}; margin-top: 4px;">
                {{ $metrics['net_pickup_7d'] >= 0 ? '+' : '' }}{{ $metrics['net_pickup_7d'] }}
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                30-day pickup: {{ $metrics['net_pickup_30d'] >= 0 ? '+' : '' }}{{ $metrics['net_pickup_30d'] }}
            </div>
        </div>

        <!-- KPI 3: Average Lead Time -->
        <div class="card" style="margin-bottom: 0; border-top: 4px solid #8B5CF6; padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Avg Booking Lead Time</div>
            <div style="font-size: 28px; font-weight: 800; color: #8B5CF6; margin-top: 4px;">
                {{ $metrics['avg_lead_time'] }} <span style="font-size: 14px; color: var(--text-muted);">days</span>
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                Reservation advance window
            </div>
        </div>

        <!-- KPI 4: Average Length of Stay (ALOS) -->
        <div class="card" style="margin-bottom: 0; border-top: 4px solid #F59E0B; padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Avg Length of Stay (ALOS)</div>
            <div style="font-size: 28px; font-weight: 800; color: #F59E0B; margin-top: 4px;">
                {{ $metrics['avg_length_of_stay'] }} <span style="font-size: 14px; color: var(--text-muted);">nights</span>
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                Per confirmed reservation
            </div>
        </div>

        <!-- KPI 5: Cancellation Rate -->
        <div class="card" style="margin-bottom: 0; border-top: 4px solid #EF4444; padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Cancellation Rate</div>
            <div style="font-size: 28px; font-weight: 800; color: #EF4444; margin-top: 4px;">
                {{ $metrics['cancellation_rate'] }}%
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                {{ $metrics['cancellations_count'] }} cancellations recorded
            </div>
        </div>

        <!-- KPI 6: Future OTB Room Nights -->
        <div class="card" style="margin-bottom: 0; border-top: 4px solid #06B6D4; padding: 18px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">On The Books (30d)</div>
            <div style="font-size: 28px; font-weight: 800; color: #06B6D4; margin-top: 4px;">
                {{ $metrics['future_room_nights_otb'] }} <span style="font-size: 14px; color: var(--text-muted);">nights</span>
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                Forward committed demand
            </div>
        </div>
    </div>

    <!-- Multi-Horizon Historical Pace Comparison (TEMAIRAZU JIDO BA+ Style) -->
    <div class="card" style="margin-bottom: 24px; padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
            <div>
                <h3 style="font-size: 18px; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-clock-rotate-left text-primary"></i> 4時点スナップショット比較 (Multi-Horizon Pace Comparison)
                </h3>
                <p style="font-size: 13px; color: var(--text-muted); margin-top: 2px;">
                    特定宿泊日（{{ $targetStayDate }}）の予約蓄積状況を、「現在」「7日前」「30日前」「1年前」の4つの時点で比較します。
                </p>
            </div>

            <!-- Target Stay Date Selector & Manual Capture -->
            <form method="GET" action="{{ route('booking_analytics.index') }}" style="display: flex; gap: 10px; align-items: center;">
                <input type="hidden" name="start_date" value="{{ $startDate }}">
                <input type="hidden" name="end_date" value="{{ $endDate }}">
                <span style="font-size: 12px; font-weight: 700; color: var(--text-muted);">Target Stay:</span>
                <input type="date" name="target_stay_date" value="{{ $targetStayDate }}" class="form-control" style="padding: 6px 10px; font-size: 13px;">
                <button type="submit" class="btn btn-secondary btn-sm">照会</button>
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table class="table" style="width: 100%; text-align: left; border-collapse: collapse;">
                <thead>
                    <tr style="background: #F8FAFC; border-bottom: 2px solid var(--border);">
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">比較時点 (Horizon)</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">予約室数 (Sold)</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">稼働率 (Occupancy)</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">ADR (平均単価)</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">予約売上 (Revenue)</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">スナップショット記録日</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Current Position -->
                    <tr style="border-bottom: 1px solid var(--border); background: #EFF6FF;">
                        <td style="padding: 14px 16px; font-weight: 800; color: #1E40AF;">
                            <i class="fa-solid fa-circle text-primary" style="font-size: 8px;"></i> 現在時点 (Current)
                        </td>
                        <td style="padding: 14px 16px; font-weight: 800; font-size: 16px;">
                            {{ $historicalComparison['current']->occupied_rooms ?? 0 }} 室
                        </td>
                        <td style="padding: 14px 16px; font-weight: 800;">
                            {{ $historicalComparison['current']->occupancy_rate ?? 0 }}%
                        </td>
                        <td style="padding: 14px 16px; font-weight: 700;">
                            ¥{{ number_format($historicalComparison['current']->adr ?? 0) }}
                        </td>
                        <td style="padding: 14px 16px; font-weight: 800; color: #1E40AF;">
                            ¥{{ number_format($historicalComparison['current']->revenue ?? 0) }}
                        </td>
                        <td style="padding: 14px 16px; font-size: 13px; color: var(--text-muted);">
                            {{ $historicalComparison['current']->snapshot_date ?? now()->toDateString() }}
                        </td>
                    </tr>

                    <!-- 7 Days Ago -->
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 14px 16px; font-weight: 700; color: var(--text-main);">7日前時点 (7 Days Prior)</td>
                        <td style="padding: 14px 16px;">{{ $historicalComparison['seven_days_ago']->occupied_rooms ?? '—' }} 室</td>
                        <td style="padding: 14px 16px;">{{ isset($historicalComparison['seven_days_ago']) ? $historicalComparison['seven_days_ago']->occupancy_rate . '%' : '—' }}</td>
                        <td style="padding: 14px 16px;">{{ isset($historicalComparison['seven_days_ago']) ? '¥' . number_format($historicalComparison['seven_days_ago']->adr) : '—' }}</td>
                        <td style="padding: 14px 16px;">{{ isset($historicalComparison['seven_days_ago']) ? '¥' . number_format($historicalComparison['seven_days_ago']->revenue) : '—' }}</td>
                        <td style="padding: 14px 16px; font-size: 13px; color: var(--text-muted);">{{ $historicalComparison['seven_days_ago']->snapshot_date ?? '未取得' }}</td>
                    </tr>

                    <!-- 30 Days Ago -->
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 14px 16px; font-weight: 700; color: var(--text-main);">30日前時点 (30 Days Prior)</td>
                        <td style="padding: 14px 16px;">{{ $historicalComparison['thirty_days_ago']->occupied_rooms ?? '—' }} 室</td>
                        <td style="padding: 14px 16px;">{{ isset($historicalComparison['thirty_days_ago']) ? $historicalComparison['thirty_days_ago']->occupancy_rate . '%' : '—' }}</td>
                        <td style="padding: 14px 16px;">{{ isset($historicalComparison['thirty_days_ago']) ? '¥' . number_format($historicalComparison['thirty_days_ago']->adr) : '—' }}</td>
                        <td style="padding: 14px 16px;">{{ isset($historicalComparison['thirty_days_ago']) ? '¥' . number_format($historicalComparison['thirty_days_ago']->revenue) : '—' }}</td>
                        <td style="padding: 14px 16px; font-size: 13px; color: var(--text-muted);">{{ $historicalComparison['thirty_days_ago']->snapshot_date ?? '未取得' }}</td>
                    </tr>

                    <!-- Previous Year -->
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 14px 16px; font-weight: 700; color: var(--text-main);">前年同日同期 (1 Year Prior)</td>
                        <td style="padding: 14px 16px;">{{ $historicalComparison['previous_year']->occupied_rooms ?? '—' }} 室</td>
                        <td style="padding: 14px 16px;">{{ isset($historicalComparison['previous_year']) ? $historicalComparison['previous_year']->occupancy_rate . '%' : '—' }}</td>
                        <td style="padding: 14px 16px;">{{ isset($historicalComparison['previous_year']) ? '¥' . number_format($historicalComparison['previous_year']->adr) : '—' }}</td>
                        <td style="padding: 14px 16px;">{{ isset($historicalComparison['previous_year']) ? '¥' . number_format($historicalComparison['previous_year']->revenue) : '—' }}</td>
                        <td style="padding: 14px 16px; font-size: 13px; color: var(--text-muted);">{{ $historicalComparison['previous_year']->snapshot_date ?? '前年データなし' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Charts Grid: Lead Time & Length of Stay & Demand Forecast -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
        <!-- Chart 1: Lead Time Distribution Bar Chart -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-chart-column text-primary"></i> 予約リードタイム分布 (Lead-Time Cohorts)</h3>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 300px;">
                    <canvas id="leadTimeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 2: Length of Stay Cohorts Doughnut Chart -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-chart-pie text-primary"></i> 宿泊日数構成比 (Length of Stay)</h3>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 300px;">
                    <canvas id="losChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 3: Forward 30-Day On The Books (OTB) Demand Curve -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3><i class="fa-solid fa-chart-line text-primary"></i> 今後30日間の確定予約室数予測 (30-Day On The Books Demand Curve)</h3>
        </div>
        <div class="card-body">
            <div style="position: relative; height: 300px;">
                <canvas id="otbChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Channel Contribution & Booking Share -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3><i class="fa-solid fa-share-nodes text-primary"></i> 販売チャネル別・予約獲得状況 (Channel Share & Cancellations)</h3>
        </div>
        <div class="card-body">
            <div style="overflow-x: auto;">
                <table class="table" style="width: 100%; text-align: left;">
                    <thead>
                        <tr style="background: #F8FAFC;">
                            <th style="padding: 10px 14px; font-size: 12px; font-weight: 700;">チャネル名</th>
                            <th style="padding: 10px 14px; font-size: 12px; font-weight: 700;">獲得予約件数</th>
                            <th style="padding: 10px 14px; font-size: 12px; font-weight: 700;">予約総売上 (¥)</th>
                            <th style="padding: 10px 14px; font-size: 12px; font-weight: 700;">キャンセル件数</th>
                            <th style="padding: 10px 14px; font-size: 12px; font-weight: 700;">キャンセル率</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($channelDistribution as $cd)
                            @php
                                $total = $cd->total_bookings + $cd->cancellations;
                                $rate = $total > 0 ? round(($cd->cancellations / $total) * 100, 1) : 0;
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px 14px; font-weight: 700;">{{ $cd->channel->name ?? 'Direct Web' }}</td>
                                <td style="padding: 12px 14px; font-weight: 800;">{{ $cd->total_bookings }} 件</td>
                                <td style="padding: 12px 14px;">¥{{ number_format($cd->total_revenue) }}</td>
                                <td style="padding: 12px 14px; color: #EF4444;">{{ $cd->cancellations }} 件</td>
                                <td style="padding: 12px 14px; font-weight: 700; color: {{ $rate > 20 ? '#EF4444' : '#10B981' }};">{{ $rate }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 24px; color: var(--text-muted);">データがありません</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Lead Time Distribution Chart
        const leadCtx = document.getElementById('leadTimeChart').getContext('2d');
        new Chart(leadCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($leadTimeDistribution['labels']) !!},
                datasets: [{
                    label: 'Reservations',
                    data: {!! json_encode($leadTimeDistribution['data']) !!},
                    backgroundColor: '#8B5CF6',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });

        // 2. Length of Stay Cohorts Chart
        const losCtx = document.getElementById('losChart').getContext('2d');
        new Chart(losCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($lengthOfStayDistribution['labels']) !!},
                datasets: [{
                    data: {!! json_encode($lengthOfStayDistribution['data']) !!},
                    backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // 3. 30-Day On The Books Demand Curve
        const otbCtx = document.getElementById('otbChart').getContext('2d');
        new Chart(otbCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($futureOnTheBooks['labels']) !!},
                datasets: [{
                    label: 'Reserved Rooms (On The Books)',
                    data: {!! json_encode($futureOnTheBooks['data']) !!},
                    borderColor: '#06B6D4',
                    backgroundColor: 'rgba(6, 182, 212, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    });
</script>
@endpush
