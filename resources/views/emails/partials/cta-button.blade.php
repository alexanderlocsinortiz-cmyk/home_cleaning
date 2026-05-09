@php
    $buttonPalette = [
        'emerald' => ['background' => '#09637e', 'text' => '#ffffff'],
        'cyan' => ['background' => '#088395', 'text' => '#ffffff'],
        'purple' => ['background' => '#7ab2b2', 'text' => '#143241'],
        'amber' => ['background' => '#294351', 'text' => '#ffffff'],
        'slate' => ['background' => '#143241', 'text' => '#ffffff'],
    ][$tone ?? 'emerald'] ?? ['background' => '#09637e', 'text' => '#ffffff'];
@endphp

<div class="cta-wrap">
    <a href="{{ $url }}" class="cta-button" style="background: {{ $buttonPalette['background'] }}; color: {{ $buttonPalette['text'] }};">{{ $label }}</a>
</div>
