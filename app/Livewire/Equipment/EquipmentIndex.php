<?php
namespace App\Livewire\Equipment;
use Livewire\Component;
use App\Models\EquipmentAsset;
class EquipmentIndex extends Component
{
    public string $search = '';
    public function render()
    {
        $items = EquipmentAsset::query()->latest()->limit(50)->get();
        return view('livewire.equipment.equipment-index', compact('items'))->layout('layouts.app');
    }
}
