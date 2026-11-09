@extends('layouts.easy')

@section('title', '本日のチェックイン受付 - ClearStay Easy Mode')

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('easy_mode.index') }}" class="easy-btn easy-btn-secondary" style="margin-bottom: 16px;">
        <i class="fa-solid fa-arrow-left"></i> メインメニューに戻る
    </a>
    <h2 style="font-size: 28px; font-weight: 900; color: var(--easy-navy);">
        <i class="fa-solid fa-user-check" style="color: #059669;"></i> 本日ご到着のお客様 (チェックイン受付)
    </h2>
    <p style="font-size: 17px; color: var(--easy-muted);">
        本日チェックイン予定のご予約一覧です。お客様のご到着時に緑色の「チェックイン完了」ボタンを押してください。
    </p>
</div>

@if($arrivals->isEmpty())
    <div class="easy-card" style="text-align: center; padding: 48px;">
        <i class="fa-solid fa-circle-check fa-3x" style="color: #059669; margin-bottom: 16px;"></i>
        <h3 style="font-size: 24px; font-weight: 800;">本日の到着分はすべて受付済みです</h3>
        <p style="color: var(--easy-muted); margin-top: 8px;">未完了のチェックイン予定はありません。</p>
        <a href="{{ route('easy_mode.index') }}" class="easy-btn easy-btn-primary" style="margin-top: 24px;">
            ホームに戻る
        </a>
    </div>
@else
    <div style="display: flex; flex-direction: column; gap: 20px;">
        @foreach($arrivals as $res)
            <div class="easy-card" style="border-left: 8px solid #059669;">
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
                            宿泊期間: {{ $res->check_in_date->format('Y/m/d') }} &rarr; {{ $res->check_out_date->format('Y/m/d') }} ({{ $res->nights }}泊 · 大人{{ $res->adults }}名)
                        </div>
                    </div>

                    <div style="text-align: right;">
                        <div style="font-size: 24px; font-weight: 900; color: var(--easy-navy); margin-bottom: 12px;">
                            ¥{{ number_format($res->total_price) }}
                        </div>
                        <form action="{{ route('easy_mode.check_in', $res->id) }}" method="POST" onsubmit="return confirm('{{ $res->guest ? $res->guest->last_name : '' }} 様のチェックインを確定しますか？\n部屋番号: {{ $res->room ? $res->room->room_number : '' }}');">
                            @csrf
                            <button type="submit" class="easy-btn easy-btn-success">
                                <i class="fa-solid fa-key"></i> チェックインを完了する
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
