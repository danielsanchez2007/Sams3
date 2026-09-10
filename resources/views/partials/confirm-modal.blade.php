{{-- Modal de confirmación global: ¿Seguro que deseas...? Aceptar / Cancelar --}}
<div id="pwConfirmModal" class="fixed inset-0 z-[9998] hidden items-center justify-center p-4" aria-modal="true" role="dialog" aria-labelledby="pwConfirmTitle" aria-describedby="pwConfirmMessage">
    <div id="pwConfirmBackdrop" class="absolute inset-0" aria-hidden="true"></div>
    <div class="pw-modal-content pw-modal-sm relative overflow-hidden">
        <div class="pw-modal-header">
            <div class="pw-modal-header-main">
                <div id="pwConfirmIconWrap" class="pw-modal-header-icon is-warning">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600" id="pwConfirmIcon"></i>
                </div>
                <div>
                    <h3 id="pwConfirmTitle" class="pw-modal-title">Confirmar acción</h3>
                    <p class="pw-modal-subtitle">Revisa el detalle antes de continuar.</p>
                </div>
            </div>
            <button type="button" id="pwConfirmClose" class="pw-modal-close" aria-label="Cerrar">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <div class="pw-modal-body">
            <p id="pwConfirmMessage" class="text-sm text-gray-600 m-0">¿Estás seguro de que deseas continuar?</p>
        </div>
        <div class="pw-modal-footer">
            <button type="button" id="pwConfirmCancel" class="pw-btn-secondary px-4 py-2.5 rounded-lg font-medium text-sm">
                Cancelar
            </button>
            <button type="button" id="pwConfirmOk" class="pw-btn-primary px-4 py-2.5 rounded-lg font-medium text-sm">
                Aceptar
            </button>
        </div>
    </div>
</div>
