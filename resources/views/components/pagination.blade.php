@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-4 px-2 py-3">
        <div class="text-sm text-slate-500">
            @if ($paginator->firstItem())
                Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
            @endif
        </div>
        <div class="flex gap-1">
            @if ($paginator->onFirstPage())
                <span class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-400">Prev</span>
            @else
                <button wire:click="previousPage('{{ $paginator->getPageName() }}')" class="btn-secondary !px-3 !py-1.5 text-sm">Prev</button>
            @endif
            @if ($paginator->hasMorePages())
                <button wire:click="nextPage('{{ $paginator->getPageName() }}')" class="btn-secondary !px-3 !py-1.5 text-sm">Next</button>
            @else
                <span class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-400">Next</span>
            @endif
        </div>
    </nav>
@endif
