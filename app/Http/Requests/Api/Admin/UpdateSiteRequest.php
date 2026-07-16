<?php

namespace App\Http\Requests\Api\Admin;

use App\Support\TenantValidation;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('site'));
    }

    public function rules(): array
    {
        return [
            'client_account_id' => ['sometimes', 'required', 'integer', TenantValidation::exists('client_accounts')],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'geofence_radius_meters' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'required', 'string', 'max:50'],
        ];
    }
}
