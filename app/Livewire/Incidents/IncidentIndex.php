<?php

namespace App\Livewire\Incidents;

use App\Models\{Incident, Site};
use App\Services\IncidentService;
use Livewire\Component;
use Livewire\WithPagination;

class IncidentIndex extends Component
{
    use WithPagination;
    public string $search='';
    public array $form=['site_id'=>'','title'=>'','type'=>'','severity'=>'medium','description'=>'','status'=>'submitted'];
    public function save(IncidentService $service): void { $data=$this->validate(['form.site_id'=>'required','form.title'=>'required','form.type'=>'required','form.severity'=>'required','form.description'=>'required'])['form']; $service->submit($data + ['tenant_id'=>auth()->user()->tenant_id ?? 1,'reported_by_user_id'=>auth()->id()]); $this->form=['site_id'=>'','title'=>'','type'=>'','severity'=>'medium','description'=>'','status'=>'submitted']; }
    public function approve(Incident $incident, IncidentService $service): void { $service->approve($incident, auth()->id() ?? 1); }
    public function close(Incident $incident, IncidentService $service): void { $service->close($incident, 'Closed from operations dashboard'); }
    public function render(){ return view('livewire.incidents.incident-index',['incidents'=>Incident::with('site')->where('title','like','%'.$this->search.'%')->latest()->paginate(10),'sites'=>Site::orderBy('name')->get()])->layout('layouts.app'); }
}
