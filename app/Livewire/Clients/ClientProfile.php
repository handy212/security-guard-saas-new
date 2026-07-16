<?php

namespace App\Livewire\Clients;

use App\Models\ClientAccount;
use App\Models\ClientContact;
use App\Models\ClientDocument;
use App\Models\ClientNote;
use App\Models\ClientReportSchedule;
use App\Models\Site;
use App\Models\User;
use App\Services\ClientOverviewService;
use App\Services\FileUploadService;
use App\Services\TenantFileStorageService;
use App\Services\TenantRoleProvisioner;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class ClientProfile extends Component
{
    use WithFileUploads;

    private const TABS = [
        'overview', 'profile', 'contacts', 'notes', 'files', 'sites', 'portal', 'users', 'reports',
    ];

    #[Locked]
    public ClientAccount $clientAccount;

    #[Url(as: 'tab')]
    public string $activeTab = 'overview';

    public array $profileForm = [];

    public array $contactForm = ['name' => '', 'email' => '', 'phone' => '', 'role' => ''];

    public array $noteForm = ['body' => '', 'is_internal' => true];

    public array $documentForm = ['title' => '', 'document_type' => 'general', 'expires_on' => '', 'client_visible' => false];

    public array $siteForm = [
        'name' => '', 'address' => '', 'latitude' => '', 'longitude' => '',
        'geofence_radius_meters' => 150, 'status' => 'active', 'instructions' => '',
    ];

    public array $portalForm = ['portal_enabled' => false, 'portal_welcome_message' => ''];

    public array $userForm = ['name' => '', 'email' => '', 'password' => ''];

    public array $reportForm = [
        'report_type' => 'daily_activity',
        'frequency' => 'weekly',
        'recipients' => '',
        'is_active' => true,
    ];

    public $documentFile;

    public ?int $editingContactId = null;

    public ?int $editingNoteId = null;

    public bool $showSiteForm = false;

    public ?int $editingSiteId = null;

    public function mount(ClientAccount $clientAccount): void
    {
        $this->authorize('view', $clientAccount);
        abort_unless((int) $clientAccount->tenant_id === (int) TenantContext::id(), 404);

        $this->clientAccount = $clientAccount;
        $this->loadProfileForm();
        $this->loadPortalForm();

        if (! in_array($this->activeTab, self::TABS, true)) {
            $this->activeTab = 'overview';
        }
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, self::TABS, true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    public function closeSiteForm(): void
    {
        $this->showSiteForm = false;
        $this->editingSiteId = null;
    }

    public function saveProfile(): void
    {
        $this->authorize('update', $this->clientAccount);

        $data = $this->validate([
            'profileForm.name' => 'required|string|max:255',
            'profileForm.industry' => 'nullable|string|max:120',
            'profileForm.email' => 'nullable|email|max:255',
            'profileForm.phone' => 'nullable|string|max:40',
            'profileForm.address' => 'nullable|string|max:500',
            'profileForm.latitude' => 'nullable|numeric',
            'profileForm.longitude' => 'nullable|numeric',
            'profileForm.status' => 'required|in:active,inactive',
            'profileForm.default_monthly_rate' => 'numeric|min:0',
        ])['profileForm'];

        $data['latitude'] = $data['latitude'] !== '' && $data['latitude'] !== null ? $data['latitude'] : null;
        $data['longitude'] = $data['longitude'] !== '' && $data['longitude'] !== null ? $data['longitude'] : null;

        $this->clientAccount->update($data);
        $this->clientAccount->refresh();
        $this->loadProfileForm();
        session()->flash('status', 'Client profile updated.');
    }

    public function saveContact(): void
    {
        $this->authorize('update', $this->clientAccount);

        $data = $this->validate([
            'contactForm.name' => 'required|string|max:255',
            'contactForm.email' => 'nullable|email|max:255',
            'contactForm.phone' => 'nullable|string|max:40',
            'contactForm.role' => 'nullable|string|max:120',
        ])['contactForm'];

        if ($this->editingContactId) {
            ClientContact::query()
                ->where('client_account_id', $this->clientAccount->id)
                ->findOrFail($this->editingContactId)
                ->update($data);
            session()->flash('status', 'Contact updated.');
        } else {
            ClientContact::create($data + [
                'tenant_id' => TenantContext::id(),
                'client_account_id' => $this->clientAccount->id,
            ]);
            session()->flash('status', 'Contact added.');
        }

        $this->resetContactForm();
        $this->clientAccount->load('contacts');
    }

    public function editContact(int $contactId): void
    {
        $this->authorize('update', $this->clientAccount);

        $contact = ClientContact::query()
            ->where('client_account_id', $this->clientAccount->id)
            ->findOrFail($contactId);

        $this->editingContactId = $contact->id;
        $this->contactForm = $contact->only(['name', 'email', 'phone', 'role']);
    }

    public function cancelContactEdit(): void
    {
        $this->resetContactForm();
    }

    public function addContact(): void
    {
        $this->saveContact();
    }

    public function deleteContact(int $contactId): void
    {
        $this->authorize('update', $this->clientAccount);

        ClientContact::query()
            ->where('client_account_id', $this->clientAccount->id)
            ->whereKey($contactId)
            ->delete();

        $this->clientAccount->load('contacts');
    }

    public function addNote(): void
    {
        $this->authorize('update', $this->clientAccount);

        $data = $this->validate([
            'noteForm.body' => 'required|string|max:5000',
            'noteForm.is_internal' => 'boolean',
        ])['noteForm'];

        if ($this->editingNoteId) {
            ClientNote::query()
                ->where('client_account_id', $this->clientAccount->id)
                ->findOrFail($this->editingNoteId)
                ->update($data);
            session()->flash('status', 'Note updated.');
        } else {
            ClientNote::create($data + [
                'tenant_id' => TenantContext::id(),
                'client_account_id' => $this->clientAccount->id,
                'user_id' => auth()->id(),
            ]);
            session()->flash('status', 'Note added.');
        }

        $this->resetNoteForm();
        $this->clientAccount->load('notes.author');
    }

    public function editNote(int $noteId): void
    {
        $this->authorize('update', $this->clientAccount);

        $note = ClientNote::query()
            ->where('client_account_id', $this->clientAccount->id)
            ->findOrFail($noteId);

        $this->editingNoteId = $note->id;
        $this->noteForm = $note->only(['body', 'is_internal']);
    }

    public function deleteNote(int $noteId): void
    {
        $this->authorize('update', $this->clientAccount);

        ClientNote::query()
            ->where('client_account_id', $this->clientAccount->id)
            ->whereKey($noteId)
            ->delete();

        $this->clientAccount->load('notes.author');
    }

    public function uploadDocument(FileUploadService $uploads): void
    {
        $this->authorize('update', $this->clientAccount);

        $data = $this->validate([
            'documentForm.title' => 'required|string|max:255',
            'documentForm.document_type' => 'required|string|max:80',
            'documentForm.expires_on' => 'nullable|date',
            'documentForm.client_visible' => 'boolean',
            'documentFile' => 'required|file|max:10240',
        ]);

        $path = $uploads->storeClientDocument(
            TenantContext::id(),
            $this->clientAccount->id,
            $this->documentFile,
        );

        ClientDocument::create([
            'tenant_id' => TenantContext::id(),
            'client_account_id' => $this->clientAccount->id,
            'title' => $data['documentForm']['title'],
            'document_type' => $data['documentForm']['document_type'],
            'file_path' => $path,
            'expires_on' => $data['documentForm']['expires_on'] ?: null,
            'client_visible' => (bool) $data['documentForm']['client_visible'],
        ]);

        $this->documentForm = ['title' => '', 'document_type' => 'general', 'expires_on' => '', 'client_visible' => false];
        $this->reset('documentFile');
        $this->clientAccount->load('documents');
        session()->flash('status', 'File uploaded.');
    }

    public function deleteDocument(int $documentId, TenantFileStorageService $storage): void
    {
        $this->authorize('update', $this->clientAccount);

        $document = ClientDocument::query()
            ->where('client_account_id', $this->clientAccount->id)
            ->findOrFail($documentId);

        $storage->delete($document->file_path);
        $document->delete();

        $this->clientAccount->load('documents');
        session()->flash('status', 'File deleted.');
    }

    public function openSiteForm(?int $siteId = null): void
    {
        $this->authorize('update', $this->clientAccount);
        $this->showSiteForm = true;
        $this->editingSiteId = $siteId;

        if ($siteId) {
            $site = Site::query()
                ->where('client_account_id', $this->clientAccount->id)
                ->findOrFail($siteId);

            $this->siteForm = [
                'name' => $site->name,
                'address' => $site->address ?? '',
                'latitude' => $site->latitude ?? '',
                'longitude' => $site->longitude ?? '',
                'geofence_radius_meters' => $site->geofence_radius_meters ?? 150,
                'status' => $site->status,
                'instructions' => $site->instructions ?? '',
            ];
        } else {
            $this->siteForm = [
                'name' => '', 'address' => '', 'latitude' => '', 'longitude' => '',
                'geofence_radius_meters' => 150, 'status' => 'active', 'instructions' => '',
            ];
        }
    }

    public function saveSite(): void
    {
        $this->authorize('update', $this->clientAccount);

        $data = $this->validate([
            'siteForm.name' => 'required|string|max:255',
            'siteForm.address' => 'nullable|string|max:500',
            'siteForm.latitude' => 'nullable|numeric',
            'siteForm.longitude' => 'nullable|numeric',
            'siteForm.geofence_radius_meters' => 'integer|min:10',
            'siteForm.status' => 'required|in:active,inactive',
            'siteForm.instructions' => 'nullable|string|max:2000',
        ])['siteForm'];

        $data['latitude'] = $data['latitude'] !== '' ? $data['latitude'] : null;
        $data['longitude'] = $data['longitude'] !== '' ? $data['longitude'] : null;

        if ($this->editingSiteId) {
            Site::query()
                ->where('client_account_id', $this->clientAccount->id)
                ->findOrFail($this->editingSiteId)
                ->update($data);
        } else {
            Site::create($data + [
                'tenant_id' => TenantContext::id(),
                'client_account_id' => $this->clientAccount->id,
            ]);
        }

        $this->editingSiteId = null;
        $this->showSiteForm = false;
        $this->clientAccount->load('sites');
        session()->flash('status', 'Site saved.');
    }

    public function deleteSite(int $siteId): void
    {
        $this->authorize('update', $this->clientAccount);

        Site::query()
            ->where('client_account_id', $this->clientAccount->id)
            ->whereKey($siteId)
            ->delete();

        $this->clientAccount->load('sites');
    }

    public function savePortal(): void
    {
        $this->authorize('update', $this->clientAccount);

        $data = $this->validate([
            'portalForm.portal_enabled' => 'boolean',
            'portalForm.portal_welcome_message' => 'nullable|string|max:1000',
        ])['portalForm'];

        $this->clientAccount->update($data);
        $this->clientAccount->refresh();
        $this->loadPortalForm();
        session()->flash('status', 'Portal settings saved.');
    }

    public function invitePortalUser(): void
    {
        $this->authorize('update', $this->clientAccount);

        if (! $this->clientAccount->portal_enabled) {
            $this->addError('userForm.email', 'Enable the client portal before creating portal users.');

            return;
        }

        $data = $this->validate([
            'userForm.name' => 'required|string|max:255',
            'userForm.email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->where(fn ($q) => $q->where('tenant_id', TenantContext::id())),
            ],
            'userForm.password' => ['required', 'string', Password::min(12)],
        ])['userForm'];

        $user = User::create([
            'tenant_id' => TenantContext::id(),
            'client_account_id' => $this->clientAccount->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => 'active',
        ]);

        app(TenantRoleProvisioner::class)->assignRole($user, 'client');

        $this->userForm = ['name' => '', 'email' => '', 'password' => ''];
        $this->clientAccount->load('portalUsers');
        session()->flash('status', "Portal user {$user->email} created.");
    }

    public function deactivatePortalUser(int $userId): void
    {
        $this->authorize('update', $this->clientAccount);

        User::query()
            ->where('client_account_id', $this->clientAccount->id)
            ->whereKey($userId)
            ->update(['status' => 'inactive']);

        $this->clientAccount->load('portalUsers');
        session()->flash('status', 'Portal user deactivated.');
    }

    public function reactivatePortalUser(int $userId): void
    {
        $this->authorize('update', $this->clientAccount);

        User::query()
            ->where('client_account_id', $this->clientAccount->id)
            ->whereKey($userId)
            ->update(['status' => 'active']);

        $this->clientAccount->load('portalUsers');
        session()->flash('status', 'Portal user reactivated.');
    }

    public function addReportSchedule(): void
    {
        $this->authorize('update', $this->clientAccount);

        $data = $this->validate([
            'reportForm.report_type' => 'required|in:daily_activity,patrol_summary,incidents,custom',
            'reportForm.frequency' => 'required|in:daily,weekly,monthly',
            'reportForm.recipients' => 'required|string|max:1000',
            'reportForm.is_active' => 'boolean',
        ])['reportForm'];

        $recipients = array_values(array_filter(array_map('trim', explode(',', $data['recipients']))));

        if ($recipients === []) {
            $this->addError('reportForm.recipients', 'Enter at least one email address.');

            return;
        }

        foreach ($recipients as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addError('reportForm.recipients', "Invalid email: {$email}");

                return;
            }
        }

        ClientReportSchedule::create([
            'tenant_id' => TenantContext::id(),
            'client_account_id' => $this->clientAccount->id,
            'report_type' => $data['report_type'],
            'frequency' => $data['frequency'],
            'recipients' => $recipients,
            'is_active' => (bool) $data['is_active'],
        ]);

        $this->reportForm = [
            'report_type' => 'daily_activity',
            'frequency' => 'weekly',
            'recipients' => '',
            'is_active' => true,
        ];

        $this->clientAccount->load('reportSchedules');
        session()->flash('status', 'Report schedule added.');
    }

    public function deleteReportSchedule(int $scheduleId): void
    {
        $this->authorize('update', $this->clientAccount);

        ClientReportSchedule::query()
            ->where('client_account_id', $this->clientAccount->id)
            ->whereKey($scheduleId)
            ->delete();

        $this->clientAccount->load('reportSchedules');
    }

    public function toggleReportSchedule(int $scheduleId): void
    {
        $this->authorize('update', $this->clientAccount);

        $schedule = ClientReportSchedule::query()
            ->where('client_account_id', $this->clientAccount->id)
            ->findOrFail($scheduleId);

        $schedule->update(['is_active' => ! $schedule->is_active]);
        $this->clientAccount->load('reportSchedules');
    }

    public function render(ClientOverviewService $overview)
    {
        $this->clientAccount->load([
            'sites' => fn ($q) => $q->orderBy('name'),
            'contacts' => fn ($q) => $q->orderBy('name'),
            'notes' => fn ($q) => $q->latest(),
            'notes.author',
            'documents' => fn ($q) => $q->latest(),
            'portalUsers' => fn ($q) => $q->orderBy('name'),
            'reportSchedules' => fn ($q) => $q->latest(),
        ]);

        $tenantId = TenantContext::id();
        $stats = $overview->stats($this->clientAccount, $tenantId);
        $markers = $overview->mapMarkers($this->clientAccount, $tenantId);
        $mapCenter = $overview->mapCenter($markers);
        $recentActivity = $overview->recentActivity($this->clientAccount, $tenantId);

        $profileTabs = [
            'overview' => ['label' => 'Overview', 'hint' => 'Map, stats, and summary', 'group' => 'Summary'],
            'profile' => ['label' => 'Profile', 'hint' => 'Company details and billing', 'group' => 'Summary'],
            'contacts' => ['label' => 'Contacts', 'hint' => 'Key people', 'group' => 'People', 'badge' => $this->clientAccount->contacts->count() > 0 ? (string) $this->clientAccount->contacts->count() : null],
            'notes' => ['label' => 'Notes', 'hint' => 'Internal notes', 'group' => 'People', 'badge' => $this->clientAccount->notes->count() > 0 ? (string) $this->clientAccount->notes->count() : null],
            'files' => ['label' => 'Files', 'hint' => 'Contracts and documents', 'group' => 'Assets', 'badge' => $this->clientAccount->documents->count() > 0 ? (string) $this->clientAccount->documents->count() : null],
            'sites' => ['label' => 'Post Sites', 'hint' => 'Sites and locations', 'group' => 'Assets', 'badge' => $this->clientAccount->sites->count() > 0 ? (string) $this->clientAccount->sites->count() : null],
            'portal' => ['label' => 'Client Portal', 'hint' => 'Portal access settings', 'group' => 'Portal'],
            'users' => ['label' => 'User Access', 'hint' => 'Portal logins', 'group' => 'Portal', 'badge' => $this->clientAccount->portalUsers->count() > 0 ? (string) $this->clientAccount->portalUsers->count() : null],
            'reports' => ['label' => 'Email Reports', 'hint' => 'Scheduled delivery', 'group' => 'Reports', 'badge' => $this->clientAccount->reportSchedules->count() > 0 ? (string) $this->clientAccount->reportSchedules->count() : null],
        ];

        return view('livewire.clients.client-profile', [
            'profileTabs' => $profileTabs,
            'stats' => $stats,
            'mapMarkers' => $markers,
            'mapCenter' => $mapCenter,
            'recentActivity' => $recentActivity,
            'documentTypes' => config('client_profile.document_types', []),
            'reportTypes' => config('client_profile.report_types', []),
            'frequencies' => config('client_profile.report_frequencies', []),
        ])->layout('layouts.app');
    }

    private function resetContactForm(): void
    {
        $this->editingContactId = null;
        $this->contactForm = ['name' => '', 'email' => '', 'phone' => '', 'role' => ''];
    }

    private function resetNoteForm(): void
    {
        $this->editingNoteId = null;
        $this->noteForm = ['body' => '', 'is_internal' => true];
    }

    private function loadProfileForm(): void
    {
        $this->profileForm = $this->clientAccount->only([
            'name', 'industry', 'email', 'phone', 'address', 'latitude', 'longitude',
            'status', 'default_monthly_rate',
        ]);
        $this->profileForm['latitude'] = $this->clientAccount->latitude ?? '';
        $this->profileForm['longitude'] = $this->clientAccount->longitude ?? '';
    }

    private function loadPortalForm(): void
    {
        $this->portalForm = [
            'portal_enabled' => (bool) $this->clientAccount->portal_enabled,
            'portal_welcome_message' => $this->clientAccount->portal_welcome_message ?? '',
        ];
    }
}
