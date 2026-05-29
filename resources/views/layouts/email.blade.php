@php
    $tone = trim($__env->yieldContent('email-tone', 'emerald'));
    $palette = [
        'emerald' => [
            'header' => '#1D4ED8',
            'button' => '#2563EB',
            'buttonText' => '#ffffff',
        ],
        'cyan' => [
            'header' => '#3B82F6',
            'button' => '#2563EB',
            'buttonText' => '#ffffff',
        ],
        'purple' => [
            'header' => '#1E40AF',
            'button' => '#2563EB',
            'buttonText' => '#ffffff',
        ],
        'amber' => [
            'header' => '#1E3A8A',
            'button' => '#2563EB',
            'buttonText' => '#ffffff',
        ],
        'slate' => [
            'header' => '#1E40AF',
            'button' => '#2563EB',
            'buttonText' => '#ffffff',
        ],
    ][$tone] ?? [
        'header' => '#1D4ED8',
        'button' => '#2563EB',
        'buttonText' => '#ffffff',
    ];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('email-title', 'Home Cleaning Service')</title>
    <style>
        body {
            margin: 0;
            padding: 24px 12px;
            background: #EFF6FF;
            font-family: Arial, sans-serif;
            color: #1E3A8A;
        }

        .email-shell {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 18px 50px rgba(37, 99, 235, 0.12);
        }

        .email-header {
            padding: 34px 32px 28px;
            color: #ffffff;
            text-align: center;
        }

        .email-brand {
            margin: 0 0 8px;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.16em;
            opacity: 0.82;
            text-transform: uppercase;
        }

        .email-title {
            margin: 0;
            font-size: 30px;
            line-height: 1.15;
            font-weight: bold;
        }

        .email-subtitle {
            margin: 10px 0 0;
            font-size: 15px;
            line-height: 1.5;
            opacity: 0.9;
        }

        .email-body {
            padding: 32px;
        }

        .email-body p {
            margin: 0 0 16px;
            font-size: 15px;
            line-height: 1.7;
            color: #1E40AF;
        }

        .summary-card {
            margin: 24px 0;
            border: 1px solid #DBEAFE;
            border-radius: 16px;
            overflow: hidden;
            background: #EFF6FF;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 12px 18px;
            border-bottom: 1px solid #DBEAFE;
            font-size: 14px;
            vertical-align: top;
        }

        .summary-table tr:last-child td {
            border-bottom: 0;
        }

        .summary-label {
            color: #3B82F6;
            width: 42%;
        }

        .summary-value {
            color: #1E3A8A;
            font-weight: bold;
            text-align: right;
        }

        .status-pill {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pill--pending {
            background: #DBEAFE;
            color: #1D4ED8;
        }

        .status-pill--confirmed,
        .status-pill--success {
            background: #DBEAFE;
            color: #1D4ED8;
        }

        .status-pill--progress {
            background: #BFDBFE;
            color: #1E40AF;
        }

        .status-pill--neutral {
            background: #DBEAFE;
            color: #1E40AF;
        }

        .callout {
            margin: 18px 0 0;
            padding: 14px 16px;
            border-radius: 14px;
            font-size: 14px;
            line-height: 1.7;
        }

        .callout--info {
            background: #EFF6FF;
            color: #1D4ED8;
        }

        .callout--success {
            background: #EFF6FF;
            color: #1D4ED8;
        }

        .callout--warning {
            background: #EFF6FF;
            color: #1D4ED8;
        }

        .cta-wrap {
            margin-top: 24px;
            text-align: center;
        }

        .cta-button {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 999px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        .code-card {
            text-align: center;
            padding: 24px;
        }

        .code-label {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #3B82F6;
        }

        .code-value {
            margin-top: 14px;
            padding-left: 0.42em;
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 0.42em;
            color: #1E3A8A;
        }

        .muted-note {
            font-size: 13px;
            color: #3B82F6;
        }

        .email-footer {
            padding: 22px 28px;
            background: #EFF6FF;
            text-align: center;
            border-top: 1px solid #DBEAFE;
        }

        .email-footer p {
            margin: 4px 0;
            font-size: 12px;
            line-height: 1.6;
            color: #60A5FA;
        }
    </style>
</head>
<body>
    <div class="email-shell">
        <div class="email-header" style="background: {{ $palette['header'] }};">
            <p class="email-brand">Home Cleaning Service</p>
            <h1 class="email-title">@yield('email-title', 'Home Cleaning Service')</h1>
            @hasSection('email-subtitle')
                <p class="email-subtitle">@yield('email-subtitle')</p>
            @endif
        </div>

        <div class="email-body">
            @yield('content')
        </div>

        <div class="email-footer">
            <p>Home Cleaning Service | Valencia City, Bukidnon</p>
            <p>&copy; {{ date('Y') }} Home Cleaning Service. This is an automated email.</p>
        </div>
    </div>
</body>
</html>
