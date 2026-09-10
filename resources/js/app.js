import './bootstrap';
import { createIcons, icons } from 'lucide';

function renderLucideIcons(options = {}) {
    return createIcons({ icons, ...options });
}

window.lucide = {
    createIcons: renderLucideIcons,
    icons,
};

function evaluateNewPassword(value) {
    const checks = {
        length: value.length >= 10,
        lower: /\p{Ll}/u.test(value),
        upper: /\p{Lu}/u.test(value),
        number: /\d/.test(value),
    };
    const passed = Object.values(checks).filter(Boolean).length;
    if (!value) {
        return { level: 'idle', label: 'Escribe una contraseña', pct: 0, checks };
    }
    if (passed < 4) {
        return {
            level: 'fail',
            label: 'No cumple',
            pct: Math.max(14, Math.round((passed / 4) * 42)),
            checks,
        };
    }
    const extra = (value.length >= 12 ? 1 : 0)
        + (/[^A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ]/.test(value) ? 1 : 0)
        + (value.length >= 14 ? 1 : 0);
    if (extra >= 2) {
        return { level: 'ok', label: 'Cumple', pct: 100, checks };
    }
    if (extra === 1) {
        return { level: 'good', label: 'Buena', pct: 78, checks };
    }
    return { level: 'weak', label: 'Débil', pct: 58, checks };
}

function confirmationInputFor(passwordInput) {
    const form = passwordInput.form;
    if (!form) return null;
    if (passwordInput.name === 'password') {
        return form.querySelector('[name="password_confirmation"]');
    }
    return null;
}

function buildPasswordMeter() {
    const wrap = document.createElement('div');
    wrap.className = 'pw-meter is-idle';
    wrap.setAttribute('data-pw-meter-ui', '1');
    wrap.innerHTML = [
        '<div class="pw-meter-head"><span class="pw-meter-label">Escribe una contraseña</span></div>',
        '<div class="pw-meter-track" aria-hidden="true"><div class="pw-meter-bar"></div></div>',
        '<ul class="pw-meter-checks">',
        '<li data-check="length">10 caracteres</li>',
        '<li data-check="upper">Mayúscula</li>',
        '<li data-check="lower">Minúscula</li>',
        '<li data-check="number">Número</li>',
        '</ul>',
        '<p class="pw-meter-match" hidden></p>',
    ].join('');
    return wrap;
}

function renderPasswordMeter(input) {
    const meter = input._pwMeter;
    if (!meter) return;
    const optional = input.getAttribute('data-pw-meter') === 'optional';
    const value = input.value || '';
    const result = evaluateNewPassword(value);
    const label = meter.querySelector('.pw-meter-label');
    const bar = meter.querySelector('.pw-meter-bar');
    const matchEl = meter.querySelector('.pw-meter-match');

    meter.classList.remove('is-idle', 'is-fail', 'is-weak', 'is-good', 'is-ok');
    if (!value && optional) {
        meter.classList.add('is-idle');
        if (label) label.textContent = 'Opcional. Si la cambias, debe cumplir las reglas.';
        if (bar) bar.style.width = '0%';
        meter.querySelectorAll('[data-check]').forEach((item) => item.classList.remove('is-on'));
        if (matchEl) {
            matchEl.hidden = true;
            matchEl.textContent = '';
        }
        return;
    }

    meter.classList.add('is-' + result.level);
    if (label) label.textContent = result.label;
    if (bar) bar.style.width = result.pct + '%';
    meter.querySelectorAll('[data-check]').forEach((item) => {
        const key = item.getAttribute('data-check');
        item.classList.toggle('is-on', Boolean(result.checks[key]));
    });

    const confirm = confirmationInputFor(input);
    if (!matchEl) return;
    if (!confirm || !value || !confirm.value) {
        matchEl.hidden = true;
        matchEl.textContent = '';
        matchEl.classList.remove('is-bad', 'is-good');
        return;
    }
    const same = confirm.value === value;
    matchEl.hidden = false;
    matchEl.classList.toggle('is-bad', !same);
    matchEl.classList.toggle('is-good', same);
    matchEl.textContent = same ? 'Las contraseñas coinciden.' : 'Las contraseñas no coinciden.';
}

function bindPasswordMeter(input) {
    if (!input || input.dataset.pwMeterBound === '1') return;
    input.dataset.pwMeterBound = '1';
    const meter = buildPasswordMeter();
    input._pwMeter = meter;
    const hint = input.parentElement ? input.parentElement.querySelector('.pw-hint') : null;
    if (hint) {
        hint.insertAdjacentElement('afterend', meter);
    } else {
        input.insertAdjacentElement('afterend', meter);
    }
    const update = () => renderPasswordMeter(input);
    input.addEventListener('input', update);
    input.addEventListener('change', update);
    const confirm = confirmationInputFor(input);
    if (confirm) {
        confirm.addEventListener('input', update);
        confirm.addEventListener('change', update);
    }
    update();
}

function initPasswordMeters(root) {
    const scope = root && root.querySelectorAll ? root : document;
    scope.querySelectorAll('input[data-pw-meter]').forEach(bindPasswordMeter);
}

window.initPasswordMeters = initPasswordMeters;
window.refreshPasswordMeters = function (root) {
    const scope = root && root.querySelectorAll ? root : document;
    scope.querySelectorAll('input[data-pw-meter]').forEach((input) => {
        if (input._pwMeter) renderPasswordMeter(input);
        else bindPasswordMeter(input);
    });
};

const bootIcons = () => renderLucideIcons();
const bootUi = () => {
    bootIcons();
    initPasswordMeters(document);
};
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootUi);
} else {
    bootUi();
}
