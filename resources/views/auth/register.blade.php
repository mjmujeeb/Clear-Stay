@extends('layouts.public')

@section('meta_title', 'Register Staff - ClearStay')

@section('content')
<div style="min-height: 85vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; background: #f8fafc;">
    <div style="width: 100%; max-width: 480px; background: white; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); padding: 36px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <h2 style="font-size: 22px; font-weight: 800; color: var(--text-dark);">{{ __('clearstay.auth.register_title') }}</h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Join hotel operations team</p>
        </div>

        @if($errors->any())
            <div style="background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #991b1b;">
                @foreach($errors->all() as $error)
                    <div><i class="fa-solid fa-circle-exclamation"></i> {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('register.post') }}" method="POST">
            @csrf

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Staff Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">{{ __('clearstay.auth.email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">{{ __('clearstay.auth.role') }}</label>
                    <select name="role" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                        <option value="frontdesk">Front Desk</option>
                        <option value="manager">Hotel Manager</option>
                        <option value="housekeeping">Housekeeping</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Default Language</label>
                    <select name="locale" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                        <option value="en">English (EN)</option>
                        <option value="ja">日本語 (JA)</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Assigned Property (Multi-Tenant)</label>
                <select name="hotel_id" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                    @foreach($hotels as $h)
                        <option value="{{ $h->id }}">{{ $h->name }} - {{ $h->city }} ({{ $h->code }})</option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 24px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">{{ __('clearstay.auth.password') }}</label>
                    <input type="password" name="password" required minlength="8"
                        style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">{{ __('clearstay.auth.password_confirmation') }}</label>
                    <input type="password" name="password_confirmation" required minlength="8"
                        style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                </div>
            </div>

            <button type="submit" style="width: 100%; background: var(--primary); color: white; padding: 12px; border-radius: 8px; font-size: 14px; font-weight: 700; border: none; cursor: pointer;">
                <i class="fa-solid fa-user-plus"></i> Complete Staff Registration
            </button>
        </form>

        <div style="margin-top: 20px; text-align: center; font-size: 13px; color: var(--text-muted);">
            Already registered? <a href="{{ route('login') }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">Sign in here &rarr;</a>
        </div>
    </div>
</div>
@endsection
