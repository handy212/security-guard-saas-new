<table class="card" width="153" height="243" cellpadding="0" cellspacing="0" style="background:#f8fafc;">
    <tr style="height: 72pt">
        <td class="header-bg" valign="middle" align="center">
            @if ($photoPath || ($photoUrl ?? null))
                <img
                    src="{{ $photoPath ?? $photoUrl }}"
                    width="{{ $photoWidth }}"
                    height="{{ $photoHeight }}"
                    alt=""
                    style="display:block;margin:0 auto;width:{{ $photoWidth }}pt;height:{{ $photoHeight }}pt;"
                >
            @endif
        </td>
    </tr>
    <tr>
        <td valign="top" style="padding: 12pt 14pt;">
            <div class="staff-name-lg">{{ $card['name'] }}</div>
            <div class="staff-role-upper">{{ $card['role'] }}</div>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 12pt;">
                <tr>
                    <td colspan="2" valign="top">
                        <div class="info-box">
                            <div class="info-box-label">ID</div>
                            <div class="info-box-value">{{ $card['employee_id'] }}</div>
                        </div>
                    </td>
                </tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 14pt;">
                <tr>
                    <td valign="bottom">
                        @if (($logoPath ?? null) || ($logoUrl ?? null))
                            <img src="{{ $logoPath ?? $logoUrl }}" alt="" height="{{ $logoHeight ?? 14 }}" style="height:{{ $logoHeight ?? 14 }}pt;width:auto;display:block;margin-bottom:3pt;">
                        @endif
                        <div class="company-name" style="color:#334155;">{{ $brand['company_name'] }}</div>
                        <div style="font-size:5.5pt;color:#94a3b8;margin-top:2pt;">Issued {{ $card['issue_date'] }}</div>
                    </td>
                    <td valign="bottom" align="right" width="44pt">
                        @if ($qrPath || ! empty($qrPng))
                            <img
                                @if ($qrPath) src="{{ $qrPath }}" @else src="data:image/png;base64,{!! $qrPng !!}" @endif
                                width="40" height="40" alt="QR"
                                style="width:40pt;height:40pt;background:#fff;padding:2pt;"
                            >
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
