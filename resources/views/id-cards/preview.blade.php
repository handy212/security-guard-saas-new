<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card Preview</title>
    @include('pdf.id-cards._styles')
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f4f5;
            padding: 16px;
        }

        .preview-scale {
            transform: scale(1.65);
            transform-origin: center center;
        }

        .card-shell {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }
    </style>
</head>
<body>
    <div class="preview-scale">
        <div class="card-shell">
            @if (($side ?? 'front') === 'back')
                @include('pdf.id-cards.back')
            @else
                @include('pdf.id-cards.'.$brand['template'])
            @endif
        </div>
    </div>
</body>
</html>
