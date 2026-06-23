<?php

namespace App\Livewire\Guards;

use App\Models\Guard;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class GuardIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public array $form = [
        'employee_number' => '', 'first_name' => '', 'last_name' => '', 'phone' => '', 'email' => '',
        'status' => 'active', 'hourly_rate' => 0, 'license_number' => '',
    ];

    protected function rules(): array
    {
        return [
            'form.employee_number' => 'nullable',
            'form.first_name' => 'required',
            'form.last_name' => 'required',
            'form.phone' => 'nullable',
            'form.email' => 'nullable|email',
            'form.status' => 'required',
            'form.hourly_rate' => 'numeric',
            'form.license_number' => 'nullable',
        ];
    }

    public function save(): void
    {
        $this->authorize('create', Guard::class);
        $data = $this->validate()['form'];
        if ($this->editingId) {
            $guard = Guard::findOrFail($this->editingId);
            $this->authorize('update', $guard);
            $guard->update($data);
        } else {
            Guard::create($data + ['tenant_id' => TenantContext::id()]);
        }
        $this->reset(['editingId']);
        $this->form = ['employee_number' => '', 'first_name' => '', 'last_name' => '', 'phone' => '', 'email' => '', 'status' => 'active', 'hourly_rate' => 0, 'license_number' => ''];
    }

    public function edit(Guard $guard): void
    {
        $this->authorize('update', $guard);
        $this->editingId = $guard->id;
        $this->form = $guard->only(array_keys($this->form));
    }

    public function delete(Guard $guard): void
    {
        $this->authorize('delete', $guard);
        $guard->delete();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.guards.guard-index', [
            'guards' => Guard::query()
                ->when($this->search, fn ($query) => $query->where(function ($query) {
                    $query->where('first_name', 'like', '%'.$this->search.'%')
                        ->orWhere('last_name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('employee_number', 'like', '%'.$this->search.'%');
                }))
                ->orderBy('first_name')
                ->paginate(10),
        ])->layout('layouts.app');
    }
}
