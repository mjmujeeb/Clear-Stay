@extends('layouts.easy')

@section('title', 'かんたん操作ホーム - ClearStay 3.0')

@push('styles')
<style>
    .task-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 24px;
        margin-top: 24px;
    }

    .task-tile {
        background: #ffffff;
        border: 3px solid var(--easy-border);
        border-radius: 20px;
        padding: 32px 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        text-decoration: none;
        color: var(--easy-text);
        box-shadow: 0 4px 14px rgba(15, 29, 47, 0.06);
        transition: all 0.25s ease;
    }

    .task-tile:hover {
        transform: translateY(-4px);
        border-color: var(--easy-teal);
        box-shadow: 0 12px 28px rgba(31, 107, 102, 0.15);
    }

    .task-icon-circle {
        width: 84px;
        height: 84px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 38px;
        margin-bottom: 20px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .task-title {
        font-size: 24px;
        font-weight: 800;
        margin-bottom: 8px;
        color: var(--easy-navy);
    }

    .task-desc {
        font-size: 16px;
        color: var(--easy-muted);
        margin-bottom: 18px;
        line-height: 1.5;
    }

    .task-badge {
        font-size: 15px;
        font-weight: 800;
        padding: 6px 16px;
        border-radius: 20px;
    }

    /* Top Summary Counter Bar */
    .summary-strip {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }

    .summary-box {
        background: #ffffff;
        border: 2px solid var(--easy-border);
        border-radius: 14px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .summary-val {
        font-size: 32px;
        font-weight: 900;
        line-height: 1;
    }

    .summary-label {
        font-size: 14px;
        font-weight: 700;
        color: var(--easy-muted);
    }
</style>
@endpush

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 12px;">
    <div>
        <div style="font-size: 16px; font-weight: 700; color: var(--easy-teal); text-transform: uppercase;">
            <i class="fa-solid fa-calendar-day"></i> {{ date('Y年m月d日') }} (本日)
        </div>
        <h2 style="font-size: 30px; font-weight: 900; color: var(--easy-navy); margin-top: 4px;">
            本日のフロント業務メニュー
        </h2>
        <p style="font-size: 17px; color: var(--easy-muted);">
            ボタンを1回タッチするだけで、安全にチェックイン・清掃・予約確認が行えます。
        </p>
    </div>
</div>

<!-- Today's Status Strip -->
<div class="summary-strip">
    <div class="summary-box" style="border-left: 6px solid #059669;">
        <i class="fa-solid fa-plane-arrival fa-2x" style="color: #059669;"></i>
        <div>
            <div class="summary-val" style="color: #059669;">{{ $todayArrivalsCount }} <span style="font-size: 18px;">組</span></div>
            <div class="summary-label">本日の到着予定 (Arrivals)</div>
        </div>
    </div>
    <div class="summary-box" style="border-left: 6px solid #2563EB;">
        <i class="fa-solid fa-plane-departure fa-2x" style="color: #2563EB;"></i>
        <div>
            <div class="summary-val" style="color: #2563EB;">{{ $todayDeparturesCount }} <span style="font-size: 18px;">組</span></div>
            <div class="summary-label">本日の出発予定 (Departures)</div>
        </div>
    </div>
    <div class="summary-box" style="border-left: 6px solid #D97706;">
        <i class="fa-solid fa-broom fa-2x" style="color: #D97706;"></i>
        <div>
            <div class="summary-val" style="color: #D97706;">{{ $dirtyRoomsCount }} <span style="font-size: 18px;">室</span></div>
            <div class="summary-label">清掃待ちのお部屋 (Dirty)</div>
        </div>
    </div>
    <div class="summary-box" style="border-left: 6px solid #10B981;">
        <i class="fa-solid fa-door-open fa-2x" style="color: #10B981;"></i>
        <div>
            <div class="summary-val" style="color: #10B981;">{{ $cleanAvailableRoomsCount }} <span style="font-size: 18px;">室</span></div>
            <div class="summary-label">入室可能・清掃完了 (Clean)</div>
        </div>
    </div>
</div>

<!-- Receptionist 5-Step Guided Check-In Wizard Banner -->
<div style="background: linear-gradient(135deg, #0F1D2F 0%, #1F6B66 100%); border-radius: 20px; padding: 28px 32px; color: #ffffff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; margin-bottom: 28px; box-shadow: 0 10px 24px rgba(15, 29, 47, 0.15);">
    <div style="max-width: 650px;">
        <span style="background: rgba(255, 255, 255, 0.2); color: #A7F3D0; font-size: 13px; font-weight: 800; text-transform: uppercase; padding: 4px 12px; border-radius: 8px; letter-spacing: 0.05em;">
            <i class="fa-solid fa-sparkles"></i> 4.1 新機能 / Guided Receptionist Flow
        </span>
        <h3 style="font-size: 26px; font-weight: 900; margin-top: 10px; margin-bottom: 6px; color: #ffffff;">
            5ステップ・かんたんチェックイン案内 (Guided Check-In Wizard)
        </h3>
        <p style="font-size: 16px; color: #E2E8F0; line-height: 1.5; margin: 0;">
            シニアスタッフや外国人スタッフでも迷わない直感型ウィザード。検索・本人確認・部屋割当・鍵受渡確認まで1画面で完了。
        </p>
    </div>
    <a href="{{ route('easy_mode.checkin_wizard') }}" class="easy-btn" style="background: #10B981; color: #064E3B; font-weight: 900; font-size: 20px; padding: 16px 36px; border-radius: 14px; text-decoration: none; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);">
        <i class="fa-solid fa-route"></i> ウィザードを開始する &rarr;
    </a>
</div>

<!-- Main 5 Core Operations -->
<div class="task-grid">
    
    <!-- Task 1: Check In -->
    <a href="{{ route('easy_mode.checkin_wizard') }}" class="task-tile" style="border-color: #10B981;">
        <div class="task-icon-circle" style="background: #D1FAE5; color: #047857;">
            <i class="fa-solid fa-user-check"></i>
        </div>
        <div class="task-title">1. チェックイン受付 (5ステップ)</div>
        <div class="task-desc">本日ご到着のお客様の受付を行います。ガイドに従って本人確認と鍵の準備を確実に実行。</div>
        <div class="task-badge" style="background: #059669; color: #ffffff;">
            到着予定 {{ $todayArrivalsCount }} 件 (案内付き) &rarr;
        </div>
    </a>

    <!-- Task 2: Check Out -->
    <a href="{{ route('easy_mode.departures') }}" class="task-tile">
        <div class="task-icon-circle" style="background: #DBEAFE; color: #1D4ED8;">
            <i class="fa-solid fa-person-walking-luggage"></i>
        </div>
        <div class="task-title">2. チェックアウト受付</div>
        <div class="task-desc">ご出発のお客様の退出処理を行います。自動で清掃予定が作成され、お部屋が更新されます。</div>
        <div class="task-badge" style="background: #1D4ED8; color: #ffffff;">
            出発予定 {{ $todayDeparturesCount }} 件 &rarr;
        </div>
    </a>

    <!-- Task 3: Room Status & Cleaning -->
    <a href="{{ route('easy_mode.rooms') }}" class="task-tile">
        <div class="task-icon-circle" style="background: #FEF3C7; color: #B45309;">
            <i class="fa-solid fa-bed"></i>
        </div>
        <div class="task-title">3. お部屋の清掃確認</div>
        <div class="task-desc">客室の清掃状態を大きな色分けカードで確認・変更できます。「清掃済み」への切替もワンタッチ。</div>
        <div class="task-badge" style="background: #B45309; color: #ffffff;">
            部屋一覧を見る &rarr;
        </div>
    </a>

    <!-- Task 4: Reservation Search -->
    <a href="{{ route('easy_mode.reservations') }}" class="task-tile">
        <div class="task-icon-circle" style="background: #EDE9FE; color: #6D28D9;">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <div class="task-title">4. 予約の検索・確認</div>
        <div class="task-desc">お客様のお名前やお電話番号を入力するだけで、該当するご予約内容を大きな文字で表示します。</div>
        <div class="task-badge" style="background: #6D28D9; color: #ffffff;">
            検索画面を開く &rarr;
        </div>
    </a>

    <!-- Task 5: Easy New Reservation -->
    <a href="{{ route('easy_mode.create') }}" class="task-tile" style="border-color: #287F79;">
        <div class="task-icon-circle" style="background: #D1FAE5; color: #065F46;">
            <i class="fa-solid fa-calendar-plus"></i>
        </div>
        <div class="task-title">5. 新規予約の登録</div>
        <div class="task-desc">お名前、電話番号、お部屋を選んでワンタッチで新しい宿泊予約を作成できます。</div>
        <div class="task-badge" style="background: #287F79; color: #ffffff;">
            予約を作成する &rarr;
        </div>
    </a>

    <!-- Task 6: Easy Video & Guide -->
    <a href="{{ route('learning_centre.index') }}" class="task-tile">
        <div class="task-icon-circle" style="background: #E0F2FE; color: #0284C7;">
            <i class="fa-solid fa-circle-play"></i>
        </div>
        <div class="task-title">6. 動画でみる使い方</div>
        <div class="task-desc">フロント操作の手順を動画と大きな図で分かりやすく解説しています。いつでも確認可能。</div>
        <div class="task-badge" style="background: #0284C7; color: #ffffff;">
            操作ビデオを見る &rarr;
        </div>
    </a>

    <!-- Task 6: Return to Standard -->
    <div class="task-tile" style="border-style: dashed; background: #F8FAFC;">
        <div class="task-icon-circle" style="background: #E2E8F0; color: #475569;">
            <i class="fa-solid fa-sliders"></i>
        </div>
        <div class="task-title">標準モードへ切替</div>
        <div class="task-desc">詳細な売上分析、OTAチャネル連携、一括更新機能を使用する場合は標準モードに切り替えます。</div>
        <form action="{{ route('easy_mode.toggle') }}" method="POST" style="margin-top: auto; width: 100%;">
            @csrf
            <button type="submit" class="easy-btn easy-btn-secondary" style="width: 100%;">
                標準モードを開く
            </button>
        </form>
    </div>

</div>
@endsection
