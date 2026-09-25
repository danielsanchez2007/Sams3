@props([
    'entries' => ['resources/css/app.css', 'resources/js/app.js'],
])
@php
    $entries = array_values(array_filter((array) $entries));
    $useVite = is_file(public_path('build/manifest.json'))
        || (is_file(public_path('hot')) && app()->environment('local'));
@endphp
@if($useVite)
    @vite($entries)
@else
    @foreach($entries as $entry)
        @if(str_contains($entry, '.css'))
            <link rel="stylesheet" href="{{ asset('css/sams.css') }}">
        @elseif(str_contains($entry, 'charts.js'))
            <script type="module" src="{{ asset('js/charts.js') }}"></script>
        @else
            <script type="module" src="{{ asset('js/sams.js') }}"></script>
        @endif
    @endforeach
@endif
<link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}?v=2">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=2">
