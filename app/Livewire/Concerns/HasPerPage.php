<?php

namespace App\Livewire\Concerns;

trait HasPerPage
{
    public int $perPage = 20;

    public function initializeHasPerPage(): void
    {
        $this->perPage = $this->defaultPerPage();
    }

    public function updatedPerPage(mixed $value): void
    {
        $this->perPage = $this->normalizePerPage($value);
        $this->resetPage();
    }

    protected function perPageOptions(): array
    {
        return [10, 15, 20, 25, 50];
    }

    protected function defaultPerPage(): int
    {
        return 20;
    }

    protected function resolvedPerPage(): int
    {
        return $this->normalizePerPage($this->perPage);
    }

    protected function normalizePerPage(mixed $value): int
    {
        $perPage = (int) $value;

        return in_array($perPage, $this->perPageOptions(), true)
            ? $perPage
            : $this->defaultPerPage();
    }
}
