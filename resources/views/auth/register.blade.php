@extends('layouts.public')

@section('meta_title', 'Register Staff - ClearStay')

@section('content')
<div style="min-height: 85vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; background: #f8fafc;">
    <div style="width: 100%; max-width: 520px; background: white; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); padding: 36px;">
        
        @if(auth()->check())
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 10px 14px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #1e40af;">
                <div>
                    <i class="fa-solid fa-user-shield"></i>
                    Admin Console: <strong>{{ auth()->user()->name }}</strong> ({{ ucfirst(auth()->user()->role) }})
                </div>
                <a href="{{ route('staff.index') }}" style="color: #2563eb; font-weight: 700; text-decoration: none;">
                    &larr; Staff Directory
                </a>
            </div>
        @endif

        <div style="text-align: center; margin-bottom: 24px;">
            <h2 style="font-size: 22px; font-weight: 800; color: var(--text-dark);">{{ __('clearstay.auth.register_title') }}</h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Register staff and configure property tenant isolation</p>
        </div>

        @if($errors->any())
            <div style="background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #991b1b;">
                <div style="font-weight: 700; margin-bottom: 6px;"><i class="fa-solid fa-triangle-exclamation"></i> Please correct the following errors:</div>
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="staffRegisterForm" action="{{ route('register.post', [], false) }}" method="POST">
            @csrf

            <!-- ClearShield Bot Defense & Human Verification (Public only) -->
            @if(!auth()->check())
                @include('components.bot-verification', ['formId' => 'register', 'withChallenge' => true])
            @endif

            <div style="margin-bottom: 16px;">
                <label for="name" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Staff Full Name <span style="color: #ef4444;">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    placeholder="e.g. Kenji Tanaka"
                    style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                @error('name')
                    <div style="color: #ef4444; font-size: 12px; margin-top: 4px;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                @enderror
            </div>

            <div style="margin-bottom: 16px;">
                <label for="email" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">{{ __('clearstay.auth.email') }} <span style="color: #ef4444;">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                    placeholder="e.g. staff.member@clearstay.jp"
                    style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                @error('email')
                    <div style="color: #ef4444; font-size: 12px; margin-top: 4px;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                @enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                <div>
                    <label for="role" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">{{ __('clearstay.auth.role') }} <span style="color: #ef4444;">*</span></label>
                    <select id="role" name="role" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                        <option value="frontdesk" {{ old('role') === 'frontdesk' ? 'selected' : '' }}>Front Desk</option>
                        <option value="manager" {{ old('role') === 'manager' ? 'selected' : '' }}>Hotel Manager</option>
                        <option value="housekeeping" {{ old('role') === 'housekeeping' ? 'selected' : '' }}>Housekeeping</option>
                        <option value="revenue" {{ old('role') === 'revenue' ? 'selected' : '' }}>Revenue Director</option>
                        <option value="qa" {{ old('role') === 'qa' ? 'selected' : '' }}>QA / Integration</option>
                    </select>
                    @error('role')
                        <div style="color: #ef4444; font-size: 12px; margin-top: 4px;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label for="locale" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Default Language <span style="color: #ef4444;">*</span></label>
                    <select id="locale" name="locale" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                        <option value="en" {{ old('locale', app()->getLocale()) === 'en' ? 'selected' : '' }}>English (EN)</option>
                        <option value="ja" {{ old('locale', app()->getLocale()) === 'ja' ? 'selected' : '' }}>日本語 (JA)</option>
                    </select>
                    @error('locale')
                        <div style="color: #ef4444; font-size: 12px; margin-top: 4px;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Multi-Tenant Assigned Property Dropdown -->
            <div style="margin-bottom: 16px;">
                <label for="hotel_id" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                    Assigned Property (Multi-Tenant) <span style="color: #ef4444;">*</span>
                </label>

                @if($hotels->isEmpty())
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 14px; font-size: 13px; color: #991b1b; display: flex; align-items: flex-start; gap: 10px;" role="alert">
                        <i class="fa-solid fa-triangle-exclamation" style="margin-top: 2px;"></i>
                        <div>
                            <strong>No Properties Available:</strong>
                            <p style="margin: 4px 0 0; line-height: 1.4;">No hotels or properties are available. Please ask your administrator to initialize or create a property before registering staff.</p>
                        </div>
                    </div>
                    <select name="hotel_id" id="hotel_id" disabled style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #f1f5f9; cursor: not-allowed; margin-top: 8px;">
                        <option value="">-- No Properties Available --</option>
                    </select>
                @else
                    <select name="hotel_id" id="hotel_id" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                        <option value="">-- Select Assigned Property --</option>
                        @foreach($hotels as $h)
                            <option value="{{ $h->id }}" {{ old('hotel_id') == $h->id ? 'selected' : '' }}>
                                {{ $h->name }} - {{ $h->city }} ({{ $h->code }})
                            </option>
                        @endforeach
                    </select>
                @endif
                @error('hotel_id')
                    <div style="color: #ef4444; font-size: 12px; margin-top: 4px;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                @enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 24px;">
                <div>
                    <label for="password" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">{{ __('clearstay.auth.password') }} <span style="color: #ef4444;">*</span></label>
                    <input type="password" id="password" name="password" required minlength="8"
                        style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                    @error('password')
                        <div style="color: #ef4444; font-size: 12px; margin-top: 4px;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">{{ __('clearstay.auth.password_confirmation') }} <span style="color: #ef4444;">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                        style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                    @error('password_confirmation')
                        <div style="color: #ef4444; font-size: 12px; margin-top: 4px;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                    @enderror
                </div>
            </div>

            <button type="submit" id="submitBtn" {{ $hotels->isEmpty() ? 'disabled' : '' }}
                style="width: 100%; background: {{ $hotels->isEmpty() ? '#94a3b8' : 'var(--primary)' }}; color: white; padding: 12px; border-radius: 8px; font-size: 14px; font-weight: 700; border: none; cursor: {{ $hotels->isEmpty() ? 'not-allowed' : 'pointer' }}; transition: background 0.2s ease;">
                <i class="fa-solid fa-user-plus"></i> {{ $hotels->isEmpty() ? 'Registration Unavailable (No Properties)' : 'Complete Staff Registration' }}
            </button>
        </form>

        <div style="margin-top: 20px; text-align: center; font-size: 13px; color: var(--text-muted);">
            Already registered? <a href="{{ route('login') }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">Sign in here &rarr;</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('staffRegisterForm');
    const submitBtn = document.getElementById('submitBtn');
    if (form && submitBtn) {
        form.addEventListener('submit', function (e) {
            @if($hotels->isEmpty())
                e.preventDefault();
                alert("No hotels or properties are available. Please ask your administrator to initialize or create a property before registering staff.");
                return false;
            @endif

            if (submitBtn.dataset.submitting === 'true') {
                e.preventDefault();
                return false;
            }
            submitBtn.dataset.submitting = 'true';
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.7';
            submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Registering staff account...';
        });
    }
});
</script>
@endsection
