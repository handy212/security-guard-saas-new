<?php

namespace App\Livewire\Concerns;

trait HasFormDrawer
{
    public bool $showForm = false;

    public function openForm(): void
    {
        $this->showForm = true;
    }

    public function closeDrawer(): void
    {
        $this->showForm = false;
    }
}
