<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card Preview — {{ $card['name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fafafa;
            padding: 24px;
            font-family: "IBM Plex Sans", ui-sans-serif, system-ui, sans-serif;
        }

        .preview-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            background: #fff;
            border-bottom: 1px solid #e4e4e7;
        }

        .preview-toolbar a {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            color: #3f3f46;
            background: #f4f4f5;
        }

        .preview-toolbar a.is-active {
            background: #18181b;
            color: #fff;
        }

        .preview-stage {
            margin-top: 48px;
        }
    </style>
</head>
<body>
    <div class="preview-toolbar">
        <a href="{{ request()->url() }}?side=front" @class(['is-active' => $side === 'front'])>Front</a>
        <a href="{{ request()->url() }}?side=back" @class(['is-active' => $side === 'back'])>Back</a>
    </div>

    <div class="preview-stage">
        <x-guard-id-card-preview
            :brand="$brand"
            :card="$card"
            :side="$side"
            :photo-url="$photoUrl"
            :logo-url="$logoUrl"
            :qr-svg="$qrSvg"
        />
    </div>
</body>
</html>
