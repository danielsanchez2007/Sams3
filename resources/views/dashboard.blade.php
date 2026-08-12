@php
    $inicioCfg = $inicioCfg ?? [];
    $logosCfg = $logosCfg ?? [];
    $logoPrimario = $logosCfg['primario'] ?? 'images/logo-principal.png';
@endphp

<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Prevention World | Plataforma Institucional</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap');

        :root {
            --primary: #003087;
            --accent: #00d4ff;
        }

        body {
            font-family: 'Inter', system_ui, sans-serif;
        }

        .hero-bg {
            background: linear-gradient(135deg, #003087 0%, #0f4a8a 100%);
            color: white;
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

        .carousel-container {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.4);
        }

        .modal {
            animation: modalPop 0.3s ease;
        }
        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50">

    <!-- HEADER -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <img src="{{ asset($logoPrimario) }}" alt="Prevention World" class="h-14 w-auto">
            </div>

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
        <section class="hero-bg pt-24 pb-20">
            <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-12 items-center">
                <div class="space-y-8">
                    <h1 class="text-5xl md:text-6xl font-bold leading-tight">
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
                        <a href="https://preventionworld.edu.co/portal/" target="_blank" class="px-8 py-4 border border-white/50 hover:bg-white/10 text-white font-semibold rounded-3xl transition-colors">
                            Conocer más
                        </a>
                    </div>
                </div>

                <!-- CARRUSEL -->
                <div class="relative">
                    <div id="carousel" class="carousel-container">
                        <div id="slides" class="flex transition-transform duration-700 ease-in-out">
                            <div class="min-w-full"><img src="https://picsum.photos/id/1015/800/520" alt="Trabajo en alturas" class="w-full h-full object-cover"></div>
                            <div class="min-w-full"><img src="https://picsum.photos/id/866/800/520" alt="Capacitación" class="w-full h-full object-cover"></div>
                            <div class="min-w-full"><img src="https://picsum.photos/id/201/800/520" alt="Certificaciones" class="w-full h-full object-cover"></div>
                            <div class="min-w-full"><img src="https://picsum.photos/id/1016/800/520" alt="Equipo de trabajo" class="w-full h-full object-cover"></div>
                        </div>
                    </div>

                    <button onclick="prevSlide()" class="absolute left-4 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white p-3 rounded-full transition-colors">
                        <i data-lucide="chevron-left" class="w-6 h-6"></i>
                    </button>
                    <button onclick="nextSlide()" class="absolute right-4 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white p-3 rounded-full transition-colors">
                        <i data-lucide="chevron-right" class="w-6 h-6"></i>
                    </button>

                    <div class="flex justify-center gap-3 mt-6">
                        <button onclick="goToSlide(0)" class="carousel-dot w-3 h-3 rounded-full bg-white/70 hover:bg-white"></button>
                        <button onclick="goToSlide(1)" class="carousel-dot w-3 h-3 rounded-full bg-white/70 hover:bg-white"></button>
                        <button onclick="goToSlide(2)" class="carousel-dot w-3 h-3 rounded-full bg-white/70 hover:bg-white"></button>
                        <button onclick="goToSlide(3)" class="carousel-dot w-3 h-3 rounded-full bg-white/70 hover:bg-white"></button>
                    </div>
                </div>
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
                    <a href="https://preventionworld.edu.co/portal/quienes-somos/" target="_blank" class="mt-6 inline-flex items-center gap-2 text-[#003087] font-medium hover:gap-3 transition-all">
                        Ver más →
                    </a>
                </div>

                <div class="glass rounded-3xl p-8 card-hover">
                    <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="graduation-cap" class="w-9 h-9 text-[#003087]"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3 text-slate-800">Campus Virtual</h3>
                    <p class="text-slate-600">Aprendizaje flexible y de alta calidad en prevención de riesgos laborales.</p>
                    <a href="https://preventionworld.edu.co/portal/" target="_blank" class="mt-6 inline-flex items-center gap-2 text-[#003087] font-medium hover:gap-3 transition-all">
                        Ingresar →
                    </a>
                </div>

                <div class="glass rounded-3xl p-8 card-hover">
                    <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="headphones" class="w-9 h-9 text-[#003087]"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3 text-slate-800">Servicio al Cliente</h3>
                    <p class="text-slate-600">Comunícate directamente con nuestras gestoras.</p>
                    <a href="#" onclick="openCustomerServiceModal()" class="mt-6 inline-flex items-center gap-2 text-[#003087] font-medium hover:gap-3 transition-all">
                        Contactar →
                    </a>
                </div>

                <div class="glass rounded-3xl p-8 card-hover">
                    <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="briefcase" class="w-9 h-9 text-[#003087]"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3 text-slate-800">Trabaje con Nosotros</h3>
                    <p class="text-slate-600">Únete a nuestro equipo y forma parte de una cultura de prevención.</p>
                    <a href="#" class="mt-6 inline-flex items-center gap-2 text-[#003087] font-medium hover:gap-3 transition-all">
                        Enviar hoja de vida →
                    </a>
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
                            <img src="{{ asset($logoPrimario) }}" alt="Prevention World" class="h-12 w-auto">
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
                            <a href="https://preventionworld.edu.co/portal/quienes-somos/" target="_blank" class="hover:text-cyan-400 transition-colors">Quiénes Somos</a>
                            <a href="https://preventionworld.edu.co/portal/" target="_blank" class="hover:text-cyan-400 transition-colors">Campus Virtual</a>
                            <a href="#" onclick="openCustomerServiceModal()" class="hover:text-cyan-400 transition-colors">Servicio al Cliente</a>
                            <a href="#" onclick="openSupportModal()" class="hover:text-cyan-400 transition-colors">Soporte Técnico</a>
                            <a href="#" class="hover:text-cyan-400 transition-colors">Certificaciones</a>
                            <a href="#" class="hover:text-cyan-400 transition-colors">Trabaje con Nosotros</a>
                        </div>
                    </div>

                    <!-- Derecha -->
                    <div>
                        <h4 class="text-white font-semibold text-lg mb-6">Contáctanos</h4>
                        <div class="space-y-4 text-sm">
                            <div class="flex items-start gap-3">
                                <i data-lucide="phone" class="w-5 h-5 mt-0.5 text-cyan-400"></i>
                                <div><p>+57 (8) 123 4567</p></div>
                            </div>
                            <div class="flex items-start gap-3">
                                <i data-lucide="mail" class="w-5 h-5 mt-0.5 text-cyan-400"></i>
                                <div><p>info@preventionworld.edu.co</p></div>
                            </div>
                        </div>

                        <div class="mt-10">
                            <h5 class="text-white font-medium mb-4">Síguenos en:</h5>
                            <div class="flex gap-4">
                                <a href="#" class="w-10 h-10 bg-slate-800 hover:bg-[#1877F2] flex items-center justify-center rounded-2xl"><i data-lucide="facebook"></i></a>
                                <a href="#" class="w-10 h-10 bg-slate-800 hover:bg-[#E4405F] flex items-center justify-center rounded-2xl"><i data-lucide="instagram"></i></a>
                                <a href="#" class="w-10 h-10 bg-slate-800 hover:bg-[#0A66C2] flex items-center justify-center rounded-2xl"><i data-lucide="linkedin"></i></a>
                                <a href="#" class="w-10 h-10 bg-slate-800 hover:bg-[#FF0000] flex items-center justify-center rounded-2xl"><i data-lucide="youtube"></i></a>
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
                        <a href="#" onclick="openPrivacyModal()" class="hover:text-slate-300 transition-colors">Política de Privacidad</a>
                        <a href="#" onclick="openTermsModal()" class="hover:text-slate-300 transition-colors">Términos de Uso</a>
                    </div>
                </div>
            </div>
        </footer>
    </main>

    <!-- ====================== MODALES ====================== -->
    <!-- Modal Servicio al Cliente -->
    <div id="customerModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-[100]">
        <div class="modal bg-white text-slate-800 rounded-3xl p-8 max-w-md w-full mx-4">
            <h3 class="text-2xl font-bold mb-6 text-center">Servicio al Cliente</h3>
            <div class="space-y-4">
                <a href="https://wa.me/573176389915" target="_blank" class="flex items-center gap-4 p-4 hover:bg-slate-100 rounded-2xl">
                    <i data-lucide="phone" class="w-6 h-6 text-green-500"></i>
                    <div><p class="font-medium">Gestora 1 - 317 638 9915</p></div>
                </a>
                <a href="https://wa.me/573160264718" target="_blank" class="flex items-center gap-4 p-4 hover:bg-slate-100 rounded-2xl">
                    <i data-lucide="phone" class="w-6 h-6 text-green-500"></i>
                    <div><p class="font-medium">Gestora 2 - 316 026 4718</p></div>
                </a>
                <a href="https://wa.me/573176389965" target="_blank" class="flex items-center gap-4 p-4 hover:bg-slate-100 rounded-2xl">
                    <i data-lucide="phone" class="w-6 h-6 text-green-500"></i>
                    <div><p class="font-medium">Gestora 3 - 317 638 9965</p></div>
                </a>
            </div>
            <button onclick="closeModal('customerModal')" class="mt-8 w-full py-4 bg-slate-200 hover:bg-slate-300 rounded-2xl">Cerrar</button>
        </div>
    </div>

    <!-- Modal Soporte Técnico -->
    <div id="supportModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-[100]">
        <div class="modal bg-white text-slate-800 rounded-3xl p-8 max-w-md w-full mx-4">
            <h3 class="text-2xl font-bold mb-6 text-center">Soporte Técnico</h3>
            <a href="https://wa.me/573115317118" target="_blank" class="block p-6 bg-green-50 hover:bg-green-100 rounded-3xl text-center mb-4">
                <i data-lucide="message-circle" class="w-10 h-10 mx-auto text-green-600 mb-3"></i>
                <p class="font-semibold">WhatsApp Soporte</p>
                <p class="text-green-600">311 531 7118</p>
            </a>
            <a href="mailto:desarrollo@preventionworld.edu.co" class="block p-6 bg-blue-50 hover:bg-blue-100 rounded-3xl text-center">
                <i data-lucide="mail" class="w-10 h-10 mx-auto text-blue-600 mb-3"></i>
                <p class="font-semibold">Correo Electrónico</p>
                <p class="text-blue-600">desarrollo@preventionworld.edu.co</p>
            </a>
            <button onclick="closeModal('supportModal')" class="mt-8 w-full py-4 bg-slate-200 hover:bg-slate-300 rounded-2xl">Cerrar</button>
        </div>
    </div>

    <!-- Modal Política de Privacidad -->
    <div id="privacyModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-[100]">
        <div class="modal bg-white text-slate-800 rounded-3xl p-8 max-w-lg w-full mx-4 max-h-[90vh] overflow-auto">
            <h3 class="text-2xl font-bold mb-6">Política de Privacidad</h3>
            <div class="prose text-sm">
                <p>En Prevention World respetamos tu privacidad...</p>
                <!-- Puedes agregar más texto aquí -->
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
                <!-- Puedes agregar más texto aquí -->
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

        // Carrusel
        let currentSlide = 0;
        const slidesContainer = document.getElementById('slides');
        const totalSlides = 4;

        function updateCarousel() {
            slidesContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
        }
        function nextSlide() { currentSlide = (currentSlide + 1) % totalSlides; updateCarousel(); }
        function prevSlide() { currentSlide = (currentSlide - 1 + totalSlides) % totalSlides; updateCarousel(); }
        function goToSlide(index) { currentSlide = index; updateCarousel(); }
        setInterval(() => { nextSlide(); }, 5000);

        // Modales
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
            document.getElementById(id).classList.add('flex');
        }
        function closeModal(id) {
            const modal = document.getElementById(id);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openCustomerServiceModal() { openModal('customerModal'); }
        function openSupportModal() { openModal('supportModal'); }
        function openPrivacyModal() { openModal('privacyModal'); }
        function openTermsModal() { openModal('termsModal'); }

        // Cerrar modal al clic fuera
        document.querySelectorAll('.fixed.inset-0').forEach(modal => {
            modal.addEventListener('click', e => {
                if (e.target === modal) closeModal(modal.id);
            });
        });
    </script>
</body>
</html>