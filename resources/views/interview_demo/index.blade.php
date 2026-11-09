@extends('layouts.app')

@section('title', __('clearstay.demo_hub.page_title'))
@section('header_title', __('clearstay.demo_hub.header_title'))
@section('header_subtitle', __('clearstay.demo_hub.header_subtitle'))

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Top Hero Banner -->
    <div class="card" style="margin-bottom: 0; background: linear-gradient(135deg, #0F1D2F, #183B50); color: #ffffff; border: 1px solid #287F79;">
        <div class="card-body" style="padding: 24px 28px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <span class="badge" style="background: rgba(45, 212, 191, 0.2); color: #2DD4BF; font-weight: 800; letter-spacing: 0.05em;">
                        {{ __('clearstay.demo_hub.badge') }}
                    </span>
                    <h2 style="font-size: 24px; font-weight: 800; margin-top: 8px;">
                        {{ __('clearstay.demo_hub.hero_title') }}
                    </h2>
                    <p style="font-size: 14px; color: #94A3B8; max-width: 800px; margin-top: 4px; line-height: 1.5;">
                        {{ __('clearstay.demo_hub.hero_desc') }}
                    </p>
                </div>

                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="{{ route('experience_lab.index') }}" class="btn btn-sm btn-secondary" style="font-weight: 700;">
                        <i class="fa-solid fa-microscope" style="color: #2DD4BF;"></i> {{ __('clearstay.demo_hub.btn_lab') }}
                    </a>
                    <a href="{{ route('easy_mode.index') }}" class="btn btn-sm" style="background: #E8A64A; color: #183B50; font-weight: 800;">
                        <i class="fa-solid fa-person-shelter"></i> {{ __('clearstay.demo_hub.btn_easy') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 10 Scenarios Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px;">
        @foreach($scenarios as $sc)
            @php
                $sId = $sc['id'];
                $sTitle = __("clearstay.demo_hub.scenarios.{$sId}.title");
                $sCat = __("clearstay.demo_hub.scenarios.{$sId}.category");
                $sDesc = __("clearstay.demo_hub.scenarios.{$sId}.description");
                $sBadge = __("clearstay.demo_hub.scenarios.{$sId}.badge");
            @endphp
            <div class="card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid #287F79;">
                <div class="card-body" style="padding: 22px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                        <span class="badge" style="background: #E6F3F2; color: #287F79; font-weight: 800; font-size: 11px;">
                            {{ $sBadge }}
                        </span>
                        <div style="width: 36px; height: 36px; border-radius: 8px; background: #f1f5f9; color: #183B50; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                            <i class="fa-solid {{ $sc['icon'] }}"></i>
                        </div>
                    </div>

                    <h3 style="font-size: 16px; font-weight: 800; color: #183B50; margin-bottom: 6px;">
                        {{ $sTitle }}
                    </h3>
                    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">
                        {{ $sCat }}
                    </div>
                    <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 18px;">
                        {{ $sDesc }}
                    </p>
                </div>

                <div style="padding: 14px 22px; background: #f8fafc; border-top: 1px solid var(--border);">
                    <button type="button" class="btn btn-sm btn-primary" onclick="executeInterviewScenario({{ $sId }})" style="width: 100%; justify-content: center; background: #287F79; font-weight: 700;">
                        <i class="fa-solid fa-play"></i> {{ __('clearstay.demo_hub.run_scenario') }}
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Live Execution Telemetry Panel -->
    <div class="card" style="border: 1px solid #1E293B; background: #0B1320; color: #F8FAFC;">
        <div class="card-header" style="background: #0F1D2F; border-bottom: 1px solid #1E293B; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="color: #2DD4BF; font-size: 14px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-circle-nodes"></i> {{ __('clearstay.demo_hub.telemetry_title') }}
            </h3>
            <span id="demoStatusBadge" style="font-size: 12px; color: #94A3B8;">{{ __('clearstay.demo_hub.no_run_yet') }}</span>
        </div>
        <div class="card-body" style="padding: 20px;">
            <div id="demoVisualBox" style="margin-bottom: 16px; display: none;">
                <div style="background: rgba(45, 212, 191, 0.1); border: 1px solid rgba(45, 212, 191, 0.3); border-radius: 8px; padding: 16px 20px;">
                    <div id="demoTitle" style="font-size: 16px; font-weight: 800; color: #2DD4BF; margin-bottom: 4px;"></div>
                    <div id="demoSummary" style="font-size: 13px; color: #E2E8F0; line-height: 1.5;"></div>
                </div>
            </div>

            <pre id="demoJsonOutput" style="background: #050A14; border: 1px solid #1E293B; border-radius: 8px; padding: 16px; font-family: 'Courier New', monospace; font-size: 12px; color: #38BDF8; overflow-x: auto; max-height: 380px;">// {{ __('clearstay.demo_hub.no_run_yet') }}</pre>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function executeInterviewScenario(id) {
        $('#demoStatusBadge').html('<i class="fa-solid fa-spinner fa-spin"></i> {{ __("clearstay.demo_hub.running") }}');
        $('#demoVisualBox').hide();
        $('#demoJsonOutput').text('Executing scenario #' + id + ' across ClearStay database and service layer...');

        // Smooth scroll to output
        $('html, body').animate({
            scrollTop: $('#demoJsonOutput').offset().top - 200
        }, 300);

        $.ajax({
            url: '/demo-centre/scenario/' + id,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                if (res.success) {
                    $('#demoStatusBadge').html('<span style="color: #2DD4BF;"><i class="fa-solid fa-circle-check"></i> Status: SUCCESS (Verified)</span>');
                    const d = res.data;
                    $('#demoTitle').text(d.title || ('Scenario #' + id + ' Execution Result'));
                    $('#demoSummary').text(d.summary || 'Scenario completed successfully with all backend assertions verified.');
                    $('#demoVisualBox').fadeIn();
                    $('#demoJsonOutput').text(JSON.stringify(res, null, 2));
                }
            },
            error: function(xhr) {
                $('#demoStatusBadge').html('<span style="color: #EF4444;"><i class="fa-solid fa-circle-xmark"></i> Execution Error</span>');
                $('#demoJsonOutput').text(JSON.stringify(xhr.responseJSON || xhr.responseText, null, 2));
            }
        });
    }
</script>
@endpush
@endsection
