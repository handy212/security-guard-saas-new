<?php

namespace App\Livewire\Sites;

use App\Models\{ClientAccount, Site};
use Livewire\Component;
use Livewire\WithPagination;

class SiteIndex extends Component
{
    use WithPagination;
    public string $search = '';
    public array $form = ['client_account_id'=>'','name'=>'','address'=>'','latitude'=>'','longitude'=>'','geofence_radius_meters'=>150,'status'=>'active'];
    public ?int $editingId = null;
    protected function rules(): array { return ['form.client_account_id'=>'required','form.name'=>'required','form.address'=>'nullable','form.latitude'=>'nullable|numeric','form.longitude'=>'nullable|numeric','form.geofence_radius_meters'=>'required|integer','form.status'=>'required']; }
    public function save(): void { $data=$this->validate()['form']; Site::updateOrCreate(['id'=>$this->editingId], $data + ['tenant_id'=>auth()->user()->tenant_id ?? 1]); $this->reset(['editingId']); $this->form=['client_account_id'=>'','name'=>'','address'=>'','latitude'=>'','longitude'=>'','geofence_radius_meters'=>150,'status'=>'active']; }
    public function edit(Site $site): void { $this->editingId=$site->id; $this->form=$site->only(['client_account_id','name','address','latitude','longitude','geofence_radius_meters','status']); }
    public function delete(Site $site): void { $site->delete(); }
    public function render() { return view('livewire.sites.site-index', ['sites'=>Site::with('clientAccount')->where('name','like','%'.$this->search.'%')->latest()->paginate(10),'clients'=>ClientAccount::orderBy('name')->get()])->layout('layouts.app'); }
}
