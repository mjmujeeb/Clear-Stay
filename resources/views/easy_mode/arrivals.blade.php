@extends('layouts.easy')

@section('title', '本日のチェックイン受付 (Arrivals) — ClearStay かんたん操作')

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('easy_mode.index') }}" class="easy-btn easy-btn-secondary" style="margin-bottom: 16px;">
        <i class="fa-solid fa-arrow-left"></i> メインメニューに戻る (Home)
    </a>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 style="font-size: 28px; font-weight: 900; color: var(--easy-navy);">
                <i class="fa-solid fa-plane-arrival" style="color: #059669;"></i> 本日ご到着のお客様 (チェックイン受付)
            </h2>
            <p style="font-size: 17px; color: var(--easy-muted); margin-top: 4px;">
                本日チェックイン予定のご予約一覧です。お客様のご到着に合わせて「5ステップ案内」または「即時チェックイン」を選択してください。
            </p>
        </div>
        <div>
            <a href="{{ route('easy_mode.checkin_wizard') }}" class="easy-btn easy-btn-primary">
                <i class="fa-solid fa-wand-magic-sparkles"></i> 5ステップ・チェックイン案内
            </a>
        </div>
    </div>
</div>

@if($arrivals->isEmpty())
    <div class="easy-card" style="text-align: center; padding: 48px;">
        <i class="fa-solid fa-circle-check fa-3x" style="color: #059669; margin-bottom: 16px;"></i>
        <h3 style="font-size: 24px; font-weight: 800; color: var(--easy-navy);">本日の到着予定分はすべて受付済みです</h3>
        <p style="color: var(--easy-muted); font-size: 17px; margin-top: 8px;">未完了のチェックイン予定はありません。お部屋の準備や次の業務へ進んでください。</p>
        <div style="margin-top: 24px; display: flex; justify-content: center; gap: 12px;">
            <a href="{{ route('easy_mode.index') }}" class="easy-btn easy-btn-primary">
                ホームに戻る
            </a>
            <a href="{{ route('easy_mode.rooms') }}" class="easy-btn easy-btn-secondary">
                客室の清掃状態を確認
            </a>
        </div>
    </div>
@else
    <div style="display: flex; flex-direction: column; gap: 20px; margin-bottom: 40px;">
        @foreach($arrivals as $res)
            <div class="easy-card" style="border-left: 8px solid #059669; margin-bottom: 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                    <div style="flex: 1; min-width: 280px;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                            <span style="background: #DCFCE7; color: #14532D; font-size: 13px; font-weight: 800; padding: 3px 10px; border-radius: 6px;">
                                本日到着 (Arrival Today)
                            </span>
                            <span style="font-size: 14px; font-weight: 700; color: var(--easy-muted);">
                                予約番号: <strong style="color: var(--easy-navy); font-size: 16px;">{{ $res->confirmation_code }}</strong>
                            </span>
                            @if($res->channel)
                                <span style="background: #F1F5F9; color: var(--easy-navy); font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 4px;">
                                    {{ $res->channel->name }}
                                </span>
                            @endif
                        </div>

                        <h3 style="font-size: 26px; font-weight: 900; color: var(--easy-navy); margin: 6px 0;">
                            {{ $res->guest ? ($res->guest->full_name ?? ($res->guest->first_name . ' ' . $res->guest->last_name)) : 'ゲスト様' }} 様
                            <span style="font-size: 16px; font-weight: 600; color: var(--easy-muted); margin-left: 8px;">
                                ({{ $res->guest && $res->guest->phone ? $res->guest->phone : '電話番号未登録' }})
                            </span>
                        </h3>

                        <div style="font-size: 18px; color: var(--easy-muted); margin-top: 6px;">
                            割当部屋: <strong style="color: #059669; font-size: 22px;">{{ $res->room ? $res->room->room_number . '号室' : '未割当 (案内時に選択)' }}</strong> 
                            ({{ $res->room && $res->room->roomType ? $res->room->roomType->name : ($res->roomType ? $res->roomType->name : '標準ルーム') }})
                        </div>

                        <div style="font-size: 15px; color: var(--easy-muted); margin-top: 6px;">
                            宿泊期間: <strong>{{ $res->check_in_date ? $res->check_in_date->format('Y/m/d') : '' }}</strong> &rarr; <strong>{{ $res->check_out_date ? $res->check_out_date->format('Y/m/d') : '' }}</strong> 
                            ({{ $res->nights }}泊 · 大人{{ $res->adults }}名)
                        </div>
                    </div>

                    <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
                        <div style="font-size: 24px; font-weight: 900; color: var(--easy-navy);">
                            ¥{{ number_format($res->total_price) }}
                            <span style="font-size: 13px; font-weight: 700; color: #059669; display: block;">事前決済済 / Paid</span>
                        </div>

                        <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">
                            <!-- Option A: 5-Step Guided Wizard -->
                            <a href="{{ route('easy_mode.checkin_wizard', ['reservation_id' => $res->id]) }}" class="easy-btn easy-btn-primary">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> 5ステップ案内へ進む
                            </a>

                            <!-- Option B: 1-Click Fast Check-In -->
                            @if($res->room_id)
                                <form action="{{ route('easy_mode.check_in', $res->id) }}" method="POST" onsubmit="return confirm('{{ $res->guest ? $res->guest->last_name : '' }} 様のチェックインを確定しますか？\n部屋番号: {{ $res->room ? $res->room->room_number : '' }}号室');">
                                    @csrf
                                    <button type="submit" class="easy-btn easy-btn-success" onclick="this.disabled=true; this.form.submit();">
                                        <i class="fa-solid fa-key"></i> 即時チェックイン
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

<!-- Today's Checked-in Guests Record -->
@if(isset($checkedInToday) && $checkedInToday->isNotEmpty())
    <div style="margin-top: 36px;">
        <h3 style="font-size: 22px; font-weight: 800; color: var(--easy-navy); margin-bottom: 16px;">
            <i class="fa-solid fa-circle-check" style="color: #059669;"></i> 本日チェックイン完了済みのお客様 ({{ $checkedInToday->count() }}組)
        </h3>

        <div style="display: flex; flex-direction: column; gap: 12px;">
            @foreach($checkedInToday as $checked)
                <div class="easy-card" style="padding: 18px 24px; border: 1px solid var(--easy-border); background: #F8FAFC; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0;">
                    <div>
                        <strong style="font-size: 18px; color: var(--easy-navy);">{{ $checked->guest ? ($checked->guest->last_name . ' ' . $checked->guest->first_name) : 'ゲスト様' }} 様</strong>
                        <span style="color: var(--easy-muted); font-size: 15px; margin-left: 10px;">
                            お部屋番号: <strong style="color: var(--easy-navy);">{{ $checked->room ? $checked->room->room_number . '号室' : '—' }}</strong>
                        </span>
                        <span style="color: var(--easy-muted); font-size: 14px; margin-left: 10px;">
                            (予約番号: {{ $checked->confirmation_code }})
                        </span>
                    </div>
                    <div style="font-size: 14px; font-weight: 700; color: #059669;">
                        <i class="fa-solid fa-clock"></i> チェックイン済: {{ $checked->checked_in_at ? $checked->checked_in_at->format('H:i') : '本日' }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@endsection
