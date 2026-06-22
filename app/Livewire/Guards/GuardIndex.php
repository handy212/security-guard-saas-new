<?php

namespace App\Livewire\Guards;

use App\Models\Guard;
use Livewire\Component;
use Livewire\WithPagination;

class GuardIndex extends Component
{
    use WithPagination;
    public string $search = '';
    public array $form = ['employee_number'=>'','first_name'=>'','last_name'=>'','phone'=>'','email'=>'','status'=>'active','hourly_rate'=>0,'license_number'=>''];
    public ?int $editingId = null;
    protected function rules(): array { return ['form.first_name'=>'required','form.last_name'=>'required','form.email'=>'nullable|email','form.phone'=>'nullable','form.status'=>'required','form.hourly_rate'=>'numeric','form.employee_number'=>'nullable','form.license_number'=>'nullable']; }
    public function save(): void { $data=$this->validate()['form']; Guard::updateOrCreate(['id'=>$this->editingId], $data + ['tenant_id'=>auth()->user()->tenant_id ?? 1]); $this->reset(['editingId']); $this->form=['employee_number'=>'','first_name'=>'','last_name'=>'','phone'=>'','email'=>'','status'=>'active','hourly_rate'=>0,'license_number'=>'']; }
    public function edit(Guard $guard): void { $this->editingId=$guard->id; $this->form=$guard->only(['employee_number','first_name','last_name','phone','email','status','hourly_rate','license_number']); }
    public function delete(Guard $guard): void { $guard->delete(); }
    public function render() { return view('livewire.guards.guard-index', ['guards'=>Guard::query()->whereRaw("concat(first_name,' ',last_name) like ?", ['%'.$this->search.'%'])->latest()->paginate(10)])->layout('layouts.app'); }
}
