@extends('layouts.app')

@section('title', __('clearstay.nav.dashboard'))
@section('header_title', $hotel ? $hotel->name : __('clearstay.nav.dashboard'))
@section('header_subtitle', __('clearstay.description'))

@section('content')
<!-- KPI Metric Cards Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 28px;">
    <!-- Occupancy Rate -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">{{ __('clearstay.kpis.occupancy_rate') }}</span>
                    <h3 style="font-size: 28px; font-weight: 800; color: var(--primary); margin-top: 6px;">{{ $kpis['occupancy_rate'] }}%</h3>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="fa-solid fa-percent"></i>
                </div>
            </div>
            <div style="margin-top: 12px; background: #e2e8f0; border-radius: 9999px; height: 6px; overflow: hidden;">
                <div style="background: var(--primary); height: 100%; width: {{ min(100, $kpis['occupancy_rate']) }}%;"></div>
            </div>
            <p style="font-size: 11px; color: var(--text-muted); margin-top: 8px;">{{ $kpis['occupied_rooms'] }} of {{ $kpis['total_rooms'] }} rooms active today</p>
        </div>
    </div>

    <!-- Available Rooms -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">{{ __('clearstay.kpis.available_rooms') }}</span>
                    <h3 style="font-size: 28px; font-weight: 800; color: #059669; margin-top: 6px;">{{ $kpis['available_rooms'] }}</h3>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="fa-solid fa-bed"></i>
                </div>
            </div>
            <p style="font-size: 11px; color: var(--text-muted); margin-top: 26px;">Clean & ready for immediate walk-in</p>
        </div>
    </div>

    <!-- Today Arrivals -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">{{ __('clearstay.kpis.today_arrivals') }}</span>
                    <h3 style="font-size: 28px; font-weight: 800; color: #d97706; margin-top: 6px;">{{ $kpis['today_arrivals'] }}</h3>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="fa-solid fa-plane-arrival"></i>
                </div>
            </div>
            <p style="font-size: 11px; color: var(--text-muted); margin-top: 26px;">Expected front-desk check-ins today</p>
        </div>
    </div>

    <!-- Monthly Revenue & ADR -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">{{ __('clearstay.kpis.monthly_revenue') }}</span>
                    <h3 style="font-size: 24px; font-weight: 800; color: var(--text-dark); margin-top: 6px;">¥{{ number_format($kpis['monthly_revenue']) }}</h3>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="fa-solid fa-yen-sign"></i>
                </div>
            </div>
            <p style="font-size: 11px; color: var(--text-muted); margin-top: 14px;">
                ADR: <strong>¥{{ number_format($kpis['adr']) }}</strong> | RevPAR: <strong>¥{{ number_format($kpis['revpar']) }}</strong>
            </p>
        </div>
    </div>
</div>

<!-- Two-Column Operational Layout -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Today's Expected Arrivals -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3><i class="fa-solid fa-door-open text-primary"></i> {{ __('clearstay.kpis.today_arrivals') }}</h3>
            </div>
            <a href="{{ route('reservations.index', ['status' => 'confirmed']) }}" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Nights</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($todayArrivals as $res)
                        <tr>
                            <td>
                                <a href="javascript:void(0)" onclick="openReservationModal({{ $res->id }})" style="color: var(--primary); font-weight: 700; text-decoration: none;">
                                    {{ $res->confirmation_code }}
                                </a>
                            </td>
                            <td>
                                <strong>{{ $res->guest->full_name }}</strong>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ $res->guest->phone ?? 'No phone' }}</div>
                            </td>
                            <td><span class="badge badge-inspecting">Room {{ $res->room->room_number }}</span></td>
                            <td>{{ $res->nights }} nts</td>
                            <td>
                                <form action="{{ route('reservations.check_in', $res->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" style="padding: 4px 10px;">
                                        <i class="fa-solid fa-key"></i> Check In
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                <i class="fa-solid fa-calendar-check" style="font-size: 24px; opacity: 0.4;"></i>
                                <div style="margin-top: 6px;">No pending arrivals scheduled for today.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Today's Expected Departures -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3><i class="fa-solid fa-door-closed" style="color: #ef4444;"></i> {{ __('clearstay.kpis.today_departures') }}</h3>
            </div>
            <a href="{{ route('reservations.index', ['status' => 'checked_in']) }}" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Balance</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($todayDepartures as $res)
                        <tr>
                            <td>
                                <a href="javascript:void(0)" onclick="openReservationModal({{ $res->id }})" style="color: var(--primary); font-weight: 700; text-decoration: none;">
                                    {{ $res->confirmation_code }}
                                </a>
                            </td>
                            <td>
                                <strong>{{ $res->guest->full_name }}</strong>
                            </td>
                            <td><span class="badge badge-occupied">Room {{ $res->room->room_number }}</span></td>
                            <td>¥{{ number_format($res->total_price) }}</td>
                            <td>
                                <form action="{{ route('reservations.check_out', $res->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm" style="padding: 4px 10px;">
                                        <i class="fa-solid fa-receipt"></i> Check Out
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                <i class="fa-solid fa-bell-concierge" style="font-size: 24px; opacity: 0.4;"></i>
                                <div style="margin-top: 6px;">No guest check-outs remaining today.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Housekeeping Urgent Tasks & Audit Logs -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px;">
    <!-- Housekeeping Tasks -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-broom text-primary"></i> Pending Housekeeping Tasks</h3>
            <a href="{{ route('housekeeping.index') }}" class="btn btn-secondary btn-sm">Go to Cleaning Board</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($urgentTasks as $task)
                        <tr>
                            <td><strong>Room {{ $task->room->room_number }}</strong></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $task->task_type)) }}</td>
                            <td>
                                <span class="badge badge-{{ $task->priority === 'urgent' ? 'dirty' : 'inspecting' }}">
                                    {{ strtoupper($task->priority) }}
                                </span>
                            </td>
                            <td><span class="badge badge-{{ $task->status }}">{{ strtoupper($task->status) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 24px;">
                                All rooms inspected and clear!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Security & Audit Trail -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-shield-halved" style="color: #059669;"></i> Real-Time Audit & Security Trail</h3>
        </div>
        <div style="padding: 16px;">
            <ul style="list-style: none;">
                @foreach($recentLogs as $log)
                    <li style="padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 13px; display: flex; justify-content: space-between;">
                        <div>
                            <strong>{{ $log->action }}</strong>
                            <div style="font-size: 11px; color: var(--text-muted);">
                                By: {{ $log->user ? $log->user->name : 'System' }} | IP: {{ $log->ip_address }}
                            </div>
                        </div>
                        <div style="font-size: 11px; color: var(--text-muted);">
                            {{ $log->created_at->diffForHumans() }}
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
