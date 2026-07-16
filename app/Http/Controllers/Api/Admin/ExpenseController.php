<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Api\Admin\StoreExpenseRequest;
use App\Http\Requests\Api\Admin\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ExpenseController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Expense::class);

        $query = Expense::with(['category', 'clientAccount', 'site'])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where('title', 'like', $term)
                    ->orWhere('vendor_name', 'like', $term)
                    ->orWhere('expense_number', 'like', $term);
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('expense_date');

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(StoreExpenseRequest $request, ExpenseService $service): JsonResponse
    {
        $expense = $service->create($request->validated());

        return $this->data($expense->load(['category', 'clientAccount', 'site']), 201);
    }

    public function show(Expense $expense): JsonResponse
    {
        $this->authorize('view', $expense);

        return $this->data($expense->load(['category', 'clientAccount', 'site']));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense, ExpenseService $service): JsonResponse
    {
        try {
            $expense = $service->update($expense, $request->validated());
        } catch (RuntimeException $e) {
            return $this->serviceError($e);
        }

        return $this->data($expense->load(['category', 'clientAccount', 'site']));
    }

    public function destroy(Expense $expense, ExpenseService $service): JsonResponse
    {
        $this->authorize('delete', $expense);

        try {
            $service->delete($expense);
        } catch (RuntimeException $e) {
            return $this->serviceError($e);
        }

        return $this->noContent();
    }

    public function approve(Expense $expense, ExpenseService $service): JsonResponse
    {
        $this->authorize('update', $expense);
        abort_unless(in_array($expense->status, ['draft', 'submitted'], true), 422);
        $service->approve($expense);

        return $this->data($expense->fresh());
    }

    public function markPaid(Request $request, Expense $expense, ExpenseService $service): JsonResponse
    {
        $this->authorize('update', $expense);
        abort_unless($expense->status === 'approved', 422);

        $data = $request->validate(['payment_method' => ['nullable', 'string', 'max:50']]);
        $service->markPaid($expense, $data['payment_method'] ?? null);

        return $this->data($expense->fresh());
    }
}
