<?php

namespace App\Livewire\Guards;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\Guard;
use Livewire\Component;
use Livewire\WithPagination;

class KnowYourGuardQueue extends Component
{
    use AuthorizesModuleAccess, WithPagination;

    public string $search = '';

    public string $statusFilter = 'queue';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'queue', 'as' => 'status'],
    ];

    public function mount(): void
    {
        $this->authorizePermission('guards.manage');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $base = Guard::query()->with('branch');

        $counts = [
            'queue' => (clone $base)->whereIn('verification_status', ['unverified', 'pending'])->count(),
            'all' => (clone $base)->count(),
            'pending' => (clone $base)->where('verification_status', 'pending')->count(),
            'unverified' => (clone $base)->where('verification_status', 'unverified')->count(),
            'verified' => (clone $base)->where('verification_status', 'verified')->count(),
            'suspended' => (clone $base)->where('verification_status', 'suspended')->count(),
        ];

        $guards = Guard::query()
            ->with('branch')
            ->when($this->statusFilter === 'queue', fn ($q) => $q->whereIn('verification_status', ['unverified', 'pending']))
            ->when($this->statusFilter === 'pending', fn ($q) => $q->where('verification_status', 'pending'))
            ->when($this->statusFilter === 'unverified', fn ($q) => $q->where('verification_status', 'unverified'))
            ->when($this->statusFilter === 'verified', fn ($q) => $q->where('verification_status', 'verified'))
            ->when($this->statusFilter === 'suspended', fn ($q) => $q->where('verification_status', 'suspended'))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('first_name', 'like', '%'.$this->search.'%')
                    ->orWhere('last_name', 'like', '%'.$this->search.'%')
                    ->orWhere('employee_number', 'like', '%'.$this->search.'%');
            }))
            ->orderByRaw("CASE verification_status WHEN 'pending' THEN 1 WHEN 'unverified' THEN 2 WHEN 'suspended' THEN 3 WHEN 'verified' THEN 4 ELSE 5 END")
            ->orderBy('first_name')
            ->paginate(15);

        return view('livewire.guards.know-your-guard-queue', [
            'guards' => $guards,
            'counts' => $counts,
        ])->layout('layouts.app');
    }
}
