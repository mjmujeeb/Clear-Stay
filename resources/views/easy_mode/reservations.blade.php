@extends('layouts.easy')

@section('title', '予約の検索・確認 (Lookup) — ClearStay かんたん操作')

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('easy_mode.index') }}" class="easy-btn easy-btn-secondary" style="margin-bottom: 16px;">
        <i class="fa-solid fa-arrow-left"></i> メインメニューに戻る (Home)
    </a>
    <h2 style="font-size: 28px; font-weight: 900; color: var(--easy-navy);">
        <i class="fa-solid fa-magnifying-glass" style="color: #6D28D9;"></i> ご予約の検索とお名前確認 (Lookup)
    </h2>
    <p style="font-size: 17px; color: var(--easy-muted); margin-top: 4px;">
        お客様のお名前、予約番号（CS-...）、お電話番号を入力して検索できます。
    </p>
</div>

<!-- Search Input Card -->
<div class="easy-card" style="margin-bottom: 24px;">
    <form action="{{ route('easy_mode.reservations') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 280px; position: relative;">
            <input type="text" name="q" value="{{ $search }}" placeholder="例: 田中、または予約番号 (CS-...)、電話番号" 
                   style="width: 100%; height: 56px; font-size: 20px; font-weight: 600; padding: 12px 20px; border: 3px solid var(--easy-teal); border-radius: 12px; outline: none; background: #FAFAFA;">
        </div>
        <button type="submit" class="easy-btn easy-btn-primary" style="min-width: 160px;">
            <i class="fa-solid fa-magnifying-glass"></i> 検索する (Search)
        </button>
        @if(!empty($search))
            <a href="{{ route('easy_mode.reservations') }}" class="easy-btn easy-btn-secondary">
                クリア
            </a>
        @endif
    </form>
</div>

<!-- Results List -->
@if($reservations->isEmpty())
    <div class="easy-card" style="text-align: center; padding: 48px;">
        <i class="fa-solid fa-folder-open fa-3x" style="color: var(--easy-muted); margin-bottom: 16px;"></i>
        <h3 style="font-size: 22px; font-weight: 800; color: var(--easy-navy);">該当するご予約が見つかりませんでした</h3>
        <p style="color: var(--easy-muted); font-size: 16px; margin-top: 8px;">お名前や電話番号、予約コードの入力内容をご確認ください。</p>
    </div>
@else
    <div style="display: flex; flex-direction: column; gap: 16px;">
        @foreach($reservations as $res)
            <div class="easy-card" style="border-left: 8px solid {{ $res->status === 'confirmed' ? '#059669' : ($res->status === 'checked_in' ? '#2563EB' : '#64748B') }}; margin-bottom: 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                            <span style="font-size: 14px; font-weight: 800; background: #f1f5f9; padding: 2px 10px; border-radius: 6px;">
                                {{ $res->confirmation_code }}
                            </span>
                            <span style="font-size: 14px; font-weight: 800; text-transform: uppercase; color: {{ $res->status === 'confirmed' ? '#059669' : ($res->status === 'checked_in' ? '#2563EB' : '#64748B') }};">
                                {{ $res->status === 'confirmed' ? '予約確定 (Confirmed)' : ($res->status === 'checked_in' ? '滞在中 (Checked-in)' : '退出済み (Checked-out)') }}
                            </span>
                            @if($res->channel)
                                <span style="background: #F1F5F9; color: var(--easy-navy); font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 4px;">
                                    {{ $res->channel->name }}
                                </span>
                            @endif
                        </div>

                        <h3 style="font-size: 26px; font-weight: 900; color: var(--easy-navy);">
                            {{ $res->guest ? ($res->guest->full_name ?? ($res->guest->first_name . ' ' . $res->guest->last_name)) : 'ゲスト様' }} 様
                            <span style="font-size: 16px; font-weight: 600; color: var(--easy-muted); margin-left: 8px;">
                                ({{ $res->guest && $res->guest->phone ? $res->guest->phone : '電話未登録' }})
                            </span>
                        </h3>

                        <div style="font-size: 17px; color: var(--easy-muted); margin-top: 4px;">
                            お部屋: <strong style="color: var(--easy-navy); font-size: 20px;">{{ $res->room ? $res->room->room_number . '号室' : '未割当' }}</strong>
                            &nbsp;|&nbsp; 宿泊期間: {{ $res->check_in_date ? $res->check_in_date->format('Y/m/d') : '' }} &rarr; {{ $res->check_out_date ? $res->check_out_date->format('Y/m/d') : '' }} ({{ $res->nights }}泊)
                        </div>
                    </div>

                    <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                        <div style="font-size: 24px; font-weight: 900; color: var(--easy-navy);">
                            ¥{{ number_format($res->total_price) }}
                        </div>

                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            @if($res->status === 'confirmed')
                                <a href="{{ route('easy_mode.checkin_wizard', ['reservation_id' => $res->id]) }}" class="easy-btn easy-btn-primary" style="font-size: 16px; min-height: 44px; padding: 8px 16px;">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i> 5ステップ案内
                                </a>
                                @if($res->room_id)
                                    <form action="{{ route('easy_mode.check_in', $res->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="easy-btn easy-btn-success" style="font-size: 16px; min-height: 44px; padding: 8px 16px;" onclick="this.disabled=true; this.form.submit();">
                                            <i class="fa-solid fa-key"></i> 即時チェックイン
                                        </button>
                                    </form>
                                @endif
                            @elseif($res->status === 'checked_in')
                                <form action="{{ route('easy_mode.check_out', $res->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="easy-btn" style="background: #2563EB; color: #ffffff; font-size: 16px; min-height: 44px; padding: 8px 16px; border-color: #1D4ED8;" onclick="this.disabled=true; this.form.submit();">
                                        <i class="fa-solid fa-arrow-right-from-bracket"></i> チェックアウト
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
@endsection
