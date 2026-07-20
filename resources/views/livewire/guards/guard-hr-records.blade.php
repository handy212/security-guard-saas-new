<div>
    <x-page-shell
        title="Guard HR Records"
        description="Skills, training, documents, and disciplinary history."
        :breadcrumbs="[
            ['label' => 'Guards', 'href' => route('guards.index')],
            ['label' => 'HR records'],
        ]"
    >
        <x-slot:actions>
            <x-button variant="secondary" wire:click="openForm('document')">Upload document</x-button>
            <x-button variant="secondary" wire:click="openForm('training')">Add training</x-button>
            <x-button variant="secondary" wire:click="openForm('disciplinary')">Log disciplinary</x-button>
            <x-button wire:click="openForm('skill')">Add skill</x-button>
        </x-slot:actions>

        <x-flash-status />

        <div class="stat-grid">
            <x-stat-card compact label="Guards" :value="$guards->count()" icon="guards" />
            <x-stat-card compact label="Skills" :value="$skills->count()" icon="check" tone="info" />
            <x-stat-card compact label="Documents" :value="$documents->count()" icon="billing" />
            <x-stat-card compact label="Disciplinary" :value="$disciplinary->count()" icon="incidents" :tone="$disciplinary->count() ? 'warning' : 'default'" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-section-card title="Skills" :description="$skills->count().' on file'" flush>
                <x-slot:actions>
                    <button type="button" wire:click="openForm('skill')" class="table-action">Add</button>
                </x-slot:actions>
                @forelse($skills as $row)
                    <div class="list-row" wire:key="skill-{{ $row->id }}">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $row->assignedGuard?->full_name ?? '—' }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $row->skill }} · {{ $row->level ?: '—' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="p-3">
                        <x-empty-state compact title="No skills" description="Record guard competencies.">
                            <x-slot:actions>
                                <x-button size="sm" wire:click="openForm('skill')">Add skill</x-button>
                            </x-slot:actions>
                        </x-empty-state>
                    </div>
                @endforelse
            </x-section-card>

            <x-section-card title="Training" :description="$training->count().' records'" flush>
                <x-slot:actions>
                    <button type="button" wire:click="openForm('training')" class="table-action">Add</button>
                </x-slot:actions>
                @forelse($training as $row)
                    <div class="list-row" wire:key="training-{{ $row->id }}">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $row->course_name }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $row->assignedGuard?->full_name ?? '—' }}
                                · <span class="tabular-nums">{{ $row->completed_on?->format('M j, Y') ?? 'Scheduled' }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-3">
                        <x-empty-state compact title="No training" description="Log completed or scheduled courses.">
                            <x-slot:actions>
                                <x-button size="sm" wire:click="openForm('training')">Add training</x-button>
                            </x-slot:actions>
                        </x-empty-state>
                    </div>
                @endforelse
            </x-section-card>

            <x-section-card title="Documents" :description="$documents->count().' files'" flush>
                <x-slot:actions>
                    <button type="button" wire:click="openForm('document')" class="table-action">Upload</button>
                </x-slot:actions>
                @forelse($documents as $row)
                    <div class="list-row" wire:key="doc-{{ $row->id }}">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $row->typeLabel() }}</span>
                                @if ($row->status ?? null)
                                    <x-badge :status="$row->status" />
                                @endif
                            </div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $row->assignedGuard?->full_name ?? '—' }}
                                @if ($row->expires_at)
                                    · expires <span class="tabular-nums">{{ $row->expires_at->format('M j, Y') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-3">
                        <x-empty-state compact title="No documents" description="Upload ID, contracts, and licenses.">
                            <x-slot:actions>
                                <x-button size="sm" wire:click="openForm('document')">Upload document</x-button>
                            </x-slot:actions>
                        </x-empty-state>
                    </div>
                @endforelse
            </x-section-card>

            <x-section-card title="Disciplinary" :description="$disciplinary->count().' records'" flush>
                <x-slot:actions>
                    <button type="button" wire:click="openForm('disciplinary')" class="table-action">Log</button>
                </x-slot:actions>
                @forelse($disciplinary as $row)
                    <div class="list-row-start" wire:key="disc-{{ $row->id }}">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $row->type }}</div>
                                @if($row->status ?? null)
                                    <x-badge :status="$row->status" />
                                @endif
                            </div>
                            <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $row->assignedGuard?->full_name ?? '—' }}
                                @if ($row->occurred_on)
                                    · <span class="tabular-nums">{{ $row->occurred_on->format('M j, Y') }}</span>
                                @endif
                            </div>
                            <div class="mt-1 text-xs text-zinc-600 dark:text-zinc-300">{{ Str::limit($row->description, 80) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="p-3">
                        <x-empty-state compact title="No records" description="Disciplinary actions appear here.">
                            <x-slot:actions>
                                <x-button size="sm" wire:click="openForm('disciplinary')">Log disciplinary</x-button>
                            </x-slot:actions>
                        </x-empty-state>
                    </div>
                @endforelse
            </x-section-card>
        </div>
    </x-page-shell>

    @if ($activeForm === 'skill')
        <x-drawer title="Add skill" description="Record a guard competency and level." width="md" close-method="closeForm">
            <x-drawer-form wire:submit.prevent="saveSkill" submit-label="Save skill" close-method="closeForm" target="saveSkill">
                <x-form-section title="Skill">
                    <x-select wire:model="skillForm.guard_id" label="Guard *" class="sm:col-span-2">
                        <option value="">Select guard</option>
                        @foreach($guards as $guard)
                            <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="skillForm.skill" label="Skill *" placeholder="First aid, CCTV…" class="sm:col-span-2" />
                    <x-input wire:model="skillForm.level" label="Level *" placeholder="Basic, advanced…" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif

    @if ($activeForm === 'document')
        <x-drawer title="Upload document" description="Attach an HR file to a guard profile." width="md" close-method="closeForm">
            <x-drawer-form wire:submit.prevent="uploadDocument" submit-label="Upload" close-method="closeForm" target="uploadDocument">
                <x-form-section title="Document">
                    <x-select wire:model="documentForm.guard_id" label="Guard *" class="sm:col-span-2">
                        <option value="">Select guard</option>
                        @foreach($guards as $guard)
                            <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="documentForm.type" label="Document type *" placeholder="ID, contract, license…" class="sm:col-span-2" />
                    <x-input wire:model="documentForm.expires_at" type="date" label="Expires" class="sm:col-span-2" />
                    <x-file-input wire:model="documentFile" label="File *" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif

    @if ($activeForm === 'training')
        <x-drawer title="Add training" description="Log a completed or scheduled course." width="md" close-method="closeForm">
            <x-drawer-form wire:submit.prevent="saveTraining" submit-label="Save training" close-method="closeForm" target="saveTraining">
                <x-form-section title="Course">
                    <x-select wire:model="trainingForm.guard_id" label="Guard *" class="sm:col-span-2">
                        <option value="">Select guard</option>
                        @foreach($guards as $guard)
                            <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="trainingForm.course_name" label="Course name *" class="sm:col-span-2" />
                    <x-input wire:model="trainingForm.completed_on" type="date" label="Completed" />
                    <x-input wire:model="trainingForm.expires_on" type="date" label="Expires" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif

    @if ($activeForm === 'disciplinary')
        <x-drawer title="Log disciplinary" description="Record a warning or disciplinary action." width="md" close-method="closeForm">
            <x-drawer-form wire:submit.prevent="saveDisciplinary" submit-label="Save record" close-method="closeForm" target="saveDisciplinary">
                <x-form-section title="Incident">
                    <x-select wire:model="disciplinaryForm.guard_id" label="Guard *" class="sm:col-span-2">
                        <option value="">Select guard</option>
                        @foreach($guards as $guard)
                            <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="disciplinaryForm.occurred_on" type="date" label="Occurred on *" />
                    <x-input wire:model="disciplinaryForm.type" label="Type *" placeholder="Warning, suspension…" />
                    <x-textarea wire:model="disciplinaryForm.description" label="Description *" rows="3" class="sm:col-span-2" />
                    <x-textarea wire:model="disciplinaryForm.action_taken" label="Action taken *" rows="2" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
