<?php

namespace App\Livewire\Public;

use App\Enums\GuardDutyType;
use App\Models\Branch;
use App\Models\GuardApplication;
use App\Models\Tenant;
use App\Services\FileUploadService;
use Livewire\Component;
use Livewire\WithFileUploads;

class GuardApplicationForm extends Component
{
    use WithFileUploads;

    public string $tenantSlug = '';

    public string $companyName = '';

    public int $tenantId = 0;

    public string $first_name = '';

    public string $last_name = '';

    public string $phone = '';

    public string $email = '';

    public string $duty_type = 'guardian';

    public string $branch_id = '';

    public string $notes = '';

    public $photo;

    public bool $submitted = false;

    public function mount(string $tenant): void
    {
        $record = Tenant::query()
            ->where('slug', $tenant)
            ->where('status', 'active')
            ->firstOrFail();

        app()->instance('currentTenant', $record);
        $this->tenantSlug = $record->slug;
        $this->tenantId = $record->id;
        $this->companyName = $record->name;
    }

    public function submit(FileUploadService $uploads): void
    {
        $tenant = Tenant::findOrFail($this->tenantId);
        app()->instance('currentTenant', $tenant);

        $data = $this->validate([
            'first_name' => 'required|string|max:120',
            'last_name' => 'required|string|max:120',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'duty_type' => 'required|in:guardian,dispatch',
            'branch_id' => 'nullable',
            'notes' => 'nullable|string|max:2000',
            'photo' => 'nullable|image|max:5120',
        ]);

        $photoPath = null;
        if ($this->photo) {
            $photoPath = $uploads->storeApplicationPhoto($tenant->id, $this->photo);
        }

        GuardApplication::create([
            'tenant_id' => $tenant->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null,
            'duty_type' => $data['duty_type'],
            'branch_id' => $data['branch_id'] ?: null,
            'notes' => $data['notes'] ?: null,
            'photo_path' => $photoPath,
            'status' => 'pending',
        ]);

        $this->submitted = true;
        $this->reset(['first_name', 'last_name', 'phone', 'email', 'notes', 'photo', 'branch_id']);
        $this->duty_type = 'guardian';
    }

    public function render()
    {
        $tenant = Tenant::findOrFail($this->tenantId);
        app()->instance('currentTenant', $tenant);

        return view('livewire.public.guard-application-form', [
            'companyName' => $this->companyName,
            'dutyTypes' => GuardDutyType::options(),
            'branches' => Branch::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.guest', [
            'title' => 'Apply — '.$this->companyName,
        ]);
    }
}
