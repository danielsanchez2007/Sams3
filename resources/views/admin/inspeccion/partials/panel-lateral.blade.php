<div class="pw-insp-aside space-y-4">
    <div class="pw-card bg-white rounded-2xl shadow-lg p-4 border border-gray-100">
        <div class="text-sm font-semibold text-gray-900 mb-1">Usuario</div>
        <div class="text-xs text-gray-500 mb-3">Por defecto quien hace la inspección. El filtro no cambia el inspector del formulario.</div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Buscar usuario</label>
        <input type="search" id="panelUserFilter" autocomplete="off" placeholder="Nombre, correo o cargo..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <div id="panelUserResults" class="pw-insp-user-results hidden mt-2"></div>
        <div id="panelUserCard" class="pw-insp-user-card mt-3">
            <div class="flex items-start gap-3">
                <img id="panelUserPhoto" src="" alt="" class="w-16 h-16 object-cover rounded-lg border border-gray-200 hidden">
                <div id="panelUserPhotoPlaceholder" class="w-16 h-16 rounded-lg border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center text-[10px] text-gray-400">—</div>
                <div class="min-w-0 flex-1">
                    <div id="panelUserName" class="font-semibold text-gray-900 text-sm truncate">—</div>
                    <div id="panelUserCargo" class="text-xs text-gray-500 truncate"></div>
                    <div id="panelUserEmail" class="text-xs text-gray-600 truncate mt-1"></div>
                    <div id="panelUserPhone" class="text-xs text-gray-600 truncate"></div>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-[10px] text-gray-500 mb-1">Firma</div>
                <img id="panelUserSignature" src="" alt="" class="h-12 max-w-full object-contain border border-gray-200 rounded bg-white hidden">
                <div id="panelUserSignPlaceholder" class="h-12 rounded border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center text-[10px] text-gray-400">—</div>
            </div>
        </div>
    </div>

    <div class="pw-card bg-white rounded-2xl shadow-lg p-4 border border-gray-100">
        <div class="text-sm font-semibold text-gray-900 mb-1">Hoja de vida del equipo</div>
        <div class="text-xs text-gray-500 mb-3">Arrastra un dato al formato o haz clic para insertarlo.</div>
        <div class="pw-hv-chips">
            @foreach($hvChips ?? [] as $chip)
                <button type="button"
                    class="pw-hv-chip{{ ($chip['value'] ?? '') === '' ? ' pw-hv-chip--empty' : '' }}"
                    draggable="true"
                    data-hv-value="{{ $chip['value'] }}"
                    title="{{ $chip['label'] }}: {{ $chip['value'] !== '' ? $chip['value'] : 'Sin dato' }}">
                    <span class="pw-hv-chip-label">{{ $chip['label'] }}</span>
                    <span class="pw-hv-chip-value">{{ $chip['value'] !== '' ? $chip['value'] : '—' }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>
