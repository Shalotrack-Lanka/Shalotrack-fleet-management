@extends('layouts.app')

@section('title', 'Vehicle Sharing')

@section('content')
<style>
    /* ── Page shell ── */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 28px;
    }

    .page-title {
        font-size: 22px;
        font-weight: 800;
        color: #021F4A;
    }

    .page-subtitle {
        font-size: 13px;
        color: #6B7280;
        margin-top: 2px;
    }

    /* ── Error banner ── */
    .err-banner {
        background: #FFF5F5;
        border: 1px solid #FECACA;
        border-left: 4px solid #DC2626;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 13px;
        color: #991B1B;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* ── Section cards ── */
    .section-card {
        background: #ffffff;
        border: 1px solid #E5E7EB;
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 24px;
    }

    .section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 24px 14px;
        border-bottom: 1px solid #F3F4F6;
    }

    .section-head-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .section-icon svg {
        width: 17px;
        height: 17px;
    }

    .section-title {
        font-size: 14px;
        font-weight: 700;
        color: #021F4A;
    }

    .section-count {
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 20px;
    }

    /* ── Table ── */
    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: #9CA3AF;
        padding: 10px 24px;
        text-align: left;
        background: #FAFAFA;
        border-bottom: 1px solid #F3F4F6;
    }

    td {
        padding: 14px 24px;
        font-size: 13px;
        color: #374151;
        border-bottom: 1px solid #F9FAFB;
        vertical-align: middle;
    }

    tr:last-child td {
        border-bottom: none;
    }

    tr:hover td {
        background: #FAFAFA;
    }

    .vehicle-cell {
        font-weight: 700;
        color: #021F4A;
    }

    .vehicle-sub {
        font-size: 11px;
        font-weight: 400;
        color: #9CA3AF;
        margin-top: 1px;
    }

    /* ── Status badges ── */
    .badge {
        display: inline-block;
        padding: 3px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.4px;
    }

    .badge-pending {
        background: #FEF3C7;
        color: #92400E;
    }

    .badge-accepted {
        background: #D1FAE5;
        color: #065F46;
    }

    .badge-declined {
        background: #FEE2E2;
        color: #991B1B;
    }

    .badge-revoked {
        background: #F3F4F6;
        color: #6B7280;
    }

    /* ── Action buttons ── */
    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: opacity 0.15s, transform 0.1s;
        text-decoration: none;
    }

    .btn-action:hover {
        opacity: 0.82;
    }

    .btn-action:active {
        transform: scale(0.97);
    }

    .btn-action svg {
        width: 13px;
        height: 13px;
        flex-shrink: 0;
    }

    .btn-accept {
        background: #D1FAE5;
        color: #065F46;
    }

    .btn-decline {
        background: #FEE2E2;
        color: #991B1B;
    }

    .btn-revoke {
        background: #FEE2E2;
        color: #991B1B;
    }

    .action-gap {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    /* ── Empty state ── */
    .empty-row td {
        padding: 36px 24px;
        text-align: center;
        color: #9CA3AF;
        font-size: 13px;
    }

    /* ── Invite modal ── */
    .modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(2, 31, 74, 0.55);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .modal-backdrop.open {
        display: flex;
    }

    .modal {
        background: #ffffff;
        border-radius: 16px;
        width: 100%;
        max-width: 420px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
    }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px 16px;
        border-bottom: 1px solid #F3F4F6;
    }

    .modal-title {
        font-size: 16px;
        font-weight: 800;
        color: #021F4A;
    }

    .modal-close {
        background: #F3F4F6;
        border: none;
        border-radius: 7px;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #6B7280;
    }

    .modal-close:hover {
        background: #E5E7EB;
    }

    .modal-body {
        padding: 20px 24px;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        color: #6B7280;
        margin-bottom: 6px;
    }

    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #D1D5DB;
        border-radius: 8px;
        font-size: 13px;
        color: #021F4A;
        outline: none;
        transition: border-color 0.15s;
    }

    .form-control:focus {
        border-color: #FA6908;
        box-shadow: 0 0 0 3px rgba(250, 105, 8, 0.1);
    }

    .modal-footer {
        display: flex;
        gap: 10px;
        padding: 0 24px 20px;
    }

    .btn-primary {
        flex: 1;
        padding: 10px;
        background: #021F4A;
        color: #fff;
        border: none;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: opacity 0.15s;
    }

    .btn-primary:hover {
        opacity: 0.88;
    }

    .btn-primary:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .btn-secondary-modal {
        flex: 1;
        padding: 10px;
        background: #F3F4F6;
        color: #374151;
        border: none;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }

    .modal-err {
        background: #FFF5F5;
        border: 1px solid #FECACA;
        border-radius: 7px;
        padding: 10px 12px;
        font-size: 12px;
        color: #991B1B;
        margin-bottom: 16px;
        display: none;
    }

    /* ── Top-right invite button ── */
    .btn-invite {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        background: #FA6908;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: opacity 0.15s;
    }

    .btn-invite:hover {
        opacity: 0.88;
    }

    .btn-invite svg {
        width: 15px;
        height: 15px;
    }
</style>

{{-- CSRF for fetch calls --}}
@php $CSRF = csrf_token(); @endphp

<div class="page-header">
    <div>
        <div class="page-title">Vehicle Sharing</div>
        <div class="page-subtitle">Share your vehicles with family or colleagues, and view vehicles shared with you</div>
    </div>
    <button class="btn-invite" onclick="openModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M12 5v14M5 12h14" />
        </svg>
        Share a vehicle
    </button>
</div>

{{-- Error banner --}}
@if($error)
<div class="err-banner">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10" />
        <line x1="12" y1="8" x2="12" y2="12" />
        <line x1="12" y1="16" x2="12.01" y2="16" />
    </svg>
    {{ $error }}
</div>
@endif

{{-- ── SECTION 1: Pending invites for me ── --}}
@php $pendingCount = count($pendingInvites); @endphp
<div class="section-card">
    <div class="section-head">
        <div class="section-head-left">
            <div class="section-icon" style="background:#FEF3C7;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#92400E" stroke-width="2">
                    <path d="M12 2a10 10 0 100 20A10 10 0 0012 2zm0 6v4l3 3" />
                </svg>
            </div>
            <div>
                <div class="section-title">Pending invites</div>
            </div>
        </div>
        @if($pendingCount > 0)
        <span class="badge badge-pending">{{ $pendingCount }} waiting</span>
        @endif
    </div>
    <table>
        <thead>
            <tr>
                <th>Vehicle</th>
                <th>Shared by</th>
                <th>Invited</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pendingInvites as $invite)
            <tr id="invite-row-{{ $invite['shareId'] }}">
                <td>
                    <div class="vehicle-cell">{{ $invite['vehicleNumber'] ?? '—' }}</div>
                    <div class="vehicle-sub">{{ trim(($invite['make'] ?? '') . ' ' . ($invite['model'] ?? '')) ?: '—' }}</div>
                </td>
                <td>{{ $invite['otherPartyName'] ?? '—' }}</td>
                <td>{{ isset($invite['invitedAt']) ? \Carbon\Carbon::parse($invite['invitedAt'])->setTimezone('Asia/Colombo')->format('d M Y, g:i A') : '—' }}</td>
                <td>
                    <div class="action-gap">
                        <button class="btn-action btn-accept" onclick="respondInvite('{{ $invite['shareId'] }}', true)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                            Accept
                        </button>
                        <button class="btn-action btn-decline" onclick="respondInvite('{{ $invite['shareId'] }}', false)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                            Decline
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr class="empty-row">
                <td colspan="4">No pending invites</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── SECTION 2: Shared with me (accepted) ── --}}
<div class="section-card">
    <div class="section-head">
        <div class="section-head-left">
            <div class="section-icon" style="background:#D1FAE5;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#065F46" stroke-width="2">
                    <path d="M1 3h15v13H1zM16 8l4 3-4 3" />
                </svg>
            </div>
            <div>
                <div class="section-title">Shared with me</div>
            </div>
        </div>
        <span class="badge badge-accepted">{{ count($sharedWithMe) }} active</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Vehicle</th>
                <th>Owner</th>
                <th>Since</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sharedWithMe as $share)
            <tr>
                <td>
                    <div class="vehicle-cell">{{ $share['vehicleNumber'] ?? '—' }}</div>
                    <div class="vehicle-sub">{{ trim(($share['make'] ?? '') . ' ' . ($share['model'] ?? '')) ?: '—' }}</div>
                </td>
                <td>{{ $share['otherPartyName'] ?? '—' }}</td>
                <td>{{ isset($share['respondedAt']) ? \Carbon\Carbon::parse($share['respondedAt'])->setTimezone('Asia/Colombo')->format('d M Y') : '—' }}</td>
                <td><span class="badge badge-accepted">Active</span></td>
            </tr>
            @empty
            <tr class="empty-row">
                <td colspan="4">No vehicles have been shared with you yet</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── SECTION 3: Shares I created ── --}}
<div class="section-card">
    <div class="section-head">
        <div class="section-head-left">
            <div class="section-icon" style="background:#EDE9FE;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#5B21B6" stroke-width="2">
                    <circle cx="18" cy="5" r="3" />
                    <circle cx="6" cy="12" r="3" />
                    <circle cx="18" cy="19" r="3" />
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
                </svg>
            </div>
            <div>
                <div class="section-title">My shares</div>
            </div>
        </div>
        <span class="badge" style="background:#EDE9FE;color:#5B21B6;">{{ count($myShares) }} total</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Vehicle</th>
                <th>Shared with</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($myShares as $share)
            @php
            $status = strtolower($share['status'] ?? 'pending');
            $badgeClass = match($status) {
            'accepted' => 'badge-accepted',
            'declined' => 'badge-declined',
            'revoked' => 'badge-revoked',
            default => 'badge-pending',
            };
            @endphp
            <tr id="share-row-{{ $share['shareId'] }}">
                <td>
                    <div class="vehicle-cell">{{ $share['vehicleNumber'] ?? '—' }}</div>
                    <div class="vehicle-sub">{{ trim(($share['make'] ?? '') . ' ' . ($share['model'] ?? '')) ?: '—' }}</div>
                </td>
                <td>{{ $share['otherPartyName'] ?? '—' }}</td>
                <td>{{ $share['otherPartyPhoneNumber'] ?? '—' }}</td>
                <td><span class="badge {{ $badgeClass }}">{{ ucfirst($status) }}</span></td>
                <td>
                    @if($status !== 'revoked')
                    <button class="btn-action btn-revoke" onclick="revokeShare('{{ $share['shareId'] }}')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                        Revoke
                    </button>
                    @else
                    <span style="font-size:12px;color:#9CA3AF;">Revoked</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr class="empty-row">
                <td colspan="5">You haven't shared any vehicles yet</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── Invite modal ── --}}
<div class="modal-backdrop" id="invite-modal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Share a vehicle</span>
            <button class="modal-close" onclick="closeModal()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="modal-err" id="modal-err"></div>

            <div class="form-group">
                <label class="form-label" for="invite-vehicle">Vehicle</label>
                <select class="form-control" id="invite-vehicle">
                    <option value="">— Select a vehicle —</option>
                    @foreach($vehicles as $v)
                    <option value="{{ $v['vehicleId'] }}">
                        {{ $v['vehicleNumber'] }} — {{ trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? '')) }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="invite-phone">Recipient's phone number</label>
                <input type="tel"
                    class="form-control"
                    id="invite-phone"
                    placeholder="e.g. 0771234567"
                    maxlength="15">
                <div style="font-size:11px;color:#9CA3AF;margin-top:4px;">
                    The person must already have the ShaloTrack app installed.
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary-modal" onclick="closeModal()">Cancel</button>
            <button class="btn-primary" id="invite-submit-btn" onclick="submitInvite()">Send invite</button>
        </div>
    </div>
</div>

<script>
    (function() {
        const CSRF = '{{ $CSRF }}';

        // ── Modal ──────────────────────────────────────────────────────────────────
        function openModal() {
            document.getElementById('invite-modal').classList.add('open');
        }

        function closeModal() {
            document.getElementById('invite-modal').classList.remove('open');
            document.getElementById('modal-err').style.display = 'none';
            document.getElementById('invite-vehicle').value = '';
            document.getElementById('invite-phone').value = '';
        }
        window.openModal = openModal;
        window.closeModal = closeModal;

        // Close on backdrop click
        document.getElementById('invite-modal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        // Close on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });

        // ── Helpers ───────────────────────────────────────────────────────────────
        async function apiFetch(url, method, body) {
            const opts = {
                method,
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
            };
            if (body) opts.body = JSON.stringify(body);
            const res = await fetch(url, opts);
            if (res.status === 401) {
                window.location.href = '/login?expired=1';
                return {
                    ok: false,
                    data: {}
                };
            }
            const data = await res.json().catch(() => ({}));
            return {
                ok: res.ok,
                status: res.status,
                data
            };
        }

        function showModalErr(msg) {
            const el = document.getElementById('modal-err');
            el.textContent = msg;
            el.style.display = 'block';
        }

        // ── Send invite ───────────────────────────────────────────────────────────
        async function submitInvite() {
            const vehicleId = document.getElementById('invite-vehicle').value.trim();
            const phone = document.getElementById('invite-phone').value.trim();
            document.getElementById('modal-err').style.display = 'none';

            if (!vehicleId) {
                showModalErr('Please select a vehicle.');
                return;
            }
            if (!phone) {
                showModalErr('Please enter a phone number.');
                return;
            }

            const btn = document.getElementById('invite-submit-btn');
            btn.disabled = true;
            btn.textContent = 'Sending…';

            const {
                ok,
                data
            } = await apiFetch('/sharing', 'POST', {
                vehicleId,
                phoneNumber: phone
            });

            btn.disabled = false;
            btn.textContent = 'Send invite';

            if (ok && data.success !== false) {
                closeModal();
                window.location.reload();
            } else {
                showModalErr(data.message ?? 'Failed to send invite. Please try again.');
            }
        }
        window.submitInvite = submitInvite;

        // ── Accept / decline invite ───────────────────────────────────────────────
        async function respondInvite(shareId, accept) {
            const row = document.getElementById('invite-row-' + shareId);
            const btns = row ? row.querySelectorAll('button') : [];
            btns.forEach(b => b.disabled = true);

            const url = accept ? '/sharing/' + shareId + '/accept' : '/sharing/' + shareId + '/decline';
            const {
                ok,
                data
            } = await apiFetch(url, 'POST');

            if (ok && data.success !== false) {
                window.location.reload();
            } else {
                btns.forEach(b => b.disabled = false);
                alert(data.message ?? (accept ? 'Failed to accept invite.' : 'Failed to decline invite.'));
            }
        }
        window.respondInvite = respondInvite;

        // ── Revoke share ──────────────────────────────────────────────────────────
        async function revokeShare(shareId) {
            if (!confirm('Revoke this share? The other person will immediately lose access to the vehicle.')) return;

            const row = document.getElementById('share-row-' + shareId);
            const btn = row ? row.querySelector('button') : null;
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Revoking…';
            }

            const {
                ok,
                data
            } = await apiFetch('/sharing/' + shareId, 'DELETE');

            if (ok && data.success !== false) {
                window.location.reload();
            } else {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Revoke';
                }
                alert(data.message ?? 'Failed to revoke share.');
            }
        }
        window.revokeShare = revokeShare;
    })();
</script>
@endsection