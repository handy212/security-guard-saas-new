<table class="card" width="153" height="243" cellpadding="0" cellspacing="0">
    <tr style="height: 44pt">
        <td class="header-bg" align="center" valign="middle" style="padding: 10pt;">
            @include('pdf.id-cards._company-header', ['showTagline' => true])
        </td>
    </tr>
    <tr style="height: 52pt">
        <td class="header-bg-dark notice">{{ $brand['emergency_text'] }}</td>
    </tr>
    <tr>
        <td class="contact" valign="top">
            @if (($logoPath ?? null) || ($logoUrl ?? null))
                <div style="text-align:center;margin-bottom:8pt;">
                    <img src="{{ $logoPath ?? $logoUrl }}" alt="" height="{{ ($logoHeight ?? 18) + 4 }}" style="height:{{ ($logoHeight ?? 18) + 4 }}pt;width:auto;">
                </div>
            @endif
            @if ($brand['phone'])
                <div><span class="contact-label">Tel:</span> {{ $brand['phone'] }}</div>
            @endif
            @if ($brand['phone_secondary'])
                <div>{{ $brand['phone_secondary'] }}</div>
            @endif
            @if ($brand['address'])
                <div style="margin-top:4pt;"><span class="contact-label">Address:</span> {{ $brand['address'] }}</div>
            @endif
            @if ($brand['website'])
                <div><span class="contact-label">Web:</span> {{ $brand['website'] }}</div>
            @endif
            @if ($brand['email'])
                <div><span class="contact-label">E-mail:</span> {{ $brand['email'] }}</div>
            @endif
            @if ($guard->phone ?? null)
                <div style="margin-top:4pt;"><span class="contact-label">Guard:</span> {{ $guard->phone }}</div>
            @endif
            @if ($verifyHost ?? null)
                <div style="margin-top:8pt;font-size:5pt;color:#64748b;text-align:center;">Verify at {{ $verifyHost }}</div>
            @endif
        </td>
    </tr>
</table>
