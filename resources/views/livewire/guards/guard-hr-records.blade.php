<div>
    <x-page-header title="Guard HR Records" description="Skills, training, documents, and disciplinary history." />

    <div class="space-y-6 p-6">
        <div class="grid gap-4 lg:grid-cols-2">
            <x-form-card title="Add skill" description="Record guard competencies and levels.">
                <form wire:submit="saveSkill" class="space-y-3">
                    <x-select wire:model="skillForm.guard_id" label="Guard">
                        @foreach($guards as $guard)
                            <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="skillForm.skill" label="Skill" placeholder="First aid, CCTV…" />
                    <x-input wire:model="skillForm.level" label="Level" placeholder="Basic, advanced…" />
                    <x-button type="submit">Save skill</x-button>
                </form>
            </x-form-card>

            <x-form-card title="Upload document" description="Attach HR files to a guard profile.">
                <form wire:submit="uploadDocument" class="space-y-3">
                    <x-select wire:model="documentForm.guard_id" label="Guard">
                        @foreach($guards as $guard)
                            <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="documentForm.type" label="Document type" placeholder="ID, contract…" />
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">File</label>
                        <input wire:model="documentFile" type="file" class="form-input w-full text-sm">
                    </div>
                    <x-button type="submit">Upload</x-button>
                </form>
            </x-form-card>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <x-section-card title="Skills">
                @forelse($skills as $row)
                    <div class="border-t border-slate-100 py-3 first:border-0 first:pt-0">
                        <div class="text-sm font-medium text-slate-900">{{ $row->assignedGuard?->full_name }}</div>
                        <div class="text-xs text-slate-500">{{ $row->skill }} · {{ $row->level ?: '—' }}</div>
                    </div>
                @empty
                    <x-empty-state title="No skills" description="Add guard skills above." />
                @endforelse
            </x-section-card>

            <x-section-card title="Training">
                @forelse($training as $row)
                    <div class="border-t border-slate-100 py-3 first:border-0 first:pt-0">
                        <div class="text-sm font-medium text-slate-900">{{ $row->course_name }}</div>
                        <div class="text-xs text-slate-500">{{ $row->completed_on?->format('M j, Y') ?? 'Scheduled' }}</div>
                    </div>
                @empty
                    <x-empty-state title="No training" description="Training records appear here." />
                @endforelse
            </x-section-card>

            <x-section-card title="Disciplinary">
                @forelse($disciplinary as $row)
                    <div class="border-t border-slate-100 py-3 first:border-0 first:pt-0">
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-sm font-medium text-slate-900">{{ $row->type }}</div>
                            @if($row->status ?? null)
                                <x-badge :status="$row->status" />
                            @endif
                        </div>
                        <div class="mt-1 text-xs text-slate-500">{{ Str::limit($row->description, 80) }}</div>
                    </div>
                @empty
                    <x-empty-state title="No records" description="Disciplinary actions appear here." />
                @endforelse
            </x-section-card>
        </div>
    </div>
</div>
