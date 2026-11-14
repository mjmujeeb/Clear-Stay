@extends('layouts.easy')

@section('title', '本日のチェックアウト受付 (Departures) — ClearStay かんたん操作')

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('easy_mode.index') }}" class="easy-btn easy-btn-secondary" style="margin-bottom: 16px;">
        <i class="fa-solid fa-arrow-left"></i> メインメニューに戻る (Home)
    </a>
    <h2 style="font-size: 28px; font-weight: 900; color: var(--easy-navy);">
        <i class="fa-solid fa-person-walking-dashed-line-arrow-right" style="color: #2563EB;"></i> 本日ご出発のお客様 (チェックアウト受付)
    </h2>
    <p style="font-size: 17px; color: var(--easy-muted); margin-top: 4px;">
        現在ご滞在中で本日退出予定のお客様一覧です。「チェックアウト完了」を1回タッチすると、客室が自動で清掃待ち（Dirty）に切り替わり、清掃タスクが作成されます。
    </p>
</div>

@if($departures->isEmpty())
    <div class="easy-card" style="text-align: center; padding: 48px;">
        <i class="fa-solid fa-circle-check fa-3x" style="color: #2563EB; margin-bottom: 16px;"></i>
        <h3 style="font-size: 24px; font-weight: 800; color: var(--easy-navy);">現在ご出発予定のお客様はいません</h3>
        <p style="color: var(--easy-muted); font-size: 17px; margin-top: 8px;">本日滞在中の未退出客室はありません。</p>
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
        @foreach($departures as $res)
            <div class="easy-card" style="border-left: 8px solid #2563EB; margin-bottom: 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                    <div style="flex: 1; min-width: 280px;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                            <span style="background: #EFF6FF; color: #1E40AF; font-size: 13px; font-weight: 800; padding: 3px 10px; border-radius: 6px;">
                                本日出発予定 (Departure Today)
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
                        </h3>

                        <div style="font-size: 18px; color: var(--easy-muted); margin-top: 6px;">
                            現在のお部屋: <strong style="color: #2563EB; font-size: 22px;">{{ $res->room ? $res->room->room_number . '号室' : '未割当' }}</strong> 
                            ({{ $res->room && $res->room->roomType ? $res->room->roomType->name : ($res->roomType ? $res->roomType->name : '標準ルーム') }})
                        </div>

                        <div style="font-size: 15px; color: var(--easy-muted); margin-top: 6px;">
                            ご滞在期間: {{ $res->check_in_date ? $res->check_in_date->format('Y/m/d') : '' }} &rarr; <strong>{{ $res->check_out_date ? $res->check_out_date->format('Y/m/d') : '' }}</strong> ({{ $res->nights }}泊)
                        </div>
                    </div>

                    <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
                        <div style="font-size: 24px; font-weight: 900; color: var(--easy-navy);">
                            請求合計: ¥{{ number_format($res->total_price) }}
                            <span style="font-size: 13px; font-weight: 700; color: #059669; display: block;">精算完了済 / Fully Paid</span>
                        </div>

                        <form action="{{ route('easy_mode.check_out', $res->id) }}" method="POST" onsubmit="return confirm('{{ $res->guest ? $res->guest->last_name : '' }} 様のチェックアウトを確定しますか？\n部屋番号 {{ $res->room ? $res->room->room_number : '' }}号室 は自動で「清掃待ち (Dirty)」に切り替わります。');">
                            @csrf
                            <button type="submit" class="easy-btn" style="background: #2563EB; color: #ffffff; border-color: #1D4ED8; min-width: 220px;" onclick="this.disabled=true; this.form.submit();">
                                <i class="fa-solid fa-key"></i> 鍵回収 & チェックアウト完了
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

<!-- Today's Checked-Out Guests Record -->
@if(isset($checkedOutToday) && $checkedOutToday->isNotEmpty())
    <div style="margin-top: 36px;">
        <h3 style="font-size: 22px; font-weight: 800; color: var(--easy-navy); margin-bottom: 16px;">
            <i class="fa-solid fa-circle-check" style="color: #2563EB;"></i> 本日チェックアウト完了済みのお部屋 ({{ $checkedOutToday->count() }}室)
        </h3>

        <div style="display: flex; flex-direction: column; gap: 12px;">
            @foreach($checkedOutToday as $out)
                <div class="easy-card" style="padding: 18px 24px; border: 1px solid var(--easy-border); background: #F8FAFC; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0;">
                    <div>
                        <strong style="font-size: 18px; color: var(--easy-navy);">{{ $out->guest ? ($out->guest->last_name . ' ' . $out->guest->first_name) : 'ゲスト様' }} 様</strong>
                        <span style="color: var(--easy-muted); font-size: 15px; margin-left: 10px;">
                            退出お部屋: <strong style="color: #D97706;">{{ $out->room ? $out->room->room_number . '号室 (清掃待ち)' : '—' }}</strong>
                        </span>
                        <span style="color: var(--easy-muted); font-size: 14px; margin-left: 10px;">
                            (予約番号: {{ $out->confirmation_code }})
                        </span>
                    </div>
                    <div style="font-size: 14px; font-weight: 700; color: #2563EB;">
                        <i class="fa-solid fa-clock"></i> 退出時刻: {{ $out->checked_out_at ? $out->checked_out_at->format('H:i') : '本日' }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@endsection
