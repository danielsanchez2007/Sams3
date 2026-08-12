@extends('layouts.admin-layout')

@section('title', 'Seguimiento de formatos - SAMS')
@section('header-title', 'Seguimiento de formatos de asignación')
@section('header-subtitle', 'Control de solicitudes enviadas por administración de oficina')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">
    @php
        // Equipos que ya entraron en una devolución en curso (pendiente/corrección/revisión).
        $equiposEnDevolucion = collect($solicitudes ?? [])
            ->filter(function ($s) {
                return (($s->tipo ?? '') === 'devolucion')
                    && (($s->estado ?? '') === 'pendiente');
            })
            ->flatMap(function ($s) {
                return $s->items->pluck('equipo_id');
            })
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    @endphp

    <div class="pw-card rounded-xl p-3 border border-gray-100">
        <label class="block text-xs font-medium text-gray-600 mb-1">Filtrar por nombre o correo</label>
        <input type="text" id="filtroSeguimientoUsuario" class="pw-input w-full md:w-96" placeholder="Ej: laura o laura@email.com">
    </div>

    <div id="seguimientoList" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-3">
    @forelse($solicitudes as $sol)
    @php
        $isPendienteRevision = (($sol->tipo ?? 'entrega') === 'devolucion' && ($sol->estado ?? '') === 'pendiente' && ($sol->workflow_step ?? '') === 'pendiente_revision_admin');
        $isEnEspera = ($sol->estado ?? '') === 'pendiente' || ($sol->workflow_step ?? '') === 'correccion_usuario';
        $equiposCount = $sol->items->count();
        if (($sol->tipo ?? 'entrega') === 'entrega') {
            $equiposCount = $sol->items
                ->filter(fn ($it) => (int) ($it->equipo?->asignacion?->user_id ?? 0) === (int) $sol->to_user_id)
                ->reject(fn ($it) => $equiposEnDevolucion->contains((int) $it->equipo_id))
                ->count();
        }
    @endphp
    <div class="pw-card rounded-lg p-3 border border-gray-100 seguimiento-card"
         data-usuario="{{ strtolower(trim(($sol->destinatario?->name ?? '') . ' ' . ($sol->destinatario?->last_name ?? ''))) }}"
         data-email="{{ strtolower($sol->destinatario?->email ?? '') }}">
        <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2 min-w-0">
                <img src="{{ $sol->destinatario?->photo ? asset('storage/' . $sol->destinatario->photo) : 'https://ui-avatars.com/api/?name=' . urlencode(trim(($sol->destinatario?->name ?? '') . ' ' . ($sol->destinatario?->last_name ?? ''))) }}"
                     alt=""
                     class="w-9 h-9 rounded-full object-cover border border-gray-200">
                <div class="min-w-0">
                    <p class="font-semibold text-sm text-gray-900 truncate">{{ $sol->destinatario?->name }} {{ $sol->destinatario?->last_name }}</p>
                    <p class="text-xs text-gray-600">Equipos: {{ $equiposCount }}</p>
                </div>
            </div>
            <div class="flex flex-col gap-1">
            <a href="{{ route('asignar.seguimiento.formato', $sol->id) }}" class="pw-btn-secondary px-3 py-2 rounded-lg text-xs">{{ $isPendienteRevision ? 'Ver formato' : 'Editar / ver formato' }}</a>
            @if(($sol->tipo ?? 'entrega') === 'entrega' && $sol->estado === 'aceptada')
            <a href="{{ route('asignar.devolucion.form', $sol->id) }}" class="pw-btn-danger px-3 py-2 rounded-lg text-xs">Devolución</a>
            @endif
            </div>
        </div>
        @if($isEnEspera)
        <div class="mt-2 inline-flex items-center px-2 py-1 rounded-md text-xs bg-yellow-100 text-yellow-800 border border-yellow-200">
            En espera
        </div>
        @endif
        @if(($sol->tipo ?? 'entrega') === 'devolucion' && $sol->estado === 'pendiente' && ($sol->workflow_step ?? null) === 'pendiente_revision_admin')
        <form method="POST" action="{{ route('asignar.seguimiento.revision', $sol->id) }}" class="mt-3 flex flex-wrap items-center gap-2">
            @csrf
            <input type="text" name="comentario_revision" class="pw-input text-xs px-2 py-1 min-w-[280px]" placeholder="Observación si requiere corrección (opcional)">
            <button type="submit" name="accion" value="aceptar" class="pw-btn-success px-3 py-2 rounded-lg text-xs">Aceptar</button>
            <button type="submit" name="accion" value="corregir" class="pw-btn-danger px-3 py-2 rounded-lg text-xs">Que lo rellene bien</button>
        </form>
        @elseif(($sol->tipo ?? 'entrega') === 'devolucion' && ($sol->workflow_step ?? null) === 'correccion_usuario')
        <div class="mt-3 p-2 rounded-lg border border-amber-200 bg-amber-50 text-xs text-amber-800">
            En corrección por el usuario. {{ $sol->comentario_revision ? 'Observación enviada: '.$sol->comentario_revision : '' }}
        </div>
        @endif
    </div>
    @empty
    <div class="pw-card rounded-2xl p-5 border border-gray-100 text-gray-600 col-span-full">
        No hay solicitudes registradas aún.
    </div>
    @endforelse
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('filtroSeguimientoUsuario');
    const cards = Array.from(document.querySelectorAll('.seguimiento-card'));
    if (!input) return;

    input.addEventListener('input', function () {
        const term = (input.value || '').trim().toLowerCase();
        cards.forEach((card) => {
            if (!term) {
                card.classList.remove('hidden');
                return;
            }
            const user = card.getAttribute('data-usuario') || '';
            const mail = card.getAttribute('data-email') || '';
            card.classList.toggle('hidden', !(user.includes(term) || mail.includes(term)));
        });
    });
});
</script>
@endsection

