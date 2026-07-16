<?php

namespace App\Http\Requests\Api\Admin;

use App\Models\Site;
use App\Support\TenantValidation;
use Illuminate\Foundation\Http\FormRequest;

class StoreSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Site::class);
    }

    public function rules(): array
    {
        return [
            'client_account_id' => ['required', 'integer', TenantValidation::exists('client_accounts')],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'geofence_radius_meters' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
        ];
    }
}
