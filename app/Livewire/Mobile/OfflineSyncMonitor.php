<?php

namespace App\Livewire\Mobile;

use App\Jobs\ProcessOfflineSyncBatch;
use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\OfflineSyncBatch;
use Illuminate\Support\Facades\DB;
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

    public function process(int $batchId): void
    {
        abort_unless(auth()->user()->can('mobile.use') || auth()->user()->can('dispatch.manage'), 403);
        $queued = DB::transaction(function () use ($batchId) {
            $batch = OfflineSyncBatch::query()
                ->whereKey($batchId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($batch->status !== 'pending') {
                return false;
            }

            $batch->update(['status' => 'processing']);
            ProcessOfflineSyncBatch::dispatch($batch->fresh())->afterCommit();

            return true;
        });

        if (! $queued) {
            $this->addError('batch', 'This batch is already processing or complete.');

            return;
        }

        session()->flash('status', 'Offline sync batch queued.');
    }

    public function render()
    {
        return view('livewire.mobile.offline-sync-monitor', [
            'items' => OfflineSyncBatch::query()
                ->with('user')
                ->when($this->search, fn ($q) => $q->where('status', $this->search))
                ->latest()
                ->limit(50)
                ->get(),
        ])->layout('layouts.app');
    }
}
