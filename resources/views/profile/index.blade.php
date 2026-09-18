@extends('layouts.app')

@section('title', 'Profile — ShaloTrack Fleet')
@section('page-title', 'Profile')

@section('content')

    @if($error)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ $error }}
        </div>
    @endif

    @if($profile)
        <div class="max-w-2xl">

            {{-- Profile card --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden mb-6">

                {{-- Header --}}
                <div class="px-6 py-6 border-b border-gray-100 flex items-center gap-5">
                    <div class="w-16 h-16 rounded-full bg-[#FA6908] flex items-center justify-center text-white text-2xl font-bold flex-shrink-0">
                        {{ strtoupper(substr($profile['fullName'] ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-800">{{ $profile['fullName'] ?? '—' }}</p>
                        <p class="text-sm text-gray-400">{{ $profile['phoneNumber'] ?? '—' }}</p>
                        <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full
                            {{ strtolower($profile['accountStatus'] ?? '') === 'active' ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-500' }}">
                            {{ match((int)($profile['accountStatus'] ?? 0)) { 0 => 'Active', 1 => 'Suspended', 2 => 'Inactive', default => 'Unknown' } }}
                        </span>
                    </div>
                </div>

                {{-- Details --}}
                <div class="px-6 py-5 space-y-4">
                    <dl class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <dt class="text-gray-400">Full Name</dt>
                            <dd class="text-gray-700 font-medium">{{ $profile['fullName'] ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <dt class="text-gray-400">Email</dt>
                            <dd class="text-gray-700">{{ $profile['email'] ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <dt class="text-gray-400">Phone</dt>
                            <dd class="text-gray-700">{{ $profile['phoneNumber'] ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <dt class="text-gray-400">NIC Number</dt>
                            <dd class="text-gray-700 font-mono text-xs">{{ $profile['nicNumber'] ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <dt class="text-gray-400">Address</dt>
                            <dd class="text-gray-700 text-right max-w-xs">{{ $profile['address'] ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <dt class="text-gray-400">Vehicles</dt>
                            <dd class="text-gray-700">{{ $profile['vehicleCount'] ?? 0 }}</dd>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <dt class="text-gray-400">Customer ID</dt>
                            <dd class="text-gray-400 font-mono text-xs">{{ $profile['customerId'] ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Edit button --}}
                <div class="px-6 py-4 border-t border-gray-100">
                    <button onclick="openEditModal()"
                            class="px-5 py-2 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition">
                        Edit Profile
                    </button>
                </div>
            </div>

            {{-- Account actions --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Account</h3>
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit"
                            class="flex items-center gap-2 px-4 py-2 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- Edit Profile Modal --}}
    <div id="edit-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40" onclick="closeEditModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Edit Profile</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Full Name *</label>
                        <input type="text" id="edit-fullName"
                               value="{{ $profile['fullName'] ?? '' }}"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Phone Number *</label>
                        <input type="tel" id="edit-phoneNumber"
                               value="{{ $profile['phoneNumber'] ?? '' }}"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Address</label>
                        <textarea id="edit-address" rows="2"
                                  class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] resize-none">{{ $profile['address'] ?? '' }}</textarea>
                    </div>
                    <p id="edit-error" class="text-red-600 text-sm hidden"></p>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
                    <button onclick="closeEditModal()"
                            class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50">Cancel</button>
                    <button id="edit-btn" onclick="submitEdit()"
                            class="flex-1 py-2 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function openEditModal()  { document.getElementById('edit-modal').classList.remove('hidden'); }
    function closeEditModal() { document.getElementById('edit-modal').classList.add('hidden'); document.getElementById('edit-error').classList.add('hidden'); }

    async function submitEdit() {
        const errEl = document.getElementById('edit-error');
        errEl.classList.add('hidden');

        const fullName    = document.getElementById('edit-fullName').value.trim();
        const phoneNumber = document.getElementById('edit-phoneNumber').value.trim();
        const address     = document.getElementById('edit-address').value.trim();

        if (!fullName)    { errEl.textContent = 'Full name is required.'; errEl.classList.remove('hidden'); return; }
        if (!phoneNumber) { errEl.textContent = 'Phone number is required.'; errEl.classList.remove('hidden'); return; }

        const btn = document.getElementById('edit-btn');
        btn.disabled = true; btn.textContent = 'Saving...';

        const res  = await fetch('/profile', {
            method: 'PUT',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ fullName, phoneNumber, address: address || null }),
        });
        const data = await res.json().catch(() => ({}));

        btn.disabled = false; btn.textContent = 'Save Changes';

        if (data.success) {
            closeEditModal();
            window.location.reload();
        } else {
            errEl.textContent = data.message ?? 'Failed to update profile.';
            errEl.classList.remove('hidden');
        }
    }
</script>
@endpush