@php
    $uiColors = $uiColors ?? [];
    $btnsCfg = $btnsCfg ?? [];
    $typographyCfg = $typographyCfg ?? [];
    $sysCfg = $uiColors['system'] ?? [];
    $cardsCfg = $uiColors['cards'] ?? [];
    $systemType = $sysCfg['type'] ?? 'gradient';
    $colorFrom = $sysCfg['gradient_from'] ?? '#42A0D7';
    $colorTo = $sysCfg['gradient_to'] ?? '#075479';
    $systemImageUrl = !empty($sysCfg['image_path']) ? asset('storage/' . $sysCfg['image_path']) : null;
    $cardsRadius = $cardsCfg['radius'] ?? 'xl';
    $cardsShadow = $cardsCfg['shadow'] ?? 'soft';
    $cardsStyle = $cardsCfg['style'] ?? 'solid';
    $cardsBgType = $cardsCfg['bg_type'] ?? 'solid';
    $cardsBgColor = $cardsCfg['bg_color'] ?? '#ffffff';
    $cardsBgOpacity = (float)($cardsCfg['bg_opacity'] ?? 0.85);
    $cardsBgFrom = $cardsCfg['bg_gradient_from'] ?? '#e0f2fe';
    $cardsBgTo = $cardsCfg['bg_gradient_to'] ?? '#bae6fd';
    $hex = ltrim($cardsBgColor, '#'); if (strlen($hex) < 6) $hex = 'ffffff';
    $r = hexdec(substr($hex,0,2)); $g = hexdec(substr($hex,2,2)); $b = hexdec(substr($hex,4,2));
    $cardsBgRgba = "rgba($r, $g, $b, $cardsBgOpacity)";
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SAMS') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    @vite('resources/css/app.css')
    <!-- Tema Prevention World -->

    <style>
        :root {
            --sams-btn-login-bg: {{ $btnsCfg['login']['bg'] ?? '#4f46e5' }};
            --sams-btn-login-text: {{ $btnsCfg['login']['text'] ?? '#ffffff' }};
            --sams-cards-bg: {{ $cardsBgColor }};
            --sams-cards-bg-rgba: {{ $cardsBgRgba }};
            --sams-cards-gradient-from: {{ $cardsBgFrom }};
            --sams-cards-gradient-to: {{ $cardsBgTo }};
            --sams-typography-font: '{{ addslashes($typographyCfg['font_family'] ?? 'Inter') }}', sans-serif;
            --sams-typography-color: {{ $typographyCfg['color'] ?? '#eaf6ff' }};
        }
    </style>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
    @yield('styles')
</head>
<body class="min-h-screen relative overflow-x-hidden overflow-y-auto bg-[#0a0a0a] pw-typography" data-cards-radius="{{ $cardsRadius }}" data-cards-shadow="{{ $cardsShadow }}" data-cards-style="{{ $cardsStyle }}" data-cards-bg-type="{{ $cardsBgType }}">
    <div id="particles-js" class="fixed inset-0 -z-10"></div>
    <div id="bubbles" class="fixed inset-0 pointer-events-none z-0"></div>
    <div class="relative z-10 min-h-screen">
        @yield('content')
    </div>
    <footer class="relative z-10 px-4 md:px-6 py-2 border-t border-cyan-300/20 text-xs text-cyan-100/80 bg-[#0a2d4a]/60">
        <div class="flex items-center justify-between">
            <span>SAMS NEXUS · Prevention World</span>
            <span>{{ date('Y') }}</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        function pwLowPowerMode() {
            const isMobile = window.matchMedia('(max-width: 767px)').matches;
            const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const lowCoreDevice = (navigator.hardwareConcurrency || 8) <= 4;
            return isMobile || prefersReduced || lowCoreDevice;
        }

        function pwDisableBgFx() {
            const particles = document.getElementById('particles-js');
            const bubbles = document.getElementById('bubbles');
            if (particles) particles.style.display = 'none';
            if (bubbles) bubbles.style.display = 'none';
            if (window.__pwBubbleTimer) {
                clearInterval(window.__pwBubbleTimer);
                window.__pwBubbleTimer = null;
            }
        }

        if (!window.__pwBgFxInit && typeof particlesJS !== 'undefined') {
            if (pwLowPowerMode()) {
                pwDisableBgFx();
            } else {
                window.__pwBgFxInit = true;
                particlesJS('particles-js', {
                particles: {
                    number: { value: 80, density: { enable: true, value_area: 800 } },
                    color: { value: ['#42A0D7', '#67e8f9', '#ffffff'] },
                    shape: { type: 'circle' },
                    opacity: { value: 0.5, random: true },
                    size: { value: 3.5, random: true },
                    line_linked: { enable: true, distance: 140, color: '#42A0D7', opacity: 0.18, width: 1 },
                    move: { enable: true, speed: 1.8, direction: 'none', random: false, straight: false, out_mode: 'out' }
                },
                interactivity: { detect_on: 'canvas', events: { onhover: { enable: true, mode: 'repulse' }, onclick: { enable: true, mode: 'push' }, resize: true } },
                retina_detect: true
            });
                window.__pwBubbleTimer = setInterval(function () {
                    const bubbles = document.getElementById('bubbles');
                    if (!bubbles) return;
                    if (bubbles.childElementCount > 24) return;
                    const bubble = document.createElement('div');
                    bubble.className = 'bubble';
                    const size = Math.random() * 45 + 14;
                    bubble.style.width = size + 'px';
                    bubble.style.height = size + 'px';
                    bubble.style.left = (Math.random() * 100) + 'vw';
                    bubble.style.animationDuration = (Math.random() * 18 + 14) + 's';
                    bubble.style.opacity = (Math.random() * 0.35 + 0.12).toString();
                    bubbles.appendChild(bubble);
                    setTimeout(function () { bubble.remove(); }, 40000);
                }, 900);
            }
        }

        window.addEventListener('resize', function () {
            if (!pwLowPowerMode()) return;
            pwDisableBgFx();
        });
        window.addEventListener('pagehide', function () {
            if (window.__pwBubbleTimer) {
                clearInterval(window.__pwBubbleTimer);
                window.__pwBubbleTimer = null;
            }
        });
    </script>
    <script>
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        (function () {
            if (window.__pwImagePickerInit) return;
            window.__pwImagePickerInit = true;

            function isImageCandidate(input) {
                if (!input || input.type !== 'file') return false;
                const accept = (input.getAttribute('accept') || '').toLowerCase();
                if (accept.includes('image')) return true;
                const hint = ((input.name || '') + ' ' + (input.id || '') + ' ' + (input.getAttribute('data-file-kind') || '')).toLowerCase();
                return /(image|imagen|foto|photo|logo|firma|signature|avatar)/.test(hint);
            }

            function openPicker(input, useCamera) {
                const prevCapture = input.getAttribute('capture');
                const prevAccept = input.getAttribute('accept');
                input.setAttribute('accept', 'image/*');

                if (useCamera) input.setAttribute('capture', 'environment');
                else input.removeAttribute('capture');

                input.click();

                setTimeout(function () {
                    if (prevAccept === null) input.removeAttribute('accept');
                    else input.setAttribute('accept', prevAccept);
                    if (prevCapture === null) input.removeAttribute('capture');
                    else input.setAttribute('capture', prevCapture);
                }, 500);
            }

            function attachImageButtons(input) {
                if (!input) return;
                if (input.dataset.cameraUiReady === '1') return;
                if (!input.dataset.cameraUiKey) {
                    input.dataset.cameraUiKey = (input.id || input.name || 'img');
                }
                const key = input.dataset.cameraUiKey;
                const parent = input.parentElement;
                if (parent) {
                    parent.querySelectorAll('.pw-image-picker-actions').forEach(function (node) {
                        node.remove();
                    });
                }

                const wrap = document.createElement('div');
                wrap.className = 'pw-image-picker-actions mt-2 flex flex-wrap gap-2';
                wrap.setAttribute('data-for', key);

                const btnCam = document.createElement('button');
                btnCam.type = 'button';
                btnCam.className = 'px-3 py-1.5 rounded-lg text-xs font-medium border border-sky-300 bg-sky-50 text-sky-700 hover:bg-sky-100';
                btnCam.textContent = 'Tomar foto';
                btnCam.addEventListener('click', function () { openPicker(input, true); });

                const btnFile = document.createElement('button');
                btnFile.type = 'button';
                btnFile.className = 'px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-300 bg-gray-50 text-gray-700 hover:bg-gray-100';
                btnFile.textContent = 'Elegir archivo';
                btnFile.addEventListener('click', function () { openPicker(input, false); });

                wrap.appendChild(btnCam);
                wrap.appendChild(btnFile);
                input.insertAdjacentElement('afterend', wrap);
                input.dataset.cameraUiReady = '1';
            }

            function enableCameraOption(root) {
                const scope = root || document;
                scope.querySelectorAll('input[type="file"]').forEach(function (input) {
                    if (input.dataset.cameraUi === 'off' || input.closest('[data-camera-ui="off"]')) return;
                    if (!isImageCandidate(input)) return;

                    if (!input.getAttribute('accept')) {
                        input.setAttribute('accept', 'image/*');
                    }

                    if (input.hasAttribute('capture')) input.removeAttribute('capture');
                    attachImageButtons(input);
                });
            }

            enableCameraOption(document);
        })();
    </script>
    <style>
        #particles-js {
            background: linear-gradient(135deg, #2ea8e6 0%, #1283c3 42%, #0a5f93 100%);
        }
        .bubble { position: absolute; border-radius: 50%; background: rgba(180, 236, 255, 0.16); border: 1px solid rgba(180, 236, 255, 0.45); box-shadow: 0 0 38px rgba(77, 195, 246, 0.46); animation: floatBubble linear infinite; pointer-events: none; }
        @keyframes floatBubble { 0% { transform: translateY(100vh) scale(0.6); opacity: 0; } 20% { opacity: 0.7; } 80% { opacity: 0.7; } 100% { transform: translateY(-150px) scale(1.4); opacity: 0; } }
        .glass { background: rgba(9, 38, 67, 0.72); backdrop-filter: blur(28px); border: 1px solid rgba(180, 236, 255, 0.24); }
        .glow-card::before { content: ''; position: absolute; inset: -4px; background: linear-gradient(45deg, #42A0D7, #67e8f9, #42A0D7); filter: blur(22px); opacity: 0.18; z-index: -1; animation: rotateBorder 16s linear infinite; }
        @keyframes rotateBorder { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        @media (max-width: 767px), (prefers-reduced-motion: reduce) {
            #particles-js,
            #bubbles {
                display: none !important;
            }
            .bubble,
            .glow-card::before {
                animation: none !important;
            }
            .glass {
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
            }
        }
    </style>
    <script>
        (function () {
            window.addEventListener('pageshow', function (e) {
                if (e.persisted) {
                    window.location.reload();
                }
            });
        })();
    </script>
</body>
</html>
