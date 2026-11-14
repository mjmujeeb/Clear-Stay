@extends('layouts.easy')

@section('title', '客室の清掃・稼働状態一覧 (Housekeeping Grid) — ClearStay かんたん操作')

@push('styles')
<style>
    .filter-pills {
        display: flex;
        gap: 10px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .filter-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
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

    .filter-pill.active {
        background: var(--easy-navy);
        color: #ffffff;
        border-color: var(--easy-navy);
    }

    .rooms-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }

    .room-tile {
        background: #ffffff;
        border: 3px solid var(--easy-border);
        border-radius: 18px;
        padding: 22px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        min-height: 240px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .room-tile:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }

    .room-tile.status-clean {
        border-color: #059669;
        background: #F0FDF4;
    }

    .room-tile.status-dirty {
        border-color: #D97706;
        background: #FFFBEB;
    }

    .room-tile.status-inspecting {
        border-color: #2563EB;
        background: #EFF6FF;
    }

    .room-tile.status-maintenance {
        border-color: #DC2626;
        background: #FEF2F2;
    }

    .room-num {
        font-size: 32px;
        font-weight: 900;
        color: var(--easy-navy);
        line-height: 1;
    }

    .status-badge-lg {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 800;
        text-transform: uppercase;
        margin-top: 8px;
    }

    .quick-status-btn {
        flex: 1;
        min-height: 44px;
        font-size: 14px;
        font-weight: 700;
        border-radius: 8px;
        border: 1px solid transparent;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: opacity 0.2s;
    }

    .quick-status-btn:hover {
        opacity: 0.9;
    }
</style>
@endpush

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('easy_mode.index') }}" class="easy-btn easy-btn-secondary" style="margin-bottom: 16px;">
        <i class="fa-solid fa-arrow-left"></i> メインメニューに戻る (Home)
    </a>
    <h2 style="font-size: 28px; font-weight: 900; color: var(--easy-navy);">
        <i class="fa-solid fa-broom" style="color: #D97706;"></i> 客室の清掃・稼働状態一覧 (Housekeeping Grid)
    </h2>
    <p style="font-size: 17px; color: var(--easy-muted); margin-top: 4px;">
        全客室の清掃状態です。ボタンを1回タッチするだけで「清掃完了 (Clean)」「清掃待ち (Dirty)」「点検中 (Inspecting)」を即座に更新できます。
    </p>
</div>

<!-- Status Filter Pills -->
<div class="filter-pills">
    <a href="{{ route('easy_mode.rooms') }}" class="filter-pill {{ empty($statusFilter) ? 'active' : '' }}">
        <i class="fa-solid fa-border-all"></i> すべての部屋 ({{ $counts['all'] }}室)
    </a>
    <a href="{{ route('easy_mode.rooms', ['status' => 'dirty']) }}" class="filter-pill {{ $statusFilter === 'dirty' ? 'active' : '' }}" style="{{ $statusFilter === 'dirty' ? '' : 'color: #D97706;' }}">
        <i class="fa-solid fa-broom"></i> 清掃待ち ({{ $counts['dirty'] }}室)
    </a>
    <a href="{{ route('easy_mode.rooms', ['status' => 'inspecting']) }}" class="filter-pill {{ $statusFilter === 'inspecting' ? 'active' : '' }}" style="{{ $statusFilter === 'inspecting' ? '' : 'color: #2563EB;' }}">
        <i class="fa-solid fa-magnifying-glass"></i> 点検中 ({{ $counts['inspecting'] }}室)
    </a>
    <a href="{{ route('easy_mode.rooms', ['status' => 'clean']) }}" class="filter-pill {{ $statusFilter === 'clean' ? 'active' : '' }}" style="{{ $statusFilter === 'clean' ? '' : 'color: #059669;' }}">
        <i class="fa-solid fa-circle-check"></i> 清掃完了 ({{ $counts['clean'] }}室)
    </a>
    <a href="{{ route('easy_mode.rooms', ['status' => 'maintenance']) }}" class="filter-pill {{ $statusFilter === 'maintenance' ? 'active' : '' }}" style="{{ $statusFilter === 'maintenance' ? '' : 'color: #DC2626;' }}">
        <i class="fa-solid fa-triangle-exclamation"></i> 修繕中 ({{ $counts['maintenance'] }}室)
    </a>
</div>

<!-- Room Tiles Grid -->
<div class="rooms-grid">
    @forelse($rooms as $room)
        <div class="room-tile status-{{ $room->status }}">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div class="room-num">{{ $room->room_number }} 号室</div>
                        <div style="font-size: 14px; font-weight: 700; color: var(--easy-muted); margin-top: 4px;">
                            {{ $room->floor }}階 · {{ $room->roomType ? $room->roomType->name : '客室' }}
                        </div>
                    </div>
                    @if($room->is_occupied)
                        <span style="background: #1E293B; color: #ffffff; font-size: 13px; font-weight: 800; padding: 4px 10px; border-radius: 6px;">
                            <i class="fa-solid fa-user"></i> 滞在中
                        </span>
                    @else
                        <span style="background: #E2E8F0; color: #334155; font-size: 13px; font-weight: 800; padding: 4px 10px; border-radius: 6px;">
                            空室
                        </span>
                    @endif
                </div>

                <div style="margin-top: 14px;">
                    @if($room->status === 'clean')
                        <span class="status-badge-lg" style="background: #DCFCE7; color: #15803D;">
                            <i class="fa-solid fa-circle-check"></i> 清掃完了 (Clean)
                        </span>
                    @elseif($room->status === 'dirty')
                        <span class="status-badge-lg" style="background: #FEF3C7; color: #B45309;">
                            <i class="fa-solid fa-broom"></i> 清掃待ち (Dirty)
                        </span>
                    @elseif($room->status === 'inspecting')
                        <span class="status-badge-lg" style="background: #DBEAFE; color: #1D4ED8;">
                            <i class="fa-solid fa-magnifying-glass"></i> 点検中 (Inspecting)
                        </span>
                    @else
                        <span class="status-badge-lg" style="background: #FEE2E2; color: #B91C1C;">
                            <i class="fa-solid fa-triangle-exclamation"></i> 故障・修繕中
                        </span>
                    @endif
                </div>
            </div>

            <!-- 1-Click Status Action Buttons -->
            <div style="margin-top: 18px; display: flex; flex-direction: column; gap: 8px;">
                @if($room->status !== 'clean')
                    <form action="{{ route('easy_mode.update_room_status', $room->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="clean">
                        <button type="submit" class="quick-status-btn" style="background: #059669; color: #ffffff; width: 100%;">
                            <i class="fa-solid fa-check"></i> 清掃完了にする (Mark Clean)
                        </button>
                    </form>
                @endif

                <div style="display: flex; gap: 8px;">
                    @if($room->status !== 'dirty')
                        <form action="{{ route('easy_mode.update_room_status', $room->id) }}" method="POST" style="flex: 1;">
                            @csrf
                            <input type="hidden" name="status" value="dirty">
                            <button type="submit" class="quick-status-btn" style="background: #FEF3C7; color: #92400E; border-color: #F59E0B; width: 100%;">
                                <i class="fa-solid fa-broom"></i> 清掃待ち
                            </button>
                        </form>
                    @endif

                    @if($room->status !== 'inspecting')
                        <form action="{{ route('easy_mode.update_room_status', $room->id) }}" method="POST" style="flex: 1;">
                            @csrf
                            <input type="hidden" name="status" value="inspecting">
                            <button type="submit" class="quick-status-btn" style="background: #EFF6FF; color: #1E40AF; border-color: #93C5FD; width: 100%;">
                                <i class="fa-solid fa-magnifying-glass"></i> 点検中
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="easy-card" style="grid-column: 1 / -1; text-align: center; padding: 48px;">
            <i class="fa-solid fa-circle-info fa-3x" style="color: var(--easy-muted); margin-bottom: 16px;"></i>
            <h3 style="font-size: 22px; font-weight: 800; color: var(--easy-navy);">該当するお部屋はありません</h3>
            <p style="color: var(--easy-muted); font-size: 16px; margin-top: 8px;">指定されたステータスの客室はありません。</p>
        </div>
    @endforelse
</div>
@endsection
