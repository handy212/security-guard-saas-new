<?php

namespace App\Livewire\Mobile;

use App\Jobs\ProcessOfflineSyncBatch;
use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\OfflineSyncBatch;
use Livewire\Component;

class OfflineSyncMonitor extends Component
{
    use AuthorizesModuleAccess;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(
            auth()->user()->can('mobile.use') || auth()->user()->can('dispatch.manage'),
            403
        );
    }

    public function process(OfflineSyncBatch $batch): void
    {
        abort_unless(auth()->user()->can('mobile.use') || auth()->user()->can('dispatch.manage'), 403);
        ProcessOfflineSyncBatch::dispatch($batch);
    }

    public function render()
    {
        return view('livewire.mobile.offline-sync-monitor', [
            'items' => OfflineSyncBatch::query()->when($this->search, fn ($q) => $q->where('status', $this->search))->latest()->limit(50)->get(),
        ])->layout('layouts.app');
    }
}
