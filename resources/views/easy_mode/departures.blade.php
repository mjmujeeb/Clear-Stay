@extends('layouts.easy')

@section('title', '本日のチェックアウト受付 - ClearStay Easy Mode')

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('easy_mode.index') }}" class="easy-btn easy-btn-secondary" style="margin-bottom: 16px;">
        <i class="fa-solid fa-arrow-left"></i> メインメニューに戻る
    </a>
    <h2 style="font-size: 28px; font-weight: 900; color: var(--easy-navy);">
        <i class="fa-solid fa-person-walking-luggage" style="color: #2563EB;"></i> 本日ご出発のお客様 (チェックアウト受付)
    </h2>
    <p style="font-size: 17px; color: var(--easy-muted);">
        現在ご滞在中で本日退出予定のお客様一覧です。青色の「チェックアウト完了」を押すと、お部屋が自動で清掃待ちになります。
    </p>
</div>

@if($departures->isEmpty())
    <div class="easy-card" style="text-align: center; padding: 48px;">
        <i class="fa-solid fa-circle-check fa-3x" style="color: #2563EB; margin-bottom: 16px;"></i>
        <h3 style="font-size: 24px; font-weight: 800;">現在ご出発予定のお客様はいません</h3>
        <p style="color: var(--easy-muted); margin-top: 8px;">本日滞在中の未退出客室はありません。</p>
        <a href="{{ route('easy_mode.index') }}" class="easy-btn easy-btn-primary" style="margin-top: 24px;">
            ホームに戻る
        </a>
    </div>
@else
    <div style="display: flex; flex-direction: column; gap: 20px;">
        @foreach($departures as $res)
            <div class="easy-card" style="border-left: 8px solid #2563EB;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <div style="font-size: 14px; font-weight: 700; color: var(--easy-muted);">
                            予約番号: <strong style="color: var(--easy-navy); font-size: 16px;">{{ $res->confirmation_code }}</strong>
                        </div>
                        <h3 style="font-size: 26px; font-weight: 900; color: var(--easy-navy); margin: 4px 0;">
                            {{ $res->guest ? ($res->guest->first_name . ' ' . $res->guest->last_name) : 'ゲスト様' }} 様
                        </h3>
                        <div style="font-size: 17px; color: var(--easy-muted);">
                            お部屋: <strong style="color: var(--easy-navy); font-size: 20px;">{{ $res->room ? $res->room->room_number . '号室' : '未割当' }}</strong> 
                            ({{ $res->room && $res->room->roomType ? $res->room->roomType->name : '標準' }})
                        </div>
                        <div style="font-size: 15px; color: var(--easy-muted); margin-top: 4px;">
                            ご滞在: {{ $res->check_in_date->format('Y/m/d') }} &rarr; {{ $res->check_out_date->format('Y/m/d') }} ({{ $res->nights }}泊)
                        </div>
                    </div>

                    <div style="text-align: right;">
                        <div style="font-size: 24px; font-weight: 900; color: var(--easy-navy); margin-bottom: 12px;">
                            合計: ¥{{ number_format($res->total_price) }}
                        </div>
                        <form action="{{ route('easy_mode.check_out', $res->id) }}" method="POST" onsubmit="return confirm('{{ $res->guest ? $res->guest->last_name : '' }} 様のチェックアウトを確定しますか？\n部屋 {{ $res->room ? $res->room->room_number : '' }} は清掃待ちに切り替わります。');">
                            @csrf
                            <button type="submit" class="easy-btn" style="background: #2563EB; color: #ffffff; border-color: #1D4ED8;">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i> チェックアウトを確定する
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
