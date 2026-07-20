<?php

namespace App\Livewire\Sites;

use App\Models\CheckpointTask;
use App\Models\ClientAccount;
use App\Models\PatrolCheckpoint;
use App\Models\PatrolRoute;
use App\Models\PostOrder;
use App\Models\ReportTemplate;
use App\Models\ReportTemplateAssignment;
use App\Models\Site;
use App\Models\SiteDocument;
use App\Models\SiteEmergencyContact;
use App\Models\SiteNote;
use App\Models\SitePost;
use App\Models\SiteReportSchedule;
use App\Models\SiteSlaRequirement;
use App\Models\TaskSubmission;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\SiteOverviewService;
use App\Services\TenantFileStorageService;
use App\Support\TenantContext;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class SiteProfile extends Component
{
    use WithFileUploads;

    private const TABS = [
        'overview', 'profile', 'contacts', 'post_orders', 'tasks', 'tours', 'kpis', 'files', 'reports',
    ];

    private const TAB_ALIASES = [
        'settings' => 'profile',
        'notes' => 'contacts',
        'guards' => 'overview',
        'tour_tags' => 'tours',
        'geofence' => 'tours',
        'email_reports' => 'reports',
        'checklists' => 'kpis',
    ];

    #[Locked]
    public Site $site;

    #[Url(as: 'tab')]
    public string $activeTab = 'overview';

    public array $profileForm = [];

    public array $contactForm = ['name' => '', 'role' => '', 'phone' => '', 'email' => '', 'priority' => 1];

    public array $noteForm = ['body' => '', 'is_internal' => true];

    public array $documentForm = ['title' => '', 'document_type' => 'general', 'expires_on' => '', 'client_visible' => false];

    public array $postForm = ['name' => '', 'description' => '', 'required_guards' => 1, 'status' => 'active'];

    public array $postOrderForm = ['site_post_id' => '', 'title' => '', 'instructions' => '', 'is_active' => true];

    public array $tourForm = ['name' => '', 'description' => '', 'expected_duration_minutes' => 30, 'status' => 'active'];

    public array $tagForm = [
        'patrol_route_id' => '', 'name' => '', 'code' => '', 'sequence' => 1,
        'instructions' => '', 'latitude' => '', 'longitude' => '',
    ];

    public array $taskForm = ['patrol_checkpoint_id' => '', 'title' => '', 'response_type' => 'yes_no', 'is_required' => true];

    public array $checklistForm = ['metric' => '', 'target_value' => '', 'frequency' => 'daily', 'grace_minutes' => 0];

    public array $reportAssignForm = ['report_template_id' => '', 'site_post_id' => ''];

    public array $reportForm = ['report_type' => 'daily_activity', 'frequency' => 'weekly', 'recipients' => '', 'is_active' => true];

    public array $settingsForm = [];

    public array $geofenceForm = [];

    public $documentFile;

    public ?int $editingContactId = null;

    public ?int $editingPostId = null;

    public ?int $editingPostOrderId = null;

    public ?int $editingTourId = null;

    public ?int $editingTourTagId = null;

    public ?int $editingTaskId = null;

    public ?int $editingChecklistId = null;

    public ?int $editingNoteId = null;

    public function mount(Site $site): void
    {
        $this->authorize('view', $site);
        abort_unless((int) $site->tenant_id === (int) TenantContext::id(), 404);

        $this->site = $site;
        $this->loadProfileForm();
        $this->loadSettingsForm();
        $this->loadGeofenceForm();

        // Canonicalize aliased tabs in the URL (settings → profile, etc.).
        $this->activeTab = $this->resolveTab($this->activeTab);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $this->resolveTab($tab);
    }

    private function resolveTab(string $tab): string
    {
        $resolved = self::TAB_ALIASES[$tab] ?? $tab;

        return in_array($resolved, self::TABS, true) ? $resolved : 'overview';
    }

    public function saveProfile(): void
    {
        $this->authorize('update', $this->site);

        if (($this->profileForm['supervisor_user_id'] ?? '') === '') {
            $this->profileForm['supervisor_user_id'] = null;
        }

        $data = $this->validate([
            'profileForm.name' => 'required|string|max:255',
            'profileForm.client_account_id' => 'required|exists:client_accounts,id',
            'profileForm.supervisor_user_id' => 'nullable|integer|exists:users,id',
            'profileForm.address' => 'nullable|string|max:500',
            'profileForm.status' => 'required|in:active,inactive',
            'profileForm.instructions' => 'nullable|string|max:5000',
        ])['profileForm'];

        $data['supervisor_user_id'] = $data['supervisor_user_id'] !== null
            ? (int) $data['supervisor_user_id']
            : null;

        if ($data['supervisor_user_id']) {
            $allowed = User::query()
                ->where('tenant_id', TenantContext::id())
                ->whereKey($data['supervisor_user_id'])
                ->where('status', 'active')
                ->role(['supervisor', 'operations-manager', 'company-admin'])
                ->exists();

            abort_unless($allowed, 422);
        }

        $this->site->update($data);
        $this->site->refresh()->load('clientAccount');
        $this->loadProfileForm();
        session()->flash('status', 'Site profile updated.');
    }

    public function saveGeofence(): void
    {
        $this->authorize('update', $this->site);
        $data = $this->validate([
            'geofenceForm.latitude' => 'nullable|numeric',
            'geofenceForm.longitude' => 'nullable|numeric',
            'geofenceForm.geofence_radius_meters' => 'integer|min:10|max:5000',
        ])['geofenceForm'];

        $data['latitude'] = $data['latitude'] !== '' && $data['latitude'] !== null ? $data['latitude'] : null;
        $data['longitude'] = $data['longitude'] !== '' && $data['longitude'] !== null ? $data['longitude'] : null;

        $this->site->update($data);
        $this->site->refresh();
        $this->loadGeofenceForm();
        session()->flash('status', 'Geofence updated.');
    }

    public function saveContact(): void
    {
        $this->authorize('update', $this->site);
        $data = $this->validate([
            'contactForm.name' => 'required|string|max:255',
            'contactForm.role' => 'nullable|string|max:120',
            'contactForm.phone' => 'required|string|max:40',
            'contactForm.email' => 'nullable|email|max:255',
            'contactForm.priority' => 'integer|min:1|max:10',
        ])['contactForm'];

        if ($this->editingContactId) {
            SiteEmergencyContact::query()->where('site_id', $this->site->id)->findOrFail($this->editingContactId)->update($data);
        } else {
            SiteEmergencyContact::create($data + ['tenant_id' => TenantContext::id(), 'site_id' => $this->site->id]);
        }

        $this->resetContactForm();
        $this->reloadSite();
        session()->flash('status', 'Contact saved.');
    }

    public function editContact(int $id): void
    {
        $contact = SiteEmergencyContact::query()->where('site_id', $this->site->id)->findOrFail($id);
        $this->editingContactId = $contact->id;
        $this->contactForm = $contact->only(['name', 'role', 'phone', 'email', 'priority']);
    }

    public function deleteContact(int $id): void
    {
        $this->authorize('update', $this->site);
        SiteEmergencyContact::query()->where('site_id', $this->site->id)->whereKey($id)->delete();
        $this->reloadSite();
    }

    public function addNote(): void
    {
        $this->authorize('update', $this->site);
        $data = $this->validate(['noteForm.body' => 'required|string|max:5000', 'noteForm.is_internal' => 'boolean'])['noteForm'];

        if ($this->editingNoteId) {
            SiteNote::query()->where('site_id', $this->site->id)->findOrFail($this->editingNoteId)->update($data);
            session()->flash('status', 'Note updated.');
        } else {
            SiteNote::create($data + ['tenant_id' => TenantContext::id(), 'site_id' => $this->site->id, 'user_id' => auth()->id()]);
            session()->flash('status', 'Note added.');
        }

        $this->resetNoteForm();
        $this->reloadSite();
    }

    public function editNote(int $id): void
    {
        $this->authorize('update', $this->site);
        $note = SiteNote::query()->where('site_id', $this->site->id)->findOrFail($id);
        $this->editingNoteId = $note->id;
        $this->noteForm = $note->only(['body', 'is_internal']);
    }

    public function deleteNote(int $id): void
    {
        $this->authorize('update', $this->site);
        SiteNote::query()->where('site_id', $this->site->id)->whereKey($id)->delete();
        $this->reloadSite();
    }

    public function uploadDocument(FileUploadService $uploads): void
    {
        $this->authorize('update', $this->site);
        $data = $this->validate([
            'documentForm.title' => 'required|string|max:255',
            'documentForm.document_type' => 'required|string|max:80',
            'documentForm.expires_on' => 'nullable|date',
            'documentForm.client_visible' => 'boolean',
            'documentFile' => 'required|file|max:10240',
        ]);

        $path = $uploads->storeSiteDocument(TenantContext::id(), $this->site->id, $this->documentFile);
        SiteDocument::create([
            'tenant_id' => TenantContext::id(),
            'site_id' => $this->site->id,
            'title' => $data['documentForm']['title'],
            'document_type' => $data['documentForm']['document_type'],
            'file_path' => $path,
            'expires_on' => $data['documentForm']['expires_on'] ?: null,
            'client_visible' => (bool) $data['documentForm']['client_visible'],
        ]);

        $this->documentForm = ['title' => '', 'document_type' => 'general', 'expires_on' => '', 'client_visible' => false];
        $this->reset('documentFile');
        $this->reloadSite();
        session()->flash('status', 'File uploaded.');
    }

    public function deleteDocument(int $id, TenantFileStorageService $storage): void
    {
        $this->authorize('update', $this->site);
        $doc = SiteDocument::query()->where('site_id', $this->site->id)->findOrFail($id);
        $storage->delete($doc->file_path);
        $doc->delete();
        $this->reloadSite();
    }

    public function addPost(): void
    {
        $this->authorize('update', $this->site);
        $data = $this->validate([
            'postForm.name' => 'required|string|max:255',
            'postForm.description' => 'nullable|string|max:1000',
            'postForm.required_guards' => 'integer|min:1',
            'postForm.status' => 'required|in:active,inactive',
        ])['postForm'];

        if ($this->editingPostId) {
            SitePost::query()->where('site_id', $this->site->id)->findOrFail($this->editingPostId)->update($data);
            session()->flash('status', 'Post updated.');
        } else {
            SitePost::create($data + ['tenant_id' => TenantContext::id(), 'site_id' => $this->site->id]);
            session()->flash('status', 'Post added.');
        }

        $this->resetPostForm();
        $this->reloadSite();
    }

    public function editPost(int $id): void
    {
        $this->authorize('update', $this->site);
        $post = SitePost::query()->where('site_id', $this->site->id)->findOrFail($id);
        $this->editingPostId = $post->id;
        $this->postForm = $post->only(['name', 'description', 'required_guards', 'status']);
    }

    public function deletePost(int $id): void
    {
        $this->authorize('update', $this->site);
        SitePost::query()->where('site_id', $this->site->id)->whereKey($id)->delete();
        $this->reloadSite();
    }

    public function addPostOrder(): void
    {
        $this->authorize('update', $this->site);
        $data = $this->validate([
            'postOrderForm.site_post_id' => 'nullable|exists:site_posts,id',
            'postOrderForm.title' => 'required|string|max:255',
            'postOrderForm.instructions' => 'required|string|max:5000',
            'postOrderForm.is_active' => 'boolean',
        ])['postOrderForm'];

        $payload = $data + [
            'tenant_id' => TenantContext::id(),
            'site_id' => $this->site->id,
            'site_post_id' => $data['site_post_id'] ?: null,
        ];

        if ($this->editingPostOrderId) {
            PostOrder::query()->where('site_id', $this->site->id)->findOrFail($this->editingPostOrderId)->update($payload);
            session()->flash('status', 'Post order updated.');
        } else {
            PostOrder::create($payload + ['version' => 1]);
            session()->flash('status', 'Post order added.');
        }

        $this->resetPostOrderForm();
        $this->reloadSite();
    }

    public function editPostOrder(int $id): void
    {
        $this->authorize('update', $this->site);
        $order = PostOrder::query()->where('site_id', $this->site->id)->findOrFail($id);
        $this->editingPostOrderId = $order->id;
        $this->postOrderForm = [
            'site_post_id' => $order->site_post_id ?? '',
            'title' => $order->title,
            'instructions' => $order->instructions,
            'is_active' => (bool) $order->is_active,
        ];
    }

    public function deletePostOrder(int $id): void
    {
        $this->authorize('update', $this->site);
        PostOrder::query()->where('site_id', $this->site->id)->whereKey($id)->delete();
        $this->reloadSite();
    }

    public function addTour(): void
    {
        $this->authorize('update', $this->site);
        $data = $this->validate([
            'tourForm.name' => 'required|string|max:255',
            'tourForm.description' => 'nullable|string|max:1000',
            'tourForm.expected_duration_minutes' => 'integer|min:5',
            'tourForm.status' => 'required|in:active,inactive',
        ])['tourForm'];

        if ($this->editingTourId) {
            PatrolRoute::query()->where('site_id', $this->site->id)->findOrFail($this->editingTourId)->update($data);
            session()->flash('status', 'Site tour updated.');
        } else {
            PatrolRoute::create($data + ['tenant_id' => TenantContext::id(), 'site_id' => $this->site->id]);
            session()->flash('status', 'Site tour created.');
        }

        $this->resetTourForm();
        $this->reloadSite();
    }

    public function editTour(int $id): void
    {
        $this->authorize('update', $this->site);
        $route = PatrolRoute::query()->where('site_id', $this->site->id)->findOrFail($id);
        $this->editingTourId = $route->id;
        $this->tourForm = $route->only(['name', 'description', 'expected_duration_minutes', 'status']);
    }

    public function deleteTour(int $id): void
    {
        $this->authorize('update', $this->site);
        PatrolRoute::query()->where('site_id', $this->site->id)->whereKey($id)->delete();
        $this->reloadSite();
    }

    public function addTourTag(): void
    {
        $this->authorize('update', $this->site);
        $data = $this->validate([
            'tagForm.patrol_route_id' => 'required|exists:patrol_routes,id',
            'tagForm.name' => 'required|string|max:255',
            'tagForm.code' => 'required|string|max:80',
            'tagForm.sequence' => 'integer|min:1',
            'tagForm.instructions' => 'nullable|string|max:1000',
            'tagForm.latitude' => 'nullable|numeric',
            'tagForm.longitude' => 'nullable|numeric',
        ])['tagForm'];

        $data['latitude'] = $data['latitude'] !== '' ? $data['latitude'] : null;
        $data['longitude'] = $data['longitude'] !== '' ? $data['longitude'] : null;
        $data['instructions'] = $data['instructions'] !== '' ? $data['instructions'] : null;

        if ($this->editingTourTagId) {
            PatrolCheckpoint::query()
                ->where('tenant_id', TenantContext::id())
                ->whereHas('route', fn ($q) => $q->where('site_id', $this->site->id))
                ->findOrFail($this->editingTourTagId)
                ->update($data);
            session()->flash('status', 'Tour tag updated.');
        } else {
            PatrolCheckpoint::create($data + [
                'tenant_id' => TenantContext::id(),
                'status' => 'active',
            ]);
            session()->flash('status', 'Tour tag added.');
        }

        $this->resetTagForm();
        $this->reloadSite();
    }

    public function editTourTag(int $id): void
    {
        $this->authorize('update', $this->site);
        $tag = PatrolCheckpoint::query()
            ->where('tenant_id', TenantContext::id())
            ->whereHas('route', fn ($q) => $q->where('site_id', $this->site->id))
            ->findOrFail($id);

        $this->editingTourTagId = $tag->id;
        $this->tagForm = [
            'patrol_route_id' => $tag->patrol_route_id,
            'name' => $tag->name,
            'code' => $tag->code,
            'sequence' => $tag->sequence,
            'instructions' => $tag->instructions ?? '',
            'latitude' => $tag->latitude ?? '',
            'longitude' => $tag->longitude ?? '',
        ];
    }

    public function deleteTourTag(int $id): void
    {
        $this->authorize('update', $this->site);
        PatrolCheckpoint::query()
            ->where('tenant_id', TenantContext::id())
            ->whereHas('route', fn ($q) => $q->where('site_id', $this->site->id))
            ->whereKey($id)
            ->delete();
        $this->reloadSite();
    }

    public function addTask(): void
    {
        $this->authorize('update', $this->site);
        $data = $this->validate([
            'taskForm.patrol_checkpoint_id' => 'required|exists:patrol_checkpoints,id',
            'taskForm.title' => 'required|string|max:255',
            'taskForm.response_type' => 'required|in:yes_no,text,number,photo',
            'taskForm.is_required' => 'boolean',
        ])['taskForm'];

        if ($this->editingTaskId) {
            CheckpointTask::query()
                ->where('tenant_id', TenantContext::id())
                ->findOrFail($this->editingTaskId)
                ->update($data);
            session()->flash('status', 'Task updated.');
        } else {
            CheckpointTask::create($data + ['tenant_id' => TenantContext::id(), 'sort_order' => 1]);
            session()->flash('status', 'Task added.');
        }

        $this->resetTaskForm();
    }

    public function editTask(int $id): void
    {
        $this->authorize('update', $this->site);
        $task = CheckpointTask::query()->where('tenant_id', TenantContext::id())->findOrFail($id);
        $this->editingTaskId = $task->id;
        $this->taskForm = $task->only(['patrol_checkpoint_id', 'title', 'response_type', 'is_required']);
    }

    public function deleteTask(int $id): void
    {
        $this->authorize('update', $this->site);
        CheckpointTask::query()->where('tenant_id', TenantContext::id())->whereKey($id)->delete();
    }

    public function addChecklist(): void
    {
        $this->authorize('update', $this->site);
        $data = $this->validate([
            'checklistForm.metric' => 'required|string|max:255',
            'checklistForm.target_value' => 'required|string|max:120',
            'checklistForm.frequency' => 'required|in:daily,weekly,monthly',
            'checklistForm.grace_minutes' => 'integer|min:0',
        ])['checklistForm'];

        if ($this->editingChecklistId) {
            SiteSlaRequirement::query()->where('site_id', $this->site->id)->findOrFail($this->editingChecklistId)->update($data);
            session()->flash('status', 'Checklist item updated.');
        } else {
            SiteSlaRequirement::create($data + [
                'tenant_id' => TenantContext::id(),
                'site_id' => $this->site->id,
                'is_active' => true,
            ]);
            session()->flash('status', 'Checklist item added.');
        }

        $this->resetChecklistForm();
        $this->reloadSite();
    }

    public function editChecklist(int $id): void
    {
        $this->authorize('update', $this->site);
        $item = SiteSlaRequirement::query()->where('site_id', $this->site->id)->findOrFail($id);
        $this->editingChecklistId = $item->id;
        $this->checklistForm = $item->only(['metric', 'target_value', 'frequency', 'grace_minutes']);
    }

    public function deleteChecklist(int $id): void
    {
        $this->authorize('update', $this->site);
        SiteSlaRequirement::query()->where('site_id', $this->site->id)->whereKey($id)->delete();
        $this->reloadSite();
    }

    public function assignReport(): void
    {
        $this->authorize('update', $this->site);
        $data = $this->validate([
            'reportAssignForm.report_template_id' => 'required|exists:report_templates,id',
            'reportAssignForm.site_post_id' => 'nullable|exists:site_posts,id',
        ])['reportAssignForm'];

        ReportTemplateAssignment::firstOrCreate([
            'tenant_id' => TenantContext::id(),
            'report_template_id' => $data['report_template_id'],
            'site_id' => $this->site->id,
            'site_post_id' => $data['site_post_id'] ?: null,
        ]);

        $this->reportAssignForm = ['report_template_id' => '', 'site_post_id' => ''];
        $this->reloadSite();
        session()->flash('status', 'Report template assigned.');
    }

    public function removeReportAssignment(int $id): void
    {
        $this->authorize('update', $this->site);
        ReportTemplateAssignment::query()->where('site_id', $this->site->id)->whereKey($id)->delete();
        $this->reloadSite();
    }

    public function addReportSchedule(): void
    {
        $this->authorize('update', $this->site);
        $data = $this->validate([
            'reportForm.report_type' => 'required|in:daily_activity,patrol_summary,incidents,custom',
            'reportForm.frequency' => 'required|in:daily,weekly,monthly',
            'reportForm.recipients' => 'required|string|max:1000',
            'reportForm.is_active' => 'boolean',
        ])['reportForm'];

        $recipients = array_values(array_filter(array_map('trim', explode(',', $data['recipients']))));
        foreach ($recipients as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addError('reportForm.recipients', "Invalid email: {$email}");

                return;
            }
        }

        SiteReportSchedule::create([
            'tenant_id' => TenantContext::id(),
            'site_id' => $this->site->id,
            'report_type' => $data['report_type'],
            'frequency' => $data['frequency'],
            'recipients' => $recipients,
            'is_active' => (bool) $data['is_active'],
        ]);

        $this->reportForm = ['report_type' => 'daily_activity', 'frequency' => 'weekly', 'recipients' => '', 'is_active' => true];
        $this->reloadSite();
        session()->flash('status', 'Email report schedule added.');
    }

    public function deleteReportSchedule(int $id): void
    {
        $this->authorize('update', $this->site);
        SiteReportSchedule::query()->where('site_id', $this->site->id)->whereKey($id)->delete();
        $this->reloadSite();
    }

    public function saveSettings(): void
    {
        $this->authorize('update', $this->site);
        $data = $this->validate([
            'settingsForm.require_geofence_clock_in' => 'boolean',
            'settingsForm.notify_on_incident' => 'boolean',
            'settingsForm.patrol_reminder_minutes' => 'integer|min:0|max:240',
            'settingsForm.show_in_client_portal' => 'boolean',
        ])['settingsForm'];

        $this->site->update(['settings' => $data]);
        $this->site->refresh();
        $this->loadSettingsForm();
        session()->flash('status', 'Site settings saved.');
    }

    public function render(SiteOverviewService $overview)
    {
        $this->reloadSite();
        $tenantId = TenantContext::id();
        $stats = $overview->stats($this->site, $tenantId);
        $markers = $overview->mapMarkers($this->site);
        $mapCenter = $overview->mapCenter($this->site);
        $upcomingShifts = $overview->upcomingShifts($this->site, $tenantId);

        $checkpoints = PatrolCheckpoint::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('route', fn ($q) => $q->where('site_id', $this->site->id))
            ->with('route')
            ->orderBy('sequence')
            ->get();

        $tasks = CheckpointTask::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('patrol_checkpoint_id', $checkpoints->pluck('id'))
            ->with('checkpoint.route')
            ->orderBy('sort_order')
            ->get();

        $taskSubmissions = TaskSubmission::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('checkpoint_task_id', $tasks->pluck('id'))
            ->with(['task', 'scan.assignedGuard', 'scan.checkpoint'])
            ->latest()
            ->limit(40)
            ->get();

        $peopleBadge = $this->site->emergencyContacts->count() + $this->site->notes->count();
        $patrolBadge = $this->site->patrolRoutes->count() + $checkpoints->count();
        $reportsBadge = $this->site->reportAssignments->count() + $this->site->reportSchedules->count();

        $profileTabs = [
            'overview' => ['label' => 'Overview', 'hint' => 'Map, KPIs, shifts', 'group' => 'Overview'],
            'profile' => ['label' => 'Profile', 'hint' => 'Details and preferences', 'group' => 'Overview'],
            'contacts' => ['label' => 'Contacts', 'hint' => 'Emergency contacts and notes', 'group' => 'People', 'badge' => $this->badge($peopleBadge)],
            'post_orders' => ['label' => 'Post Orders', 'hint' => 'Posts and orders', 'group' => 'Operations', 'badge' => $this->badge($this->site->postOrders->count())],
            'tasks' => ['label' => 'Tasks', 'hint' => 'Checkpoint tasks', 'group' => 'Operations', 'badge' => $this->badge($tasks->count())],
            'tours' => ['label' => 'Patrol', 'hint' => 'Routes, tags, geofence', 'group' => 'Patrol', 'badge' => $this->badge($patrolBadge), 'icon' => 'patrols'],
            'kpis' => ['label' => 'SLA & Checklists', 'hint' => 'Compliance targets', 'group' => 'Compliance', 'badge' => $this->badge($this->site->slaRequirements->count())],
            'files' => ['label' => 'Files', 'hint' => 'Documents', 'group' => 'Compliance', 'badge' => $this->badge($this->site->documents->count())],
            'reports' => ['label' => 'Reports', 'hint' => 'Templates and email schedules', 'group' => 'Compliance', 'badge' => $this->badge($reportsBadge)],
        ];

        return view('livewire.sites.site-profile', [
            'profileTabs' => $profileTabs,
            'stats' => $stats,
            'mapMarkers' => $markers,
            'mapCenter' => $mapCenter,
            'upcomingShifts' => $upcomingShifts,
            'checkpoints' => $checkpoints,
            'tasks' => $tasks,
            'taskSubmissions' => $taskSubmissions,
            'clients' => ClientAccount::orderBy('name')->get(),
            'supervisorCandidates' => User::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->role(['supervisor', 'operations-manager', 'company-admin'])
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'email']),
            'reportTemplates' => ReportTemplate::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(),
            'documentTypes' => config('site_profile.document_types', []),
            'reportTypes' => config('site_profile.report_types', []),
            'frequencies' => config('site_profile.report_frequencies', []),
        ])->layout('layouts.app');
    }

    private function reloadSite(): void
    {
        $this->site->load([
            'clientAccount',
            'posts' => fn ($q) => $q->orderBy('name'),
            'postOrders' => fn ($q) => $q->latest(),
            'patrolRoutes' => fn ($q) => $q->orderBy('name'),
            'patrolRoutes.checkpoints',
            'emergencyContacts' => fn ($q) => $q->orderBy('priority'),
            'documents' => fn ($q) => $q->latest(),
            'notes' => fn ($q) => $q->latest(),
            'notes.author',
            'slaRequirements',
            'reportAssignments.template',
            'reportSchedules' => fn ($q) => $q->latest(),
        ]);
    }

    private function loadProfileForm(): void
    {
        $this->profileForm = $this->site->only([
            'name', 'client_account_id', 'supervisor_user_id', 'address', 'status', 'instructions',
        ]);
        $this->profileForm['supervisor_user_id'] = $this->site->supervisor_user_id ?? '';
    }

    private function loadGeofenceForm(): void
    {
        $this->geofenceForm = [
            'latitude' => $this->site->latitude ?? '',
            'longitude' => $this->site->longitude ?? '',
            'geofence_radius_meters' => $this->site->geofence_radius_meters ?? 150,
        ];
    }

    private function loadSettingsForm(): void
    {
        $this->settingsForm = $this->site->resolvedSettings();
    }

    private function resetContactForm(): void
    {
        $this->editingContactId = null;
        $this->contactForm = ['name' => '', 'role' => '', 'phone' => '', 'email' => '', 'priority' => 1];
    }

    private function resetNoteForm(): void
    {
        $this->editingNoteId = null;
        $this->noteForm = ['body' => '', 'is_internal' => true];
    }

    private function resetPostForm(): void
    {
        $this->editingPostId = null;
        $this->postForm = ['name' => '', 'description' => '', 'required_guards' => 1, 'status' => 'active'];
    }

    private function resetPostOrderForm(): void
    {
        $this->editingPostOrderId = null;
        $this->postOrderForm = ['site_post_id' => '', 'title' => '', 'instructions' => '', 'is_active' => true];
    }

    private function resetTourForm(): void
    {
        $this->editingTourId = null;
        $this->tourForm = ['name' => '', 'description' => '', 'expected_duration_minutes' => 30, 'status' => 'active'];
    }

    private function resetTagForm(): void
    {
        $this->editingTourTagId = null;
        $this->tagForm = [
            'patrol_route_id' => '', 'name' => '', 'code' => '', 'sequence' => 1,
            'instructions' => '', 'latitude' => '', 'longitude' => '',
        ];
    }

    private function resetTaskForm(): void
    {
        $this->editingTaskId = null;
        $this->taskForm = ['patrol_checkpoint_id' => '', 'title' => '', 'response_type' => 'yes_no', 'is_required' => true];
    }

    private function resetChecklistForm(): void
    {
        $this->editingChecklistId = null;
        $this->checklistForm = ['metric' => '', 'target_value' => '', 'frequency' => 'daily', 'grace_minutes' => 0];
    }

    private function badge(int $count): ?string
    {
        return $count > 0 ? (string) $count : null;
    }
}
