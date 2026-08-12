@props([
    'codigo' => null,
    'extra' => null, // ej: nombre del equipo (opcional, para tooltip)
])

@php
    $raw = trim((string) ($codigo ?? ''));
    $short = $raw;
    $fullCode = preg_replace('/-\([^)]+\)$/u', '', $raw);

    // Formato corto: IN-0001 (secuencia de inventario)
    if (preg_match('/\bIN-(\d{1,6})\b/i', $raw, $m)) {
        $n = (int) $m[1];
        $short = 'IN-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    } elseif (preg_match('/-IN-(\d{1,6})(?:-|$)/i', $raw, $m)) {
        $n = (int) $m[1];
        $short = 'IN-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    $showFull = $fullCode !== '' && strcasecmp($short, $fullCode) !== 0;

    $tooltip = $fullCode;
    if (is_string($extra) && trim($extra) !== '') {
        $tooltip = $fullCode . ' — ' . trim($extra);
    }
@endphp

<span
    class="inline-flex flex-wrap items-baseline gap-x-2 gap-y-0.5"
    @if($showFull && $tooltip !== '') title="{{ $tooltip }}" @endif
>
    <span class="font-semibold text-gray-900">{{ $short }}</span>
    @if($showFull)
        <span class="text-xs text-gray-500 font-mono tracking-tight">{{ $fullCode }}</span>
    @endif
</span>
