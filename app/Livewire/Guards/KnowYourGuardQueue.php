<?php

namespace App\Livewire\Guards;

use App\Models\Guard;
use Livewire\Component;
use Livewire\WithPagination;

class KnowYourGuardQueue extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);

        return view('livewire.guards.know-your-guard-queue', [
            'guards' => Guard::query()
                ->with('branch')
                ->whereIn('verification_status', ['unverified', 'pending'])
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('first_name', 'like', '%'.$this->search.'%')
                        ->orWhere('last_name', 'like', '%'.$this->search.'%')
                        ->orWhere('employee_number', 'like', '%'.$this->search.'%');
                }))
                ->orderByRaw("FIELD(verification_status, 'pending', 'unverified')")
                ->orderBy('first_name')
                ->paginate(15),
        ])->layout('layouts.app');
    }
}
