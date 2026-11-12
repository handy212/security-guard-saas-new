<?php

namespace App\Livewire\Concerns;

trait EmbedsInScheduler
{
    public bool $embedded = false;

    protected function queryString(): array
    {
        return $this->embedded ? [] : ($this->queryString ?? []);
    }

    public function renderedEmbedsInScheduler(): void
    {
        if ($this->embedded && (isset($this->date) || isset($this->cursorDate))) {
            $this->dispatch('scheduler-date-changed', date: $this->date ?? $this->cursorDate);
        }
    }
}
