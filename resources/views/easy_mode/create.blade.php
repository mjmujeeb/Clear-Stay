@extends('layouts.easy')

@section('title', 'かんたん新規予約作成 (New Walk-in Reservation) — ClearStay かんたん操作')

@push('styles')
<style>
    .tactile-input {
        width: 100%;
        min-height: 54px;
        padding: 12px 18px;
        font-size: 19px;
        font-weight: 600;
        border: 3px solid var(--easy-border);
        border-radius: 12px;
        outline: none;
        background: #FAFAFA;
        color: var(--easy-text);
        transition: border-color 0.2s;
    }

    .tactile-input:focus {
        border-color: var(--easy-teal);
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(31, 107, 102, 0.15);
    }
</style>
@endpush

@section('content')
<div style="max-width: 820px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <a href="{{ route('easy_mode.index') }}" class="easy-btn easy-btn-secondary" style="margin-bottom: 16px;">
            <i class="fa-solid fa-arrow-left"></i> メインメニューに戻る (Home)
        </a>
        <h2 style="font-size: 28px; font-weight: 900; color: var(--easy-navy);">
            <i class="fa-solid fa-calendar-plus" style="color: #7E22CE;"></i> 新規予約の入力 (Create Reservation)
        </h2>
        <p style="font-size: 17px; color: var(--easy-muted); margin-top: 4px;">
            新規予約の入力・登録を行います。電話予約や直接ご来館のお客様の予約を、日付とお名前を選ぶだけで登録できます。
        </p>
    </div>

    <div class="easy-card">
        <form action="{{ route('easy_mode.store') }}" method="POST">
            @csrf

            <!-- 1. Dates Selection -->
            <div style="margin-bottom: 28px;">
                <label style="display: block; font-size: 18px; font-weight: 800; color: var(--easy-navy); margin-bottom: 8px;">
                    1. 宿泊日程の選択 (Stay Dates) <span style="color: #EF4444;">*</span>
                </label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <span style="font-size: 14px; font-weight: 700; color: var(--easy-muted); display: block; margin-bottom: 4px;">チェックイン日 (Check-in)</span>
                        <input type="date" name="check_in_date" id="checkInDate" required value="{{ old('check_in_date', $today) }}" class="tactile-input">
                    </div>
                    <div>
                        <span style="font-size: 14px; font-weight: 700; color: var(--easy-muted); display: block; margin-bottom: 4px;">チェックアウト日 (Check-out)</span>
                        <input type="date" name="check_out_date" id="checkOutDate" required value="{{ old('check_out_date', $tomorrow) }}" class="tactile-input">
                    </div>
                </div>
            </div>

            <!-- 2. Room Selection -->
            <div style="margin-bottom: 28px;">
                <label style="display: block; font-size: 18px; font-weight: 800; color: var(--easy-navy); margin-bottom: 8px;">
                    2. 空室・部屋の選択 (Select Clean Room) <span style="color: #EF4444;">*</span>
                </label>
                <select name="room_id" id="roomSelect" required class="tactile-input" style="background: #ffffff; cursor: pointer;">
                    <option value="">お部屋を選んでください (Select an available room)</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" data-price="{{ $room->roomType->base_price ?? 15000 }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                            {{ $room->room_number }}号室 ({{ $room->floor }}階) — {{ $room->roomType->name ?? '客室' }} · 1泊 ¥{{ number_format($room->roomType->base_price ?? 15000) }}
                        </option>
                    @endforeach
                </select>
                <div style="font-size: 14px; color: #059669; font-weight: 700; margin-top: 6px;">
                    <i class="fa-solid fa-circle-check"></i> 清掃完了（Clean）かつ空室の客室のみが表示されています。
                </div>
            </div>

            <!-- 3. Guest Name -->
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 18px; font-weight: 800; color: var(--easy-navy); margin-bottom: 8px;">
                    3. お客様のお名前 (Guest Full Name) <span style="color: #EF4444;">*</span>
                </label>
                <input type="text" name="guest_name" required placeholder="例: 佐藤 健司 / Kenji Sato" 
                    value="{{ old('guest_name') }}" class="tactile-input">
                <div style="font-size: 14px; color: var(--easy-muted); margin-top: 4px;">漢字またはローマ字（お名前と名字の間にスペース）で入力してください。</div>
            </div>

            <!-- 4. Guest Phone -->
            <div style="margin-bottom: 32px;">
                <label style="display: block; font-size: 18px; font-weight: 800; color: var(--easy-navy); margin-bottom: 8px;">
                    4. ご連絡先電話番号 (Phone Number)
                </label>
                <input type="text" name="guest_phone" placeholder="例: 090-1234-5678" 
                    value="{{ old('guest_phone') }}" class="tactile-input">
            </div>

            <!-- Submit Button with Double-Submission Guard -->
            <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                <button type="submit" class="easy-btn easy-btn-primary" style="flex: 1; min-height: 56px; font-size: 20px;" onclick="this.disabled=true; this.form.submit();">
                    <i class="fa-solid fa-check"></i> 予約を確定して登録する (Complete Booking)
                </button>
                <a href="{{ route('easy_mode.index') }}" class="easy-btn easy-btn-secondary" style="min-height: 56px;">
                    キャンセル
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
