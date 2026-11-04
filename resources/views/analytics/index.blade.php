@extends('layouts.app')

@section('title', __('clearstay.analytics.title'))
@section('header_title', __('clearstay.analytics.title'))
@section('header_subtitle', __('clearstay.analytics.subtitle'))

@section('content')
<!-- KPI Metric Bar -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 18px;">
            <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Average Daily Rate (ADR)</span>
            <h3 style="font-size: 26px; font-weight: 800; color: var(--primary); margin-top: 6px;">¥{{ number_format($kpis['adr'] ?? 0) }}</h3>
            <span style="font-size: 11px; color: var(--text-muted);">Per occupied room night</span>
        </div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 18px;">
            <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">RevPAR</span>
            <h3 style="font-size: 26px; font-weight: 800; color: #7c3aed; margin-top: 6px;">¥{{ number_format($kpis['revpar'] ?? 0) }}</h3>
            <span style="font-size: 11px; color: var(--text-muted);">Per available room</span>
        </div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 18px;">
            <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Current Occupancy</span>
            <h3 style="font-size: 26px; font-weight: 800; color: #059669; margin-top: 6px;">{{ $kpis['occupancy_rate'] ?? 0 }}%</h3>
            <span style="font-size: 11px; color: var(--text-muted);">{{ $kpis['occupied_rooms'] ?? 0 }} of {{ $kpis['total_rooms'] ?? 0 }} rooms active</span>
        </div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 18px;">
            <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Monthly Revenue</span>
            <h3 style="font-size: 26px; font-weight: 800; color: #d97706; margin-top: 6px;">¥{{ number_format($kpis['monthly_revenue'] ?? 0) }}</h3>
            <span style="font-size: 11px; color: var(--text-muted);">This month's completed & booked</span>
        </div>
    </div>
</div>

<!-- Charts Grid -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
    <!-- Occupancy Line Chart -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-chart-line text-primary"></i> {{ __('clearstay.analytics.occupancy_trend') }}</h3>
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
            <h3><i class="fa-solid fa-chart-pie text-primary"></i> Room Share</h3>
        </div>
        <div class="card-body">
            <div style="position: relative; height: 320px;">
                <canvas id="distributionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Revenue Bar Chart -->
<div class="card">
    <div class="card-header">
        <h3><i class="fa-solid fa-chart-column text-primary"></i> {{ __('clearstay.analytics.monthly_revenue') }}</h3>
    </div>
    <div class="card-body">
        <div style="position: relative; height: 320px;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
</div>
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
            url: "{{ route('analytics.occupancy') }}",
            method: 'GET',
            data: { days_back: daysBack, days_forward: daysForward },
            success: function (res) {
                const ctx = document.getElementById('occupancyChart').getContext('2d');
                if (occupancyChartInstance) {
                    occupancyChartInstance.destroy();
                }

                occupancyChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: res.labels,
                        datasets: [
                            {
                                label: 'Occupancy Rate (%)',
                                data: res.occupancy_percentages,
                                borderColor: '#0284c7',
                                backgroundColor: 'rgba(2, 132, 199, 0.1)',
                                tension: 0.35,
                                fill: true,
                                borderWidth: 3,
                                pointBackgroundColor: '#0284c7',
                                pointRadius: 4,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Rooms Sold',
                                data: res.rooms_sold,
                                borderColor: '#10b981',
                                backgroundColor: 'transparent',
                                borderDash: [5, 5],
                                borderWidth: 2,
                                pointRadius: 3,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            y: {
                                min: 0,
                                max: 100,
                                title: { display: true, text: 'Occupancy (%)' },
                                ticks: { callback: v => v + '%' }
                            },
                            y1: {
                                position: 'right',
                                min: 0,
                                title: { display: true, text: 'Rooms Count' },
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
            url: "{{ route('analytics.distribution') }}",
            method: 'GET',
            success: function (res) {
                const ctx = document.getElementById('distributionChart').getContext('2d');
                if (distributionChartInstance) {
                    distributionChartInstance.destroy();
                }

                distributionChartInstance = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: res.labels,
                        datasets: [{
                            data: res.booking_counts,
                            backgroundColor: [
                                '#0284c7', '#38bdf8', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff'
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
            url: "{{ route('analytics.revenue') }}",
            method: 'GET',
            data: { months: months },
            success: function (res) {
                const ctx = document.getElementById('revenueChart').getContext('2d');
                if (revenueChartInstance) {
                    revenueChartInstance.destroy();
                }

                revenueChartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: res.labels,
                        datasets: [
                            {
                                label: 'Monthly Revenue (JPY)',
                                data: res.revenue,
                                backgroundColor: 'rgba(2, 132, 199, 0.85)',
                                borderRadius: 8,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Bookings Count',
                                data: res.reservations_count,
                                type: 'line',
                                borderColor: '#f59e0b',
                                borderWidth: 3,
                                pointBackgroundColor: '#f59e0b',
                                pointRadius: 5,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                title: { display: true, text: 'Revenue (¥)' },
                                ticks: { callback: v => '¥' + Number(v).toLocaleString() }
                            },
                            y1: {
                                position: 'right',
                                min: 0,
                                title: { display: true, text: 'Reservations' },
                                grid: { drawOnChartArea: false }
                            }
                        }
                    }
                });
            }
        });
    }
</script>
@endpush
