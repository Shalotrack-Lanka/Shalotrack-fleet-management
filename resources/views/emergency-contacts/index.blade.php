@extends('layouts.app')
@section('title', 'Emergency Contacts — ShaloTrack Fleet')
@section('page-title', 'Emergency Contacts')

@section('content')

    @if($error)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ $error }}
        </div>
    @endif

    <div class="max-w-2xl">

        <div class="flex items-center justify-between mb-6">
            <p class="text-sm text-gray-500">{{ count($contacts) }} contact(s)</p>
            <button onclick="openAddModal()"
                    class="flex items-center gap-2 px-4 py-2 bg-[#FA6908] hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Contact
            </button>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-amber-50">
                <p class="text-sm text-amber-700 flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Emergency contacts are notified when an SOS alert is triggered from the mobile app.
                </p>
            </div>

            @if(empty($contacts))
                <div class="p-12 text-center">
                    <div class="w-14 h-14 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <p class="text-gray-400 font-medium mb-1">No emergency contacts</p>
                    <p class="text-gray-300 text-sm">Add contacts who should be notified in an emergency.</p>
                </div>
            @else
                <div class="divide-y divide-gray-50">
                    @foreach($contacts as $contact)
                        <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition"
                             id="contact-{{ $contact['emergencyContactId'] }}">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-red-500 font-bold text-sm">{{ strtoupper(substr($contact['name'] ?? 'U', 0, 1)) }}</span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800 text-sm">{{ $contact['name'] }}</p>
                                    <p class="text-xs text-gray-400">{{ $contact['phoneNumber'] }} · {{ $contact['relationship'] ?? 'Other' }}</p>
                                </div>
                            </div>
                            <button onclick="confirmDelete('{{ $contact['emergencyContactId'] }}', '{{ $contact['name'] }}')"
                                    class="text-gray-300 hover:text-red-500 transition p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Add Modal --}}
    <div id="add-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40" onclick="closeAddModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Add Emergency Contact</h3>
                    <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Full Name *</label>
                        <input type="text" id="add-name" placeholder="John Silva"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Phone Number *</label>
                        <div class="flex rounded-lg border border-gray-200 overflow-hidden focus-within:ring-2 focus-within:ring-[#FA6908]">
                            <span class="px-3 py-2 bg-gray-50 text-gray-500 text-sm border-r border-gray-200">🇱🇰 +94</span>
                            <input type="tel" id="add-phone" placeholder="071 234 5678"
                                   class="flex-1 px-3 py-2 text-sm outline-none" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Relationship</label>
                        <select id="add-relationship" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]">
                            <option value="Family">Family</option>
                            <option value="Spouse">Spouse</option>
                            <option value="Parent">Parent</option>
                            <option value="Sibling">Sibling</option>
                            <option value="Friend">Friend</option>
                            <option value="Colleague">Colleague</option>
                            <option value="Other" selected>Other</option>
                        </select>
                    </div>
                    <p id="add-error" class="text-red-600 text-sm hidden"></p>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
                    <button onclick="closeAddModal()" class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50">Cancel</button>
                    <button id="add-btn" onclick="submitAdd()" class="flex-1 py-2 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600">Add Contact</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div id="delete-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40" onclick="closeDeleteModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
                <h3 class="font-semibold text-gray-800 mb-2">Remove Contact</h3>
                <p class="text-sm text-gray-500 mb-6">Remove <strong id="delete-name"></strong> from emergency contacts?</p>
                <input type="hidden" id="delete-id" />
                <p id="delete-error" class="text-red-600 text-sm mb-4 hidden"></p>
                <div class="flex gap-3">
                    <button onclick="closeDeleteModal()" class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm rounded-lg">Cancel</button>
                    <button id="delete-btn" onclick="submitDelete()" class="flex-1 py-2 bg-red-500 text-white text-sm font-semibold rounded-lg hover:bg-red-600">Remove</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    function showEl(id) { document.getElementById(id).classList.remove('hidden'); }
    function hideEl(id) { document.getElementById(id).classList.add('hidden'); }
    function showErr(id, msg) { const el = document.getElementById(id); el.textContent = msg; el.classList.remove('hidden'); }
    function setBtn(id, loading, label) { const b = document.getElementById(id); b.disabled = loading; b.textContent = loading ? 'Please wait...' : label; }

    async function apiFetch(url, method, body = null) {
        const opts = { method, credentials: 'include', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } };
        if (body) opts.body = JSON.stringify(body);
        const res = await fetch(url, opts);
        return { ok: res.ok, data: await res.json().catch(() => ({})) };
    }

    function openAddModal() { showEl('add-modal'); document.getElementById('add-name').focus(); }
    function closeAddModal() { hideEl('add-modal'); hideEl('add-error'); ['add-name','add-phone'].forEach(id => document.getElementById(id).value = ''); }

    async function submitAdd() {
        hideEl('add-error');
        const name  = document.getElementById('add-name').value.trim();
        const phone = document.getElementById('add-phone').value.trim();
        const rel   = document.getElementById('add-relationship').value;
        if (!name)  { showErr('add-error', 'Name is required.'); return; }
        if (!phone) { showErr('add-error', 'Phone number is required.'); return; }

        // Normalise phone
        const digits = phone.replace(/\D/g,'');
        let e164 = phone;
        if (digits.length === 9)  e164 = `+94${digits}`;
        if (digits.length === 10 && digits.startsWith('0')) e164 = `+94${digits.slice(1)}`;

        setBtn('add-btn', true, 'Add Contact');
        const { ok, data } = await apiFetch('/emergency-contacts', 'POST', { name, phoneNumber: e164, relationship: rel });
        setBtn('add-btn', false, 'Add Contact');

        if (ok) { closeAddModal(); window.location.reload(); }
        else    { showErr('add-error', data.message ?? 'Failed to add contact.'); }
    }

    function confirmDelete(id, name) {
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-name').textContent = name;
        hideEl('delete-error');
        showEl('delete-modal');
    }
    function closeDeleteModal() { hideEl('delete-modal'); }

    async function submitDelete() {
        const id = document.getElementById('delete-id').value;
        setBtn('delete-btn', true, 'Remove');
        const { ok, data } = await apiFetch(`/emergency-contacts/${id}`, 'DELETE');
        setBtn('delete-btn', false, 'Remove');
        if (ok) { closeDeleteModal(); document.getElementById(`contact-${id}`)?.remove(); window.location.reload(); }
        else    { showErr('delete-error', data.message ?? 'Failed to remove.'); }
    }
</script>
@endpush