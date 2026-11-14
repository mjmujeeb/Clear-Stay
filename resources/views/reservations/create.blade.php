@extends('layouts.app')

@section('title', __('clearstay.reservations.new_reservation'))
@section('header_title', __('clearstay.reservations.new_reservation'))
@section('header_subtitle', 'Intelligent, date-aware hotel reservation creation with dynamic weekend and holiday rate calculation.')

@section('content')
<div style="max-width: 1000px; margin: 0 auto; padding-bottom: 60px;">
    <div class="card" style="border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden;">
        <div class="card-header" style="background: #F8FAFC; padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: #EEF2FF; color: #4F46E5; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="fa-solid fa-calendar-plus"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #0F172A;">
                        {{ app()->getLocale() === 'ja' ? '新規宿泊予約の作成' : 'Create Guest Reservation' }}
                    </h3>
                    <p style="margin: 2px 0 0; font-size: 12px; color: #64748B;">
                        {{ app()->getLocale() === 'ja' ? '自動料金エンジンによる日別料金計算および競合防止行ロック保護' : 'Real-time dynamic rate calculation with concurrency row-level locking' }}
                    </p>
                </div>
            </div>
            <a href="{{ route('reservations.index') }}" class="btn btn-secondary btn-sm" style="border-radius: 8px;">
                <i class="fa-solid fa-arrow-left"></i> {{ app()->getLocale() === 'ja' ? '予約一覧へ戻る' : 'Back to List' }}
            </a>
        </div>
        <div class="card-body" style="padding: 28px;">
            <form action="{{ route('reservations.store') }}" method="POST" id="reservationForm">
                @csrf

                <!-- Guest Selection Mode -->
                <div style="margin-bottom: 24px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 18px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #1E293B; margin-bottom: 10px;">
                        {{ app()->getLocale() === 'ja' ? '宿泊者情報' : 'Guest Identification' }}
                    </label>
                    <div style="display: flex; gap: 24px; margin-bottom: 14px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 600; color: #334155;">
                            <input type="radio" name="guest_mode" value="new" checked onchange="toggleGuestMode('new')">
                            <span>{{ app()->getLocale() === 'ja' ? '新規ゲスト登録' : 'Register New Guest' }}</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 600; color: #334155;">
                            <input type="radio" name="guest_mode" value="existing" onchange="toggleGuestMode('existing')">
                            <span>{{ app()->getLocale() === 'ja' ? '既存の登録ゲストを選択' : 'Select Existing Registered Guest' }}</span>
                        </label>
                    </div>

                    <!-- New Guest Fields -->
                    <div id="newGuestFields">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 12px;">
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? '名 (First Name) *' : 'First Name *' }}</label>
                                <input type="text" name="first_name" value="{{ old('first_name') }}" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 13px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? '姓 (Last Name) *' : 'Last Name *' }}</label>
                                <input type="text" name="last_name" value="{{ old('last_name') }}" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 13px;">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? 'メールアドレス *' : 'Email Address *' }}</label>
                                <input type="email" name="email" value="{{ old('email') }}" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 13px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? '電話番号' : 'Phone Number' }}</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+81-90-1234-5678" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 13px;">
                            </div>
                        </div>
                    </div>

                    <!-- Existing Guest Select -->
                    <div id="existingGuestFields" style="display: none;">
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">{{ app()->getLocale() === 'ja' ? '登録済みゲストを選択' : 'Select Guest Profile' }}</label>
                        <select name="guest_id" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 13px;">
                            <option value="">-- {{ app()->getLocale() === 'ja' ? 'ゲストを選択してください' : 'Choose Guest' }} --</option>
                            @foreach($guests as $g)
                                <option value="{{ $g->id }}">{{ $g->full_name }} ({{ $g->email }}) - VIP: {{ strtoupper($g->vip_level) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Room & Dates Selection -->
                <div style="margin-bottom: 24px;">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 13px; font-weight: 700; color: #1E293B; margin-bottom: 6px;">
                            {{ app()->getLocale() === 'ja' ? '客室の選択 *' : 'Select Room *' }}
                        </label>
                        <select name="room_id" id="roomSelect" required style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px; font-weight: 600;">
                            <option value="">-- {{ app()->getLocale() === 'ja' ? '客室を選択してください' : 'Select Room' }} --</option>
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}" data-price="{{ $r->roomType->base_price }}" data-type="{{ $r->roomType->name }}" {{ request('room_id') == $r->id ? 'selected' : '' }}>
                                    Room {{ $r->room_number }} - {{ $r->roomType->name }} ({{ $r->floor }}F | ¥{{ number_format($r->roomType->base_price) }}/{{ app()->getLocale() === 'ja' ? '泊' : 'night' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; color: #1E293B; margin-bottom: 6px;">
                                {{ app()->getLocale() === 'ja' ? 'チェックイン日 (Check-In) *' : 'Check-In Date *' }}
                            </label>
                            <input type="date" name="check_in_date" id="checkInDate" value="{{ old('check_in_date', date('Y-m-d')) }}" required
                                style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px; font-weight: 600;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; color: #1E293B; margin-bottom: 6px;">
                                {{ app()->getLocale() === 'ja' ? 'チェックアウト日 (Check-Out) *' : 'Check-Out Date *' }}
                            </label>
                            <input type="date" name="check_out_date" id="checkOutDate" value="{{ old('check_out_date', date('Y-m-d', strtotime('+1 day'))) }}" required
                                style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px; font-weight: 600;">
                            <div style="font-size: 11px; color: #64748B; margin-top: 4px;">
                                <i class="fa-solid fa-circle-info"></i> {{ app()->getLocale() === 'ja' ? 'チェックアウト当日の宿泊料金は請求されません。' : 'Check-out day is not charged as an occupied night.' }}
                            </div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; color: #1E293B; margin-bottom: 6px;">{{ app()->getLocale() === 'ja' ? '大人の人数' : 'Adults Count' }}</label>
                            <input type="number" name="adults" value="{{ old('adults', 1) }}" min="1" max="10" required
                                style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; color: #1E293B; margin-bottom: 6px;">{{ app()->getLocale() === 'ja' ? '子どもの人数' : 'Children Count' }}</label>
                            <input type="number" name="children" value="{{ old('children', 0) }}" min="0" max="10"
                                style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px;">
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: #1E293B; margin-bottom: 6px;">{{ app()->getLocale() === 'ja' ? '特別リクエスト・特記事項' : 'Special Requests / Concierge Notes' }}</label>
                        <textarea name="special_requests" rows="2" placeholder="{{ app()->getLocale() === 'ja' ? '例: 禁煙ルーム希望、高層階、記念日利用など' : 'e.g. Non-smoking, high floor, anniversary surprise' }}"
                            style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px;">{{ old('special_requests') }}</textarea>
                    </div>
                </div>

                <!-- Live Dynamic Pricing Quote & Nightly Breakdown Card -->
                <div id="priceQuoteCard" style="background: #F8FAFC; border: 1px solid #CBD5E1; border-radius: 12px; padding: 20px; margin-bottom: 24px; display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #E2E8F0; padding-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: #E0E7FF; color: #4338CA; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                                <i class="fa-solid fa-calculator"></i>
                            </div>
                            <div>
                                <h4 style="font-size: 15px; font-weight: 800; color: #0F172A; margin: 0;">
                                    {{ app()->getLocale() === 'ja' ? '自動料金見積・日別計算明細' : 'Dynamic Price Calculation Quote' }}
                                </h4>
                                <div style="display: flex; align-items: center; gap: 8px; margin-top: 2px;">
                                    <span id="quoteNightsBadge" class="badge" style="background: #EEF2FF; color: #3730A3; font-weight: 700;">1 Night</span>
                                    <span id="quoteAvailabilityBadge" class="badge" style="background: #D1FAE5; color: #065F46; font-weight: 700;">Available</span>
                                </div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 11px; color: #64748B; font-weight: 600;">
                                {{ app()->getLocale() === 'ja' ? '合計ご請求金額 (税込)' : 'Authoritative Grand Total' }}
                            </div>
                            <div id="quoteGrandTotal" style="font-size: 26px; font-weight: 900; color: #0F172A;">
                                ¥0
                            </div>
                        </div>
                    </div>

                    <!-- Nightly Breakdown Table -->
                    <div style="overflow-x: auto; margin-bottom: 14px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px; background: white; border-radius: 8px; border: 1px solid #E2E8F0; overflow: hidden;">
                            <thead>
                                <tr style="background: #F1F5F9; text-align: left; color: #475569;">
                                    <th style="padding: 8px 10px;">{{ app()->getLocale() === 'ja' ? '宿泊日' : 'Date' }}</th>
                                    <th style="padding: 8px 10px;">{{ app()->getLocale() === 'ja' ? '曜日' : 'Day' }}</th>
                                    <th style="padding: 8px 10px;">{{ app()->getLocale() === 'ja' ? '基準料金' : 'Base Rate' }}</th>
                                    <th style="padding: 8px 10px;">{{ app()->getLocale() === 'ja' ? '適用料金ルール・理由' : 'Pricing Rule / Reason' }}</th>
                                    <th style="padding: 8px 10px; text-align: right;">{{ app()->getLocale() === 'ja' ? '適用泊室料' : 'Nightly Rate' }}</th>
                                </tr>
                            </thead>
                            <tbody id="quoteBreakdownBody">
                                <!-- Populated dynamically by JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pricing Summary Totals -->
                    <div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 12px 16px; border-radius: 8px; border: 1px solid #E2E8F0; font-size: 12px;">
                        <div style="color: #64748B;">
                            {{ app()->getLocale() === 'ja' ? '基準合計' : 'Base Total' }}: <strong id="quoteBaseTotal" style="color: #1E293B;">¥0</strong> | 
                            {{ app()->getLocale() === 'ja' ? '割増・調整' : 'Surcharges' }}: <strong id="quoteSurchargeTotal" style="color: #DC2626;">+¥0</strong> | 
                            {{ app()->getLocale() === 'ja' ? '内消費税' : 'Tax Total' }}: <strong id="quoteTaxTotal" style="color: #1E293B;">¥0</strong>
                        </div>
                        <div style="font-size: 11px; color: #94A3B8;">
                            <i class="fa-solid fa-lock"></i> {{ app()->getLocale() === 'ja' ? 'サーバー側で確定金額を再計算しスナップショットを保存します' : 'Authoritatively saved in snapshot on confirmation' }}
                        </div>
                    </div>
                </div>

                <!-- Overlap / Concurrency Safety Notice -->
                <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 10px; padding: 12px 16px; margin-bottom: 24px; font-size: 12px; color: #166534; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-shield-halved" style="font-size: 16px; color: #16A34A;"></i>
                    <span>
                        <strong>{{ app()->getLocale() === 'ja' ? '二重予約防止・行ロック保護:' : 'Double-Booking Prevention:' }}</strong> 
                        {{ app()->getLocale() === 'ja' ? '予約確定時にトランザクション内で対象客室の行ロック（lockForUpdate）を取得し、重複予約を確実に防ぎます。' : 'This reservation will execute inside a database transaction with a row-level lock on the target room.' }}
                    </span>
                </div>

                <button type="submit" id="btnSubmitReservation" class="btn btn-primary" style="padding: 12px 28px; font-size: 15px; font-weight: 800; border-radius: 8px; background: #0F172A; border: none; cursor: pointer;">
                    <i class="fa-solid fa-calendar-check"></i> {{ app()->getLocale() === 'ja' ? '上記料金で予約を確定する' : 'Confirm & Book Reservation' }}
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

document.addEventListener('DOMContentLoaded', function () {
    const roomSelect = document.getElementById('roomSelect');
    const checkInInput = document.getElementById('checkInDate');
    const checkOutInput = document.getElementById('checkOutDate');
    const quoteCard = document.getElementById('priceQuoteCard');
    const btnSubmit = document.getElementById('btnSubmitReservation');

    let debounceTimer = null;

    function fetchPriceQuote() {
        const roomId = roomSelect.value;
        const checkIn = checkInInput.value;
        const checkOut = checkOutInput.value;

        if (!roomId || !checkIn || !checkOut || checkIn >= checkOut) {
            quoteCard.style.display = 'none';
            return;
        }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetch("{{ route('reservations.price_quote') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    room_id: roomId,
                    check_in_date: checkIn,
                    check_out_date: checkOut
                })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    quoteCard.style.display = 'none';
                    return;
                }

                const q = data.quote;
                quoteCard.style.display = 'block';

                // Badges and status
                document.getElementById('quoteNightsBadge').textContent = q.nights_count + ' ' + (q.nights_count > 1 ? 'Nights' : 'Night');
                const availBadge = document.getElementById('quoteAvailabilityBadge');
                if (q.is_available) {
                    availBadge.textContent = 'Room Available';
                    availBadge.style.background = '#D1FAE5';
                    availBadge.style.color = '#065F46';
                    btnSubmit.disabled = false;
                } else {
                    availBadge.textContent = 'Room Occupied (' + (q.conflicting_code || 'Conflict') + ')';
                    availBadge.style.background = '#FEE2E2';
                    availBadge.style.color = '#991B1B';
                    btnSubmit.disabled = true;
                }

                document.getElementById('quoteGrandTotal').textContent = '¥' + Number(q.grand_total).toLocaleString();
                document.getElementById('quoteBaseTotal').textContent = '¥' + Number(q.base_total).toLocaleString();
                document.getElementById('quoteSurchargeTotal').textContent = '+¥' + Number(q.surcharge_total).toLocaleString();
                document.getElementById('quoteTaxTotal').textContent = '¥' + Number(q.tax_total).toLocaleString();

                const tbody = document.getElementById('quoteBreakdownBody');
                tbody.innerHTML = '';

                q.nightly_breakdown.forEach(night => {
                    let badgeHtml = '';
                    if (night.applied_rules && night.applied_rules.length > 0) {
                        night.applied_rules.forEach(r => {
                            let colorBg = r.badge_color === 'red' ? '#FEE2E2' : (r.badge_color === 'amber' ? '#FEF3C7' : (r.badge_color === 'purple' ? '#EDE9FE' : '#DBEAFE'));
                            let colorText = r.badge_color === 'red' ? '#991B1B' : (r.badge_color === 'amber' ? '#92400E' : (r.badge_color === 'purple' ? '#5B21B6' : '#1E40AF'));
                            badgeHtml += `<span class="badge" style="background: ${colorBg}; color: ${colorText}; margin-right: 4px; font-size: 11px; padding: 2px 6px; border-radius: 4px;">${r.badge}</span>`;
                        });
                    } else {
                        badgeHtml = '<span style="color: #94A3B8; font-size: 11px;">Standard Base Rate</span>';
                    }

                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid #F1F5F9';
                    tr.innerHTML = `
                        <td style="padding: 8px 10px; font-weight: 700; color: #1E293B;">${night.date}</td>
                        <td style="padding: 8px 10px; color: ${night.is_weekend ? '#D97706' : '#64748B'}; font-weight: ${night.is_weekend ? '800' : '600'};">
                            ${night.day_of_week} (${night.day_name_ja})
                        </td>
                        <td style="padding: 8px 10px; color: #64748B;">¥${Number(night.base_rate).toLocaleString()}</td>
                        <td style="padding: 8px 10px;">${badgeHtml}</td>
                        <td style="padding: 8px 10px; text-align: right; font-weight: 800; color: #0F172A;">
                            ¥${Number(night.nightly_rate).toLocaleString()}
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            })
            .catch(err => {
                console.error('Price quote failed', err);
            });
        }, 250);
    }

    roomSelect.addEventListener('change', fetchPriceQuote);
    checkInInput.addEventListener('change', fetchPriceQuote);
    checkOutInput.addEventListener('change', fetchPriceQuote);

    // Initial trigger if room already selected
    if (roomSelect.value) {
        fetchPriceQuote();
    }
});
</script>
@endsection
