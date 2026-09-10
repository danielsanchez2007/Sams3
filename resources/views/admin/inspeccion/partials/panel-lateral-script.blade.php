<script>
document.addEventListener('DOMContentLoaded', function () {
    var usuarios = @json($usuariosJs ?? []);
    var panelUserId = {{ (int) ($panelUserId ?? 0) }};
    var filterInput = document.getElementById('panelUserFilter');
    var results = document.getElementById('panelUserResults');
    var editable = document.getElementById('editableTemplate');

    function findUser(id) {
        return usuarios.find(function (x) { return parseInt(x.id, 10) === parseInt(id, 10); });
    }

    function setImg(el, placeholder, src) {
        if (!el || !placeholder) return;
        if (src) {
            el.src = src;
            el.classList.remove('hidden');
            placeholder.classList.add('hidden');
        } else {
            el.classList.add('hidden');
            el.removeAttribute('src');
            placeholder.classList.remove('hidden');
        }
    }

    function renderPanelUser(id) {
        var u = findUser(id);
        var nameEl = document.getElementById('panelUserName');
        var cargoEl = document.getElementById('panelUserCargo');
        var emailEl = document.getElementById('panelUserEmail');
        var phoneEl = document.getElementById('panelUserPhone');
        if (!u) {
            if (nameEl) nameEl.textContent = 'Usuario no encontrado';
            return;
        }
        if (nameEl) nameEl.textContent = u.name || 'Usuario';
        if (cargoEl) cargoEl.textContent = u.cargo || 'Sin cargo';
        if (emailEl) emailEl.textContent = u.email || '';
        if (phoneEl) phoneEl.textContent = u.phone || '';
        setImg(document.getElementById('panelUserPhoto'), document.getElementById('panelUserPhotoPlaceholder'), u.photo);
        setImg(document.getElementById('panelUserSignature'), document.getElementById('panelUserSignPlaceholder'), u.signature);
    }

    function renderResults(query) {
        if (!results) return;
        var q = (query || '').trim().toLowerCase();
        if (!q) {
            results.classList.add('hidden');
            results.innerHTML = '';
            return;
        }
        var matches = usuarios.filter(function (u) {
            return [u.name, u.email, u.cargo, u.phone].join(' ').toLowerCase().indexOf(q) !== -1;
        }).slice(0, 8);
        results.innerHTML = matches.length
            ? matches.map(function (u) {
                return '<button type="button" class="pw-insp-user-result" data-user-id="' + u.id + '">' +
                    '<span class="font-medium">' + (u.name || 'Usuario') + '</span>' +
                    '<span class="text-xs text-gray-500">' + (u.cargo || u.email || '') + '</span></button>';
            }).join('')
            : '<div class="px-3 py-2 text-xs text-gray-500">Sin coincidencias</div>';
        results.classList.remove('hidden');
    }

    if (filterInput) {
        filterInput.addEventListener('input', function () {
            renderResults(filterInput.value);
        });
    }
    if (results) {
        results.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-user-id]');
            if (!btn) return;
            renderPanelUser(btn.getAttribute('data-user-id'));
            results.classList.add('hidden');
            if (filterInput) filterInput.value = '';
        });
    }

    function insertAtEditable(text, clientX, clientY) {
        if (!editable || !text) return;
        editable.focus();
        var range = null;
        if (typeof clientX === 'number' && document.caretRangeFromPoint) {
            range = document.caretRangeFromPoint(clientX, clientY);
        } else if (typeof clientX === 'number' && document.caretPositionFromPoint) {
            var pos = document.caretPositionFromPoint(clientX, clientY);
            if (pos) {
                range = document.createRange();
                range.setStart(pos.offsetNode, pos.offset);
                range.collapse(true);
            }
        }
        var sel = window.getSelection();
        if (range && sel) {
            sel.removeAllRanges();
            sel.addRange(range);
        }
        if (document.queryCommandSupported && document.queryCommandSupported('insertText')) {
            document.execCommand('insertText', false, text);
        } else if (sel && sel.rangeCount) {
            var r = sel.getRangeAt(0);
            r.deleteContents();
            r.insertNode(document.createTextNode(text));
        } else {
            editable.appendChild(document.createTextNode(text));
        }
    }

    document.querySelectorAll('.pw-hv-chip').forEach(function (chip) {
        chip.addEventListener('dragstart', function (e) {
            var value = chip.getAttribute('data-hv-value') || '';
            e.dataTransfer.setData('text/plain', value);
            e.dataTransfer.effectAllowed = 'copy';
            chip.classList.add('pw-hv-chip--dragging');
        });
        chip.addEventListener('dragend', function () {
            chip.classList.remove('pw-hv-chip--dragging');
            if (editable) editable.classList.remove('pw-insp-drop-target');
        });
        chip.addEventListener('click', function () {
            insertAtEditable(chip.getAttribute('data-hv-value') || '');
        });
    });

    if (editable) {
        editable.addEventListener('dragover', function (e) {
            e.preventDefault();
            editable.classList.add('pw-insp-drop-target');
        });
        editable.addEventListener('dragleave', function () {
            editable.classList.remove('pw-insp-drop-target');
        });
        editable.addEventListener('drop', function (e) {
            e.preventDefault();
            editable.classList.remove('pw-insp-drop-target');
            insertAtEditable(e.dataTransfer.getData('text/plain'), e.clientX, e.clientY);
        });
    }

    renderPanelUser(panelUserId);
});
</script>
