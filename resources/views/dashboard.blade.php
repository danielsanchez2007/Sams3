@php
    $inicioCfg = $inicioCfg ?? [];
    $logosCfg = $logosCfg ?? [];
    $logoPrimario = $logosCfg['primario'] ?? 'images/logo-instituto.png';
    $logoFallback = 'images/logo-principal.png';
    $campusVirtualUrl = 'https://instituto.preventionworld.edu.co/';
    $quienesSomosUrl = 'https://preventionworld.edu.co/portal/quienes-somos/';
    $waCliente = '573177126532';
    $waSoporte = '573249804945';
    $waClienteUrl = 'https://wa.me/' . $waCliente;
    $waSoporteUrl = 'https://wa.me/' . $waSoporte;
    $facebookUrl = 'https://www.facebook.com/share/1G3b5MKFSF/';
    $instagramUrl = 'https://www.instagram.com/preventionworldqhsesas?igsh=YWd3YmI3enNzeXly';
    $tiktokUrl = 'https://www.tiktok.com/@preventionworldqhsesas?_r=1&_t=ZS-99LLRWc66AS';
@endphp

<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Prevention World | Plataforma Institucional</title>
    <x-sams-assets />

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap');

        :root {
            --primary: #003087;
            --accent: #00d4ff;
        }

        .pw-logo-header {
            height: 4rem;
            width: auto;
            max-width: 280px;
            object-fit: contain;
            display: block;
        }
        .pw-logo-footer {
            height: 3rem;
            width: auto;
            max-width: 220px;
            object-fit: contain;
            display: block;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 208, 255, 0.15);
        }

        .card-hover {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        .hero-carousel {
            position: relative;
            min-height: 34rem;
            overflow: hidden;
        }
        @media (min-width: 768px) {
            .hero-carousel { min-height: 40rem; }
        }

        .carousel-slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            visibility: hidden;
            transition: opacity 1.1s ease, visibility 1.1s ease;
        }
        .carousel-slide.is-active {
            opacity: 1;
            visibility: visible;
            z-index: 1;
        }
        .carousel-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scale(1.04);
        }
        .carousel-slide.is-active img {
            animation: kenburns 8.5s ease-out forwards;
        }

        @keyframes kenburns {
            0% { transform: scale(1.04) translate3d(0, 0, 0); }
            100% { transform: scale(1.14) translate3d(-1.5%, -1%, 0); }
        }

        .carousel-overlay {
            background:
                linear-gradient(90deg, rgba(0, 24, 72, 0.88) 0%, rgba(0, 48, 135, 0.55) 48%, rgba(0, 48, 135, 0.22) 100%),
                linear-gradient(180deg, rgba(0, 20, 60, 0.35) 0%, rgba(0, 20, 60, 0.15) 40%, rgba(0, 20, 60, 0.55) 100%);
        }

        .carousel-dot {
            width: 0.7rem;
            height: 0.7rem;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.45);
            transition: width 0.3s ease, background 0.3s ease;
        }
        .carousel-dot.is-active {
            width: 2rem;
            background: #fff;
        }

        .caption-chip {
            animation: captionIn 0.7s ease both;
        }
        @keyframes captionIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal {
            animation: modalPop 0.3s ease;
        }
        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .social-btn {
            width: 2.5rem;
            height: 2.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            background: #1e293b;
            color: #fff;
            transition: background 0.2s ease, transform 0.2s ease;
        }
        .social-btn:hover { transform: translateY(-2px); }
        .social-btn.facebook:hover { background: #1877F2; }
        .social-btn.instagram:hover { background: #E4405F; }
        .social-btn.tiktok:hover { background: #010101; }
        .social-btn.whatsapp:hover { background: #25D366; }
    </style>
</head>
<body class="min-h-screen bg-slate-50">

    <!-- HEADER -->
    <header class="bg-white/95 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-3 flex justify-between items-center">
            <a href="{{ route('sistema.info') }}" class="flex items-center gap-3 min-w-0">
                <img
                    src="{{ asset($logoPrimario) }}"
                    alt="Prevention World"
                    class="pw-logo-header h-14 md:h-16 w-auto max-w-[280px] object-contain"
                    onerror="this.onerror=null;this.src='{{ asset($logoFallback) }}';"
                >
            </a>

            @auth
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3">
                    @if(Auth::user()->photo)
                        <img src="{{ asset('storage/' . Auth::user()->photo) }}" class="w-9 h-9 rounded-2xl object-cover ring-2 ring-cyan-400" alt="">
                    @else
                        <div class="w-9 h-9 bg-blue-700 text-white rounded-2xl flex items-center justify-center font-bold">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <p class="font-semibold text-slate-800">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-emerald-600">En línea</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 rounded-2xl transition-colors flex items-center gap-2">
                        <i data-lucide="log-out"></i>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>
            @else
            <a href="{{ route('login') }}" class="px-7 py-3 bg-[#003087] hover:bg-blue-800 text-white font-semibold rounded-3xl transition-colors flex items-center gap-2">
                <i data-lucide="log-in"></i> Iniciar Sesión
            </a>
            @endauth
        </div>
    </header>

    <main>
        <!-- HERO CON CARRUSEL -->
        <section class="hero-carousel" id="heroCarousel" aria-roledescription="carrusel">
            <div class="carousel-slide is-active" data-caption="Trabajo en Alturas">
                <img src="{{ asset('images/carousel-alturas.jpg') }}" alt="Trabajo en alturas">
            </div>
            <div class="carousel-slide" data-caption="Realidad Virtual">
                <img src="{{ asset('images/carousel-realidad-virtual.jpg') }}" alt="Capacitación en realidad virtual">
            </div>
            <div class="carousel-slide" data-caption="Prevention World">
                <img src="{{ asset('images/carousel-yedy-pw.jpg') }}" alt="Prevention World">
            </div>

            <div class="carousel-overlay absolute inset-0 z-[2] pointer-events-none"></div>

            <div class="relative z-[3] max-w-7xl mx-auto px-6 h-full min-h-[34rem] md:min-h-[40rem] flex items-center">
                <div class="space-y-8 max-w-xl text-white">
                    <span id="slideCaption" class="caption-chip inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/15 border border-white/20 text-sm tracking-wide">
                        Trabajo en Alturas
                    </span>
                    <h1 class="text-5xl md:text-6xl font-bold leading-tight drop-shadow-lg">
                        Gestiona la Seguridad<br>y Salud Ocupacional
                    </h1>
                    <p class="text-2xl text-cyan-300">con Prevention World</p>

                    <p class="text-lg text-slate-200 max-w-md">
                        Plataforma integral para formación SST, certificaciones, campus virtual y gestión de seguridad.
                    </p>

                    <div class="flex flex-wrap gap-4">
                        <a href="{{ route('login') }}" class="px-8 py-4 bg-white text-[#003087] font-semibold rounded-3xl hover:scale-105 transition-transform flex items-center gap-3 shadow-lg">
                            <i data-lucide="shield-check" class="w-6 h-6"></i>
                            Acceder al Sistema
                        </a>
                        <a href="{{ $campusVirtualUrl }}" target="_blank" rel="noopener noreferrer" class="px-8 py-4 border border-white/50 hover:bg-white/10 text-white font-semibold rounded-3xl transition-colors">
                            Conocer más
                        </a>
                    </div>
                </div>
            </div>

            <button type="button" onclick="prevSlide()" class="absolute left-4 md:left-6 top-1/2 -translate-y-1/2 z-[4] bg-black/35 hover:bg-black/60 text-white p-3 rounded-full transition-colors" aria-label="Anterior">
                <i data-lucide="chevron-left" class="w-6 h-6"></i>
            </button>
            <button type="button" onclick="nextSlide()" class="absolute right-4 md:right-6 top-1/2 -translate-y-1/2 z-[4] bg-black/35 hover:bg-black/60 text-white p-3 rounded-full transition-colors" aria-label="Siguiente">
                <i data-lucide="chevron-right" class="w-6 h-6"></i>
            </button>

            <div class="absolute bottom-6 left-0 right-0 z-[4] flex justify-center gap-3">
                <button type="button" onclick="goToSlide(0)" class="carousel-dot is-active" aria-label="Diapositiva 1"></button>
                <button type="button" onclick="goToSlide(1)" class="carousel-dot" aria-label="Diapositiva 2"></button>
                <button type="button" onclick="goToSlide(2)" class="carousel-dot" aria-label="Diapositiva 3"></button>
            </div>
        </section>

        <!-- SECCIONES -->
        <section class="max-w-7xl mx-auto px-6 py-20">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="glass rounded-3xl p-8 card-hover">
                    <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="users" class="w-9 h-9 text-[#003087]"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3 text-slate-800">Quiénes Somos</h3>
                    <p class="text-slate-600">Institución líder en educación, seguridad y salud ocupacional en Colombia.</p>
                    <a href="{{ $quienesSomosUrl }}" target="_blank" rel="noopener noreferrer" class="mt-6 inline-flex items-center gap-2 text-[#003087] font-medium hover:gap-3 transition-all">
                        Ver más →
                    </a>
                </div>

                <div class="glass rounded-3xl p-8 card-hover">
                    <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="graduation-cap" class="w-9 h-9 text-[#003087]"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3 text-slate-800">Campus Virtual</h3>
                    <p class="text-slate-600">Aprendizaje flexible y de alta calidad en prevención de riesgos laborales.</p>
                    <a href="{{ $campusVirtualUrl }}" target="_blank" rel="noopener noreferrer" class="mt-6 inline-flex items-center gap-2 text-[#003087] font-medium hover:gap-3 transition-all">
                        Ingresar →
                    </a>
                </div>

                <div class="glass rounded-3xl p-8 card-hover">
                    <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="headphones" class="w-9 h-9 text-[#003087]"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3 text-slate-800">Servicio al Cliente</h3>
                    <p class="text-slate-600">Comunícate directamente con nuestras gestoras.</p>
                    <a href="{{ $waClienteUrl }}" target="_blank" rel="noopener noreferrer" class="mt-6 inline-flex items-center gap-2 text-[#003087] font-medium hover:gap-3 transition-all">
                        Contactar por WhatsApp →
                    </a>
                </div>

                <div class="glass rounded-3xl p-8 card-hover">
                    <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="briefcase" class="w-9 h-9 text-[#003087]"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3 text-slate-800">Trabaje con Nosotros</h3>
                    <p class="text-slate-600">Únete a nuestro equipo y forma parte de una cultura de prevención.</p>
                    <span class="mt-6 inline-flex items-center gap-2 text-slate-400 font-medium">
                        Próximamente
                    </span>
                </div>
            </div>
        </section>

        <!-- ====================== FOOTER ====================== -->
        <footer class="bg-slate-900 text-slate-300">
            <div class="max-w-7xl mx-auto px-6 pt-16 pb-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                    <!-- Izquierda -->
                    <div>
                        <div class="flex items-center gap-4 mb-6">
                            <img
                                src="{{ asset($logoPrimario) }}"
                                alt="Prevention World"
                                class="pw-logo-footer h-12 w-auto max-w-[220px] object-contain"
                                onerror="this.onerror=null;this.src='{{ asset($logoFallback) }}';"
                            >
                        </div>
                        <p class="text-lg font-medium text-white mb-3">"Una Cultura para Mejorar Nuestra Calidad de Vida"</p>
                        <p class="text-slate-400 leading-relaxed">
                            Instituto Prevention World QHSE S.A.S.<br>
                            Líderes en formación SST y seguridad ocupacional.
                        </p>
                    </div>

                    <!-- Centro -->
                    <div>
                        <h4 class="text-white font-semibold text-lg mb-6">Enlaces Rápidos</h4>
                        <div class="grid grid-cols-2 gap-y-3 text-sm">
                            <a href="{{ $quienesSomosUrl }}" target="_blank" rel="noopener noreferrer" class="hover:text-cyan-400 transition-colors">Quiénes Somos</a>
                            <a href="{{ $campusVirtualUrl }}" target="_blank" rel="noopener noreferrer" class="hover:text-cyan-400 transition-colors">Campus Virtual</a>
                            <a href="{{ $waClienteUrl }}" target="_blank" rel="noopener noreferrer" class="hover:text-cyan-400 transition-colors">Servicio al Cliente</a>
                            <a href="{{ $waSoporteUrl }}" target="_blank" rel="noopener noreferrer" class="hover:text-cyan-400 transition-colors">Soporte Técnico</a>
                            <span class="text-slate-500 cursor-default">Certificaciones</span>
                            <span class="text-slate-500 cursor-default" title="Próximamente">Trabaje con Nosotros</span>
                        </div>
                    </div>

                    <!-- Derecha -->
                    <div>
                        <h4 class="text-white font-semibold text-lg mb-6">Contáctanos</h4>
                        <div class="space-y-4 text-sm">
                            <a href="{{ $waClienteUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-start gap-3 hover:text-cyan-400 transition-colors">
                                <i data-lucide="message-circle" class="w-5 h-5 mt-0.5 text-cyan-400"></i>
                                <div>
                                    <p>Servicio al cliente</p>
                                    <p class="text-slate-400">+57 317 712 6532</p>
                                </div>
                            </a>
                            <a href="{{ $waSoporteUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-start gap-3 hover:text-cyan-400 transition-colors">
                                <i data-lucide="headphones" class="w-5 h-5 mt-0.5 text-cyan-400"></i>
                                <div>
                                    <p>Soporte técnico</p>
                                    <p class="text-slate-400">+57 324 980 4945</p>
                                </div>
                            </a>
                            <div class="flex items-start gap-3">
                                <i data-lucide="mail" class="w-5 h-5 mt-0.5 text-cyan-400"></i>
                                <div><p>info@preventionworld.edu.co</p></div>
                            </div>
                        </div>

                        <div class="mt-10">
                            <h5 class="text-white font-medium mb-4">Síguenos en:</h5>
                            <div class="flex gap-4">
                                <a href="{{ $facebookUrl }}" target="_blank" rel="noopener noreferrer" class="social-btn facebook" aria-label="Facebook">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v3H7v3h3v7h3v-7h3.2l.8-3H13v-3c0-.6.4-1 1-1z"/></svg>
                                </a>
                                <a href="{{ $instagramUrl }}" target="_blank" rel="noopener noreferrer" class="social-btn instagram" aria-label="Instagram">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                                </a>
                                <a href="{{ $tiktokUrl }}" target="_blank" rel="noopener noreferrer" class="social-btn tiktok" aria-label="TikTok">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M14.5 3c.4 2.6 1.9 4.4 4.5 4.7v3.1c-1.5-.05-2.9-.5-4.1-1.3v6.7c0 3.4-2.6 6.1-6.2 6.1S2.6 19.6 2.6 16.2 5.2 10.1 8.8 10.1c.4 0 .8 0 1.2.1v3.2c-.4-.1-.8-.2-1.2-.2-1.7 0-3.1 1.4-3.1 3.1s1.4 3.1 3.1 3.1 3.1-1.4 3.1-3.1V3h2.6z"/></svg>
                                </a>
                                <a href="{{ $waClienteUrl }}" target="_blank" rel="noopener noreferrer" class="social-btn whatsapp" aria-label="WhatsApp">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.5 3.5A11 11 0 0 0 2.1 17.3L1 23l5.8-1.1A11 11 0 0 0 12 23a11 11 0 0 0 8.5-19.5zM12 21a9 9 0 0 1-4.6-1.3l-.3-.2-3.4.7.7-3.3-.2-.3A9 9 0 1 1 12 21zm5-6.7c-.3-.1-1.6-.8-1.9-.9s-.4-.1-.6.1-.7.9-.8 1.1-.3.2-.6.1a7.4 7.4 0 0 1-2.2-1.4 8.2 8.2 0 0 1-1.5-1.9c-.2-.3 0-.4.1-.6l.5-.6.1-.3c0-.1 0-.3-.1-.4s-.6-1.5-.8-2-.4-.5-.6-.5h-.5c-.2 0-.4.1-.6.3s-.8.8-.8 1.9.8 2.2.9 2.3c.1.2 1.6 2.5 3.8 3.5 1.4.6 1.9.7 2.6.6.4-.1 1.6-.6 1.8-1.3.2-.6.2-1.2.2-1.3 0-.1-.2-.2-.4-.3z"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barra inferior -->
            <div class="border-t border-slate-800 py-6">
                <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center text-xs text-slate-500">
                    <p>© {{ date('Y') }} Instituto Prevention World QHSE S.A.S. Todos los derechos reservados.</p>
                    <div class="flex gap-6 mt-4 md:mt-0">
                        <a href="#" onclick="openPrivacyModal(); return false;" class="hover:text-slate-300 transition-colors">Política de Privacidad</a>
                        <a href="#" onclick="openTermsModal(); return false;" class="hover:text-slate-300 transition-colors">Términos de Uso</a>
                    </div>
                </div>
            </div>
        </footer>
    </main>

    <!-- Modal Política de Privacidad -->
    <div id="privacyModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-[100]">
        <div class="modal bg-white text-slate-800 rounded-3xl p-8 max-w-lg w-full mx-4 max-h-[90vh] overflow-auto">
            <h3 class="text-2xl font-bold mb-6">Política de Privacidad</h3>
            <div class="prose text-sm">
                <p>En Prevention World respetamos tu privacidad...</p>
            </div>
            <label class="flex items-center gap-3 mt-8">
                <input type="checkbox" id="acceptPrivacy" class="w-5 h-5 accent-cyan-600">
                <span>He leído y acepto la Política de Privacidad</span>
            </label>
            <button onclick="closeModal('privacyModal')" class="mt-6 w-full py-4 bg-cyan-600 text-white rounded-2xl">Aceptar y Cerrar</button>
        </div>
    </div>

    <!-- Modal Términos de Uso -->
    <div id="termsModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-[100]">
        <div class="modal bg-white text-slate-800 rounded-3xl p-8 max-w-lg w-full mx-4 max-h-[90vh] overflow-auto">
            <h3 class="text-2xl font-bold mb-6">Términos de Uso</h3>
            <div class="prose text-sm">
                <p>Al acceder a esta plataforma aceptas nuestros términos...</p>
            </div>
            <label class="flex items-center gap-3 mt-8">
                <input type="checkbox" id="acceptTerms" class="w-5 h-5 accent-cyan-600">
                <span>He leído y acepto los Términos de Uso</span>
            </label>
            <button onclick="closeModal('termsModal')" class="mt-6 w-full py-4 bg-cyan-600 text-white rounded-2xl">Aceptar y Cerrar</button>
        </div>
    </div>

    <script>
        lucide.createIcons();

        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const dots = document.querySelectorAll('.carousel-dot');
        const captionEl = document.getElementById('slideCaption');
        const totalSlides = slides.length;
        let autoplay = setInterval(nextSlide, 6000);
        const hero = document.getElementById('heroCarousel');

        function restartKenBurns(img) {
            img.style.animation = 'none';
            void img.offsetWidth;
            img.style.animation = '';
        }

        function updateCarousel() {
            slides.forEach((slide, i) => {
                const active = i === currentSlide;
                slide.classList.toggle('is-active', active);
                if (active) {
                    const img = slide.querySelector('img');
                    restartKenBurns(img);
                    if (captionEl) {
                        captionEl.textContent = slide.dataset.caption || '';
                        captionEl.classList.remove('caption-chip');
                        void captionEl.offsetWidth;
                        captionEl.classList.add('caption-chip');
                    }
                }
            });
            dots.forEach((dot, i) => dot.classList.toggle('is-active', i === currentSlide));
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            updateCarousel();
        }
        function prevSlide() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            updateCarousel();
        }
        function goToSlide(index) {
            currentSlide = index;
            updateCarousel();
            resetAutoplay();
        }
        function resetAutoplay() {
            clearInterval(autoplay);
            autoplay = setInterval(nextSlide, 6000);
        }

        if (hero) {
            hero.addEventListener('mouseenter', () => clearInterval(autoplay));
            hero.addEventListener('mouseleave', resetAutoplay);
        }

        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
            document.getElementById(id).classList.add('flex');
        }
        function closeModal(id) {
            const modal = document.getElementById(id);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openPrivacyModal() { openModal('privacyModal'); }
        function openTermsModal() { openModal('termsModal'); }
    </script>
</body>
</html>
