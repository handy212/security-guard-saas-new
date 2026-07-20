@php
    $hasLogo = ($logoPath ?? null) || ($logoUrl ?? null);
    $logoSrc = $logoPath ?? $logoUrl ?? null;
    $align = $align ?? 'left'; // left | center | right
    $tableAlign = match ($align) {
        'center' => 'center',
        'right' => 'right',
        default => 'left',
    };
    $textAlign = $align === 'center' ? 'center' : ($align === 'right' ? 'right' : 'left');
@endphp

@if ($hasLogo && $logoSrc)
    <table cellpadding="0" cellspacing="0" align="{{ $tableAlign }}" style="margin-bottom: 2pt;">
        <tr>
            @if ($align === 'right')
                <td valign="middle" style="padding-right: 6pt; text-align: right;">
                    <div class="company-name" style="text-align:{{ $textAlign }};">{{ $brand['company_name'] }}</div>
                    @if (! empty($showTagline) && ! empty($brand['tagline']))
                        <div class="company-tagline" style="text-align:{{ $textAlign }};">{{ $brand['tagline'] }}</div>
                    @endif
                </td>
                <td valign="middle">
                    <img
                        src="{{ $logoSrc }}"
                        alt=""
                        height="{{ $logoHeight ?? 18 }}"
                        style="height:{{ $logoHeight ?? 18 }}pt;width:auto;display:block;"
                    >
                </td>
            @else
                <td valign="middle" style="padding-right: 6pt;">
                    <img
                        src="{{ $logoSrc }}"
                        alt=""
                        height="{{ $logoHeight ?? 18 }}"
                        style="height:{{ $logoHeight ?? 18 }}pt;width:auto;display:block;"
                    >
                </td>
                <td valign="middle" style="text-align:{{ $textAlign }};">
                    <div class="company-name" style="text-align:{{ $textAlign }};">{{ $brand['company_name'] }}</div>
                    @if (! empty($showTagline) && ! empty($brand['tagline']))
                        <div class="company-tagline" style="text-align:{{ $textAlign }};">{{ $brand['tagline'] }}</div>
                    @endif
                </td>
            @endif
        </tr>
    </table>
@else
    <div class="company-name" style="text-align:{{ $textAlign }};">{{ $brand['company_name'] }}</div>
    @if (! empty($showTagline) && ! empty($brand['tagline']))
        <div class="company-tagline" style="text-align:{{ $textAlign }};">{{ $brand['tagline'] }}</div>
    @endif
@endif
