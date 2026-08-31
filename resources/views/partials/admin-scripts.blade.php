<!-- Admin Scripts -->
<script>
    (function () {
        var APP_BASE = @json(rtrim(request()->getBasePath(), '/'));
        window.appUrl = function (path) {
            var p = String(path || '');
            return APP_BASE + (p.charAt(0) === '/' ? p : '/' + p);
        };
    })();

    window.pwEscapeHtml = function (value) {
        return String(value == null ? '' : value).replace(/[&<>"'`]/g, function (ch) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '`': '&#96;' })[ch];
        });
    };

    // --- Notificaciones (éxito, error, aviso) ---
    function showNotification(message, type) {
        type = type || 'success';
        var container = document.getElementById('notificationsContainer');
        if (!container) return;
        var id = 'pw-toast-' + Date.now();
        var bg = type === 'error' ? 'bg-red-50 border-red-200 text-red-800' : (type === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-green-50 border-green-200 text-green-800');
        var icon = type === 'error' ? 'alert-circle' : (type === 'warning' ? 'alert-triangle' : 'check-circle');
        var div = document.createElement('div');
        div.id = id;
        div.className = 'pw-toast pointer-events-auto flex items-start gap-3 p-4 rounded-xl border shadow-lg text-sm ' + bg;
        div.setAttribute('role', 'alert');
        div.innerHTML = '<i data-lucide="' + icon + '" class="w-5 h-5 shrink-0 mt-0.5"></i><span class="flex-1 pw-toast-message"></span><button type="button" class="pw-toast-close shrink-0 p-1 rounded hover:opacity-70" aria-label="Cerrar">×</button>';
        var msgNode = div.querySelector('.pw-toast-message');
        if (msgNode) msgNode.textContent = (message || '');
        container.appendChild(div);
        if (typeof lucide !== 'undefined') lucide.createIcons && lucide.createIcons();
        var close = function() {
            div.style.opacity = '0';
            div.style.transform = 'translateX(100%)';
            setTimeout(function() { if (div.parentNode) div.parentNode.removeChild(div); }, 300);
        };
        div.querySelector('.pw-toast-close').addEventListener('click', close);
        setTimeout(close, 6000);
    }

    // --- Modal de confirmación (¿Seguro que deseas...? Aceptar / Cancelar) ---
    function showConfirmModal(options) {
        options = options || {};
        var modal = document.getElementById('pwConfirmModal');
        var titleEl = document.getElementById('pwConfirmTitle');
        var messageEl = document.getElementById('pwConfirmMessage');
        var okBtn = document.getElementById('pwConfirmOk');
        var cancelBtn = document.getElementById('pwConfirmCancel');
        var backdrop = document.getElementById('pwConfirmBackdrop');
        if (!modal || !okBtn || !cancelBtn) return;
        var title = options.title || 'Confirmar acción';
        var message = options.message || '¿Estás seguro de que deseas continuar?';
        var confirmText = options.confirmText || 'Aceptar';
        var cancelText = options.cancelText || 'Cancelar';
        var danger = options.danger === true;
        var onConfirm = options.onConfirm || function() {};
        titleEl.textContent = title;
        messageEl.textContent = message;
        okBtn.textContent = confirmText;
        cancelBtn.textContent = cancelText;
        okBtn.className = 'px-4 py-2.5 rounded-xl font-medium text-sm transition ' + (danger ? 'pw-btn-danger' : 'pw-btn-primary');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        var close = function() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        };
        var handleConfirm = function() {
            close();
            onConfirm();
        };
        okBtn.onclick = handleConfirm;
        cancelBtn.onclick = close;
        backdrop.onclick = close;
        modal.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') close();
        });
    }

    // Formularios con data-confirm: al enviar se muestra el modal; si acepta se envía el form
    function initConfirmForms() {
        document.querySelectorAll('form[data-confirm]').forEach(function(form) {
            if (form.dataset.confirmBound === '1') return;
            form.dataset.confirmBound = '1';
            form.addEventListener('submit', function(e) {
                var msg = form.getAttribute('data-confirm');
                if (!msg) return;
                e.preventDefault();
                showConfirmModal({
                    title: 'Confirmar',
                    message: msg,
                    confirmText: 'Aceptar',
                    cancelText: 'Cancelar',
                    danger: (form.getAttribute('data-confirm-danger') === '1' || form.getAttribute('data-confirm-danger') === 'true'),
                    onConfirm: function() {
                        form.removeAttribute('data-confirm');
                        form.removeAttribute('data-confirm-danger');
                        form.submit();
                    }
                });
            });
        });
    }

    // Initialize Lucide icons
    document.addEventListener('DOMContentLoaded', function() {
        try {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        } catch (error) {
            console.error('Error initializing icons:', error);
        }

        if (window.pwFlash) {
            if (window.pwFlash.success) showNotification(window.pwFlash.success, 'success');
            if (window.pwFlash.error) showNotification(window.pwFlash.error, 'error');
            if (window.pwFlash.warning) showNotification(window.pwFlash.warning, 'warning');
            window.pwFlash = null;
        }

        try { initConfirmForms(); } catch (err) { console.error('initConfirmForms', err); }
        try {
            initAutoSubmitFilters();
        } catch (error) {
            console.error('Error initializing auto submit filters:', error);
        }

        try {
            initResponsiveSidebar();
        } catch (error) {
            console.error('Error initializing responsive sidebar:', error);
        }

        try {
            initMobileBackButton();
        } catch (error) {
            console.error('Error initializing mobile back button:', error);
        }

        try {
            initTopMenuDropdowns();
        } catch (error) {
            console.error('Error initializing top menu dropdowns:', error);
        }
    });

    function initResponsiveSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        const closeBtn = document.getElementById('sidebarClose');

        if (!sidebar || !overlay || !toggleBtn) return;

        const isMobile = () => window.matchMedia('(max-width: 767px)').matches;
        const storageKey = 'sams.sidebar.desktopCollapsed';

        const applyDesktopState = () => {
            if (isMobile()) return;
            const collapsed = localStorage.getItem(storageKey) === '1';
            if (collapsed) {
                sidebar.classList.add('md:w-0', 'md:overflow-hidden', 'md:border-r-0');
            } else {
                sidebar.classList.remove('md:w-0', 'md:overflow-hidden', 'md:border-r-0');
            }
        };

        const openSidebar = () => {
            if (!isMobile()) return;
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            toggleBtn.setAttribute('aria-expanded', 'true');
            document.body.classList.add('overflow-hidden');
        };

        const closeSidebar = () => {
            if (!isMobile()) return;
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            toggleBtn.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('overflow-hidden');
        };

        const toggleDesktop = () => {
            if (isMobile()) return;
            const collapsedNow = localStorage.getItem(storageKey) === '1';
            localStorage.setItem(storageKey, collapsedNow ? '0' : '1');
            applyDesktopState();
        };

        toggleBtn.addEventListener('click', () => {
            if (isMobile()) {
                const expanded = toggleBtn.getAttribute('aria-expanded') === 'true';
                expanded ? closeSidebar() : openSidebar();
                return;
            }
            toggleDesktop();
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', closeSidebar);
        }

        overlay.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeSidebar();
        });

        window.addEventListener('resize', () => {
            if (!isMobile()) {
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                toggleBtn.setAttribute('aria-expanded', 'false');
                sidebar.classList.remove('-translate-x-full');
                applyDesktopState();
            } else {
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('md:w-0', 'md:overflow-hidden', 'md:border-r-0');
            }
        });

        sidebar.querySelectorAll('a').forEach(a => {
            a.addEventListener('click', () => {
                closeSidebar();
            });
        });

        applyDesktopState();

        window.addEventListener('pageshow', function () {
            try {
                if (!overlay || !sidebar || !toggleBtn) return;
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                toggleBtn.setAttribute('aria-expanded', 'false');
                if (isMobile()) {
                    sidebar.classList.add('-translate-x-full');
                }
            } catch (e) {}
        });
    }

    function initTopMenuDropdowns() {
        const topMenu = document.getElementById('topMenu');
        if (!topMenu) return;

        topMenu.querySelectorAll('[data-dropdown]').forEach(function (dropdown) {
            const btn = dropdown.querySelector('button');
            const panel = dropdown.querySelector('.top-dropdown-panel');
            if (!btn || !panel) return;

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                e.preventDefault();
                const isOpen = dropdown.classList.contains('dropdown-open');
                topMenu.querySelectorAll('[data-dropdown]').forEach(function (d) {
                    d.classList.remove('dropdown-open');
                    const p = d.querySelector('.top-dropdown-panel');
                    const b = d.querySelector('button');
                    if (p) { p.classList.add('hidden'); p.classList.remove('block'); }
                    if (b) b.setAttribute('aria-expanded', 'false');
                });
                if (!isOpen) {
                    dropdown.classList.add('dropdown-open');
                    panel.classList.remove('hidden');
                    panel.classList.add('block');
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        });

        document.addEventListener('click', function () {
            topMenu.querySelectorAll('[data-dropdown]').forEach(function (d) {
                d.classList.remove('dropdown-open');
                const p = d.querySelector('.top-dropdown-panel');
                const b = d.querySelector('button');
                if (p) { p.classList.add('hidden'); p.classList.remove('block'); }
                if (b) b.setAttribute('aria-expanded', 'false');
            });
        });
    }

    function initMobileBackButton() {
        const btn = document.getElementById('mobileBackBtn');
        if (!btn) return;

        const dashboardUrl = "{{ route('admin.dashboard') }}";
        const authPathPattern = /\/(login|logout|register)(\/|$|\?)/i;
        const currentUrl = window.location.href;
        const currentKey = 'sams.mobile.currentUrl';
        const prevKey = 'sams.mobile.prevUrl';

        function isUsableInternalUrl(url) {
            if (!url || typeof url !== 'string') return false;
            try {
                const parsed = new URL(url, window.location.origin);
                if (parsed.origin !== window.location.origin) return false;
                if (authPathPattern.test(parsed.pathname)) return false;
                if (parsed.href === currentUrl) return false;
                return true;
            } catch (e) {
                return false;
            }
        }

        try {
            const lastCurrent = sessionStorage.getItem(currentKey);
            if (isUsableInternalUrl(lastCurrent)) {
                sessionStorage.setItem(prevKey, lastCurrent);
            }
            sessionStorage.setItem(currentKey, currentUrl);
        } catch (e) {}

        function goSafeBack() {
            try {
                const ref = document.referrer;
                if (isUsableInternalUrl(ref) && window.history.length > 1) {
                    const before = window.location.href;
                    history.back();
                    setTimeout(function () {
                        if (window.location.href === before) {
                            const prev = sessionStorage.getItem(prevKey);
                            if (isUsableInternalUrl(prev)) {
                                window.location.href = prev;
                            } else {
                                window.location.href = dashboardUrl;
                            }
                        }
                    }, 450);
                    return;
                }
            } catch (e) {}

            try {
                const prev = sessionStorage.getItem(prevKey);
                if (isUsableInternalUrl(prev)) {
                    window.location.href = prev;
                    return;
                }
            } catch (e) {}

            window.location.href = dashboardUrl;
        }

        btn.addEventListener('click', goSafeBack);
    }

    function initAutoSubmitFilters(root = document) {
        const forms = root.querySelectorAll('form[data-auto-submit="1"]');
        forms.forEach(form => {
            if (form.dataset.autoSubmitBound === '1') return;
            form.dataset.autoSubmitBound = '1';

            const debounceMs = parseInt(form.getAttribute('data-auto-submit-debounce') || '500', 10);
            const minLen = parseInt(form.getAttribute('data-auto-submit-minlen') || '2', 10);
            let t = null;

            const shouldSubmitText = (el) => {
                const v = (el.value || '').trim();
                if (v.length === 0) return true;
                if (isNaN(minLen) || minLen <= 0) return true;
                return v.length >= minLen;
            };

            const scheduleSubmit = () => {
                if (t) clearTimeout(t);
                t = setTimeout(() => {
                    try {
                        form.requestSubmit ? form.requestSubmit() : form.submit();
                    } catch (e) {
                        try { form.submit(); } catch (e2) {}
                    }
                }, isNaN(debounceMs) ? 500 : debounceMs);
            };

            form.querySelectorAll('input, select').forEach(el => {
                if (el.hasAttribute('data-no-auto-submit')) return;
                const tag = (el.tagName || '').toLowerCase();
                const type = (el.getAttribute('type') || '').toLowerCase();

                if (tag === 'select' || type === 'checkbox' || type === 'radio') {
                    el.addEventListener('change', scheduleSubmit);
                } else {
                    el.addEventListener('input', function() {
                        if (!shouldSubmitText(el)) return;
                        scheduleSubmit();
                    });
                    el.addEventListener('change', scheduleSubmit);
                    el.addEventListener('blur', function() {
                        if (!shouldSubmitText(el)) return;
                        scheduleSubmit();
                    });
                    el.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            if (!shouldSubmitText(el)) return;
                            e.preventDefault();
                            scheduleSubmit();
                        }
                    });
                }
            });
        });
    }

    // Alias: mensaje simple (usa notificación en lugar de alert)
    function showMessage(message) {
        showNotification(message, 'success');
    }

    // Toggle submenu
    function toggleSubmenu(menu) {
        try {
            const submenu = document.getElementById(menu + '-submenu');
            const arrow = document.getElementById(menu + '-arrow');
            
            if (submenu && arrow) {
                submenu.classList.toggle('hidden');
                if (submenu.classList.contains('hidden')) {
                    arrow.style.transform = 'rotate(0deg)';
                } else {
                    arrow.style.transform = 'rotate(180deg)';
                }
            }
        } catch (error) {
            console.error('Error toggling submenu:', error);
        }
    }

    // Profile modal functions
    function showProfileModal() {
        try {
            alert('Mi Perfil - Función en desarrollo');
        } catch (error) {
            console.error('Error showing profile modal:', error);
        }
    }

    // Reinitialize icons when content changes
    function reinitializeIcons() {
        try {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        } catch (error) {
            console.error('Error reinitializing icons:', error);
        }
    }
</script>
