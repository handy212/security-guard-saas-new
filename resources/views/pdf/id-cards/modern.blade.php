<table class="card" width="153" height="243" cellpadding="0" cellspacing="0">
    <tr style="height: 62pt">
        <td class="header-bg" valign="top" style="padding: 10pt 12pt 0;">
            @include('pdf.id-cards._company-header', ['showTagline' => true])
        </td>
    </tr>
    <tr style="height: 28pt">
        <td align="center" valign="top" style="padding: 0;">
            @if ($photoPath || ($photoUrl ?? null))
                <img
                    src="{{ $photoPath ?? $photoUrl }}"
                    width="{{ $photoWidth }}"
                    height="{{ $photoHeight }}"
                    alt=""
                    style="display:block;margin:-22pt auto 0;width:{{ $photoWidth }}pt;height:{{ $photoHeight }}pt;"
                >
            @endif
        </td>
    </tr>
    <tr>
        <td valign="top" style="padding: 8pt 14pt 6pt;">
            <div class="staff-name">{{ $card['name'] }}</div>
            <div class="staff-role">{{ $card['role'] }}</div>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 10pt;">
                <tr class="info-row">
                    <td class="info-label">ID</td>
                    <td class="info-value">{{ $card['employee_id'] }}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr style="height: 42pt">
        <td class="footer-bg" valign="middle" style="padding: 8pt 12pt;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td valign="middle" width="60%">
                        <div class="id-number">{{ $card['employee_id'] }}</div>
                        <div class="scan-hint" style="color:#94a3b8;font-size:5.5pt;">Issued {{ $card['issue_date'] }}</div>
                        <div class="scan-hint">Scan to verify</div>
                    </td>
                    <td valign="middle" align="right" width="40%">
                        @if ($qrPath || ! empty($qrPng))
                            <img
                                @if ($qrPath) src="{{ $qrPath }}" @else src="data:image/png;base64,{!! $qrPng !!}" @endif
                                width="36" height="36" alt="QR"
                                style="width:36pt;height:36pt;"
                            >
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
