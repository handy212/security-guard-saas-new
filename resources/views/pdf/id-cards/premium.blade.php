<table class="card" width="153" height="243" cellpadding="0" cellspacing="0" style="background:#ffffff;">
    <tr style="height: 36pt">
        <td class="header-bg premium-header-cell" valign="middle" style="padding: 6pt 10pt;position:relative;">
            <div class="premium-badge-pdf">SECURITY ID</div>
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="right" valign="middle">
                        @include('pdf.id-cards._company-header', ['showTagline' => true, 'align' => 'right'])
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr style="height: 88pt">
        <td align="center" valign="middle" style="padding: 6pt 14pt 4pt;">
            @if ($photoPath || ($photoUrl ?? null))
                <div class="premium-photo-frame-pdf" style="display:inline-block;width:{{ $photoWidth }}pt;">
                    <img
                        src="{{ $photoPath ?? $photoUrl }}"
                        width="{{ $photoWidth }}"
                        height="{{ $photoHeight }}"
                        alt=""
                        style="display:block;width:{{ $photoWidth }}pt;height:{{ $photoHeight }}pt;border:2pt solid {{ $brand['brand_color'] }};object-fit:cover;"
                    >
                </div>
            @endif
        </td>
    </tr>
    <tr>
        <td valign="top" style="padding: 4pt 12pt 4pt;">
            <div class="staff-name">{{ $card['name'] }}</div>
            <div class="premium-role-pill-pdf">{{ $card['role'] }}</div>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 6pt;">
                <tr>
                    <td width="48%" class="premium-chip-pdf" valign="top">
                        <div class="premium-chip-label-pdf">Guard ID</div>
                        <div class="premium-chip-value-pdf">{{ $card['employee_id'] }}</div>
                    </td>
                    <td width="4%"></td>
                    <td width="48%" class="premium-chip-pdf" valign="top">
                        <div class="premium-chip-label-pdf">Issued</div>
                        <div class="premium-chip-value-pdf">{{ $card['issue_date'] }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr style="height: 36pt">
        <td class="footer-premium" valign="middle" style="padding: 6pt 10pt;border-top:2pt solid {{ $brand['brand_color'] }};">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td valign="middle" width="60%">
                        <div class="scan-hint">Scan to verify (KYG)</div>
                    </td>
                    <td valign="middle" align="right" width="40%">
                        @if ($qrPath || ! empty($qrPng))
                            <img
                                @if ($qrPath) src="{{ $qrPath }}" @else src="data:image/png;base64,{!! $qrPng !!}" @endif
                                width="32" height="32" alt="QR"
                                style="width:32pt;height:32pt;background:#fff;padding:2pt;"
                            >
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
