@extends('layouts.app')

@section('content')
@php
$myShares = $myShares ?? [];
$sharedWithMe = $sharedWithMe ?? [];
$pendingInvites = $pendingInvites ?? [];
$vehicles = $vehicles ?? [];
$error = $error ?? null;
$pendingCount = count($pendingInvites);
@endphp
<style>
    /* ── Vehicle Sharing ─────────────────────────────────────────────────────── */
    .sh-wrap {
        max-width: 900px;
        margin: 0 auto;
        padding: 2rem 1.25rem 3rem;
    }

    /* Header */
    .sh-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.75rem;
        flex-wrap: wrap;
    }

    .sh-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: #021F4A;
        line-height: 1.2;
    }

    .sh-subtitle {
        font-size: .875rem;
        color: #6b7280;
        margin-top: .3rem;
    }

    .sh-share-btn {
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

    .sh-share-btn:hover {
        background: #e05a00;
    }

    /* Error banner */
    .sh-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: .75rem;
        padding: 1rem 1.25rem;
        font-size: .875rem;
        color: #dc2626;
        margin-bottom: 1.25rem;
    }

    /* Tabs */
    .sh-tabs {
        display: flex;
        gap: .25rem;
        border-bottom: 2px solid #e5e7eb;
        margin-bottom: 1.75rem;
        overflow-x: auto;
        scrollbar-width: none;
    }

    .sh-tabs::-webkit-scrollbar {
        display: none;
    }

    .sh-tab {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .75rem 1.125rem;
        font-size: .875rem;
        font-weight: 600;
        color: #6b7280;
        background: none;
        border: none;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        white-space: nowrap;
        margin-bottom: -2px;
        transition: color .15s, border-color .15s;
    }

    .sh-tab:hover {
        color: #FA6908;
    }

    .sh-tab.active {
        color: #FA6908;
        border-bottom-color: #FA6908;
    }

    .sh-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 18px;
        height: 18px;
        padding: 0 .35rem;
        background: #FA6908;
        color: #fff;
        font-size: .6875rem;
        font-weight: 700;
        border-radius: 99px;
        line-height: 1;
    }

    /* Panels */
    .sh-panel {
        display: none;
    }

    .sh-panel.active {
        display: block;
    }

    /* Share cards */
    .sh-list {
        display: flex;
        flex-direction: column;
        gap: .875rem;
    }

    .sh-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: .875rem;
        padding: 1.125rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: box-shadow .15s;
        flex-wrap: wrap;
    }

    .sh-card:hover {
        box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
    }

    .sh-card-icon {
        width: 44px;
        height: 44px;
        background: #f0f9ff;
        border-radius: .625rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sh-card-icon.orange {
        background: #fff7f0;
    }

    .sh-card-icon svg {
        color: #0284c7;
    }

    .sh-card-icon.orange svg {
        color: #FA6908;
    }

    .sh-card-body {
        flex: 1;
        min-width: 140px;
    }

    .sh-card-vehicle {
        font-size: .9375rem;
        font-weight: 700;
        color: #021F4A;
    }

    .sh-card-make {
        font-size: .8125rem;
        color: #6b7280;
        margin-top: .15rem;
    }

    .sh-card-person {
        font-size: .8125rem;
        color: #374151;
        margin-top: .35rem;
        font-weight: 500;
    }

    .sh-card-phone {
        font-size: .75rem;
        color: #9ca3af;
        margin-top: .1rem;
    }

    .sh-card-meta {
        font-size: .75rem;
        color: #9ca3af;
        margin-top: .25rem;
    }

    .sh-card-actions {
        display: flex;
        gap: .5rem;
        flex-shrink: 0;
    }

    /* Status badges */
    .sh-status {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .6875rem;
        font-weight: 700;
        border-radius: 99px;
        padding: .2em .7em;
        line-height: 1.6;
        border: 1px solid transparent;
    }

    .sh-status.pending {
        background: #fffbeb;
        color: #b45309;
        border-color: #fde68a;
    }

    .sh-status.accepted {
        background: #f0fdf4;
        color: #16a34a;
        border-color: #bbf7d0;
    }

    .sh-status.revoked {
        background: #f9fafb;
        color: #6b7280;
        border-color: #e5e7eb;
    }

    .sh-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
        background: currentColor;
    }

    /* Action buttons */
    .sh-btn-accept {
        padding: .5rem .875rem;
        background: #16a34a;
        border: none;
        border-radius: .5rem;
        font-size: .8125rem;
        font-weight: 700;
        color: #fff;
        cursor: pointer;
        transition: background .15s;
        white-space: nowrap;
        font-family: inherit;
    }

    .sh-btn-accept:hover {
        background: #15803d;
    }

    .sh-btn-accept:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    .sh-btn-decline {
        padding: .5rem .875rem;
        background: #fff;
        border: 1px solid #fecaca;
        border-radius: .5rem;
        font-size: .8125rem;
        font-weight: 600;
        color: #dc2626;
        cursor: pointer;
        transition: background .15s;
        white-space: nowrap;
        font-family: inherit;
    }

    .sh-btn-decline:hover {
        background: #fef2f2;
    }

    .sh-btn-decline:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    .sh-btn-revoke {
        padding: .5rem .875rem;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: .5rem;
        font-size: .8125rem;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        transition: background .15s;
        white-space: nowrap;
        font-family: inherit;
    }

    .sh-btn-revoke:hover {
        background: #fef2f2;
        border-color: #fecaca;
        color: #dc2626;
    }

    .sh-btn-revoke:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    /* Empty state */
    .sh-empty {
        background: #fff;
        border: 1px dashed #e5e7eb;
        border-radius: .875rem;
        padding: 3rem 1.5rem;
        text-align: center;
    }

    .sh-empty-icon {
        width: 54px;
        height: 54px;
        background: #f9fafb;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }

    .sh-empty-icon svg {
        color: #d1d5db;
    }

    .sh-empty-title {
        font-size: 1rem;
        font-weight: 700;
        color: #021F4A;
    }

    .sh-empty-desc {
        font-size: .8125rem;
        color: #6b7280;
        margin-top: .375rem;
        max-width: 360px;
        margin-left: auto;
        margin-right: auto;
    }

    .sh-empty-action {
        margin-top: 1.25rem;
    }

    .sh-empty-cta {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .625rem 1.125rem;
        background: #FA6908;
        color: #fff;
        font-size: .8125rem;
        font-weight: 700;
        border: none;
        border-radius: .625rem;
        cursor: pointer;
        transition: background .15s;
        font-family: inherit;
    }

    .sh-empty-cta:hover {
        background: #e05a00;
    }

    /* ── Share Modal ──────────────────────────────────────────────────────────── */
    .sh-overlay {
        position: fixed;
        inset: 0;
        background: rgba(2, 31, 74, .5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 1rem;
    }

    .sh-overlay.open {
        display: flex;
    }

    .sh-modal {
        background: #fff;
        border-radius: 1rem;
        width: 100%;
        max-width: 480px;
        box-shadow: 0 24px 64px rgba(0, 0, 0, .2);
    }

    .sh-modal-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .sh-modal-title {
        font-size: 1.0625rem;
        font-weight: 800;
        color: #021F4A;
    }

    .sh-modal-close {
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

    .sh-modal-close:hover {
        background: #f3f4f6;
        color: #374151;
    }

    .sh-modal-body {
        padding: 1.5rem;
    }

    .sh-modal-foot {
        padding: .875rem 1.5rem;
        border-top: 1px solid #f3f4f6;
        display: flex;
        justify-content: flex-end;
        gap: .75rem;
    }

    .sh-field {
        margin-bottom: 1.125rem;
    }

    .sh-label {
        display: block;
        font-size: .8125rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: .375rem;
    }

    .sh-label .req {
        color: #dc2626;
        margin-left: .1em;
    }

    .sh-input,
    .sh-select {
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

    .sh-input:focus,
    .sh-select:focus {
        outline: none;
        border-color: #FA6908;
        box-shadow: 0 0 0 3px rgba(250, 105, 8, .12);
    }

    .sh-modal-err {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: .5rem;
        padding: .75rem 1rem;
        font-size: .8125rem;
        color: #dc2626;
        margin-bottom: 1rem;
        display: none;
    }

    .sh-modal-hint {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: .5rem;
        padding: .75rem 1rem;
        font-size: .8125rem;
        color: #16a34a;
        margin-top: 1rem;
        display: flex;
        gap: .5rem;
        align-items: flex-start;
    }

    .sh-modal-hint svg {
        flex-shrink: 0;
        margin-top: .05rem;
    }

    .sh-btn-cancel {
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

    .sh-btn-cancel:hover {
        background: #f3f4f6;
    }

    .sh-btn-primary {
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

    .sh-btn-primary:hover {
        background: #e05a00;
    }

    .sh-btn-primary:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    /* Revoke confirm modal */
    .sh-confirm {
        background: #fff;
        border-radius: 1rem;
        width: 100%;
        max-width: 380px;
        box-shadow: 0 24px 64px rgba(0, 0, 0, .2);
        padding: 2rem 1.5rem;
        text-align: center;
    }

    .sh-confirm-icon {
        width: 54px;
        height: 54px;
        background: #fef2f2;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }

    .sh-confirm-title {
        font-size: 1.0625rem;
        font-weight: 800;
        color: #021F4A;
    }

    .sh-confirm-text {
        font-size: .875rem;
        color: #6b7280;
        margin: .5rem 0 1.5rem;
        line-height: 1.5;
    }

    .sh-confirm-actions {
        display: flex;
        gap: .75rem;
        justify-content: center;
    }

    .sh-btn-danger {
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

    .sh-btn-danger:hover {
        background: #b91c1c;
    }

    .sh-btn-danger:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    /* Toast */
    .sh-toast {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        z-index: 3000;
        background: #021F4A;
        color: #fff;
        font-size: .875rem;
        font-weight: 600;
        padding: .875rem 1.25rem;
        border-radius: .75rem;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .2);
        display: none;
        align-items: center;
        gap: .625rem;
        max-width: 340px;
        animation: sh-toast-in .25s ease;
    }

    .sh-toast.visible {
        display: flex;
    }

    .sh-toast.success {
        background: #16a34a;
    }

    .sh-toast.error {
        background: #dc2626;
    }

    @keyframes sh-toast-in {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 640px) {
        .sh-wrap {
            padding: 1.25rem .875rem 2rem;
        }

        .sh-top {
            flex-direction: column;
            align-items: stretch;
        }

        .sh-share-btn {
            width: 100%;
            justify-content: center;
        }

        .sh-tab {
            padding: .625rem .875rem;
        }

        .sh-card {
            flex-wrap: wrap;
        }

        .sh-card-actions {
            width: 100%;
        }

        .sh-modal,
        .sh-confirm {
            border-radius: .75rem;
        }

        .sh-modal-body {
            padding: 1.25rem;
        }

        .sh-confirm-actions {
            flex-direction: column;
        }

        .sh-btn-cancel,
        .sh-btn-danger {
            width: 100%;
            text-align: center;
        }

        .sh-toast {
            left: 1rem;
            right: 1rem;
            max-width: none;
        }
    }
</style>

<div class="sh-wrap">

    {{-- Page header --}}
    <div class="sh-top">
        <div>
            <div class="sh-title">Vehicle Sharing</div>
            <div class="sh-subtitle">Share your vehicles with others, or manage incoming invitations</div>
        </div>
        @if(count($vehicles) > 0)
        <button class="sh-share-btn" onclick="openShare()">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
            </svg>
            Share Vehicle
        </button>
        @endif
    </div>

    {{-- Error banner --}}
    @if($error)
    <div class="sh-error">{{ $error }}</div>
    @endif

    {{-- Tabs --}}
    <div class="sh-tabs">
        <button class="sh-tab{{ $pendingCount > 0 ? ' active' : ' active' }}" id="sh-tab-pending" onclick="switchTab('pending')">
            Pending Invites
            @if($pendingCount > 0)
            <span class="sh-badge">{{ $pendingCount }}</span>
            @endif
        </button>
        <button class="sh-tab" id="sh-tab-withme" onclick="switchTab('withme')">
            Shared With Me
        </button>
        <button class="sh-tab" id="sh-tab-myshares" onclick="switchTab('myshares')">
            My Shares
        </button>
    </div>

    {{-- Pending Invites panel --}}
    <div class="sh-panel active" id="sh-panel-pending">
        <div class="sh-list" id="sh-pending-list"></div>
    </div>

    {{-- Shared With Me panel --}}
    <div class="sh-panel" id="sh-panel-withme">
        <div class="sh-list" id="sh-withme-list"></div>
    </div>

    {{-- My Shares panel --}}
    <div class="sh-panel" id="sh-panel-myshares">
        <div class="sh-list" id="sh-myshares-list"></div>
    </div>

</div>

{{-- ── Share Vehicle Modal ─────────────────────────────────────────────────── --}}
<div class="sh-overlay" id="sh-share-overlay">
    <div class="sh-modal">
        <div class="sh-modal-head">
            <span class="sh-modal-title">Share a Vehicle</span>
            <button class="sh-modal-close" onclick="closeShare()">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="sh-modal-body">
            <div class="sh-modal-err" id="sh-share-err"></div>
            <div class="sh-field">
                <label class="sh-label" for="sh-vehicle-sel">
                    Vehicle <span class="req">*</span>
                </label>
                <select id="sh-vehicle-sel" class="sh-select">
                    <option value="">— Select a vehicle —</option>
                    @foreach($vehicles as $v)
                    <option value="{{ $v['vehicleId'] ?? $v['id'] ?? '' }}">
                        {{ $v['vehicleNumber'] ?? $v['vehicle_number'] ?? 'Unknown' }}
                        @if(!empty($v['make']) || !empty($v['model']))
                        — {{ trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? '')) }}
                        @endif
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="sh-field" style="margin-bottom:0">
                <label class="sh-label" for="sh-phone-in">
                    Their Phone Number <span class="req">*</span>
                </label>
                <input id="sh-phone-in" type="tel" class="sh-input"
                    placeholder="e.g. +94 77 987 6543"
                    autocomplete="off">
                <div style="font-size:.75rem;color:#6b7280;margin-top:.35rem">
                    Include country code · Must match their ShaloTrack account
                </div>
            </div>
            <div class="sh-modal-hint">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                The person must have the ShaloTrack app installed and registered with this phone number. They'll receive a push notification and can accept or decline the invite.
            </div>
        </div>
        <div class="sh-modal-foot">
            <button class="sh-btn-cancel" onclick="closeShare()">Cancel</button>
            <button class="sh-btn-primary" id="sh-share-submit" onclick="submitShare()">Send Invite</button>
        </div>
    </div>
</div>

{{-- ── Revoke Confirm Modal ────────────────────────────────────────────────── --}}
<div class="sh-overlay" id="sh-revoke-overlay">
    <div class="sh-confirm">
        <div class="sh-confirm-icon">
            <svg width="24" height="24" fill="none" stroke="#dc2626" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
        </div>
        <div class="sh-confirm-title">Revoke Share?</div>
        <div class="sh-confirm-text" id="sh-revoke-text">
            This person will lose access to the vehicle immediately.
        </div>
        <div class="sh-confirm-actions">
            <button class="sh-btn-cancel" onclick="closeRevoke()">Cancel</button>
            <button class="sh-btn-danger" id="sh-revoke-btn" onclick="submitRevoke()">Revoke</button>
        </div>
    </div>
</div>

{{-- ── Toast ───────────────────────────────────────────────────────────────── --}}
<div class="sh-toast" id="sh-toast">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" id="sh-toast-icon">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
    </svg>
    <span id="sh-toast-text"></span>
</div>

<script>
    const CSRF = '{{ csrf_token() }}';

    /* ── State ─────────────────────────────────────────────────────────────────── */
    let PENDING = @json($pendingInvites);
    let WITH_ME = @json($sharedWithMe);
    let MY_SHARES = @json($myShares);
    let _revokeId = null;

    /* ── Utilities ──────────────────────────────────────────────────────────────── */
    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function fmtDate(iso) {
        if (!iso) return '—';
        try {
            return new Date(iso).toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        } catch {
            return '—';
        }
    }

    function vehicleLabel(s) {
        const make = s.make ?? '';
        const model = s.model ?? '';
        const sub = [make, model].filter(Boolean).join(' ');
        return sub ? `${esc(s.vehicleNumber)} <span style="font-weight:400;color:#6b7280">· ${esc(sub)}</span>` : esc(s.vehicleNumber ?? '');
    }

    /* ── Toast ──────────────────────────────────────────────────────────────────── */
    let _toastTimer;

    function showToast(msg, type = 'success') {
        const t = document.getElementById('sh-toast');
        const tx = document.getElementById('sh-toast-text');
        const ic = document.getElementById('sh-toast-icon');
        tx.textContent = msg;
        t.className = `sh-toast visible ${type}`;
        ic.innerHTML = type === 'success' ?
            '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>' :
            '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>';
        clearTimeout(_toastTimer);
        _toastTimer = setTimeout(() => t.classList.remove('visible'), 3500);
    }

    /* ── Tab switching ──────────────────────────────────────────────────────────── */
    function switchTab(name) {
        ['pending', 'withme', 'myshares'].forEach(t => {
            document.getElementById(`sh-tab-${t}`).classList.toggle('active', t === name);
            document.getElementById(`sh-panel-${t}`).classList.toggle('active', t === name);
        });
    }

    /* ── Render: Pending Invites ────────────────────────────────────────────────── */
    function renderPending() {
        const el = document.getElementById('sh-pending-list');
        if (!PENDING.length) {
            el.innerHTML = emptyState(
                'No pending invitations',
                'You have no vehicle share invitations waiting for your response.',
                false
            );
            return;
        }
        el.innerHTML = PENDING.map(s => `
    <div class="sh-card" id="sh-p-${esc(s.shareId)}">
        <div class="sh-card-icon">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
        </div>
        <div class="sh-card-body">
            <div class="sh-card-vehicle">${vehicleLabel(s)}</div>
            <div class="sh-card-person">From: ${esc(s.otherPartyName)}</div>
            <div class="sh-card-phone">${esc(s.otherPartyPhoneNumber)}</div>
            <div class="sh-card-meta">Invited ${fmtDate(s.invitedAt)}</div>
        </div>
        <div class="sh-card-actions">
            <button class="sh-btn-accept"  id="sh-p-acc-${esc(s.shareId)}"
                    onclick="acceptShare('${esc(s.shareId)}')">Accept</button>
            <button class="sh-btn-decline" id="sh-p-dec-${esc(s.shareId)}"
                    onclick="declineShare('${esc(s.shareId)}')">Decline</button>
        </div>
    </div>`).join('');
    }

    /* ── Render: Shared With Me ─────────────────────────────────────────────────── */
    function renderWithMe() {
        const el = document.getElementById('sh-withme-list');
        if (!WITH_ME.length) {
            el.innerHTML = emptyState(
                'No shared vehicles',
                'When someone shares a vehicle with you and you accept, it will appear here.',
                false
            );
            return;
        }
        el.innerHTML = WITH_ME.map(s => `
    <div class="sh-card">
        <div class="sh-card-icon orange">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h11a2 2 0 012 2v3m-2 12H8m0 0a3 3 0 100-6 3 3 0 000 6zm11 0a3 3 0 100-6 3 3 0 000 6z"/>
            </svg>
        </div>
        <div class="sh-card-body">
            <div class="sh-card-vehicle">${vehicleLabel(s)}</div>
            <div class="sh-card-person">Shared by: ${esc(s.otherPartyName)}</div>
            <div class="sh-card-phone">${esc(s.otherPartyPhoneNumber)}</div>
            <div class="sh-card-meta">
                <span class="sh-status accepted"><span class="sh-status-dot"></span>Active</span>
                &nbsp;· Since ${fmtDate(s.respondedAt ?? s.invitedAt)}
            </div>
        </div>
    </div>`).join('');
    }

    /* ── Render: My Shares ──────────────────────────────────────────────────────── */
    function renderMyShares() {
        const el = document.getElementById('sh-myshares-list');
        if (!MY_SHARES.length) {
            el.innerHTML = emptyState(
                'You haven\'t shared any vehicles',
                'Share a vehicle with someone so they can view its location in real time.',
                true
            );
            return;
        }
        el.innerHTML = MY_SHARES.map(s => {
            const st = (s.status ?? '').toLowerCase();
            const badge = st === 'accepted' ?
                `<span class="sh-status accepted"><span class="sh-status-dot"></span>Active</span>` :
                st === 'pending' ?
                `<span class="sh-status pending"><span class="sh-status-dot"></span>Pending</span>` :
                `<span class="sh-status revoked"><span class="sh-status-dot"></span>Revoked</span>`;
            const canRevoke = st === 'pending' || st === 'accepted';
            const revokeBtn = canRevoke ?
                `<button class="sh-btn-revoke" id="sh-m-rev-${esc(s.shareId)}"
                       onclick="confirmRevoke('${esc(s.shareId)}','${esc(s.otherPartyName)}','${esc(s.vehicleNumber)}')">
                   Revoke
               </button>` : '';
            return `
        <div class="sh-card" id="sh-m-${esc(s.shareId)}">
            <div class="sh-card-icon orange">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
            </div>
            <div class="sh-card-body">
                <div class="sh-card-vehicle">${vehicleLabel(s)}</div>
                <div class="sh-card-person">Shared with: ${esc(s.otherPartyName)}</div>
                <div class="sh-card-phone">${esc(s.otherPartyPhoneNumber)}</div>
                <div class="sh-card-meta">${badge} &nbsp;· Invited ${fmtDate(s.invitedAt)}</div>
            </div>
            <div class="sh-card-actions">${revokeBtn}</div>
        </div>`;
        }).join('');
    }

    /* ── Empty state helper ─────────────────────────────────────────────────────── */
    function emptyState(title, desc, withCta) {
        const cta = withCta ?
            `<div class="sh-empty-action">
               <button class="sh-empty-cta" onclick="openShare()">
                   <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                   </svg>
                   Share a Vehicle
               </button>
           </div>` : '';
        return `
    <div class="sh-empty">
        <div class="sh-empty-icon">
            <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
            </svg>
        </div>
        <div class="sh-empty-title">${title}</div>
        <div class="sh-empty-desc">${desc}</div>
        ${cta}
    </div>`;
    }

    /* ── Accept invite ──────────────────────────────────────────────────────────── */
    async function acceptShare(id) {
        setBtns(id, true, 'accept');
        try {
            const res = await fetch(`/sharing/${id}/accept`, {
                method: 'POST',
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
                /* Move from PENDING to WITH_ME */
                const share = PENDING.find(s => s.shareId === id);
                if (share) {
                    PENDING = PENDING.filter(s => s.shareId !== id);
                    share.status = 'Accepted';
                    share.respondedAt = new Date().toISOString();
                    WITH_ME.unshift(share);
                }
                updatePendingBadge();
                renderPending();
                renderWithMe();
                showToast('Invite accepted — vehicle now visible in your dashboard.');
                /* Switch to Shared With Me tab */
                switchTab('withme');
            } else {
                const json = await res.json().catch(() => ({}));
                showToast(json.message ?? 'Failed to accept. Please try again.', 'error');
                setBtns(id, false, 'accept');
            }
        } catch {
            showToast('Network error — please try again.', 'error');
            setBtns(id, false, 'accept');
        }
    }

    /* ── Decline invite ─────────────────────────────────────────────────────────── */
    async function declineShare(id) {
        setBtns(id, true, 'decline');
        try {
            const res = await fetch(`/sharing/${id}/decline`, {
                method: 'POST',
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
                PENDING = PENDING.filter(s => s.shareId !== id);
                updatePendingBadge();
                renderPending();
                showToast('Invitation declined.');
            } else {
                showToast('Failed to decline. Please try again.', 'error');
                setBtns(id, false, 'decline');
            }
        } catch {
            showToast('Network error — please try again.', 'error');
            setBtns(id, false, 'decline');
        }
    }

    /* ── Revoke modal ───────────────────────────────────────────────────────────── */
    function confirmRevoke(id, name, vehNum) {
        _revokeId = id;
        document.getElementById('sh-revoke-text').textContent =
            `${name} will immediately lose access to ${vehNum}.`;
        document.getElementById('sh-revoke-btn').disabled = false;
        document.getElementById('sh-revoke-btn').textContent = 'Revoke';
        document.getElementById('sh-revoke-overlay').classList.add('open');
    }

    function closeRevoke() {
        document.getElementById('sh-revoke-overlay').classList.remove('open');
        _revokeId = null;
    }

    /* ── Submit revoke ──────────────────────────────────────────────────────────── */
    async function submitRevoke() {
        if (!_revokeId) return;
        const id = _revokeId;
        const btn = document.getElementById('sh-revoke-btn');
        btn.disabled = true;
        btn.textContent = 'Revoking…';

        try {
            const res = await fetch(`/sharing/${id}`, {
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
                /* Mark as Revoked in MY_SHARES */
                const share = MY_SHARES.find(s => s.shareId === id);
                if (share) share.status = 'Revoked';
                renderMyShares();
                closeRevoke();
                showToast('Share revoked successfully.');
            } else {
                showToast('Failed to revoke. Please try again.', 'error');
                btn.disabled = false;
                btn.textContent = 'Revoke';
            }
        } catch {
            showToast('Network error — please try again.', 'error');
            btn.disabled = false;
            btn.textContent = 'Revoke';
        }
    }

    /* ── Share Vehicle modal ────────────────────────────────────────────────────── */
    function openShare() {
        document.getElementById('sh-vehicle-sel').value = '';
        document.getElementById('sh-phone-in').value = '';
        document.getElementById('sh-share-err').style.display = 'none';
        document.getElementById('sh-share-submit').disabled = false;
        document.getElementById('sh-share-submit').textContent = 'Send Invite';
        document.getElementById('sh-share-overlay').classList.add('open');
        setTimeout(() => document.getElementById('sh-vehicle-sel').focus(), 60);
    }

    function closeShare() {
        document.getElementById('sh-share-overlay').classList.remove('open');
    }

    async function submitShare() {
        const vehicleId = document.getElementById('sh-vehicle-sel').value;
        const phone = document.getElementById('sh-phone-in').value.trim();
        const errEl = document.getElementById('sh-share-err');

        if (!vehicleId) {
            showModalErr(errEl, 'Please select a vehicle.');
            return;
        }
        if (!phone) {
            showModalErr(errEl, 'Phone number is required.');
            return;
        }

        const btn = document.getElementById('sh-share-submit');
        btn.disabled = true;
        btn.textContent = 'Sending…';
        errEl.style.display = 'none';

        try {
            const res = await fetch('/sharing', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    vehicleId,
                    phoneNumber: phone
                }),
            });
            if (res.status === 401) {
                window.location.href = '/login?expired=1';
                return;
            }

            const json = await res.json().catch(() => ({}));

            if (!res.ok) {
                showModalErr(errEl, json.message ?? 'Failed to send invite. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Send Invite';
                return;
            }

            /* Append to MY_SHARES and re-render */
            const share = json.data ?? json;
            if (share && share.shareId) {
                MY_SHARES.unshift(share);
            }
            renderMyShares();
            closeShare();
            showToast('Invite sent — waiting for them to accept.');
            switchTab('myshares');

        } catch {
            showModalErr(errEl, 'Network error — please try again.');
            btn.disabled = false;
            btn.textContent = 'Send Invite';
        }
    }

    /* ── Helpers ────────────────────────────────────────────────────────────────── */
    function showModalErr(el, msg) {
        el.textContent = msg;
        el.style.display = 'block';
    }

    function setBtns(shareId, disabled, action) {
        ['acc', 'dec'].forEach(k => {
            const el = document.getElementById(`sh-p-${k}-${shareId}`);
            if (el) {
                el.disabled = disabled;
                if (k === 'acc' && action === 'accept') el.textContent = disabled ? 'Accepting…' : 'Accept';
                if (k === 'dec' && action === 'decline') el.textContent = disabled ? 'Declining…' : 'Decline';
            }
        });
    }

    function updatePendingBadge() {
        const tab = document.getElementById('sh-tab-pending');
        /* Re-render badge inside tab */
        const label = 'Pending Invites';
        if (PENDING.length > 0) {
            tab.innerHTML = `${label} <span class="sh-badge">${PENDING.length}</span>`;
        } else {
            tab.textContent = label;
        }
    }

    /* ── Keyboard / backdrop close ──────────────────────────────────────────────── */
    document.addEventListener('keydown', ev => {
        if (ev.key !== 'Escape') return;
        closeShare();
        closeRevoke();
    });
    ['sh-share-overlay', 'sh-revoke-overlay'].forEach(id => {
        const el = document.getElementById(id);
        el.addEventListener('click', ev => {
            if (ev.target === el) {
                closeShare();
                closeRevoke();
            }
        });
    });

    /* ── Init ───────────────────────────────────────────────────────────────────── */
    renderPending();
    renderWithMe();
    renderMyShares();

    /* Switch to Pending tab first if there are pending invites */
    switchTab(PENDING.length > 0 ? 'pending' : 'myshares');
</script>
@endsection