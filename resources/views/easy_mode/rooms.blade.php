@extends('layouts.easy')

@section('title', 'お部屋の清掃状態 - ClearStay Easy Mode')

@push('styles')
<style>
    .rooms-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 20px;
        margin-top: 24px;
    }

    .room-tile {
        background: #ffffff;
        border: 3px solid var(--easy-border);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 4px 10px rgba(0,0,0,0.04);
        min-height: 200px;
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
        display: inline-block;
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 800;
        text-transform: uppercase;
        margin-top: 6px;
    }
</style>
@endpush

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('easy_mode.index') }}" class="easy-btn easy-btn-secondary" style="margin-bottom: 16px;">
        <i class="fa-solid fa-arrow-left"></i> メインメニューに戻る
    </a>
    <h2 style="font-size: 28px; font-weight: 900; color: var(--easy-navy);">
        <i class="fa-solid fa-bed" style="color: #D97706;"></i> 客室の清掃・稼働状態一覧
    </h2>
    <p style="font-size: 17px; color: var(--easy-muted);">
        各部屋の現在の清掃状況です。ボタンを押すだけで「清掃済み (Clean)」または「清掃待ち (Dirty)」に切り替えられます。
    </p>
</div>

<div class="rooms-grid">
    @foreach($rooms as $room)
        <div class="room-tile status-{{ $room->status }}">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div class="room-num">{{ $room->room_number }} 号室</div>
                        <div style="font-size: 14px; color: var(--easy-muted); margin-top: 4px;">
                            {{ $room->floor }}階 · {{ $room->roomType ? $room->roomType->name : '客室' }}
                        </div>
                    </div>
                    @if($room->is_occupied)
                        <span style="background: #1E293B; color: #ffffff; font-size: 12px; font-weight: 700; padding: 4px 8px; border-radius: 6px;">
                            滞在中
                        </span>
                    @else
                        <span style="background: #E2E8F0; color: #475569; font-size: 12px; font-weight: 700; padding: 4px 8px; border-radius: 6px;">
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

            <!-- 1-Click Action Buttons -->
            <div style="margin-top: 16px; display: flex; gap: 8px;">
                @if($room->status !== 'clean')
                    <form action="{{ route('easy_mode.update_room_status', $room->id) }}" method="POST" style="flex: 1;">
                        @csrf
                        <input type="hidden" name="status" value="clean">
                        <button type="submit" class="easy-btn easy-btn-success" style="width: 100%; min-height: 42px; font-size: 15px; padding: 6px;">
                            <i class="fa-solid fa-check"></i> 清掃完了にする
                        </button>
                    </form>
                @else
                    <form action="{{ route('easy_mode.update_room_status', $room->id) }}" method="POST" style="flex: 1;">
                        @csrf
                        <input type="hidden" name="status" value="dirty">
                        <button type="submit" class="easy-btn" style="width: 100%; min-height: 42px; font-size: 15px; padding: 6px; background: #FEF3C7; color: #92400E; border-color: #F59E0B;">
                            <i class="fa-solid fa-broom"></i> 清掃待ちにする
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection
