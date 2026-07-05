@php
    $hasLogo = ($logoPath ?? null) || ($logoUrl ?? null);
    $logoSrc = $logoPath ?? $logoUrl ?? null;
@endphp

@if ($hasLogo && $logoSrc)
    <table cellpadding="0" cellspacing="0" style="margin-bottom: 4pt;">
        <tr>
            <td valign="middle" style="padding-right: 6pt;">
                <img
                    src="{{ $logoSrc }}"
                    alt=""
                    height="{{ $logoHeight ?? 18 }}"
                    style="height:{{ $logoHeight ?? 18 }}pt;width:auto;display:block;"
                >
            </td>
            <td valign="middle">
                <div class="company-name">{{ $brand['company_name'] }}</div>
                @if (! empty($showTagline))
                    <div class="company-tagline">{{ $brand['tagline'] }}</div>
                @endif
            </td>
        </tr>
    </table>
@else
    <div class="company-name">{{ $brand['company_name'] }}</div>
    @if (! empty($showTagline))
        <div class="company-tagline">{{ $brand['tagline'] }}</div>
    @endif
@endif
