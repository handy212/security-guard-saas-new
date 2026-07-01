<?php

namespace App\Livewire\Guards;

use App\Models\Branch;
use App\Models\DisciplinaryRecord;
use App\Models\Guard;
use App\Models\GuardCertification;
use App\Models\GuardSkill;
use App\Models\TrainingRecord;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\GuardVerificationService;
use App\Services\QrCodeService;
use App\Support\TenantContext;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class GuardProfile extends Component
{
    use WithFileUploads;

    #[Locked]
    public Guard $guard;

    public string $activeTab = 'overview';

    public array $overviewForm = [];

    public array $certForm = ['name' => '', 'issuer' => '', 'issued_at' => '', 'expires_at' => ''];

    public array $skillForm = ['skill' => '', 'level' => 'basic'];

    public array $trainingForm = ['course_name' => '', 'provider' => '', 'completed_on' => '', 'expires_on' => ''];

    public array $disciplinaryForm = ['occurred_on' => '', 'type' => 'warning', 'description' => '', 'action_taken' => ''];

    public array $documentForm = ['type' => 'id', 'expires_at' => ''];

    public $photoFile;

    public $documentFile;

    public function mount(Guard $guard): void
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        abort_unless((int) $guard->tenant_id === (int) TenantContext::id(), 404);

        $this->guard = $guard->load(['branch', 'user', 'documents', 'certifications', 'skills', 'trainingRecords', 'disciplinaryRecords']);
        $this->activeTab = request()->query('tab', 'overview');
        $this->loadOverviewForm();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function saveOverview(): void
    {
        $this->authorize('update', $this->guard);

        $data = $this->validate([
            'overviewForm.employee_number' => 'nullable',
            'overviewForm.first_name' => 'required',
            'overviewForm.last_name' => 'required',
            'overviewForm.phone' => 'nullable',
            'overviewForm.email' => 'nullable|email',
            'overviewForm.status' => 'required',
            'overviewForm.hourly_rate' => 'numeric',
            'overviewForm.license_number' => 'nullable',
            'overviewForm.license_expires_at' => 'nullable|date',
            'overviewForm.rank' => 'nullable',
            'overviewForm.branch_id' => 'nullable',
            'overviewForm.user_id' => 'nullable',
            'overviewForm.emergency_contact_name' => 'nullable',
            'overviewForm.emergency_contact_phone' => 'nullable',
            'overviewForm.show_current_assignment' => 'boolean',
        ])['overviewForm'];

        $data['branch_id'] = $data['branch_id'] ?: null;
        $data['user_id'] = $data['user_id'] ?: null;
        $data['license_expires_at'] = $data['license_expires_at'] ?: null;

        $this->guard->update($data);
        $this->guard->refresh();
        $this->loadOverviewForm();
    }

    public function uploadPhoto(FileUploadService $uploads): void
    {
        $this->authorize('update', $this->guard);
        $this->validate(['photoFile' => 'required|image|max:5120']);
        $path = $uploads->storeGuardPhoto(TenantContext::id(), $this->guard->id, $this->photoFile);
        $this->guard->update(['photo_path' => $path]);
        $this->reset('photoFile');
        $this->guard->refresh();
    }

    public function uploadDocument(FileUploadService $uploads): void
    {
        $this->authorize('update', $this->guard);
        $data = $this->validate([
            'documentForm.type' => 'required',
            'documentForm.expires_at' => 'nullable|date',
            'documentFile' => 'required|file|max:10240',
        ]);

        $uploads->storeGuardDocument(
            TenantContext::id(),
            $this->guard->id,
            $data['documentForm']['type'],
            $this->documentFile,
            $data['documentForm']['expires_at'] ?? null
        );

        $this->reset('documentFile');
        $this->guard->load('documents');
    }

    public function saveCertification(): void
    {
        $this->authorize('update', $this->guard);
        GuardCertification::create($this->validate([
            'certForm.name' => 'required',
            'certForm.issuer' => 'nullable',
            'certForm.issued_at' => 'nullable|date',
            'certForm.expires_at' => 'nullable|date',
        ])['certForm'] + ['tenant_id' => TenantContext::id(), 'guard_id' => $this->guard->id, 'status' => 'valid']);
        $this->certForm = ['name' => '', 'issuer' => '', 'issued_at' => '', 'expires_at' => ''];
        $this->guard->load('certifications');
    }

    public function deleteCertification(int $id): void
    {
        $this->authorize('update', $this->guard);
        GuardCertification::where('guard_id', $this->guard->id)->whereKey($id)->delete();
        $this->guard->load('certifications');
    }

    public function saveSkill(): void
    {
        $this->authorize('update', $this->guard);
        GuardSkill::create($this->validate([
            'skillForm.skill' => 'required',
            'skillForm.level' => 'required',
        ])['skillForm'] + ['tenant_id' => TenantContext::id(), 'guard_id' => $this->guard->id]);
        $this->skillForm = ['skill' => '', 'level' => 'basic'];
        $this->guard->load('skills');
    }

    public function saveTraining(): void
    {
        $this->authorize('update', $this->guard);
        TrainingRecord::create($this->validate([
            'trainingForm.course_name' => 'required',
            'trainingForm.provider' => 'nullable',
            'trainingForm.completed_on' => 'nullable|date',
            'trainingForm.expires_on' => 'nullable|date',
        ])['trainingForm'] + ['tenant_id' => TenantContext::id(), 'guard_id' => $this->guard->id, 'status' => 'completed']);
        $this->trainingForm = ['course_name' => '', 'provider' => '', 'completed_on' => '', 'expires_on' => ''];
        $this->guard->load('trainingRecords');
    }

    public function saveDisciplinary(): void
    {
        $this->authorize('update', $this->guard);
        DisciplinaryRecord::create($this->validate([
            'disciplinaryForm.occurred_on' => 'required|date',
            'disciplinaryForm.type' => 'required',
            'disciplinaryForm.description' => 'required',
            'disciplinaryForm.action_taken' => 'required',
        ])['disciplinaryForm'] + [
            'tenant_id' => TenantContext::id(),
            'guard_id' => $this->guard->id,
            'recorded_by' => auth()->id(),
        ]);
        $this->disciplinaryForm = ['occurred_on' => '', 'type' => 'warning', 'description' => '', 'action_taken' => ''];
        $this->guard->load('disciplinaryRecords');
    }

    public function submitForReview(GuardVerificationService $verification): void
    {
        $this->authorize('update', $this->guard);
        $verification->submitForReview($this->guard);
        $this->guard->refresh();
    }

    public function markVerified(GuardVerificationService $verification): void
    {
        $this->authorize('update', $this->guard);
        $this->resetErrorBag('verification');

        $this->guard->refresh();
        $this->guard->load(['documents', 'certifications']);

        $checklist = $verification->vettingChecklist($this->guard);

        if (! $checklist['ready']) {
            $missing = collect($checklist['items'])
                ->reject(fn (array $item) => $item['passed'])
                ->pluck('label')
                ->implode(', ');

            $this->addError('verification', "Complete these requirements first: {$missing}.");

            return;
        }

        $verification->markVerified($this->guard, auth()->id());
        $this->guard->refresh();
        session()->flash('status', 'Guard verified. QR code is now active.');
    }

    public function suspend(GuardVerificationService $verification): void
    {
        $this->authorize('update', $this->guard);
        $verification->suspend($this->guard);
        $this->guard->refresh();
    }

    public function regenerateToken(GuardVerificationService $verification): void
    {
        $this->authorize('update', $this->guard);

        if ($this->guard->verification_status !== 'verified') {
            $this->addError('verification', 'Mark this guard as verified before issuing a QR code.');

            return;
        }

        $verification->issueToken($this->guard);
        $this->guard->refresh();
        session()->flash('status', 'QR code regenerated.');
    }

    public function render(GuardVerificationService $verification, QrCodeService $qr)
    {
        $this->guard->load(['branch', 'user', 'documents', 'certifications', 'skills', 'trainingRecords', 'disciplinaryRecords']);

        $token = $this->guard->verification_status === 'verified'
            ? $this->guard->activeVerificationToken()
            : null;
        $verifyUrl = $token ? $verification->verificationUrl($token) : null;
        $qrSvg = $verifyUrl ? $qr->svg($verifyUrl, 100) : null;

        return view('livewire.guards.guard-profile', [
            'branches' => Branch::orderBy('name')->get(),
            'users' => User::where('tenant_id', TenantContext::id())->orderBy('name')->get(),
            'checklist' => $verification->vettingChecklist($this->guard),
            'verifyUrl' => $verifyUrl,
            'qrSvg' => $qrSvg,
            'lastScannedAt' => $token?->last_scanned_at,
            'tokenExpiresAt' => $token?->expires_at,
        ])->layout('layouts.app');
    }

    private function loadOverviewForm(): void
    {
        $this->overviewForm = $this->guard->only([
            'employee_number', 'first_name', 'last_name', 'phone', 'email', 'status',
            'hourly_rate', 'license_number', 'rank', 'branch_id', 'user_id',
            'emergency_contact_name', 'emergency_contact_phone', 'show_current_assignment',
        ]);
        $this->overviewForm['license_expires_at'] = $this->guard->license_expires_at?->format('Y-m-d') ?? '';
        $this->overviewForm['branch_id'] = $this->guard->branch_id ?? '';
        $this->overviewForm['user_id'] = $this->guard->user_id ?? '';
        $this->overviewForm['show_current_assignment'] = (bool) $this->guard->show_current_assignment;
    }
}
