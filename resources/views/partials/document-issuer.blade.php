<div style="margin-bottom: 12px">
    @if ($issuerLogo)
        <img src="{{ $issuerLogo }}" alt="" style="max-height: 48px; max-width: 144px; margin-bottom: 4px">
    @endif
    <div class="brand">{{ $issuer['name'] }}</div>
    @if (!empty($issuer['address']))
        <div style="white-space: pre-line">{{ $issuer['address'] }}</div>
    @endif
    @foreach (['email', 'phone', 'website', 'tax_id', 'registration_number'] as $field)
        @if (!empty($issuer[$field]))
            <div>{{ match ($field) { 'tax_id' => 'Tax ID: ', 'registration_number' => 'Registration no.: ', default => '' } }}{{ $issuer[$field] }}</div>
        @endif
    @endforeach
</div>
