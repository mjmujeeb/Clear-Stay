<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Access Denied - ClearStay Threat Shield</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #0b1329;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 580px;
            width: 90%;
            background: #111e38;
            border: 1px solid #dc2626;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(220, 38, 38, 0.25);
            text-align: center;
        }
        .shield-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(220, 38, 38, 0.15);
            border: 2px solid #dc2626;
            color: #ef4444;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 24px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.5); }
            70% { box-shadow: 0 0 0 16px rgba(220, 38, 38, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
        }
        h1 {
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 12px;
        }
        p {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .incident-box {
            background: #080d1a;
            border: 1px solid #1e293b;
            border-radius: 8px;
            padding: 16px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            text-align: left;
            margin-bottom: 24px;
            color: #38bdf8;
        }
        .incident-row {
            margin-bottom: 6px;
        }
        .incident-row:last-child {
            margin-bottom: 0;
        }
        .btn-retry {
            display: inline-block;
            background: #dc2626;
            color: white;
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            transition: background 0.2s;
        }
        .btn-retry:hover {
            background: #b91c1c;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="shield-icon">
            <i class="fa-solid fa-shield-virus"></i>
        </div>
        <h1>403 Forbidden: Request Blocked</h1>
        <p>
            Your IP address has been temporarily restricted by the <strong>ClearStay Web Application Firewall (WAF)</strong>.
            This action was triggered due to automated threat patterns, rapid bot submission rates, or security policy violations.
        </p>

        <div class="incident-box">
            <div class="incident-row"><strong>Target IP:</strong> {{ $ip ?? request()->ip() }}</div>
            <div class="incident-row"><strong>Status:</strong> THREAT_MITIGATION_ACTIVE</div>
            <div class="incident-row"><strong>Timestamp:</strong> {{ now()->toIso8601String() }}</div>
            <div class="incident-row"><strong>Defense System:</strong> ClearShield Adaptive Rate Limiter & Bot Defense</div>
        </div>

        <a href="/" class="btn-retry"><i class="fa-solid fa-rotate-left"></i> Return to Homepage</a>
    </div>
</body>
</html>
