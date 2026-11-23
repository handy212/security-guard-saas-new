@props(['issuer'])

@php
    $logo = app(\App\Services\CompanyProfileService::class)->logoDataUri($issuer['logo_path'] ?? null);
@endphp

<div class="min-w-0 break-words">
    <div class="billing-document-label">Issued by</div>
    @if ($logo)
        <img src="{{ $logo }}" alt="" class="my-2 max-h-12 max-w-36 object-contain object-left">
    @endif
    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $issuer['name'] }}</div>
    @if (!empty($issuer['address']))
        <div class="whitespace-pre-line">{{ $issuer['address'] }}</div>
    @endif
    @foreach (['email', 'phone', 'website', 'tax_id', 'registration_number'] as $field)
        @if (!empty($issuer[$field]))
            <div>{{ match ($field) { 'tax_id' => 'Tax ID: ', 'registration_number' => 'Registration no.: ', default => '' } }}{{ $issuer[$field] }}</div>
        @endif
    @endforeach
</div>
