@extends('layouts.app')

@section('title', 'Vehicle Sharing — ShaloTrack Fleet')
@section('page-title', 'Vehicle Sharing')

@section('content')

    @if($error)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ $error }}
        </div>
    @endif

    {{-- Pending invites banner --}}
    @if(!empty($pendingInvites))
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
            <p class="text-sm font-semibold text-blue-800 mb-3">
                📨 You have {{ count($pendingInvites) }} pending invite(s)
            </p>
            <div class="space-y-3">
                @foreach($pendingInvites as $invite)
                    <div class="flex items-center justify-between bg-white rounded-lg px-4 py-3 border border-blue-100"
                         id="invite-{{ $invite['shareId'] }}">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ $invite['vehicleNumber'] }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $invite['make'] }} {{ $invite['model'] }} ·
                                Shared by {{ $invite['otherPartyName'] ?? $invite['otherPartyPhoneNumber'] }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="respondInvite('{{ $invite['shareId'] }}', true)"
                                    class="px-3 py-1.5 text-xs bg-green-500 text-white font-semibold rounded-lg hover:bg-green-600 transition">
                                Accept
                            </button>
                            <button onclick="respondInvite('{{ $invite['shareId'] }}', false)"
                                    class="px-3 py-1.5 text-xs border border-gray-200 text-gray-600 font-semibold rounded-lg hover:bg-gray-50 transition">
                                Decline
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-6">

        {{-- Vehicles I'm sharing (owner) --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">Vehicles I'm Sharing</h3>
                <button onclick="openInviteModal()"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-[#FA6908] text-white text-xs font-semibold rounded-lg hover:bg-orange-600 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Invite
                </button>
            </div>

            @if(empty($myShares))
                <div class="p-8 text-center">
                    <p class="text-gray-400 text-sm">You haven't shared any vehicles yet.</p>
                    <p class="text-gray-300 text-xs mt-1">Invite someone by their phone number.</p>
                </div>
            @else
                <div class="divide-y divide-gray-50">
                    @foreach($myShares as $share)
                        <div class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition"
                             id="share-{{ $share['shareId'] }}">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ $share['vehicleNumber'] }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ $share['make'] }} {{ $share['model'] }}
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Shared with: <strong>{{ $share['otherPartyName'] ?? $share['otherPartyPhoneNumber'] }}</strong>
                                </p>
                                <p class="text-xs mt-0.5">
                                    @php $status = strtolower($share['status'] ?? ''); @endphp
                                    @if($status === 'accepted')
                                        <span class="text-green-600">● Accepted</span>
                                    @elseif($status === 'pending')
                                        <span class="text-amber-500">● Pending</span>
                                    @else
                                        <span class="text-gray-400">● {{ ucfirst($status) }}</span>
                                    @endif
                                </p>
                            </div>
                            <button onclick="confirmRevoke('{{ $share['shareId'] }}', '{{ $share['vehicleNumber'] }}', '{{ $share['otherPartyName'] ?? $share['otherPartyPhoneNumber'] }}')"
                                    class="text-xs text-gray-400 hover:text-red-500 transition px-2 py-1">
                                Revoke
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Vehicles shared with me --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Shared With Me</h3>
            </div>

            @if(empty($sharedWithMe))
                <div class="p-8 text-center">
                    <p class="text-gray-400 text-sm">No vehicles shared with you yet.</p>
                    <p class="text-gray-300 text-xs mt-1">When someone shares a vehicle, it appears here.</p>
                </div>
            @else
                <div class="divide-y divide-gray-50">
                    @foreach($sharedWithMe as $share)
                        <div class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ $share['vehicleNumber'] }}</p>
                                <p class="text-xs text-gray-400">{{ $share['make'] }} {{ $share['model'] }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Owner: <strong>{{ $share['otherPartyName'] ?? $share['otherPartyPhoneNumber'] }}</strong>
                                </p>
                                <p class="text-xs text-green-600 mt-0.5">● Active</p>
                            </div>
                            <a href="/dashboard" class="text-xs text-[#FA6908] hover:underline">View on map →</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ================================================================
         INVITE MODAL
    ================================================================ --}}
    <div id="invite-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40" onclick="closeInviteModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Invite to View Vehicle</h3>
                    <button onclick="closeInviteModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <p class="text-sm text-gray-500">The invited person must have a ShaloTrack account. They'll receive an invite to view this vehicle's live location.</p>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Vehicle</label>
                        <select id="invite-vehicle" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]">
                            <option value="">Select a vehicle</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle['vehicleId'] }}">{{ $vehicle['vehicleNumber'] }} — {{ $vehicle['make'] }} {{ $vehicle['model'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Phone Number</label>
                        <div class="flex rounded-lg border border-gray-200 overflow-hidden focus-within:ring-2 focus-within:ring-[#FA6908]">
                            <span class="px-3 py-2 bg-gray-50 text-gray-500 text-sm border-r border-gray-200">🇱🇰 +94</span>
                            <input type="tel" id="invite-phone" placeholder="071 234 5678"
                                   class="flex-1 px-3 py-2 text-sm outline-none" />
                        </div>
                    </div>
                    <p id="invite-error" class="text-red-600 text-sm hidden"></p>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
                    <button onclick="closeInviteModal()"
                            class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50">Cancel</button>
                    <button id="invite-btn" onclick="submitInvite()"
                            class="flex-1 py-2 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600">Send Invite</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Revoke confirm modal --}}
    <div id="revoke-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40" onclick="closeRevokeModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
                <h3 class="font-semibold text-gray-800 mb-2">Revoke Access</h3>
                <p class="text-sm text-gray-500 mb-6">
                    Remove <strong id="revoke-person"></strong>'s access to <strong id="revoke-vehicle"></strong>?
                </p>
                <input type="hidden" id="revoke-id" />
                <p id="revoke-error" class="text-red-600 text-sm mb-4 hidden"></p>
                <div class="flex gap-3">
                    <button onclick="closeRevokeModal()" class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50">Cancel</button>
                    <button id="revoke-btn" onclick="submitRevoke()" class="flex-1 py-2 bg-red-500 text-white text-sm font-semibold rounded-lg hover:bg-red-600">Revoke</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function showEl(id)  { document.getElementById(id).classList.remove('hidden'); }
    function hideEl(id)  { document.getElementById(id).classList.add('hidden'); }
    function showErr(id, msg) { const el = document.getElementById(id); el.textContent = msg; el.classList.remove('hidden'); }
    function hideErr(id) { document.getElementById(id).classList.add('hidden'); }
    function setBtn(id, loading, label) { const btn = document.getElementById(id); btn.disabled = loading; btn.textContent = loading ? 'Please wait...' : label; }

    async function apiFetch(url, method, body = null) {
        const opts = { method, credentials: 'include', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } };
        if (body) opts.body = JSON.stringify(body);
        const res  = await fetch(url, opts);
        const data = await res.json().catch(() => ({}));
        return { ok: res.ok, data };
    }

    // ---- Invite modal ----
    function openInviteModal() { showEl('invite-modal'); }
    function closeInviteModal() { hideEl('invite-modal'); hideErr('invite-error'); document.getElementById('invite-phone').value = ''; document.getElementById('invite-vehicle').value = ''; }

    async function submitInvite() {
        hideErr('invite-error');
        const vehicleId = document.getElementById('invite-vehicle').value;
        const phone     = document.getElementById('invite-phone').value.trim();
        if (!vehicleId) { showErr('invite-error', 'Please select a vehicle.'); return; }
        if (!phone)     { showErr('invite-error', 'Please enter a phone number.'); return; }

        setBtn('invite-btn', true, 'Send Invite');
        const { ok, data } = await apiFetch('/sharing', 'POST', { vehicleId, phoneNumber: phone });
        setBtn('invite-btn', false, 'Send Invite');

        if (ok) { closeInviteModal(); window.location.reload(); }
        else    { showErr('invite-error', data.message ?? 'Failed to send invite.'); }
    }

    // ---- Accept/Decline pending invite ----
    async function respondInvite(shareId, accept) {
        const url = accept ? `/sharing/${shareId}/accept` : `/sharing/${shareId}/decline`;
        const { ok, data } = await apiFetch(url, 'POST');
        if (ok) {
            document.getElementById(`invite-${shareId}`)?.remove();
            window.location.reload();
        } else {
            alert(data.message ?? 'Failed to respond to invite.');
        }
    }

    // ---- Revoke modal ----
    function confirmRevoke(shareId, vehicle, person) {
        document.getElementById('revoke-id').value = shareId;
        document.getElementById('revoke-vehicle').textContent = vehicle;
        document.getElementById('revoke-person').textContent = person;
        hideErr('revoke-error');
        showEl('revoke-modal');
    }
    function closeRevokeModal() { hideEl('revoke-modal'); }

    async function submitRevoke() {
        const id = document.getElementById('revoke-id').value;
        setBtn('revoke-btn', true, 'Revoke');
        const { ok, data } = await apiFetch(`/sharing/${id}`, 'DELETE');
        setBtn('revoke-btn', false, 'Revoke');
        if (ok) { closeRevokeModal(); document.getElementById(`share-${id}`)?.remove(); window.location.reload(); }
        else    { showErr('revoke-error', data.message ?? 'Failed to revoke.'); }
    }
</script>
@endpush