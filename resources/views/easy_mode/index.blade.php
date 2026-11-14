@extends('layouts.easy')

@section('title', 'ClearStay かんたん操作 — Simple Hotel Operations')

@push('styles')
<style>
    /* Time-aware Greeting Header */
    .greeting-header {
        background: #ffffff;
        border: 2px solid var(--easy-border);
        border-radius: 20px;
        padding: 24px 30px;
        margin-bottom: 24px;
        box-shadow: 0 4px 14px rgba(15, 29, 47, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .greeting-title {
        font-size: 28px;
        font-weight: 900;
        color: var(--easy-navy);
        line-height: 1.2;
    }

    .greeting-subtitle {
        font-size: 16px;
        color: var(--easy-muted);
        margin-top: 4px;
    }

    /* Operational Summary Strip */
    .summary-strip {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .summary-box {
        background: #ffffff;
        border: 2px solid var(--easy-border);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 4px 12px rgba(15, 29, 47, 0.04);
        transition: transform 0.2s ease;
    }

    .summary-box:hover {
        transform: translateY(-2px);
    }

    .summary-val {
        font-size: 32px;
        font-weight: 900;
        line-height: 1.1;
    }

    .summary-label {
        font-size: 14px;
        font-weight: 700;
        color: var(--easy-muted);
        margin-top: 2px;
    }

    .summary-subtext {
        font-size: 12px;
        color: var(--easy-muted);
    }

    /* Next Recommended Task Card */
    .next-task-card {
        background: linear-gradient(135deg, #0F1D2F 0%, #1F6B66 100%);
        border-radius: 22px;
        padding: 28px 32px;
        color: #ffffff;
        margin-bottom: 28px;
        box-shadow: 0 10px 28px rgba(15, 29, 47, 0.16);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .next-task-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.2);
        color: #A7F3D0;
        font-size: 13px;
        font-weight: 800;
        padding: 4px 12px;
        border-radius: 8px;
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    .next-task-title {
        font-size: 24px;
        font-weight: 900;
        line-height: 1.3;
        margin-bottom: 8px;
    }

    .next-task-desc {
        font-size: 16px;
        color: #E2E8F0;
        line-height: 1.5;
        max-width: 680px;
    }

    /* Role Workspace Filter Pills */
    .role-pills {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .role-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 30px;
        font-size: 16px;
        font-weight: 800;
        border: 2px solid var(--easy-border);
        background: #ffffff;
        color: var(--easy-navy);
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .role-pill.active {
        background: var(--easy-teal);
        color: #ffffff;
        border-color: var(--easy-teal);
        box-shadow: 0 4px 12px rgba(31, 107, 102, 0.25);
    }

    /* Task Tile Grid */
    .task-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 24px;
    }

    .task-tile {
        background: #ffffff;
        border: 3px solid var(--easy-border);
        border-radius: 20px;
        padding: 30px 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        text-decoration: none;
        color: var(--easy-text);
        box-shadow: 0 4px 14px rgba(15, 29, 47, 0.05);
        transition: all 0.25s ease;
        position: relative;
    }

    .task-tile:hover {
        transform: translateY(-4px);
        border-color: var(--easy-teal);
        box-shadow: 0 12px 28px rgba(31, 107, 102, 0.15);
    }

    .task-icon-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 34px;
        margin-bottom: 18px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .task-title {
        font-size: 22px;
        font-weight: 800;
        margin-bottom: 6px;
        color: var(--easy-navy);
    }

    .task-desc {
        font-size: 15px;
        color: var(--easy-muted);
        margin-bottom: 18px;
        line-height: 1.5;
        flex: 1;
    }

    .task-badge {
        font-size: 14px;
        font-weight: 800;
        padding: 6px 16px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
</style>
@endpush

@section('content')

<!-- 1. Time-aware Greeting Header -->
<div class="greeting-header">
    <div>
        <div style="font-size: 15px; font-weight: 700; color: var(--easy-teal); text-transform: uppercase; margin-bottom: 4px;">
            <i class="fa-solid fa-calendar-day"></i> {{ date('Y年m月d日') }} ({{ date('l') }})
        </div>
        <h2 class="greeting-title">
            {{ $greetingJa }}、{{ auth()->user()->name ?? 'スタッフ' }} さん
        </h2>
        <div class="greeting-subtitle">
            {{ $greetingEn }} — 本日のホテルフロント・客室オペレーション状況
        </div>
    </div>
    <div>
        <a href="{{ route('easy_mode.checkin_wizard') }}" class="easy-btn easy-btn-primary">
            <i class="fa-solid fa-wand-magic-sparkles"></i> 5ステップ・チェックイン案内
        </a>
    </div>
</div>

<!-- 2. Today's Operational Status Strip -->
<div class="summary-strip">
    <!-- Arrivals -->
    <a href="{{ route('easy_mode.arrivals') }}" class="summary-box" style="border-left: 6px solid #059669; text-decoration: none; color: inherit;">
        <i class="fa-solid fa-plane-arrival fa-2x" style="color: #059669;"></i>
        <div>
            <div class="summary-val" style="color: #059669;">
                {{ $todayArrivalsCount }} <span style="font-size: 16px;">組</span>
            </div>
            <div class="summary-label">本日の到着予定 (Arrivals)</div>
            <div class="summary-subtext">完了: {{ $todayArrivalsCheckedInCount }} 組済</div>
        </div>
    </a>

    <!-- Departures -->
    <a href="{{ route('easy_mode.departures') }}" class="summary-box" style="border-left: 6px solid #2563EB; text-decoration: none; color: inherit;">
        <i class="fa-solid fa-plane-departure fa-2x" style="color: #2563EB;"></i>
        <div>
            <div class="summary-val" style="color: #2563EB;">
                {{ $todayDeparturesCount }} <span style="font-size: 16px;">組</span>
            </div>
            <div class="summary-label">本日の出発予定 (Departures)</div>
            <div class="summary-subtext">退室済: {{ $todayDeparturesCheckedOutCount }} 組</div>
        </div>
    </a>

    <!-- Dirty Rooms -->
    <a href="{{ route('easy_mode.rooms', ['status' => 'dirty']) }}" class="summary-box" style="border-left: 6px solid #D97706; text-decoration: none; color: inherit;">
        <i class="fa-solid fa-broom fa-2x" style="color: #D97706;"></i>
        <div>
            <div class="summary-val" style="color: #D97706;">
                {{ $dirtyRoomsCount }} <span style="font-size: 16px;">室</span>
            </div>
            <div class="summary-label">清掃待ち (Dirty)</div>
            <div class="summary-subtext">点検中: {{ $inspectingRoomsCount }} 室</div>
        </div>
    </a>

    <!-- Ready Rooms -->
    <a href="{{ route('easy_mode.rooms', ['status' => 'clean']) }}" class="summary-box" style="border-left: 6px solid #10B981; text-decoration: none; color: inherit;">
        <i class="fa-solid fa-door-open fa-2x" style="color: #10B981;"></i>
        <div>
            <div class="summary-val" style="color: #10B981;">
                {{ $cleanAvailableRoomsCount }} <span style="font-size: 16px;">室</span>
            </div>
            <div class="summary-label">入室可能・清掃済 (Ready)</div>
            <div class="summary-subtext">稼働中: {{ $occupiedRoomsCount }} 室 / 全{{ $totalRoomsCount }}室</div>
        </div>
    </a>
</div>

<!-- 3. Deterministic "Next Recommended Task" Banner -->
<div class="next-task-card">
    <div>
        <div class="next-task-badge">
            <i class="{{ $nextTask['icon'] }}"></i> {{ $nextTask['badge_ja'] }} ({{ $nextTask['badge_en'] }})
        </div>
        <div class="next-task-title">
            {{ $nextTask['title_ja'] }}
        </div>
        <div class="next-task-desc">
            {{ $nextTask['desc_ja'] }}
        </div>
    </div>
    <div>
        <a href="{{ $nextTask['action_url'] }}" class="easy-btn" style="background: #ffffff; color: var(--easy-navy); min-width: 220px; font-weight: 800; border: none;">
            <i class="fa-solid fa-arrow-right"></i> {{ $nextTask['action_label_ja'] }}
        </a>
    </div>
</div>

<!-- 4. Role Workspace Filter Pills -->
<div class="role-pills">
    <a href="{{ route('easy_mode.index', ['role' => 'all']) }}" class="role-pill {{ $activeRole === 'all' ? 'active' : '' }}">
        <i class="fa-solid fa-layer-group"></i> すべての業務 (All Workspaces)
    </a>
    <a href="{{ route('easy_mode.index', ['role' => 'receptionist']) }}" class="role-pill {{ $activeRole === 'receptionist' ? 'active' : '' }}">
        <i class="fa-solid fa-bell-concierge"></i> フロント受付 (Receptionist)
    </a>
    <a href="{{ route('easy_mode.index', ['role' => 'housekeeping']) }}" class="role-pill {{ $activeRole === 'housekeeping' ? 'active' : '' }}">
        <i class="fa-solid fa-broom"></i> 客室清掃 (Housekeeping)
    </a>
    <a href="{{ route('easy_mode.index', ['role' => 'manager']) }}" class="role-pill {{ $activeRole === 'manager' ? 'active' : '' }}">
        <i class="fa-solid fa-clipboard-check"></i> 管理・巡回 (Supervisor)
    </a>
</div>

<!-- 5. Interactive Operation Tiles Grid -->
<div class="task-grid">
    <!-- Tile 1: 5-Step Guided Check-In Wizard (Receptionist) -->
    @if(in_array($activeRole, ['all', 'receptionist']))
    <a href="{{ route('easy_mode.checkin_wizard') }}" class="task-tile">
        <div class="task-icon-circle" style="background: #E6F4F1; color: var(--easy-teal);">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
        </div>
        <div class="task-title">5ステップ・チェックイン案内</div>
        <div class="task-desc">
            お名前の確認から鍵のお渡しまで、迷わず安全にチェックインを完了できるステップ式案内です。
        </div>
        <div class="task-badge" style="background: #DCFCE7; color: #14532D;">
            <i class="fa-solid fa-circle-check"></i> 本日到着: {{ $todayArrivalsCount }} 組
        </div>
    </a>
    @endif

    <!-- Tile 2: Arrivals List & Quick Check-In (Receptionist) -->
    @if(in_array($activeRole, ['all', 'receptionist']))
    <a href="{{ route('easy_mode.arrivals') }}" class="task-tile">
        <div class="task-icon-circle" style="background: #DCFCE7; color: #059669;">
            <i class="fa-solid fa-plane-arrival"></i>
        </div>
        <div class="task-title">本日の到着一覧・受付</div>
        <div class="task-desc">
            本日到着するすべてのお客様一覧を確認し、ワンタッチでチェックインを確定できます。
        </div>
        <div class="task-badge" style="background: #E6F4F1; color: var(--easy-teal);">
            <i class="fa-solid fa-user-clock"></i> 残り {{ $todayArrivalsCount }} 組の到着待ち
        </div>
    </a>
    @endif

    <!-- Tile 3: Departures & 1-Click Checkout (Receptionist / Housekeeping) -->
    @if(in_array($activeRole, ['all', 'receptionist', 'housekeeping']))
    <a href="{{ route('easy_mode.departures') }}" class="task-tile">
        <div class="task-icon-circle" style="background: #EFF6FF; color: #2563EB;">
            <i class="fa-solid fa-person-walking-dashed-line-arrow-right"></i>
        </div>
        <div class="task-title">出発・チェックアウト処理</div>
        <div class="task-desc">
            鍵を回収し、ボタンを1回タッチするだけでチェックアウトと清掃依頼を同時に完了します。
        </div>
        <div class="task-badge" style="background: #DBEAFE; color: #1E40AF;">
            <i class="fa-solid fa-key"></i> 出発予定: {{ $todayDeparturesCount }} 組
        </div>
    </a>
    @endif

    <!-- Tile 4: Room Status & Cleaning Grid (Housekeeping / Manager) -->
    @if(in_array($activeRole, ['all', 'housekeeping', 'manager']))
    <a href="{{ route('easy_mode.rooms') }}" class="task-tile">
        <div class="task-icon-circle" style="background: #FEF3C7; color: #D97706;">
            <i class="fa-solid fa-broom"></i>
        </div>
        <div class="task-title">客室の清掃状態・清掃管理</div>
        <div class="task-desc">
            全客室の清掃状態を大きなカードで確認し、「清掃完了」「点検中」をワンタッチで切り替えます。
        </div>
        <div class="task-badge" style="background: #FEF3C7; color: #92400E;">
            <i class="fa-solid fa-triangle-exclamation"></i> 清掃待ち: {{ $dirtyRoomsCount }} 室
        </div>
    </a>
    @endif

    <!-- Tile 5: Create New Reservation (Receptionist / Manager) -->
    @if(in_array($activeRole, ['all', 'receptionist', 'manager']))
    <a href="{{ route('easy_mode.create') }}" class="task-tile">
        <div class="task-icon-circle" style="background: #F3E8FF; color: #7E22CE;">
            <i class="fa-solid fa-calendar-plus"></i>
        </div>
        <div class="task-title">新しい予約の登録 (Walk-in)</div>
        <div class="task-desc">
            電話やお立ち寄りのお客様の予約を、日付とお名前を選ぶだけで簡単に追加登録できます。
        </div>
        <div class="task-badge" style="background: #F3E8FF; color: #6B21A8;">
            <i class="fa-solid fa-bed"></i> 空室: {{ $cleanAvailableRoomsCount }} 室即時割当可
        </div>
    </a>
    @endif

    <!-- Tile 6: Quick Reservation Search (Receptionist / Manager) -->
    @if(in_array($activeRole, ['all', 'receptionist', 'manager']))
    <a href="{{ route('easy_mode.reservations') }}" class="task-tile">
        <div class="task-icon-circle" style="background: #F1F5F9; color: var(--easy-navy);">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <div class="task-title">予約のかんたん検索</div>
        <div class="task-desc">
            お名前、電話番号、予約番号のいずれかを入力するだけで、瞬時に予約カードを見つけられます。
        </div>
        <div class="task-badge" style="background: #E2E8F0; color: #334155;">
            <i class="fa-solid fa-bolt"></i> 即時照会
        </div>
    </a>
    @endif
</div>

@endsection
