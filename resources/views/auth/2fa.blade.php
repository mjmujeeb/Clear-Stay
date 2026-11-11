@extends('layouts.public')

@section('meta_title', 'Two-Factor Authentication - ClearStay Security')

@section('content')
<div style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; background: #f8fafc;">
    <div style="width: 100%; max-width: 440px; background: white; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); padding: 36px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 52px; height: 52px; background: linear-gradient(135deg, #0284c7, #0f172a); border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 24px; margin-bottom: 14px; box-shadow: 0 8px 16px rgba(2, 132, 199, 0.25);">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h2 style="font-size: 22px; font-weight: 800; color: var(--text-dark);">{{ __('clearstay.security.two_factor_title') }}</h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                {{ __('clearstay.security.two_factor_prompt', ['email' => $user->email]) }}
            </p>
        </div>

        @if($errors->any())
            <div style="background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #991b1b;">
                @foreach($errors->all() as $error)
                    <div><i class="fa-solid fa-circle-exclamation"></i> {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('login.2fa.post', [], false) }}" method="POST">
            @csrf

            <!-- Bot Defense / Time-Lock -->
            @include('components.bot-verification', ['formId' => '2fa', 'withChallenge' => false])

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    {{ __('clearstay.security.verification_code_or_recovery') }}
                </label>
                <input type="text" 
                       name="code" 
                       placeholder="e.g. 123456 or ABCD-EFGH" 
                       required 
                       autofocus
                       autocomplete="one-time-code"
                       style="width: 100%; padding: 14px; border: 2px solid #0284c7; border-radius: 8px; font-size: 18px; font-weight: 800; letter-spacing: 0.15em; text-align: center; outline: none;">
                <span style="display: block; font-size: 11px; color: var(--text-muted); margin-top: 6px; text-align: center;">
                    Enter your 6-digit Authenticator PIN or 8-character recovery code
                </span>
            </div>

            <button type="submit" style="width: 100%; background: var(--primary); color: white; padding: 12px; border-radius: 8px; font-size: 14px; font-weight: 700; border: none; cursor: pointer; transition: background 0.2s;">
                <i class="fa-solid fa-lock-open"></i> {{ __('clearstay.security.verify_and_continue') }}
            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border); text-align: center; font-size: 12px; color: var(--text-muted);">
            Need assistance or lost your device? Contact your hotel manager or IT security team.
            <div style="margin-top: 8px;">
                <a href="{{ route('login') }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">&larr; Return to Login</a>
            </div>
        </div>
    </div>
</div>
@endsection
