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
                        All modifications are atomic, previewed before commit, and broadcasted to connected channels.
                    </p>
                </div>
            </div>
            <div class="card-body">
                <!-- Validation & Alert Container -->
                <div id="formAlertBox" style="display: none; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px;"></div>

                <form id="bulkEditorForm" action="{{ route('bulk_editor.commit') }}" method="POST">
                    @csrf
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: var(--text-muted);">START DATE *</label>
                            <input type="date" id="inputStartDate" name="start_date" class="form-control" value="{{ old('start_date', now()->toDateString()) }}" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: var(--text-muted);">END DATE *</label>
                            <input type="date" id="inputEndDate" name="end_date" class="form-control" value="{{ old('end_date', now()->addDays(7)->toDateString()) }}" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: var(--text-muted);">ROOM TYPE</label>
                        <select id="selectRoomType" name="room_type_id" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                            <option value="">All Room Types in Hotel (Multi-Category)</option>
                            @foreach($roomTypes as $rt)
                                <option value="{{ $rt->id }}">{{ $rt->name }} (Current: ¥{{ number_format($rt->base_price) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px; padding: 14px; margin-bottom: 18px;">
                        <div style="font-size: 13px; font-weight: 700; color: var(--text-main); margin-bottom: 10px;">Operation Parameters (Fill at least one)</div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: var(--text-muted);">PRICE ADJUSTMENT DELTA (¥)</label>
                                <input type="number" id="inputPriceDelta" name="price_delta" placeholder="e.g. +2000 or -1500" class="form-control" style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border);">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: var(--text-muted);">MIN STAY NIGHTS</label>
                                <input type="number" id="inputMinStay" name="min_stay" placeholder="e.g. 2" min="1" max="30" class="form-control" style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border);">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: var(--text-muted);">SET BASE RATE (EXACT ¥)</label>
                                <input type="number" id="inputBaseRate" name="base_rate" placeholder="e.g. 18500" min="1000" class="form-control" style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border);">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: var(--text-muted);">AVAILABLE UNITS OVERRIDE</label>
                                <input type="number" id="inputAvailableUnits" name="available_units" placeholder="e.g. 8" min="0" max="100" class="form-control" style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border);">
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 10px; margin-top: 6px;">
                            <input type="checkbox" name="stop_sell" value="1" id="stopSellToggle" style="width: 18px; height: 18px; cursor: pointer;">
                            <label for="stopSellToggle" style="font-size: 13px; font-weight: 600; color: #991B1B; cursor: pointer;">
                                Apply Emergency Stop-Sell (Close Room Types to Inbound Bookings)
                            </label>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: var(--text-muted);">AUDIT REASON / CHANGE RATIONALE *</label>
                        <input type="text" id="inputReason" name="reason" placeholder="e.g. High season adjustment for upcoming city festival." required minlength="5" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="button" id="btnPreviewDiff" onclick="generateDiffPreview()" class="btn btn-secondary" style="flex: 1; justify-content: center; font-weight: 700; padding: 12px;">
                            <i class="fa-solid fa-magnifying-glass"></i> Preview Diff Before Applying
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Interactive Universal Inventory Matrix Sample -->
        <div class="card">
            <div class="card-header" style="background: white; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700;">
                        <i class="fa-solid fa-calendar-days text-primary"></i> Live Inventory Matrix (Next 14 Days)
                    </h3>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                        Active availability and rate baseline synced with OTAs
                    </p>
                </div>
                <div style="display: flex; gap: 8px;">
                    <form action="{{ route('bulk_editor.seed_demo') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-secondary" title="Re-initialize 90-day demo inventory">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Refresh Demo Data
                        </button>
                    </form>
                    <a href="{{ route('reports.export_inventory') }}" class="btn btn-sm btn-secondary">
                        <i class="fa-solid fa-file-csv"></i> CSV
                    </a>
                </div>
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
                                <span class="badge" style="background: #D1FAE5; color: #065F46; font-weight: 700;">
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
                                    <span class="badge" style="background: #FEE2E2; color: #991B1B; font-weight: 700;">STOP</span>
                                @else
                                    <span class="badge" style="background: #F1F5F9; color: #475569;">OPEN</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="padding: 30px; text-align: center;">
                                <div style="color: var(--text-main); font-weight: 700; font-size: 15px; margin-bottom: 6px;">
                                    Your inventory workspace is ready to configure.
                                </div>
                                <p style="font-size: 13px; color: var(--text-muted); margin: 0 0 16px;">
                                    Initialize realistic multi-channel demonstration data with one click to explore ClearStay.
                                </p>
                                <form action="{{ route('bulk_editor.seed_demo') }}" method="POST" style="display: inline-block;">
                                    @csrf
                                    <button type="submit" class="btn" style="background: #287F79; color: white; border: none; font-weight: 700; padding: 8px 16px;">
                                        <i class="fa-solid fa-wand-magic-sparkles"></i> Initialize 90-Day Demonstration Data
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- INTERACTIVE DIFF PREVIEW MODAL / PANEL -->
    <div id="diffPreviewModal" style="display: none; margin-bottom: 30px;">
        <div class="card" style="border: 2px solid #287F79; box-shadow: 0 10px 25px -5px rgba(40, 127, 121, 0.15);">
            <div class="card-header" style="background: linear-gradient(135deg, #183B50, #0F1D2F); color: white; display: flex; justify-content: space-between; align-items: center; padding: 18px 24px;">
                <div>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #FFFFFF;">
                        <i class="fa-solid fa-code-compare" style="color: #287F79;"></i> Universal Update Preview &amp; Impact Analysis
                    </h3>
                    <p style="font-size: 12px; color: #94A3B8; margin: 4px 0 0;">
                        Review affected records and channel broadcast scope before committing.
                    </p>
                </div>
                <button type="button" onclick="closeDiffPreview()" style="background: rgba(255,255,255,0.1); border: none; color: white; font-size: 16px; border-radius: 6px; padding: 6px 12px; cursor: pointer;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="card-body" style="padding: 24px;">
                <!-- Loading State -->
                <div id="previewLoadingState" style="display: none; text-align: center; padding: 40px 20px;">
                    <i class="fa-solid fa-spinner fa-spin" style="font-size: 32px; color: #287F79; margin-bottom: 12px;"></i>
                    <div style="font-weight: 700; color: var(--text-main); font-size: 15px;">Calculating Multi-Channel Impact...</div>
                    <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Evaluating affected room types, inventory rows, and OTA broadcast targets.</div>
                </div>

                <!-- Error State -->
                <div id="previewErrorState" style="display: none; background: #FEE2E2; border: 1px solid #FCA5A5; color: #991B1B; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px;">
                    <div style="font-weight: 700; margin-bottom: 4px;"><i class="fa-solid fa-circle-exclamation"></i> Preview Generation Error</div>
                    <div id="previewErrorMessage" style="font-size: 13px;"></div>
                </div>

                <!-- Content Container -->
                <div id="previewContentContainer" style="display: none;">
                    <!-- Key Metric Chips -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 24px;">
                        <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px; padding: 14px;">
                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Total Days</div>
                            <div id="diffTotalDays" style="font-size: 24px; font-weight: 800; color: #183B50; margin-top: 2px;">0</div>
                        </div>
                        <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px; padding: 14px;">
                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Records Affected</div>
                            <div id="diffTotalRecords" style="font-size: 24px; font-weight: 800; color: #0284C7; margin-top: 2px;">0</div>
                        </div>
                        <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px; padding: 14px;">
                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Connected Channels</div>
                            <div id="diffChannelsCount" style="font-size: 24px; font-weight: 800; color: #287F79; margin-top: 2px;">0</div>
                        </div>
                        <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px; padding: 14px;">
                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Affected Categories</div>
                            <div id="diffRoomTypesCount" style="font-size: 24px; font-weight: 800; color: #7C3AED; margin-top: 2px;">All</div>
                        </div>
                    </div>

                    <!-- Warnings Notification Box -->
                    <div id="diffWarningsContainer" style="display: none; background: #FFFBEB; border: 1px solid #FCD34D; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px;">
                        <div style="font-weight: 700; color: #92400E; margin-bottom: 6px;"><i class="fa-solid fa-triangle-exclamation"></i> Operational Notices:</div>
                        <ul id="diffWarningsList" style="margin: 0; padding-left: 20px; font-size: 13px; color: #78350F; line-height: 1.6;"></ul>
                    </div>

                    <!-- Before-and-After Summary Table -->
                    <h4 style="font-size: 14px; font-weight: 700; color: var(--text-main); margin: 0 0 10px;">
                        Proposed Parameter Adjustments
                    </h4>
                    <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 24px; border: 1px solid var(--border);">
                        <thead>
                            <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                                <th style="padding: 10px 14px;">Field</th>
                                <th style="padding: 10px 14px;">Existing Value</th>
                                <th style="padding: 10px 14px;">Proposed Value</th>
                                <th style="padding: 10px 14px; text-align: right;">Impact Scope</th>
                            </tr>
                        </thead>
                        <tbody id="diffSummaryTbody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>

                    <!-- Detailed Sample Items Table -->
                    <h4 style="font-size: 14px; font-weight: 700; color: var(--text-main); margin: 0 0 10px;">
                        Sample Record Impact (First 15 Rows)
                    </h4>
                    <div style="overflow-x: auto; max-height: 320px; border: 1px solid var(--border); border-radius: 8px; margin-bottom: 24px;">
                        <table class="table" style="width: 100%; border-collapse: collapse; font-size: 12px;">
                            <thead style="position: sticky; top: 0; background: #F8FAFC; z-index: 1;">
                                <tr style="border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                                    <th style="padding: 10px 12px;">Date</th>
                                    <th style="padding: 10px 12px;">Room Type</th>
                                    <th style="padding: 10px 12px;">Available Units</th>
                                    <th style="padding: 10px 12px;">Base Rate</th>
                                    <th style="padding: 10px 12px;">Min Stay</th>
                                    <th style="padding: 10px 12px; text-align: right;">Stop Sell</th>
                                </tr>
                            </thead>
                            <tbody id="diffSampleTbody">
                                <!-- Populated dynamically via JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Confirmation & Action Buttons -->
                    <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px; padding: 18px 22px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                        <div>
                            <div style="font-weight: 700; font-size: 14px; color: var(--text-main);">Ready to commit these adjustments?</div>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                Outbox synchronization events will be queued immediately. You can roll back anytime with 1-click.
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="button" onclick="closeDiffPreview()" class="btn btn-secondary" style="font-weight: 600;">
                                Cancel / Edit Parameters
                            </button>
                            <button type="button" id="btnConfirmApply" onclick="submitCommitForm()" class="btn" style="background: #287F79; color: white; border: none; font-weight: 700; padding: 10px 20px;">
                                <i class="fa-solid fa-check"></i> Confirm &amp; Broadcast Bulk Changes
                            </button>
                        </div>
                    </div>
                </div>
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

<script>
// Generate AJAX preview without leaving the page
function generateDiffPreview() {
    const form = document.getElementById('bulkEditorForm');
    const alertBox = document.getElementById('formAlertBox');
    const previewModal = document.getElementById('diffPreviewModal');
    const loadingState = document.getElementById('previewLoadingState');
    const errorState = document.getElementById('previewErrorState');
    const contentContainer = document.getElementById('previewContentContainer');
    const previewBtn = document.getElementById('btnPreviewDiff');

    alertBox.style.display = 'none';

    // 1. Client-side input validation
    const startDate = document.getElementById('inputStartDate').value;
    const endDate = document.getElementById('inputEndDate').value;
    const reason = document.getElementById('inputReason').value.trim();

    if (!startDate || !endDate) {
        showFormAlert('Please select both Start Date and End Date.', 'error');
        return;
    }
    if (new Date(startDate) > new Date(endDate)) {
        showFormAlert('End Date must be on or after Start Date.', 'error');
        return;
    }
    if (reason.length < 5) {
        showFormAlert('Please provide a descriptive audit reason (at least 5 characters).', 'error');
        document.getElementById('inputReason').focus();
        return;
    }

    // Check that at least one modification parameter is provided
    const priceDelta = document.getElementById('inputPriceDelta').value;
    const baseRate = document.getElementById('inputBaseRate').value;
    const minStay = document.getElementById('inputMinStay').value;
    const availableUnits = document.getElementById('inputAvailableUnits').value;
    const stopSell = document.getElementById('stopSellToggle').checked;

    if (!priceDelta && !baseRate && !minStay && !availableUnits && !stopSell) {
        showFormAlert('Please configure at least one operation parameter (Price Delta, Base Rate, Min Stay, Available Units, or Stop-Sell).', 'warning');
        return;
    }

    // 2. Open preview container and show loading
    previewModal.style.display = 'block';
    loadingState.style.display = 'block';
    errorState.style.display = 'none';
    contentContainer.style.display = 'none';
    previewBtn.disabled = true;
    previewBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Calculating Impact...';

    // Scroll smoothly to preview container
    previewModal.scrollIntoView({ behavior: 'smooth', block: 'start' });

    // 3. Fetch preview diff via AJAX
    const formData = new FormData(form);

    fetch('{{ route("bulk_editor.preview") }}', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(errData => {
                throw new Error(errData.message || 'Validation failed on server.');
            }).catch(e => {
                throw new Error('Server returned HTTP ' + response.status + ' (' + response.statusText + ')');
            });
        }
        return response.json();
    })
    .then(data => {
        loadingState.style.display = 'none';
        previewBtn.disabled = false;
        previewBtn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> Refresh Preview Diff';

        if (!data.success || !data.preview) {
            throw new Error(data.message || 'Unable to compute diff matrix.');
        }

        renderDiffView(data.preview);
    })
    .catch(err => {
        loadingState.style.display = 'none';
        errorState.style.display = 'block';
        document.getElementById('previewErrorMessage').textContent = err.message || 'Network error occurred while fetching preview.';
        previewBtn.disabled = false;
        previewBtn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> Preview Diff Before Applying';
    });
}

function renderDiffView(preview) {
    const contentContainer = document.getElementById('previewContentContainer');
    contentContainer.style.display = 'block';

    // Populate Chips
    document.getElementById('diffTotalDays').textContent = preview.total_days || 0;
    document.getElementById('diffTotalRecords').textContent = preview.total_records_affected || 0;
    document.getElementById('diffChannelsCount').textContent = preview.channels_count || (preview.channels ? preview.channels.length : 0);
    document.getElementById('diffRoomTypesCount').textContent = (preview.room_types_affected && preview.room_types_affected.length > 0) ? preview.room_types_affected.length : 'All';

    // Warnings
    const warningsContainer = document.getElementById('diffWarningsContainer');
    const warningsList = document.getElementById('diffWarningsList');
    warningsList.innerHTML = '';

    if (preview.warnings && preview.warnings.length > 0) {
        warningsContainer.style.display = 'block';
        preview.warnings.forEach(w => {
            const li = document.createElement('li');
            li.textContent = w;
            warningsList.appendChild(li);
        });
    } else if (preview.total_records_affected === 0) {
        warningsContainer.style.display = 'block';
        const li = document.createElement('li');
        li.textContent = 'No matching inventory rows found for this date range. Consider clicking "Refresh Demo Data" above.';
        warningsList.appendChild(li);
    } else {
        warningsContainer.style.display = 'none';
    }

    // High-Impact Summary Table
    const summaryTbody = document.getElementById('diffSummaryTbody');
    summaryTbody.innerHTML = '';

    const changes = preview.changes_summary || {};
    
    // Room Rate row
    if (changes.base_rate !== undefined || changes.price_delta !== undefined) {
        let proposed = changes.base_rate !== undefined ? ('¥' + Number(changes.base_rate).toLocaleString()) : ((Number(changes.price_delta) >= 0 ? '+' : '') + Number(changes.price_delta).toLocaleString() + ' (Delta)');
        addSummaryRow(summaryTbody, 'Room Base Rate', 'Existing Tier Rates', proposed, '<span class="badge" style="background:#D1FAE5; color:#065F46;">Rate Matrix</span>');
    }
    // Min stay row
    if (changes.min_stay !== undefined) {
        addSummaryRow(summaryTbody, 'Minimum Stay', 'Existing rule (1-2 nights)', changes.min_stay + ' night(s)', '<span class="badge" style="background:#E0E7FF; color:#3730A3;">Booking Restrictions</span>');
    }
    // Available units row
    if (changes.available_units !== undefined) {
        addSummaryRow(summaryTbody, 'Available Units', 'Current allotment', changes.available_units + ' rooms', '<span class="badge" style="background:#FEF3C7; color:#92400E;">Capacity Override</span>');
    }
    // Stop sell row
    if (changes.stop_sell !== undefined) {
        const stopText = changes.stop_sell ? '<span class="badge" style="background:#FEE2E2; color:#991B1B; font-weight:700;">STOP SELL ENABLED</span>' : '<span class="badge" style="background:#D1FAE5; color:#065F46;">OPEN</span>';
        addSummaryRow(summaryTbody, 'Inbound Distribution', 'Channels Open', stopText, '<span class="badge" style="background:#FEE2E2; color:#991B1B;">OTA Channel State</span>');
    }

    // Detailed Samples Table
    const sampleTbody = document.getElementById('diffSampleTbody');
    sampleTbody.innerHTML = '';

    const items = preview.sample_items || [];
    if (items.length === 0) {
        sampleTbody.innerHTML = '<tr><td colspan="6" style="padding: 20px; text-align: center; color: var(--text-muted);">No records found in range.</td></tr>';
    } else {
        items.forEach(item => {
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid var(--border)';

            const rateChanged = item.old_rate !== item.new_rate;
            const unitsChanged = item.old_available !== item.new_available;
            const stopChanged = item.old_stop_sell !== item.new_stop_sell;

            const rateHtml = rateChanged 
                ? `<span style="text-decoration: line-through; color: var(--text-muted);">¥${Number(item.old_rate).toLocaleString()}</span> &rarr; <strong style="color: #065F46;">¥${Number(item.new_rate).toLocaleString()}</strong>`
                : `¥${Number(item.old_rate).toLocaleString()}`;

            const unitsHtml = unitsChanged
                ? `<span style="text-decoration: line-through; color: var(--text-muted);">${item.old_available}</span> &rarr; <strong>${item.new_available}</strong>`
                : `${item.old_available}`;

            const stopHtml = item.new_stop_sell
                ? '<span class="badge" style="background:#FEE2E2; color:#991B1B; font-weight:700;">STOP</span>'
                : '<span class="badge" style="background:#F1F5F9; color:#475569;">OPEN</span>';

            tr.innerHTML = `
                <td style="padding: 10px 12px; font-weight: 600;">${item.date}</td>
                <td style="padding: 10px 12px;">${item.room_type}</td>
                <td style="padding: 10px 12px;">${unitsHtml}</td>
                <td style="padding: 10px 12px;">${rateHtml}</td>
                <td style="padding: 10px 12px;">${item.new_min_stay || 1} nt</td>
                <td style="padding: 10px 12px; text-align: right;">${stopHtml}</td>
            `;
            sampleTbody.appendChild(tr);
        });
    }
}

function addSummaryRow(tbody, field, existingVal, proposedVal, scopeHtml) {
    const tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid var(--border)';
    tr.innerHTML = `
        <td style="padding: 10px 14px; font-weight: 700; color: var(--text-main);">${field}</td>
        <td style="padding: 10px 14px; color: var(--text-muted);">${existingVal}</td>
        <td style="padding: 10px 14px; font-weight: 700; color: #183B50;">${proposedVal}</td>
        <td style="padding: 10px 14px; text-align: right;">${scopeHtml}</td>
    `;
    tbody.appendChild(tr);
}

function closeDiffPreview() {
    document.getElementById('diffPreviewModal').style.display = 'none';
    const previewBtn = document.getElementById('btnPreviewDiff');
    previewBtn.disabled = false;
    previewBtn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> Preview Diff Before Applying';
}

function submitCommitForm() {
    const confirmMsg = 'Are you sure you want to broadcast these changes across all connected booking channels? This will generate transactional outbox events.';
    if (confirm(confirmMsg)) {
        const form = document.getElementById('bulkEditorForm');
        form.submit();
    }
}

function showFormAlert(message, type) {
    const alertBox = document.getElementById('formAlertBox');
    alertBox.style.display = 'block';
    if (type === 'error') {
        alertBox.style.background = '#FEE2E2';
        alertBox.style.border = '1px solid #FCA5A5';
        alertBox.style.color = '#991B1B';
    } else {
        alertBox.style.background = '#FFFBEB';
        alertBox.style.border = '1px solid #FCD34D';
        alertBox.style.color = '#92400E';
    }
    alertBox.textContent = message;
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>
@endsection
