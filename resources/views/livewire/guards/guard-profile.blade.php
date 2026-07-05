<div>
    <x-page-shell
        :title="$guard->full_name"
        :description="$guard->employee_number ? 'Employee #'.$guard->employee_number : 'Guard profile'"
        :breadcrumbs="[
            ['label' => 'Guards', 'href' => route('guards.index')],
            ['label' => $guard->full_name],
        ]"
    >
        <x-slot:actions>
            @if ($idCardEligibility['can_download'])
                <x-button variant="secondary" :href="route('guards.id-card.print', $guard)" target="_blank" title="Open print-ready ID card (matches preview)">
                    Print ID card
                </x-button>
            @elseif ($idCardEligibility['action'] === 'regenerate')
                <x-button variant="secondary" wire:click="setTab('verification')" title="{{ $idCardEligibility['message'] }}">
                    Set up ID card
                </x-button>
            @elseif ($idCardEligibility['action'] === 'verification')
                <x-button variant="secondary" wire:click="setTab('verification')" title="{{ $idCardEligibility['message'] }}">
                    Verify for ID card
                </x-button>
            @else
                <x-button variant="secondary" type="button" disabled class="cursor-not-allowed opacity-60" title="{{ $idCardEligibility['message'] }}">
                    Print ID card
                </x-button>
            @endif
            <x-button variant="secondary" :href="route('guards.index')">Back to roster</x-button>
        </x-slot:actions>

        @error('verification')
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ $message }}
            </div>
        @enderror

        <div class="flex flex-wrap items-center gap-2">
            <x-badge :status="$guard->status" />
            <x-badge :status="$guard->verification_status" />
            @if ($guard->rank)
                <span class="text-xs text-zinc-500">{{ $guard->rank }}</span>
            @endif
        </div>

        <x-tabs :tabs="$profileTabs" :active="$activeTab" />

        @if ($activeTab === 'overview')
            <div class="grid gap-4 lg:grid-cols-3">
                <div class="lg:col-span-1">
                    <x-section-card title="Photo">
                        @if ($guard->photo_path)
                            <img src="{{ route('files.guard-photo', $guard) }}" alt="" class="mx-auto h-32 w-32 rounded-full object-cover">
                        @else
                            <div class="mx-auto flex h-32 w-32 items-center justify-center rounded-full bg-zinc-100 text-2xl font-semibold text-zinc-500">
                                {{ strtoupper(substr($guard->first_name, 0, 1)) }}
                            </div>
                        @endif
                        <form wire:submit="uploadPhoto" class="mt-3 space-y-2">
                            <input wire:model="photoFile" type="file" accept="image/*" class="form-input text-xs">
                            @error('photoFile') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            <x-button type="submit" size="sm">Upload photo</x-button>
                        </form>
                    </x-section-card>
                </div>
                <div class="lg:col-span-2">
                    <x-form-card title="Profile details">
                        <form wire:submit="saveOverview" class="grid gap-3 sm:grid-cols-2">
                            <x-input wire:model="overviewForm.employee_number" label="Employee #" />
                            <x-select wire:model="overviewForm.status" label="Status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </x-select>
                            <x-input wire:model="overviewForm.first_name" label="First name" />
                            <x-input wire:model="overviewForm.last_name" label="Last name" />
                            <x-input wire:model="overviewForm.phone" label="Phone" />
                            <x-input wire:model="overviewForm.email" label="Email" type="email" />
                            <x-input wire:model="overviewForm.rank" label="Rank" />
                            <x-select wire:model="overviewForm.branch_id" label="Branch">
                                <option value="">None</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </x-select>
                            <x-input wire:model="overviewForm.license_number" label="License #" />
                            <x-input wire:model="overviewForm.license_expires_at" label="License expires" type="date" />
                            <x-input wire:model="overviewForm.hourly_rate" label="Hourly rate" type="number" step="0.01" />
                            <x-select wire:model="overviewForm.user_id" label="Linked user account">
                                <option value="">None</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </x-select>
                            <x-input wire:model="overviewForm.emergency_contact_name" label="Emergency contact" />
                            <x-input wire:model="overviewForm.emergency_contact_phone" label="Emergency phone" />
                            <label class="flex items-center gap-2 text-sm sm:col-span-2">
                                <input type="checkbox" wire:model="overviewForm.show_current_assignment" class="rounded border-zinc-300">
                                Show current assignment on public verification page
                            </label>
                            <div class="sm:col-span-2">
                                <x-button type="submit">Save profile</x-button>
                            </div>
                        </form>
                    </x-form-card>
                </div>
            </div>
        @endif

        @if ($activeTab === 'documents')
            <div class="grid gap-4 lg:grid-cols-2">
                <x-form-card title="Upload document">
                    <form wire:submit="uploadDocument" class="space-y-3">
                        <x-select wire:model="documentForm.type" label="Type">
                            <option value="id">National ID</option>
                            <option value="passport">Passport</option>
                            <option value="contract">Contract</option>
                            <option value="license">License</option>
                            <option value="other">Other</option>
                        </x-select>
                        <x-input wire:model="documentForm.expires_at" label="Expires" type="date" />
                        <input wire:model="documentFile" type="file" class="form-input text-sm">
                        @error('documentFile') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <x-button type="submit">Upload</x-button>
                    </form>
                </x-form-card>
                <x-section-card title="Documents on file">
                    @forelse($guard->documents as $doc)
                        <div class="flex items-center justify-between border-t border-zinc-100 py-2 text-sm first:border-0">
                            <div>
                                <div class="font-medium">{{ ucfirst($doc->type) }}</div>
                                <div class="text-xs text-zinc-500">{{ $doc->expires_at?->format('M j, Y') ?? 'No expiry' }}</div>
                            </div>
                            <a href="{{ route('files.guard-document', $doc) }}" target="_blank" class="btn-link">View</a>
                        </div>
                    @empty
                        <x-empty-state title="No documents" description="Upload ID and license documents for KYG." />
                    @endforelse
                </x-section-card>
            </div>
        @endif

        @if ($activeTab === 'certifications')
            <div class="grid gap-4 lg:grid-cols-2">
                <x-form-card title="Add certification">
                    <form wire:submit="saveCertification" class="space-y-3">
                        <x-input wire:model="certForm.name" label="Name" />
                        <x-input wire:model="certForm.issuer" label="Issuer" />
                        <x-input wire:model="certForm.issued_at" label="Issued" type="date" />
                        <x-input wire:model="certForm.expires_at" label="Expires" type="date" />
                        <x-button type="submit">Add</x-button>
                    </form>
                </x-form-card>
                <x-section-card title="Certifications">
                    @forelse($guard->certifications as $cert)
                        <div class="flex items-center justify-between border-t border-zinc-100 py-2 text-sm first:border-0">
                            <div>
                                <div class="font-medium">{{ $cert->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $cert->expires_at?->format('M j, Y') ?? 'No expiry' }}</div>
                            </div>
                            <button wire:click="deleteCertification({{ $cert->id }})" wire:confirm="Remove?" class="text-xs text-red-600">Remove</button>
                        </div>
                    @empty
                        <x-empty-state title="No certifications" />
                    @endforelse
                </x-section-card>
            </div>
        @endif

        @if ($activeTab === 'training')
            <div class="grid gap-4 lg:grid-cols-2">
                <x-form-card title="Add skill">
                    <form wire:submit="saveSkill" class="space-y-3">
                        <x-input wire:model="skillForm.skill" label="Skill" />
                        <x-input wire:model="skillForm.level" label="Level" />
                        <x-button type="submit">Save skill</x-button>
                    </form>
                </x-form-card>
                <x-form-card title="Add training">
                    <form wire:submit="saveTraining" class="space-y-3">
                        <x-input wire:model="trainingForm.course_name" label="Course" />
                        <x-input wire:model="trainingForm.provider" label="Provider" />
                        <x-input wire:model="trainingForm.completed_on" label="Completed" type="date" />
                        <x-input wire:model="trainingForm.expires_on" label="Expires" type="date" />
                        <x-button type="submit">Save training</x-button>
                    </form>
                </x-form-card>
            </div>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <x-section-card title="Skills">
                    @forelse($guard->skills as $skill)
                        <div class="border-t border-zinc-100 py-2 text-sm first:border-0">{{ $skill->skill }} · {{ $skill->level }}</div>
                    @empty
                        <p class="text-xs text-zinc-500">No skills recorded.</p>
                    @endforelse
                </x-section-card>
                <x-section-card title="Training">
                    @forelse($guard->trainingRecords as $row)
                        <div class="border-t border-zinc-100 py-2 text-sm first:border-0">
                            <div class="font-medium">{{ $row->course_name }}</div>
                            <div class="text-xs text-zinc-500">{{ $row->completed_on?->format('M j, Y') ?? 'Scheduled' }}</div>
                        </div>
                    @empty
                        <p class="text-xs text-zinc-500">No training records.</p>
                    @endforelse
                </x-section-card>
            </div>
        @endif

        @if ($activeTab === 'disciplinary')
            <div class="grid gap-4 lg:grid-cols-2">
                <x-form-card title="Record disciplinary action">
                    <form wire:submit="saveDisciplinary" class="space-y-3">
                        <x-input wire:model="disciplinaryForm.occurred_on" label="Date" type="date" />
                        <x-select wire:model="disciplinaryForm.type" label="Type">
                            <option value="warning">Warning</option>
                            <option value="suspension">Suspension</option>
                            <option value="termination">Termination</option>
                        </x-select>
                        <x-textarea wire:model="disciplinaryForm.description" label="Description" rows="3" />
                        <x-input wire:model="disciplinaryForm.action_taken" label="Action taken" />
                        <x-button type="submit">Save record</x-button>
                    </form>
                </x-form-card>
                <x-section-card title="History">
                    @forelse($guard->disciplinaryRecords as $row)
                        <div class="border-t border-zinc-100 py-2 text-sm first:border-0">
                            <div class="font-medium">{{ ucfirst($row->type) }} · {{ $row->occurred_on?->format('M j, Y') }}</div>
                            <div class="text-xs text-zinc-500">{{ Str::limit($row->description, 100) }}</div>
                        </div>
                    @empty
                        <x-empty-state title="No records" />
                    @endforelse
                </x-section-card>
            </div>
        @endif

        @if ($activeTab === 'verification')
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(300px,360px)] xl:items-start">
                <div class="space-y-4">
                    <x-section-card title="Vetting checklist">
                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
                        <div>
                    @error('verification')
                        <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                            {{ $message }}
                        </div>
                    @enderror

                    <ul class="space-y-2">
                        @foreach($checklist['items'] as $item)
                            <li class="flex items-center justify-between gap-2 text-sm">
                                <div class="flex items-center gap-2">
                                    @if ($item['passed'])
                                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">✓</span>
                                        <span class="text-zinc-700">{{ $item['label'] }}</span>
                                    @else
                                        <span class="flex h-5 w-5 items-center justify-center rounded-full border border-zinc-300 text-zinc-400">○</span>
                                        <span class="text-zinc-900">{{ $item['label'] }}</span>
                                    @endif
                                </div>
                                @if (! $item['passed'] && ! empty($item['tab']))
                                    <button type="button" wire:click="setTab('{{ $item['tab'] }}')" class="text-xs font-medium text-zinc-600 hover:text-zinc-900 hover:underline">
                                        Fix →
                                    </button>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    @if ($checklist['ready'])
                        <p class="mt-3 text-xs text-emerald-700">All requirements met — ready to verify.</p>
                    @else
                        <p class="mt-3 text-xs text-zinc-500">Complete every item above before marking this guard as verified.</p>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($guard->verification_status !== 'verified')
                            <x-button wire:click="submitForReview" variant="secondary" size="sm">Submit for review</x-button>
                        @endif
                        @if ($checklist['ready'] && $guard->verification_status !== 'verified')
                            <x-button wire:click="markVerified" size="sm">Mark verified</x-button>
                        @elseif ($guard->verification_status !== 'verified')
                            <x-button type="button" size="sm" disabled class="opacity-50 cursor-not-allowed">Mark verified</x-button>
                        @endif
                        @if ($guard->verification_status === 'verified')
                            <x-button wire:click="suspend" variant="danger" size="sm" wire:confirm="Suspend this guard's verification?">Suspend</x-button>
                        @endif
                    </div>
                    @if ($guard->verified_at)
                        <p class="mt-3 text-xs text-zinc-500">Verified {{ $guard->verified_at->format('M j, Y g:i A') }}</p>
                    @endif
                        </div>

                        <div class="lg:border-l lg:border-zinc-200 lg:pl-6">
                            <h3 class="mb-3 text-sm font-semibold text-zinc-900">QR verification</h3>
                            @if ($guard->verification_status !== 'verified')
                                <p class="text-sm text-zinc-500">Complete the checklist and mark this guard as verified to activate the QR code and ID card.</p>
                            @elseif ($verifyUrl && $qrSvg)
                                <div class="flex flex-col items-center lg:items-start">
                                    <div class="rounded-lg border border-zinc-200 bg-white p-2">{!! $qrSvg !!}</div>
                                    <p class="mt-2 max-w-[200px] break-all text-xs text-zinc-500">{{ $verifyUrl }}</p>
                                    @if ($lastScannedAt)
                                        <p class="mt-2 text-xs text-zinc-500">Last scanned {{ $lastScannedAt->format('M j, Y g:i A') }}</p>
                                    @endif
                                    @if ($tokenExpiresAt)
                                        <p class="text-xs text-zinc-400">Token expires {{ $tokenExpiresAt->format('M j, Y') }}</p>
                                    @endif
                                    <x-button
                                        wire:click="rotateQrToken"
                                        variant="secondary"
                                        size="sm"
                                        class="mt-3"
                                        wire:confirm="Rotate this guard's QR code? Any already-printed ID cards will stop verifying until reprinted."
                                    >Rotate QR code</x-button>
                                </div>
                            @else
                                <p class="text-sm text-zinc-500">No active token. Issue one to enable verification and the ID card.</p>
                                <x-button wire:click="issueQrToken" size="sm" class="mt-3">Issue QR code</x-button>
                            @endif
                        </div>
                    </div>
                </x-section-card>
                </div>

                <div class="xl:sticky xl:top-4">
                    <x-section-card title="ID card">
                        @if ($idCardEligibility['can_download'])
                            <div class="mb-4 flex justify-center">
                                <x-segment-control
                                    field="idCardPreviewSide"
                                    :active="$idCardPreviewSide"
                                    :options="['front' => 'Front', 'back' => 'Back']"
                                />
                            </div>

                            <div class="flex justify-center" wire:key="guard-card-{{ $idCardPreviewSide }}-{{ $idCardBrand['orientation'] ?? 'portrait' }}-{{ $guard->id }}">
                                <x-guard-id-card-preview
                                    :brand="$idCardBrand"
                                    :card="$idCardData"
                                    :side="$idCardPreviewSide"
                                    :photo-url="$photoUrl"
                                    :logo-url="$idCardBrand['logo_url']"
                                    :qr-svg="$qrSvg"
                                />
                            </div>

                            @unless ($guard->photo_path)
                                <p class="mt-3 text-center text-xs text-amber-700">No photo — upload one on the Profile tab.</p>
                            @endunless

                            <div class="mt-4 flex flex-col gap-2">
                                <x-button :href="route('guards.id-card.print', $guard)" target="_blank" class="w-full justify-center">Print ID card</x-button>
                                <x-button variant="secondary" :href="route('settings.id-card')" class="w-full justify-center">Customize design</x-button>
                            </div>
                        @else
                            <x-empty-state
                                compact
                                :title="$idCardEligibility['action'] === 'regenerate' ? 'QR token required' : 'ID card not available'"
                                :description="$idCardEligibility['message']"
                            >
                                <x-slot:actions>
                                    @if ($idCardEligibility['action'] === 'regenerate')
                                        <x-button wire:click="issueQrToken" size="sm">Issue QR code</x-button>
                                    @elseif ($idCardEligibility['action'] === 'verification')
                                        <x-button wire:click="markVerified" size="sm" :disabled="! $checklist['ready']">Mark verified</x-button>
                                    @endif
                                </x-slot:actions>
                            </x-empty-state>
                        @endif
                    </x-section-card>
                </div>
            </div>
        @endif
    </x-page-shell>
</div>
