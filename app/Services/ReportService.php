<?php

namespace App\Services;

class ReportService
{
    /**
     * Generate daily activity reports and PDF exports.
     */
    public function handle(array $payload = []): array
    {
        return ['ok' => true, 'message' => 'Generate daily activity reports and PDF exports.'];
    }
}
