<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>SCAS Dog Records</title>
    <style>
        @page { margin: 70px 32px 46px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #262626; }
        h1 { font-size: 20px; color: #4c1d95; margin: 0; }
        h2 { font-size: 17px; color: #4c1d95; margin: 0 0 3px; }
        h3 { font-size: 10px; color: #4c1d95; margin: 16px 0 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .muted { color: #737373; }
        .dog { page-break-before: always; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 5px; text-align: left; vertical-align: top; border-bottom: 1px solid #e5e5e5; }
        thead th { background: #f3eefb; color: #4c1d95; font-weight: bold; }
        .details td.label { width: 22%; color: #525252; font-weight: bold; background: #faf8fd; }
        .details td.value { width: 28%; }

        /* Repeated on every page. */
        .page-header { position: fixed; top: -52px; left: 0; right: 0; height: 36px; border-bottom: 2px solid #ede7f8; }
        .page-header td { border: 0; padding: 0; vertical-align: middle; }
        .page-header img { height: 30px; }
        .page-header .org { font-size: 10px; font-weight: bold; color: #4c1d95; padding-left: 8px; }
        .page-header .sub { font-size: 8px; color: #a3a3a3; text-align: right; }
        .footer { position: fixed; bottom: -30px; left: 0; right: 0; font-size: 8px; color: #a3a3a3; }
        .footer .page:after { content: counter(page); }

        /* Cover. */
        .cover { background: #4c1d95; color: #ffffff; border-radius: 10px; margin-bottom: 18px; }
        .cover td { border: 0; padding: 16px 18px; vertical-align: middle; }
        .cover .logo-cell { width: 86px; padding-right: 0; }
        .cover .logo { width: 78px; height: 78px; background: #ffffff; border-radius: 39px; padding: 8px; }
        .cover h1 { color: #ffffff; font-size: 22px; }
        .cover p { margin: 4px 0 0; color: #ddd6fe; font-size: 10px; }
        .cover .count { text-align: right; font-size: 26px; font-weight: bold; color: #ffffff; }
        .cover .count span { display: block; font-size: 9px; font-weight: normal; color: #ddd6fe; }

        /* Dog profile card. */
        .profile { border: 1px solid #ede7f8; border-radius: 10px; background: #faf8fd; }
        .profile td { border: 0; padding: 14px; vertical-align: top; }
        .profile .photo-cell { width: 150px; padding-right: 0; }
        .profile .photo { width: 140px; height: 140px; border-radius: 10px; border: 3px solid #ffffff; }
        .profile .placeholder { background: #ffffff; padding: 22px; width: 96px; height: 96px; }
        .profile .id { font-size: 10px; color: #6d28d9; font-weight: bold; letter-spacing: 0.5px; }
        .status { display: inline-block; margin-top: 6px; padding: 3px 9px; border-radius: 9px; background: #ede7f8; color: #4c1d95; font-size: 9px; font-weight: bold; }
        .facts { margin-top: 12px; }
        .facts td { padding: 3px 0; border: 0; width: 50%; }
        .facts .fact-label { color: #8b8b8b; font-size: 8px; text-transform: uppercase; letter-spacing: 0.4px; }
        .facts .fact-value { font-size: 10px; color: #262626; font-weight: bold; }
    </style>
</head>
<body>
    <div class="page-header">
        <table>
            <tr>
                <td style="width: 34px;">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="SCAS">
                    @endif
                </td>
                <td class="org">Second Chance Animal Sanctuary</td>
                <td class="sub">Dog records &middot; {{ $generatedAt->format('j M Y') }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        SCAS dog records &middot; generated {{ $generatedAt->format('j M Y, H:i') }} &middot; page <span class="page"></span>
    </div>

    <table class="cover">
        <tr>
            @if ($logo)
                <td class="logo-cell"><img class="logo" src="{{ $logo }}" alt="SCAS logo"></td>
            @endif
            <td>
                <h1>SCAS Dog Records</h1>
                <p>Second Chance Animal Sanctuary &middot; generated {{ $generatedAt->format('j M Y, H:i') }}</p>
            </td>
            <td class="count">
                {{ count($profiles) }}
                <span>{{ Str::plural('dog', count($profiles)) }}</span>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>SCAS ID</th>
                <th>Name</th>
                <th>Breed</th>
                <th>Gender</th>
                <th>Current Status</th>
                <th>Current Location</th>
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
                </tr>
            @endforeach
        </tbody>
    </table>

    @foreach ($profiles as $profile)
        @php
            $details = $profile['details'];
            $facts = array_filter([
                'Breed' => $details['Breed'] ?? null,
                'Gender' => $details['Gender'] ?? null,
                'Size' => $details['Size'] ?? null,
                'Date of birth' => $details['Date Of Birth'] ?? null,
                'Location' => $details['Current Location'] ?? null,
                'Energy' => $details['Energy Level'] ?? null,
            ]);
        @endphp

        <div class="dog">
            <table class="profile">
                <tr>
                    <td class="photo-cell">
                        @if ($profile['photo'])
                            <img class="photo" src="{{ $profile['photo'] }}" alt="{{ $details['Name'] ?? '' }}">
                        @elseif ($logo)
                            <img class="photo placeholder" src="{{ $logo }}" alt="No photo">
                        @endif
                    </td>
                    <td>
                        <div class="id">{{ $details['SCAS ID'] ?? '' }}</div>
                        <h2>{{ $details['Name'] ?? '' }}</h2>
                        @isset($details['Current Status'])
                            <span class="status">{{ $details['Current Status'] }}</span>
                        @endisset

                        @if ($facts !== [])
                            <table class="facts">
                                @foreach (array_chunk($facts, 2, true) as $pair)
                                    <tr>
                                        @foreach ($pair as $label => $value)
                                            <td>
                                                <div class="fact-label">{{ $label }}</div>
                                                <div class="fact-value">{{ $value }}</div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                    </td>
                </tr>
            </table>

            @isset($details['Personality Description'])
                <h3>About</h3>
                <p>{{ $details['Personality Description'] }}</p>
            @endisset

            <h3>Details</h3>
            <table class="details">
                @foreach (array_chunk($details, 2, true) as $pair)
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
