@extends('layouts.admin-layout')

@section('title', 'Asistente SAMS - Gemini')
@section('header-title', 'Asistente SAMS')
@section('header-subtitle', 'Pregunta por el sistema. Escribe o habla.')

@php
    $samsWakeName = trim((string) (auth()->user()->name ?? ''));
    if ($samsWakeName === '') {
        $samsWakeName = trim((string) (auth()->user()->email ?? 'Usuario'));
    }
@endphp

@section('content')
<div id="samsWakeBubble" class="fixed bottom-28 left-1/2 -translate-x-1/2 z-[13000] pointer-events-none hidden flex-col items-center">
    <div class="sams-wake-icon flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-2xl ring-4 ring-indigo-300/60">
        <i data-lucide="sparkles" class="w-10 h-10 text-white"></i>
    </div>
    <p class="mt-2 px-3 py-1 rounded-full bg-white/95 text-sm font-semibold text-indigo-800 shadow-md border border-indigo-100">SAMS te escucha</p>
</div>

<style>
@keyframes sams-wake-pulse {
    0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.5); }
    50% { transform: scale(1.06); box-shadow: 0 0 24px 8px rgba(99, 102, 241, 0.35); }
}
.sams-wake-icon { animation: sams-wake-pulse 1.2s ease-in-out infinite; }
</style>

<div class="flex flex-col h-[calc(100vh-8rem)] max-w-4xl mx-auto">
    @if(!$geminiConfigured)
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm mb-4">
            <strong>IA externa opcional.</strong> Puedes preguntar ya por <strong>totales de equipos, préstamos y asignaciones</strong> (respuesta inmediata con datos del sistema). Para charlas generales estilo asistente, añade una API key en <code class="bg-amber-100 px-1 rounded">.env</code>: <code>GEMINI_API_KEY</code>, <code>GROQ_API_KEY</code> u <code>OPENROUTER_API_KEY</code>.
        </div>
    @elseif(!empty($providers))
        <p class="text-xs text-gray-500 mb-2">Motores activos: {{ implode(', ', $providers) }}. Preguntas de inventario, préstamos o asignaciones se responden al instante con datos de SAMS; el resto usa la IA externa.</p>
    @else
        <p class="text-xs text-gray-500 mb-2">Sin API de IA externa: igual puedes preguntar totales de equipos, préstamos y asignaciones (datos en vivo del sistema).</p>
    @endif

    @if(auth()->user() && auth()->user()->empresa_id === null)
        <p class="text-xs text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2 mb-3">
            <strong>Administrador global:</strong> pide el <strong>manual técnico</strong> (p. ej. «dame el manual técnico») y se descargará un <strong>PDF</strong> generado en el servidor. Si después escribes «sí, en PDF» o «descargar PDF», también se entiende gracias al historial del chat. Para solo HTML, añade «solo html» en el mensaje.
        </p>
    @endif

    <div id="chatContainer" class="flex-1 overflow-y-auto space-y-6 p-4 pb-2">
        <div id="chatWelcome" class="text-center py-12">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i data-lucide="sparkles" class="w-8 h-8 text-white"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-1">Asistente SAMS</h3>
            <p class="text-sm text-gray-500 max-w-sm mx-auto">Pregunta lo que quieras sobre el sistema. Puedes escribir o usar el micrófono para hablar.</p>
            <div class="flex flex-wrap justify-center gap-2 mt-6">
                @foreach(($suggestionPrompts ?? ['¿Qué es SAMS?']) as $prompt)
                    <button type="button" class="suggestion-btn pw-btn-secondary px-4 py-2 rounded-full text-sm transition">{{ $prompt }}</button>
                @endforeach
            </div>
        </div>
        <div id="chatMessages" class="space-y-6 hidden"></div>
    </div>

    <div class="p-4 pt-2 border-t border-gray-200 bg-white/80 rounded-t-2xl">
        <div class="flex gap-2 items-end">
            <div class="flex-1 relative">
                <textarea id="chatInput" rows="1" placeholder="Escribe o usa el micrófono: habla y se enviará solo al callar." class="w-full px-4 py-3 pr-12 rounded-2xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none max-h-32" style="min-height: 48px;"></textarea>
                <button type="button" id="btnVoice" class="absolute right-2 bottom-2 w-10 h-10 rounded-xl flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-blue-600 transition" title="Hablar (clic para escuchar, clic de nuevo para enviar)">
                    <i data-lucide="mic" class="w-5 h-5" id="iconMic"></i>
                </button>
            </div>
            <button type="button" id="btnSend" class="pw-btn-primary p-3 rounded-2xl transition shadow-md">
                <i data-lucide="send" class="w-5 h-5"></i>
            </button>
        </div>
        <p id="voiceStatus" class="text-xs text-gray-400 mt-2 hidden">Escuchando... Habla y al terminar se enviará solo (1.5 s de silencio).</p>
        <p id="voiceError" class="text-xs text-amber-600 mt-2 hidden"></p>
        <div id="voiceControls" class="flex items-center gap-3 mt-3 flex-wrap">
            <span class="text-xs text-gray-500">Voz (lectura):</span>
            <select id="voiceGender" class="text-xs border border-gray-300 rounded-lg px-2 py-1">
                <option value="female">Femenina</option>
                <option value="male">Masculina</option>
                <option value="default">La más natural (recomendado)</option>
            </select>
            <button type="button" id="btnReleer" class="p-2 rounded-lg bg-gray-100 hover:bg-blue-100 hover:text-blue-600 text-gray-600 text-xs flex items-center gap-1" title="Releer último mensaje">
                <i data-lucide="volume-2" class="w-4 h-4"></i>
                <span>Releer</span>
            </button>
            <div id="ttsControls" class="flex items-center gap-2" style="display: none;">
                <button type="button" id="btnPause" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600" title="Pausar">
                    <i data-lucide="pause" class="w-4 h-4"></i>
                </button>
                <button type="button" id="btnResume" class="hidden p-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600" title="Continuar">
                    <i data-lucide="play" class="w-4 h-4"></i>
                </button>
                <button type="button" id="btnStop" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600" title="Detener">
                    <i data-lucide="square" class="w-4 h-4"></i>
                </button>
                <span id="ttsStatus" class="text-xs text-gray-500">Leyendo...</span>
            </div>
        </div>
    </div>
    @if(!empty($quickActions ?? []))
    <div class="mt-4 p-3 rounded-xl bg-gray-50 border border-gray-100">
        <p class="text-xs font-medium text-gray-600 mb-2">Acceso rápido SAMS</p>
        <div class="flex flex-wrap gap-2">
            @foreach($quickActions as $a)
            <a href="{{ $a['url'] }}" class="inline-flex items-center px-3 py-2 rounded-lg text-sm bg-white border border-gray-200 hover:border-blue-300 hover:bg-blue-50 text-gray-700 transition">{{ $a['label'] }}</a>
            @endforeach
        </div>
    </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    var SamsWakeUserName = @json($samsWakeName);

    var container = document.getElementById('chatContainer');
    var welcome = document.getElementById('chatWelcome');
    var messagesEl = document.getElementById('chatMessages');
    var input = document.getElementById('chatInput');
    var btnSend = document.getElementById('btnSend');
    var btnVoice = document.getElementById('btnVoice');
    var voiceStatus = document.getElementById('voiceStatus');

    var history = [];
    var voiceError = document.getElementById('voiceError');
    var ttsControls = document.getElementById('ttsControls');
    var btnPause = document.getElementById('btnPause');
    var btnResume = document.getElementById('btnResume');
    var btnStop = document.getElementById('btnStop');
    var ttsStatus = document.getElementById('ttsStatus');
    var voiceGender = document.getElementById('voiceGender');
    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    var recognition = null;
    var synth = window.speechSynthesis;
    var isListening = false;
    var spanishVoices = [];
    var lastModelMessage = '';
    var silenceTimer = null;
    var SILENCE_MS = 1500;

    var wakeRec = null;
    var wakeEnabled = false;
    var wakeFlowActive = false;
    var commandFromWake = false;
    var toggleOyeSams = document.getElementById('toggleOyeSams');
    var samsWakeBubble = document.getElementById('samsWakeBubble');
    var micPausedWake = false;
    var wakeDebounceUntil = 0;

    function normalizeWakeText(s) {
        try {
            return String(s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9\s]/gi, ' ');
        } catch (e) {
            return String(s || '').toLowerCase().replace(/[^a-z0-9\s]/gi, ' ');
        }
    }
    function matchesWakePhrase(text) {
        var t = normalizeWakeText(text);
        if (t.indexOf('oye sams') >= 0) return true;
        if (t.indexOf('hey sams') >= 0) return true;
        if (t.indexOf('hola sams') >= 0) return true;
        if (t.indexOf('ei sams') >= 0) return true;
        return /\b(oye|hey|hola|ei)\s+sams?\b/.test(t);
    }
    function showSamsWakeBubble() {
        if (!samsWakeBubble) return;
        samsWakeBubble.classList.remove('hidden');
        samsWakeBubble.classList.add('flex');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
    function hideSamsWakeBubble() {
        if (!samsWakeBubble) return;
        samsWakeBubble.classList.add('hidden');
        samsWakeBubble.classList.remove('flex');
    }
    function stopWakeListening() {
        if (!wakeRec) return;
        try { wakeRec.abort(); } catch (e) {}
    }
    function restartWakeIfEnabled() {
        if (!wakeEnabled || !wakeRec) return;
        wakeFlowActive = false;
        hideSamsWakeBubble();
        setTimeout(function() {
            if (!wakeEnabled || !wakeRec) return;
            try { wakeRec.start(); } catch (e) {}
        }, 450);
    }
    function onWakePhraseDetected() {
        if (!wakeEnabled || wakeFlowActive || !wakeRec) return;
        var now = Date.now();
        if (now < wakeDebounceUntil) return;
        wakeDebounceUntil = now + 4500;
        wakeFlowActive = true;
        stopWakeListening();
        showSamsWakeBubble();
        var greeting = 'Hola, ' + SamsWakeUserName + '. Te escucho, dime tu pregunta.';
        speakText(greeting, function() {
            if (!recognition) {
                wakeFlowActive = false;
                restartWakeIfEnabled();
                return;
            }
            commandFromWake = true;
            input.value = '';
            voiceStatus.textContent = 'Di tu pregunta… se enviará al detectar silencio.';
            voiceStatus.classList.remove('hidden');
            try {
                recognition.start();
            } catch (e) {
                commandFromWake = false;
                wakeFlowActive = false;
                hideSamsWakeBubble();
                restartWakeIfEnabled();
            }
        });
    }

    function hideVoiceError() {
        voiceError.classList.add('hidden');
        voiceError.textContent = '';
    }

    if (SpeechRecognition) {
        try {
            recognition = new SpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.lang = 'es-419';
            recognition.maxAlternatives = 1;
            recognition.onresult = function(e) {
                for (var i = e.resultIndex; i < e.results.length; i++) {
                    var r = e.results[i];
                    if (r.isFinal && r[0] && r[0].transcript && r[0].transcript.trim()) {
                        input.value = (input.value + ' ' + r[0].transcript).trim();
                        if (silenceTimer) clearTimeout(silenceTimer);
                        silenceTimer = setTimeout(function() {
                            silenceTimer = null;
                            if (isListening && input.value.trim()) recognition.stop();
                        }, SILENCE_MS);
                    }
                }
            };
            recognition.onstart = function() {
                isListening = true;
                hideVoiceError();
                voiceStatus.classList.remove('hidden');
                btnVoice.classList.add('!bg-red-100', '!text-red-600');
            };
            recognition.onend = function() {
                isListening = false;
                if (silenceTimer) { clearTimeout(silenceTimer); silenceTimer = null; }
                voiceStatus.classList.add('hidden');
                btnVoice.classList.remove('!bg-red-100', '!text-red-600');
                var txt = input.value.trim();
                var fromWake = commandFromWake;
                commandFromWake = false;
                var resumeMicWake = micPausedWake;
                micPausedWake = false;
                if (txt) {
                    if (fromWake) {
                        wakeFlowActive = false;
                    }
                    setTimeout(function() {
                        sendMessage(undefined, { afterReply: fromWake ? function() { restartWakeIfEnabled(); } : null });
                    }, 150);
                } else {
                    if (fromWake) {
                        hideSamsWakeBubble();
                        wakeFlowActive = false;
                        restartWakeIfEnabled();
                    } else if (resumeMicWake && wakeEnabled) {
                        restartWakeIfEnabled();
                    }
                }
            };
            recognition.onerror = function(e) {
                voiceStatus.classList.add('hidden');
                btnVoice.classList.remove('!bg-red-100', '!text-red-600');
                var fw = commandFromWake;
                commandFromWake = false;
                if (fw) {
                    wakeFlowActive = false;
                    hideSamsWakeBubble();
                    restartWakeIfEnabled();
                    return;
                }
                if (e.error === 'not-allowed') {
                    voiceError.textContent = 'Permiso denegado. Permite el micrófono en el navegador e intenta de nuevo.';
                } else if (e.error === 'no-speech') {
                    voiceError.textContent = 'No se detectó voz. Habla más cerca del micrófono e intenta de nuevo.';
                } else if (e.error === 'network') {
                    voiceError.textContent = 'Error de red con el reconocimiento de voz. Usa Chrome para mejor compatibilidad.';
                } else {
                    voiceError.textContent = 'Error de voz. Usa Chrome y permite el micrófono.';
                }
                voiceError.classList.remove('hidden');
            };
        } catch (err) {
            recognition = null;
        }
    }

    if (SpeechRecognition) {
        try {
            wakeRec = new SpeechRecognition();
            wakeRec.continuous = true;
            wakeRec.interimResults = true;
            wakeRec.lang = 'es-419';
            wakeRec.onresult = function(e) {
                if (!wakeEnabled || wakeFlowActive) return;
                var buf = '';
                for (var i = 0; i < e.results.length; i++) {
                    buf += e.results[i][0].transcript;
                }
                if (matchesWakePhrase(buf)) {
                    onWakePhraseDetected();
                }
            };
            wakeRec.onerror = function(e) {
                if (!wakeEnabled) return;
                if (e.error === 'not-allowed') return;
                if (e.error === 'aborted') return;
                setTimeout(function() {
                    if (wakeEnabled && !wakeFlowActive && wakeRec) {
                        try { wakeRec.start(); } catch (x) {}
                    }
                }, 600);
            };
            wakeRec.onend = function() {
                if (wakeEnabled && !wakeFlowActive && wakeRec) {
                    setTimeout(function() {
                        try { wakeRec.start(); } catch (x) {}
                    }, 320);
                }
            };
        } catch (werr) {
            wakeRec = null;
        }
    }

    function voiceNaturalScore(v) {
        var name = (v.name || '').toLowerCase();
        var lang = (v.lang || '').toLowerCase();
        var s = 0;
        if (lang.indexOf('419') >= 0 || lang.indexOf('mx') >= 0 || lang.indexOf('co') >= 0) s += 2;
        if (name.indexOf('google') >= 0) s += 5;
        if (name.indexOf('microsoft') >= 0 && name.indexOf('natural') >= 0) s += 3;
        if (name.indexOf('premium') >= 0 || name.indexOf('neural') >= 0) s += 2;
        if (name.indexOf('españa') >= 0 || lang === 'es-es') s += 1;
        return s;
    }

    function loadVoices() {
        var voices = synth.getVoices();
        var es = voices.filter(function(v) { return v.lang && /^es/i.test(v.lang); });
        es.sort(function(a, b) { return voiceNaturalScore(b) - voiceNaturalScore(a); });
        spanishVoices = es;
    }
    loadVoices();
    if (synth.onvoiceschanged !== undefined) synth.onvoiceschanged = loadVoices;

    var femalePatterns = ['female','mujer','femenina','helena','sabina','paulina','raquel','monica','laura','lucia','elena','catalina','hortensia','valencia','españa'];
    var malePatterns = ['male','hombre','masculino','pablo','jorge','carlos','antonio','diego','miguel'];

    function getSelectedVoice() {
        loadVoices();
        var pref = voiceGender ? voiceGender.value : 'default';
        if (spanishVoices.length === 0) return null;
        var n = function(v) { return (v.name || '').toLowerCase(); };
        if (pref === 'female') {
            var f = spanishVoices.find(function(v) {
                var name = n(v);
                if (malePatterns.some(function(p) { return name.indexOf(p) >= 0; })) return false;
                return femalePatterns.some(function(p) { return name.indexOf(p) >= 0; });
            });
            if (!f) f = spanishVoices.find(function(v) {
                var name = n(v);
                return !malePatterns.some(function(p) { return name.indexOf(p) >= 0; });
            });
            return f || spanishVoices[0];
        }
        if (pref === 'male') {
            var m = spanishVoices.find(function(v) {
                var name = n(v);
                return malePatterns.some(function(p) { return name.indexOf(p) >= 0; });
            });
            return m || (spanishVoices[1] || spanishVoices[0]);
        }
        return spanishVoices[0];
    }

    function prepareTextForTTS(txt) {
        if (!txt) return '';
        var t = txt.replace(/\*\*([^*]+)\*\*/g, '$1').replace(/\*([^*]+)\*/g, '$1').replace(/\[([^\]]+)\]\([^)]+\)/g, '$1').replace(/`([^`]+)`/g, '$1');
        t = t.replace(/\n{2,}/g, '. ').replace(/\n/g, ' ');
        t = t.replace(/\.{2,}/g, '.').replace(/\s{2,}/g, ' ');
        t = t.replace(/\s+([.,;:])/g, '$1');
        t = t.replace(/([.!?])\s+/g, '$1 ');
        return t.trim();
    }

    function showTtsControls() {
        if (ttsControls) ttsControls.style.display = 'flex';
        if (btnPause) btnPause.classList.remove('hidden');
        if (btnResume) btnResume.classList.add('hidden');
    }
    function hideTtsControls() {
        if (ttsControls) ttsControls.style.display = 'none';
        if (btnPause) btnPause.classList.remove('hidden');
        if (btnResume) btnResume.classList.add('hidden');
    }

    function speakText(text, onDone) {
        if (!synth || !text) {
            if (typeof onDone === 'function') onDone();
            return;
        }
        synth.cancel();
        var clean = prepareTextForTTS(text);
        if (!clean) {
            if (typeof onDone === 'function') onDone();
            return;
        }
        try {
            var u = new SpeechSynthesisUtterance(clean);
            var v = getSelectedVoice();
            if (v) {
                u.voice = v;
                u.lang = v.lang || 'es-419';
            } else {
                u.lang = 'es-419';
            }
            u.rate = 0.96;
            u.pitch = 1.02;
            u.volume = 1;
            u.onstart = function() {
                showTtsControls();
                if (ttsStatus) ttsStatus.textContent = 'Leyendo...';
            };
            u.onend = u.onerror = function() {
                hideTtsControls();
                if (typeof onDone === 'function') onDone();
            };
            synth.speak(u);
        } catch (e) {
            if (typeof onDone === 'function') onDone();
        }
    }

    if (btnPause) btnPause.addEventListener('click', function() {
        if (synth.speaking) {
            synth.pause();
            btnPause.classList.add('hidden');
            if (btnResume) btnResume.classList.remove('hidden');
            if (ttsStatus) ttsStatus.textContent = 'Pausado';
        }
    });
    if (btnResume) btnResume.addEventListener('click', function() {
        if (synth.paused) {
            synth.resume();
            btnResume.classList.add('hidden');
            btnPause.classList.remove('hidden');
            if (ttsStatus) ttsStatus.textContent = 'Leyendo...';
        }
    });
    if (btnStop) btnStop.addEventListener('click', function() {
        synth.cancel();
        hideTtsControls();
    });
    var btnReleer = document.getElementById('btnReleer');
    if (btnReleer) btnReleer.addEventListener('click', function() {
        if (lastModelMessage) speakText(lastModelMessage);
    });

    function appendMessage(role, text) {
        welcome.classList.add('hidden');
        messagesEl.classList.remove('hidden');
        if (role === 'model') lastModelMessage = text || '';
        var div = document.createElement('div');
        div.className = 'flex gap-3 ' + (role === 'user' ? 'flex-row-reverse' : '') + ' group';
        var bubble = document.createElement('div');
        bubble.className = 'max-w-[85%] px-4 py-3 rounded-2xl ' + (role === 'user' ? 'bg-blue-600 text-white rounded-br-md' : 'bg-gray-100 text-gray-800 rounded-bl-md');
        bubble.textContent = text;
        div.appendChild(bubble);
        if (role === 'model') {
            var wrap = document.createElement('div');
            wrap.className = 'flex items-start gap-2 flex-shrink-0';
            var icon = document.createElement('div');
            icon.className = 'w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center';
            icon.innerHTML = '<i data-lucide="sparkles" class="w-4 h-4 text-white"></i>';
            var btnSpeak = document.createElement('button');
            btnSpeak.type = 'button';
            btnSpeak.className = 'w-9 h-9 rounded-xl flex items-center justify-center text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition';
            btnSpeak.title = 'Leer en voz alta';
            btnSpeak.innerHTML = '<i data-lucide="volume-2" class="w-4 h-4"></i>';
            btnSpeak.addEventListener('click', function() { speakText(text); });
            wrap.appendChild(icon);
            wrap.appendChild(btnSpeak);
            div.insertBefore(wrap, bubble);
        }
        messagesEl.appendChild(div);
        container.scrollTop = container.scrollHeight;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function appendModelThinking() {
        var div = document.createElement('div');
        div.id = 'modelThinking';
        div.className = 'flex gap-3';
        div.innerHTML = '<div class="w-9 h-9 rounded-xl bg-gray-200 flex items-center justify-center flex-shrink-0"><i data-lucide="sparkles" class="w-4 h-4 text-gray-500"></i></div><div class="px-4 py-3 rounded-2xl rounded-bl-md bg-gray-100 text-gray-500 text-sm">Pensando...</div>';
        messagesEl.appendChild(div);
        container.scrollTop = container.scrollHeight;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function removeModelThinking() {
        var el = document.getElementById('modelThinking');
        if (el) el.remove();
    }

    function sendMessage(text, options) {
        options = options || {};
        var afterReply = options.afterReply;
        text = (text || input.value || '').trim();
        if (!text) return;
        input.value = '';
        appendMessage('user', text);
        history.push({ role: 'user', parts: [{ text: text }] });
        appendModelThinking();

        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        if (!csrfToken) {
            removeModelThinking();
            appendMessage('model', 'Falta el token de seguridad (CSRF). Recarga la página e intenta de nuevo.');
            if (typeof afterReply === 'function') afterReply();
            return;
        }

        var controller = new AbortController();
        var clientTimeoutMs = 52000;
        var timeoutId = setTimeout(function() {
            try { controller.abort(); } catch (e) {}
        }, clientTimeoutMs);

        fetch('{{ route("gemini.chat") }}', {
            method: 'POST',
            signal: controller.signal,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ message: text, history: history.slice(-12) })
        })
        .then(function(r) {
            clearTimeout(timeoutId);
            var ct = (r.headers.get('content-type') || '').toLowerCase();
            if (!r.ok) {
                if (ct.indexOf('application/json') >= 0) {
                    return r.json().then(function(d) {
                        return Promise.reject({ http: r.status, data: d });
                    });
                }
                return Promise.reject({ http: r.status, data: { error: 'Error del servidor (HTTP ' + r.status + ').' } });
            }
            if (ct.indexOf('application/json') < 0) {
                return Promise.reject({ http: r.status, data: { error: 'Respuesta no válida del servidor.' } });
            }
            return r.json();
        })
        .then(function(data) {
            removeModelThinking();
            if (data.error) {
                appendMessage('model', 'Error: ' + data.error);
                if (typeof afterReply === 'function') afterReply();
                return;
            }
            var reply = data.reply || '';
            appendMessage('model', reply);
            history.push({ role: 'model', parts: [{ text: reply }] });
            container.scrollTop = container.scrollHeight;
            if (data.download_manual_pdf && data.manual_pdf_url) {
                try {
                    var ap = document.createElement('a');
                    ap.href = data.manual_pdf_url;
                    ap.setAttribute('download', 'SAMS-Manual-Tecnico.pdf');
                    ap.rel = 'noopener';
                    document.body.appendChild(ap);
                    ap.click();
                    document.body.removeChild(ap);
                } catch (e) {}
            }
            if (data.download_manual && data.manual_url) {
                try {
                    var a = document.createElement('a');
                    a.href = data.manual_url;
                    a.setAttribute('download', 'SAMS-Manual-Tecnico.html');
                    a.rel = 'noopener';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                } catch (e) {}
            }
            speakText(reply, typeof afterReply === 'function' ? afterReply : undefined);
        })
        .catch(function(err) {
            clearTimeout(timeoutId);
            removeModelThinking();
            if (err && err.name === 'AbortError') {
                appendMessage('model', 'Tiempo de espera agotado. Para totales de equipos o préstamos, reintenta; si solo falla la IA externa, revisa las API keys en .env.');
                if (typeof afterReply === 'function') afterReply();
                return;
            }
            if (err && err.data) {
                var d = err.data;
                var msg = d.error || d.message || '';
                if (!msg && d.errors && typeof d.errors === 'object') {
                    try {
                        msg = Object.keys(d.errors).map(function(k) {
                            return Array.isArray(d.errors[k]) ? d.errors[k].join(' ') : String(d.errors[k]);
                        }).join(' ');
                    } catch (e) {}
                }
                if (msg) {
                    appendMessage('model', 'Error: ' + msg);
                    if (typeof afterReply === 'function') afterReply();
                    return;
                }
            }
            if (err && err.message) {
                appendMessage('model', 'Error: ' + err.message);
                if (typeof afterReply === 'function') afterReply();
                return;
            }
            appendMessage('model', 'No se pudo completar la petición. Recarga la página, revisa que la sesión siga activa o prueba otro navegador.');
            if (typeof afterReply === 'function') afterReply();
        });
    }

    btnSend.addEventListener('click', function() { sendMessage(); });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    document.querySelectorAll('.suggestion-btn').forEach(function(btn) {
        btn.addEventListener('click', function() { sendMessage(btn.textContent); });
    });

    if (recognition) {
        btnVoice.addEventListener('click', function() {
            hideVoiceError();
            if (isListening) {
                recognition.stop();
                return;
            }
            if (wakeEnabled && wakeRec && !wakeFlowActive) {
                micPausedWake = true;
                stopWakeListening();
            }
            try {
                recognition.start();
            } catch (e) {
                micPausedWake = false;
                voiceError.textContent = 'No se pudo iniciar el micrófono. Usa Chrome y permite el acceso.';
                voiceError.classList.remove('hidden');
            }
        });
    } else {
        btnVoice.style.display = 'none';
    }

    if (toggleOyeSams) {
        if (!wakeRec) {
            toggleOyeSams.disabled = true;
            toggleOyeSams.parentElement.classList.add('opacity-50');
        }
        toggleOyeSams.addEventListener('change', function() {
            wakeEnabled = !!toggleOyeSams.checked;
            var hint = document.getElementById('wakeModeHint');
            if (hint) hint.classList.toggle('hidden', !wakeEnabled);
            if (!wakeEnabled) {
                stopWakeListening();
                return;
            }
            if (!wakeRec) return;
            try {
                wakeRec.start();
            } catch (e) {
                voiceError.textContent = 'No se pudo activar el modo Oye SAMS. Permite el micrófono.';
                voiceError.classList.remove('hidden');
                toggleOyeSams.checked = false;
                wakeEnabled = false;
            }
        });
    }

    if (!SpeechRecognition) {
        var wakeCard = document.getElementById('wakeOyeSamsCard');
        if (wakeCard) wakeCard.classList.add('hidden');
    }
});
</script>
@endsection
