<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checklist - {{ $entry['name'] ?? '' }}</title>
    @include('pdf.checklist-styles')
</head>
<body>
    @include('pdf.checklist-entry', ['entry' => $entry])
</body>
</html>
