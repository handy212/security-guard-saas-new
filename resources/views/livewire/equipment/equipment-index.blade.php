<div class="p-6 space-y-5">
    <h1 class="text-2xl font-bold">Equipment & Uniforms</h1>
    <div class="rounded-xl border bg-white p-4">
        <p class="text-sm text-slate-600">Enterprise module included. Extend this screen with create/edit forms, exports, approvals, and filters.</p>
    </div>
    <div class="overflow-auto rounded-xl border bg-white">
        <table class="w-full text-sm"><thead><tr class="bg-slate-50 text-left"><th class="p-3">Record</th><th>Created</th></tr></thead><tbody>
        @forelse($items as $item)<tr class="border-t"><td class="p-3">#{{ $item->id }}</td><td>{{ $item->created_at }}</td></tr>@empty<tr><td class="p-3" colspan="2">No records yet.</td></tr>@endforelse
        </tbody></table>
    </div>
</div>
