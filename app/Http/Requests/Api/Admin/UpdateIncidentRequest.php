<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\IncidentSeverity;
use App\Support\TenantValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('incident'));
    }

    public function rules(): array
    {
        return [
            'site_id' => ['sometimes', 'required', 'integer', TenantValidation::exists('sites')],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'incident_type' => ['nullable', 'string', 'max:100'],
            'severity' => ['sometimes', 'required', Rule::enum(IncidentSeverity::class)],
            'description' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
