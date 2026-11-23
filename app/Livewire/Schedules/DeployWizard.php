<?php

namespace App\Livewire\Schedules;

use App\Exceptions\AssignmentException;
use App\Livewire\Concerns\EmbedsInScheduler;
use App\Models\ClientAccount;
use App\Models\EquipmentAsset;
use App\Models\Guard;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftConfirmation;
use App\Models\Site;
use App\Models\SitePost;
use App\Services\AssetManagementService;
use App\Services\ScheduleService;
use App\Services\WorkforceService;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use RuntimeException;

class DeployWizard extends Component
{
    use EmbedsInScheduler;

    public int $step = 1;

    public string $date = '';

    public string $client_account_id = '';

    public string $site_id = '';

    public string $site_post_id = '';

    public string $shift_mode = 'existing'; // existing | new

    public string $shift_id = '';

    public string $title = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public int $required_guards = 1;

    public string $guard_id = '';

    /** @var list<int|string> */
    public array $selectedAssetIds = [];

    public bool $confirm_now = true;

    public ?int $createdAssignmentId = null;

    protected $queryString = [
        'date' => ['except' => ''],
        'site_id' => ['except' => '', 'as' => 'site'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $this->step = 1;
        $this->date = $this->date ?: today()->toDateString();
        $this->validate(['date' => 'required|date_format:Y-m-d']);
        if ($this->site_id) {
            $this->updatedSiteId();
        }
        $this->resetShiftTimes();
    }

    public function updatedClientAccountId(): void
    {
        $this->site_id = '';
        $this->site_post_id = '';
        $this->shift_id = '';
        $this->selectedAssetIds = [];
    }

    public function updatedSiteId(): void
    {
        $this->site_post_id = '';
        $this->shift_id = '';
        $this->selectedAssetIds = [];
        if ($this->site_id) {
            $site = Site::find($this->site_id);
            if ($site?->client_account_id) {
                $this->client_account_id = (string) $site->client_account_id;
            }
        }
    }

    public function updatedDate(): void
    {
        $this->validateAtStep(1, ['date' => 'required|date_format:Y-m-d']);
        $this->resetShiftTimes();
        $this->shift_id = '';
    }

    public function toggleAsset(int $assetId): void
    {
        $ids = array_map('intval', $this->selectedAssetIds);
        if (in_array($assetId, $ids, true)) {
            $this->selectedAssetIds = array_values(array_filter($ids, fn ($id) => $id !== $assetId));
        } else {
            $this->selectedAssetIds = array_values([...$ids, $assetId]);
        }
    }

    public function nextStep(ScheduleService $schedules): void
    {
        $this->resetErrorBag();
        if ($this->step === 1) {
            $this->validateSiteDetails();
            $this->step = 2;
            $this->saveDraft();

            return;
        }

        if ($this->step === 2) {
            try {
                $schedules->validateShiftCapacity($this->deploymentShift());
            } catch (AssignmentException $e) {
                $this->assignmentError($e);

                return;
            }
            $this->step = 3;
            $this->saveDraft();

            return;
        }

        if ($this->step === 3) {
            $this->validateAtStep(3, [
                'guard_id' => ['required', TenantValidation::exists('guards')],
            ]);
            try {
                $schedules->validateGuardAssignment($this->deploymentShift(), Guard::findOrFail((int) $this->guard_id));
            } catch (AssignmentException $e) {
                $this->assignmentError($e);

                return;
            }
            $this->step = 4;
            $this->saveDraft();

            return;
        }

        if ($this->step === 4) {
            $this->validateAtStep(4, [
                'selectedAssetIds' => 'array',
                'selectedAssetIds.*' => ['integer', TenantValidation::exists('equipment_assets')],
            ]);
            $this->step = 5;
            $this->saveDraft();
        }
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function deploy(ScheduleService $schedules, WorkforceService $workforce, AssetManagementService $assets): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);

        $this->resetErrorBag();
        $this->validateSiteDetails();
        $this->validateAtStep(3, [
            'guard_id' => ['required', TenantValidation::exists('guards')],
        ]);
        $this->validateAtStep(4, [
            'selectedAssetIds' => 'array',
            'selectedAssetIds.*' => ['integer', TenantValidation::exists('equipment_assets')],
        ]);

        try {
            $assignment = DB::transaction(function () use ($schedules, $workforce, $assets) {
                $shift = $this->deploymentShift();
                if (! $shift->exists) {
                    $shift = $schedules->createShift($shift->getAttributes());
                }

                $assignment = $schedules->assignGuard($shift, Guard::findOrFail((int) $this->guard_id));

                if ($this->confirm_now) {
                    $confirmation = ShiftConfirmation::where('shift_assignment_id', $assignment->id)->first();
                    if ($confirmation) {
                        $workforce->confirmShift($confirmation);
                    }
                }

                try {
                    $assets->issueKitForAssignment(
                        $assignment->load('shift'),
                        array_map('intval', $this->selectedAssetIds),
                    );
                } catch (RuntimeException $e) {
                    $this->step = 4;
                    throw ValidationException::withMessages(['selectedAssetIds' => $e->getMessage()]);
                }

                return $assignment;
            });
        } catch (AssignmentException $e) {
            $this->assignmentError($e);

            return;
        }

        $this->createdAssignmentId = $assignment->id;
        $this->step = 6;
        session()->forget($this->draftKey());
        session()->flash('status', $this->confirm_now ? 'Assignment confirmed.' : 'Assignment created. Awaiting confirmation.');
    }

    public function resetWizard(): void
    {
        session()->forget($this->draftKey());
        $this->step = 1;
        $this->site_post_id = '';
        $this->shift_id = '';
        $this->guard_id = '';
        $this->selectedAssetIds = [];
        $this->createdAssignmentId = null;
        $this->shift_mode = 'existing';
        $this->title = '';
        $this->resetShiftTimes();
        $this->resetErrorBag();
    }

    public function resumeDraft(): void
    {
        abort_unless(auth()->user()?->can('schedules.manage'), 403);
        $draft = session()->get($this->draftKey());

        if (! $this->matchesDraft($draft)) {
            return;
        }

        $this->fill($draft);
        $this->resetErrorBag();
    }

    private function saveDraft(): void
    {
        session()->put($this->draftKey(), $this->only([
            'date', 'step', 'client_account_id', 'site_id', 'site_post_id', 'shift_mode',
            'shift_id', 'title', 'starts_at', 'ends_at', 'required_guards', 'guard_id',
            'selectedAssetIds', 'confirm_now',
        ]));
    }

    private function matchesDraft(?array $draft): bool
    {
        return $this->step === 1
            && ($draft['date'] ?? null) === $this->date
            && ($this->site_id === '' || $this->site_id === $draft['site_id']);
    }

    private function draftKey(): string
    {
        return 'deployment_draft.'.TenantContext::id().'.'.TenantContext::userId();
    }

    public function render(AssetManagementService $assets)
    {
        $tenantId = TenantContext::id();

        $sitesQuery = Site::where('tenant_id', $tenantId)->orderBy('name');
        if ($this->client_account_id) {
            $sitesQuery->where('client_account_id', $this->client_account_id);
        }

        $posts = $this->site_id
            ? SitePost::where('site_id', $this->site_id)->orderBy('name')->get()
            : collect();

        $shifts = $this->site_id
            ? $this->shiftQuery()
                ->with(['assignments.assignedGuard', 'sitePost'])
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->orderBy('starts_at')
                ->get()
                ->filter(fn (Shift $shift) => $shift->activeAssignmentsCount() < $shift->required_guards)
                ->values()
            : collect();

        $guards = Guard::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('verification_status', 'verified')
            ->orderBy('first_name')
            ->get();

        $kitAssets = $this->site_id
            ? $assets->availableDeployKit($tenantId, (int) $this->site_id)
            : collect();

        $kitGrouped = $kitAssets->groupBy(fn (EquipmentAsset $asset) => $asset->category ?: ($asset->assetCategory?->name ?? 'Other'));

        $assignment = $this->createdAssignmentId
            ? ShiftAssignment::with(['shift.site', 'shift.sitePost', 'assignedGuard', 'equipmentAssignments.asset'])
                ->find($this->createdAssignmentId)
            : null;

        $selectedLabels = $kitAssets
            ->whereIn('id', array_map('intval', $this->selectedAssetIds))
            ->map(fn (EquipmentAsset $a) => $a->displayLabel())
            ->values();

        return view('livewire.schedules.deploy-wizard', [
            'canResumeDraft' => $this->matchesDraft(session()->get($this->draftKey())),
            'selectedShift' => $this->shift_id
                ? Shift::with(['site', 'sitePost'])->where('tenant_id', $tenantId)->find($this->shift_id)
                : null,
            'clients' => ClientAccount::where('tenant_id', $tenantId)->orderBy('name')->get(),
            'sites' => $sitesQuery->get(),
            'posts' => $posts,
            'shifts' => $shifts,
            'guards' => $guards,
            'kitGrouped' => $kitGrouped,
            'selectedLabels' => $selectedLabels,
            'assignment' => $assignment,
            'steps' => [
                1 => 'Site',
                2 => 'Shift',
                3 => 'Guard',
                4 => 'Kit',
                5 => 'Confirm',
                6 => 'Done',
            ],
        ])->layout('layouts.app');
    }

    private function validateAtStep(int $step, array $rules): void
    {
        try {
            $this->validate($rules);
        } catch (ValidationException $e) {
            $this->step = $step;
            throw $e;
        }
    }

    private function validateSiteDetails(): void
    {
        $this->validateAtStep(1, [
            'client_account_id' => ['required', TenantValidation::exists('client_accounts')],
            'site_id' => ['required', TenantValidation::exists('sites')->where('client_account_id', $this->client_account_id)],
            'site_post_id' => ['nullable', TenantValidation::exists('site_posts')->where('site_id', $this->site_id)],
            'date' => 'required|date_format:Y-m-d',
        ]);
    }

    private function shiftQuery(): Builder
    {
        return Shift::where('tenant_id', TenantContext::id())
            ->where('client_account_id', $this->client_account_id)
            ->where('site_id', $this->site_id)
            ->whereDate('starts_at', $this->date)
            ->when($this->site_post_id, fn ($q) => $q->where('site_post_id', $this->site_post_id));
    }

    private function deploymentShift(): Shift
    {
        $this->validateAtStep(2, ['shift_mode' => 'required|in:existing,new']);
        if ($this->shift_mode === 'existing') {
            $this->validateAtStep(2, ['shift_id' => 'required|integer']);
            $shift = $this->shiftQuery()->find($this->shift_id);
            if (! $shift) {
                $this->step = 2;
                throw ValidationException::withMessages(['shift_id' => 'Select a shift matching this client, site, post, and date.']);
            }

            return $shift;
        }

        $this->validateAtStep(2, [
            'title' => 'required|string|max:255',
            'starts_at' => ['required', 'date', 'after_or_equal:'.$this->date.' 00:00', 'before:'.Carbon::parse($this->date)->addDay()->toDateString().' 00:00'],
            'ends_at' => 'required|date|after:starts_at',
            'required_guards' => 'required|integer|min:1|max:50',
        ]);

        return new Shift([
            'tenant_id' => TenantContext::id(),
            'client_account_id' => (int) $this->client_account_id,
            'site_id' => (int) $this->site_id,
            'site_post_id' => $this->site_post_id ?: null,
            'title' => $this->title,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'required_guards' => $this->required_guards,
            'status' => 'open',
        ]);
    }

    private function assignmentError(AssignmentException $e): void
    {
        $this->step = $e->field === 'shift_id' ? 2 : 3;
        $this->addError($e->field, $e->getMessage());
    }

    private function resetShiftTimes(): void
    {
        $day = Carbon::parse($this->date ?: today()->toDateString());
        $this->starts_at = $day->copy()->setTime(8, 0)->format('Y-m-d\TH:i');
        $this->ends_at = $day->copy()->setTime(16, 0)->format('Y-m-d\TH:i');
        if ($this->title === '') {
            $this->title = 'Deployment · '.$day->format('M j');
        }
    }
}
