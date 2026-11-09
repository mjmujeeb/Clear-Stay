@extends('layouts.app')

@section('title', 'Bulk Change Studio & 1-Click Rollback - ClearStay 2.0')
@section('header_title', 'Bulk Inventory & Price Studio')
@section('header_subtitle', 'Execute date-range adjustments with preview diffing, transactional safety and instant 1-click rollback.')

@section('content')
<div class="bulk-editor-container">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px;">
        <!-- Left Column: Bulk Change Form -->
        <div class="card">
            <div class="card-header" style="background: white;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                        <i class="fa-solid fa-layer-group text-primary"></i> Configure Date-Range Adjustment
                    </h3>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                        All modifications are atomic and broadcast automatically to connected distribution channels.
                    </p>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('bulk_editor.preview') }}" method="POST">
                    @csrf
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: var(--text-muted);">START DATE</label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date', now()->toDateString()) }}" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: var(--text-muted);">END DATE</label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date', now()->addDays(7)->toDateString()) }}" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: var(--text-muted);">ROOM TYPE</label>
                        <select name="room_type_id" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                            <option value="">All Room Types in Hotel</option>
                            @foreach($roomTypes as $rt)
                                <option value="{{ $rt->id }}">{{ $rt->name }} (Current: ¥{{ number_format($rt->base_price) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px; padding: 14px; margin-bottom: 18px;">
                        <div style="font-size: 13px; font-weight: 700; color: var(--text-main); margin-bottom: 10px;">Operation Parameters</div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: var(--text-muted);">PRICE ADJUSTMENT (¥ or %)</label>
                                <input type="number" name="price_delta" placeholder="e.g. +2000 or -1500" class="form-control" style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border);">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: var(--text-muted);">MIN STAY NIGHTS</label>
                                <input type="number" name="min_stay" placeholder="e.g. 2" min="1" max="30" class="form-control" style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border);">
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" name="stop_sell" value="1" id="stopSellToggle" style="width: 18px; height: 18px;">
                            <label for="stopSellToggle" style="font-size: 13px; font-weight: 600; color: #991B1B; cursor: pointer;">
                                Apply Emergency Stop-Sell (Close Room Types to Inbound Bookings)
                            </label>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: var(--text-muted);">AUDIT REASON / CHANGE RATIONALE *</label>
                        <input type="text" name="reason" placeholder="e.g. Surge demand adjustment for Tokyo marathon weekend." required minlength="5" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-secondary" style="flex: 1; justify-content: center; font-weight: 700;">
                            <i class="fa-solid fa-magnifying-glass"></i> Preview Diff Before Applying
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Interactive Universal Inventory Matrix Sample -->
        <div class="card">
            <div class="card-header" style="background: white;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                        <i class="fa-solid fa-calendar-days text-primary"></i> Live Inventory Matrix (Next 7 Days)
                    </h3>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                        Single source of truth feeding all external booking engines
                    </p>
                </div>
                <a href="{{ route('reports.export_inventory') }}" class="btn btn-sm btn-secondary">
                    <i class="fa-solid fa-file-csv"></i> Export Matrix
                </a>
            </div>
            <div class="card-body" style="padding: 0; overflow-x: auto;">
                <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                            <th style="padding: 12px 16px;">Date</th>
                            <th style="padding: 12px 10px;">Available</th>
                            <th style="padding: 12px 10px;">Booked</th>
                            <th style="padding: 12px 10px;">Rate</th>
                            <th style="padding: 12px 14px; text-align: right;">Stop Sell</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentInventories as $inv)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 12px 16px; font-weight: 600;">
                                {{ $inv->date->format('M d (D)') }}
                            </td>
                            <td style="padding: 12px 10px;">
                                <span class="badge" style="background: #D1FAE5; color: #065F46;">
                                    {{ $inv->available_units }} rooms
                                </span>
                            </td>
                            <td style="padding: 12px 10px; color: var(--text-muted);">
                                {{ $inv->reserved_units }}
                            </td>
                            <td style="padding: 12px 10px; font-weight: 700; color: #183B50;">
                                ¥{{ number_format($inv->base_rate) }}
                            </td>
                            <td style="padding: 12px 14px; text-align: right;">
                                @if($inv->stop_sell)
                                    <span class="badge" style="background: #FEE2E2; color: #991B1B;">STOP</span>
                                @else
                                    <span class="badge" style="background: #F1F5F9; color: #475569;">OPEN</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);">
                                No inventory records loaded. Run database seeder.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bottom Full-Width Card: Rollback Audit History -->
    <div class="card">
        <div class="card-header" style="background: white;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                    <i class="fa-solid fa-clock-rotate-left" style="color: #287F79;"></i> Safe Rollback Audit Trail
                </h3>
                <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                    Any bulk adjustment can be cleanly rolled back with 1-click, restoring historical rates and pushing corrections to OTAs.
                </p>
            </div>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                        <th style="padding: 12px 20px;">Batch ID</th>
                        <th style="padding: 12px 14px;">Summary</th>
                        <th style="padding: 12px 14px;">Executed By</th>
                        <th style="padding: 12px 14px;">Status</th>
                        <th style="padding: 12px 14px;">Timestamp</th>
                        <th style="padding: 12px 20px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 14px 20px; font-family: monospace; font-size: 12px;">
                            #{{ $batch->id }}
                        </td>
                        <td style="padding: 14px 14px; font-weight: 600;">
                            {{ $batch->summary }}
                        </td>
                        <td style="padding: 14px 14px; color: var(--text-muted);">
                            {{ $batch->user?->name ?? 'System' }}
                        </td>
                        <td style="padding: 14px 14px;">
                            @if($batch->status === 'applied')
                                <span class="badge" style="background: #D1FAE5; color: #065F46;">Applied</span>
                            @elseif($batch->status === 'rolled_back')
                                <span class="badge" style="background: #FEE2E2; color: #991B1B;">Rolled Back</span>
                            @else
                                <span class="badge" style="background: #FEF3C7; color: #92400E;">{{ $batch->status }}</span>
                            @endif
                        </td>
                        <td style="padding: 14px 14px; color: var(--text-muted); font-size: 12px;">
                            {{ $batch->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td style="padding: 14px 20px; text-align: right;">
                            @if($batch->status === 'applied')
                            <form action="{{ route('bulk_editor.rollback', $batch) }}" method="POST" onsubmit="return confirm('Are you sure you want to 1-click rollback batch #{{ $batch->id }}? All original values will be restored and broadcasted.');">
                                @csrf
                                <button type="submit" class="btn btn-sm" style="background: #FFFBEB; color: #B45309; border: 1px solid #FCD34D; font-weight: 700;">
                                    <i class="fa-solid fa-undo"></i> 1-Click Rollback
                                </button>
                            </form>
                            @else
                                <span style="font-size: 12px; color: var(--text-muted);">Restored</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding: 24px; text-align: center; color: var(--text-muted);">
                            No bulk change batches executed yet. Adjustments will appear here with instant rollback capability.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
