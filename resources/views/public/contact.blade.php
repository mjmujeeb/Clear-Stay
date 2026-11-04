@extends('layouts.public')

@section('meta_title', 'Contact & Property Support - ClearStay')
@section('meta_description', 'Contact ClearStay hospitality operations team and property managers across Tokyo, Kyoto, and Osaka.')

@section('content')
<section style="background: #f8fafc; padding: 60px 0 40px; border-bottom: 1px solid var(--border);">
    <div class="container" style="text-align: center; max-width: 700px;">
        <h1 style="font-size: 36px; font-weight: 800; color: var(--text-dark);">Contact & Support</h1>
        <p style="font-size: 15px; color: var(--text-muted); margin-top: 8px;">Front desk concierge and property management contact points</p>
    </div>
</section>

<div class="container" style="padding: 60px 24px; max-width: 900px;">
    @if(session('success'))
        <div style="background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; border-radius: 10px; padding: 14px 18px; margin-bottom: 24px; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; border-radius: 10px; padding: 14px 18px; margin-bottom: 24px; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
        <div>
            <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 16px;">Property Inquiries</h3>
            <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 24px;">
                For guest reservations, corporate group bookings, or property inquiries, please reach out directly to the respective hotel front desk.
            </p>

            @foreach($hotels as $h)
                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 10px; padding: 16px; margin-bottom: 16px;">
                    <h4 style="font-size: 15px; font-weight: 700; color: var(--primary);">{{ $h->name }}</h4>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">{{ $h->address }}, {{ $h->city }}</p>
                    <p style="font-size: 12px; margin-top: 6px;"><i class="fa-solid fa-phone text-primary"></i> {{ $h->phone ?? '+81-3-1234-5678' }}</p>
                    <p style="font-size: 12px;"><i class="fa-solid fa-envelope text-primary"></i> {{ $h->email ?? 'info@clearstay.jp' }}</p>
                </div>
            @endforeach
        </div>

        <div style="background: white; border: 1px solid var(--border); border-radius: 16px; padding: 28px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <h3 style="font-size: 18px; font-weight: 800;">Send Message to Desk</h3>
                <span style="font-size: 11px; background: #e0f2fe; color: #0284c7; padding: 2px 8px; border-radius: 4px; font-weight: 700;">
                    <i class="fa-solid fa-shield-halved"></i> Bot Protected
                </span>
            </div>
            
            <form action="{{ route('public.contact.post') }}" method="POST">
                @csrf

                <!-- ClearShield Bot Defense & Human Verification -->
                @include('components.bot-verification', ['formId' => 'contact', 'withChallenge' => true])

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Your Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Select Hotel</label>
                    <select name="hotel_id" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                        @foreach($hotels as $h)
                            <option value="{{ $h->id }}" {{ old('hotel_id') == $h->id ? 'selected' : '' }}>{{ $h->name }} ({{ $h->city }})</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Message</label>
                    <textarea name="message" rows="4" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">{{ old('message') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fa-solid fa-paper-plane"></i> Send Inquiry to Front Desk
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
