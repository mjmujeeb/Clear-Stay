@extends('layouts.app')

@section('title', 'プライバシー保護・個人情報統制 (Privacy Centre) - ClearStay 4.0')
@section('header_title', 'プライバシー保護・個人情報統制センター (Privacy Centre)')
@section('header_subtitle', 'インドDPDP法2023 / 2025年規則適合設計・顧客PIIマスキング・アクセス監査証跡')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Top Action Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; background: #ffffff; padding: 18px 24px; border-radius: 14px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: #183B50;">
                <i class="fa-solid fa-user-lock" style="color: #287F79;"></i> プライバシー・バイ・デザイン統制センター
            </h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 2px;">
                宿泊者個人情報 (PII) の常時マスキング、正当理由を義務付ける閲覧解除、不変の監査ログ
            </p>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="{{ route('privacy_centre.export_audit') }}" class="btn btn-secondary" style="font-weight: 600;">
                <i class="fa-solid fa-file-csv" style="color: #059669;"></i> 監査証跡CSV出力 (Export Audit Evidence)
            </a>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <div class="card" style="margin-bottom: 0; padding: 18px 20px; border-left: 4px solid #287F79;">
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">データ保護方針</div>
            <div style="font-size: 22px; font-weight: 800; color: #183B50; margin-top: 4px;">デフォルト秘匿</div>
            <div style="font-size: 11px; color: #059669; margin-top: 2px;"><i class="fa-solid fa-check"></i> 電話・メール常時マスキング</div>
        </div>

        <div class="card" style="margin-bottom: 0; padding: 18px 20px; border-left: 4px solid #0284C7;">
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">記録された閲覧解除ログ</div>
            <div style="font-size: 26px; font-weight: 800; color: #0284C7; margin-top: 4px;">{{ $accessLogs->total() }} <span style="font-size: 14px; font-weight: 600;">件</span></div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">正当理由付き監査記録</div>
        </div>

        <div class="card" style="margin-bottom: 0; padding: 18px 20px; border-left: 4px solid #10B981;">
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">テナント間隔離検証</div>
            <div style="font-size: 22px; font-weight: 800; color: #059669; margin-top: 4px;">100% 完全隔離</div>
            <div style="font-size: 11px; color: #059669; margin-top: 2px;"><i class="fa-solid fa-shield"></i> 他ホテル漏洩 0件</div>
        </div>

        <div class="card" style="margin-bottom: 0; padding: 18px 20px; border-left: 4px solid #6366F1;">
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">法令適合アーキテクチャ</div>
            <div style="font-size: 22px; font-weight: 800; color: #4338CA; margin-top: 4px;">DPDP 2023 準拠</div>
            <div style="font-size: 11px; color: #4338CA; margin-top: 2px;">データ受託者義務の実装</div>
        </div>
    </div>

    <!-- Data Classification Matrix Card -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-layer-group text-primary"></i> ClearStay データ分類基準 (Data Classification Matrix)</h3>
            <span style="font-size: 12px; color: var(--text-muted);">保護要件に基づく4段階のデータ格付け</span>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>機密区分 (Classification)</th>
                        <th>対象データ項目</th>
                        <th>適用される保護策</th>
                        <th>アクセス可能ロール</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="badge" style="background: #E2E8F0; color: #334155; font-weight: 800;">PUBLIC (公開)</span></td>
                        <td>ホテル名称、所在地、客室タイプ基本仕様、公式アメニティ</td>
                        <td>公衆閲覧可能、CDNキャッシュ有効</td>
                        <td>すべての利用者・一般宿泊者</td>
                    </tr>
                    <tr>
                        <td><span class="badge" style="background: #E0F2FE; color: #0369A1; font-weight: 800;">INTERNAL (社内)</span></td>
                        <td>客室清掃状態、稼働率メトリクス、システム稼働ヘルスデータ</td>
                        <td>認証セッション必須、テナントスコープ制限</td>
                        <td>ホテルスタッフ全般、清掃員</td>
                    </tr>
                    <tr>
                        <td><span class="badge" style="background: #FEF3C7; color: #92400E; font-weight: 800;">CONFIDENTIAL (機密)</span></td>
                        <td>宿泊者氏名、滞在日程、割当部屋番号、宿泊代金総額</td>
                        <td>所属ホテル限定アクセス、ログ監査</td>
                        <td>支配人、フロント係、予約担当</td>
                    </tr>
                    <tr>
                        <td><span class="badge" style="background: #FEE2E2; color: #991B1B; font-weight: 800;">RESTRICTED (極秘)</span></td>
                        <td>電話番号、メールアドレス、クレジットカードトークン、パスワードハッシュ</td>
                        <td>UI上常時マスキング、正当理由付き閲覧解除ログ記録、Bcryptハッシュ</td>
                        <td>正当理由を提示したフロント・支配人のみ</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 1: Guest PII Masking & On-Demand Audited Unmasking -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3><i class="fa-solid fa-eye-slash text-primary"></i> 顧客個人情報の常時マスキングと閲覧解除実演</h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    画面表示時は電話・メールが自動マスクされます。「閲覧解除」を実行するには業務上の正当理由の入力が必須です。
                </p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>顧客ID</th>
                        <th>お名前 (Guest Name)</th>
                        <th>マスクされたメールアドレス</th>
                        <th>マスクされた電話番号</th>
                        <th>予約履歴</th>
                        <th style="text-align: right;">監査付き閲覧解除</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($guests as $g)
                        <tr id="guestRow{{ $g['id'] }}">
                            <td><span style="font-family: monospace; font-weight: 700;">#{{ $g['id'] }}</span></td>
                            <td><strong>{{ $g['name'] }}</strong></td>
                            <td>
                                <span class="masked-email" id="emailVal{{ $g['id'] }}" style="font-family: monospace; background: #f1f5f9; padding: 4px 8px; border-radius: 6px;">
                                    {{ $g['masked_email'] }}
                                </span>
                            </td>
                            <td>
                                <span class="masked-phone" id="phoneVal{{ $g['id'] }}" style="font-family: monospace; background: #f1f5f9; padding: 4px 8px; border-radius: 6px;">
                                    {{ $g['masked_phone'] }}
                                </span>
                            </td>
                            <td>{{ $g['reservations_count'] }} 件</td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="openUnmaskModal({{ $g['id'] }}, '{{ addslashes($g['name']) }}')" title="Unmask with Justification">
                                    <i class="fa-solid fa-key" style="color: #287F79;"></i> 解除申請 (Unmask)
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Privacy Access Logs -->
    <div class="card">
        <div class="card-header" style="justify-content: space-between;">
            <div>
                <h3><i class="fa-solid fa-clipboard-list text-primary"></i> 個人情報アクセス監査ログ (Privacy Access Logs)</h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    誰が・いつ・どの顧客の個人情報を・どのような正当理由で閲覧したかの改ざん不可な証跡
                </p>
            </div>
            <a href="{{ route('privacy_centre.export_audit') }}" class="btn btn-sm btn-secondary">
                <i class="fa-solid fa-download"></i> CSV出力
            </a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>追跡相関ID (Correlation ID)</th>
                        <th>タイムスタンプ</th>
                        <th>操作実行者</th>
                        <th>対象顧客</th>
                        <th>閲覧解除理由 (Justification)</th>
                        <th>アクセス元IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accessLogs as $log)
                        <tr>
                            <td>
                                <span style="font-family: monospace; font-size: 11px; font-weight: 700; color: #287F79;">{{ $log->correlation_id }}</span>
                            </td>
                            <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <strong>{{ $log->user?->name ?? 'System' }}</strong>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ $log->user?->role ?? '' }}</div>
                            </td>
                            <td>Guest #{{ $log->guest_id }}</td>
                            <td style="max-width: 300px; font-size: 12px;">{{ $log->justification }}</td>
                            <td style="font-family: monospace; font-size: 11px;">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">まだ閲覧解除ログはありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($accessLogs->hasPages())
            <div style="padding: 16px 24px; border-top: 1px solid var(--border);">
                {{ $accessLogs->links() }}
            </div>
        @endif
    </div>

    <!-- Section 3: India DPDP Act 2023 Architecture Mapping -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-scale-balanced text-primary"></i> インドDPDP法2023・2025年規則 適合性マッピング</h3>
            <span style="font-size: 12px; color: var(--text-muted);">Digital Personal Data Protection Act, 2023 への技術的適合状況</span>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>条文番号 (Section)</th>
                        <th>法令要件タイトル</th>
                        <th>適合ステータス</th>
                        <th>ClearStay システムでの実装内容</th>
                        <th>実証エビデンス</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dpdpMatrix as $item)
                        <tr>
                            <td><strong style="color: #183B50;">{{ $item['section'] }}</strong></td>
                            <td><strong>{{ $item['title'] }}</strong></td>
                            <td>
                                <span class="badge" style="background: #D1FAE5; color: #065F46; font-weight: 800;">
                                    <i class="fa-solid fa-check"></i> {{ $item['status'] }}
                                </span>
                            </td>
                            <td style="font-size: 12px; line-height: 1.45;">{{ $item['description'] }}</td>
                            <td style="font-size: 11px; color: var(--text-muted); font-family: monospace;">{{ $item['evidence'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Audited Unmask Request -->
<div id="unmaskModal" class="modal-overlay">
    <div class="modal-container" style="max-width: 540px;">
        <div class="modal-header">
            <div>
                <h3 style="font-size: 18px; font-weight: 800; color: #183B50;">
                    <i class="fa-solid fa-shield-halved" style="color: #287F79;"></i> 顧客個人情報の閲覧解除申請
                </h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    DPDP法および個人情報保護規程に基づき、閲覧理由の入力が必要です
                </p>
            </div>
            <button type="button" class="btn btn-sm btn-secondary" onclick="closeUnmaskModal()">&times;</button>
        </div>

        <form id="unmaskForm" onsubmit="submitUnmask(event)">
            <input type="hidden" id="unmaskGuestId">
            <div class="modal-body" style="display: flex; flex-direction: column; gap: 16px;">
                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 12px 16px;">
                    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">対象顧客</div>
                    <div id="unmaskGuestName" style="font-size: 16px; font-weight: 800; color: #183B50; margin-top: 2px;"></div>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #183B50; margin-bottom: 6px;">
                        閲覧理由・正当事由 (Business / Legal Justification) <span style="color: #EF4444;">*</span>
                    </label>
                    <textarea id="unmaskJustification" required rows="3" placeholder="例: チェックイン時の旅館業法に基づく本人確認 / 緊急の予約確認電話" style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;"></textarea>
                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">5文字以上入力してください。不変の監査ログに記録されます。</div>
                </div>

                <div id="unmaskAlert" style="display: none; padding: 10px 14px; border-radius: 8px; font-size: 12px;"></div>
            </div>

            <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeUnmaskModal()">キャンセル</button>
                <button type="submit" class="btn btn-primary" id="btnUnmaskSubmit" style="background: #287F79;">
                    <i class="fa-solid fa-lock-open"></i> 解除して表示する
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openUnmaskModal(guestId, guestName) {
        $('#unmaskGuestId').val(guestId);
        $('#unmaskGuestName').text(guestName + ' (ID #' + guestId + ')');
        $('#unmaskJustification').val('');
        $('#unmaskAlert').hide();
        $('#unmaskModal').addClass('active');
    }

    function closeUnmaskModal() {
        $('#unmaskModal').removeClass('active');
    }

    function submitUnmask(e) {
        e.preventDefault();
        const guestId = $('#unmaskGuestId').val();
        const justification = $('#unmaskJustification').val();
        const btn = $('#btnUnmaskSubmit');
        const alertBox = $('#unmaskAlert');

        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> 監査記録中...');

        $.ajax({
            url: '/privacy-centre/unmask/' + guestId,
            method: 'POST',
            data: {
                justification: justification,
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-lock-open"></i> 解除して表示する');
                if (res.success) {
                    // Update table row in real time
                    $('#emailVal' + guestId).text(res.unmasked_email).css({'background': '#D1FAE5', 'color': '#065F46', 'font-weight': '700'});
                    $('#phoneVal' + guestId).text(res.unmasked_phone).css({'background': '#D1FAE5', 'color': '#065F46', 'font-weight': '700'});

                    alertBox.attr('style', 'display: block; background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0;')
                        .html('<i class="fa-solid fa-circle-check"></i> ' + res.message);

                    setTimeout(function() {
                        closeUnmaskModal();
                    }, 1200);
                } else {
                    alertBox.attr('style', 'display: block; background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA;')
                        .text(res.message);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-lock-open"></i> 解除して表示する');
                alertBox.attr('style', 'display: block; background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA;')
                    .text(xhr.responseJSON?.message || 'エラーが発生しました。正当理由をご確認ください。');
            }
        });
    }
</script>
@endpush
@endsection
