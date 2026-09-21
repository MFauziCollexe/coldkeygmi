<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checklist {{ $start }} s/d {{ $end }}</title>
    @include('pdf.checklist-styles')
</head>
<body>
    @foreach($entries as $index => $entry)
        <div class="entry-block">
            @include('pdf.checklist-entry', ['entry' => $entry])
        </div>
    @endforeach
</body>
</html>