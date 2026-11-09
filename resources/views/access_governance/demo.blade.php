@extends('layouts.app')

@section('title', 'アクセスガバナンス検証デモ (Live Demo Studio) - ClearStay 4.0')
@section('header_title', 'アクセスガバナンス・ライブ検証デモ (Live Demo Studio)')
@section('header_subtitle', '採用面接向け・6ロール認可・2名承認・特権昇格遮断・自動期限切れの対話型実演')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Interview Role Switcher Strip -->
    <div class="card" style="margin-bottom: 0; background: linear-gradient(135deg, #0F1D2F, #183B50); color: #ffffff; border: 1px solid #287F79;">
        <div class="card-body" style="padding: 20px 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                <div>
                    <div style="font-size: 11px; font-weight: 800; color: #2DD4BF; text-transform: uppercase; letter-spacing: 0.06em;">
                        <i class="fa-solid fa-users-viewfinder"></i> 6ロール実演セッション切替 (Active Session Switcher)
                    </div>
                    <div style="font-size: 18px; font-weight: 800; margin-top: 4px;">
                        現在のログイン: <span style="color: #E8A64A;">{{ auth()->user()->name }}</span>
                        <span class="badge" style="background: rgba(255,255,255,0.15); color: #2DD4BF; margin-left: 8px;">{{ strtoupper(auth()->user()->role) }}</span>
                    </div>
                </div>

                <!-- 1-Click Role Switch Forms -->
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <form action="{{ route('access_governance.switch_demo_user', 'manager') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-sm" style="background: {{ auth()->user()->role === 'manager' ? '#2DD4BF' : 'rgba(255,255,255,0.1)' }}; color: {{ auth()->user()->role === 'manager' ? '#0F1D2F' : '#ffffff' }}; font-weight: 700;">
                            支配人 (Manager)
                        </button>
                    </form>

                    <form action="{{ route('access_governance.switch_demo_user', 'receptionist') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-sm" style="background: {{ auth()->user()->role === 'frontdesk' ? '#2DD4BF' : 'rgba(255,255,255,0.1)' }}; color: {{ auth()->user()->role === 'frontdesk' ? '#0F1D2F' : '#ffffff' }}; font-weight: 700;">
                            フロント (Receptionist)
                        </button>
                    </form>

                    <form action="{{ route('access_governance.switch_demo_user', 'housekeeping') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-sm" style="background: {{ auth()->user()->role === 'housekeeping' ? '#2DD4BF' : 'rgba(255,255,255,0.1)' }}; color: {{ auth()->user()->role === 'housekeeping' ? '#0F1D2F' : '#ffffff' }}; font-weight: 700;">
                            清掃員 (Housekeeper)
                        </button>
                    </form>

                    <form action="{{ route('access_governance.switch_demo_user', 'secadmin') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-sm" style="background: {{ auth()->user()->role === 'security_admin' ? '#2DD4BF' : 'rgba(255,255,255,0.1)' }}; color: {{ auth()->user()->role === 'security_admin' ? '#0F1D2F' : '#ffffff' }}; font-weight: 700;">
                            セキュリティ管理者
                        </button>
                    </form>

                    <form action="{{ route('access_governance.switch_demo_user', 'auditor') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-sm" style="background: {{ auth()->user()->role === 'auditor' ? '#2DD4BF' : 'rgba(255,255,255,0.1)' }}; color: {{ auth()->user()->role === 'auditor' ? '#0F1D2F' : '#ffffff' }}; font-weight: 700;">
                            監査役 (Auditor)
                        </button>
                    </form>

                    <form action="{{ route('access_governance.switch_demo_user', 'qa') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-sm" style="background: {{ auth()->user()->role === 'qa' ? '#2DD4BF' : 'rgba(255,255,255,0.1)' }}; color: {{ auth()->user()->role === 'qa' ? '#0F1D2F' : '#ffffff' }}; font-weight: 700;">
                            QA検証員 (QA)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- The 5 Guided Scenarios Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px;">
        
        <!-- Scenario 1: Normal Grant -->
        <div class="card" style="border-top: 4px solid #10B981; margin-bottom: 0;">
            <div class="card-body" style="padding: 22px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <span class="badge" style="background: #D1FAE5; color: #047857; font-weight: 800;">SCENARIO 1</span>
                    <span style="font-size: 11px; font-weight: 700; color: #10B981;"><i class="fa-solid fa-circle-check"></i> 通常付与 (Low Risk)</span>
                </div>
                <h3 style="font-size: 16px; font-weight: 800; color: #183B50; margin-bottom: 6px;">
                    支配人による通常業務権限の付与
                </h3>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 16px;">
                    支配人 (Kenji Sato) がフロント係 (Aiko Tanaka) に対し、大型連休対応のため客室変更権限 (<code style="color: #287F79;">reservation.modify.property</code>) を8時間付与。2名承認不要で即時反映。
                </p>
                <button type="button" class="btn btn-sm btn-primary" onclick="runScenario(1)" style="width: 100%; justify-content: center; background: #059669;">
                    <i class="fa-solid fa-play"></i> シナリオ1を実行する
                </button>
            </div>
        </div>

        <!-- Scenario 2: Two-Person Approval -->
        <div class="card" style="border-top: 4px solid #F59E0B; margin-bottom: 0;">
            <div class="card-body" style="padding: 22px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <span class="badge" style="background: #FEF3C7; color: #B45309; font-weight: 800;">SCENARIO 2</span>
                    <span style="font-size: 11px; font-weight: 700; color: #D97706;"><i class="fa-solid fa-user-lock"></i> 2名承認 (High Risk)</span>
                </div>
                <h3 style="font-size: 16px; font-weight: 800; color: #183B50; margin-bottom: 6px;">
                    高リスク料金オーバーライドの2名承認
                </h3>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 16px;">
                    限度額を超える割引権限 (<code style="color: #B45309;">financial.rate_override</code>) を申請。直接付与が拒否され保留状態となった後、セキュリティ管理者が承認して有効化。
                </p>
                <button type="button" class="btn btn-sm btn-primary" onclick="runScenario(2)" style="width: 100%; justify-content: center; background: #D97706;">
                    <i class="fa-solid fa-play"></i> シナリオ2を実行する
                </button>
            </div>
        </div>

        <!-- Scenario 3: Blocked Escalation -->
        <div class="card" style="border-top: 4px solid #EF4444; margin-bottom: 0;">
            <div class="card-body" style="padding: 22px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <span class="badge" style="background: #FEE2E2; color: #991B1B; font-weight: 800;">SCENARIO 3</span>
                    <span style="font-size: 11px; font-weight: 700; color: #EF4444;"><i class="fa-solid fa-shield-halved"></i> 遮断試行 (Critical)</span>
                </div>
                <h3 style="font-size: 16px; font-weight: 800; color: #183B50; margin-bottom: 6px;">
                    不正特権昇格（セルフ昇格）の自動遮断
                </h3>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 16px;">
                    支配人が自身へ全社プラットフォーム権限 (<code style="color: #EF4444;">platform.admin</code>) の付与を試行。ポリシーエンジンが即座に拒否し、セキュリティリスクイベントへ記録。
                </p>
                <button type="button" class="btn btn-sm btn-danger" onclick="runScenario(3)" style="width: 100%; justify-content: center;">
                    <i class="fa-solid fa-shield-halved"></i> シナリオ3を実行する
                </button>
            </div>
        </div>

        <!-- Scenario 4: Auto Expiry -->
        <div class="card" style="border-top: 4px solid #3B82F6; margin-bottom: 0;">
            <div class="card-body" style="padding: 22px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <span class="badge" style="background: #DBEAFE; color: #1E40AF; font-weight: 800;">SCENARIO 4</span>
                    <span style="font-size: 11px; font-weight: 700; color: #2563EB;"><i class="fa-solid fa-clock"></i> 自動剥奪 (Lifecycle)</span>
                </div>
                <h3 style="font-size: 16px; font-weight: 800; color: #183B50; margin-bottom: 6px;">
                    一時的特権の有効期限切れ自動失効
                </h3>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 16px;">
                    付与された一時的権限の期限が到来したケースをシミュレート。動的認可判定 (<code style="color: #2563EB;">hasPermission()</code>) が即座に拒否（false）を返すことを実証。
                </p>
                <button type="button" class="btn btn-sm btn-primary" onclick="runScenario(4)" style="width: 100%; justify-content: center; background: #2563EB;">
                    <i class="fa-solid fa-play"></i> シナリオ4を実行する
                </button>
            </div>
        </div>

        <!-- Scenario 5: Auditor Read-Only -->
        <div class="card" style="border-top: 4px solid #8B5CF6; margin-bottom: 0;">
            <div class="card-body" style="padding: 22px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <span class="badge" style="background: #EDE9FE; color: #5B21B6; font-weight: 800;">SCENARIO 5</span>
                    <span style="font-size: 11px; font-weight: 700; color: #7C3AED;"><i class="fa-solid fa-scale-balanced"></i> 監査独立性 (Auditor)</span>
                </div>
                <h3 style="font-size: 16px; font-weight: 800; color: #183B50; margin-bottom: 6px;">
                    独立セキュリティ監査役による改ざん不可調査
                </h3>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 16px;">
                    監査役 (Daisuke Mori) が不変の監査ログを検査。監査役はデータ変更権限を持たず（Read-Only）、監査の独立性と客観性が保証されていることを確認。
                </p>
                <button type="button" class="btn btn-sm btn-primary" onclick="runScenario(5)" style="width: 100%; justify-content: center; background: #7C3AED;">
                    <i class="fa-solid fa-play"></i> シナリオ5を実行する
                </button>
            </div>
        </div>

    </div>

    <!-- Live Execution Console / Telemetry Monitor -->
    <div class="card" style="border: 1px solid #1E293B; background: #0B1320; color: #F8FAFC;">
        <div class="card-header" style="background: #0F1D2F; border-bottom: 1px solid #1E293B; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="color: #2DD4BF; font-size: 14px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-terminal"></i> リアルタイム認可判定エンジン・コンソール出力 (Live Evaluation Telemetry)
            </h3>
            <span id="consoleStatus" style="font-size: 12px; color: #94A3B8;">待機中 (Select a scenario to execute)</span>
        </div>
        <div class="card-body" style="padding: 20px;">
            <div id="consoleVisual" style="margin-bottom: 16px; display: none;">
                <div style="background: rgba(45, 212, 191, 0.1); border: 1px solid rgba(45, 212, 191, 0.3); border-radius: 8px; padding: 14px 18px;">
                    <div id="consoleTitle" style="font-size: 16px; font-weight: 800; color: #2DD4BF; margin-bottom: 4px;"></div>
                    <div id="consoleSummary" style="font-size: 13px; color: #E2E8F0; line-height: 1.5;"></div>
                </div>
            </div>

            <pre id="consoleJson" style="background: #050A14; border: 1px solid #1E293B; border-radius: 8px; padding: 16px; font-family: 'Courier New', monospace; font-size: 12px; color: #38BDF8; overflow-x: auto; max-height: 380px;">// Telemetry will appear here upon scenario execution.</pre>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function runScenario(id) {
        $('#consoleStatus').html('<i class="fa-solid fa-spinner fa-spin"></i> 判定エンジン実行中...');
        $('#consoleVisual').hide();
        $('#consoleJson').text('Evaluating authorization policy rules across tenant isolation and role matrices...');

        $.ajax({
            url: '/access-governance/demo/scenario/' + id,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                if (res.success) {
                    const sc = res.scenario;
                    $('#consoleStatus').html('<span style="color: #2DD4BF;"><i class="fa-solid fa-circle-check"></i> 実行成功 (Verified)</span>');
                    $('#consoleTitle').text(sc.title);
                    $('#consoleSummary').text(sc.summary);
                    $('#consoleVisual').fadeIn();
                    $('#consoleJson').text(JSON.stringify(sc, null, 2));
                }
            },
            error: function(xhr) {
                $('#consoleStatus').html('<span style="color: #EF4444;"><i class="fa-solid fa-circle-xmark"></i> 実行エラー</span>');
                $('#consoleJson').text(JSON.stringify(xhr.responseJSON || xhr.responseText, null, 2));
            }
        });
    }
</script>
@endpush
@endsection
