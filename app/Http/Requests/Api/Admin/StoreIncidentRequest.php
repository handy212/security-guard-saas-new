<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\IncidentSeverity;
use App\Models\Incident;
use App\Support\TenantValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Incident::class);
    }

    public function rules(): array
    {
        return [
            'site_id' => ['required', 'integer', TenantValidation::exists('sites')],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'incident_type' => ['nullable', 'string', 'max:100'],
            'severity' => ['required', Rule::enum(IncidentSeverity::class)],
            'description' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
