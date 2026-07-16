<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Estimate;
use App\Services\EstimateService;
use App\Support\TenantValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class EstimateController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Estimate::class);

        $query = Estimate::with('clientAccount')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where('estimate_number', 'like', $term)
                    ->orWhereHas('clientAccount', fn ($c) => $c->where('name', 'like', $term));
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest();

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, EstimateService $service): JsonResponse
    {
        $this->authorize('create', Estimate::class);

        [$form, $items] = $this->validatedEstimate($request);
        $estimate = $service->create($form, $items);

        return $this->data($estimate, 201);
    }

    public function show(Estimate $estimate): JsonResponse
    {
        $this->authorize('view', $estimate);

        return $this->data($estimate->load(['clientAccount', 'items']));
    }

    public function update(Request $request, Estimate $estimate, EstimateService $service): JsonResponse
    {
        $this->authorize('update', $estimate);

        try {
            [$form, $items] = $this->validatedEstimate($request, partial: true);
            if (! $request->has('items')) {
                $items = $estimate->items->map(fn ($item) => [
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'is_taxable' => (bool) $item->is_taxable,
                ])->values()->all();
            }
            $estimate = $service->update($estimate, $form, $items);
        } catch (RuntimeException $e) {
            return $this->serviceError($e);
        }

        return $this->data($estimate);
    }

    public function destroy(Estimate $estimate, EstimateService $service): JsonResponse
    {
        $this->authorize('delete', $estimate);

        try {
            $service->delete($estimate);
        } catch (RuntimeException $e) {
            return $this->serviceError($e);
        }

        return $this->noContent();
    }

    public function send(Estimate $estimate, EstimateService $service): JsonResponse
    {
        $this->authorize('update', $estimate);

        try {
            $estimate = $service->send($estimate);
        } catch (RuntimeException $e) {
            return $this->serviceError($e);
        }

        return $this->data($estimate);
    }

    public function accept(Estimate $estimate, EstimateService $service): JsonResponse
    {
        $this->authorize('update', $estimate);

        return $this->data($service->accept($estimate));
    }

    public function convert(Estimate $estimate, EstimateService $service): JsonResponse
    {
        $this->authorize('update', $estimate);
        $invoice = $service->convertToInvoice($estimate);

        return $this->data(['estimate' => $estimate->fresh(), 'invoice' => $invoice->load('items')]);
    }

    private function validatedEstimate(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        $form = $request->validate([
            'client_account_id' => [...$required, 'integer', TenantValidation::exists('client_accounts')],
            'estimate_date' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
        ]);

        $items = $request->validate([
            'items' => [...$required, 'array', 'min:1'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.is_taxable' => ['nullable', 'boolean'],
        ])['items'] ?? [];

        return [$form, $items];
    }
}
