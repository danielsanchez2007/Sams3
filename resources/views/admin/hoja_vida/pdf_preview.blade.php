@extends('layouts.admin-layout')

@section('title', 'Vista previa PDF - Hoja de Vida')
@section('header-title', 'Vista previa PDF')
@section('header-subtitle', ($equipo->codigo ?: $equipo->id) . ' - ' . $equipo->nombre)

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('hoja-vida.form', [$clase, $equipo]) }}" class="pw-btn-secondary px-3 py-2 text-sm rounded-lg">
        <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
        Volver
    </a>
    <a id="downloadPdfBtn" href="{{ route('hoja-vida.pdf', [$clase, $equipo]) }}?async=0&amp;download=1" class="pw-btn-primary px-3 py-2 text-sm rounded-lg">Descargar PDF</a>
</div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-4">
    <div class="p-4 rounded-2xl border border-green-200 bg-green-50 text-green-800 text-sm">
        Vista previa de cómo quedará el PDF final generado desde el Excel (tabla recortada, con colores y datos). Desde aquí puedes revisar y descargar el PDF.
    </div>

    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden" style="height: 75vh;">
        <iframe id="pdfFrame" src="{{ route('hoja-vida.pdf', [$clase, $equipo]) }}?async=0&amp;t={{ time() }}" class="w-full h-full" style="border:0;" title="Vista previa PDF"></iframe>
    </div>
</div>
@endsection
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
@endsection

