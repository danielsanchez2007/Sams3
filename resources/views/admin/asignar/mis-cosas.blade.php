@extends('layouts.admin-layout')

@section('title', 'Mis cosas - SAMS')
@section('header-title', 'Mis cosas')
@section('header-subtitle', 'Mis equipos de oficina y mis actas')

@section('content')
<div class="max-w-7xl mx-auto grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="pw-card rounded-2xl p-6">
        <div class="flex items-center gap-2 mb-3">
            <i data-lucide="package-check" class="w-5 h-5 text-emerald-600"></i>
            <h3 class="text-lg font-semibold text-gray-900">Mis equipos</h3>
        </div>
        <div class="space-y-3 max-h-96 overflow-auto">
            @forelse(($misEquipos ?? collect()) as $row)
            @php($eq = $row->equipo)
            <div class="border border-gray-200 rounded-xl p-3 flex items-start gap-3">
                <div class="flex gap-1">
                    @forelse($eq->imagenes->take(1) as $img)
                    <img src="{{ asset('storage/' . $img->path) }}" class="w-12 h-12 rounded object-cover border border-gray-200" alt="">
                    @empty
                    <div class="w-12 h-12 rounded bg-gray-100"></div>
                    @endforelse
                </div>
                <div class="min-w-0">
                    <p class="font-medium text-sm text-gray-900 truncate">{{ $eq->nombre }}</p>
                    <p class="text-xs text-gray-500">
                        @if($eq?->codigo)
                            <x-codigo-short :codigo="$eq->codigo" :extra="$eq->nombre" />
                        @else
                            sin código
                        @endif
                    </p>
                    <p class="text-xs text-gray-600">En uso</p>
                    <p class="text-xs text-gray-600">{{ $eq->tipoEquipo?->nombre }} / {{ $eq->claseEquipo?->nombre }}</p>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-500">No tienes equipos asignados actualmente.</p>
            @endforelse
        </div>
    </div>

    <div class="pw-card rounded-2xl p-6">
        <div class="flex items-center gap-2 mb-3">
            <i data-lucide="file-signature" class="w-5 h-5 text-blue-600"></i>
            <h3 class="text-lg font-semibold text-gray-900">Mis actas</h3>
        </div>
        <div class="space-y-3 max-h-96 overflow-auto">
            @forelse(($misActasResumen ?? collect()) as $acta)
            <div class="border border-gray-200 rounded-xl p-3">
                <div class="flex items-center justify-between gap-2">
                    <p class="font-medium text-sm text-gray-900">Acta #{{ $acta['id'] }} · {{ $acta['tipo'] }}</p>
                    <span class="text-[10px] px-2 py-1 rounded {{ $acta['estado_color'] === 'green' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ $acta['estado_label'] }}
                    </span>
                </div>
                <p class="text-xs text-gray-500">Por: {{ $acta['creador'] }} · {{ optional($acta['fecha'])->format('Y-m-d H:i') }}</p>
                <p class="text-xs text-gray-600 mt-1">Equipos: {{ $acta['count'] }}</p>
                @if(!empty($acta['html']))
                <button type="button"
                        class="btn-mini-vista mt-2 text-xs text-blue-700 hover:underline"
                        data-template-id="acta-html-{{ $loop->index }}">
                    Ver mini vista
                </button>
                <template id="acta-html-{{ $loop->index }}">@safeHtml($acta['html'])</template>
                @endif
            </div>
            @empty
            <p class="text-sm text-gray-500">No tienes actas firmadas aún.</p>
            @endforelse
        </div>
    </div>
</div>

<div id="miniVistaModal" class="fixed inset-0 bg-black/50 hidden z-[9999]">
    <div class="min-h-full w-full flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[85vh] overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h4 class="font-semibold text-gray-900">Mini vista de acta</h4>
                <button type="button" id="cerrarMiniVista" class="px-2 py-1 text-sm rounded hover:bg-gray-100">Cerrar</button>
            </div>
            <div id="miniVistaBody" class="p-4 overflow-auto text-xs" style="max-height: calc(85vh - 56px);"></div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('miniVistaModal');
    const body = document.getElementById('miniVistaBody');
    const closeBtn = document.getElementById('cerrarMiniVista');

    function closeModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        if (body) body.innerHTML = '';
    }

    document.querySelectorAll('.btn-mini-vista').forEach((btn) => {
        btn.addEventListener('click', function () {
            const tplId = btn.getAttribute('data-template-id');
            const tpl = tplId ? document.getElementById(tplId) : null;
            if (!tpl || !body || !modal) return;
            body.innerHTML = tpl.innerHTML;
            modal.classList.remove('hidden');
        });
    });

    closeBtn?.addEventListener('click', closeModal);
    modal?.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
});
</script>
@endsection

