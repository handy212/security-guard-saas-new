<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\ClientAccount;
use App\Models\ClientComplaint;
use App\Models\DailyActivityReport;
use App\Models\DispatchEvent;
use App\Models\EquipmentAsset;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\FleetVehicle;
use App\Models\Guard;
use App\Models\Incident;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\NotificationTemplate;
use App\Models\PassdownLog;
use App\Models\ReportTemplate;
use App\Models\Shift;
use App\Models\Site;
use App\Models\PatrolSession;
use App\Models\SosAlert;
use App\Models\User;
use App\Models\VisitorLog;
use App\Models\WebhookSubscription;
use App\Policies\AuditLogPolicy;
use App\Policies\BranchPolicy;
use App\Policies\ClientAccountPolicy;
use App\Policies\ClientComplaintPolicy;
use App\Policies\DispatchEventPolicy;
use App\Policies\EquipmentAssetPolicy;
use App\Policies\EstimatePolicy;
use App\Policies\ExpensePolicy;
use App\Policies\FleetVehiclePolicy;
use App\Policies\PassdownLogPolicy;
use App\Policies\PatrolSessionPolicy;
use App\Policies\DailyActivityReportPolicy;
use App\Policies\GuardPolicy;
use App\Policies\IncidentPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LeaveRequestPolicy;
use App\Policies\NotificationTemplatePolicy;
use App\Policies\ReportTemplatePolicy;
use App\Policies\ShiftPolicy;
use App\Policies\SitePolicy;
use App\Policies\SosAlertPolicy;
use App\Policies\UserPolicy;
use App\Policies\VisitorLogPolicy;
use App\Policies\WebhookSubscriptionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        AuditLog::class => AuditLogPolicy::class,
        Branch::class => BranchPolicy::class,
        ClientAccount::class => ClientAccountPolicy::class,
        ClientComplaint::class => ClientComplaintPolicy::class,
        DispatchEvent::class => DispatchEventPolicy::class,
        EquipmentAsset::class => EquipmentAssetPolicy::class,
        Estimate::class => EstimatePolicy::class,
        Expense::class => ExpensePolicy::class,
        FleetVehicle::class => FleetVehiclePolicy::class,
        Site::class => SitePolicy::class,
        Guard::class => GuardPolicy::class,
        Shift::class => ShiftPolicy::class,
        Incident::class => IncidentPolicy::class,
        Invoice::class => InvoicePolicy::class,
        NotificationTemplate::class => NotificationTemplatePolicy::class,
        PassdownLog::class => PassdownLogPolicy::class,
        ReportTemplate::class => ReportTemplatePolicy::class,
        SosAlert::class => SosAlertPolicy::class,
        DailyActivityReport::class => DailyActivityReportPolicy::class,
        PatrolSession::class => PatrolSessionPolicy::class,
        User::class => UserPolicy::class,
        VisitorLog::class => VisitorLogPolicy::class,
        WebhookSubscription::class => WebhookSubscriptionPolicy::class,
        LeaveRequest::class => LeaveRequestPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
