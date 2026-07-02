@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-col gap-3 px-2 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-zinc-500">
            @if ($paginator->firstItem())
                Showing <span class="font-medium text-zinc-700">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span> of <span class="font-medium text-zinc-700">{{ $paginator->total() }}</span>
            @endif
        </div>
        <div class="flex gap-1">
            @if ($paginator->onFirstPage())
                <span class="pagination-btn pagination-btn-disabled">Prev</span>
            @else
                <button wire:click="previousPage('{{ $paginator->getPageName() }}')" class="pagination-btn">Prev</button>
            @endif
            @if ($paginator->hasMorePages())
                <button wire:click="nextPage('{{ $paginator->getPageName() }}')" class="pagination-btn">Next</button>
            @else
                <span class="pagination-btn pagination-btn-disabled">Next</span>
            @endif
        </div>
    </nav>
@endif
