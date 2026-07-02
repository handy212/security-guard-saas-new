<div>
    <x-page-shell title="Passdown Logs" description="Shift handoff notes between guards on the same post.">
        <div class="stat-grid">
            <x-stat-card compact label="Total logs" :value="$logs->count()" icon="plan" />
            <x-stat-card compact label="Sites" :value="$sites->count()" icon="sites" tone="info" />
            <x-stat-card compact label="Posts" :value="$posts->count()" icon="schedules" />
            <x-stat-card compact label="Today" :value="$logs->filter(fn ($l) => $l->created_at?->isToday())->count()" icon="check" tone="success" />
        </div>

        <x-form-card title="New passdown">
            <div class="grid gap-3 sm:grid-cols-2">
                <x-select wire:model="form.site_id" label="Site">
                    <option value="">Select</option>
                    @foreach($sites as $site)<option value="{{ $site->id }}">{{ $site->name }}</option>@endforeach
                </x-select>
                <x-select wire:model="form.site_post_id" label="Post (optional)">
                    <option value="">Any</option>
                    @foreach($posts as $post)<option value="{{ $post->id }}">{{ $post->name }}</option>@endforeach
                </x-select>
                <x-textarea wire:model="form.content" label="Handoff notes" class="sm:col-span-2" rows="4" />
            </div>
            <x-button class="mt-3" wire:click="save">Save passdown</x-button>
        </x-form-card>

        <x-section-card title="Recent passdowns" class="mt-4">
            @forelse($logs as $log)
                <div class="border-t py-3 first:border-0" wire:key="passdown-{{ $log->id }}">
                    <div class="text-sm font-medium">{{ $log->site?->name }} @if($log->sitePost) · {{ $log->sitePost->name }} @endif</div>
                    <div class="text-xs text-zinc-500">{{ $log->assignedGuard?->full_name }} — {{ $log->created_at?->diffForHumans() }}</div>
                    <p class="mt-1 text-sm text-zinc-700">{{ $log->content }}</p>
                </div>
            @empty
                <x-empty-state title="No passdown logs" />
            @endforelse
        </x-section-card>
    </x-page-shell>
</div>
