<?php
namespace App\Livewire\Visitors;
use Livewire\Component;
use App\Models\VisitorLog;
class VisitorLogIndex extends Component
{
    public string $search = '';
    public function render()
    {
        $items = VisitorLog::query()->latest()->limit(50)->get();
        return view('livewire.visitors.visitor-log-index', compact('items'))->layout('layouts.app');
    }
}
