<?php

namespace App\Livewire\Guards;

use App\Enums\GuardDocumentType;
use App\Enums\GuardDutyType;
use App\Models\Branch;
use App\Models\DisciplinaryRecord;
use App\Models\Guard;
use App\Models\GuardAvailability;
use App\Models\GuardCertification;
use App\Models\GuardNote;
use App\Models\GuardReminder;
use App\Models\GuardSiteAssignment;
use App\Models\GuardSkill;
use App\Models\Site;
use App\Models\TrainingRecord;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\GuardIdCardPresenter;
use App\Services\GuardOverviewService;
use App\Services\GuardVerificationService;
use App\Services\QrCodeService;
use App\Support\TenantContext;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class GuardProfile extends Component
{
    use WithFileUploads;

    private const TABS = [
        'overview', 'profile', 'availability', 'kpis', 'licenses', 'notes', 'reminders',
        'files', 'sites', 'skills', 'disciplinary', 'department', 'settings',
    ];

    #[Locked]
    public Guard $guard;

    #[Url(as: 'tab')]
    public string $activeTab = 'overview';

    public string $idCardPreviewSide = 'front';

    public array $profileForm = [];

    public array $licenseForm = [];

    public array $availabilityForm = ['weekday' => 1, 'starts_at' => '08:00', 'ends_at' => '17:00', 'is_available' => true];

    public array $certForm = ['name' => '', 'issuer' => '', 'issued_at' => '', 'expires_at' => ''];

    public array $skillForm = ['skill' => '', 'skill_custom' => '', 'level' => 'basic'];

    public array $trainingForm = ['course_name' => '', 'course_custom' => '', 'provider' => '', 'completed_on' => '', 'expires_on' => ''];

    public array $disciplinaryForm = ['occurred_on' => '', 'type' => 'warning', 'description' => '', 'action_taken' => ''];

    public array $noteForm = ['body' => '', 'is_internal' => true];

    public array $reminderForm = ['title' => '', 'due_at' => ''];

    public array $siteAssignForm = ['site_id' => '', 'is_primary' => false, 'notes' => ''];

    public array $departmentForm = [];

    public array $settingsForm = [];

    public array $documentForm = ['type' => 'id', 'expires_at' => ''];

    public $photoFile;

    public $documentFile;

    public ?int $previewDocumentId = null;

    public ?int $editingAvailabilityId = null;

    public function mount(Guard $guard): void
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        abort_unless((int) $guard->tenant_id === (int) TenantContext::id(), 404);

        $this->guard = $guard;
        $this->loadProfileForm();
        $this->loadLicenseForm();
        $this->loadDepartmentForm();
        $this->loadSettingsForm();

        if (! in_array($this->activeTab, self::TABS, true)) {
            $this->activeTab = 'overview';
        }
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->activeTab = $tab;
        }
    }

    public function setIdCardPreviewSide(string $side): void
    {
        $this->idCardPreviewSide = in_array($side, ['front', 'back'], true) ? $side : 'front';
    }

    public function saveProfile(): void
    {
        $this->authorize('update', $this->guard);

        $data = $this->validate([
            'profileForm.employee_number' => 'nullable',
            'profileForm.first_name' => 'required',
            'profileForm.last_name' => 'required',
            'profileForm.phone' => 'nullable',
            'profileForm.email' => 'nullable|email',
            'profileForm.status' => 'required',
            'profileForm.monthly_rate' => 'numeric',
            'profileForm.user_id' => 'nullable',
            'profileForm.emergency_contact_name' => 'nullable',
            'profileForm.emergency_contact_phone' => 'nullable',
            'profileForm.hire_date' => 'nullable|date',
        ])['profileForm'];

        $data['user_id'] = $data['user_id'] ?: null;
        $data['hire_date'] = $data['hire_date'] ?: null;

        $this->guard->update($data);
        $this->guard->refresh();
        $this->loadProfileForm();
        session()->flash('status', 'Profile updated.');
    }

    public function saveLicense(): void
    {
        $this->authorize('update', $this->guard);

        $data = $this->validate([
            'licenseForm.license_number' => 'nullable|string|max:120',
            'licenseForm.license_expires_at' => 'nullable|date',
        ])['licenseForm'];

        $data['license_expires_at'] = $data['license_expires_at'] ?: null;

        $this->guard->update($data);
        $this->guard->refresh();
        $this->loadLicenseForm();
        session()->flash('status', 'License details saved.');
    }

    public function saveDepartment(): void
    {
        $this->authorize('update', $this->guard);

        $data = $this->validate([
            'departmentForm.branch_id' => 'nullable',
            'departmentForm.rank' => 'nullable|string|max:120',
            'departmentForm.duty_type' => 'required|in:guardian,dispatch',
        ])['departmentForm'];

        $data['branch_id'] = $data['branch_id'] ?: null;

        $this->guard->update($data);
        $this->guard->refresh()->load('branch');
        $this->loadDepartmentForm();
        session()->flash('status', 'Department updated.');
    }

    public function saveSettings(): void
    {
        $this->authorize('update', $this->guard);

        $data = $this->validate([
            'settingsForm.show_current_assignment' => 'boolean',
            'settingsForm.notify_on_shift_change' => 'boolean',
            'settingsForm.allow_open_shift_bids' => 'boolean',
            'settingsForm.preferred_contact_method' => 'required|in:phone,email,sms',
        ])['settingsForm'];

        $this->guard->update([
            'settings' => $data,
            'show_current_assignment' => (bool) $data['show_current_assignment'],
        ]);
        $this->guard->refresh();
        $this->loadSettingsForm();
        session()->flash('status', 'Settings saved.');
    }

    public function uploadPhoto(FileUploadService $uploads): void
    {
        $this->authorize('update', $this->guard);
        $this->validate(['photoFile' => 'required|image|max:5120']);
        $path = $uploads->storeGuardPhoto(TenantContext::id(), $this->guard->id, $this->photoFile);
        $this->guard->update(['photo_path' => $path]);
        $this->reset('photoFile');
        $this->guard->refresh();
        session()->flash('status', 'Photo uploaded.');
    }

    public function saveAvailability(): void
    {
        $this->authorize('update', $this->guard);

        $data = $this->validate([
            'availabilityForm.weekday' => 'required|integer|min:0|max:6',
            'availabilityForm.starts_at' => 'required|date_format:H:i',
            'availabilityForm.ends_at' => 'required|date_format:H:i',
            'availabilityForm.is_available' => 'boolean',
        ])['availabilityForm'];

        $payload = $data + ['tenant_id' => TenantContext::id(), 'guard_id' => $this->guard->id];

        if ($this->editingAvailabilityId) {
            GuardAvailability::query()
                ->where('guard_id', $this->guard->id)
                ->findOrFail($this->editingAvailabilityId)
                ->update($payload);
        } else {
            GuardAvailability::create($payload);
        }

        $this->resetAvailabilityForm();
        $this->reloadGuard();
        session()->flash('status', 'Availability saved.');
    }

    public function editAvailability(int $id): void
    {
        $availability = GuardAvailability::query()->where('guard_id', $this->guard->id)->findOrFail($id);
        $this->editingAvailabilityId = $availability->id;
        $this->availabilityForm = [
            'weekday' => (int) $availability->weekday,
            'starts_at' => substr((string) $availability->starts_at, 0, 5),
            'ends_at' => substr((string) $availability->ends_at, 0, 5),
            'is_available' => (bool) $availability->is_available,
        ];
    }

    public function deleteAvailability(int $id): void
    {
        $this->authorize('update', $this->guard);
        GuardAvailability::query()->where('guard_id', $this->guard->id)->whereKey($id)->delete();
        $this->reloadGuard();
    }

    public function addNote(): void
    {
        $this->authorize('update', $this->guard);
        $data = $this->validate(['noteForm.body' => 'required|string|max:5000', 'noteForm.is_internal' => 'boolean'])['noteForm'];
        GuardNote::create($data + ['tenant_id' => TenantContext::id(), 'guard_id' => $this->guard->id, 'user_id' => auth()->id()]);
        $this->noteForm = ['body' => '', 'is_internal' => true];
        $this->reloadGuard();
        session()->flash('status', 'Note added.');
    }

    public function deleteNote(int $id): void
    {
        $this->authorize('update', $this->guard);
        GuardNote::query()->where('guard_id', $this->guard->id)->whereKey($id)->delete();
        $this->reloadGuard();
    }

    public function addReminder(): void
    {
        $this->authorize('update', $this->guard);
        $data = $this->validate([
            'reminderForm.title' => 'required|string|max:255',
            'reminderForm.due_at' => 'required|date',
        ])['reminderForm'];

        GuardReminder::create($data + [
            'tenant_id' => TenantContext::id(),
            'guard_id' => $this->guard->id,
            'user_id' => auth()->id(),
        ]);

        $this->reminderForm = ['title' => '', 'due_at' => ''];
        $this->reloadGuard();
        session()->flash('status', 'Reminder added.');
    }

    public function completeReminder(int $id): void
    {
        $this->authorize('update', $this->guard);
        GuardReminder::query()->where('guard_id', $this->guard->id)->whereKey($id)->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
        $this->reloadGuard();
    }

    public function deleteReminder(int $id): void
    {
        $this->authorize('update', $this->guard);
        GuardReminder::query()->where('guard_id', $this->guard->id)->whereKey($id)->delete();
        $this->reloadGuard();
    }

    public function assignSite(): void
    {
        $this->authorize('update', $this->guard);
        $data = $this->validate([
            'siteAssignForm.site_id' => 'required|exists:sites,id',
            'siteAssignForm.is_primary' => 'boolean',
            'siteAssignForm.notes' => 'nullable|string|max:500',
        ])['siteAssignForm'];

        if ($data['is_primary']) {
            GuardSiteAssignment::query()
                ->where('guard_id', $this->guard->id)
                ->update(['is_primary' => false]);
        }

        GuardSiteAssignment::updateOrCreate(
            ['guard_id' => $this->guard->id, 'site_id' => $data['site_id']],
            [
                'tenant_id' => TenantContext::id(),
                'is_primary' => (bool) $data['is_primary'],
                'notes' => $data['notes'] ?: null,
            ]
        );

        $this->siteAssignForm = ['site_id' => '', 'is_primary' => false, 'notes' => ''];
        $this->reloadGuard();
        session()->flash('status', 'Site assigned.');
    }

    public function removeSiteAssignment(int $id): void
    {
        $this->authorize('update', $this->guard);
        GuardSiteAssignment::query()->where('guard_id', $this->guard->id)->whereKey($id)->delete();
        $this->reloadGuard();
    }

    public function uploadDocument(FileUploadService $uploads): void
    {
        $this->authorize('update', $this->guard);
        $data = $this->validate([
            'documentForm.type' => ['required', Rule::in(array_column(GuardDocumentType::cases(), 'value'))],
            'documentForm.expires_at' => 'nullable|date',
            'documentFile' => 'required|file|max:10240',
        ]);

        $uploads->storeGuardDocument(
            TenantContext::id(),
            $this->guard->id,
            $data['documentForm']['type'],
            $this->documentFile,
            $data['documentForm']['expires_at'] ?: null
        );

        $this->reset('documentFile');
        $this->reloadGuard();
        session()->flash('status', 'Document uploaded.');
    }

    public function openDocumentPreview(int $documentId): void
    {
        $this->authorize('update', $this->guard);
        abort_unless($this->guard->documents()->whereKey($documentId)->exists(), 404);
        $this->previewDocumentId = $documentId;
    }

    public function closeDocumentPreview(): void
    {
        $this->previewDocumentId = null;
    }

    public function saveCertification(): void
    {
        $this->authorize('update', $this->guard);
        $data = $this->validate([
            'certForm.name' => 'required',
            'certForm.issuer' => 'nullable',
            'certForm.issued_at' => 'nullable|date',
            'certForm.expires_at' => 'nullable|date',
        ])['certForm'];
        $data['issued_at'] = $data['issued_at'] ?: null;
        $data['expires_at'] = $data['expires_at'] ?: null;

        GuardCertification::create($data + ['tenant_id' => TenantContext::id(), 'guard_id' => $this->guard->id, 'status' => 'valid']);
        $this->certForm = ['name' => '', 'issuer' => '', 'issued_at' => '', 'expires_at' => ''];
        $this->reloadGuard();
        session()->flash('status', 'Certification added.');
    }

    public function deleteCertification(int $id): void
    {
        $this->authorize('update', $this->guard);
        GuardCertification::where('guard_id', $this->guard->id)->whereKey($id)->delete();
        $this->reloadGuard();
    }

    public function saveSkill(): void
    {
        $this->authorize('update', $this->guard);
        $data = $this->validate([
            'skillForm.skill' => 'required',
            'skillForm.skill_custom' => 'required_if:skillForm.skill,_other',
            'skillForm.level' => 'required',
        ])['skillForm'];

        GuardSkill::create([
            'skill' => $data['skill'] === '_other' ? trim($data['skill_custom']) : $data['skill'],
            'level' => $data['level'],
            'tenant_id' => TenantContext::id(),
            'guard_id' => $this->guard->id,
        ]);
        $this->skillForm = ['skill' => '', 'skill_custom' => '', 'level' => 'basic'];
        $this->reloadGuard();
        session()->flash('status', 'Skill added.');
    }

    public function deleteSkill(int $id): void
    {
        $this->authorize('update', $this->guard);
        GuardSkill::where('guard_id', $this->guard->id)->whereKey($id)->delete();
        $this->reloadGuard();
    }

    public function saveTraining(): void
    {
        $this->authorize('update', $this->guard);
        $data = $this->validate([
            'trainingForm.course_name' => 'required',
            'trainingForm.course_custom' => 'required_if:trainingForm.course_name,_other',
            'trainingForm.provider' => 'nullable',
            'trainingForm.completed_on' => 'nullable|date',
            'trainingForm.expires_on' => 'nullable|date',
        ])['trainingForm'];
        $data['course_name'] = $data['course_name'] === '_other' ? trim($data['course_custom']) : $data['course_name'];
        unset($data['course_custom']);
        $data['completed_on'] = $data['completed_on'] ?: null;
        $data['expires_on'] = $data['expires_on'] ?: null;

        TrainingRecord::create($data + ['tenant_id' => TenantContext::id(), 'guard_id' => $this->guard->id, 'status' => 'completed']);
        $this->trainingForm = ['course_name' => '', 'course_custom' => '', 'provider' => '', 'completed_on' => '', 'expires_on' => ''];
        $this->reloadGuard();
        session()->flash('status', 'Training record added.');
    }

    public function saveDisciplinary(): void
    {
        $this->authorize('update', $this->guard);
        $data = $this->validate([
            'disciplinaryForm.occurred_on' => 'required|date',
            'disciplinaryForm.type' => 'required|in:warning,reprimand,suspension,termination',
            'disciplinaryForm.description' => 'required|string|max:2000',
            'disciplinaryForm.action_taken' => 'required|string|max:1000',
        ])['disciplinaryForm'];

        DisciplinaryRecord::create($data + [
            'tenant_id' => TenantContext::id(),
            'guard_id' => $this->guard->id,
            'recorded_by' => auth()->id(),
        ]);

        $this->disciplinaryForm = ['occurred_on' => '', 'type' => 'warning', 'description' => '', 'action_taken' => ''];
        $this->reloadGuard();
        session()->flash('status', 'Disciplinary record added.');
    }

    public function deleteDisciplinary(int $id): void
    {
        $this->authorize('update', $this->guard);
        DisciplinaryRecord::where('guard_id', $this->guard->id)->whereKey($id)->delete();
        $this->reloadGuard();
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
        session()->flash('status', 'Verification suspended. Existing QR codes still scan and show suspended status.');
    }

    public function reinstate(GuardVerificationService $verification): void
    {
        $this->authorize('update', $this->guard);
        $verification->reinstate($this->guard);
        $this->guard->refresh();
        session()->flash('status', 'Verification reinstated. Existing QR code remains valid.');
    }

    public function issueQrToken(GuardVerificationService $verification): void
    {
        $this->authorize('update', $this->guard);

        if (! in_array($this->guard->verification_status, ['verified', 'suspended'], true)) {
            $this->addError('verification', 'Mark this guard as verified before issuing a QR code.');

            return;
        }

        if ($this->guard->verification_status === 'suspended') {
            $this->addError('verification', 'Reinstate this guard before issuing a new QR code.');

            return;
        }

        $verification->ensureToken($this->guard);
        $this->guard->refresh();
        session()->flash('status', 'QR code issued.');
    }

    public function rotateQrToken(GuardVerificationService $verification): void
    {
        $this->authorize('update', $this->guard);

        if ($this->guard->verification_status !== 'verified') {
            $this->addError('verification', 'Mark this guard as verified before rotating a QR code.');

            return;
        }

        $verification->rotateToken($this->guard);
        $this->guard->refresh();
        session()->flash('status', 'QR code rotated. Previously printed ID cards will no longer verify until reprinted.');
    }

    public function render(
        GuardVerificationService $verification,
        QrCodeService $qr,
        GuardIdCardPresenter $presenter,
        GuardOverviewService $overview,
    ) {
        $this->reloadGuard();
        $tenantId = TenantContext::id();
        $stats = $overview->stats($this->guard, $tenantId);
        $kpiMetrics = $overview->kpiMetrics($this->guard, $tenantId);

        $token = in_array($this->guard->verification_status, ['verified', 'suspended'], true)
            ? $this->guard->activeVerificationToken()
            : null;
        $verifyUrl = $token ? $verification->verificationUrl($token) : null;
        $qrSvg = $verifyUrl ? $qr->svg($verifyUrl, 56) : null;
        $checklist = $verification->vettingChecklist($this->guard);
        $idCardEligibility = $verification->idCardEligibility($this->guard);
        $checklistIncomplete = collect($checklist['items'])->reject(fn (array $item) => $item['passed'])->count();
        $idCardBrand = $presenter->branding($this->guard->tenant, $this->guard->branch);
        $idCardData = $presenter->cardData($this->guard, $token);
        $photoUrl = $this->guard->photo_path ? route('files.guard-photo', $this->guard) : null;
        $previewDocument = $this->previewDocumentId
            ? $this->guard->documents->firstWhere('id', $this->previewDocumentId)
            : null;

        $profileTabs = [
            'overview' => ['label' => 'Overview', 'hint' => 'Summary and KYG', 'group' => 'Summary'],
            'profile' => ['label' => 'Profile', 'hint' => 'Photo and personal details', 'group' => 'Summary'],
            'department' => ['label' => 'Department', 'hint' => 'Branch and rank', 'group' => 'Summary'],
            'settings' => ['label' => 'Settings', 'hint' => 'Guard preferences', 'group' => 'Summary'],
            'availability' => ['label' => 'Availability', 'hint' => 'Weekly schedule', 'group' => 'Work', 'badge' => $this->badge($this->guard->availabilities->count())],
            'sites' => ['label' => 'Assign Sites', 'hint' => 'Site assignments', 'group' => 'Work', 'badge' => $this->badge($this->guard->siteAssignments->count())],
            'kpis' => ['label' => 'KPIs', 'hint' => 'Performance metrics', 'group' => 'Work'],
            'licenses' => ['label' => 'Licenses', 'hint' => 'License and certifications', 'group' => 'Qualifications', 'badge' => $this->badge($this->guard->certifications->count())],
            'skills' => ['label' => 'Skill Set', 'hint' => 'Skills and competencies', 'group' => 'Qualifications', 'badge' => $this->badge($this->guard->skills->count())],
            'files' => ['label' => 'Files', 'hint' => 'ID and clearance docs', 'group' => 'Qualifications', 'badge' => $this->badge($this->guard->documents->count())],
            'notes' => ['label' => 'Notes', 'hint' => 'Internal notes', 'group' => 'HR', 'badge' => $this->badge($this->guard->notes->count())],
            'disciplinary' => ['label' => 'Disciplinary', 'hint' => 'Warnings and actions', 'group' => 'HR', 'badge' => $this->badge($this->guard->disciplinaryRecords->count())],
            'reminders' => ['label' => 'Reminders', 'hint' => 'Follow-ups', 'group' => 'HR', 'badge' => $this->badge($this->guard->reminders->where('is_completed', false)->count())],
        ];

        if ($checklistIncomplete > 0) {
            $profileTabs['overview']['badge'] = (string) $checklistIncomplete;
        }

        return view('livewire.guards.guard-profile', [
            'dutyTypes' => GuardDutyType::options(),
            'branches' => Branch::orderBy('name')->get(),
            'users' => User::where('tenant_id', $tenantId)->orderBy('name')->get(),
            'sites' => Site::where('tenant_id', $tenantId)->orderBy('name')->get(),
            'checklist' => $checklist,
            'profileTabs' => $profileTabs,
            'stats' => $stats,
            'kpiMetrics' => $kpiMetrics,
            'verifyUrl' => $verifyUrl,
            'qrSvg' => $qrSvg,
            'lastScannedAt' => $token?->last_scanned_at,
            'tokenExpiresAt' => $token?->expires_at,
            'idCardEligibility' => $idCardEligibility,
            'idCardBrand' => $idCardBrand,
            'idCardData' => $idCardData,
            'photoUrl' => $photoUrl,
            'documentTypes' => GuardDocumentType::options(),
            'skillOptions' => config('guard_hr.skills'),
            'trainingCourses' => config('guard_hr.training_courses'),
            'skillLevels' => config('guard_hr.skill_levels'),
            'weekdays' => config('guard_profile.weekdays'),
            'previewDocument' => $previewDocument,
        ])->layout('layouts.app');
    }

    private function reloadGuard(): void
    {
        $this->guard->load([
            'branch',
            'user',
            'tenant',
            'documents',
            'certifications',
            'skills',
            'trainingRecords',
            'disciplinaryRecords' => fn ($q) => $q->latest('occurred_on'),
            'availabilities' => fn ($q) => $q->orderBy('weekday')->orderBy('starts_at'),
            'notes' => fn ($q) => $q->latest(),
            'notes.author',
            'reminders' => fn ($q) => $q->orderBy('due_at'),
            'siteAssignments.site.clientAccount',
        ]);
    }

    private function loadProfileForm(): void
    {
        $this->profileForm = $this->guard->only([
            'employee_number', 'first_name', 'last_name', 'phone', 'email', 'status',
            'monthly_rate', 'user_id', 'emergency_contact_name', 'emergency_contact_phone', 'hire_date',
        ]);
        $this->profileForm['user_id'] = $this->guard->user_id ?? '';
        $this->profileForm['hire_date'] = $this->guard->hire_date?->format('Y-m-d') ?? '';
    }

    private function loadLicenseForm(): void
    {
        $this->licenseForm = [
            'license_number' => $this->guard->license_number ?? '',
            'license_expires_at' => $this->guard->license_expires_at?->format('Y-m-d') ?? '',
        ];
    }

    private function loadDepartmentForm(): void
    {
        $this->departmentForm = [
            'branch_id' => $this->guard->branch_id ?? '',
            'rank' => $this->guard->rank ?? '',
            'duty_type' => $this->guard->duty_type instanceof GuardDutyType
                ? $this->guard->duty_type->value
                : ($this->guard->duty_type ?: 'guardian'),
        ];
    }

    private function loadSettingsForm(): void
    {
        $this->settingsForm = $this->guard->resolvedSettings();
    }

    private function resetAvailabilityForm(): void
    {
        $this->editingAvailabilityId = null;
        $this->availabilityForm = ['weekday' => 1, 'starts_at' => '08:00', 'ends_at' => '17:00', 'is_available' => true];
    }

    private function badge(int $count): ?string
    {
        return $count > 0 ? (string) $count : null;
    }
}
