@extends('layouts.app')

@section('title', __('clearstay.board.title'))
@section('header_title', __('clearstay.board.title'))
@section('header_subtitle', __('clearstay.board.subtitle'))

@push('styles')
<style>
    .board-controls {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: white;
        padding: 16px 20px;
        border-radius: 12px;
        border: 1px solid var(--border);
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .status-legend {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        font-size: 12px;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .room-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 32px;
    }

    .room-card {
        background: white;
        border-radius: 12px;
        border: 2px solid var(--border);
        padding: 16px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .room-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px -3px rgba(0,0,0,0.08);
        border-color: var(--primary);
    }

    .room-card.status-clean { border-top: 4px solid var(--success); }
    .room-card.status-dirty { border-top: 4px solid var(--danger); }
    .room-card.status-inspecting { border-top: 4px solid var(--warning); }
    .room-card.status-maintenance { border-top: 4px solid #64748b; }

    .room-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .room-number {
        font-size: 20px;
        font-weight: 800;
        color: var(--text-dark);
    }

    .floor-badge {
        font-size: 11px;
        color: var(--text-muted);
        background: #f1f5f9;
        padding: 2px 6px;
        border-radius: 4px;
    }

    .room-type {
        font-size: 12px;
        color: var(--text-muted);
        margin-bottom: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .room-guest-section {
        background: #f8fafc;
        border-radius: 8px;
        padding: 8px 10px;
        margin-bottom: 12px;
        font-size: 12px;
        min-height: 48px;
    }

    .guest-name {
        font-weight: 700;
        color: var(--text-dark);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .stay-dates {
        font-size: 11px;
        color: var(--text-muted);
    }

    .quick-status-selector {
        margin-top: 10px;
        width: 100%;
        padding: 4px 6px;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        background: #ffffff;
        cursor: pointer;
    }

    .live-pulse {
        display: inline-block;
        width: 8px;
        height: 8px;
        background: var(--success);
        border-radius: 50%;
        margin-right: 6px;
        animation: pulseAnimation 2s infinite;
    }

    @keyframes pulseAnimation {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
</style>
@endpush

@section('content')
<!-- Board Header Controls -->
<div class="board-controls">
    <!-- Filters -->
    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <select id="filterFloor" class="form-control" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
            <option value="">{{ __('clearstay.board.all_floors') }}</option>
            @foreach($availableFloors as $fl)
                <option value="{{ $fl }}" {{ $floorFilter == $fl ? 'selected' : '' }}>Floor {{ $fl }}F</option>
            @endforeach
        </select>

        <select id="filterStatus" class="form-control" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
            <option value="">{{ __('clearstay.board.all_statuses') }}</option>
            <option value="clean" {{ $statusFilter == 'clean' ? 'selected' : '' }}>{{ __('clearstay.board.clean') }}</option>
            <option value="dirty" {{ $statusFilter == 'dirty' ? 'selected' : '' }}>{{ __('clearstay.board.dirty') }}</option>
            <option value="inspecting" {{ $statusFilter == 'inspecting' ? 'selected' : '' }}>{{ __('clearstay.board.inspecting') }}</option>
            <option value="maintenance" {{ $statusFilter == 'maintenance' ? 'selected' : '' }}>{{ __('clearstay.board.maintenance') }}</option>
        </select>

        <select id="filterType" class="form-control" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
            <option value="">{{ __('clearstay.board.all_types') }}</option>
            @foreach($roomTypes as $t)
                <option value="{{ $t->id }}" {{ $typeFilter == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
            @endforeach
        </select>
    </div>

    <!-- Live Auto-Refresh Indicator -->
    <div style="display: flex; align-items: center; gap: 16px;">
        <span style="font-size: 12px; color: var(--text-muted); display: flex; align-items: center;">
            <span class="live-pulse"></span>
            <span id="refreshTimer">{{ __('clearstay.general.auto_refresh') }}</span>
        </span>

        <button type="button" class="btn btn-secondary btn-sm" onclick="reloadBoard(true)">
            <i class="fa-solid fa-arrows-rotate"></i> {{ __('clearstay.general.refresh') }}
        </button>
    </div>
</div>

<!-- Status Summary Legend -->
<div style="background: white; border-radius: 12px; border: 1px solid var(--border); padding: 12px 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
    <div class="status-legend">
        <div class="legend-item">
            <span class="legend-dot" style="background: var(--success);"></span>
            <span>Clean & Ready (<strong id="countClean">{{ $counts['clean'] }}</strong>)</span>
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background: var(--danger);"></span>
            <span>Dirty (<strong id="countDirty">{{ $counts['dirty'] }}</strong>)</span>
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background: var(--warning);"></span>
            <span>Inspecting (<strong id="countInspecting">{{ $counts['inspecting'] }}</strong>)</span>
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background: #64748b;"></span>
            <span>Maintenance (<strong id="countMaintenance">{{ $counts['maintenance'] }}</strong>)</span>
        </div>
        <div class="legend-item">
            <span class="badge badge-occupied" style="font-size: 10px;">Occupied (<strong id="countOccupied">{{ $counts['occupied'] }}</strong>)</span>
        </div>
    </div>
    <div style="font-size: 12px; color: var(--text-muted);">
        <i class="fa-solid fa-hand-pointer"></i> {{ __('clearstay.board.click_for_preview') }}
    </div>
</div>

<!-- Room Grid Container (Floors) -->
<div id="boardContainer">
    @foreach($floors as $floor => $floorRooms)
        <div class="floor-section" data-floor="{{ $floor }}" style="margin-bottom: 28px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                <h3 style="font-size: 16px; font-weight: 800; color: var(--text-main);">{{ $floor }}F</h3>
                <div style="flex: 1; height: 1px; background: var(--border);"></div>
                <span style="font-size: 12px; color: var(--text-muted);">{{ count($floorRooms) }} Rooms</span>
            </div>

            <div class="room-grid">
                @foreach($floorRooms as $room)
                    @php
                        $activeRes = $room->reservations->first();
                    @endphp
                    <div class="room-card status-{{ $room->status }}" 
                         id="roomCard-{{ $room->id }}"
                         data-room-id="{{ $room->id }}"
                         data-floor="{{ $room->floor }}"
                         data-status="{{ $room->status }}"
                         data-type-id="{{ $room->room_type_id }}"
                         onclick="handleRoomClick({{ $room->id }}, {{ $activeRes ? $activeRes->id : 'null' }})">
                        
                        <div class="room-header">
                            <span class="room-number">{{ $room->room_number }}</span>
                            <div>
                                @if($room->is_occupied)
                                    <span class="badge badge-occupied" id="badgeOcc-{{ $room->id }}">{{ __('clearstay.board.occupied') }}</span>
                                @else
                                    <span class="badge badge-{{ $room->status }}" id="badgeStat-{{ $room->id }}">{{ ucfirst($room->status) }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="room-type">{{ $room->roomType->name }}</div>

                        <div class="room-guest-section" id="guestSection-{{ $room->id }}">
                            @if($activeRes)
                                <div class="guest-name"><i class="fa-solid fa-user"></i> {{ $activeRes->guest->full_name }}</div>
                                <div class="stay-dates">{{ $activeRes->check_in_date->format('M d') }} - {{ $activeRes->check_out_date->format('M d') }} ({{ $activeRes->confirmation_code }})</div>
                            @else
                                <div style="color: var(--text-muted); padding-top: 6px;"><i class="fa-solid fa-bed"></i> {{ __('clearstay.board.vacant') }}</div>
                            @endif
                        </div>

                        <!-- Quick Status Selector (Stop Propagation so it doesn't open modal) -->
                        <div onclick="event.stopPropagation();">
                            <select class="quick-status-selector" onchange="quickUpdateRoomStatus({{ $room->id }}, this.value)">
                                <option value="clean" {{ $room->status == 'clean' ? 'selected' : '' }}>Clean</option>
                                <option value="dirty" {{ $room->status == 'dirty' ? 'selected' : '' }}>Dirty</option>
                                <option value="inspecting" {{ $room->status == 'inspecting' ? 'selected' : '' }}>Inspecting</option>
                                <option value="maintenance" {{ $room->status == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            </select>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
    function handleRoomClick(roomId, reservationId) {
        if (reservationId) {
            openReservationModal(reservationId);
        } else {
            // Vacant room - ask if user wants to create a reservation for this room
            if (confirm('Room is currently vacant. Would you like to create a new reservation for this room?')) {
                window.location.href = "{{ route('reservations.create') }}?room_id=" + roomId;
            }
        }
    }

    // Quick status toggle via AJAX
    function quickUpdateRoomStatus(roomId, newStatus) {
        $.ajax({
            url: '/board/room/' + roomId + '/status',
            method: 'POST',
            data: { status: newStatus },
            success: function (res) {
                if (res.success) {
                    const card = $('#roomCard-' + roomId);
                    card.removeClass('status-clean status-dirty status-inspecting status-maintenance');
                    card.addClass('status-' + newStatus);
                    card.attr('data-status', newStatus);

                    if (!card.find('.badge-occupied').length) {
                        $('#badgeStat-' + roomId)
                            .attr('class', 'badge badge-' + newStatus)
                            .text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                    }
                    reloadBoard(false); // background sync counts
                }
            },
            error: function () {
                alert('Failed to update room status.');
            }
        });
    }

    // Live Board Polling Refresh (every 10 seconds via AJAX)
    let pollInterval = setInterval(() => reloadBoard(false), 10000);

    function reloadBoard(showVisual) {
        if (showVisual) {
            $('#refreshTimer').text('Refreshing live board...');
        }

        $.ajax({
            url: "{{ route('board.status') }}",
            method: 'GET',
            success: function (res) {
                if (res.success) {
                    // Update counts
                    $('#countClean').text(res.counts.clean);
                    $('#countDirty').text(res.counts.dirty);
                    $('#countInspecting').text(res.counts.inspecting);
                    $('#countMaintenance').text(res.counts.maintenance);
                    $('#countOccupied').text(res.counts.occupied);

                    // Update room cards
                    res.rooms.forEach(function (room) {
                        const card = $('#roomCard-' + room.id);
                        if (card.length) {
                            card.removeClass('status-clean status-dirty status-inspecting status-maintenance');
                            card.addClass('status-' + room.status);
                            card.attr('data-status', room.status);

                            // Update guest info
                            const guestSec = $('#guestSection-' + room.id);
                            if (room.is_occupied && room.current_guest) {
                                guestSec.html(
                                    `<div class="guest-name"><i class="fa-solid fa-user"></i> ${room.current_guest}</div>
                                     <div class="stay-dates">${room.check_in} - ${room.check_out} (${room.confirmation_code})</div>`
                                );
                                card.attr('onclick', `handleRoomClick(${room.id}, ${room.reservation_id})`);
                            } else {
                                guestSec.html('<div style="color: var(--text-muted); padding-top: 6px;"><i class="fa-solid fa-bed"></i> Vacant</div>');
                                card.attr('onclick', `handleRoomClick(${room.id}, null)`);
                            }
                        }
                    });

                    $('#refreshTimer').text("{{ __('clearstay.general.auto_refresh') }}");
                }
            }
        });
    }

    // Client-side quick filter
    $('#filterFloor, #filterStatus, #filterType').on('change', function () {
        const floor = $('#filterFloor').val();
        const status = $('#filterStatus').val();
        const type = $('#filterType').val();

        $('.room-card').each(function () {
            const card = $(this);
            let show = true;

            if (floor && card.attr('data-floor') !== floor) show = false;
            if (status && card.attr('data-status') !== status) show = false;
            if (type && card.attr('data-type-id') !== type) show = false;

            if (show) card.show(); else card.hide();
        });

        // Hide empty floor sections
        $('.floor-section').each(function () {
            const visibleRooms = $(this).find('.room-card:visible').length;
            if (visibleRooms === 0) $(this).hide(); else $(this).show();
        });
    });
</script>
@endpush
