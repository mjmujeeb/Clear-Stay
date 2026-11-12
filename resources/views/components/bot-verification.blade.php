@php
    $botService = app(\App\Services\BotDefenseService::class);
    $timeLockToken = $botService->generateTimeLockToken($formId ?? 'general');
    $challenge = $botService->generateChallenge();
    $showChallenge = $withChallenge ?? true;
@endphp

<!-- Invisible Decoy Honeypot Trap (Automated bots crawling form inputs fill this in) -->
<div style="position: absolute; left: -9999px; top: -9999px; opacity: 0; pointer-events: none; width: 0; height: 0; overflow: hidden;" aria-hidden="true">
    <label for="_cs_hp_token">Leave blank</label>
    <input type="text" id="_cs_hp_token" name="_cs_hp_token" tabindex="-1" autocomplete="off" value="">
    <label for="_cs_hp_website">Do not fill</label>
    <input type="text" id="_cs_hp_website" name="_cs_hp_website" tabindex="-1" autocomplete="off" value="">
</div>

<!-- Cryptographic Time-Lock Token (Detects sub-second automated programmatic form submits) -->
<input type="hidden" name="_cs_timelock" value="{{ $timeLockToken }}">

@if($showChallenge)
<input type="hidden" name="_cs_challenge_token" value="{{ $challenge['token'] }}">

<div class="clearshield-verification-box" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 14px; margin-bottom: 18px; display: flex; flex-direction: column; gap: 8px;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #0284c7;">
            <i class="fa-solid fa-shield-halved"></i>
            <span>ClearShield™ {{ __('clearstay.security.bot_verification_title') }}</span>
        </div>
        <span style="font-size: 10px; color: #64748b; background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-weight: 600;">
            Anti-Bot Guard
        </span>
    </div>

    <div style="display: flex; align-items: center; gap: 10px; font-size: 13px; flex-wrap: wrap;">
        <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-weight: 700; color: #0f172a; letter-spacing: 0.05em; font-family: monospace; white-space: nowrap;">
            {{ $challenge['question'] }}
        </div>
        <input type="number" 
               name="_cs_challenge_answer" 
               placeholder="Result" 
               required 
               autocomplete="off"
               style="width: 80px; padding: 6px 10px; border: 1px solid #94a3b8; border-radius: 6px; font-size: 14px; font-weight: 700; text-align: center; outline: none;">
        <span style="font-size: 11px; color: #64748b; flex: 1; min-width: 120px;">{{ __('clearstay.security.solve_to_verify') }}</span>
    </div>
</div>
@endif
