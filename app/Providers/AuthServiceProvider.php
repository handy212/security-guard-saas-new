<?php

namespace App\Providers;

use App\Models\ClientAccount;
use App\Models\ClientComplaint;
use App\Models\DailyActivityReport;
use App\Models\EquipmentAsset;
use App\Models\Guard;
use App\Models\Incident;
use App\Models\Invoice;
use App\Models\Shift;
use App\Models\Site;
use App\Models\PatrolSession;
use App\Models\SosAlert;
use App\Models\VisitorLog;
use App\Policies\ClientAccountPolicy;
use App\Policies\ClientComplaintPolicy;
use App\Policies\EquipmentAssetPolicy;
use App\Policies\PatrolSessionPolicy;
use App\Policies\DailyActivityReportPolicy;
use App\Policies\GuardPolicy;
use App\Policies\IncidentPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\ShiftPolicy;
use App\Policies\SitePolicy;
use App\Policies\SosAlertPolicy;
use App\Policies\VisitorLogPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        ClientAccount::class => ClientAccountPolicy::class,
        ClientComplaint::class => ClientComplaintPolicy::class,
        Site::class => SitePolicy::class,
        Guard::class => GuardPolicy::class,
        Shift::class => ShiftPolicy::class,
        Incident::class => IncidentPolicy::class,
        Invoice::class => InvoicePolicy::class,
        SosAlert::class => SosAlertPolicy::class,
        DailyActivityReport::class => DailyActivityReportPolicy::class,
        PatrolSession::class => PatrolSessionPolicy::class,
        VisitorLog::class => VisitorLogPolicy::class,
        EquipmentAsset::class => EquipmentAssetPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
