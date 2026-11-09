@extends('layouts.app')

@section('title', 'かんたん新規予約作成 (Easy Booking) - ClearStay')
@section('header_title', 'かんたん操作・新規予約登録')
@section('header_subtitle', '大きな入力欄と分かりやすいガイダンスによるシンプル予約作成')

@section('content')
<div style="max-width: 720px; margin: 0 auto;">
    <div style="margin-bottom: 20px;">
        <a href="{{ route('easy_mode.index') }}" class="btn btn-secondary" style="font-size: 16px; padding: 12px 20px;">
            <i class="fa-solid fa-arrow-left"></i> かんたんメニューに戻る
        </a>
    </div>

    <div class="card" style="border: 2px solid #287F79; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.06);">
        <div class="card-header" style="background: #E6F3F2; padding: 20px 24px; border-bottom: 2px solid #287F79;">
            <h3 style="font-size: 20px; font-weight: 800; color: #183B50; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-calendar-plus" style="color: #287F79;"></i> 新規予約の入力 (Create Reservation)
            </h3>
        </div>

        <div class="card-body" style="padding: 28px;">
            <form action="{{ route('easy_mode.store') }}" method="POST">
                @csrf

                <!-- Step 1: Guest Name -->
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 16px; font-weight: 700; color: #183B50; margin-bottom: 8px;">
                        1. お客様のお名前 (Guest Full Name) <span style="color: #EF4444;">*</span>
                    </label>
                    <input type="text" name="guest_name" required placeholder="例: 佐藤 健司 / Kenji Sato" 
                        value="{{ old('guest_name') }}"
                        style="width: 100%; padding: 14px 18px; font-size: 18px; border: 2px solid var(--border); border-radius: 10px; outline: none; background: #f8fafc;">
                    <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">漢字またはローマ字で入力してください。</div>
                </div>

                <!-- Step 2: Guest Phone -->
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 16px; font-weight: 700; color: #183B50; margin-bottom: 8px;">
                        2. ご連絡先電話番号 (Phone Number)
                    </label>
                    <input type="text" name="guest_phone" placeholder="例: 090-1234-5678" 
                        value="{{ old('guest_phone') }}"
                        style="width: 100%; padding: 14px 18px; font-size: 18px; border: 2px solid var(--border); border-radius: 10px; outline: none; background: #f8fafc;">
                </div>

                <!-- Step 3: Room Selection -->
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 16px; font-weight: 700; color: #183B50; margin-bottom: 8px;">
                        3. お部屋の選択 (Select Room) <span style="color: #EF4444;">*</span>
                    </label>
                    <select name="room_id" required style="width: 100%; padding: 14px 18px; font-size: 18px; border: 2px solid var(--border); border-radius: 10px; outline: none; background: #ffffff; cursor: pointer;">
                        <option value="">お部屋を選んでください</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                {{ $room->room_number }}号室 — {{ $room->roomType->name }} (1泊 ¥{{ number_format($room->roomType->base_price) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Step 4: Dates -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 32px;">
                    <div>
                        <label style="display: block; font-size: 16px; font-weight: 700; color: #183B50; margin-bottom: 8px;">
                            4. チェックイン日 (Arrival) <span style="color: #EF4444;">*</span>
                        </label>
                        <input type="date" name="check_in_date" required value="{{ old('check_in_date', $today) }}"
                            style="width: 100%; padding: 14px 18px; font-size: 18px; border: 2px solid var(--border); border-radius: 10px; outline: none; background: #ffffff;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 16px; font-weight: 700; color: #183B50; margin-bottom: 8px;">
                            5. チェックアウト日 (Departure) <span style="color: #EF4444;">*</span>
                        </label>
                        <input type="date" name="check_out_date" required value="{{ old('check_out_date', $tomorrow) }}"
                            style="width: 100%; padding: 14px 18px; font-size: 18px; border: 2px solid var(--border); border-radius: 10px; outline: none; background: #ffffff;">
                    </div>
                </div>

                <!-- Submit Button -->
                <div style="display: flex; gap: 14px; align-items: center;">
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 16px 28px; font-size: 18px; font-weight: 800; border-radius: 12px; background: #287F79; justify-content: center; box-shadow: 0 4px 12px rgba(40, 127, 121, 0.3);">
                        <i class="fa-solid fa-check"></i> この内容で予約を登録する (Register Reservation)
                    </button>
                    <a href="{{ route('easy_mode.index') }}" class="btn btn-secondary" style="padding: 16px 24px; font-size: 16px;">
                        キャンセル
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
