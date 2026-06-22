<?php
namespace App\Livewire\Mobile;
use Livewire\Component;
use App\Models\OfflineSyncBatch;
class OfflineSyncMonitor extends Component
{
    public string $search = '';
    public function render()
    {
        $items = OfflineSyncBatch::query()->latest()->limit(50)->get();
        return view('livewire.mobile.offline-sync-monitor', compact('items'))->layout('layouts.app');
    }
}
