<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>SCAS Dog Records</title>
    <style>
        @page { margin: 28px 32px 40px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #262626; }
        h1 { font-size: 18px; color: #4c1d95; margin: 0 0 2px; }
        h2 { font-size: 14px; color: #4c1d95; margin: 0 0 2px; }
        h3 { font-size: 10px; color: #4c1d95; margin: 14px 0 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        .muted { color: #737373; }
        .dog { page-break-before: always; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 5px; text-align: left; vertical-align: top; border-bottom: 1px solid #e5e5e5; }
        thead th { background: #f3eefb; color: #4c1d95; font-weight: bold; }
        .details td.label { width: 22%; color: #525252; font-weight: bold; background: #faf8fd; }
        .details td.value { width: 28%; }
        .footer { position: fixed; bottom: -24px; left: 0; right: 0; font-size: 8px; color: #a3a3a3; }
    </style>
</head>
<body>
    <div class="footer">SCAS dog records &middot; generated {{ $generatedAt->format('j M Y, H:i') }}</div>

    <h1>SCAS Dog Records</h1>
    <p class="muted">{{ count($profiles) }} {{ Str::plural('dog', count($profiles)) }} &middot; generated {{ $generatedAt->format('j M Y, H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>SCAS ID</th>
                <th>Name</th>
                <th>Breed</th>
                <th>Gender</th>
                <th>Current Status</th>
                <th>Current Location</th>
                <th>Archived</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($profiles as $profile)
                <tr>
                    <td>{{ $profile['details']['SCAS ID'] ?? '' }}</td>
                    <td>{{ $profile['details']['Name'] ?? '' }}</td>
                    <td>{{ $profile['details']['Breed'] ?? '' }}</td>
                    <td>{{ $profile['details']['Gender'] ?? '' }}</td>
                    <td>{{ $profile['details']['Current Status'] ?? '' }}</td>
                    <td>{{ $profile['details']['Current Location'] ?? '' }}</td>
                    <td>{{ $profile['details']['Archived'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @foreach ($profiles as $profile)
        <div class="dog">
            <h2>{{ $profile['details']['Name'] ?? '' }}</h2>
            <p class="muted">{{ $profile['details']['SCAS ID'] ?? '' }}</p>

            <h3>Details</h3>
            <table class="details">
                @foreach (array_chunk($profile['details'], 2, true) as $pair)
                    <tr>
                        @foreach ($pair as $label => $value)
                            <td class="label">{{ $label }}</td>
                            <td class="value">{{ $value }}</td>
                        @endforeach
                        @if (count($pair) === 1)
                            <td class="label"></td>
                            <td class="value"></td>
                        @endif
                    </tr>
                @endforeach
            </table>

            @foreach ($profile['related'] as $title => $table)
                <h3>{{ $title }}</h3>
                <table>
                    <thead>
                        <tr>
                            @foreach ($table['headings'] as $heading)
                                <th>{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($table['rows'] as $row)
                            <tr>
                                @foreach ($row as $value)
                                    <td>{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        </div>
    @endforeach
</body>
</html>
