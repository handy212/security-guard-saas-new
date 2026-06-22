<?php

namespace App\Livewire\Guards;

use App\Models\DisciplinaryRecord;
use App\Models\Guard;
use App\Models\GuardSkill;
use App\Models\TrainingRecord;
use App\Services\FileUploadService;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithFileUploads;

class GuardHrRecords extends Component
{
    use WithFileUploads;

    public array $skillForm = ['guard_id' => '', 'skill' => '', 'level' => 'basic'];

    public array $trainingForm = ['guard_id' => '', 'course_name' => '', 'completed_on' => '', 'expires_on' => ''];

    public array $disciplinaryForm = ['guard_id' => '', 'occurred_on' => '', 'type' => 'warning', 'description' => '', 'action_taken' => ''];

    public array $documentForm = ['guard_id' => '', 'type' => 'license', 'expires_at' => ''];

    public $documentFile;

    public function saveSkill(): void
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        GuardSkill::create($this->validate([
            'skillForm.guard_id' => 'required',
            'skillForm.skill' => 'required',
            'skillForm.level' => 'required',
        ])['skillForm'] + ['tenant_id' => TenantContext::id()]);
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
            $data['documentForm']['expires_at'] ?? null
        );

        $this->reset('documentFile');
    }

    public function render()
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);

        return view('livewire.guards.guard-hr-records', [
            'guards' => Guard::orderBy('first_name')->get(),
            'skills' => GuardSkill::with('assignedGuard')->latest()->limit(50)->get(),
            'training' => TrainingRecord::latest()->limit(50)->get(),
            'disciplinary' => DisciplinaryRecord::latest()->limit(50)->get(),
        ])->layout('layouts.app');
    }
}
