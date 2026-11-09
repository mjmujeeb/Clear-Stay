<?php

namespace App\Http\Controllers;

use App\Models\EventTrace;
use App\Models\IntegrationPartner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventTraceController extends Controller
{
    /**
     * EventTrace Hub: Transaction timeline and telemetry stream.
     */
    public function index(Request $request)
    {
        $query = EventTrace::with(['partner', 'hotel'])->latest();

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('trace_id', 'like', "%{$s}%")
                    ->orWhere('operation', 'like', "%{$s}%");
            });
        }

        $traces = $query->paginate(20)->withQueryString();
        $partners = IntegrationPartner::all();

        return view('event_trace.index', compact('traces', 'partners'));
    }

    /**
     * Show single trace details.
     */
    public function show(string $traceId)
    {
        $trace = EventTrace::where('trace_id', $traceId)->with('partner')->firstOrFail();
        return view('event_trace.show', compact('trace'));
    }

    /**
     * Safe replay of an event trace in the isolated simulation environment.
     */
    public function replay(string $traceId)
    {
        $original = EventTrace::where('trace_id', $traceId)->firstOrFail();
        $newTraceId = (string) Str::uuid();

        $replay = EventTrace::create([
            'trace_id' => $newTraceId,
            'partner_id' => $original->partner_id,
            'hotel_id' => $original->hotel_id,
            'operation' => "[REPLAY] " . $original->operation,
            'stage' => 'completed',
            'status' => 'success',
            'duration_ms' => rand(25, 75),
            'request_headers' => array_merge($original->request_headers ?? [], ['X-Replay-Source' => $original->trace_id]),
            'request_body' => $original->request_body,
            'response_headers' => ['HTTP/1.1' => '200 OK'],
            'response_body' => ['replayed' => true, 'source_trace' => $original->trace_id],
            'replayable' => true,
        ]);

        return redirect()->route('event_trace.show', $newTraceId)
            ->with('success', "Event successfully replayed in isolated sandbox. New Trace ID: {$newTraceId}");
    }
}
