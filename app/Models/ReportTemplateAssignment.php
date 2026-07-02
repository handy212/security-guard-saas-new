<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTemplateAssignment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'report_template_id', 'site_id', 'site_post_id',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function sitePost(): BelongsTo
    {
        return $this->belongsTo(SitePost::class);
    }
}
