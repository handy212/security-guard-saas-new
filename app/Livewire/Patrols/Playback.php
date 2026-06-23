<?php

namespace App\Livewire\Patrols;

use App\Models\PatrolPlaybackPoint;
use App\Models\PatrolSession;
use App\Support\TenantContext;
use Livewire\Component;

class Playback extends Component
{
    public ?int $sessionId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
        $this->sessionId = PatrolSession::where('tenant_id', TenantContext::id())->latest()->value('id');
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $sessions = PatrolSession::with('assignedGuard')->where('tenant_id', $tenantId)->latest()->limit(30)->get();
        $points = collect();

        if ($this->sessionId) {
            $points = PatrolPlaybackPoint::where('patrol_session_id', $this->sessionId)
                ->orderBy('recorded_at')
                ->get();
        }

        $markers = $points->isNotEmpty()
            ? [['lat' => (float) $points->first()->latitude, 'lng' => (float) $points->first()->longitude, 'label' => 'Start']]
            : [];

        if ($points->count() > 1) {
            $last = $points->last();
            $markers[] = ['lat' => (float) $last->latitude, 'lng' => (float) $last->longitude, 'label' => 'End'];
        }

        return view('livewire.patrols.playback', [
            'sessions' => $sessions,
            'points' => $points,
            'markers' => $markers,
            'polyline' => $points->map(fn ($p) => ['lat' => (float) $p->latitude, 'lng' => (float) $p->longitude])->values()->all(),
        ])->layout('layouts.app');
    }
}
