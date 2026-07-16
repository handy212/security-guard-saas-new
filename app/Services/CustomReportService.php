<?php

namespace App\Services;

use App\Models\CustomReportSubmission;
use App\Models\ReportTemplate;
use App\Models\ReportTemplateAssignment;
use App\Models\ReportTemplateField;
use App\Models\User;
use App\Support\TenantContext;

class CustomReportService
{
    public function createTemplate(array $data, array $fields): ReportTemplate
    {
        $template = ReportTemplate::create($data + ['tenant_id' => TenantContext::id()]);

        $this->syncFields($template, $fields);

        return $template->load('fields');
    }

    public function updateTemplate(ReportTemplate $template, array $data, array $fields): ReportTemplate
    {
        $template->update(collect($data)->only(['name', 'description', 'client_account_id', 'is_active'])->all());
        $this->syncFields($template, $fields);

        return $template->fresh('fields');
    }

    public function deleteTemplate(ReportTemplate $template): void
    {
        $template->assignments()->delete();
        $template->fields()->delete();
        $template->delete();
    }

    private function syncFields(ReportTemplate $template, array $fields): void
    {
        $template->fields()->delete();

        foreach ($fields as $index => $field) {
            ReportTemplateField::create([
                'report_template_id' => $template->id,
                'label' => $field['label'],
                'field_type' => $field['field_type'],
                'is_required' => $field['is_required'] ?? false,
                'sort_order' => $field['sort_order'] ?? $index,
                'options' => $field['options'] ?? null,
            ]);
        }
    }

    public function assignToSite(int $templateId, int $siteId, ?int $sitePostId = null): ReportTemplateAssignment
    {
        return ReportTemplateAssignment::firstOrCreate([
            'tenant_id' => TenantContext::id(),
            'report_template_id' => $templateId,
            'site_id' => $siteId,
            'site_post_id' => $sitePostId,
        ]);
    }

    public function saveDraft(int $templateId, int $guardId, int $siteId, array $data, ?int $assignmentId = null): CustomReportSubmission
    {
        return CustomReportSubmission::updateOrCreate(
            [
                'tenant_id' => TenantContext::id(),
                'report_template_id' => $templateId,
                'guard_id' => $guardId,
                'site_id' => $siteId,
                'status' => 'draft',
            ],
            [
                'shift_assignment_id' => $assignmentId,
                'data' => $data,
            ]
        );
    }

    public function submit(CustomReportSubmission $submission): CustomReportSubmission
    {
        $submission->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return $submission->fresh();
    }

    public function deliverToClient(CustomReportSubmission $submission): void
    {
        $submission->update(['delivered_at' => now(), 'status' => 'delivered']);

        User::role('client')
            ->where('tenant_id', $submission->tenant_id)
            ->when($submission->site?->client_account_id, fn ($q) => $q->where('client_account_id', $submission->site->client_account_id))
            ->each(function (User $user) use ($submission) {
                $user->notify(new \App\Notifications\GenericGuardOpsNotification(
                    'New report available',
                    'A custom report has been submitted for '.$submission->site?->name,
                    '/client-portal',
                    'report.delivered',
                ));
            });
    }

    public function templatesForSite(int $siteId)
    {
        return ReportTemplate::where('is_active', true)
            ->whereHas('assignments', fn ($q) => $q->where('site_id', $siteId))
            ->with('fields')
            ->get();
    }
}
