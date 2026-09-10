@extends('layouts.admin-layout')

@section('title', 'Mi Perfil - SAMS')
@section('header-title', 'Mi Perfil')
@section('header-subtitle', 'Información personal y datos requeridos para usar el sistema')

@section('header-actions')
<button type="submit" form="form-profile" class="pw-btn-primary px-4 py-2 rounded-lg inline-flex items-center gap-2">
    <i data-lucide="save" class="w-4 h-4"></i>
    Guardar cambios
</button>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    @if(session('success'))
    <div class="mb-6 p-4 rounded-xl flex items-center gap-2" style="background: rgba(22,163,74,.20); border:1px solid rgba(74,222,128,.45); color:#dcfce7;">
        <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('warning'))
    <div class="mb-6 p-4 rounded-xl flex items-center gap-2" style="background: rgba(217,119,6,.20); border:1px solid rgba(251,191,36,.45); color:#fef3c7;">
        <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0"></i>
        <span>{{ session('warning') }}</span>
    </div>
    @endif

    @if(!$user->isProfileComplete())
    <div class="mb-6 p-4 rounded-xl" style="background: rgba(37,99,235,.18); border:1px solid rgba(96,165,250,.45); color:#dbeafe;">
        <p class="font-medium flex items-center gap-2">
            <i data-lucide="info" class="w-5 h-5 flex-shrink-0"></i>
            Para poder usar el sistema debes completar: <strong>foto</strong>, <strong>firma</strong>, <strong>tipo de documento</strong> y <strong>número de documento</strong>. Los campos marcados con * son obligatorios.
        </p>
    </div>
    @endif

    <form id="form-profile" action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')

        <div class="pw-card rounded-2xl p-6 md:p-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="user" class="w-5 h-5"></i>
                Datos personales
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="pw-input w-full">
                    @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Apellidos</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="pw-input w-full">
                    @error('last_name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Género</label>
                    <select name="gender" class="pw-select w-full">
                        <option value="">Selecciona...</option>
                        <option value="hombre" {{ old('gender', $user->gender) == 'hombre' ? 'selected' : '' }}>Hombre</option>
                        <option value="mujer" {{ old('gender', $user->gender) == 'mujer' ? 'selected' : '' }}>Mujer</option>
                        <option value="otro" {{ old('gender', $user->gender) == 'otro' ? 'selected' : '' }}>Otro</option>
                    </select>
                    @error('gender')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div id="gender_other_wrap" class="{{ old('gender', $user->gender) == 'otro' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Especificar género</label>
                    <input type="text" name="gender_other" value="{{ old('gender_other', $user->gender_other) }}" class="pw-input w-full" placeholder="Especificar">
                    @error('gender_other')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de nacimiento</label>
                    <input type="date" name="birth_date" value="{{ old('birth_date', $user->birth_date ? $user->birth_date->format('Y-m-d') : '') }}" class="pw-input w-full">
                    @error('birth_date')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="pw-card rounded-2xl p-6 md:p-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="id-card" class="w-5 h-5"></i>
                Documento de identidad *
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de documento *</label>
                    <select name="document_type" required class="pw-select w-full">
                        <option value="">Selecciona...</option>
                        <option value="CC" {{ old('document_type', $user->document_type) == 'CC' ? 'selected' : '' }}>Cédula de Ciudadanía</option>
                        <option value="CE" {{ old('document_type', $user->document_type) == 'CE' ? 'selected' : '' }}>Cédula de Extranjería</option>
                        <option value="TI" {{ old('document_type', $user->document_type) == 'TI' ? 'selected' : '' }}>Tarjeta de Identidad</option>
                        <option value="PP" {{ old('document_type', $user->document_type) == 'PP' ? 'selected' : '' }}>Pasaporte</option>
                    </select>
                    @error('document_type')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de documento *</label>
                    <input type="text" name="document_number" value="{{ old('document_number', $user->document_number) }}" required class="pw-input w-full" placeholder="Ej: 123456789">
                    @error('document_number')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="pw-card rounded-2xl p-6 md:p-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="mail" class="w-5 h-5"></i>
                Contacto
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo personal *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="pw-input w-full">
                    @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono personal</label>
                    <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" class="pw-input w-full">
                    @error('phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <input type="text" name="address" value="{{ old('address', $user->address) }}" class="pw-input w-full">
                    @error('address')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="has_corporate_email" value="0">
                    <input type="checkbox" id="has_corporate_email" name="has_corporate_email" value="1" {{ old('has_corporate_email', $user->has_corporate_email) ? 'checked' : '' }} class="rounded border-gray-300">
                    <label for="has_corporate_email" class="text-sm font-medium text-gray-700">¿Tiene correo corporativo?</label>
                </div>
                <div id="corporate_email_wrap" class="{{ old('has_corporate_email', $user->has_corporate_email) ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo corporativo</label>
                    <input type="email" name="corporate_email" value="{{ old('corporate_email', $user->corporate_email) }}" class="pw-input w-full">
                    @error('corporate_email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="has_corporate_phone" value="0">
                    <input type="checkbox" id="has_corporate_phone" name="has_corporate_phone" value="1" {{ old('has_corporate_phone', $user->has_corporate_phone) ? 'checked' : '' }} class="rounded border-gray-300">
                    <label for="has_corporate_phone" class="text-sm font-medium text-gray-700">¿Tiene teléfono corporativo?</label>
                </div>
                <div id="corporate_phone_wrap" class="{{ old('has_corporate_phone', $user->has_corporate_phone) ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono corporativo</label>
                    <input type="tel" name="corporate_phone" value="{{ old('corporate_phone', $user->corporate_phone) }}" class="pw-input w-full">
                    @error('corporate_phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="pw-card rounded-2xl p-6 md:p-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="briefcase" class="w-5 h-5"></i>
                Grupo y cargo (solo lectura)
            </h3>
            <p class="text-sm text-gray-600 mb-4">Estos datos los asigna la administración y no se pueden editar desde el perfil.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Grupo</label>
                    <input type="text" value="{{ $user->grupo ? $user->grupo->name : '—' }}" readonly class="pw-input w-full bg-gray-100 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Cargo</label>
                    <input type="text" value="{{ $user->cargo ? $user->cargo->name : '—' }}" readonly class="pw-input w-full bg-gray-100 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Rol</label>
                    <input type="text" value="{{ $user->role ? $user->role->name : '—' }}" readonly class="pw-input w-full bg-gray-100 cursor-not-allowed">
                </div>
            </div>
        </div>

        <div class="pw-card rounded-2xl p-6 md:p-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="image" class="w-5 h-5"></i>
                Foto y firma (obligatorios para usar el sistema)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto *</label>
                    <div class="flex flex-col sm:flex-row items-start gap-4">
                        <div class="flex-shrink-0 w-24 h-24 rounded-xl border-2 border-gray-200 overflow-hidden bg-gray-100">
                            @if($user->photo)
                                <img id="photo-preview" src="{{ $user->photo_url }}" alt="Foto" class="w-full h-full object-cover">
                            @else
                                <div id="photo-preview" class="w-full h-full flex items-center justify-center text-gray-400">
                                    <i data-lucide="user" class="w-10 h-10"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <input type="file" name="photo" accept="image/jpeg,image/png,image/jpg,image/gif" class="hidden" id="photo-input" data-camera-ui="off">
                            <div class="flex items-center gap-2">
                                <button type="button" id="photoTakeBtn" class="px-2 py-1 rounded border border-gray-400 text-xs">Tomar foto</button>
                                <button type="button" id="photoPickBtn" class="px-2 py-1 rounded border border-gray-400 text-xs">Elegir archivo</button>
                                <span id="photoFileName" class="text-xs text-gray-500 truncate">Sin archivo...</span>
                            </div>
                            @error('photo')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Firma *</label>
                    <div class="flex flex-col sm:flex-row items-start gap-4">
                        <div class="flex-shrink-0 w-32 h-16 rounded-xl border-2 border-gray-200 overflow-hidden bg-white flex items-center justify-center">
                            @if($user->signature)
                                <img id="signature-preview" src="{{ $user->signature_url }}" alt="Firma" class="max-w-full max-h-full object-contain">
                            @else
                                <span id="signature-preview" class="text-gray-400 text-sm">Sin firma</span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2">
                                <button type="button" id="sigTakeBtn" class="px-2 py-1 rounded border border-gray-400 text-xs">Tomar foto</button>
                                <button type="button" id="sigPickBtn" class="px-2 py-1 rounded border border-gray-400 text-xs">Elegir archivo</button>
                                <button type="button" id="sigModeDrawBtn" class="px-2 py-1 rounded border border-gray-400 text-xs">Dibujar firma</button>
                            </div>
                            <div id="sigFileWrap">
                                <input type="file" name="signature" accept="image/jpeg,image/png,image/jpg,image/gif" class="hidden" id="signature-input" data-camera-ui="off">
                                <span id="sigFileName" class="text-xs text-gray-500">Sin archivo...</span>
                            </div>
                            <input type="hidden" name="signature_drawn" id="signature-drawn">
                            <span id="sigDrawInfo" class="hidden text-xs text-emerald-300">Firma dibujada lista para guardar.</span>
                            @error('signature')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pw-card rounded-2xl p-6 md:p-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="lock" class="w-5 h-5"></i>
                Cambiar contraseña
            </h3>
            <p class="text-sm text-gray-600 mb-4">Para cambiarla, confirma tu contraseña actual. Deja en blanco la nueva si no quieres modificarla.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña actual</label>
                    <input type="password" name="current_password" class="pw-input w-full" autocomplete="current-password">
                    @error('current_password')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nueva contraseña</label>
                    <input type="password" name="password" class="pw-input w-full" autocomplete="new-password" minlength="10" maxlength="72" placeholder="Mínimo 10 caracteres" data-pw-meter="optional">
                    @error('password')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" class="pw-input w-full" autocomplete="new-password">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" form="form-profile" class="pw-btn-primary px-6 py-3 rounded-xl inline-flex items-center gap-2">
                <i data-lucide="save" class="w-5 h-5"></i>
                Guardar cambios
            </button>
        </div>
    </form>
</div>

<div id="signatureDrawModal" class="fixed inset-0 bg-black/70 hidden z-[70] items-center justify-center p-4">
    <div class="pw-modal-content w-full max-w-4xl rounded-xl p-4">
        <div class="flex items-center justify-between mb-3">
            <h4 class="text-base font-semibold">Dibujar firma</h4>
            <button type="button" id="sigCloseModalBtn" class="px-3 py-1 rounded border border-gray-400 text-xs">Cerrar</button>
        </div>
        <canvas id="signature-canvas-modal" width="1200" height="420" class="w-full bg-white rounded-lg border border-gray-300"></canvas>
        <div class="flex items-center justify-between mt-3">
            <span class="text-xs text-gray-300">Panel blanco grande, firma en negro.</span>
            <div class="flex items-center gap-2">
                <button type="button" id="sigClearBtn" class="px-3 py-1.5 rounded border border-gray-400 text-xs">Limpiar</button>
                <button type="button" id="sigUseBtn" class="pw-btn-primary px-3 py-1.5 rounded text-xs">Usar firma</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // Género "otro" mostrar campo
    var genderSelect = document.querySelector('select[name="gender"]');
    var genderOtherWrap = document.getElementById('gender_other_wrap');
    if (genderSelect && genderOtherWrap) {
        genderSelect.addEventListener('change', function() {
            genderOtherWrap.classList.toggle('hidden', this.value !== 'otro');
        });
    }

    // Checkboxes correo/teléfono corporativo
    var hasCorpEmail = document.getElementById('has_corporate_email');
    var corpEmailWrap = document.getElementById('corporate_email_wrap');
    if (hasCorpEmail && corpEmailWrap) {
        hasCorpEmail.addEventListener('change', function() {
            corpEmailWrap.classList.toggle('hidden', !this.checked);
        });
    }
    var hasCorpPhone = document.getElementById('has_corporate_phone');
    var corpPhoneWrap = document.getElementById('corporate_phone_wrap');
    if (hasCorpPhone && corpPhoneWrap) {
        hasCorpPhone.addEventListener('change', function() {
            corpPhoneWrap.classList.toggle('hidden', !this.checked);
        });
    }

    // Previews foto y firma
    var photoInput = document.getElementById('photo-input');
    var photoPreview = document.getElementById('photo-preview');
    var photoTakeBtn = document.getElementById('photoTakeBtn');
    var photoPickBtn = document.getElementById('photoPickBtn');
    var photoFileName = document.getElementById('photoFileName');
    if (photoInput && photoPreview) {
        function openPicker(input, capture) {
            if (capture) {
                input.setAttribute('capture', 'environment');
            } else {
                input.removeAttribute('capture');
            }
            input.click();
        }
        if (photoTakeBtn) photoTakeBtn.addEventListener('click', function() { openPicker(photoInput, true); });
        if (photoPickBtn) photoPickBtn.addEventListener('click', function() { openPicker(photoInput, false); });
        photoInput.addEventListener('change', function(e) {
            var f = e.target.files[0];
            if (!f) return;
            if (photoFileName) photoFileName.textContent = f.name;
            var reader = new FileReader();
            reader.onload = function() {
                if (photoPreview.tagName === 'IMG') {
                    photoPreview.src = reader.result;
                } else {
                    var img = document.createElement('img');
                    img.id = 'photo-preview';
                    img.alt = 'Foto';
                    img.className = 'w-full h-full object-cover';
                    img.src = reader.result;
                    photoPreview.parentNode.replaceChild(img, photoPreview);
                }
            };
            reader.readAsDataURL(f);
        });
    }
    var signatureInput = document.getElementById('signature-input');
    var signaturePreview = document.getElementById('signature-preview');
    var sigTakeBtn = document.getElementById('sigTakeBtn');
    var sigPickBtn = document.getElementById('sigPickBtn');
    var sigFileName = document.getElementById('sigFileName');
    if (signatureInput && signaturePreview) {
        function openSigPicker(capture) {
            if (capture) {
                signatureInput.setAttribute('capture', 'environment');
            } else {
                signatureInput.removeAttribute('capture');
            }
            signatureInput.click();
        }
        if (sigTakeBtn) sigTakeBtn.addEventListener('click', function() { openSigPicker(true); });
        if (sigPickBtn) sigPickBtn.addEventListener('click', function() { openSigPicker(false); });
        signatureInput.addEventListener('change', function(e) {
            var f = e.target.files[0];
            if (!f) return;
            if (sigFileName) sigFileName.textContent = f.name;
            var reader = new FileReader();
            reader.onload = function() {
                if (signaturePreview.tagName === 'IMG') {
                    signaturePreview.src = reader.result;
                } else {
                    var img = document.createElement('img');
                    img.id = 'signature-preview';
                    img.alt = 'Firma';
                    img.className = 'max-w-full max-h-full object-contain';
                    img.src = reader.result;
                    signaturePreview.parentNode.replaceChild(img, signaturePreview);
                }
            };
            reader.readAsDataURL(f);
        });
    }

    // Firma dibujada
    var sigModeDrawBtn = document.getElementById('sigModeDrawBtn');
    var sigModal = document.getElementById('signatureDrawModal');
    var sigCloseModalBtn = document.getElementById('sigCloseModalBtn');
    var sigUseBtn = document.getElementById('sigUseBtn');
    var sigDrawInfo = document.getElementById('sigDrawInfo');
    var sigCanvas = document.getElementById('signature-canvas-modal');
    var sigDrawn = document.getElementById('signature-drawn');
    var sigClearBtn = document.getElementById('sigClearBtn');
    var form = document.getElementById('form-profile');
    var isDrawing = false;
    var hasStroke = false;

    function openDrawModal() {
        if (!sigModal) return;
        sigModal.classList.remove('hidden');
        sigModal.classList.add('flex');
    }
    function closeDrawModal() {
        if (!sigModal) return;
        sigModal.classList.add('hidden');
        sigModal.classList.remove('flex');
    }
    if (sigModeDrawBtn) sigModeDrawBtn.addEventListener('click', openDrawModal);
    if (sigCloseModalBtn) sigCloseModalBtn.addEventListener('click', closeDrawModal);

    if (sigCanvas) {
        var ctx = sigCanvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, sigCanvas.width, sigCanvas.height);
        ctx.strokeStyle = '#000000';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        function pos(e) {
            var r = sigCanvas.getBoundingClientRect();
            var p = e.touches ? e.touches[0] : e;
            return {
                x: (p.clientX - r.left) * (sigCanvas.width / r.width),
                y: (p.clientY - r.top) * (sigCanvas.height / r.height)
            };
        }
        function start(e) {
            isDrawing = true;
            hasStroke = true;
            var p = pos(e);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
            e.preventDefault();
        }
        function draw(e) {
            if (!isDrawing) return;
            var p = pos(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            e.preventDefault();
        }
        function end(e) {
            isDrawing = false;
            e.preventDefault();
        }
        sigCanvas.addEventListener('mousedown', start);
        sigCanvas.addEventListener('mousemove', draw);
        window.addEventListener('mouseup', end);
        sigCanvas.addEventListener('touchstart', start, { passive: false });
        sigCanvas.addEventListener('touchmove', draw, { passive: false });
        sigCanvas.addEventListener('touchend', end, { passive: false });
    }

    if (sigClearBtn && sigCanvas) {
        sigClearBtn.addEventListener('click', function() {
            var ctx = sigCanvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, sigCanvas.width, sigCanvas.height);
            hasStroke = false;
            if (sigDrawn) sigDrawn.value = '';
        });
    }

    if (sigUseBtn && sigCanvas && sigDrawn) {
        sigUseBtn.addEventListener('click', function() {
            if (!hasStroke) return;
            var dataUrl = sigCanvas.toDataURL('image/png');
            sigDrawn.value = dataUrl;
            if (signaturePreview) {
                if (signaturePreview.tagName === 'IMG') {
                    signaturePreview.src = dataUrl;
                } else {
                    var img = document.createElement('img');
                    img.id = 'signature-preview';
                    img.alt = 'Firma';
                    img.className = 'max-w-full max-h-full object-contain';
                    img.src = dataUrl;
                    signaturePreview.parentNode.replaceChild(img, signaturePreview);
                    signaturePreview = img;
                }
            }
            if (sigDrawInfo) sigDrawInfo.classList.remove('hidden');
            closeDrawModal();
        });
    }

    if (form) {
        form.addEventListener('submit', function() {
            if (sigCanvas && sigDrawn && hasStroke && !sigDrawn.value) {
                sigDrawn.value = sigCanvas.toDataURL('image/png');
            }
        });
    }
});
</script>
@endsection
