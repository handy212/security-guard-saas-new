<table class="card" width="153" height="243" cellpadding="0" cellspacing="0" style="background:#ffffff;">
    <tr>
        <td class="accent-bar" width="5"></td>
        <td valign="top">
            <table width="148" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding: 12pt 10pt 8pt 8pt; border-bottom: 1pt solid #f1f5f9;">
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td valign="top" width="42" style="padding-right: 8pt;">
                                    @if ($photoPath || ($photoUrl ?? null))
                                        <img src="{{ $photoPath ?? $photoUrl }}" width="{{ $photoWidth ?? 38 }}" height="{{ $photoHeight ?? 38 }}" alt="" style="width:{{ $photoWidth ?? 38 }}pt;height:{{ $photoHeight ?? 38 }}pt;">
                                    @endif
                                </td>
                                <td valign="top">
                                    @if (($logoPath ?? null) || ($logoUrl ?? null))
                                        <img src="{{ $logoPath ?? $logoUrl }}" alt="" height="12" style="height:12pt;width:auto;display:block;margin-bottom:3pt;">
                                    @endif
                                    <div class="company-accent">{{ $brand['company_name'] }}</div>
                                    <div class="creative-name">{{ $card['name'] }}</div>
                                    <div class="creative-role">{{ $card['role'] }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 6pt 10pt 6pt 8pt;">
                        @foreach ([
                            ['ID', 'ID', $card['employee_id']],
                        ] as [$icon, $label, $value])
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 6pt; border-bottom: 0.5pt solid #f1f5f9; padding-bottom: 5pt;">
                                <tr>
                                    <td class="creative-icon" width="18" height="18">{{ $icon }}</td>
                                    <td style="padding-left: 6pt;" valign="middle">
                                        <div class="creative-label">{{ $label }}</div>
                                        <div class="creative-value">{{ $value }}</div>
                                    </td>
                                </tr>
                            </table>
                        @endforeach
                    </td>
                </tr>
                <tr>
                    <td class="footer-dark" style="padding: 8pt 10pt 8pt 8pt;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td valign="middle">
                                    <div class="id-mono">{{ $card['employee_id'] }}</div>
                                    <div style="font-size:5.5pt;color:#64748b;margin-top:1pt;">Issued {{ $card['issue_date'] }}</div>
                                </td>
                                <td valign="middle" align="right">
                                    @if ($qrPath || ! empty($qrPng))
                                        <img
                                            @if ($qrPath) src="{{ $qrPath }}" @else src="data:image/png;base64,{!! $qrPng !!}" @endif
                                            width="34" height="34" alt="QR"
                                            style="width:34pt;height:34pt;background:#fff;padding:2pt;"
                                        >
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
