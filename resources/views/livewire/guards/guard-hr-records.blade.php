<div class="p-6 space-y-6">
    <h1 class="text-2xl font-bold">Guard HR Records</h1>
    <div class="grid gap-4 lg:grid-cols-2">
        <form wire:submit="saveSkill" class="space-y-2 rounded-xl border bg-white p-4">
            <h2 class="font-bold">Add Skill</h2>
            <select wire:model="skillForm.guard_id" class="w-full rounded border p-2">@foreach($guards as $guard)<option value="{{ $guard->id }}">{{ $guard->full_name }}</option>@endforeach</select>
            <input wire:model="skillForm.skill" class="w-full rounded border p-2" placeholder="Skill">
            <input wire:model="skillForm.level" class="w-full rounded border p-2" placeholder="Level">
            <button class="rounded bg-slate-900 px-3 py-2 text-white">Save skill</button>
        </form>
        <form wire:submit="uploadDocument" class="space-y-2 rounded-xl border bg-white p-4">
            <h2 class="font-bold">Upload Document</h2>
            <select wire:model="documentForm.guard_id" class="w-full rounded border p-2">@foreach($guards as $guard)<option value="{{ $guard->id }}">{{ $guard->full_name }}</option>@endforeach</select>
            <input wire:model="documentForm.type" class="w-full rounded border p-2" placeholder="Document type">
            <input wire:model="documentFile" type="file" class="w-full rounded border p-2">
            <button class="rounded bg-slate-900 px-3 py-2 text-white">Upload</button>
        </form>
    </div>
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><h3 class="font-bold">Skills</h3>@foreach($skills as $row)<div class="border-t py-2">{{ $row->assignedGuard?->full_name }} · {{ $row->skill }}</div>@endforeach</div>
        <div class="rounded-xl border bg-white p-4"><h3 class="font-bold">Training</h3>@foreach($training as $row)<div class="border-t py-2">{{ $row->course_name }}</div>@endforeach</div>
        <div class="rounded-xl border bg-white p-4"><h3 class="font-bold">Disciplinary</h3>@foreach($disciplinary as $row)<div class="border-t py-2">{{ $row->type }} · {{ $row->description }}</div>@endforeach</div>
    </div>
</div>
