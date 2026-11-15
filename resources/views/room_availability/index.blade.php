@extends('layouts.app')

@section('title', app()->getLocale() === 'ja' ? '客室在庫一覧 (マルチチャネル可視化) - ClearStay' : 'Live Room Availability Matrix - ClearStay')

@section('content')
<div class="content-header" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <span class="badge" style="background: #287F79; color: #fff; font-size: 11px; font-weight: 800; padding: 4px 10px; letter-spacing: 0.05em;">CLEARSTAY 5.0 ENTERPRISE</span>
                <span class="badge" style="background: #0284C7; color: #fff; font-size: 11px; font-weight: 700; padding: 4px 10px;">SIMULATED OTA NETWORK</span>
            </div>
            <h1 style="font-size: 26px; font-weight: 800; color: var(--text-dark); margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-table-cells" style="color: #287F79;"></i>
                {{ app()->getLocale() === 'ja' ? '客室在庫一覧・マルチチャネル可視化' : 'Live Room Availability Matrix' }}
            </h1>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                {{ app()->getLocale() === 'ja' 
                    ? 'ホテルPMSの中央在庫と、接続された各シミュレートOTA（Booking.com、Agoda、Expedia、楽天、じゃらん）の独立した実在庫を対比確認・不整合検知します。' 
                    : 'View authoritative central hotel PMS availability alongside independent stateful inventory across all simulated distribution channels.' }}
            </p>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-secondary" onclick="refreshMatrixData()" id="refreshBtn">
                <i class="fa-solid fa-arrows-rotate"></i>
                <span>{{ app()->getLocale() === 'ja' ? '在庫更新' : 'Refresh Data' }}</span>
            </button>
            <a href="{{ route('room_availability.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-secondary">
                <i class="fa-solid fa-file-csv" style="color: #10B981;"></i>
                <span>{{ app()->getLocale() === 'ja' ? 'CSV出力' : 'Export CSV' }}</span>
            </a>
            <a href="{{ route('room_availability.export', array_merge(request()->query(), ['format' => 'print'])) }}" target="_blank" class="btn btn-secondary">
                <i class="fa-solid fa-print"></i>
                <span>{{ app()->getLocale() === 'ja' ? '印刷表示' : 'Print View' }}</span>
            </a>
            <a href="{{ route('simulators.index') }}" class="btn btn-primary" style="background: #183B50;">
                <i class="fa-solid fa-network-wired"></i>
                <span>{{ app()->getLocale() === 'ja' ? 'シミュレータ管理' : 'Simulator Lab' }}</span>
            </a>
        </div>
    </div>
</div>

<!-- Simulation Environment Notice -->
<div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-left: 4px solid #3B82F6; padding: 12px 18px; border-radius: 8px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <i class="fa-solid fa-circle-info" style="color: #2563EB; font-size: 18px;"></i>
        <div style="font-size: 13px; color: #1E40AF;">
            <strong>{{ app()->getLocale() === 'ja' ? 'シミュレーション環境稼働中' : 'Stateful Simulation Mode Active' }}:</strong>
            {{ app()->getLocale() === 'ja'
                ? '外部OTAチャンネルは独立したリモート在庫テーブル（simulated_partner_inventory）で個別管理されています。各社独自のアロットメントや在庫減少が忠実に再現されます。'
                : 'External OTA channels maintain independent simulated remote inventories. Discrepancies reflect actual stored differences between PMS and partner channels.' }}
        </div>
    </div>
    <span class="badge" style="background: #DBEAFE; color: #1E40AF; font-size: 11px;">Property: {{ $hotel->name }}</span>
</div>

<!-- KPI Dashboard Cards -->
@if($viewMode === 'single' && isset($matrixData['kpi']))
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="margin: 0; padding: 18px; border-left: 4px solid #287F79;">
        <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">{{ app()->getLocale() === 'ja' ? '監視対象客室タイプ' : 'Room Types Monitored' }}</div>
        <div style="font-size: 28px; font-weight: 800; color: var(--text-dark); margin-top: 4px;" id="kpiRoomTypes">
            {{ $matrixData['kpi']['total_room_types'] }}
        </div>
        <div style="font-size: 12px; color: #64748B; margin-top: 4px;">{{ $matrixData['kpi']['total_pms_capacity'] }} physical total rooms</div>
    </div>

    <div class="card" style="margin: 0; padding: 18px; border-left: 4px solid #10B981;">
        <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">{{ app()->getLocale() === 'ja' ? '中央PMS空室残数' : 'Central PMS Available' }}</div>
        <div style="font-size: 28px; font-weight: 800; color: #047857; margin-top: 4px;" id="kpiPmsAvail">
            {{ $matrixData['kpi']['total_pms_available'] }}
        </div>
        <div style="font-size: 12px; color: #64748B; margin-top: 4px;">Reserved: {{ $matrixData['kpi']['total_pms_reserved'] }} | Maint: {{ $matrixData['kpi']['total_pms_maintenance'] }}</div>
    </div>

    <div class="card" style="margin: 0; padding: 18px; border-left: 4px solid #3B82F6;">
        <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">{{ app()->getLocale() === 'ja' ? '完全整合チャンネルセル' : 'Matched Channel Cells' }}</div>
        <div style="font-size: 28px; font-weight: 800; color: #1D4ED8; margin-top: 4px;" id="kpiMatched">
            {{ $matrixData['kpi']['matched_cells'] }}
        </div>
        <div style="font-size: 12px; color: #64748B; margin-top: 4px;">PMS and channel inventory agree</div>
    </div>

    <div class="card" style="margin: 0; padding: 18px; border-left: 4px solid #F59E0B;">
        <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">{{ app()->getLocale() === 'ja' ? '検知された在庫不整合' : 'Discrepancies' }}</div>
        <div style="font-size: 28px; font-weight: 800; color: #B45309; margin-top: 4px;" id="kpiDiscrepancies">
            {{ $matrixData['kpi']['discrepancy_cells'] }}
        </div>
        <div style="font-size: 12px; color: #64748B; margin-top: 4px;">Quantity or mapping difference</div>
    </div>

    <div class="card" style="margin: 0; padding: 18px; border-left: 4px solid #EF4444;">
        <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">{{ app()->getLocale() === 'ja' ? '重大オーバーセルリスク' : 'Critical Oversell Risk' }}</div>
        <div style="font-size: 28px; font-weight: 800; color: #B91C1C; margin-top: 4px;" id="kpiOversell">
            {{ $matrixData['kpi']['oversell_risk_cells'] }}
        </div>
        <div style="font-size: 12px; color: #64748B; margin-top: 4px;">Channel selling > PMS available!</div>
    </div>
</div>
@endif

<!-- Filter & Control Toolbar -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 20px;">
        <form method="GET" action="{{ route('room_availability.index') }}" id="matrixFilterForm">
            <!-- View Mode Switcher -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 18px; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; gap: 8px; align-items: center;">
                    <span style="font-size: 13px; font-weight: 700; color: var(--text-dark); margin-right: 6px;">{{ app()->getLocale() === 'ja' ? '表示モード:' : 'View Mode:' }}</span>
                    <label class="btn {{ $viewMode === 'single' ? 'btn-primary' : 'btn-secondary' }}" style="cursor: pointer; padding: 7px 14px; font-size: 13px; font-weight: 600;">
                        <input type="radio" name="view" value="single" {{ $viewMode === 'single' ? 'checked' : '' }} onchange="this.form.submit()" style="display: none;">
                        <i class="fa-regular fa-calendar"></i> {{ app()->getLocale() === 'ja' ? '単一宿泊日' : 'Single Stay Date' }}
                    </label>
                    <label class="btn {{ $viewMode === 'multi' ? 'btn-primary' : 'btn-secondary' }}" style="cursor: pointer; padding: 7px 14px; font-size: 13px; font-weight: 600;">
                        <input type="radio" name="view" value="multi" {{ $viewMode === 'multi' ? 'checked' : '' }} onchange="this.form.submit()" style="display: none;">
                        <i class="fa-solid fa-calendar-days"></i> {{ app()->getLocale() === 'ja' ? '連泊・滞在期間' : 'Multi-Night Stay' }}
                    </label>
                </div>

                <div style="display: flex; align-items: center; gap: 10px; font-size: 12px; color: var(--text-muted);">
                    <i class="fa-solid fa-shield-halved" style="color: #287F79;"></i>
                    <span>Row-Locked Inventory (`FOR UPDATE`) & Composite Unique Key `(hotel_id, room_type_id, date)` Protected</span>
                </div>
            </div>

            <!-- Parameters Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; align-items: flex-end;">
                @if($viewMode === 'single')
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        <i class="fa-regular fa-calendar-check" style="color: #287F79;"></i> {{ app()->getLocale() === 'ja' ? '対象宿泊日' : 'Stay Date' }}
                    </label>
                    <input type="date" name="stay_date" value="{{ $stayDate }}" class="form-control" style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; font-weight: 600;" onchange="this.form.submit()">
                </div>
                @else
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        <i class="fa-solid fa-arrow-right-to-bracket" style="color: #287F79;"></i> {{ app()->getLocale() === 'ja' ? 'チェックイン日' : 'Check-In' }}
                    </label>
                    <input type="date" name="check_in_date" value="{{ $checkInDate }}" class="form-control" style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; font-weight: 600;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        <i class="fa-solid fa-arrow-right-from-bracket" style="color: #EF4444;"></i> {{ app()->getLocale() === 'ja' ? 'チェックアウト日' : 'Check-Out (Excluded)' }}
                    </label>
                    <input type="date" name="check_out_date" value="{{ $checkOutDate }}" class="form-control" style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; font-weight: 600;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        <i class="fa-solid fa-bed" style="color: #F59E0B;"></i> {{ app()->getLocale() === 'ja' ? '必要部屋数' : 'Requested Rooms' }}
                    </label>
                    <input type="number" min="1" max="20" name="requested_rooms" value="{{ $requestedRooms }}" class="form-control" style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; font-weight: 600;">
                </div>
                @endif

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        <i class="fa-solid fa-door-open" style="color: #6366F1;"></i> {{ app()->getLocale() === 'ja' ? '客室タイプ絞込' : 'Room Type' }}
                    </label>
                    <select name="room_type_id" class="form-control" style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px;" onchange="this.form.submit()">
                        <option value="">{{ app()->getLocale() === 'ja' ? '全客室タイプ (All)' : 'All Room Types' }}</option>
                        @foreach($roomTypes as $rt)
                            <option value="{{ $rt->id }}" {{ $roomTypeId == $rt->id ? 'selected' : '' }}>{{ $rt->name }} ({{ $rt->code ?? 'RT-'.$rt->id }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        <i class="fa-solid fa-filter" style="color: #EC4899;"></i> {{ app()->getLocale() === 'ja' ? '整合ステータス' : 'Parity Status' }}
                    </label>
                    <select name="status_filter" class="form-control" style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px;" onchange="this.form.submit()">
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>{{ app()->getLocale() === 'ja' ? '全ステータス (All)' : 'All Statuses' }}</option>
                        <option value="matched" {{ $statusFilter === 'matched' ? 'selected' : '' }}>MATCHED (完全一致)</option>
                        <option value="discrepancy" {{ $statusFilter === 'discrepancy' ? 'selected' : '' }}>DISCREPANCY (数量差異)</option>
                        <option value="oversell_risk" {{ $statusFilter === 'oversell_risk' ? 'selected' : '' }}>OVERSELL RISK (過剰販売リスク)</option>
                        <option value="low_stock" {{ $statusFilter === 'low_stock' ? 'selected' : '' }}>LOW STOCK (残少 1-2室)</option>
                        <option value="sold_out" {{ $statusFilter === 'sold_out' ? 'selected' : '' }}>SOLD OUT (満室 0室)</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; height: 38px; justify-content: center;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>{{ app()->getLocale() === 'ja' ? '条件適用' : 'Apply Filter' }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- VIEW A: SINGLE DATE AVAILABILITY MATRIX -->
@if($viewMode === 'single')
<div class="card">
    <div class="card-header" style="background: #F8FAFC; border-bottom: 2px solid #E2E8F0; padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0F1D2F; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-list-check" style="color: #287F79;"></i>
                    {{ app()->getLocale() === 'ja' ? '宿泊日別・チャネル別空室マトリクス' : 'Multi-Channel Availability Matrix' }}
                    <span style="font-size: 13px; font-weight: 600; color: #64748B;">({{ Carbon\Carbon::parse($stayDate)->format('Y年m月d日 (D)') }})</span>
                </h3>
            </div>
            <div style="display: flex; gap: 14px; font-size: 11px; font-weight: 700; align-items: center;">
                <span style="display: flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: #10B981; display: inline-block;"></span> MATCHED</span>
                <span style="display: flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: #F59E0B; display: inline-block;"></span> DISCREPANCY</span>
                <span style="display: flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: #EF4444; display: inline-block;"></span> OVERSELL RISK</span>
                <span style="display: flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: #64748B; display: inline-block;"></span> NOT MAPPED</span>
            </div>
        </div>
    </div>

    <div class="card-body" style="padding: 0; overflow-x: auto;">
        <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px; margin: 0;">
            <thead>
                <tr style="background: #0F1D2F; color: #ffffff; text-align: left; font-size: 12px; letter-spacing: 0.04em;">
                    <th style="padding: 14px 18px; min-width: 220px;">ROOM TYPE / CODE</th>
                    <th style="padding: 14px 12px; text-align: center; width: 80px;">PHYSICAL</th>
                    <th style="padding: 14px 12px; text-align: center; width: 80px;">RESERVED</th>
                    <th style="padding: 14px 12px; text-align: center; width: 80px;">MAINT</th>
                    <th style="padding: 14px 14px; text-align: center; background: #1E3A4C; width: 110px; border-left: 2px solid #38BDF8; border-right: 2px solid #38BDF8;">
                        CENTRAL PMS
                    </th>
                    <th style="padding: 14px 12px; text-align: center; min-width: 110px;">
                        <div style="font-size: 11px; color: #94A3B8;">SIMULATED</div>
                        Booking.com
                    </th>
                    <th style="padding: 14px 12px; text-align: center; min-width: 110px;">
                        <div style="font-size: 11px; color: #94A3B8;">SIMULATED</div>
                        Agoda
                    </th>
                    <th style="padding: 14px 12px; text-align: center; min-width: 110px;">
                        <div style="font-size: 11px; color: #94A3B8;">SIMULATED</div>
                        Expedia
                    </th>
                    <th style="padding: 14px 12px; text-align: center; min-width: 110px;">
                        <div style="font-size: 11px; color: #94A3B8;">SIMULATED</div>
                        Rakuten Travel
                    </th>
                    <th style="padding: 14px 12px; text-align: center; min-width: 110px;">
                        <div style="font-size: 11px; color: #94A3B8;">SIMULATED</div>
                        Jalan.net
                    </th>
                    <th style="padding: 14px 12px; text-align: center; min-width: 110px;">
                        <div style="font-size: 11px; color: #94A3B8;">SIMULATED</div>
                        Direct Web
                    </th>
                    <th style="padding: 14px 14px; text-align: center; width: 140px;">PARITY / ACTION</th>
                </tr>
            </thead>
            <tbody>
                @forelse($matrixData['rows'] as $row)
                <tr style="border-bottom: 1px solid var(--border); transition: background 0.15s;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='transparent'">
                    <!-- Room Type Details -->
                    <td style="padding: 14px 18px;">
                        <div style="font-weight: 800; font-size: 14px; color: var(--text-dark);">{{ $row['room_type_name'] }}</div>
                        <div style="display: flex; gap: 8px; align-items: center; margin-top: 4px;">
                            <span style="font-family: monospace; font-size: 11px; color: #64748B; background: #F1F5F9; padding: 2px 6px; border-radius: 4px;">{{ $row['room_type_code'] }}</span>
                            <span style="font-size: 11px; color: #64748B;"><i class="fa-solid fa-user-group"></i> Max {{ $row['max_occupancy'] }}</span>
                        </div>
                    </td>

                    <!-- Physical Capacity -->
                    <td style="padding: 14px 12px; text-align: center; font-weight: 700; color: #475569;">
                        {{ $row['pms']['total_units'] }}
                    </td>

                    <!-- Reserved -->
                    <td style="padding: 14px 12px; text-align: center; font-weight: 700; color: #F59E0B;">
                        {{ $row['pms']['reserved_units'] }}
                    </td>

                    <!-- Maintenance / Blocked -->
                    <td style="padding: 14px 12px; text-align: center; font-weight: 700; color: #64748B;">
                        {{ $row['pms']['maintenance_units'] }}
                    </td>

                    <!-- Central PMS Available (Authoritative) -->
                    <td style="padding: 14px 14px; text-align: center; background: #F0FDF4; border-left: 2px solid #38BDF8; border-right: 2px solid #38BDF8; cursor: pointer;"
                        onclick="openQuantityModal({{ $row['room_type_id'] }}, '{{ $stayDate }}', 'pms')"
                        title="Click to view PMS ledger and movements">
                        <div style="font-size: 18px; font-weight: 900; color: {{ $row['pms']['available_units'] === 0 ? '#EF4444' : ($row['pms']['available_units'] <= 2 ? '#D97706' : '#059669') }};">
                            {{ $row['pms']['available_units'] }}
                        </div>
                        <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; margin-top: 2px;">
                            @if(!$row['pms']['has_record'])
                                <span class="badge" style="background: #F1F5F9; color: #64748B; font-size: 9px;">NO RECORD</span>
                            @elseif($row['pms']['available_units'] === 0)
                                <span class="badge" style="background: #FEE2E2; color: #DC2626; font-size: 9px;">SOLD OUT</span>
                            @elseif($row['pms']['available_units'] <= 2)
                                <span class="badge" style="background: #FEF3C7; color: #D97706; font-size: 9px;">LOW</span>
                            @else
                                <span class="badge" style="background: #D1FAE5; color: #047857; font-size: 9px;">IN STOCK</span>
                            @endif
                        </div>
                    </td>

                    <!-- Mapped OTA Channels -->
                    @php
                        $otaPartners = ['booking_com', 'agoda', 'expedia', 'rakuten', 'jalan', 'direct_web'];
                    @endphp

                    @foreach($otaPartners as $partnerKey)
                        @php
                            $ch = $row['channels'][$partnerKey] ?? null;
                        @endphp
                        <td style="padding: 12px; text-align: center; cursor: pointer; transition: background 0.15s;"
                            onclick="openQuantityModal({{ $row['room_type_id'] }}, '{{ $stayDate }}', '{{ $partnerKey }}')"
                            title="{{ $ch['tooltip'] ?? 'Channel information' }}">
                            @if(!$ch || !$ch['is_configured'])
                                <div style="font-size: 11px; font-weight: 700; color: #94A3B8;">NOT CONFIGURED</div>
                            @elseif(!$ch['is_mapped'])
                                <div style="font-size: 11px; font-weight: 700; color: #94A3B8;">NOT MAPPED</div>
                            @elseif($ch['remote_units'] === null)
                                <div style="font-size: 11px; font-weight: 700; color: #EF4444;">MISSING</div>
                                <div style="font-size: 9px; color: #64748B;">No Remote Record</div>
                            @else
                                <div style="font-size: 16px; font-weight: 800; color: {{ $ch['status'] === 'OVERSELL_RISK' ? '#DC2626' : ($ch['status'] === 'DISCREPANCY' ? '#D97706' : '#0F172A') }};">
                                    {{ $ch['remote_units'] }}
                                </div>
                                <div style="margin-top: 2px;">
                                    @if($ch['status'] === 'MATCHED')
                                        <span class="badge" style="background: #DCFCE7; color: #15803D; font-size: 9px; padding: 2px 6px;">MATCHED</span>
                                    @elseif($ch['status'] === 'OVERSELL_RISK')
                                        <span class="badge" style="background: #FEE2E2; color: #B91C1C; font-size: 9px; padding: 2px 6px;">OVERSELL!</span>
                                    @elseif($ch['status'] === 'DISCREPANCY')
                                        <span class="badge" style="background: #FEF3C7; color: #B45309; font-size: 9px; padding: 2px 6px;">DIFF: {{ $ch['difference'] }}</span>
                                    @elseif($ch['status'] === 'OUT_OF_SYNC')
                                        <span class="badge" style="background: #FEE2E2; color: #B91C1C; font-size: 9px; padding: 2px 6px;">FAILED</span>
                                    @elseif($ch['status'] === 'PENDING')
                                        <span class="badge" style="background: #E0F2FE; color: #0369A1; font-size: 9px; padding: 2px 6px;">PENDING</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                    @endforeach

                    <!-- Row Overall Parity / Action -->
                    <td style="padding: 14px; text-align: center;">
                        @if($row['has_oversell_risk'])
                            <span class="badge" style="background: #EF4444; color: #fff; font-size: 10px; font-weight: 800; padding: 4px 8px;">
                                <i class="fa-solid fa-triangle-exclamation"></i> OVERSELL RISK
                            </span>
                        @elseif($row['has_discrepancy'])
                            <span class="badge" style="background: #F59E0B; color: #fff; font-size: 10px; font-weight: 800; padding: 4px 8px;">
                                <i class="fa-solid fa-arrows-rotate"></i> DISCREPANCY
                            </span>
                        @else
                            <span class="badge" style="background: #10B981; color: #fff; font-size: 10px; font-weight: 800; padding: 4px 8px;">
                                <i class="fa-solid fa-check"></i> ALL IN SYNC
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" style="padding: 40px; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-bed" style="font-size: 32px; color: #CBD5E1; margin-bottom: 12px; display: block;"></i>
                        {{ app()->getLocale() === 'ja' ? '該当する客室タイプ・在庫データは見つかりませんでした。' : 'No room types or inventory records matching the selected criteria.' }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Expandable Channel Allotment Breakdown (Part 12) -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header" style="background: #F8FAFC; cursor: pointer;" onclick="toggleAllotmentDrawer()">
        <h3 style="margin: 0; font-size: 14px; font-weight: 700; color: #183B50; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-chart-pie" style="color: #287F79;"></i>
            {{ app()->getLocale() === 'ja' ? 'チャネル別在庫配分・アロットメント構成 (Channel Allotments)' : 'Channel Allocation & Pooled Inventory Model' }}
            <span style="font-size: 11px; color: #64748B; font-weight: 500;">(Click to expand/collapse)</span>
        </h3>
        <i class="fa-solid fa-chevron-down" id="allotmentChevron" style="transition: transform 0.2s;"></i>
    </div>
    <div class="card-body" id="allotmentDrawer" style="display: none; padding: 20px;">
        <div style="font-size: 13px; color: #475569; margin-bottom: 16px; line-height: 1.6;">
            <strong>Inventory Distribution Model:</strong> ClearStay implements an active <strong>Shared Pool with Soft Quota Allocation</strong>. 
            All simulated OTA channels draw from the unified central PMS capacity unless dedicated contracted blocks are specified. 
            Simultaneous reserved allocations across channels cannot exceed physical capacity.
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
            @foreach($matrixData['rows'] as $r)
            <div style="border: 1px solid var(--border); border-radius: 8px; padding: 14px; background: #ffffff;">
                <div style="font-weight: 800; font-size: 13px; color: var(--text-dark); margin-bottom: 8px; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px;">
                    {{ $r['room_type_name'] }}
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                    <span style="color: #64748B;">Physical Total:</span>
                    <strong>{{ $r['pms']['total_units'] }} rooms</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                    <span style="color: #059669;">Central Available:</span>
                    <strong>{{ $r['pms']['available_units'] }} rooms</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                    <span style="color: #F59E0B;">In-Stay Reserved:</span>
                    <strong>{{ $r['pms']['reserved_units'] }} rooms</strong>
                </div>
                <div style="border-top: 1px dashed #e2e8f0; margin-top: 8px; padding-top: 8px; font-size: 11px;">
                    <span style="font-weight: 700; color: #475569;">Simulated Channels Status:</span>
                    <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px;">
                        @foreach($r['channels'] as $c)
                            <span class="badge" style="background: #F1F5F9; color: #334155; font-size: 10px;">
                                {{ $c['channel_name'] }}: {{ $c['remote_units'] !== null ? $c['remote_units'] : 'N/A' }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@else
<!-- VIEW B: MULTI-NIGHT STAY RANGE AVAILABILITY MATRIX -->
<div class="card">
    <div class="card-header" style="background: #F8FAFC; border-bottom: 2px solid #E2E8F0; padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0F1D2F; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-calendar-days" style="color: #287F79;"></i>
                    {{ app()->getLocale() === 'ja' ? '連泊・滞在期間 空室可視化' : 'Multi-Night Stay Availability Matrix' }}
                    <span style="font-size: 13px; font-weight: 600; color: #64748B;">
                        ({{ Carbon\Carbon::parse($checkInDate)->format('M d') }} &rarr; {{ Carbon\Carbon::parse($checkOutDate)->format('M d, Y') }}, {{ $matrixData['nights_count'] }} occupied nights)
                    </span>
                </h3>
                <div style="font-size: 12px; color: #64748B; margin-top: 4px;">
                    Checkout date ({{ Carbon\Carbon::parse($checkOutDate)->format('M d') }}) is strictly <strong>excluded</strong>. Availability evaluated across all {{ $matrixData['nights_count'] }} occupied nights.
                </div>
            </div>
            <div>
                <span class="badge" style="background: #1E3A4C; color: #38BDF8; font-size: 12px; padding: 6px 12px;">
                    Requested: {{ $requestedRooms }} {{ Str::plural('Room', $requestedRooms) }}
                </span>
            </div>
        </div>
    </div>

    <div class="card-body" style="padding: 0; overflow-x: auto;">
        <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px; margin: 0;">
            <thead>
                <tr style="background: #0F1D2F; color: #ffffff; text-align: left; font-size: 12px;">
                    <th style="padding: 14px 18px; min-width: 200px;">ROOM TYPE</th>
                    <th style="padding: 14px 12px; text-align: center; width: 90px;">CAPACITY</th>
                    @foreach($matrixData['nights'] as $n)
                    <th style="padding: 14px 12px; text-align: center; min-width: 90px; border-left: 1px solid rgba(255,255,255,0.1);">
                        <div>{{ Carbon\Carbon::parse($n)->format('D') }}</div>
                        <div style="font-size: 13px; font-weight: 800;">{{ Carbon\Carbon::parse($n)->format('m/d') }}</div>
                    </th>
                    @endforeach
                    <th style="padding: 14px 12px; text-align: center; background: #1E3A4C; width: 100px; border-left: 2px solid #38BDF8;">
                        MIN AVAIL
                    </th>
                    <th style="padding: 14px 14px; text-align: center; min-width: 180px;">STAY FEASIBILITY</th>
                </tr>
            </thead>
            <tbody>
                @forelse($matrixData['rows'] as $stayRow)
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 14px 18px;">
                        <div style="font-weight: 800; font-size: 14px; color: var(--text-dark);">{{ $stayRow['room_type_name'] }}</div>
                        <div style="font-family: monospace; font-size: 11px; color: #64748B;">{{ $stayRow['room_type_code'] }}</div>
                    </td>
                    <td style="padding: 14px 12px; text-align: center; font-weight: 700; color: #475569;">
                        {{ $stayRow['nightly'][array_key_first($stayRow['nightly'])]['pms']['total_units'] ?? 'N/A' }}
                    </td>

                    @foreach($matrixData['nights'] as $n)
                        @php
                            $nightPms = $stayRow['nightly'][$n]['pms']['available_units'] ?? 0;
                            $nightInsufficient = ($nightPms < $requestedRooms);
                        @endphp
                        <td style="padding: 14px 12px; text-align: center; border-left: 1px solid var(--border); background: {{ $nightInsufficient ? '#FEF2F2' : 'transparent' }};">
                            <div style="font-size: 16px; font-weight: 800; color: {{ $nightPms === 0 ? '#EF4444' : ($nightInsufficient ? '#DC2626' : '#059669') }};">
                                {{ $nightPms }}
                            </div>
                            <div style="font-size: 10px; color: #64748B; margin-top: 2px;">
                                @if($nightInsufficient)
                                    <span style="color: #DC2626; font-weight: 700;">Deficit: {{ $requestedRooms - $nightPms }}</span>
                                @else
                                    <span>OK</span>
                                @endif
                            </div>
                        </td>
                    @endforeach

                    <!-- Minimum Available in Stay -->
                    <td style="padding: 14px 12px; text-align: center; background: #F0FDF4; border-left: 2px solid #38BDF8; font-size: 18px; font-weight: 900; color: {{ $stayRow['min_pms_available'] >= $requestedRooms ? '#047857' : '#DC2626' }};">
                        {{ $stayRow['min_pms_available'] }}
                    </td>

                    <!-- Stay Feasibility Assessment -->
                    <td style="padding: 14px; text-align: center;">
                        @if($stayRow['can_accommodate'])
                            <span class="badge" style="background: #D1FAE5; color: #065F46; font-size: 11px; font-weight: 800; padding: 6px 12px;">
                                <i class="fa-solid fa-circle-check"></i> CAN ACCOMMODATE {{ $requestedRooms }} {{ Str::plural('ROOM', $requestedRooms) }}
                            </span>
                        @else
                            <span class="badge" style="background: #FEE2E2; color: #991B1B; font-size: 11px; font-weight: 800; padding: 6px 12px;"
                                  title="Insufficient on: {{ collect($stayRow['insufficient_dates'])->pluck('date')->implode(', ') }}">
                                <i class="fa-solid fa-circle-xmark"></i> INSUFFICIENT ON {{ count($stayRow['insufficient_dates']) }} NIGHT(S)
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="padding: 40px; text-align: center; color: var(--text-muted);">
                        No room types found for stay evaluation.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Interactive Quantity Details Modal (Part 9) -->
<div class="modal-overlay" id="quantityModal" onclick="if(event.target===this) closeQuantityModal()">
    <div class="modal-container" style="max-width: 680px;">
        <div class="modal-header" style="background: #0F1D2F; color: #ffffff;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-magnifying-glass-chart" style="color: #2DD4BF;"></i>
                    <span id="modalTitle">Room Inventory & Parity Inspector</span>
                </h3>
                <div style="font-size: 12px; color: #94A3B8; margin-top: 2px;" id="modalSubtitle">Loading ledger details...</div>
            </div>
            <button type="button" onclick="closeQuantityModal()" style="border: none; background: transparent; color: #94A3B8; font-size: 18px; cursor: pointer;">&times;</button>
        </div>

        <div class="modal-body" id="modalBody" style="padding: 24px;">
            <div style="text-align: center; padding: 30px; color: #64748B;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 28px; color: #287F79; margin-bottom: 12px; display: block;"></i>
                Querying live database ledger and simulated channel records...
            </div>
        </div>

        <div class="modal-footer" style="background: #F8FAFC; border-top: 1px solid var(--border); padding: 14px 24px; display: flex; justify-content: space-between; align-items: center;">
            <div id="modalActionContainer"></div>
            <button type="button" class="btn btn-secondary" onclick="closeQuantityModal()">{{ app()->getLocale() === 'ja' ? '閉じる' : 'Close' }}</button>
        </div>
    </div>
</div>

<script>
function toggleAllotmentDrawer() {
    const drawer = document.getElementById('allotmentDrawer');
    const chevron = document.getElementById('allotmentChevron');
    if (drawer.style.display === 'none') {
        drawer.style.display = 'block';
        chevron.style.transform = 'rotate(180deg)';
    } else {
        drawer.style.display = 'none';
        chevron.style.transform = 'rotate(0deg)';
    }
}

function refreshMatrixData() {
    const btn = document.getElementById('refreshBtn');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Refreshing...';
    btn.disabled = true;

    const form = document.getElementById('matrixFilterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);

    fetch(`{{ route('room_availability.data') }}?${params.toString()}`)
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Updated!';
            setTimeout(() => {
                window.location.reload();
            }, 300);
        })
        .catch(err => {
            console.error('Matrix refresh failed', err);
            window.location.reload();
        });
}

function openQuantityModal(roomTypeId, date, channelCode) {
    const modal = document.getElementById('quantityModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');
    const modalBody = document.getElementById('modalBody');
    const actionContainer = document.getElementById('modalActionContainer');

    modalTitle.innerText = channelCode === 'pms' ? 'Central Hotel PMS Inventory Ledger' : `${channelCode.toUpperCase()} Simulator Inspector`;
    modalSubtitle.innerText = `Stay Date: ${date} | Room Type ID: ${roomTypeId}`;
    modalBody.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #64748B;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size: 28px; color: #287F79; margin-bottom: 12px; display: block;"></i>
            Inspecting persistent database state...
        </div>`;
    actionContainer.innerHTML = '';
    modal.classList.add('active');

    fetch(`{{ route('room_availability.details') }}?room_type_id=${roomTypeId}&date=${date}&channel_code=${channelCode}`)
        .then(res => res.json())
        .then(res => {
            if (!res.success) {
                modalBody.innerHTML = `<div class="alert alert-danger">Failed to load details: ${res.message || 'Error'}</div>`;
                return;
            }

            const d = res.details;
            modalSubtitle.innerText = `${d.hotel_name} | ${d.room_type_name} (${d.room_type_code}) | Stay Date: ${d.stay_date}`;

            let html = `
                <!-- Central PMS Values -->
                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                    <div style="font-size: 12px; font-weight: 800; color: #183B50; text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-hotel" style="color: #287F79;"></i> Authoritative Central PMS State
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; text-align: center;">
                        <div style="background: #fff; padding: 10px; border-radius: 6px; border: 1px solid #E2E8F0;">
                            <div style="font-size: 11px; color: #64748B;">Physical Total</div>
                            <div style="font-size: 18px; font-weight: 800; color: #0F172A;">${d.pms.total_units}</div>
                        </div>
                        <div style="background: #fff; padding: 10px; border-radius: 6px; border: 1px solid #E2E8F0;">
                            <div style="font-size: 11px; color: #64748B;">Reserved</div>
                            <div style="font-size: 18px; font-weight: 800; color: #F59E0B;">${d.pms.reserved_units}</div>
                        </div>
                        <div style="background: #fff; padding: 10px; border-radius: 6px; border: 1px solid #E2E8F0;">
                            <div style="font-size: 11px; color: #64748B;">Maintenance</div>
                            <div style="font-size: 18px; font-weight: 800; color: #64748B;">${d.pms.maintenance_units}</div>
                        </div>
                        <div style="background: #F0FDF4; padding: 10px; border-radius: 6px; border: 1px solid #BBF7D0;">
                            <div style="font-size: 11px; color: #047857; font-weight: 700;">PMS Available</div>
                            <div style="font-size: 20px; font-weight: 900; color: #059669;">${d.pms.available_units}</div>
                        </div>
                    </div>
                </div>`;

            if (d.channel) {
                const ch = d.channel;
                const statusColor = ch.reconciliation_status === 'MATCHED' ? '#10B981' : (ch.reconciliation_status === 'OVERSELL_RISK' ? '#EF4444' : '#F59E0B');
                html += `
                    <!-- Channel Remote State -->
                    <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <div style="font-size: 12px; font-weight: 800; color: #1E40AF; text-transform: uppercase;">
                                <i class="fa-solid fa-globe"></i> ${ch.channel_name} Simulated Remote Inventory
                            </div>
                            <span class="badge" style="background: ${statusColor}; color: #fff; font-size: 10px;">${ch.reconciliation_status}</span>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align: center; margin-bottom: 12px;">
                            <div style="background: #fff; padding: 10px; border-radius: 6px; border: 1px solid #BFDBFE;">
                                <div style="font-size: 11px; color: #64748B;">Remote Room Code</div>
                                <div style="font-size: 14px; font-weight: 800; color: #1E3A8A; font-family: monospace;">${ch.remote_room_code || 'N/A'}</div>
                            </div>
                            <div style="background: #fff; padding: 10px; border-radius: 6px; border: 1px solid #BFDBFE;">
                                <div style="font-size: 11px; color: #64748B;">Remote Available</div>
                                <div style="font-size: 20px; font-weight: 900; color: #1E40AF;">${ch.remote_available_units !== null ? ch.remote_available_units : 'Missing'}</div>
                            </div>
                            <div style="background: #fff; padding: 10px; border-radius: 6px; border: 1px solid #BFDBFE;">
                                <div style="font-size: 11px; color: #64748B;">Difference (PMS - Remote)</div>
                                <div style="font-size: 18px; font-weight: 800; color: ${ch.difference === 0 ? '#10B981' : '#B45309'};">${ch.difference !== null ? ch.difference : 'N/A'}</div>
                            </div>
                        </div>

                        <div style="font-size: 12px; color: #1E3A8A; line-height: 1.5; background: rgba(255,255,255,0.7); padding: 10px 12px; border-radius: 6px;">
                            <strong>Reconciliation Advice:</strong> ${ch.recommended_action}
                        </div>
                    </div>`;

                if (ch.reconciliation_status !== 'MATCHED') {
                    actionContainer.innerHTML = `
                        <button type="button" class="btn btn-primary" onclick="realignInventory(${roomTypeId}, '${date}', '${ch.partner_code}')">
                            <i class="fa-solid fa-arrows-rotate"></i> Re-align ${ch.channel_name} to PMS (${d.pms.available_units} units)
                        </button>`;
                }
            }

            // Reservations List
            html += `
                <div style="border-top: 1px solid var(--border); padding-top: 16px;">
                    <div style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 8px;">
                        <i class="fa-solid fa-calendar-check"></i> Active Reservations on this Stay Date (${d.reservations.length})
                    </div>`;

            if (d.reservations.length > 0) {
                html += `
                    <div style="max-height: 160px; overflow-y: auto; border: 1px solid var(--border); border-radius: 6px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                            <thead style="background: #F1F5F9; color: #475569;">
                                <tr>
                                    <th style="padding: 6px 10px; text-align: left;">Reservation #</th>
                                    <th style="padding: 6px 10px; text-align: left;">Guest</th>
                                    <th style="padding: 6px 10px; text-align: center;">Rooms</th>
                                    <th style="padding: 6px 10px; text-align: center;">Room Assigned</th>
                                    <th style="padding: 6px 10px; text-align: center;">Stay</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${d.reservations.map(r => `
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 6px 10px; font-weight: 700; color: #287F79;">${r.reservation_number}</td>
                                        <td style="padding: 6px 10px;">${r.guest_name}</td>
                                        <td style="padding: 6px 10px; text-align: center; font-weight: 700;">${r.rooms_count}</td>
                                        <td style="padding: 6px 10px; text-align: center;">${r.room_number}</td>
                                        <td style="padding: 6px 10px; text-align: center; font-size: 11px; color: #64748B;">${r.check_in} &rarr; ${r.check_out}</td>
                                    </tr>`).join('')}
                            </tbody>
                        </table>
                    </div>`;
            } else {
                html += `<div style="font-size: 12px; color: #94A3B8; font-style: italic; padding: 10px 0;">No active reservations occupying this room type on this date.</div>`;
            }

            html += `</div>`;
            modalBody.innerHTML = html;
        })
        .catch(err => {
            modalBody.innerHTML = `<div class="alert alert-danger">Communication error inspecting ledger.</div>`;
        });
}

function realignInventory(roomTypeId, date, partnerCode) {
    if (!confirm(`Are you sure you want to re-align ${partnerCode.toUpperCase()} simulated inventory to match Central PMS?`)) {
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch(`{{ route('room_availability.realign') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            room_type_id: roomTypeId,
            date: date,
            partner_code: partnerCode,
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            alert(res.message);
            closeQuantityModal();
            window.location.reload();
        } else {
            alert('Re-alignment failed: ' + (res.message || 'Unknown error'));
        }
    })
    .catch(err => {
        alert('Communication error executing re-alignment.');
    });
}

function closeQuantityModal() {
    document.getElementById('quantityModal').classList.remove('active');
}
</script>
@endsection
