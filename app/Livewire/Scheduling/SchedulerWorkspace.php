<?php

namespace App\Livewire\Scheduling;

use App\Services\NavigationBuilder;
use Livewire\Attributes\On;
use Livewire\Component;

class SchedulerWorkspace extends Component
{
    public const PANELS = [
        'roster' => ['scheduling.schedule-index', 'schedules.index'],
        'calendar' => ['schedules.calendar-view', 'schedules.calendar'],
        'templates' => ['scheduling.shift-template-index', 'schedules.templates'],
        'deploy' => ['schedules.deploy-wizard', 'schedules.deploy'],
        'attendance' => ['scheduling.attendance-index', 'schedules.attendance'],
        'reconciliation' => ['attendance.reconciliation-board', 'schedules.reconciliation'],
        'deployment-sheet' => ['schedules.deployment-sheet', 'schedules.deployment-sheet'],
        'shift-status' => ['scheduling.shift-status-index', 'schedules.shift-status'],
        'open-shifts' => ['scheduling.open-shifts-index', 'schedules.open-shifts'],
        'shift-exchange' => ['scheduling.shift-exchange-index', 'schedules.shift-exchange'],
        'time-off' => ['scheduling.time-off-index', 'schedules.time-off'],
    ];

    public string $activeTab = 'roster';

    public string $date = '';

    protected $queryString = [
        'activeTab' => ['as' => 'tab', 'except' => 'roster', 'history' => false],
        'date' => ['except' => '', 'history' => false],
    ];

    public function mount(): void
    {
        $this->date = $this->date ?: today()->toDateString();
        $this->setTab($this->activeTab);
    }

    #[On('scheduler-open-panel')]
    public function setTab(string $tab, ?string $date = null): void
    {
        abort_unless(isset(self::PANELS[$tab]), 404);
        abort_unless(isset($this->tabs()[$tab]), 403);
        if ($date !== null) {
            $this->date = $date;
        }
        $this->validate(['date' => 'required|date_format:Y-m-d']);
        $this->activeTab = $tab;
    }

    public function updatedActiveTab(string $tab): void
    {
        $this->setTab($tab);
    }

    #[On('scheduler-date-changed')]
    public function updateSelectedDate(string $date): void
    {
        $this->date = $date;
        $this->validate(['date' => 'required|date_format:Y-m-d']);
    }

    private function tabs(): array
    {
        $navigation = app(NavigationBuilder::class);
        $tabs = [];
        foreach (array_merge(config('navigation.schedules', []), config('navigation.schedules_more', [])) as $link) {
            if (! $navigation->linkVisible($link)) {
                continue;
            }
            foreach (self::PANELS as $tab => [, $route]) {
                if (parse_url(route($route), PHP_URL_PATH) === $link['href']) {
                    $tabs[$tab] = ['label' => $link['label'], 'group' => $link['group'] ?? 'Staffing', 'icon' => $link['icon'] ?? 'schedules'];
                }
            }
        }
        return $tabs;
    }

    public function render()
    {
        $tabs = $this->tabs();
        abort_unless(isset($tabs[$this->activeTab]), 403);
        $params = ['embedded' => true];
        if (in_array($this->activeTab, ['roster', 'attendance', 'deploy', 'deployment-sheet'], true)) {
            $params['date'] = $this->date;
        } elseif ($this->activeTab === 'calendar') {
            $params['cursorDate'] = $this->date;
        }
        $paths = [];
        foreach (self::PANELS as $tab => [, $route]) {
            $paths[parse_url(route($route), PHP_URL_PATH)] = $tab;
        }
        return view('livewire.scheduling.scheduler-workspace', [
            'tabs' => $tabs,
            'panel' => self::PANELS[$this->activeTab][0],
            'panelParams' => $params,
            'paths' => $paths,
        ])->layout('layouts.app');
    }
}
