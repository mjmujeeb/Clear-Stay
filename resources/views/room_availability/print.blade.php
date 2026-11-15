<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>ClearStay - Room Availability Report ({{ $stayDate }})</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            margin: 20px;
            color: #1e293b;
            font-size: 12px;
        }
        .header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        h1 {
            font-size: 20px;
            margin: 0 0 4px;
            color: #0f172a;
        }
        .meta {
            font-size: 11px;
            color: #64748b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background: #f1f5f9;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .num {
            text-align: center;
            font-weight: 700;
        }
        .pms-col {
            background: #f0fdf4;
            color: #166534;
            font-weight: 800;
        }
        .badge {
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
            display: inline-block;
        }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #287F79; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 700;">Print Document</button>
        <button onclick="window.close()" style="padding: 8px 16px; background: #94a3b8; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 700;">Close Window</button>
    </div>

    <div class="header">
        <div>
            <h1>ClearStay Enterprise - Live Room Availability Report</h1>
            <div class="meta">Property: <strong>{{ $hotel->name ?? 'All Properties' }}</strong> | Stay Date: <strong>{{ $stayDate }}</strong></div>
        </div>
        <div style="text-align: right;">
            <div class="meta">Generated: {{ now()->format('Y-m-d H:i:s') }}</div>
            <div class="meta">Environment: <strong>SIMULATION DEMONSTRATION</strong></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Room Type</th>
                <th class="num">Physical</th>
                <th class="num">Reserved</th>
                <th class="num">Maint</th>
                <th class="num pms-col">Central PMS</th>
                @foreach($matrixData['channels'] as $ch)
                    <th class="num">{{ $ch->name }}</th>
                @endforeach
                <th class="num">Parity Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($matrixData['rows'] as $r)
            <tr>
                <td>
                    <strong>{{ $r['room_type_name'] }}</strong>
                    <div style="font-size: 10px; color: #64748b; font-family: monospace;">{{ $r['room_type_code'] }}</div>
                </td>
                <td class="num">{{ $r['pms']['total_units'] }}</td>
                <td class="num">{{ $r['pms']['reserved_units'] }}</td>
                <td class="num">{{ $r['pms']['maintenance_units'] }}</td>
                <td class="num pms-col" style="font-size: 14px;">{{ $r['pms']['available_units'] }}</td>
                @foreach($matrixData['channels'] as $ch)
                    @php $c = $r['channels'][$ch->code] ?? null; @endphp
                    <td class="num">
                        @if(!$c || !$c['is_configured'])
                            <span style="color: #94a3b8;">N/C</span>
                        @elseif(!$c['is_mapped'])
                            <span style="color: #94a3b8;">N/M</span>
                        @elseif($c['remote_units'] === null)
                            <span style="color: #ef4444;">Missing</span>
                        @else
                            {{ $c['remote_units'] }}
                        @endif
                    </td>
                @endforeach
                <td class="num">
                    @if($r['has_oversell_risk'])
                        <span class="badge" style="background: #fee2e2; color: #991b1b;">OVERSELL RISK</span>
                    @elseif($r['has_discrepancy'])
                        <span class="badge" style="background: #fef3c7; color: #92400e;">DISCREPANCY</span>
                    @else
                        <span class="badge" style="background: #dcfce7; color: #166534;">MATCHED</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; font-size: 10px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 10px;">
        ClearStay 5.0 Enterprise Hotel Management System | Certified Database Integrity & Multi-Channel Synchronization Ledger
    </div>
</body>
</html>
