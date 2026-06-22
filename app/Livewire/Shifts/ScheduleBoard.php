<?php

namespace App\Livewire\Shifts;

use App\Models\{ClientAccount, Guard, Shift, Site, SitePost};
use App\Services\ScheduleService;
use Livewire\Component;

class ScheduleBoard extends Component
{
    public string $date;
    public array $form = ['client_account_id'=>'','site_id'=>'','site_post_id'=>'','title'=>'','starts_at'=>'','ends_at'=>'','required_guards'=>1,'billing_rate'=>0,'status'=>'open'];
    public ?int $assignShiftId = null;
    public ?int $assignGuardId = null;
    public function mount(): void { $this->date = today()->toDateString(); $this->form['starts_at']=today()->setHour(8)->format('Y-m-d\TH:i'); $this->form['ends_at']=today()->setHour(17)->format('Y-m-d\TH:i'); }
    public function save(ScheduleService $service): void { $data=$this->validate(['form.client_account_id'=>'required','form.site_id'=>'required','form.title'=>'required','form.starts_at'=>'required','form.ends_at'=>'required','form.required_guards'=>'integer','form.billing_rate'=>'numeric'])['form']; $service->createShift($data + ['tenant_id'=>auth()->user()->tenant_id ?? 1]); }
    public function assign(ScheduleService $service): void { $service->assignGuard(Shift::findOrFail($this->assignShiftId), Guard::findOrFail($this->assignGuardId)); $this->reset(['assignShiftId','assignGuardId']); }
    public function render() { return view('livewire.shifts.schedule-board', ['shifts'=>Shift::with(['site','sitePost','assignments.guard'])->whereDate('starts_at',$this->date)->orderBy('starts_at')->get(),'clients'=>ClientAccount::orderBy('name')->get(),'sites'=>Site::orderBy('name')->get(),'posts'=>SitePost::orderBy('name')->get(),'guards'=>Guard::where('status','active')->orderBy('first_name')->get()])->layout('layouts.app'); }
}
