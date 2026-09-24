@extends('layouts.app')

@section('content')
@php
$contacts = $contacts ?? [];
$error = $error ?? null;
@endphp
<style>
    /* ── Emergency Contacts ──────────────────────────────────────────────────── */
    .ec-wrap {
        max-width: 820px;
        margin: 0 auto;
        padding: 2rem 1.25rem 3rem;
    }

    /* Header */
    .ec-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.75rem;
        flex-wrap: wrap;
    }

    .ec-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: #021F4A;
        line-height: 1.2;
    }

    .ec-subtitle {
        font-size: .875rem;
        color: #6b7280;
        margin-top: .3rem;
    }

    .ec-add-btn {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .625rem 1.25rem;
        background: #FA6908;
        color: #fff;
        font-size: .875rem;
        font-weight: 700;
        border: none;
        border-radius: .625rem;
        cursor: pointer;
        transition: background .15s;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .ec-add-btn:hover {
        background: #e05a00;
    }

    /* Error banner */
    .ec-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: .75rem;
        padding: 1rem 1.25rem;
        font-size: .875rem;
        color: #dc2626;
        margin-bottom: 1.25rem;
    }

    /* Info strip */
    .ec-info {
        display: flex;
        align-items: center;
        gap: .75rem;
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: .75rem;
        padding: .875rem 1.25rem;
        font-size: .8125rem;
        color: #0369a1;
        margin-bottom: 1.5rem;
    }

    .ec-info svg {
        flex-shrink: 0;
        color: #0284c7;
    }

    /* Contact cards */
    .ec-list {
        display: flex;
        flex-direction: column;
        gap: .875rem;
    }

    .ec-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: .875rem;
        padding: 1.125rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: box-shadow .15s;
    }

    .ec-card:hover {
        box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
    }

    .ec-avatar {
        width: 46px;
        height: 46px;
        background: #fff7f0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .ec-avatar svg {
        color: #FA6908;
    }

    .ec-body {
        flex: 1;
        min-width: 0;
    }

    .ec-name {
        font-size: .9375rem;
        font-weight: 700;
        color: #021F4A;
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
    }

    .ec-rel-badge {
        display: inline-block;
        font-size: .6875rem;
        font-weight: 600;
        color: #FA6908;
        background: #fff7f0;
        border: 1px solid #fed7aa;
        border-radius: 99px;
        padding: .1em .65em;
        line-height: 1.6;
    }

    .ec-phone {
        font-size: .8125rem;
        color: #374151;
        margin-top: .2rem;
        font-weight: 500;
    }

    .ec-meta {
        font-size: .75rem;
        color: #9ca3af;
        margin-top: .2rem;
    }

    .ec-del-btn {
        width: 36px;
        height: 36px;
        background: none;
        border: 1px solid #fecaca;
        border-radius: .5rem;
        color: #dc2626;
        cursor: pointer;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background .15s, border-color .15s;
    }

    .ec-del-btn:hover {
        background: #fef2f2;
        border-color: #dc2626;
    }

    /* Empty state */
    .ec-empty {
        background: #fff;
        border: 1px dashed #e5e7eb;
        border-radius: .875rem;
        padding: 3.5rem 1.5rem;
        text-align: center;
    }

    .ec-empty-icon {
        width: 56px;
        height: 56px;
        background: #f9fafb;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }

    .ec-empty-icon svg {
        color: #d1d5db;
    }

    .ec-empty-title {
        font-size: 1rem;
        font-weight: 700;
        color: #021F4A;
    }

    .ec-empty-desc {
        font-size: .8125rem;
        color: #6b7280;
        margin-top: .375rem;
        max-width: 340px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Overlay + Modals */
    .ec-overlay {
        position: fixed;
        inset: 0;
        background: rgba(2, 31, 74, .5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 1rem;
    }

    .ec-overlay.open {
        display: flex;
    }

    .ec-modal {
        background: #fff;
        border-radius: 1rem;
        width: 100%;
        max-width: 460px;
        box-shadow: 0 24px 64px rgba(0, 0, 0, .2);
    }

    .ec-modal-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .ec-modal-title {
        font-size: 1.0625rem;
        font-weight: 800;
        color: #021F4A;
    }

    .ec-modal-close {
        width: 30px;
        height: 30px;
        background: none;
        border: none;
        cursor: pointer;
        color: #9ca3af;
        border-radius: .5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .ec-modal-close:hover {
        background: #f3f4f6;
        color: #374151;
    }

    .ec-modal-body {
        padding: 1.5rem;
    }

    .ec-modal-foot {
        padding: .875rem 1.5rem;
        border-top: 1px solid #f3f4f6;
        display: flex;
        justify-content: flex-end;
        gap: .75rem;
    }

    .ec-field {
        margin-bottom: 1.125rem;
    }

    .ec-label {
        display: block;
        font-size: .8125rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: .375rem;
    }

    .ec-label .opt {
        color: #9ca3af;
        font-weight: 400;
    }

    .ec-label .req {
        color: #dc2626;
        margin-left: .1em;
    }

    .ec-input,
    .ec-select {
        width: 100%;
        padding: .625rem .875rem;
        border: 1px solid #d1d5db;
        border-radius: .625rem;
        font-size: .875rem;
        color: #111827;
        background: #fff;
        transition: border-color .15s;
        box-sizing: border-box;
        font-family: inherit;
    }

    .ec-input:focus,
    .ec-select:focus {
        outline: none;
        border-color: #FA6908;
        box-shadow: 0 0 0 3px rgba(250, 105, 8, .12);
    }

    .ec-modal-err {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: .5rem;
        padding: .75rem 1rem;
        font-size: .8125rem;
        color: #dc2626;
        margin-bottom: 1rem;
        display: none;
    }

    /* Buttons */
    .ec-btn-cancel {
        padding: .625rem 1.125rem;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: .625rem;
        font-size: .875rem;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        font-family: inherit;
    }

    .ec-btn-cancel:hover {
        background: #f3f4f6;
    }

    .ec-btn-primary {
        padding: .625rem 1.25rem;
        background: #FA6908;
        border: none;
        border-radius: .625rem;
        font-size: .875rem;
        font-weight: 700;
        color: #fff;
        cursor: pointer;
        transition: background .15s;
        font-family: inherit;
    }

    .ec-btn-primary:hover {
        background: #e05a00;
    }

    .ec-btn-primary:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    .ec-btn-danger {
        padding: .625rem 1.25rem;
        background: #dc2626;
        border: none;
        border-radius: .625rem;
        font-size: .875rem;
        font-weight: 700;
        color: #fff;
        cursor: pointer;
        transition: background .15s;
        font-family: inherit;
    }

    .ec-btn-danger:hover {
        background: #b91c1c;
    }

    .ec-btn-danger:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    /* Delete confirm modal */
    .ec-confirm {
        background: #fff;
        border-radius: 1rem;
        width: 100%;
        max-width: 380px;
        box-shadow: 0 24px 64px rgba(0, 0, 0, .2);
        padding: 2rem 1.5rem;
        text-align: center;
    }

    .ec-confirm-icon {
        width: 54px;
        height: 54px;
        background: #fef2f2;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }

    .ec-confirm-title {
        font-size: 1.0625rem;
        font-weight: 800;
        color: #021F4A;
    }

    .ec-confirm-text {
        font-size: .875rem;
        color: #6b7280;
        margin: .5rem 0 1.5rem;
        line-height: 1.5;
    }

    .ec-confirm-actions {
        display: flex;
        gap: .75rem;
        justify-content: center;
    }

    @media (max-width: 620px) {
        .ec-wrap {
            padding: 1.25rem .875rem 2rem;
        }

        .ec-top {
            flex-direction: column;
            align-items: stretch;
        }

        .ec-add-btn {
            width: 100%;
            justify-content: center;
        }

        .ec-card {
            flex-wrap: wrap;
        }

        .ec-modal,
        .ec-confirm {
            border-radius: .75rem;
        }

        .ec-modal-body {
            padding: 1.25rem;
        }

        .ec-confirm-actions {
            flex-direction: column;
        }

        .ec-btn-cancel,
        .ec-btn-danger {
            width: 100%;
            text-align: center;
        }
    }
</style>

<div class="ec-wrap">

    {{-- Page header --}}
    <div class="ec-top">
        <div>
            <div class="ec-title">Emergency Contacts</div>
            <div class="ec-subtitle">People ShaloTrack will notify if you trigger an SOS alert</div>
        </div>
        <button class="ec-add-btn" onclick="openAdd()">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Add Contact
        </button>
    </div>

    {{-- Error banner --}}
    @if($error)
    <div class="ec-error">{{ $error }}</div>
    @endif

    {{-- Info strip --}}
    <div class="ec-info">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        When you press the SOS button in the ShaloTrack app, these contacts receive an SMS and push notification with your live location.
    </div>

    {{-- Contact list (JS-rendered from PHP array) --}}
    <div class="ec-list" id="ec-list"></div>

</div>

{{-- ── Add Contact Modal ──────────────────────────────────────────────────── --}}
<div class="ec-overlay" id="ec-add-overlay">
    <div class="ec-modal">
        <div class="ec-modal-head">
            <span class="ec-modal-title">Add Emergency Contact</span>
            <button class="ec-modal-close" onclick="closeAdd()">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="ec-modal-body">
            <div class="ec-modal-err" id="ec-add-err"></div>
            <div class="ec-field">
                <label class="ec-label" for="ec-name">
                    Full Name <span class="req">*</span>
                </label>
                <input id="ec-name" type="text" class="ec-input"
                    placeholder="e.g. Kasun Perera" maxlength="80"
                    autocomplete="name">
            </div>
            <div class="ec-field">
                <label class="ec-label" for="ec-phone">
                    Phone Number <span class="req">*</span>
                </label>
                <input id="ec-phone" type="tel" class="ec-input"
                    placeholder="e.g. +94 77 123 4567"
                    autocomplete="tel">
                <div style="font-size:.75rem;color:#6b7280;margin-top:.35rem">
                    Include country code (e.g. +94 for Sri Lanka)
                </div>
            </div>
            <div class="ec-field" style="margin-bottom:0">
                <label class="ec-label" for="ec-relation">
                    Relationship <span class="opt">(optional)</span>
                </label>
                <select id="ec-relation" class="ec-select">
                    <option value="">— Select relationship —</option>
                    <option>Family</option>
                    <option>Spouse</option>
                    <option>Partner</option>
                    <option>Parent</option>
                    <option>Child</option>
                    <option>Sibling</option>
                    <option>Friend</option>
                    <option>Colleague</option>
                    <option>Other</option>
                </select>
            </div>
        </div>
        <div class="ec-modal-foot">
            <button class="ec-btn-cancel" onclick="closeAdd()">Cancel</button>
            <button class="ec-btn-primary" id="ec-add-submit" onclick="submitAdd()">
                Add Contact
            </button>
        </div>
    </div>
</div>

{{-- ── Delete Confirm Modal ───────────────────────────────────────────────── --}}
<div class="ec-overlay" id="ec-del-overlay">
    <div class="ec-confirm">
        <div class="ec-confirm-icon">
            <svg width="24" height="24" fill="none" stroke="#dc2626" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </div>
        <div class="ec-confirm-title">Remove Contact?</div>
        <div class="ec-confirm-text" id="ec-del-text">
            This person will no longer be notified when you trigger an SOS alert.
        </div>
        <div class="ec-confirm-actions">
            <button class="ec-btn-cancel" onclick="closeDel()">Cancel</button>
            <button class="ec-btn-danger" id="ec-del-btn" onclick="submitDel()">Remove</button>
        </div>
    </div>
</div>

<script>
    const CSRF = '{{ csrf_token() }}';

    /* ── State ─────────────────────────────────────────────────────────────── */
    let CONTACTS = @json($contacts);
    let _delId = null;

    /* ── Utility ────────────────────────────────────────────────────────────── */
    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function fmtDate(iso) {
        if (!iso) return '';
        try {
            return new Date(iso).toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        } catch {
            return '';
        }
    }

    /* ── Render ─────────────────────────────────────────────────────────────── */
    function render() {
        const list = document.getElementById('ec-list');

        if (!CONTACTS.length) {
            list.innerHTML = `
        <div class="ec-empty">
            <div class="ec-empty-icon">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.768-.231-1.48-.634-2.07M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.768.231-1.48.634-2.07m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div class="ec-empty-title">No emergency contacts yet</div>
            <div class="ec-empty-desc">Add someone who should be notified when you press the SOS button.</div>
        </div>`;
            return;
        }

        list.innerHTML = CONTACTS.map(c => {
            const rel = c.relationship ?
                `<span class="ec-rel-badge">${esc(c.relationship)}</span>` : '';
            return `
        <div class="ec-card">
            <div class="ec-avatar">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div class="ec-body">
                <div class="ec-name">${esc(c.name)}${rel}</div>
                <div class="ec-phone">${esc(c.phoneNumber)}</div>
                <div class="ec-meta">Added ${fmtDate(c.createdAt)}</div>
            </div>
            <button class="ec-del-btn"
                    onclick="confirmDel('${esc(c.emergencyContactId)}','${esc(c.name)}')"
                    title="Remove ${esc(c.name)}">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>`;
        }).join('');
    }

    /* ── Add Modal ──────────────────────────────────────────────────────────── */
    function openAdd() {
        document.getElementById('ec-name').value = '';
        document.getElementById('ec-phone').value = '';
        document.getElementById('ec-relation').value = '';
        hideErr('ec-add-err');
        setBtn('ec-add-submit', false, 'Add Contact');
        document.getElementById('ec-add-overlay').classList.add('open');
        setTimeout(() => document.getElementById('ec-name').focus(), 60);
    }

    function closeAdd() {
        document.getElementById('ec-add-overlay').classList.remove('open');
    }

    async function submitAdd() {
        const name = document.getElementById('ec-name').value.trim();
        const phone = document.getElementById('ec-phone').value.trim();
        const relation = document.getElementById('ec-relation').value.trim();
        const errEl = document.getElementById('ec-add-err');

        if (!name) {
            showErr(errEl, 'Name is required.');
            return;
        }
        if (!phone) {
            showErr(errEl, 'Phone number is required.');
            return;
        }

        setBtn('ec-add-submit', true, 'Saving…');
        hideErr('ec-add-err');

        try {
            const res = await fetch('/emergency-contacts', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name,
                    phoneNumber: phone,
                    relationship: relation || null
                }),
            });

            if (res.status === 401) {
                window.location.href = '/login?expired=1';
                return;
            }

            const json = await res.json().catch(() => ({}));

            if (!res.ok) {
                showErr(errEl, json.message ?? 'Failed to add contact. Please try again.');
                setBtn('ec-add-submit', false, 'Add Contact');
                return;
            }

            /* Append new contact to local array and re-render */
            const contact = json.data ?? json;
            if (contact && contact.emergencyContactId) {
                CONTACTS.push(contact);
            } else {
                /* Controller returned success but no data — reload to get fresh list */
                window.location.reload();
                return;
            }
            render();
            closeAdd();

        } catch {
            showErr(errEl, 'Network error — please try again.');
            setBtn('ec-add-submit', false, 'Add Contact');
        }
    }

    /* ── Delete Modal ───────────────────────────────────────────────────────── */
    function confirmDel(id, name) {
        _delId = id;
        document.getElementById('ec-del-text').textContent =
            `"${name}" will no longer be notified when you trigger an SOS alert.`;
        setBtn('ec-del-btn', false, 'Remove');
        document.getElementById('ec-del-overlay').classList.add('open');
    }

    function closeDel() {
        document.getElementById('ec-del-overlay').classList.remove('open');
        _delId = null;
    }

    async function submitDel() {
        if (!_delId) return;
        setBtn('ec-del-btn', true, 'Removing…');

        try {
            const res = await fetch(`/emergency-contacts/${_delId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                },
            });

            if (res.status === 401) {
                window.location.href = '/login?expired=1';
                return;
            }

            if (res.ok) {
                const gone = _delId;
                CONTACTS = CONTACTS.filter(c => c.emergencyContactId !== gone);
                render();
                closeDel();
            } else {
                /* Fallback: reload on unknown errors */
                window.location.reload();
            }

        } catch {
            setBtn('ec-del-btn', false, 'Remove');
        }
    }

    /* ── Helpers ────────────────────────────────────────────────────────────── */
    function showErr(el, msg) {
        if (typeof el === 'string') el = document.getElementById(el);
        el.textContent = msg;
        el.style.display = 'block';
    }

    function hideErr(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    }

    function setBtn(id, disabled, label) {
        const btn = document.getElementById(id);
        if (!btn) return;
        btn.disabled = disabled;
        btn.textContent = label;
    }

    /* ── Keyboard / backdrop close ──────────────────────────────────────────── */
    document.addEventListener('keydown', ev => {
        if (ev.key !== 'Escape') return;
        closeAdd();
        closeDel();
    });
    ['ec-add-overlay', 'ec-del-overlay'].forEach(id => {
        const el = document.getElementById(id);
        el.addEventListener('click', ev => {
            if (ev.target === el) {
                closeAdd();
                closeDel();
            }
        });
    });

    /* ── Init ───────────────────────────────────────────────────────────────── */
    render();
</script>
@endsection