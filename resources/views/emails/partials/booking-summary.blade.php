<div class="summary-card">
    <table role="presentation" class="summary-table">
        @foreach($rows as $row)
            <tr>
                <td class="summary-label">{{ $row['label'] }}</td>
                <td class="summary-value">{!! $row['value'] !!}</td>
            </tr>
        @endforeach

        @isset($statusLabel)
            <tr>
                <td class="summary-label">Status</td>
                <td class="summary-value">
                    <span class="status-pill status-pill--{{ $statusTone ?? 'neutral' }}">{{ $statusLabel }}</span>
                </td>
            </tr>
        @endisset
    </table>
</div>
