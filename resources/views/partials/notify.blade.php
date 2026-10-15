{{--
    Shared notifications + request guard for every Fleet page.

    Include once, right after <body>:   @include('partials.notify')

    Public API (window.StToast):
        StToast.success('Saved.')
        StToast.error('Could not save.', { reference: '20261015-AB12CD34' })
        StToast.warning(...) / StToast.info(...)
        StToast.show({ type, title, message, reference, duration, sticky, id, action: { label, onClick } })
        StToast.fromResponse(json, 'Fallback text')   // json is the body of a failed fetch() (ApiErrors shape)
        StToast.dismiss(id)

    Request guard (same-origin fetch() only — Firebase, maps and SignalR are never touched):
      • sends Accept: application/json + X-Requested-With, and the CSRF header on writes, if the page forgot
        (without Accept, Laravel answers errors with an HTML page and res.json() throws "Unexpected token <")
      • 401 / session expired  → one "sign in again" notice, then redirect to /login?expired=1
      • 419 page expired       → one "reload" notice
      • 429 too many requests  → one "slow down" notice with the wait time
      • offline / back online  → a persistent banner that clears itself
    Pages still show their own messages for ordinary failures; this only adds the global conditions.

    Self-contained on purpose (own CSS, no Tailwind build dependency) so it can never be purged.
--}}
<style>
    #st-toasts {
        position: fixed;
        z-index: 2147483000;
        top: max(16px, env(safe-area-inset-top));
        right: 16px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        width: min(420px, calc(100vw - 32px));
        pointer-events: none;
    }

    @media (max-width: 640px) {
        #st-toasts {
            top: auto;
            bottom: calc(96px + env(safe-area-inset-bottom));
            right: 16px;
            left: 16px;
            width: auto;
        }
    }

    .st-toast {
        --tone: #0369a1;
        --tone-bg: #e0f2fe;
        --tone-fg: #0c4a6e;
        position: relative;
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 12px 12px 12px 14px;
        background: #fff;
        color: #1f2937;
        border: 1px solid #e5e7eb;
        border-left: 4px solid var(--tone);
        border-radius: 14px;
        box-shadow: 0 10px 30px -8px rgba(2, 31, 74, .35), 0 2px 6px rgba(2, 31, 74, .12);
        pointer-events: auto;
        overflow: hidden;
        font: 500 13.5px/1.45 Figtree, system-ui, -apple-system, 'Segoe UI', sans-serif;
        opacity: 0;
        transform: translateY(-8px) scale(.98);
        transition: opacity .18s ease, transform .18s ease;
    }

    .st-toast.in {
        opacity: 1;
        transform: none;
    }

    .st-toast.out {
        opacity: 0;
        transform: translateX(16px);
    }

    .st-toast.success {
        --tone: #16a34a;
        --tone-bg: #dcfce7;
        --tone-fg: #14532d;
    }

    .st-toast.error {
        --tone: #dc2626;
        --tone-bg: #fee2e2;
        --tone-fg: #7f1d1d;
    }

    .st-toast.warning {
        --tone: #d97706;
        --tone-bg: #fef3c7;
        --tone-fg: #78350f;
    }

    .st-toast .ic {
        flex: none;
        width: 28px;
        height: 28px;
        border-radius: 999px;
        display: grid;
        place-items: center;
        background: var(--tone-bg);
        color: var(--tone);
        margin-top: 1px;
    }

    .st-toast .ic svg {
        width: 16px;
        height: 16px;
    }

    .st-toast .bd {
        flex: 1;
        min-width: 0;
    }

    .st-toast .ti {
        font-weight: 700;
        color: #111827;
        margin: 0 0 1px;
    }

    .st-toast .ms {
        margin: 0;
        color: #374151;
        overflow-wrap: anywhere;
    }

    .st-toast .ref {
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        font-size: 12px;
        color: #6b7280;
    }

    .st-toast .ref code {
        font: 600 11.5px ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        background: #f3f4f6;
        color: #374151;
        padding: 2px 6px;
        border-radius: 6px;
        user-select: all;
    }

    .st-toast .btns {
        display: flex;
        gap: 8px;
        margin-top: 10px;
        flex-wrap: wrap;
    }

    .st-toast button {
        font: inherit;
        cursor: pointer;
    }

    .st-toast .btn {
        border: 1px solid var(--tone);
        background: var(--tone);
        color: #fff;
        padding: 5px 12px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 12.5px;
    }

    .st-toast .btn.ghost {
        background: transparent;
        color: var(--tone-fg);
        border-color: #d1d5db;
    }

    .st-toast .cp {
        border: 0;
        background: transparent;
        color: var(--tone-fg);
        font-weight: 600;
        font-size: 12px;
        padding: 2px 4px;
        text-decoration: underline;
    }

    .st-toast .x {
        flex: none;
        border: 0;
        background: transparent;
        color: #9ca3af;
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        margin: -2px -2px 0 0;
    }

    .st-toast .x:hover {
        background: #f3f4f6;
        color: #374151;
    }

    .st-toast button:focus-visible {
        outline: 2px solid var(--tone);
        outline-offset: 2px;
    }

    .st-toast .bar {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 3px;
        background: var(--tone);
        opacity: .35;
        transform-origin: left;
        animation: st-bar linear forwards;
    }

    .st-toast:hover .bar {
        animation-play-state: paused;
    }

    @keyframes st-bar {
        from {
            transform: scaleX(1)
        }

        to {
            transform: scaleX(0)
        }
    }

    /* ── Confirm dialog ─────────────────────────────────────────────────────── */
    .st-confirm-wrap {
        position: fixed;
        inset: 0;
        z-index: 2147483001;
        display: grid;
        place-items: center;
        padding: 16px;
        background: rgba(2, 15, 37, .55);
        backdrop-filter: blur(3px);
        opacity: 0;
        transition: opacity .15s ease;
    }

    .st-confirm-wrap.in {
        opacity: 1;
    }

    .st-confirm {
        width: min(420px, 100%);
        background: #fff;
        color: #1f2937;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 24px 60px -12px rgba(2, 31, 74, .5);
        font: 500 14px/1.5 Figtree, system-ui, -apple-system, 'Segoe UI', sans-serif;
        transform: translateY(8px) scale(.98);
        transition: transform .15s ease;
    }

    .st-confirm-wrap.in .st-confirm {
        transform: none;
    }

    .st-confirm h2 {
        margin: 0 0 6px;
        font-size: 17px;
        font-weight: 800;
        color: #021F4A;
    }

    .st-confirm p {
        margin: 0;
        color: #4b5563;
        overflow-wrap: anywhere;
    }

    .st-confirm .row {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .st-confirm button {
        font-family: inherit;
        font-size: 13.5px;
        font-weight: 700;
        line-height: 1;
        padding: 10px 16px;
        border-radius: 10px;
        cursor: pointer;
        border: 1px solid #d1d5db;
        background: #fff;
        color: #374151;
    }

    .st-confirm button.ok {
        background: #021F4A;
        border-color: #021F4A;
        color: #fff;
    }

    .st-confirm button.ok.danger {
        background: #dc2626;
        border-color: #dc2626;
    }

    .st-confirm button:focus-visible {
        outline: 3px solid rgba(250, 105, 8, .55);
        outline-offset: 2px;
    }

    @media (prefers-reduced-motion: reduce) {

        .st-confirm-wrap,
        .st-confirm {
            transition: none;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .st-toast {
            transition: none;
        }

        .st-toast .bar {
            display: none;
        }
    }
</style>

<div id="st-toasts" role="region" aria-label="Notifications"></div>

<script>
    (function () {
        'use strict';
        if (window.StToast) return;

        var DEBUG = @json((bool) config('app.error_details'));
        var MAX_VISIBLE = 4;
        var region = document.getElementById('st-toasts');
        var live = {};      // id -> element
        var recent = {};    // dedupe key -> timestamp

        var ICONS = {
            success: '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>',
            error: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
            warning: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>',
            info: '<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>'
        };
        var TITLES = { success: 'Done', error: 'Something went wrong', warning: 'Heads up', info: 'Note' };

        function svg(path, cls) {
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"' + (cls ? ' class="' + cls + '"' : '') + '>' + path + '</svg>';
        }

        function el(tag, cls, text) {
            var n = document.createElement(tag);
            if (cls) n.className = cls;
            if (text != null) n.textContent = text;   // textContent only: messages may echo server/user text
            return n;
        }

        function dismiss(id) {
            var n = live[id];
            if (!n) return;
            delete live[id];
            n.classList.remove('in');
            n.classList.add('out');
            setTimeout(function () { if (n.parentNode) n.parentNode.removeChild(n); }, 220);
        }

        function copy(text, btn) {
            var done = function () { btn.textContent = 'Copied'; setTimeout(function () { btn.textContent = 'Copy'; }, 1600); };
            try {
                if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(text).then(done, done); return; }
            } catch (e) { /* fall through */ }
            try {
                var ta = el('textarea'); ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
                document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); done();
            } catch (e) { /* nothing more we can do */ }
        }

        function show(opts) {
            opts = opts || {};
            var type = ICONS[opts.type] ? opts.type : 'info';
            var message = opts.message == null ? '' : String(opts.message);
            var title = opts.title === false ? '' : (opts.title || (type === 'success' ? '' : TITLES[type]));
            var id = opts.id || (type + ':' + message);

            // Same notice twice in a row (polling, double-click, retries) is shown once.
            var now = Date.now();
            if (!opts.id && recent[id] && now - recent[id] < 3000) return id;
            recent[id] = now;
            if (live[id]) dismiss(id);

            var sticky = !!opts.sticky;
            var duration = sticky ? 0 : (opts.duration || (type === 'error' || opts.reference ? 9000 : type === 'warning' ? 7000 : 4200));

            var n = el('div', 'st-toast ' + type);
            n.setAttribute('role', type === 'error' || type === 'warning' ? 'alert' : 'status');

            var ic = el('div', 'ic'); ic.innerHTML = svg(ICONS[type]); n.appendChild(ic);

            var bd = el('div', 'bd');
            if (title) bd.appendChild(el('p', 'ti', title));
            if (message) bd.appendChild(el('p', 'ms', message));

            if (opts.reference) {
                var ref = el('div', 'ref');
                ref.appendChild(el('span', null, 'Reference'));
                ref.appendChild(el('code', null, opts.reference));
                var cp = el('button', 'cp', 'Copy'); cp.type = 'button';
                cp.addEventListener('click', function () { copy(opts.reference, cp); });
                ref.appendChild(cp);
                bd.appendChild(ref);
            }
            if (DEBUG && opts.code) {
                bd.appendChild(el('div', 'ref', 'code: ' + opts.code + (opts.status ? ' · HTTP ' + opts.status : '')));
            }

            if (opts.action && opts.action.label) {
                var btns = el('div', 'btns');
                var b = el('button', 'btn', opts.action.label); b.type = 'button';
                b.addEventListener('click', function () { dismiss(id); try { opts.action.onClick && opts.action.onClick(); } catch (e) { } });
                btns.appendChild(b);
                bd.appendChild(btns);
            }
            n.appendChild(bd);

            var x = el('button', 'x'); x.type = 'button'; x.setAttribute('aria-label', 'Dismiss notification');
            x.innerHTML = svg('<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>');
            x.addEventListener('click', function () { dismiss(id); });
            n.appendChild(x);

            if (duration) {
                var bar = el('div', 'bar'); bar.style.animationDuration = duration + 'ms'; n.appendChild(bar);
                bar.addEventListener('animationend', function () { dismiss(id); });
            }

            live[id] = n;
            region.appendChild(n);
            // Newest on top on desktop, oldest dropped past the cap.
            if (window.matchMedia && !matchMedia('(max-width:640px)').matches) region.insertBefore(n, region.firstChild);
            var keys = Object.keys(live);
            while (keys.length > MAX_VISIBLE) dismiss(keys.shift());

            requestAnimationFrame(function () { requestAnimationFrame(function () { n.classList.add('in'); }); });
            return id;
        }

        // Turns the JSON of a failed request (ApiErrors / bootstrap JSON renderer) into one notice.
        function fromResponse(data, fallback, extra) {
            data = (data && typeof data === 'object') ? data : {};
            var msg = data.message, validation = false;
            if (data.errors && typeof data.errors === 'object') {                  // Laravel validation shape
                var first = Object.keys(data.errors)[0];
                var detail = first ? [].concat(data.errors[first])[0] : '';
                if (detail) { msg = detail; validation = true; }
            }
            return show(Object.assign({
                type: (data.timeout || validation) ? 'warning' : 'error',
                title: validation ? 'Please check your details' : data.timeout ? 'Taking longer than expected' : undefined,
                message: msg || fallback || 'That did not work. Please try again.',
                reference: validation ? undefined : data.reference,
                code: data.code
            }, extra || {}));
        }

        // In-page replacement for window.confirm(): resolves true / false, traps focus, Esc cancels.
        // Danger confirmations focus "Cancel" first so a stray Enter never deletes something.
        function confirmDialog(opts) {
            opts = opts || {};
            return new Promise(function (resolve) {
                var prev = document.activeElement;
                var uid = 'stc' + Date.now().toString(36);
                var wrap = el('div', 'st-confirm-wrap');
                var box = el('div', 'st-confirm');
                box.setAttribute('role', 'alertdialog');
                box.setAttribute('aria-modal', 'true');
                var h = el('h2', null, opts.title || 'Are you sure?'); h.id = uid + 't';
                var p = el('p', null, opts.message || ''); p.id = uid + 'm';
                box.setAttribute('aria-labelledby', h.id);
                box.setAttribute('aria-describedby', p.id);
                var row = el('div', 'row');
                var no = el('button', null, opts.cancelLabel || 'Cancel'); no.type = 'button';
                var yes = el('button', 'ok' + (opts.danger ? ' danger' : ''), opts.confirmLabel || 'Confirm'); yes.type = 'button';
                row.appendChild(no); row.appendChild(yes);
                box.appendChild(h); if (opts.message) box.appendChild(p); box.appendChild(row);
                wrap.appendChild(box);

                function done(v) {
                    document.removeEventListener('keydown', onKey, true);
                    wrap.classList.remove('in');
                    setTimeout(function () { if (wrap.parentNode) wrap.parentNode.removeChild(wrap); }, 160);
                    try { if (prev && document.contains(prev)) prev.focus({ preventScroll: true }); } catch (e) { }
                    resolve(v);
                }
                function onKey(e) {
                    if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); done(false); return; }
                    if (e.key !== 'Tab') return;
                    var f = [no, yes];
                    var i = f.indexOf(document.activeElement);
                    e.preventDefault();
                    f[(i + (e.shiftKey ? -1 : 1) + f.length) % f.length].focus();
                }
                no.addEventListener('click', function () { done(false); });
                yes.addEventListener('click', function () { done(true); });
                wrap.addEventListener('mousedown', function (e) { if (e.target === wrap) done(false); });
                document.addEventListener('keydown', onKey, true);

                document.body.appendChild(wrap);
                requestAnimationFrame(function () { wrap.classList.add('in'); (opts.danger ? no : yes).focus({ preventScroll: true }); });
            });
        }

        window.StToast = {
            show: show,
            dismiss: dismiss,
            fromResponse: fromResponse,
            confirm: confirmDialog,
            success: function (m, o) { return show(Object.assign({ type: 'success', message: m }, o || {})); },
            error: function (m, o) { return show(Object.assign({ type: 'error', message: m }, o || {})); },
            warning: function (m, o) { return show(Object.assign({ type: 'warning', message: m }, o || {})); },
            info: function (m, o) { return show(Object.assign({ type: 'info', message: m }, o || {})); }
        };

        /* ── Request guard ────────────────────────────────────────────────────────── */
        var nativeFetch = window.fetch;
        if (typeof nativeFetch !== 'function') return;

        function csrf() {
            var m = document.querySelector('meta[name="csrf-token"]');
            return m ? m.getAttribute('content') : '';
        }
        function sameOrigin(url) {
            try { return new URL(url, location.href).origin === location.origin; } catch (e) { return false; }
        }

        var lastGlobal = {};
        function once(key, ms, fn) {
            var t = Date.now();
            if (lastGlobal[key] && t - lastGlobal[key] < ms) return;
            lastGlobal[key] = t; fn();
        }

        function onStatus(status, res) {
            if (status === 401) {
                once('401', 15000, function () {
                    show({
                        id: 'session-expired', type: 'warning', title: 'Session expired', sticky: true,
                        message: 'For your security you were signed out. Sign in again to continue.',
                        action: { label: 'Sign in', onClick: function () { location.href = '/login?expired=1'; } }
                    });
                    setTimeout(function () { location.href = '/login?expired=1'; }, 6000);
                });
            } else if (status === 419) {
                once('419', 15000, function () {
                    show({
                        id: 'page-expired', type: 'warning', title: 'This page is out of date', sticky: true,
                        message: 'It was open for a long time. Reload to continue — nothing you have already saved is lost.',
                        action: { label: 'Reload page', onClick: function () { location.reload(); } }
                    });
                });
            } else if (status === 429) {
                once('429', 8000, function () {
                    var wait = parseInt(res.headers.get('Retry-After') || '', 10);
                    show({
                        id: 'rate-limited', type: 'warning', title: 'Slow down a little',
                        message: 'Too many requests in a short time.' + (wait > 0 && wait < 3600 ? ' Try again in ' + wait + ' seconds.' : ' Please wait a moment and try again.')
                    });
                });
            }
        }

        window.fetch = function (input, init) {
            var url = typeof input === 'string' ? input : (input && input.url) || '';
            if (!sameOrigin(url)) return nativeFetch.apply(this, arguments);

            try {
                init = Object.assign({}, init || {});
                var h = new Headers(init.headers || (typeof input !== 'string' && input && input.headers) || {});
                var method = String(init.method || (typeof input !== 'string' && input && input.method) || 'GET').toUpperCase();
                if (!h.has('Accept')) h.set('Accept', 'application/json');
                if (!h.has('X-Requested-With')) h.set('X-Requested-With', 'XMLHttpRequest');
                if (method !== 'GET' && method !== 'HEAD' && !h.has('X-CSRF-TOKEN') && csrf()) h.set('X-CSRF-TOKEN', csrf());
                init.headers = h;
            } catch (e) { /* never block a request because of the guard */ }

            return nativeFetch.call(this, input, init).then(function (res) {
                try { onStatus(res.status, res); } catch (e) { }
                return res;
            });
        };

        /* ── Offline banner ───────────────────────────────────────────────────────── */
        function offline() {
            show({
                id: 'offline', type: 'warning', title: "You're offline", sticky: true,
                message: 'Live positions and saves will resume when your connection is back.'
            });
        }
        window.addEventListener('offline', offline);
        window.addEventListener('online', function () {
            dismiss('offline');
            show({ id: 'online', type: 'success', title: false, message: 'Back online.', duration: 2500 });
        });
        if (navigator.onLine === false) offline();
    })();
</script>