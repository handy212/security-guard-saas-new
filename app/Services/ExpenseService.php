<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Support\MutableStatus;
use App\Support\TenantContext;

class ExpenseService
{
    public function ensureDefaultCategories(int $tenantId): void
    {
        foreach (['Fuel', 'Uniforms', 'Equipment', 'Travel', 'Office', 'Other'] as $name) {
            ExpenseCategory::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $name],
                ['is_active' => true],
            );
        }
    }

    public function create(array $data, ?string $receiptPath = null): Expense
    {
        $tenantId = TenantContext::id();
        $this->ensureDefaultCategories($tenantId);

        return Expense::create([
            'tenant_id' => $tenantId,
            'expense_category_id' => ! empty($data['expense_category_id']) ? $data['expense_category_id'] : null,
            'client_account_id' => ! empty($data['client_account_id']) ? $data['client_account_id'] : null,
            'site_id' => ! empty($data['site_id']) ? $data['site_id'] : null,
            'created_by_user_id' => auth()->id(),
            'expense_number' => $this->nextNumber($tenantId),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'vendor_name' => $data['vendor_name'] ?? null,
            'expense_date' => $data['expense_date'],
            'amount' => $data['amount'],
            'status' => $data['status'] ?? 'draft',
            'payment_method' => $data['payment_method'] ?? null,
            'receipt_path' => $receiptPath,
        ]);
    }

    public function update(Expense $expense, array $data, ?string $receiptPath = null): Expense
    {
        MutableStatus::assertMutable($expense);

        $payload = [
            'expense_category_id' => array_key_exists('expense_category_id', $data) ? ($data['expense_category_id'] ?: null) : $expense->expense_category_id,
            'client_account_id' => array_key_exists('client_account_id', $data) ? ($data['client_account_id'] ?: null) : $expense->client_account_id,
            'site_id' => array_key_exists('site_id', $data) ? ($data['site_id'] ?: null) : $expense->site_id,
            'title' => $data['title'] ?? $expense->title,
            'description' => $data['description'] ?? $expense->description,
            'vendor_name' => $data['vendor_name'] ?? $expense->vendor_name,
            'expense_date' => $data['expense_date'] ?? $expense->expense_date,
            'amount' => $data['amount'] ?? $expense->amount,
            'payment_method' => $data['payment_method'] ?? $expense->payment_method,
        ];

        if ($receiptPath !== null) {
            $payload['receipt_path'] = $receiptPath;
        }

        $expense->update($payload);

        return $expense->fresh();
    }

    public function delete(Expense $expense): void
    {
        MutableStatus::assertMutable($expense);
        $expense->delete();
    }

    public function approve(Expense $expense): void
    {
        $expense->update([
            'status' => 'approved',
            'approved_by_user_id' => auth()->id(),
            'approved_at' => now(),
        ]);
    }

    public function markPaid(Expense $expense, ?string $paymentMethod = null): void
    {
        $expense->update([
            'status' => 'paid',
            'payment_method' => $paymentMethod ?? $expense->payment_method,
            'paid_at' => now(),
        ]);
    }

    private function nextNumber(int $tenantId): string
    {
        $count = Expense::where('tenant_id', $tenantId)->count() + 1;

        return 'EXP-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}
