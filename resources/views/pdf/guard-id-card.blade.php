<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $card['name'] }} — ID Card</title>
    @include('pdf.id-cards._styles')
</head>
<body>
    @include('pdf.id-cards.'.$brand['template'])

    <div class="page-break"></div>

    @include('pdf.id-cards.back')
</body>
</html>
