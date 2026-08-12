{{-- Modal de confirmación global: ¿Seguro que deseas...? Aceptar / Cancelar --}}
<div id="pwConfirmModal" class="fixed inset-0 z-[9998] hidden items-center justify-center p-4" aria-modal="true" role="dialog" aria-labelledby="pwConfirmTitle" aria-describedby="pwConfirmMessage">
    <div id="pwConfirmBackdrop" class="absolute inset-0 bg-black/50 backdrop-blur-sm" aria-hidden="true"></div>
    <div class="pw-modal-content relative w-full bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
        <div class="p-6">
            <div id="pwConfirmIconWrap" class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 bg-amber-100">
                <i data-lucide="alert-triangle" class="w-6 h-6 text-amber-600" id="pwConfirmIcon"></i>
            </div>
            <h3 id="pwConfirmTitle" class="text-lg font-semibold text-gray-900 mb-2">Confirmar acción</h3>
            <p id="pwConfirmMessage" class="text-sm text-gray-600 mb-6">¿Estás seguro de que deseas continuar?</p>
            <div class="flex flex-wrap gap-3 justify-end">
                <button type="button" id="pwConfirmCancel" class="px-4 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium text-sm transition">
                    Cancelar
                </button>
                <button type="button" id="pwConfirmOk" class="pw-btn-primary px-4 py-2.5 rounded-xl font-medium text-sm transition">
                    Aceptar
                </button>
            </div>
        </div>
    </div>
</div>
