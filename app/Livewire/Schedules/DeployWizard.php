<?php

namespace App\Livewire\Schedules;

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
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use RuntimeException;

class DeployWizard extends Component
{
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
        'step' => ['except' => 1],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $this->date = $this->date ?: today()->toDateString();
        $this->resetShiftTimes();
    }

    public function updatedClientAccountId(): void
    {
        $this->site_id = '';
        $this->site_post_id = '';
        $this->shift_id = '';
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

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'client_account_id' => ['required', TenantValidation::exists('client_accounts')],
                'site_id' => ['required', TenantValidation::exists('sites')],
                'site_post_id' => ['nullable', TenantValidation::exists('site_posts')],
                'date' => 'required|date',
            ]);
            $this->step = 2;

            return;
        }

        if ($this->step === 2) {
            if ($this->shift_mode === 'existing') {
                $this->validate([
                    'shift_id' => ['required', TenantValidation::exists('shifts')],
                ]);
            } else {
                $this->validate([
                    'title' => 'required|string|max:255',
                    'starts_at' => 'required|date',
                    'ends_at' => 'required|date|after:starts_at',
                    'required_guards' => 'required|integer|min:1|max:50',
                ]);
            }
            $this->step = 3;

            return;
        }

        if ($this->step === 3) {
            $this->validate([
                'guard_id' => ['required', TenantValidation::exists('guards')],
            ]);
            $this->step = 4;

            return;
        }

        if ($this->step === 4) {
            $this->validate([
                'selectedAssetIds' => 'array',
                'selectedAssetIds.*' => ['integer', TenantValidation::exists('equipment_assets')],
            ]);
            $this->step = 5;
        }
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function deploy(ScheduleService $schedules, WorkforceService $workforce, AssetManagementService $assets): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);

        $this->validate([
            'client_account_id' => ['required', TenantValidation::exists('client_accounts')],
            'site_id' => ['required', TenantValidation::exists('sites')],
            'guard_id' => ['required', TenantValidation::exists('guards')],
            'selectedAssetIds' => 'array',
            'selectedAssetIds.*' => ['integer', TenantValidation::exists('equipment_assets')],
        ]);

        $tenantId = TenantContext::id();

        try {
            $assignment = DB::transaction(function () use ($schedules, $workforce, $assets, $tenantId) {
                if ($this->shift_mode === 'existing') {
                    $shift = Shift::where('tenant_id', $tenantId)->findOrFail($this->shift_id);
                } else {
                    $this->validate([
                        'title' => 'required|string|max:255',
                        'starts_at' => 'required|date',
                        'ends_at' => 'required|date|after:starts_at',
                        'required_guards' => 'required|integer|min:1|max:50',
                    ]);

                    $shift = $schedules->createShift([
                        'tenant_id' => $tenantId,
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

                $assignment = $schedules->assignGuard($shift, Guard::findOrFail((int) $this->guard_id));

                if ($this->confirm_now) {
                    $confirmation = ShiftConfirmation::where('shift_assignment_id', $assignment->id)->first();
                    if ($confirmation) {
                        $workforce->confirmShift($confirmation);
                    }
                }

                $assets->issueKitForAssignment(
                    $assignment->load('shift'),
                    array_map('intval', $this->selectedAssetIds),
                );

                return $assignment;
            });
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
            $field = str_contains(strtolower($message), 'available') || str_contains(strtolower($message), 'issue')
                ? 'selectedAssetIds'
                : 'guard_id';
            $this->addError($field, $message);
            $this->step = $field === 'selectedAssetIds' ? 4 : 3;

            return;
        }

        $this->createdAssignmentId = $assignment->id;
        $this->step = 6;
        $kitCount = count($this->selectedAssetIds);
        session()->flash(
            'status',
            $kitCount > 0
                ? "Guard deployed with {$kitCount} kit item".($kitCount === 1 ? '' : 's').'.'
                : 'Guard deployed to site.'
        );
    }

    public function resetWizard(): void
    {
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
            ? Shift::with(['assignments.assignedGuard', 'sitePost'])
                ->where('tenant_id', $tenantId)
                ->where('site_id', $this->site_id)
                ->whereDate('starts_at', $this->date)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->orderBy('starts_at')
                ->get()
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
