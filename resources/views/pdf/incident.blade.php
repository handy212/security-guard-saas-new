<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Incident #{{ $incident->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; }
        .meta { margin-bottom: 16px; color: #444; }
    </style>
</head>
<body>
    <h1>{{ $incident->title }}</h1>
    <div class="meta">
        <div>Site: {{ $incident->site?->name }}</div>
        <div>Type: {{ $incident->type }} | Severity: {{ $incident->severity }} | Status: {{ $incident->status }}</div>
        <div>Reported: {{ optional($incident->reported_at)->format('Y-m-d H:i') }}</div>
    </div>
    <p>{{ $incident->description }}</p>
</body>
</html>
