@extends('layouts.easy')

@section('title', '5ステップ・かんたんチェックイン案内 - ClearStay Easy Mode')

@push('styles')
<style>
    .wizard-container {
        max-width: 1040px;
        margin: 0 auto;
        padding-bottom: 60px;
    }

    /* Stepper Bar */
    .wizard-stepper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #ffffff;
        border: 2px solid var(--easy-border);
        border-radius: 16px;
        padding: 18px 24px;
        margin-bottom: 28px;
        box-shadow: 0 4px 14px rgba(15, 29, 47, 0.05);
        overflow-x: auto;
        gap: 12px;
    }

    .step-item {
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 700;
        font-size: 17px;
        color: var(--easy-muted);
        white-space: nowrap;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 10px;
        transition: all 0.2s ease;
    }

    .step-item.active {
        color: var(--easy-navy);
        background: #E6F4F1;
        font-weight: 900;
    }

    .step-item.completed {
        color: var(--easy-teal);
    }

    .step-num {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #E2E8F0;
        color: #475569;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        font-weight: 900;
        flex-shrink: 0;
    }

    .step-item.active .step-num {
        background: var(--easy-teal);
        color: #ffffff;
        box-shadow: 0 3px 8px rgba(31, 107, 102, 0.35);
    }

    .step-item.completed .step-num {
        background: #059669;
        color: #ffffff;
    }

    .step-divider {
        flex: 1;
        height: 3px;
        background: #E2E8F0;
        min-width: 20px;
    }

    /* Step Card */
    .wizard-card {
        background: #ffffff;
        border: 3px solid var(--easy-border);
        border-radius: 20px;
        padding: 36px 32px;
        box-shadow: 0 6px 20px rgba(15, 29, 47, 0.06);
    }

    .wizard-card-title {
        font-size: 26px;
        font-weight: 900;
        color: var(--easy-navy);
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 10px;
    }

    .wizard-card-subtitle {
        font-size: 17px;
        color: var(--easy-muted);
        margin-bottom: 28px;
        line-height: 1.6;
    }

    /* Tactile Form Elements */
    .tactile-input {
        width: 100%;
        min-height: 56px;
        font-size: 20px;
        padding: 12px 20px;
        border: 3px solid var(--easy-border);
        border-radius: 12px;
        font-weight: 600;
        color: var(--easy-text);
        background: #FAFAFA;
        box-sizing: border-box;
        transition: border-color 0.2s;
    }

    .tactile-input:focus {
        border-color: var(--easy-teal);
        background: #ffffff;
        outline: none;
        box-shadow: 0 0 0 4px rgba(31, 107, 102, 0.15);
    }

    /* Tactile Room Grid */
    .room-tile-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 20px;
    }

    .room-tile-card {
        background: #F8FAFC;
        border: 3px solid #CBD5E1;
        border-radius: 16px;
        padding: 20px;
        cursor: pointer;
        text-align: center;
        transition: all 0.2s ease;
        position: relative;
    }

    .room-tile-card:hover {
        border-color: var(--easy-teal);
        transform: translateY(-2px);
    }

    .room-tile-card.selected {
        border-color: var(--easy-teal);
        background: #E6F4F1;
        box-shadow: 0 6px 16px rgba(31, 107, 102, 0.2);
    }

    .room-tile-card .selected-badge {
        display: none;
        position: absolute;
        top: 10px;
        right: 10px;
        background: var(--easy-teal);
        color: #ffffff;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }

    .room-tile-card.selected .selected-badge {
        display: flex;
    }

    .room-tile-num {
        font-size: 32px;
        font-weight: 900;
        color: var(--easy-navy);
        line-height: 1;
        margin-bottom: 6px;
    }

    .room-tile-type {
        font-size: 15px;
        font-weight: 700;
        color: var(--easy-muted);
        margin-bottom: 8px;
    }

    .room-tile-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 800;
        background: #D1FAE5;
        color: #047857;
    }

    /* Button Bar */
    .wizard-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 36px;
        padding-top: 24px;
        border-top: 2px solid #E2E8F0;
        gap: 16px;
        flex-wrap: wrap;
    }

    .btn-step-prev {
        background: #E2E8F0;
        color: #334155;
        border: none;
        border-radius: 12px;
        min-height: 54px;
        padding: 12px 28px;
        font-size: 18px;
        font-weight: 800;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .btn-step-next {
        background: var(--easy-teal);
        color: #ffffff;
        border: none;
        border-radius: 12px;
        min-height: 54px;
        padding: 12px 36px;
        font-size: 20px;
        font-weight: 800;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 4px 12px rgba(31, 107, 102, 0.3);
    }

    .btn-step-next:disabled {
        background: #94A3B8;
        cursor: not-allowed;
        box-shadow: none;
    }

    /* Step 1 Result Row */
    .search-result-row {
        background: #F8FAFC;
        border: 2px solid #CBD5E1;
        border-radius: 14px;
        padding: 18px 22px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .search-result-row:hover, .search-result-row.active {
        border-color: var(--easy-teal);
        background: #F0FDF4;
        box-shadow: 0 4px 12px rgba(31, 107, 102, 0.1);
    }
</style>
@endpush

@section('content')
<div class="wizard-container">

    <!-- Top Breadcrumb & Heading -->
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div>
            <a href="{{ route('easy_mode.index') }}" style="color: var(--easy-teal); font-weight: 700; text-decoration: none; font-size: 16px; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-arrow-left"></i> かんたん操作ホームへ戻る / Back to Easy Home
            </a>
            <h1 style="font-size: 32px; font-weight: 900; color: var(--easy-navy); margin-top: 6px;">
                <i class="fa-solid fa-route" style="color: var(--easy-teal);"></i> 5ステップ・かんたんチェックイン案内
            </h1>
            <p style="font-size: 17px; color: var(--easy-muted);">
                シニアスタッフや新人の方でも迷わず操作できる、安心・確実なステップ別チェックイン手続きです。
            </p>
        </div>
        <div>
            <span style="background: #E6F4F1; color: var(--easy-teal); padding: 8px 16px; border-radius: 12px; font-weight: 800; font-size: 16px;">
                <i class="fa-solid fa-hotel"></i> {{ session('current_hotel_name', 'ClearStay Resort') }}
            </span>
        </div>
    </div>

    <!-- 5-Step Stepper Header -->
    <div class="wizard-stepper" role="tablist">
        <div class="step-item active" id="tab-step-1" onclick="goToStep(1)">
            <div class="step-num">1</div>
            <div>
                <div>予約の検索・選択</div>
                <div style="font-size: 12px; color: var(--easy-muted);">Find Reservation</div>
            </div>
        </div>
        <div class="step-divider"></div>
        <div class="step-item" id="tab-step-2" onclick="goToStep(2)">
            <div class="step-num">2</div>
            <div>
                <div>本人確認・詳細</div>
                <div style="font-size: 12px; color: var(--easy-muted);">Verify Identity</div>
            </div>
        </div>
        <div class="step-divider"></div>
        <div class="step-item" id="tab-step-3" onclick="goToStep(3)">
            <div class="step-num">3</div>
            <div>
                <div>部屋の割当・確認</div>
                <div style="font-size: 12px; color: var(--easy-muted);">Room Assignment</div>
            </div>
        </div>
        <div class="step-divider"></div>
        <div class="step-item" id="tab-step-4" onclick="goToStep(4)">
            <div class="step-num">4</div>
            <div>
                <div>内容確認・鍵準備</div>
                <div style="font-size: 12px; color: var(--easy-muted);">Review & Keys</div>
            </div>
        </div>
        <div class="step-divider"></div>
        <div class="step-item" id="tab-step-5" onclick="goToStep(5)">
            <div class="step-num">5</div>
            <div>
                <div>チェックイン完了</div>
                <div style="font-size: 12px; color: var(--easy-muted);">Completed</div>
            </div>
        </div>
    </div>

    <!-- Hidden Form for submission -->
    <form id="wizard-form" method="POST" action="{{ route('easy_mode.checkin_wizard.complete') }}">
        @csrf
        <input type="hidden" name="reservation_id" id="hidden_reservation_id" value="{{ $selectedReservation->id ?? '' }}">
        <input type="hidden" name="room_id" id="hidden_room_id" value="{{ $selectedReservation->room_id ?? '' }}">
        <input type="hidden" name="keycards_issued" id="hidden_keycards" value="1">
        <input type="hidden" name="passport_or_id" id="hidden_passport_or_id" value="">
        <input type="hidden" name="guest_phone" id="hidden_guest_phone" value="">

        <!-- STEP 1: FIND RESERVATION -->
        <div class="wizard-card step-panel" id="panel-step-1">
            <div class="wizard-card-title">
                <i class="fa-solid fa-magnifying-glass" style="color: var(--easy-teal);"></i>
                ステップ 1: お客様のご予約を検索または選択してください
            </div>
            <div class="wizard-card-subtitle">
                お名前、電話番号、または予約番号（CS-XXXX-XXXX）を入力してください。本日の到着予定からも直接選べます。
            </div>

            <!-- Search Bar -->
            <div style="margin-bottom: 24px; position: relative;">
                <label for="search-input" style="display: block; font-weight: 800; font-size: 18px; margin-bottom: 8px;">
                    <i class="fa-solid fa-keyboard"></i> お名前 または 予約番号を入力 (Search Query):
                </label>
                <div style="display: flex; gap: 12px;">
                    <input type="text" id="search-input" class="tactile-input" placeholder="例: 田中、CS-8921、090-XXXX-XXXX" value="{{ $selectedReservation->guest->first_name ?? '' }}">
                    <button type="button" class="easy-btn" style="background: var(--easy-teal); color: #fff; flex-shrink: 0;" onclick="executeSearch()">
                        <i class="fa-solid fa-magnifying-glass"></i> 検索
                    </button>
                </div>
            </div>

            <!-- Search Results Container -->
            <div id="search-results-box" style="margin-bottom: 28px; display: none;">
                <h3 style="font-size: 19px; font-weight: 900; margin-bottom: 12px; color: var(--easy-navy);">
                    <i class="fa-solid fa-list-check"></i> 検索結果 (該当件数: <span id="search-count">0</span>件)
                </h3>
                <div id="search-results-list"></div>
            </div>

            <!-- Today's Arrivals Fallback / Fast Pick -->
            <div id="today-arrivals-box">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 style="font-size: 19px; font-weight: 900; color: var(--easy-navy);">
                        <i class="fa-solid fa-plane-arrival" style="color: #059669;"></i> 本日の到着予定ゲスト (Today's Arrivals)
                    </h3>
                    <span style="font-size: 15px; color: var(--easy-muted); font-weight: 700;">
                        {{ count($todayArrivals) }} 件の到着予定
                    </span>
                </div>

                @if(count($todayArrivals) > 0)
                    @foreach($todayArrivals as $arr)
                        <div class="search-result-row {{ ($selectedReservation && $selectedReservation->id == $arr->id) ? 'active' : '' }}" onclick="selectReservation({{ json_encode([
                            'id' => $arr->id,
                            'confirmation_code' => $arr->confirmation_code,
                            'guest_name' => ($arr->guest->first_name ?? '') . ' ' . ($arr->guest->last_name ?? ''),
                            'phone' => $arr->guest->phone ?? '—',
                            'passport_or_id' => $arr->guest->passport_or_id ?? '—',
                            'room_number' => $arr->room->room_number ?? '未定',
                            'room_id' => $arr->room_id,
                            'room_type' => $arr->room->roomType->name ?? 'Standard',
                            'check_in_date' => $arr->check_in_date ? $arr->check_in_date->format('Y-m-d') : '',
                            'check_out_date' => $arr->check_out_date ? $arr->check_out_date->format('Y-m-d') : '',
                            'nights' => $arr->nights ?? 1,
                            'total_price' => number_format((float)$arr->total_price),
                            'status' => $arr->status
                        ]) }})">
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div style="width: 48px; height: 48px; border-radius: 50%; background: #D1FAE5; color: #047857; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <div>
                                    <div style="font-size: 20px; font-weight: 900; color: var(--easy-navy);">
                                        {{ $arr->guest->first_name ?? 'Guest' }} {{ $arr->guest->last_name ?? '' }} 様
                                    </div>
                                    <div style="font-size: 15px; color: var(--easy-muted); font-weight: 700;">
                                        予約番号: <strong>{{ $arr->confirmation_code }}</strong> &bull; 電話: {{ $arr->guest->phone ?? '未登録' }}
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 18px; font-weight: 800; color: var(--easy-teal);">
                                    部屋: {{ $arr->room->room_number ?? '未割当' }}
                                </div>
                                <div style="font-size: 14px; color: var(--easy-muted);">
                                    {{ $arr->nights ?? 1 }}泊 &bull; &yen;{{ number_format((float)$arr->total_price) }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div style="background: #F8FAFC; border: 2px dashed #CBD5E1; border-radius: 12px; padding: 28px; text-align: center; color: var(--easy-muted);">
                        <i class="fa-solid fa-calendar-check fa-2x" style="margin-bottom: 8px; color: #94A3B8;"></i>
                        <p style="font-size: 18px; font-weight: 700;">本日到着予定の未処理予約はありません。</p>
                        <p style="font-size: 15px;">上部の検索バーから予約番号やお名前で過去・今後の予約を検索できます。</p>
                    </div>
                @endif
            </div>

            <!-- Step 1 Actions -->
            <div class="wizard-actions">
                <div></div>
                <button type="button" class="btn-step-next" id="btn-next-1" onclick="goToStep(2)" disabled>
                    次へ進む (本人確認へ) &rarr;
                </button>
            </div>
        </div>

        <!-- STEP 2: VERIFY GUEST IDENTITY -->
        <div class="wizard-card step-panel" id="panel-step-2" style="display: none;">
            <div class="wizard-card-title">
                <i class="fa-solid fa-id-card" style="color: var(--easy-teal);"></i>
                ステップ 2: お客様のご本人確認と情報照合
            </div>
            <div class="wizard-card-subtitle">
                ご提示いただいた身分証明書（パスポート、運転免許証、マイナンバーカード等）とお名前・電話番号を照合してください。
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; background: #F8FAFC; border: 2px solid #CBD5E1; border-radius: 14px; padding: 24px; margin-bottom: 24px;">
                <div>
                    <label style="font-size: 15px; font-weight: 700; color: var(--easy-muted); display: block; margin-bottom: 4px;">お名前 (Guest Name):</label>
                    <div id="step2-guest-name" style="font-size: 24px; font-weight: 900; color: var(--easy-navy);">—</div>
                </div>
                <div>
                    <label style="font-size: 15px; font-weight: 700; color: var(--easy-muted); display: block; margin-bottom: 4px;">予約確認番号 (Confirmation Code):</label>
                    <div id="step2-confirm-code" style="font-size: 22px; font-weight: 900; color: var(--easy-teal);">—</div>
                </div>
                <div>
                    <label for="step2-phone-input" style="font-size: 15px; font-weight: 700; color: var(--easy-muted); display: block; margin-bottom: 4px;">連絡先電話番号 (Phone):</label>
                    <input type="text" id="step2-phone-input" class="tactile-input" style="min-height: 48px; font-size: 18px;" placeholder="例: 090-1234-5678">
                </div>
                <div>
                    <label for="step2-passport-input" style="font-size: 15px; font-weight: 700; color: var(--easy-muted); display: block; margin-bottom: 4px;">身分証番号 / パスポート番号 (ID/Passport):</label>
                    <input type="text" id="step2-passport-input" class="tactile-input" style="min-height: 48px; font-size: 18px;" placeholder="例: TK987654321">
                </div>
            </div>

            <!-- Mandatory Verification Checkbox with Large Touch Area -->
            <label style="display: flex; align-items: center; gap: 16px; background: #FEF3C7; border: 2px solid #F59E0B; border-radius: 14px; padding: 18px 24px; cursor: pointer; margin-bottom: 20px;">
                <input type="checkbox" id="chk-verify-id" style="width: 28px; height: 28px; accent-color: var(--easy-teal);" onchange="toggleIdVerification()">
                <span style="font-size: 19px; font-weight: 800; color: #92400E;">
                    <i class="fa-solid fa-circle-check"></i> 本人確認書類（パスポートまたは運転免許証など）を確認しました
                </span>
            </label>

            <!-- Step 2 Actions -->
            <div class="wizard-actions">
                <button type="button" class="btn-step-prev" onclick="goToStep(1)">
                    &larr; 前のステップに戻る
                </button>
                <button type="button" class="btn-step-next" id="btn-next-2" onclick="saveGuestAndNext()" disabled>
                    お部屋の割当へ進む &rarr;
                </button>
            </div>
        </div>

        <!-- STEP 3: ROOM ASSIGNMENT -->
        <div class="wizard-card step-panel" id="panel-step-3" style="display: none;">
            <div class="wizard-card-title">
                <i class="fa-solid fa-bed" style="color: var(--easy-teal);"></i>
                ステップ 3: お部屋の割り当て・確認
            </div>
            <div class="wizard-card-subtitle">
                現在割り当てられている部屋を確認するか、清掃完了済みの空室から新しいお部屋を選択してください。
            </div>

            <!-- Current Room Info Bar -->
            <div style="background: #E6F4F1; border: 2px solid var(--easy-teal); border-radius: 14px; padding: 18px 24px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                <div>
                    <span style="font-size: 15px; font-weight: 700; color: var(--easy-teal); text-transform: uppercase;">現在の指定客室</span>
                    <div style="font-size: 26px; font-weight: 900; color: var(--easy-navy);" id="step3-current-room-text">
                        Room 201 (Deluxe King)
                    </div>
                </div>
                <span class="room-tile-badge" style="font-size: 16px; padding: 8px 16px;">
                    <i class="fa-solid fa-check"></i> 清掃済み・入室準備完了
                </span>
            </div>

            <h3 style="font-size: 20px; font-weight: 900; color: var(--easy-navy); margin-top: 10px;">
                別の清掃完了室へ変更する場合は以下からタッチして選択:
            </h3>

            <!-- Available Rooms Grid -->
            <div class="room-tile-grid" id="available-rooms-grid">
                @foreach($availableRooms as $r)
                    <div class="room-tile-card" id="room-card-{{ $r->id }}" onclick="selectRoom({{ $r->id }}, '{{ $r->room_number }}', '{{ $r->roomType->name ?? 'Standard' }}')">
                        <div class="selected-badge"><i class="fa-solid fa-check"></i></div>
                        <div class="room-tile-num">{{ $r->room_number }}</div>
                        <div class="room-tile-type">{{ $r->roomType->name ?? 'Standard' }}</div>
                        <div class="room-tile-badge"><i class="fa-solid fa-sparkles"></i> 清掃完了</div>
                    </div>
                @endforeach
            </div>

            <!-- Step 3 Actions -->
            <div class="wizard-actions">
                <button type="button" class="btn-step-prev" onclick="goToStep(2)">
                    &larr; 前のステップに戻る
                </button>
                <button type="button" class="btn-step-next" id="btn-next-3" onclick="goToStep(4)">
                    内容確認へ進む &rarr;
                </button>
            </div>
        </div>

        <!-- STEP 4: REVIEW & KEYCARD PREPARATION -->
        <div class="wizard-card step-panel" id="panel-step-4" style="display: none;">
            <div class="wizard-card-title">
                <i class="fa-solid fa-key" style="color: var(--easy-teal);"></i>
                ステップ 4: ご宿泊内容の最終確認とルームキーの準備
            </div>
            <div class="wizard-card-subtitle">
                チェックイン前に以下の内容が正しいか確認し、お渡しするカードキーの枚数を準備してください。
            </div>

            <!-- Comprehensive Summary Card -->
            <div style="background: #F8FAFC; border: 3px solid #CBD5E1; border-radius: 16px; padding: 28px; margin-bottom: 24px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <div style="font-size: 14px; font-weight: 700; color: var(--easy-muted);">ご宿泊者様氏名</div>
                        <div id="step4-guest-name" style="font-size: 22px; font-weight: 900; color: var(--easy-navy);">—</div>
                    </div>
                    <div>
                        <div style="font-size: 14px; font-weight: 700; color: var(--easy-muted);">客室番号</div>
                        <div id="step4-room-num" style="font-size: 26px; font-weight: 900; color: var(--easy-teal);">Room —</div>
                    </div>
                    <div>
                        <div style="font-size: 14px; font-weight: 700; color: var(--easy-muted);">宿泊期間</div>
                        <div id="step4-dates" style="font-size: 18px; font-weight: 800; color: var(--easy-navy);">—</div>
                    </div>
                    <div>
                        <div style="font-size: 14px; font-weight: 700; color: var(--easy-muted);">宿泊合計代金</div>
                        <div id="step4-price" style="font-size: 22px; font-weight: 900; color: #059669;">&yen;—</div>
                    </div>
                </div>

                <div style="border-top: 2px dashed #CBD5E1; padding-top: 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <span style="font-size: 18px; font-weight: 800; color: var(--easy-navy);">
                            <i class="fa-solid fa-credit-card" style="color: #059669;"></i> 決済状況:
                        </span>
                        <span class="room-tile-badge" style="font-size: 16px; margin-left: 8px;">
                            決済済み (Paid)
                        </span>
                    </div>

                    <!-- Keycard Quantity Selector -->
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 18px; font-weight: 800; color: var(--easy-navy);">
                            お渡しするルームキー:
                        </span>
                        <div style="display: flex; align-items: center; border: 2px solid #CBD5E1; border-radius: 12px; overflow: hidden; background: #fff;">
                            <button type="button" style="width: 46px; height: 46px; font-size: 22px; border: none; background: #F1F5F9; cursor: pointer;" onclick="changeKeyCount(-1)">&minus;</button>
                            <span id="keycount-display" style="min-width: 50px; text-align: center; font-size: 20px; font-weight: 900;">1 枚</span>
                            <button type="button" style="width: 46px; height: 46px; font-size: 22px; border: none; background: #F1F5F9; cursor: pointer;" onclick="changeKeyCount(1)">&#43;</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4 Actions -->
            <div class="wizard-actions">
                <button type="button" class="btn-step-prev" onclick="goToStep(3)">
                    &larr; 前のステップに戻る
                </button>
                <button type="button" class="btn-step-next" style="background: #059669; font-size: 22px; padding: 14px 40px;" onclick="submitCheckinForm()">
                    <i class="fa-solid fa-check-to-slot"></i> チェックインを完了する (入室中に切替) &rarr;
                </button>
            </div>
        </div>

        <!-- STEP 5: COMPLETED CELEBRATION -->
        <div class="wizard-card step-panel" id="panel-step-5" style="display: none; text-align: center;">
            <div style="width: 96px; height: 96px; border-radius: 50%; background: #D1FAE5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 52px; margin: 0 auto 20px; box-shadow: 0 8px 24px rgba(5, 150, 105, 0.25);">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h2 style="font-size: 34px; font-weight: 900; color: var(--easy-navy); margin-bottom: 8px;">
                チェックインが完了しました！
            </h2>
            <p style="font-size: 19px; color: var(--easy-muted); margin-bottom: 28px;">
                お部屋のステータスが「入室中」に更新され、セキュアな台帳ログが記録されました。
            </p>

            <div style="max-width: 480px; margin: 0 auto 36px; background: #F0FDF4; border: 3px solid #86EFAC; border-radius: 18px; padding: 24px; text-align: center;">
                <div style="font-size: 16px; font-weight: 700; color: #166534;">お渡しするお部屋番号</div>
                <div id="step5-room-number" style="font-size: 54px; font-weight: 900; color: #14532D; line-height: 1.1; margin: 8px 0;">
                    Room 201
                </div>
                <div style="font-size: 18px; font-weight: 800; color: #166534;" id="step5-keys-instruction">
                    <i class="fa-solid fa-key"></i> ルームキー 1 枚をお客様へお渡しください
                </div>
            </div>

            <div style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
                <button type="button" class="easy-btn" style="background: var(--easy-teal); color: #fff; font-size: 20px; padding: 14px 32px;" onclick="resetWizardForNext()">
                    <i class="fa-solid fa-user-plus"></i> 次のお客様をチェックインする
                </button>
                <a href="{{ route('easy_mode.index') }}" class="easy-btn easy-btn-secondary" style="font-size: 20px; padding: 14px 28px;">
                    <i class="fa-solid fa-house"></i> かんたんホームへ戻る
                </a>
                <a href="{{ route('overview.index') }}" class="easy-btn" style="background: #E2E8F0; color: #334155; font-size: 18px;">
                    標準ダッシュボードで確認
                </a>
            </div>
        </div>

    </form>

</div>
@endsection

@push('scripts')
<script>
    let currentReservation = null;
    let selectedRoomId = null;
    let selectedRoomNum = '';
    let selectedRoomType = '';
    let keyCount = 1;

    document.addEventListener('DOMContentLoaded', function () {
        @if($selectedReservation)
            selectReservation({
                id: {{ $selectedReservation->id }},
                confirmation_code: '{{ $selectedReservation->confirmation_code }}',
                guest_name: '{{ ($selectedReservation->guest->first_name ?? '') . ' ' . ($selectedReservation->guest->last_name ?? '') }}',
                phone: '{{ $selectedReservation->guest->phone ?? '—' }}',
                passport_or_id: '{{ $selectedReservation->guest->passport_or_id ?? '—' }}',
                room_number: '{{ $selectedReservation->room->room_number ?? '未定' }}',
                room_id: {{ $selectedReservation->room_id ?? 'null' }},
                room_type: '{{ $selectedReservation->room->roomType->name ?? 'Standard' }}',
                check_in_date: '{{ $selectedReservation->check_in_date ? $selectedReservation->check_in_date->format('Y-m-d') : '' }}',
                check_out_date: '{{ $selectedReservation->check_out_date ? $selectedReservation->check_out_date->format('Y-m-d') : '' }}',
                nights: {{ $selectedReservation->nights ?? 1 }},
                total_price: '{{ number_format((float)$selectedReservation->total_price) }}',
                status: '{{ $selectedReservation->status }}'
            });
            @if($selectedReservation->status === 'checked_in')
                goToStep(5);
                document.getElementById('step5-room-number').innerText = 'Room {{ $selectedReservation->room->room_number ?? '' }}';
            @endif
        @endif
    });

    function goToStep(step) {
        if (step > 1 && !currentReservation) {
            alert('先にお客様の予約を選択してください / Please select a reservation first.');
            return;
        }

        // Hide all panels
        for (let i = 1; i <= 5; i++) {
            const panel = document.getElementById('panel-step-' + i);
            const tab = document.getElementById('tab-step-' + i);
            if (panel) panel.style.display = 'none';
            if (tab) {
                tab.classList.remove('active');
                if (i < step) {
                    tab.classList.add('completed');
                } else {
                    tab.classList.remove('completed');
                }
            }
        }

        // Show current panel & activate tab
        const activePanel = document.getElementById('panel-step-' + step);
        const activeTab = document.getElementById('tab-step-' + step);
        if (activePanel) activePanel.style.display = 'block';
        if (activeTab) activeTab.classList.add('active');

        // Populate step-specific information
        if (step === 2) {
            document.getElementById('step2-guest-name').innerText = currentReservation.guest_name + ' 様';
            document.getElementById('step2-confirm-code').innerText = currentReservation.confirmation_code;
            document.getElementById('step2-phone-input').value = currentReservation.phone !== '—' ? currentReservation.phone : '';
            document.getElementById('step2-passport-input').value = currentReservation.passport_or_id !== '—' ? currentReservation.passport_or_id : '';
        } else if (step === 3) {
            document.getElementById('step3-current-room-text').innerText = 'Room ' + selectedRoomNum + ' (' + selectedRoomType + ')';
            highlightSelectedRoomCard(selectedRoomId);
        } else if (step === 4) {
            document.getElementById('step4-guest-name').innerText = currentReservation.guest_name + ' 様';
            document.getElementById('step4-room-num').innerText = 'Room ' + selectedRoomNum + ' (' + selectedRoomType + ')';
            document.getElementById('step4-dates').innerText = currentReservation.check_in_date + ' 〜 ' + currentReservation.check_out_date + ' (' + currentReservation.nights + '泊)';
            document.getElementById('step4-price').innerText = '¥' + currentReservation.total_price;
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function selectReservation(res) {
        currentReservation = res;
        document.getElementById('hidden_reservation_id').value = res.id;
        document.getElementById('btn-next-1').disabled = false;

        selectedRoomId = res.room_id;
        selectedRoomNum = res.room_number;
        selectedRoomType = res.room_type;
        document.getElementById('hidden_room_id').value = res.room_id || '';

        // Highlight matching row in UI
        document.querySelectorAll('.search-result-row').forEach(el => el.classList.remove('active'));
    }

    function executeSearch() {
        const q = document.getElementById('search-input').value.trim();
        if (!q) return;

        fetch('{{ route('easy_mode.checkin_wizard.search') }}?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            const box = document.getElementById('search-results-box');
            const list = document.getElementById('search-results-list');
            const count = document.getElementById('search-count');
            list.innerHTML = '';

            if (data.results && data.results.length > 0) {
                count.innerText = data.results.length;
                data.results.forEach(item => {
                    const row = document.createElement('div');
                    row.className = 'search-result-row';
                    row.onclick = () => {
                        selectReservation(item);
                        row.classList.add('active');
                    };
                    row.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <div style="width: 48px; height: 48px; border-radius: 50%; background: #D1FAE5; color: #047857; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div>
                                <div style="font-size: 20px; font-weight: 900; color: var(--easy-navy);">
                                    ${item.guest_name} 様
                                </div>
                                <div style="font-size: 15px; color: var(--easy-muted); font-weight: 700;">
                                    予約番号: <strong>${item.confirmation_code}</strong> &bull; 電話: ${item.phone}
                                </div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 18px; font-weight: 800; color: var(--easy-teal);">
                                部屋: ${item.room_number}
                            </div>
                            <div style="font-size: 14px; color: var(--easy-muted);">
                                ${item.nights}泊 &bull; &yen;${item.total_price}
                            </div>
                        </div>
                    `;
                    list.appendChild(row);
                });
                box.style.display = 'block';
            } else {
                count.innerText = '0';
                list.innerHTML = '<div style="padding: 16px; color: var(--easy-muted); font-weight: 700;">一致する予約が見つかりませんでした。別のキーワードでお試しください。</div>';
                box.style.display = 'block';
            }
        });
    }

    function toggleIdVerification() {
        const isChecked = document.getElementById('chk-verify-id').checked;
        document.getElementById('btn-next-2').disabled = !isChecked;
    }

    function saveGuestAndNext() {
        document.getElementById('hidden_passport_or_id').value = document.getElementById('step2-passport-input').value;
        document.getElementById('hidden_guest_phone').value = document.getElementById('step2-phone-input').value;
        goToStep(3);
    }

    function selectRoom(id, num, type) {
        selectedRoomId = id;
        selectedRoomNum = num;
        selectedRoomType = type;
        document.getElementById('hidden_room_id').value = id;
        document.getElementById('step3-current-room-text').innerText = 'Room ' + num + ' (' + type + ')';
        highlightSelectedRoomCard(id);
    }

    function highlightSelectedRoomCard(id) {
        document.querySelectorAll('.room-tile-card').forEach(c => c.classList.remove('selected'));
        const card = document.getElementById('room-card-' + id);
        if (card) card.classList.add('selected');
    }

    function changeKeyCount(delta) {
        keyCount = Math.max(1, Math.min(4, keyCount + delta));
        document.getElementById('keycount-display').innerText = keyCount + ' 枚';
        document.getElementById('hidden_keycards').value = keyCount;
    }

    function submitCheckinForm() {
        const form = document.getElementById('wizard-form');
        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('step5-room-number').innerText = 'Room ' + (selectedRoomNum || data.reservation.room.room_number);
                document.getElementById('step5-keys-instruction').innerHTML = '<i class="fa-solid fa-key"></i> ルームキー ' + keyCount + ' 枚をお客様へお渡しください';
                goToStep(5);
            } else {
                alert(data.message || 'Check-in failed');
            }
        })
        .catch(err => {
            // Fallback: normal form submit
            form.submit();
        });
    }

    function resetWizardForNext() {
        window.location.href = '{{ route('easy_mode.checkin_wizard') }}';
    }
</script>
@endpush
