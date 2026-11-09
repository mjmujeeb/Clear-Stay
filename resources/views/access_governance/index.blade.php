@extends('layouts.app')

@section('title', 'アクセスガバナンス・権限管理 (Access Governance) - ClearStay 4.0')
@section('header_title', 'アクセスガバナンス・権限管理 (Access Governance)')
@section('header_subtitle', '支配人による権限昇格・2名承認ワークフロー・一時的アクセス制御・IEEEハイブリッド認可')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Top Action Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; background: #ffffff; padding: 18px 24px; border-radius: 14px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: #183B50;">
                <i class="fa-solid fa-user-shield" style="color: #287F79;"></i> 従業員アクセス制御・権限統制センター
            </h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 2px;">
                最小権限の原則 (PoLP) および職務分掌 (SoD) を強制するエンタープライズ認可システム
            </p>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('access_governance.demo') }}" class="btn btn-primary" style="background: #287F79; font-weight: 700;">
                <i class="fa-solid fa-play-circle"></i> 5シナリオ検証デモ (Live Demo Studio)
            </a>
            <button type="button" class="btn btn-secondary" onclick="openGrantModal(null)" style="font-weight: 600;">
                <i class="fa-solid fa-key"></i> 権限付与・昇格ウィザード
            </button>
        </div>
    </div>

    <!-- Summary Metrics Strip -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <div class="card" style="margin-bottom: 0; padding: 18px 20px; border-left: 4px solid #287F79;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">登録従業員数</div>
                    <div style="font-size: 26px; font-weight: 800; color: #183B50; margin-top: 4px;">{{ $staff->count() }} <span style="font-size: 14px; font-weight: 600;">名</span></div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #E6F3F2; color: #287F79; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 0; padding: 18px 20px; border-left: 4px solid #F59E0B;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">2名承認待ち昇格申請</div>
                    <div style="font-size: 26px; font-weight: 800; color: #B45309; margin-top: 4px;">{{ $pendingRequests->count() }} <span style="font-size: 14px; font-weight: 600;">件</span></div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #FEF3C7; color: #B45309; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="fa-solid fa-user-clock"></i>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 0; padding: 18px 20px; border-left: 4px solid #10B981;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">有効な一時的昇格権限</div>
                    <div style="font-size: 26px; font-weight: 800; color: #047857; margin-top: 4px;">{{ $activeElevations->count() }} <span style="font-size: 14px; font-weight: 600;">件</span></div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #D1FAE5; color: #047857; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="fa-solid fa-shield-check"></i>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 0; padding: 18px 20px; border-left: 4px solid #EF4444;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">自動遮断された不正試行</div>
                    <div style="font-size: 26px; font-weight: 800; color: #B91C1C; margin-top: 4px;">{{ $recentRiskEvents->where('action_taken', 'deny')->count() }} <span style="font-size: 14px; font-weight: 600;">件</span></div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #FEE2E2; color: #B91C1C; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="fa-solid fa-ban"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 1: Pending Two-Person Approval Requests (If any) -->
    @if($pendingRequests->isNotEmpty())
        <div class="card" style="border: 2px solid #F59E0B; background: #FFFBEB;">
            <div class="card-header" style="background: #FEF3C7; border-bottom: 1px solid #FDE68A;">
                <h3 style="color: #92400E; font-size: 15px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> セキュリティ管理者による2名承認が必要な昇格申請 ({{ $pendingRequests->count() }}件)
                </h3>
                <span class="badge" style="background: #D97706; color: #ffffff;">Two-Person Rule Enforced</span>
            </div>
            <div class="table-responsive">
                <table class="data-table" style="background: #ffffff;">
                    <thead>
                        <tr>
                            <th>対象従業員</th>
                            <th>申請された権限</th>
                            <th>リスク水準</th>
                            <th>申請者</th>
                            <th>有効期間</th>
                            <th>申請理由 (Justification)</th>
                            <th style="text-align: right;">審査アクション</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingRequests as $req)
                            <tr>
                                <td>
                                    <strong>{{ $req->user->name }}</strong>
                                    <div style="font-size: 11px; color: var(--text-muted);">{{ $req->user->email }} ({{ $req->user->role }})</div>
                                </td>
                                <td>
                                    <span style="font-family: monospace; font-size: 12px; font-weight: 700; color: #183B50;">{{ $req->permission->name }}</span>
                                    <div style="font-size: 11px; color: var(--text-muted);">{{ $req->permission->description_ja ?? $req->permission->description }}</div>
                                </td>
                                <td>
                                    <span class="badge" style="background: #FEE2E2; color: #991B1B; font-weight: 800;">
                                        <i class="fa-solid fa-circle-exclamation"></i> {{ strtoupper($req->risk_level) }}
                                    </span>
                                </td>
                                <td>
                                    <strong>{{ $req->requester->name }}</strong>
                                    <div style="font-size: 11px; color: var(--text-muted);">{{ $req->requester->role }}</div>
                                </td>
                                <td>{{ $req->duration_hours }}時間</td>
                                <td style="max-width: 260px; font-size: 12px;">{{ $req->justification }}</td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 8px;">
                                        <form action="{{ route('access_governance.approve_request', $req->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="review_notes" value="Approved by Security Administrator via governance dashboard.">
                                            <button type="submit" class="btn btn-sm btn-success" title="Approve Request">
                                                <i class="fa-solid fa-check"></i> 承認 (Approve)
                                            </button>
                                        </form>
                                        <form action="{{ route('access_governance.reject_request', $req->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="review_notes" value="Rejected: Insufficient justification for rate override.">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Reject Request">
                                                <i class="fa-solid fa-xmark"></i> 却下
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Section 2: Staff Roster & Permission Controls -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-address-book text-primary"></i> 従業員権限一覧 (Property Staff Roster)</h3>
            <span style="font-size: 12px; color: var(--text-muted);">ホテル所属スタッフの標準ロールおよび一時的権限昇格の管理</span>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>従業員氏名 / 所属</th>
                        <th>メールアドレス</th>
                        <th>基本ロール (Base Role)</th>
                        <th>所属ホテル</th>
                        <th>一時的特権 (Active Elevations)</th>
                        <th>アカウント状態</th>
                        <th style="text-align: right;">権限操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($staff as $member)
                        @php
                            $activeOverrides = $member->userPermissions->filter(fn($p) => $p->isValidNow());
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $member->name }}</strong>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ $member->department ?? 'General Operations' }}</div>
                            </td>
                            <td>
                                <span style="font-family: monospace; font-size: 12px;">{{ $member->email }}</span>
                            </td>
                            <td>
                                @php
                                    $badgeCol = match($member->role) {
                                        'superadmin' => '#0F1D2F',
                                        'manager', 'owner' => '#183B50',
                                        'security_admin' => '#B91C1C',
                                        'auditor' => '#6D28D9',
                                        'frontdesk', 'receptionist' => '#0284C7',
                                        'housekeeping' => '#059669',
                                        'qa' => '#287F79',
                                        default => '#475569',
                                    };
                                @endphp
                                <span class="badge" style="background: {{ $badgeCol }}; color: #ffffff;">
                                    {{ strtoupper($member->role) }}
                                </span>
                            </td>
                            <td>{{ $member->hotel?->name ?? 'Global Platform' }}</td>
                            <td>
                                @if($activeOverrides->isNotEmpty())
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        @foreach($activeOverrides as $ov)
                                            <div style="font-size: 11px; display: inline-flex; align-items: center; gap: 6px; background: #D1FAE5; color: #065F46; padding: 2px 8px; border-radius: 6px; font-weight: 700;">
                                                <i class="fa-solid fa-bolt"></i> {{ $ov->permission->name }}
                                                @if($ov->expires_at)
                                                    <span style="font-size: 10px; opacity: 0.8;">({{ $ov->expires_at->diffForHumans() }})</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="font-size: 11px; color: var(--text-muted);">標準権限のみ</span>
                                @endif
                            </td>
                            <td>
                                @if($member->is_active && !$member->is_suspended)
                                    <span class="badge badge-clean"><i class="fa-solid fa-circle-check"></i> 有効</span>
                                @else
                                    <span class="badge badge-dirty"><i class="fa-solid fa-circle-xmark"></i> 凍結中</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="openGrantModal({{ $member->id }}, '{{ addslashes($member->name) }}', '{{ $member->email }}')" title="Grant or Elevate Permission">
                                    <i class="fa-solid fa-key" style="color: #287F79;"></i> 昇格付与
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 3: Active Elevations & Revocation Controls -->
    @if($activeElevations->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-stopwatch text-primary"></i> 有効な一時的権限昇格一覧 (Active Temporary Elevations)</h3>
                <span style="font-size: 12px; color: var(--text-muted);">期限到来時に自動剥奪される特権セッション</span>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>対象従業員</th>
                            <th>付与された特権</th>
                            <th>付与者 (Granted By)</th>
                            <th>有効期限 (Expiry)</th>
                            <th>付与理由 (Reason)</th>
                            <th style="text-align: right;">緊急取消</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activeElevations as $el)
                            <tr>
                                <td><strong>{{ $el->user->name }}</strong> ({{ $el->user->email }})</td>
                                <td>
                                    <span style="font-family: monospace; font-size: 12px; font-weight: 700; color: #287F79;">{{ $el->permission->name }}</span>
                                </td>
                                <td>{{ $el->grantedByUser?->name ?? 'System' }}</td>
                                <td>
                                    <strong>{{ $el->expires_at ? $el->expires_at->format('Y-m-d H:i') : 'Permanent' }}</strong>
                                    <div style="font-size: 11px; color: var(--text-muted);">{{ $el->expires_at ? $el->expires_at->diffForHumans() : '' }}</div>
                                </td>
                                <td style="font-size: 12px;">{{ $el->reason }}</td>
                                <td style="text-align: right;">
                                    <form action="{{ route('access_governance.revoke', $el->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('この権限を直ちに剥奪しますか？')" title="Revoke Immediately">
                                            <i class="fa-solid fa-trash-can"></i> 即時剥奪
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

<!-- Modal: Permission Grant & Elevation Wizard with Deterministic Risk Engine -->
<div id="grantModal" class="modal-overlay">
    <div class="modal-container" style="max-width: 680px;">
        <div class="modal-header">
            <div>
                <h3 style="font-size: 18px; font-weight: 800; color: #183B50;">
                    <i class="fa-solid fa-key" style="color: #287F79;"></i> 従業員権限の昇格・付与ウィザード
                </h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    リスク判定エンジンが昇格の影響を自動解析し、2名承認要否を判定します
                </p>
            </div>
            <button type="button" class="btn btn-sm btn-secondary" onclick="closeGrantModal()">&times;</button>
        </div>

        <form action="{{ route('access_governance.grant') }}" method="POST" id="grantForm">
            @csrf
            <div class="modal-body" style="display: flex; flex-direction: column; gap: 20px;">
                
                <!-- Target User Selection -->
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #183B50; margin-bottom: 6px;">
                        1. 対象従業員 (Target Staff Member) <span style="color: #EF4444;">*</span>
                    </label>
                    <select name="target_user_id" id="modalTargetUser" required style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-weight: 600;" onchange="evaluateModalRisk()">
                        <option value="">従業員を選択してください</option>
                        @foreach($staff as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} — {{ $m->email }} ({{ $m->role }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Permission Selection -->
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #183B50; margin-bottom: 6px;">
                        2. 付与・昇格する権限 (Permission to Grant) <span style="color: #EF4444;">*</span>
                    </label>
                    <select name="permission_id" id="modalPermissionSelect" required style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-weight: 600;" onchange="evaluateModalRisk()">
                        <option value="">権限を選択してください</option>
                        @foreach($allPermissions as $p)
                            <option value="{{ $p->id }}" data-name="{{ $p->name }}">
                                [{{ strtoupper($p->category) }}] {{ $p->name }} — {{ $p->description_ja ?? $p->description }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Deterministic Risk Engine Warning Card -->
                <div id="riskWarningBox" style="display: none; border-radius: 10px; padding: 16px; border: 1px solid #CBD5E1; background: #F8FAFC;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">
                            <i class="fa-solid fa-microchip"></i> リスク評価エンジン判定結果
                        </span>
                        <span id="riskBadge" class="badge" style="font-size: 11px;">LOW RISK</span>
                    </div>
                    <div id="riskTitle" style="font-size: 14px; font-weight: 700; color: #183B50; margin-bottom: 4px;"></div>
                    <div id="riskImpact" style="font-size: 12px; color: var(--text-muted); line-height: 1.45; margin-bottom: 6px;"></div>
                    <div id="riskAffectedData" style="font-size: 11px; color: #0284C7; font-weight: 600;"></div>
                    <div id="approvalRoleNotice" style="margin-top: 10px; padding: 8px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; display: none;"></div>
                </div>

                <!-- Duration in Hours -->
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #183B50; margin-bottom: 6px;">
                        3. 付与期間 (Duration) <span style="color: #EF4444;">*</span>
                    </label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="number" name="duration_hours" id="modalDuration" value="24" min="1" max="720" required style="width: 140px; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-weight: 700;">
                        <span style="font-size: 13px; color: var(--text-muted);">時間 (推奨: シフト期間内 8〜24時間)</span>
                    </div>
                </div>

                <!-- Business Justification -->
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #183B50; margin-bottom: 6px;">
                        4. 業務上の正当理由 (Business Justification) <span style="color: #EF4444;">*</span>
                    </label>
                    <textarea name="justification" id="modalJustification" required rows="3" placeholder="例: 大型連休の混雑対応に伴い、フロントリーダーの部屋割当権限を一時的に付与" style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; resize: vertical;"></textarea>
                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">監査証跡として保存されます。具体的に記入してください。</div>
                </div>

            </div>

            <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn btn-secondary" onclick="closeGrantModal()">キャンセル</button>
                <button type="submit" class="btn btn-primary" id="btnSubmitGrant" style="background: #287F79;">
                    <i class="fa-solid fa-shield-halved"></i> <span id="submitGrantText">権限を即時付与する</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openGrantModal(userId = null, userName = '', userEmail = '') {
        $('#grantModal').addClass('active');
        if (userId) {
            $('#modalTargetUser').val(userId);
        }
        evaluateModalRisk();
    }

    function closeGrantModal() {
        $('#grantModal').removeClass('active');
    }

    function evaluateModalRisk() {
        const userId = $('#modalTargetUser').val();
        const selectedOpt = $('#modalPermissionSelect option:selected');
        const permName = selectedOpt.data('name');

        if (!userId || !permName) {
            $('#riskWarningBox').hide();
            return;
        }

        $.ajax({
            url: '{{ route('access_governance.evaluate_risk') }}',
            method: 'POST',
            data: {
                target_user_id: userId,
                permission_name: permName,
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                if (!res.success) return;
                const ev = res.evaluation;
                $('#riskWarningBox').show();
                $('#riskTitle').text(ev.title);
                $('#riskImpact').text(ev.impact_description);
                $('#riskAffectedData').html('<i class="fa-solid fa-database"></i> 影響対象データ: ' + ev.affected_data);

                // Badge styling
                const badge = $('#riskBadge');
                badge.text(ev.risk_level.toUpperCase() + ' RISK');
                if (ev.risk_level === 'critical') {
                    badge.attr('style', 'background: #7F1D1D; color: #fff;');
                    $('#riskWarningBox').css({'border-color': '#EF4444', 'background': '#FEF2F2'});
                } else if (ev.risk_level === 'high') {
                    badge.attr('style', 'background: #DC2626; color: #fff;');
                    $('#riskWarningBox').css({'border-color': '#F87171', 'background': '#FEF2F2'});
                } else if (ev.risk_level === 'medium') {
                    badge.attr('style', 'background: #D97706; color: #fff;');
                    $('#riskWarningBox').css({'border-color': '#FBBF24', 'background': '#FFFBEB'});
                } else {
                    badge.attr('style', 'background: #059669; color: #fff;');
                    $('#riskWarningBox').css({'border-color': '#A7F3D0', 'background': '#F0FDF4'});
                }

                // Approval notice
                const notice = $('#approvalRoleNotice');
                if (ev.is_prohibited) {
                    notice.show().css({'background': '#FEE2E2', 'color': '#991B1B'})
                        .html('<i class="fa-solid fa-ban"></i> <b>特権昇格禁止ポリシー:</b> この権限は支配人権限で付与できません（自動遮断されます）。');
                    $('#btnSubmitGrant').prop('disabled', true);
                    $('#submitGrantText').text('付与禁止 (Prohibited)');
                } else if (ev.requires_approval) {
                    notice.show().css({'background': '#FEF3C7', 'color': '#92400E'})
                        .html('<i class="fa-solid fa-user-lock"></i> <b>2名承認必須:</b> 申請後、セキュリティ管理者 (' + ev.approval_role + ') の承認を経て有効化されます。');
                    $('#btnSubmitGrant').prop('disabled', false);
                    $('#submitGrantText').text('2名承認を申請する (Request Approval)');
                } else {
                    notice.hide();
                    $('#btnSubmitGrant').prop('disabled', false);
                    $('#submitGrantText').text('権限を即時付与する (Grant Direct)');
                }
            }
        });
    }
</script>
@endpush
@endsection
