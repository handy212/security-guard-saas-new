<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $guard->full_name }} — ID Card</title>
    <style>
        @page { margin: 0; }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #111111;
        }

        table { border-collapse: collapse; }

        .band-white {
            background-color: #ffffff;
            text-align: center;
            padding: 6pt 4pt 0;
            vertical-align: middle;
        }

        .company-logo {
            font-size: 8pt;
            font-weight: bold;
            line-height: 1.15;
        }

        .tagline {
            margin-top: 2pt;
            font-size: 5pt;
            color: #333333;
            line-height: 1.2;
        }

        .band-red {
            background-color: {{ $brand['brand_color'] }};
            color: #ffffff;
            border-top: 1.5pt solid #111111;
            vertical-align: top;
            padding: 0;
        }

        .guard-name {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.15;
            text-align: center;
            padding-top: 6pt;
        }

        .role-bar {
            background: #ffffff;
            color: #111111;
            font-weight: bold;
            font-size: 6pt;
            text-align: center;
            padding: 3pt 0;
            margin-top: 5pt;
        }

        .id-pill {
            background: #ffffff;
            color: #111111;
            font-weight: bold;
            font-size: 6pt;
            padding: 2pt 8pt;
        }

        .back-notice {
            background-color: {{ $brand['brand_color'] }};
            color: #ffffff;
            padding: 5pt 6pt;
            font-size: 6pt;
            line-height: 1.3;
            vertical-align: middle;
        }

        .back-footer {
            background-color: #f5f5f5;
            padding: 4pt 5pt;
            font-size: 6pt;
            line-height: 1.35;
            vertical-align: top;
        }

        .contact-label { font-weight: bold; }

        .qr-caption {
            font-size: 5.5pt;
            color: #333333;
            margin-top: 2pt;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <table width="241" cellpadding="0" cellspacing="0" style="border:1pt solid #1a1a1a">
        <tr style="height:151pt">
            <td width="78" height="151" style="padding:0 0 0 3pt;background-color:#ffffff;vertical-align:top;line-height:0;font-size:0">
                @if ($photoPath)
                    <img
                        src="{{ $photoPath }}"
                        alt=""
                        width="96"
                        height="201"
                        style="display:block;margin:0;padding:0;border:0"
                    >
                @else
                    <table width="72" height="151" cellpadding="0" cellspacing="0" style="border:1.5pt solid #111;border-bottom:0">
                        <tr>
                            <td style="background:#e8e8e8;text-align:center;font-size:14pt;font-weight:bold;color:#666;vertical-align:middle">
                                {{ strtoupper(substr($guard->first_name, 0, 1)) }}
                            </td>
                        </tr>
                    </table>
                @endif
            </td>
            <td width="163" height="151" style="padding:0;vertical-align:top">
                <table width="163" height="151" cellpadding="0" cellspacing="0">
                    <tr style="height:48pt">
                        <td class="band-white">
                            <div class="company-logo">{{ $brand['company_name'] }}</div>
                            <div class="tagline">{{ $brand['tagline'] }}</div>
                        </td>
                    </tr>
                    <tr style="height:103pt">
                        <td class="band-red" height="103" valign="top" style="background-color:{{ $brand['brand_color'] }}">
                            <table width="163" height="103" cellpadding="0" cellspacing="0">
                                <tr style="height:68pt">
                                    <td valign="top" style="background-color:{{ $brand['brand_color'] }}">
                                        <div class="guard-name">{{ strtoupper($guard->full_name) }}</div>
                                        <div class="role-bar">{{ $guard->rank ?: ($guard->branch?->name ?: 'Security Officer') }}</div>
                                    </td>
                                </tr>
                                <tr style="height:35pt">
                                    <td valign="bottom" align="center" style="padding-bottom:5pt;background-color:{{ $brand['brand_color'] }}">
                                        <span class="id-pill">{{ $guard->employee_number ?: ('ID-'.$guard->id) }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table width="241" cellpadding="0" cellspacing="0" style="border:1pt solid #1a1a1a;page-break-before:always">
        <tr style="height:38pt">
            <td class="band-white">
                <div class="company-logo">{{ $brand['company_name'] }}</div>
                <div class="tagline">{{ $brand['tagline'] }}</div>
            </td>
        </tr>
        <tr style="height:34pt">
            <td class="back-notice">{{ $brand['emergency_text'] }}</td>
        </tr>
        <tr style="height:79pt">
            <td class="back-footer">
                <table width="241" cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" width="140">
                            @if ($brand['phone'])
                                <div><span class="contact-label">Tel:</span> {{ $brand['phone'] }}</div>
                            @endif
                            @if ($brand['phone_secondary'])
                                <div>{{ $brand['phone_secondary'] }}</div>
                            @endif
                            @if ($brand['address'])
                                <div style="margin-top:2pt;"><span class="contact-label">Address:</span> {{ $brand['address'] }}</div>
                            @endif
                            @if ($brand['website'])
                                <div><span class="contact-label">Web:</span> {{ $brand['website'] }}</div>
                            @endif
                            @if ($brand['email'])
                                <div><span class="contact-label">E-mail:</span> {{ $brand['email'] }}</div>
                            @endif
                            @if ($guard->phone)
                                <div style="margin-top:2pt;"><span class="contact-label">Guard:</span> {{ $guard->phone }}</div>
                            @endif
                        </td>
                        <td valign="middle" width="101" style="text-align:center">
                            @if ($qrPath || ! empty($qrPng))
                                <img
                                    @if ($qrPath)
                                        src="{{ $qrPath }}"
                                    @else
                                        src="data:image/png;base64,{!! $qrPng !!}"
                                    @endif
                                    width="52"
                                    height="52"
                                    alt="QR code"
                                    style="width:52pt;height:52pt;border:0.5pt solid #cccccc;"
                                >
                                <div class="qr-caption">Scan to verify</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>
