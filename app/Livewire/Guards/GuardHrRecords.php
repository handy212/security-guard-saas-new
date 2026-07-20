<?php

namespace App\Livewire\Guards;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\DisciplinaryRecord;
use App\Models\Guard;
use App\Models\GuardDocument;
use App\Models\GuardSkill;
use App\Models\TrainingRecord;
use App\Services\FileUploadService;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithFileUploads;

class GuardHrRecords extends Component
{
    use AuthorizesModuleAccess, WithFileUploads;

    public ?string $activeForm = null;

    public array $skillForm = ['guard_id' => '', 'skill' => '', 'level' => 'basic'];

    public array $trainingForm = ['guard_id' => '', 'course_name' => '', 'completed_on' => '', 'expires_on' => ''];

    public array $disciplinaryForm = ['guard_id' => '', 'occurred_on' => '', 'type' => 'warning', 'description' => '', 'action_taken' => ''];

    public array $documentForm = ['guard_id' => '', 'type' => 'license', 'expires_at' => ''];

    public $documentFile;

    public function mount(): void
    {
        $this->authorizePermission('guards.manage');
    }

    public function openForm(string $form): void
    {
        $this->activeForm = $form;
        $this->resetErrorBag();
    }

    public function closeForm(): void
    {
        $this->activeForm = null;
        $this->reset('documentFile');
        $this->resetErrorBag();
    }

    public function saveSkill(): void
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        GuardSkill::create($this->validate([
            'skillForm.guard_id' => 'required',
            'skillForm.skill' => 'required',
            'skillForm.level' => 'required',
        ])['skillForm'] + ['tenant_id' => TenantContext::id()]);

        $this->skillForm = ['guard_id' => '', 'skill' => '', 'level' => 'basic'];
        $this->activeForm = null;
        session()->flash('status', 'Skill saved.');
    }

    public function saveTraining(): void
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        TrainingRecord::create($this->validate([
            'trainingForm.guard_id' => 'required',
            'trainingForm.course_name' => 'required',
            'trainingForm.completed_on' => 'nullable|date',
            'trainingForm.expires_on' => 'nullable|date',
        ])['trainingForm'] + ['tenant_id' => TenantContext::id()]);

        $this->trainingForm = ['guard_id' => '', 'course_name' => '', 'completed_on' => '', 'expires_on' => ''];
        $this->activeForm = null;
        session()->flash('status', 'Training record saved.');
    }

    public function saveDisciplinary(): void
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        DisciplinaryRecord::create($this->validate([
            'disciplinaryForm.guard_id' => 'required',
            'disciplinaryForm.occurred_on' => 'required|date',
            'disciplinaryForm.type' => 'required',
            'disciplinaryForm.description' => 'required',
            'disciplinaryForm.action_taken' => 'required',
        ])['disciplinaryForm'] + ['tenant_id' => TenantContext::id()]);

        $this->disciplinaryForm = ['guard_id' => '', 'occurred_on' => '', 'type' => 'warning', 'description' => '', 'action_taken' => ''];
        $this->activeForm = null;
        session()->flash('status', 'Disciplinary record saved.');
    }

    public function uploadDocument(FileUploadService $uploads): void
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        $data = $this->validate([
            'documentForm.guard_id' => 'required',
            'documentForm.type' => 'required',
            'documentForm.expires_at' => 'nullable|date',
            'documentFile' => 'required|file|max:10240',
        ]);

        $uploads->storeGuardDocument(
            TenantContext::id(),
            (int) $data['documentForm']['guard_id'],
            $data['documentForm']['type'],
            $data['documentFile'],
            $data['documentForm']['expires_at'] ?: null
        );

        $this->reset('documentFile');
        $this->documentForm = ['guard_id' => '', 'type' => 'license', 'expires_at' => ''];
        $this->activeForm = null;
        session()->flash('status', 'Document uploaded.');
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        return view('livewire.guards.guard-hr-records', [
            'guards' => Guard::where('tenant_id', $tenantId)->orderBy('first_name')->get(),
            'skills' => GuardSkill::with('assignedGuard')->where('tenant_id', $tenantId)->latest()->limit(50)->get(),
            'training' => TrainingRecord::with('assignedGuard')->where('tenant_id', $tenantId)->latest()->limit(50)->get(),
            'disciplinary' => DisciplinaryRecord::with('assignedGuard')->where('tenant_id', $tenantId)->latest()->limit(50)->get(),
            'documents' => GuardDocument::with('assignedGuard')->where('tenant_id', $tenantId)->latest()->limit(50)->get(),
        ])->layout('layouts.app');
    }
}
