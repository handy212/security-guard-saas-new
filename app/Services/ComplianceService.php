<?php
namespace App\Services;
use App\Models\GuardCertification; use App\Models\GuardDocument;
class ComplianceService {
    public function expiringCertifications(int $days = 30) { return GuardCertification::query()->whereDate('expires_on','<=',now()->addDays($days))->get(); }
    public function expiringDocuments(int $days = 30) { return GuardDocument::query()->whereDate('expires_on','<=',now()->addDays($days))->get(); }
}
