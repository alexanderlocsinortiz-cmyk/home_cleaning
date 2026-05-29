@php
    $buttonPalette = [
        'emerald' => ['background' => '#2563EB', 'text' => '#ffffff'],
        'cyan' => ['background' => '#3B82F6', 'text' => '#ffffff'],
        'purple' => ['background' => '#1D4ED8', 'text' => '#ffffff'],
        'amber' => ['background' => '#1E40AF', 'text' => '#ffffff'],
        'slate' => ['background' => '#1E3A8A', 'text' => '#ffffff'],
    ][$tone ?? 'emerald'] ?? ['background' => '#2563EB', 'text' => '#ffffff'];
@endphp

<div class="cta-wrap">
    <a href="{{ $url }}" class="cta-button" style="background: {{ $buttonPalette['background'] }}; color: {{ $buttonPalette['text'] }};">{{ $label }}</a>
</div>
