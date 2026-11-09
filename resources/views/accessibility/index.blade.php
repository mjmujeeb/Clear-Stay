@extends('layouts.app')

@section('title', 'ユニバーサル・アクセシビリティ (Accessibility Centre) - ClearStay 4.0')
@section('header_title', 'ユニバーサル・アクセシビリティセンター (Accessibility Centre)')
@section('header_subtitle', 'WCAG 2.2 AA準拠・コントラストテーマ・フォント拡大リフロー (100%〜300%)・キーボード完全操作')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Form: Configure User Accessibility Preferences -->
    <form action="{{ route('accessibility.update') }}" method="POST">
        @csrf

        <div class="card" style="border: 2px solid #287F79;">
            <div class="card-header" style="background: #E6F3F2; justify-content: space-between;">
                <div>
                    <h3 style="font-size: 18px; font-weight: 800; color: #183B50;">
                        <i class="fa-solid fa-universal-access" style="color: #287F79;"></i> ディスプレイ・視覚アクセシビリティ設定
                    </h3>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                        設定は即座にアプリケーション全体に永続適用されます（セッションおよびユーザープロファイル保存）
                    </p>
                </div>
                <button type="submit" class="btn btn-primary" style="background: #287F79; font-weight: 700;">
                    <i class="fa-solid fa-check"></i> 設定を保存して適用する
                </button>
            </div>

            <div class="card-body" style="padding: 24px; display: flex; flex-direction: column; gap: 24px;">

                <!-- Control 1: High-Contrast Profiles -->
                <div>
                    <label style="display: block; font-size: 15px; font-weight: 800; color: #183B50; margin-bottom: 12px;">
                        1. コントラスト・テーマプロファイル (Contrast Profile)
                    </label>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px;">
                        @foreach($contrastThemes as $theme)
                            <label style="cursor: pointer; border: 2px solid {{ $currentTheme === $theme['id'] ? '#287F79' : 'var(--border)' }}; border-radius: 12px; padding: 16px; background: {{ $currentTheme === $theme['id'] ? '#F0FDF4' : '#ffffff' }}; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong style="font-size: 14px; color: #183B50;">{{ $theme['name'] }}</strong>
                                    <input type="radio" name="contrast_theme" value="{{ $theme['id'] }}" {{ $currentTheme === $theme['id'] ? 'checked' : '' }} onchange="previewThemeChange('{{ $theme['id'] }}')">
                                </div>
                                <div style="font-size: 12px; color: var(--text-muted); line-height: 1.4;">{{ $theme['description'] }}</div>
                                <div style="margin-top: auto; display: flex; gap: 6px; align-items: center; padding-top: 8px;">
                                    <div style="width: 20px; height: 20px; border-radius: 4px; background: {{ $theme['preview_bg'] }}; border: 1px solid #CBD5E1;"></div>
                                    <div style="width: 20px; height: 20px; border-radius: 4px; background: {{ $theme['preview_text'] }};"></div>
                                    <div style="width: 20px; height: 20px; border-radius: 4px; background: {{ $theme['preview_accent'] }};"></div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Control 2: Font Scaling (100% to 300%) -->
                <div>
                    <label style="display: block; font-size: 15px; font-weight: 800; color: #183B50; margin-bottom: 8px;">
                        2. フォント拡大率 (Fluid Font Scaling & Reflow)
                    </label>
                    <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">
                        WCAG 1.4.10 に準拠。最大300%まで拡大しても、ボタンや表のテキストが欠落せず横スクロールなしで自動リフローします。
                    </p>

                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        @foreach([100, 125, 150, 200, 300] as $scale)
                            <label style="flex: 1; min-width: 90px; text-align: center; border: 2px solid {{ $currentFontScale == $scale ? '#287F79' : 'var(--border)' }}; border-radius: 10px; padding: 12px; background: {{ $currentFontScale == $scale ? '#F0FDF4' : '#ffffff' }}; cursor: pointer;">
                                <input type="radio" name="font_scale" value="{{ $scale }}" {{ $currentFontScale == $scale ? 'checked' : '' }} style="display: none;">
                                <div style="font-size: 18px; font-weight: 800; color: #183B50;">{{ $scale }}%</div>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                    @if($scale == 100) 標準 @elseif($scale == 150) 中拡大 @elseif($scale == 300) 最大リフロー @else 拡大 @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Control 3: Reduced Motion -->
                <div style="padding-top: 12px; border-top: 1px solid var(--border);">
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                        <input type="checkbox" name="reduced_motion" value="1" {{ $currentReducedMotion ? 'checked' : '' }} style="width: 20px; height: 20px;">
                        <div>
                            <div style="font-size: 14px; font-weight: 700; color: #183B50;">
                                アニメーションおよび視覚エフェクトを無効化 (prefers-reduced-motion)
                            </div>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                前庭感覚障害や画面酔いを防止するため、すべてのスライド・フェード・ポップアップアニメーションを即時停止します。
                            </div>
                        </div>
                    </label>
                </div>

            </div>
        </div>
    </form>

    <!-- WCAG 2.2 AA Compliance Matrix Card -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-clipboard-check text-primary"></i> WCAG 2.2 レベルAA 適合状況監査</h3>
            <span style="font-size: 12px; color: var(--text-muted);">Web Content Accessibility Guidelines (WCAG) 2.2 検証レポート</span>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>原則 (Principle)</th>
                        <th>達成基準 (Success Criterion)</th>
                        <th>適合ステータス</th>
                        <th>ClearStay での技術的実装</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($wcagChecklist as $item)
                        <tr>
                            <td><strong style="color: #183B50;">{{ $item['principle'] }}</strong></td>
                            <td><strong>{{ $item['criterion'] }}</strong></td>
                            <td>
                                <span class="badge" style="background: #D1FAE5; color: #065F46; font-weight: 800;">
                                    <i class="fa-solid fa-circle-check"></i> {{ $item['status'] }}
                                </span>
                            </td>
                            <td style="font-size: 12px; line-height: 1.45;">{{ $item['description'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Keyboard Navigation & Shortcuts Guide -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-keyboard text-primary"></i> キーボード操作・支援技術ガイド</h3>
            <span style="font-size: 12px; color: var(--text-muted);">マウスを使用しない完全なキーボード操作のサポート</span>
        </div>
        <div class="card-body" style="padding: 24px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 10px; padding: 16px;">
                    <div style="font-size: 13px; font-weight: 700; color: #183B50; margin-bottom: 4px;">
                        <kbd style="background: #e2e8f0; padding: 2px 8px; border-radius: 4px;">Tab</kbd> / <kbd style="background: #e2e8f0; padding: 2px 8px; border-radius: 4px;">Shift + Tab</kbd>
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted);">
                        すべてのボタン、フォーム入力欄、リンク間を論理的なフォーカス順序で移動します。
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 10px; padding: 16px;">
                    <div style="font-size: 13px; font-weight: 700; color: #183B50; margin-bottom: 4px;">
                        <kbd style="background: #e2e8f0; padding: 2px 8px; border-radius: 4px;">Ctrl + K</kbd> / <kbd style="background: #e2e8f0; padding: 2px 8px; border-radius: 4px;">Cmd + K</kbd>
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted);">
                        どこからでもグローバル検索コマンドパレットを開き、予約・部屋・設定へ即座にジャンプできます。
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 10px; padding: 16px;">
                    <div style="font-size: 13px; font-weight: 700; color: #183B50; margin-bottom: 4px;">
                        <kbd style="background: #e2e8f0; padding: 2px 8px; border-radius: 4px;">Esc</kbd> (エスケープ)
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted);">
                        開いているモーダルダイアログ、ツールチップ、検索窓を安全に閉じます。
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 10px; padding: 16px;">
                    <div style="font-size: 13px; font-weight: 700; color: #183B50; margin-bottom: 4px;">
                        スキップリンク (<code style="color: #287F79;">#mainContent</code>)
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted);">
                        ページ最上部でTabキーを押すと現れるスキップリンクにより、ナビゲーションを飛ばして本文へ直接フォーカスできます。
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
