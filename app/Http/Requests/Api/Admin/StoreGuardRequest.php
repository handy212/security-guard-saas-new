<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\GuardDutyType;
use App\Models\Guard;
use App\Support\TenantValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Guard::class);
    }

    public function rules(): array
    {
        return [
            'employee_number' => ['nullable', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'monthly_rate' => ['nullable', 'numeric', 'min:0'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_expires_at' => ['nullable', 'date'],
            'rank' => ['nullable', 'string', 'max:100'],
            'duty_type' => ['required', Rule::enum(GuardDutyType::class)],
            'branch_id' => ['nullable', 'integer', TenantValidation::exists('branches')],
        ];
    }
}
