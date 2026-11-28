<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #18181b; margin: 28px; }
        h1 { font-size: 20px; }
        h2 { font-size: 14px; }
        .meta { padding: 12px; background: #fafafa; line-height: 1.7; }
        .body { line-height: 1.5; white-space: pre-wrap; overflow-wrap: break-word; }
    </style>
</head>
<body>
    @include('partials.document-issuer')
    <h1>{{ $report->title ?: 'Daily activity report' }}</h1>
    <div class="meta">
        <div>Site {{ $report->site?->name ?? '—' }}</div>
        <div>Guard {{ $report->assignedGuard?->full_name ?? '—' }}</div>
        <div>Report date {{ $report->report_date?->format('M j, Y') ?? '—' }}</div>
        <div>Approved {{ $report->approved_at?->format('M j, Y H:i') ?? '—' }}</div>
    </div>
    <h2>Shift summary</h2>
    <div class="body">{{ $report->summary }}</div>
    @if ($includeHandover && $report->handover_notes)
        <h2>Handover notes</h2>
        <div class="body">{{ $report->handover_notes }}</div>
    @endif
</body>
</html>
