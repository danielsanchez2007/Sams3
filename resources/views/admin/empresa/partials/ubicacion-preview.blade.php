@php
    $mapsUrl = $mapsUrl ?? null;
    $query = trim((string) ($query ?? ''));
    $lat = isset($lat) && $lat !== '' && $lat !== null ? (float) $lat : null;
    $lng = isset($lng) && $lng !== '' && $lng !== null ? (float) $lng : null;
    $hasCoords = $lat !== null && $lng !== null && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180 && !($lat === 0.0 && $lng === 0.0);
    if (! $hasCoords && is_string($mapsUrl) && $mapsUrl !== '') {
        if (preg_match('/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $mapsUrl, $m)
            || preg_match('/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/', $mapsUrl, $m)
            || preg_match('/[?&](?:q|query|ll)=(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/i', $mapsUrl, $m)
        ) {
            $lat = (float) $m[1];
            $lng = (float) $m[2];
            $hasCoords = $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180 && !($lat === 0.0 && $lng === 0.0);
        }
    }
    $openUrl = $mapsUrl ?: ($query !== '' ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query) : null);
    $heightClass = $heightClass ?? 'h-28';
@endphp

@if($hasCoords || $openUrl || $query !== '')
<div class="ubicacion-preview mt-2 rounded-lg border border-slate-200 overflow-hidden bg-slate-50 max-w-xl">
    @if($hasCoords)
        @php
            $pad = 0.012;
            $bbox = ($lng - $pad).','.($lat - $pad).','.($lng + $pad).','.($lat + $pad);
            $osmSrc = 'https://www.openstreetmap.org/export/embed.html?bbox='.rawurlencode($bbox).'&layer=mapnik&marker='.rawurlencode($lat.','.$lng);
        @endphp
        <iframe
            class="w-full {{ $heightClass }} border-0"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            src="{{ $osmSrc }}"
            title="Mapa de ubicación">
        </iframe>
    @endif
    <div class="flex items-start gap-2 px-3 py-2.5 {{ $hasCoords ? 'border-t border-slate-200 bg-white' : '' }}">
        <i data-lucide="map-pin" class="w-4 h-4 mt-0.5 shrink-0 text-blue-600"></i>
        <div class="min-w-0">
            @if($query !== '')
            <p class="text-xs text-slate-600 leading-snug">{{ $query }}</p>
            @endif
            @if($openUrl)
            <a href="{{ $openUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:underline mt-0.5">
                Abrir en Google Maps
            </a>
            @endif
        </div>
    </div>
</div>
@endif
