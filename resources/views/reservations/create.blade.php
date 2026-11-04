@extends('layouts.app')

@section('title', __('clearstay.reservations.new_reservation'))
@section('header_title', __('clearstay.reservations.new_reservation'))
@section('header_subtitle', 'Book a room with automated conflict check and transactional row locking')

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-calendar-plus text-primary"></i> Create Guest Reservation</h3>
            <a href="{{ route('reservations.index') }}" class="btn btn-secondary btn-sm">Back to List</a>
        </div>
        <div class="card-body">
            <form action="{{ route('reservations.store') }}" method="POST">
                @csrf

                <!-- Guest Selection Mode -->
                <div style="margin-bottom: 24px; background: #f8fafc; border: 1px solid var(--border); border-radius: 10px; padding: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 10px;">Guest Identification</label>
                    <div style="display: flex; gap: 20px; margin-bottom: 14px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;">
                            <input type="radio" name="guest_mode" value="new" checked onchange="toggleGuestMode('new')">
                            <span>Register New Guest</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;">
                            <input type="radio" name="guest_mode" value="existing" onchange="toggleGuestMode('existing')">
                            <span>Select Existing Registered Guest</span>
                        </label>
                    </div>

                    <!-- New Guest Fields -->
                    <div id="newGuestFields">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 12px;">
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">First Name *</label>
                                <input type="text" name="first_name" value="{{ old('first_name') }}" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Last Name *</label>
                                <input type="text" name="last_name" value="{{ old('last_name') }}" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Email Address *</label>
                                <input type="email" name="email" value="{{ old('email') }}" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Phone Number</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+81-90-1234-5678" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                            </div>
                        </div>
                    </div>

                    <!-- Existing Guest Select -->
                    <div id="existingGuestFields" style="display: none;">
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Select Guest Profile</label>
                        <select name="guest_id" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                            <option value="">-- Choose Guest --</option>
                            @foreach($guests as $g)
                                <option value="{{ $g->id }}">{{ $g->full_name }} ({{ $g->email }}) - VIP: {{ strtoupper($g->vip_level) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Room & Dates -->
                <div style="margin-bottom: 24px;">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Select Room</label>
                        <select name="room_id" id="roomSelect" required style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                            <option value="">-- Select Room --</option>
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}" data-price="{{ $r->roomType->base_price }}" {{ request('room_id') == $r->id ? 'selected' : '' }}>
                                    Room {{ $r->room_number }} - {{ $r->roomType->name }} (Floor {{ $r->floor }}F | ¥{{ number_format($r->roomType->base_price) }}/night)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">{{ __('clearstay.reservations.check_in') }}</label>
                            <input type="date" name="check_in_date" id="checkInDate" value="{{ old('check_in_date', date('Y-m-d')) }}" required
                                style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">{{ __('clearstay.reservations.check_out') }}</label>
                            <input type="date" name="check_out_date" id="checkOutDate" value="{{ old('check_out_date', date('Y-m-d', strtotime('+1 day'))) }}" required
                                style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Adults Count</label>
                            <input type="number" name="adults" value="{{ old('adults', 1) }}" min="1" max="10" required
                                style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Children Count</label>
                            <input type="number" name="children" value="{{ old('children', 0) }}" min="0" max="10"
                                style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Special Requests / Concierge Notes</label>
                        <textarea name="special_requests" rows="3" placeholder="e.g. Non-smoking, high floor, anniversary surprise"
                            style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px;">{{ old('special_requests') }}</textarea>
                    </div>
                </div>

                <div style="background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 8px; padding: 12px 16px; margin-bottom: 24px; font-size: 12px; color: #0369a1;">
                    <i class="fa-solid fa-shield-halved"></i> <strong>Concurrency Safety:</strong> This booking will execute inside a database transaction with a row-level lock on the target room to verify availability and prevent simultaneous double-booking.
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 12px 24px; font-size: 14px;">
                    <i class="fa-solid fa-check"></i> Confirm & Book Reservation
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleGuestMode(mode) {
        if (mode === 'new') {
            document.getElementById('newGuestFields').style.display = 'block';
            document.getElementById('existingGuestFields').style.display = 'none';
        } else {
            document.getElementById('newGuestFields').style.display = 'none';
            document.getElementById('existingGuestFields').style.display = 'block';
        }
    }
</script>
@endsection
