@extends('layouts.app')

@section('title', app()->getLocale() === 'ja' ? '接続準備センター - ClearStay 4.1' : 'Partner Readiness Centre - ClearStay 4.1')
@section('header_title', app()->getLocale() === 'ja' ? 'パートナー接続準備 & 適合性認証センター' : 'Partner Readiness & Certification Centre')
@section('header_subtitle', app()->getLocale() === 'ja' ? '外部連携パートナーの適合性評価、レイテンシ検証、契約適合性スコアリングおよび公式認証レポート発行。' : 'Comprehensive integration certification reports, compliance scoring, and formal export artifacts.')

@section('content')
<div class="readiness-container">
    @if(session('success'))
        <div style="background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; color: #065F46; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-circle-check" style="font-size: 16px; color: #10B981;"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div style="background: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; color: #991B1B; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-circle-exclamation" style="font-size: 16px; color: #EF4444;"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--text-main); margin: 0;">
                {{ app()->getLocale() === 'ja' ? 'パートナー適合性評価 & 認証管理' : 'Partner Readiness & Compliance Reports' }}
            </h2>
            <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 0;">
                {{ app()->getLocale() === 'ja' ? 'TEMAIRAZU互換の自動コンプライアンス検証。ゼロ・データ不整合を保証するリアルタイム品質評価。' : 'TEMAIRAZU-style automated compliance certificates ensuring zero data discrepancies across partner hotels.' }}
            </p>
        </div>
    </div>

    <!-- Section 1: Partner Registry & Current Status Card -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header" style="background: white; border-bottom: 1px solid var(--border); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--text-main);">
                    <i class="fa-solid fa-handshake" style="color: #287F79;"></i>
                    {{ app()->getLocale() === 'ja' ? '連携パートナー台帳 & 最新認証状況' : 'Integration Partner Registry & Latest Assessment' }}
                </h3>
                <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                    {{ app()->getLocale() === 'ja' ? '登録済みパートナーの直近評価結果とオンデマンド認証実行' : 'Registered distribution partners, latest audit compliance ratings, and on-demand certification triggers' }}
                </p>
            </div>
            <span class="badge" style="background: #F1F5F9; color: #475569; font-weight: 700; font-size: 12px; padding: 4px 10px;">
                {{ $partners->count() }} {{ app()->getLocale() === 'ja' ? '社登録' : 'Partners' }}
            </span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div style="overflow-x: auto;">
                <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                            <th style="padding: 12px 20px;">{{ app()->getLocale() === 'ja' ? 'パートナー名' : 'Partner & Category' }}</th>
                            <th style="padding: 12px 14px;">{{ app()->getLocale() === 'ja' ? 'API仕様 / 認証' : 'API Protocol' }}</th>
                            <th style="padding: 12px 14px;">{{ app()->getLocale() === 'ja' ? '契約数' : 'Contracts' }}</th>
                            <th style="padding: 12px 14px;">{{ app()->getLocale() === 'ja' ? '最新適合性評価' : 'Latest Readiness Report' }}</th>
                            <th style="padding: 12px 20px; text-align: right;">{{ app()->getLocale() === 'ja' ? '操作' : 'Action' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($partners as $partner)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 14px 20px;">
                                <div style="font-weight: 700; color: var(--text-main); font-size: 14px;">
                                    {{ $partner->name }}
                                </div>
                                <div style="font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; margin-top: 2px;">
                                    <span>{{ $partner->company ?? 'Independent Partner' }}</span>
                                    <span>•</span>
                                    <span class="badge" style="background: #E0F2FE; color: #0369A1; font-size: 10px; font-weight: 700; padding: 2px 6px;">
                                        {{ strtoupper($partner->category ?? 'OTA') }}
                                    </span>
                                </div>
                            </td>
                            <td style="padding: 14px 14px; font-size: 12px; color: var(--text-muted);">
                                <div><code style="background: #F1F5F9; padding: 2px 6px; border-radius: 4px;">{{ $partner->api_version ?? 'v1.0' }}</code></div>
                                <div style="margin-top: 2px;">{{ strtoupper($partner->auth_type ?? 'Bearer') }}</div>
                            </td>
                            <td style="padding: 14px 14px;">
                                <span class="badge" style="background: #F1F5F9; color: #475569; font-weight: 600;">
                                    {{ $partner->contracts->count() }} {{ app()->getLocale() === 'ja' ? '件' : 'active' }}
                                </span>
                            </td>
                            <td style="padding: 14px 14px;">
                                @if($partner->latestReadinessReport)
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="flex: 1; max-width: 70px; height: 7px; background: #E2E8F0; border-radius: 4px; overflow: hidden;">
                                            <div style="width: {{ $partner->latestReadinessReport->score_percentage }}%; height: 100%; background: #10B981;"></div>
                                        </div>
                                        <span style="font-weight: 800; color: #065F46; font-size: 13px;">
                                            {{ $partner->latestReadinessReport->score_percentage }}%
                                        </span>
                                        <span class="badge" style="background: {{ $partner->latestReadinessReport->status_color['bg'] }}; color: {{ $partner->latestReadinessReport->status_color['text'] }}; font-weight: 700; font-size: 11px; border: 1px solid {{ $partner->latestReadinessReport->status_color['border'] }}; padding: 2px 8px;">
                                            {{ $partner->latestReadinessReport->status_label }}
                                        </span>
                                        <a href="{{ route('readiness.show', $partner->latestReadinessReport) }}" style="font-size: 11px; color: #0284C7; text-decoration: none; margin-left: 4px;" title="View Report">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    </div>
                                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px;">
                                        {{ $partner->latestReadinessReport->created_at->format('Y-m-d H:i') }} ({{ $partner->latestReadinessReport->report_code }})
                                    </div>
                                @else
                                    <span class="badge" style="background: #F1F5F9; color: #64748B; font-weight: 600; padding: 4px 10px; border: 1px dashed #CBD5E1;">
                                        <i class="fa-solid fa-clock-rotate-left"></i> {{ app()->getLocale() === 'ja' ? '未評価 (アセスメント未実施)' : 'No readiness assessment has been generated yet' }}
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 14px 20px; text-align: right;">
                                <form action="{{ route('readiness.generate', $partner) }}" method="POST" style="display: inline-block;">
                                    @csrf
                                    <input type="hidden" name="idempotency_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                                    <button type="submit" class="btn btn-sm" style="background: #287F79; color: white; border: none; font-weight: 700; padding: 6px 12px; font-size: 12px; border-radius: 6px; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#1e635e'" onmouseout="this.style.background='#287F79'">
                                        <i class="fa-solid fa-play"></i> {{ app()->getLocale() === 'ja' ? '適合性監査を実行' : 'Generate Readiness Report' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="padding: 24px; text-align: center; color: var(--text-muted);">
                                {{ app()->getLocale() === 'ja' ? '登録されている外部連携パートナーはありません。' : 'No integration partners registered in the system.' }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 2: Historical Certification Records Card -->
    <div class="card">
        <div class="card-header" style="background: white; border-bottom: 1px solid var(--border); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--text-main);">
                    <i class="fa-solid fa-certificate" style="color: #F59E0B;"></i>
                    {{ app()->getLocale() === 'ja' ? '発行済み適合性証明書履歴' : 'Integration Certification Records' }}
                </h3>
                <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;">
                    {{ app()->getLocale() === 'ja' ? 'レイテンシ、契約検証、再試行回復力、冪等性ベンチマークに基づく監査ログ' : 'Evaluated against latency, contract validity, retry resilience, and idempotency benchmarks' }}
                </p>
            </div>
        </div>
        <div class="card-body" style="padding: 0;">
            <div style="overflow-x: auto;">
                <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                            <th style="padding: 12px 20px;">{{ app()->getLocale() === 'ja' ? '証明書コード' : 'Report Code' }}</th>
                            <th style="padding: 12px 14px;">{{ app()->getLocale() === 'ja' ? '対象パートナー' : 'Partner Name' }}</th>
                            <th style="padding: 12px 14px;">{{ app()->getLocale() === 'ja' ? '適合スコア' : 'Readiness Score' }}</th>
                            <th style="padding: 12px 14px;">{{ app()->getLocale() === 'ja' ? '判定結果' : 'Certification Status' }}</th>
                            <th style="padding: 12px 14px;">{{ app()->getLocale() === 'ja' ? '発行日時' : 'Evaluation Date' }}</th>
                            <th style="padding: 12px 20px; text-align: right;">{{ app()->getLocale() === 'ja' ? '詳細確認' : 'Action' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 14px 20px; font-family: monospace; font-size: 12px; font-weight: 700;">
                                <a href="{{ route('readiness.show', $report) }}" style="color: #0284C7; text-decoration: none;">
                                    {{ $report->report_code }}
                                </a>
                            </td>
                            <td style="padding: 14px 14px; font-weight: 700; color: var(--text-main);">
                                {{ $report->partner?->name ?? 'Integration Partner' }}
                            </td>
                            <td style="padding: 14px 14px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="flex: 1; max-width: 80px; height: 8px; background: #E2E8F0; border-radius: 4px; overflow: hidden;">
                                        <div style="width: {{ $report->score_percentage }}%; height: 100%; background: #10B981;"></div>
                                    </div>
                                    <span style="font-weight: 800; color: #065F46;">{{ $report->score_percentage }}%</span>
                                </div>
                            </td>
                            <td style="padding: 14px 14px;">
                                <span class="badge" style="background: {{ $report->status_color['bg'] }}; color: {{ $report->status_color['text'] }}; font-weight: 700; border: 1px solid {{ $report->status_color['border'] }};">
                                    {{ $report->status_label }}
                                </span>
                            </td>
                            <td style="padding: 14px 14px; color: var(--text-muted); font-size: 12px;">
                                {{ $report->created_at->format('Y-m-d H:i') }}
                            </td>
                            <td style="padding: 14px 20px; text-align: right;">
                                <a href="{{ route('readiness.show', $report) }}" class="btn btn-sm btn-secondary" style="font-size: 12px; padding: 4px 10px;">
                                    {{ app()->getLocale() === 'ja' ? '証明書を表示' : 'View Certificate' }} <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="padding: 24px; text-align: center; color: var(--text-muted);">
                                {{ app()->getLocale() === 'ja' ? '適合性証明書はまだ発行されていません。上部のパートナー台帳から「適合性監査を実行」をクリックしてください。' : 'No readiness reports generated yet. Click "Generate Readiness Report" on any partner above to evaluate.' }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($reports->hasPages())
        <div style="padding: 16px 20px; border-top: 1px solid var(--border);">
            {{ $reports->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
