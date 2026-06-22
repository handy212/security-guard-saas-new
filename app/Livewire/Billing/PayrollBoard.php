<?php

namespace App\Livewire\Billing;

use Livewire\Component;

class PayrollBoard extends Component
{
    public function render()
    {
        return view('livewire.billing.payroll-board', ['timesheets'=>\App\Models\Timesheet::latest()->limit(80)->get(),'exports'=>\App\Models\AccountingExport::latest()->limit(20)->get()])->layout('layouts.app');
    }
}
