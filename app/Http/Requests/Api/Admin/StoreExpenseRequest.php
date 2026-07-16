<?php

namespace App\Http\Requests\Api\Admin;

use App\Models\Expense;
use App\Support\TenantValidation;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Expense::class);
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['nullable', 'integer', TenantValidation::exists('expense_categories')],
            'client_account_id' => ['nullable', 'integer', TenantValidation::exists('client_accounts')],
            'site_id' => ['nullable', 'integer', TenantValidation::exists('sites')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:50'],
        ];
    }
}
