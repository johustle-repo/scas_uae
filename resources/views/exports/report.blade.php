<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        @page { margin: 28px 32px 40px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #262626; }
        h1 { font-size: 18px; color: #4c1d95; margin: 0 0 2px; }
        .muted { color: #737373; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 4px 5px; text-align: left; vertical-align: top; border-bottom: 1px solid #e5e5e5; }
        thead th { background: #f3eefb; color: #4c1d95; font-weight: bold; }
        .footer { position: fixed; bottom: -24px; left: 0; right: 0; font-size: 8px; color: #a3a3a3; }
    </style>
</head>
<body>
    <div class="footer">SCAS &middot; {{ $report['title'] }} &middot; generated {{ $generatedAt->format('j M Y, H:i') }}</div>

    <h1>{{ $report['title'] }}</h1>
    <p class="muted">{{ $report['description'] }} &middot; {{ count($report['rows']) }} {{ Str::plural('row', count($report['rows'])) }} &middot; generated {{ $generatedAt->format('j M Y, H:i') }}</p>

    <table>
        <thead>
            <tr>
                @foreach ($report['headings'] as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($report['headings']) }}" class="muted">No records.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
