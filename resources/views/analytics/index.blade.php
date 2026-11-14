@extends('layouts.app')

@section('title', 'ClearStay 4.2 - ホテル経営分析 & パフォーマンスレポート')
@section('header_title', 'ホテル経営分析 & パフォーマンスレポート (Hotel Analytics & Reports)')
@section('header_subtitle', '商業ホテル収益性、OTA手数料控除後の純売上、稼働率・ADR・RevPAR、および客室アセット運用効率の統合ダッシュボード')

@section('content')
<!-- Executive Financial & Performance KPI Metric Bar -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <!-- Gross Revenue -->
    <div class="card" style="margin-bottom: 0; border-top: 4px solid var(--primary-accent); padding: 18px;">
        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">当月総売上 (Gross)</span>
        <h3 style="font-size: 26px; font-weight: 800; color: var(--text-main); margin-top: 4px;">
            ¥{{ number_format($kpis['gross_revenue'] ?? $kpis['monthly_revenue'] ?? 0) }}
        </h3>
        <span style="font-size: 11px; color: var(--text-muted);">手数料控除前の室料総計</span>
    </div>

    <!-- Net Realized Revenue -->
    <div class="card" style="margin-bottom: 0; border-top: 4px solid #10B981; padding: 18px;">
        <span style="font-size: 11px; font-weight: 700; color: #047857; text-transform: uppercase;">純手残り売上 (Net Revenue)</span>
        <h3 style="font-size: 26px; font-weight: 800; color: #10B981; margin-top: 4px;">
            ¥{{ number_format($kpis['net_revenue'] ?? 0) }}
        </h3>
        <span style="font-size: 11px; color: #047857;">OTA手数料 (¥{{ number_format($kpis['commission_expense'] ?? 0) }}) 差引後</span>
    </div>

    <!-- Occupancy Rate -->
    <div class="card" style="margin-bottom: 0; border-top: 4px solid #059669; padding: 18px;">
        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">当月平均稼働率</span>
        <h3 style="font-size: 26px; font-weight: 800; color: #059669; margin-top: 4px;">
            {{ $kpis['occupancy_rate'] ?? 0 }}%
        </h3>
        <span style="font-size: 11px; color: var(--text-muted);">{{ $kpis['occupied_rooms'] ?? 0 }} / 全{{ $kpis['total_rooms'] ?? 0 }}室 稼働中</span>
    </div>

    <!-- ADR (Average Daily Rate) -->
    <div class="card" style="margin-bottom: 0; border-top: 4px solid var(--primary); padding: 18px;">
        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">平均客室単価 (ADR)</span>
        <h3 style="font-size: 26px; font-weight: 800; color: var(--primary); margin-top: 4px;">
            ¥{{ number_format($kpis['adr'] ?? 0) }}
        </h3>
        <span style="font-size: 11px; color: var(--text-muted);">販売室数 1室あたり平均単価</span>
    </div>

    <!-- RevPAR -->
    <div class="card" style="margin-bottom: 0; border-top: 4px solid #7C3AED; padding: 18px;">
        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">RevPAR (利用可能室収益)</span>
        <h3 style="font-size: 26px; font-weight: 800; color: #7C3AED; margin-top: 4px;">
            ¥{{ number_format($kpis['revpar'] ?? 0) }}
        </h3>
        <span style="font-size: 11px; color: var(--text-muted);">保有全室あたり収益力</span>
    </div>

    <!-- Operational Readiness Score -->
    <div class="card" style="margin-bottom: 0; border-top: 4px solid #F59E0B; padding: 18px;">
        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">客室即時提供率</span>
        <h3 style="font-size: 26px; font-weight: 800; color: #F59E0B; margin-top: 4px;">
            {{ $operations['readiness_score'] ?? 100 }}%
        </h3>
        <span style="font-size: 11px; color: var(--text-muted);">清掃済: {{ $kpis['clean_rooms'] ?? 0 }}室 / 清掃待: {{ $kpis['dirty_rooms'] ?? 0 }}室</span>
    </div>
</div>

<!-- Executive Insights Commentary Panel -->
@if(!empty($executiveInsights))
    <div class="card" style="margin-bottom: 24px; background: #F8FAFC; border-left: 6px solid var(--primary-accent); padding: 20px 24px;">
        <div style="font-size: 14px; font-weight: 800; color: var(--primary-accent); text-transform: uppercase; margin-bottom: 8px;">
            <i class="fa-solid fa-lightbulb"></i> エグゼクティブ・パフォーマンス所見 (Executive Analytical Commentary)
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
            @foreach($executiveInsights as $insight)
                <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 10px; padding: 14px 18px;">
                    <div style="font-weight: 800; font-size: 14px; color: var(--text-main); margin-bottom: 4px;">
                        {{ $insight['title'] }}
                    </div>
                    <div style="font-size: 13px; color: var(--text-muted); line-height: 1.5;">
                        {{ $insight['text'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<!-- Channel Net Profitability & Commission Deduction Matrix -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3><i class="fa-solid fa-hand-holding-dollar text-primary"></i> チャネル別純収益性 & OTA手数料控除マトリクス (Channel Net Profitability)</h3>
        <span style="font-size: 12px; color: var(--text-muted);">契約手数料スケジュールに基づく手残り利益計算</span>
    </div>
    <div class="card-body">
        <div style="overflow-x: auto;">
            <table class="table" style="width: 100%; text-align: left; border-collapse: collapse;">
                <thead>
                    <tr style="background: #F8FAFC; border-bottom: 2px solid var(--border);">
                        <th style="padding: 12px 14px; font-size: 12px; font-weight: 700;">販売チャネル</th>
                        <th style="padding: 12px 14px; font-size: 12px; font-weight: 700;">予約件数</th>
                        <th style="padding: 12px 14px; font-size: 12px; font-weight: 700;">総売上 (Gross ¥)</th>
                        <th style="padding: 12px 14px; font-size: 12px; font-weight: 700;">手数料率 (%)</th>
                        <th style="padding: 12px 14px; font-size: 12px; font-weight: 700;">OTA手数料額 (¥)</th>
                        <th style="padding: 12px 14px; font-size: 12px; font-weight: 700;">純実現売上 (Net ¥)</th>
                        <th style="padding: 12px 14px; font-size: 12px; font-weight: 700;">純利益率 (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($channelProfitability as $cp)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 12px 14px; font-weight: 800; color: var(--text-main);">
                                {{ $cp['channel_name'] }}
                            </td>
                            <td style="padding: 12px 14px; font-weight: 700;">
                                {{ $cp['booking_count'] }} 件
                            </td>
                            <td style="padding: 12px 14px; font-weight: 700;">
                                ¥{{ number_format($cp['gross_revenue']) }}
                            </td>
                            <td style="padding: 12px 14px; color: #EF4444; font-weight: 700;">
                                {{ $cp['commission_rate'] }}%
                            </td>
                            <td style="padding: 12px 14px; color: #EF4444;">
                                -¥{{ number_format($cp['commission_amount']) }}
                            </td>
                            <td style="padding: 12px 14px; font-weight: 800; color: #10B981;">
                                ¥{{ number_format($cp['net_revenue']) }}
                            </td>
                            <td style="padding: 12px 14px; font-weight: 800;">
                                <span style="background: {{ $cp['margin_percent'] >= 88 ? '#DCFCE7' : '#FEF3C7' }}; color: {{ $cp['margin_percent'] >= 88 ? '#14532D' : '#92400E' }}; padding: 3px 8px; border-radius: 6px; font-size: 12px;">
                                    {{ $cp['margin_percent'] }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">チャネル別収益データはありません</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Charts Grid: Occupancy Trend & Room Share -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
    <!-- Occupancy Line Chart -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-chart-line text-primary"></i> 稼働率推移トレンド (Occupancy Trend)</h3>
            <div style="display: flex; gap: 6px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="loadOccupancyChart(7, 7)">14 Days</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="loadOccupancyChart(14, 14)">28 Days</button>
            </div>
        </div>
        <div class="card-body">
            <div style="position: relative; height: 320px;">
                <canvas id="occupancyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Room Type Share Doughnut Chart -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-chart-pie text-primary"></i> 部屋タイプ別販売構成比</h3>
        </div>
        <div class="card-body">
            <div style="position: relative; height: 320px;">
                <canvas id="distributionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Revenue Bar Chart -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3><i class="fa-solid fa-chart-column text-primary"></i> 月次売上推移 (Monthly Revenue History)</h3>
    </div>
    <div class="card-body">
        <div style="position: relative; height: 320px;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
</div>

<!-- Room Type Financial Yield & Performance Table -->
@if(!empty($roomTypePerformance))
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3><i class="fa-solid fa-bed text-primary"></i> 部屋タイプ別・収益パフォーマンス (Room Type Performance & Yield)</h3>
        </div>
        <div class="card-body">
            <div style="overflow-x: auto;">
                <table class="table" style="width: 100%; text-align: left;">
                    <thead>
                        <tr style="background: #F8FAFC;">
                            <th style="padding: 10px 14px; font-size: 12px; font-weight: 700;">部屋タイプ</th>
                            <th style="padding: 10px 14px; font-size: 12px; font-weight: 700;">保有客室数</th>
                            <th style="padding: 10px 14px; font-size: 12px; font-weight: 700;">基準単価 (¥)</th>
                            <th style="padding: 10px 14px; font-size: 12px; font-weight: 700;">当月販売室数</th>
                            <th style="padding: 10px 14px; font-size: 12px; font-weight: 700;">総売上額 (¥)</th>
                            <th style="padding: 10px 14px; font-size: 12px; font-weight: 700;">実績ADR (¥)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roomTypePerformance as $rtp)
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px 14px; font-weight: 800;">{{ $rtp['name'] }}</td>
                                <td style="padding: 12px 14px;">{{ $rtp['total_rooms'] }} 室</td>
                                <td style="padding: 12px 14px;">¥{{ number_format($rtp['base_price']) }}</td>
                                <td style="padding: 12px 14px; font-weight: 700;">{{ $rtp['bookings_sold'] }} 室</td>
                                <td style="padding: 12px 14px; font-weight: 800; color: var(--primary);">¥{{ number_format($rtp['total_revenue']) }}</td>
                                <td style="padding: 12px 14px; font-weight: 700;">¥{{ number_format($rtp['realized_adr']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@endsection

@push('scripts')
<script>
    let occupancyChartInstance = null;
    let distributionChartInstance = null;
    let revenueChartInstance = null;

    // Load Charts via jQuery AJAX
    $(document).ready(function () {
        loadOccupancyChart(7, 7);
        loadDistributionChart();
        loadRevenueChart(6);
    });

    function loadOccupancyChart(daysBack, daysForward) {
        $.ajax({
            url: '{{ route("analytics.occupancy") }}',
            data: { days_back: daysBack, days_forward: daysForward },
            success: function (data) {
                const ctx = document.getElementById('occupancyChart').getContext('2d');
                if (occupancyChartInstance) {
                    occupancyChartInstance.destroy();
                }

                occupancyChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: '稼働率 (%)',
                                data: data.occupancy_percentages,
                                borderColor: '#059669',
                                backgroundColor: 'rgba(5, 150, 105, 0.1)',
                                tension: 0.3,
                                fill: true,
                                yAxisID: 'y'
                            },
                            {
                                label: '販売室数',
                                data: data.rooms_sold,
                                borderColor: '#2563eb',
                                borderDash: [5, 5],
                                tension: 0.3,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                max: 100,
                                min: 0,
                                ticks: { callback: v => v + '%' }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                min: 0,
                                max: Math.max(...data.rooms_sold, 10),
                                grid: { drawOnChartArea: false }
                            }
                        }
                    }
                });
            }
        });
    }

    function loadDistributionChart() {
        $.ajax({
            url: '{{ route("analytics.distribution") }}',
            success: function (data) {
                const ctx = document.getElementById('distributionChart').getContext('2d');
                if (distributionChartInstance) {
                    distributionChartInstance.destroy();
                }

                distributionChartInstance = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.booking_counts,
                            backgroundColor: ['#1e40af', '#059669', '#d97706', '#7c3aed', '#db2777', '#0891b2']
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
            }
        });
    }

    function loadRevenueChart(months) {
        $.ajax({
            url: '{{ route("analytics.revenue") }}',
            data: { months: months },
            success: function (data) {
                const ctx = document.getElementById('revenueChart').getContext('2d');
                if (revenueChartInstance) {
                    revenueChartInstance.destroy();
                }

                revenueChartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: '月間室料売上 (¥)',
                            data: data.revenue,
                            backgroundColor: '#287F79',
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: v => '¥' + Number(v).toLocaleString() }
                            }
                        }
                    }
                });
            }
        });
    }
</script>
@endpush
