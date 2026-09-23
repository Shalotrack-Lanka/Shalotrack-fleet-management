@extends('layouts.app')

@section('title', 'Complaints — ShaloTrack Fleet')
@section('page-title', 'Complaints')

@section('content')

@if($error)
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    {{ $error }}
</div>
@endif

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-800">My Complaints</h3>
        <button onclick="openFileModal()"
            class="flex items-center gap-1.5 px-3 py-1.5 bg-[#FA6908] text-white text-xs font-semibold rounded-lg hover:bg-orange-600 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            File Complaint
        </button>
    </div>

    @if(empty($complaints))
    <div class="p-8 text-center">
        <p class="text-gray-400 text-sm">You haven't filed any complaints.</p>
        <p class="text-gray-300 text-xs mt-1">Report a device issue, billing question or app bug here.</p>
    </div>
    @else
    <div class="divide-y divide-gray-50">
        @foreach($complaints as $complaint)
        @php
        $categoryLabels = ['Device Issue', 'Billing', 'App Bug', 'Other'];
        $category = $categoryLabels[$complaint['category'] ?? 3] ?? 'Other';

        $statusMeta = [
        0 => ['label' => 'With Dealer', 'class' => 'text-amber-500'],
        1 => ['label' => 'With Admin', 'class' => 'text-blue-500'],
        2 => ['label' => 'Resolved', 'class' => 'text-green-600'],
        3 => ['label' => 'Closed', 'class' => 'text-gray-400'],
        ];
        $status = $statusMeta[$complaint['status'] ?? 0] ?? $statusMeta[0];
        $replyCount = count($complaint['replies'] ?? []);
        @endphp
        <a href="/complaints/{{ $complaint['complaintId'] }}"
            class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition">
            <div>
                <p class="text-sm font-semibold text-gray-800">
                    {{ $category }} — {{ $complaint['vehicleNumber'] ?? 'Vehicle' }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5 line-clamp-1 max-w-md">
                    {{ $complaint['description'] ?? '' }}
                </p>
                <p class="text-xs mt-1">
                    <span class="{{ $status['class'] }}">● {{ $status['label'] }}</span>
                    @if($replyCount > 0)
                    <span class="text-gray-300 mx-1">·</span>
                    <span class="text-gray-400">{{ $replyCount }} {{ $replyCount === 1 ? 'reply' : 'replies' }}</span>
                    @endif
                </p>
            </div>
            <span class="text-xs text-[#FA6908]">View thread →</span>
        </a>
        @endforeach
    </div>
    @endif
</div>

{{-- ================================================================
         FILE COMPLAINT MODAL
    ================================================================ --}}
<div id="file-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40" onclick="closeFileModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">File a Complaint</h3>
                <button onclick="closeFileModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <p class="text-sm text-gray-500">Complaints can only be filed against vehicles you own.</p>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Vehicle</label>
                    <select id="file-vehicle" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]">
                        <option value="">Select a vehicle</option>
                        @foreach($vehicles as $vehicle)
                        <option value="{{ $vehicle['vehicleId'] }}">{{ $vehicle['vehicleNumber'] }} — {{ $vehicle['make'] }} {{ $vehicle['model'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Category</label>
                    <select id="file-category" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]">
                        <option value="0">Device Issue</option>
                        <option value="1">Billing</option>
                        <option value="2">App Bug</option>
                        <option value="3">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Description</label>
                    <textarea id="file-description" rows="4" maxlength="2000" placeholder="Describe the issue..."
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] resize-none"></textarea>
                </div>
                <p id="file-error" class="text-red-600 text-sm hidden"></p>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
                <button onclick="closeFileModal()"
                    class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50">Cancel</button>
                <button id="file-btn" onclick="submitComplaint()"
                    class="flex-1 py-2 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600">Submit</button>
            </div>
        </div>
    </div>
</div>

<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function showEl(id) {
        document.getElementById(id)?.classList.remove('hidden');
    }

    function hideEl(id) {
        document.getElementById(id)?.classList.add('hidden');
    }

    function showErr(id, msg) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = msg;
            el.classList.remove('hidden');
        }
    }

    function hideErr(id) {
        document.getElementById(id)?.classList.add('hidden');
    }

    function setBtn(id, loading, label) {
        const btn = document.getElementById(id);
        if (!btn) return;
        btn.disabled = loading;
        btn.textContent = loading ? 'Please wait…' : label;
    }

    async function apiFetch(url, method, body = null) {
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
                status: 401,
                data: {}
            };
        }
        const data = await res.json().catch(() => ({}));
        return {
            ok: res.ok,
            data
        };
    }

    function openFileModal() {
        showEl('file-modal');
    }

    function closeFileModal() {
        hideEl('file-modal');
        hideErr('file-error');
        document.getElementById('file-vehicle').value = '';
        document.getElementById('file-category').value = '0';
        document.getElementById('file-description').value = '';
    }

    async function submitComplaint() {
        hideErr('file-error');
        const vehicleId = document.getElementById('file-vehicle').value;
        const category = parseInt(document.getElementById('file-category').value, 10);
        const description = document.getElementById('file-description').value.trim();

        if (!vehicleId) {
            showErr('file-error', 'Please select a vehicle.');
            return;
        }
        if (!description) {
            showErr('file-error', 'Please describe the issue.');
            return;
        }

        setBtn('file-btn', true, 'Submit');
        const {
            ok,
            data
        } = await apiFetch('/complaints', 'POST', {
            vehicleId,
            category,
            description
        });
        setBtn('file-btn', false, 'Submit');

        if (ok) {
            closeFileModal();
            window.location.reload();
        } else {
            showErr('file-error', data.message ?? 'Failed to submit complaint.');
        }
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') hideEl('file-modal');
    });
</script>

@endsection