@extends('layouts.app')

@section('title', __('clearstay.reservations.title'))
@section('header_title', __('clearstay.reservations.title'))
@section('header_subtitle', 'Multi-room bookings, serialized row-locked date modifications, and guest check-ins')

@section('content')
<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 14px;">
        <form action="{{ route('reservations.index') }}" method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; flex: 1;">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search Code, Guest Name, Room..." 
                style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; min-width: 240px;">

            <select name="status" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
                <option value="">{{ __('clearstay.board.all_statuses') }}</option>
                <option value="confirmed" {{ $status == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="checked_in" {{ $status == 'checked_in' ? 'selected' : '' }}>Checked In</option>
                <option value="checked_out" {{ $status == 'checked_out' ? 'selected' : '' }}>Checked Out</option>
                <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>

            <input type="date" name="date" value="{{ $date }}" style="padding: 7px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">

            <button type="submit" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-magnifying-glass"></i> Filter
            </button>
            @if($search || $status || $date)
                <a href="{{ route('reservations.index') }}" class="btn btn-secondary btn-sm" title="Clear Filters">Clear</a>
            @endif
        </form>

        <a href="{{ route('reservations.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> {{ __('clearstay.reservations.new_reservation') }}
        </a>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Guest Information</th>
                    <th>Room</th>
                    <th>Dates (Stay Duration)</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reservations as $res)
                    <tr>
                        <td>
                            <a href="javascript:void(0)" onclick="openReservationModal({{ $res->id }})" style="color: var(--primary); font-weight: 700; text-decoration: none;">
                                <i class="fa-solid fa-eye"></i> {{ $res->confirmation_code }}
                            </a>
                        </td>
                        <td>
                            <strong>{{ $res->guest->full_name }}</strong>
                            <div style="font-size: 11px; color: var(--text-muted);">
                                {{ $res->guest->email }} | {{ $res->guest->phone ?? 'N/A' }}
                            </div>
                        </td>
                        <td>
                            <strong>Room {{ $res->room->room_number }}</strong>
                            <div style="font-size: 11px; color: var(--text-muted);">{{ $res->room->roomType->name }}</div>
                        </td>
                        <td>
                            <div>{{ $res->check_in_date->format('Y-m-d') }} &rarr; {{ $res->check_out_date->format('Y-m-d') }}</div>
                            <div style="font-size: 11px; color: var(--text-muted);">{{ $res->nights }} night(s), {{ $res->adults }} adult(s)</div>
                        </td>
                        <td>
                            <strong>¥{{ number_format($res->total_price) }}</strong>
                            <div><span class="badge badge-inspecting" style="font-size: 9px;">{{ ucfirst($res->payment_status) }}</span></div>
                        </td>
                        <td>
                            <span class="badge badge-{{ $res->status }}">{{ strtoupper(str_replace('_', ' ', $res->status)) }}</span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 6px; align-items: center;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="openReservationModal({{ $res->id }})" title="View Details & Change Dates">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                @if($res->status === 'confirmed')
                                    <form action="{{ route('reservations.check_in', $res->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" title="Check In Guest">
                                            <i class="fa-solid fa-key"></i> In
                                        </button>
                                    </form>
                                @elseif($res->status === 'checked_in')
                                    <form action="{{ route('reservations.check_out', $res->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm" title="Check Out Guest">
                                            <i class="fa-solid fa-receipt"></i> Out
                                        </button>
                                    </form>
                                @endif

                                @if(in_array($res->status, ['confirmed', 'checked_in']))
                                    <form action="{{ route('reservations.cancel', $res->id) }}" method="POST" onsubmit="return confirm('Cancel reservation {{ $res->confirmation_code }}?');">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm" title="Cancel Booking">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            <i class="fa-solid fa-calendar-xmark" style="font-size: 28px; opacity: 0.4;"></i>
                            <div style="margin-top: 8px;">No reservations found matching your criteria.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($reservations->hasPages())
        <div style="padding: 16px 24px; border-top: 1px solid var(--border);">
            {{ $reservations->links() }}
        </div>
    @endif
</div>
@endsection
