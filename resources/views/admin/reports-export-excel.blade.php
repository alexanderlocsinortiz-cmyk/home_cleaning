<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
</head>
<body>
    <table border="1">
        <thead>
            <tr>
                <th colspan="{{ count($headers) }}">{{ $title }}</th>
            </tr>
            <tr>
                <th colspan="{{ count($headers) }}">Generated: {{ now()->format('Y-m-d H:i:s') }}</th>
            </tr>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($headers as $header)
                        <td>{{ $row[$header] ?? '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
