@extends('layouts.app')

@section('title', 'Complaint — ShaloTrack Fleet')
@section('page-title', 'Complaint Detail')

@section('content')

    @if($error)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ $error }}
        </div>
        <a href="/complaints" class="text-sm text-[#FA6908] hover:underline">← Back to Complaints</a>
    @elseif($complaint)
        @php
            $categoryLabels = ['Device Issue', 'Billing', 'App Bug', 'Other'];
            $category = $categoryLabels[$complaint['category'] ?? 3] ?? 'Other';

            $statusMeta = [
                0 => ['label' => 'With Dealer', 'class' => 'text-amber-500'],
                1 => ['label' => 'With Admin',  'class' => 'text-blue-500'],
                2 => ['label' => 'Resolved',    'class' => 'text-green-600'],
                3 => ['label' => 'Closed',      'class' => 'text-gray-400'],
            ];
            $status = $statusMeta[$complaint['status'] ?? 0] ?? $statusMeta[0];
            $isClosed = in_array($complaint['status'] ?? 0, [2, 3], true);

            $authorMeta = [
                0 => ['label' => 'You',    'align' => 'justify-end', 'bubble' => 'bg-[#FA6908] text-white'],
                1 => ['label' => 'Dealer', 'align' => 'justify-start', 'bubble' => 'bg-gray-100 text-gray-800'],
                2 => ['label' => 'Admin',  'align' => 'justify-start', 'bubble' => 'bg-blue-50 text-blue-900'],
            ];
        @endphp

        <a href="/complaints" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-gray-600 mb-4">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Complaints
        </a>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden" id="complaint-root"
             data-complaint-id="{{ $complaint['complaintId'] }}"
             data-reply-count="{{ count($complaint['replies'] ?? []) }}">

            <div class="px-6 py-4 border-b border-gray-100">
                <p class="text-sm font-semibold text-gray-800">{{ $category }} — {{ $complaint['vehicleNumber'] ?? 'Vehicle' }}</p>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $complaint['make'] ?? '' }} {{ $complaint['model'] ?? '' }}
                    @if(!empty($complaint['dealerName']))
                        · Dealer: {{ $complaint['dealerName'] }}
                    @endif
                </p>
                <p class="text-xs mt-1"><span class="{{ $status['class'] }}">● {{ $status['label'] }}</span></p>
            </div>

            {{-- Original complaint description --}}
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ $complaint['description'] ?? '' }}</p>
                <p class="text-xs text-gray-400 mt-2">Filed {{ $complaint['createdAt'] ?? '' }}</p>
            </div>

            {{-- Reply thread --}}
            <div id="reply-thread" class="px-6 py-4 space-y-3 max-h-[420px] overflow-y-auto">
                @forelse($complaint['replies'] ?? [] as $reply)
                    @php $meta = $authorMeta[$reply['authorType'] ?? 2] ?? $authorMeta[2]; @endphp
                    <div class="flex {{ $meta['align'] }}">
                        <div class="max-w-[75%] rounded-xl px-4 py-2 {{ $meta['bubble'] }}">
                            <p class="text-xs font-semibold opacity-75 mb-0.5">{{ $reply['authorName'] ?? $meta['label'] }}</p>
                            <p class="text-sm whitespace-pre-line">{{ $reply['message'] ?? '' }}</p>
                            <p class="text-[10px] opacity-60 mt-1">{{ $reply['createdAt'] ?? '' }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400 text-sm text-center py-6">No replies yet.</p>
                @endforelse
            </div>

            {{-- Composer --}}
            <div class="px-6 py-4 border-t border-gray-100">
                @if($isClosed)
                    <p class="text-sm text-gray-400 text-center py-2">This complaint is {{ strtolower($status['label']) }} and can no longer receive replies.</p>
                @else
                    <div class="flex gap-3">
                        <input type="text" id="reply-message" placeholder="Type a reply..."
                               class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]" />
                        <button id="reply-btn" onclick="submitReply()"
                                class="px-4 py-2 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition">
                            Send
                        </button>
                    </div>
                    <p id="reply-error" class="text-red-600 text-sm mt-2 hidden"></p>
                @endif
            </div>
        </div>
    @endif

@endsection

@push('scripts')
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const root = document.getElementById('complaint-root');
    const complaintId = root?.dataset.complaintId;
    let lastReplyCount = root ? parseInt(root.dataset.replyCount, 10) : 0;

    function showErr(id, msg) { const el = document.getElementById(id); el.textContent = msg; el.classList.remove('hidden'); }
    function hideErr(id) { document.getElementById(id)?.classList.add('hidden'); }
    function setBtn(id, loading, label) { const btn = document.getElementById(id); if (!btn) return; btn.disabled = loading; btn.textContent = loading ? 'Sending...' : label; }

    async function apiFetch(url, method, body = null) {
        const opts = { method, credentials: 'include', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } };
        if (body) opts.body = JSON.stringify(body);
        const res  = await fetch(url, opts);
        const data = await res.json().catch(() => ({}));
        return { ok: res.ok, data };
    }

    async function submitReply() {
        hideErr('reply-error');
        const input = document.getElementById('reply-message');
        const message = input.value.trim();
        if (!message) { showErr('reply-error', 'Please type a message.'); return; }

        setBtn('reply-btn', true, 'Send');
        const { ok, data } = await apiFetch(`/complaints/${complaintId}/reply`, 'POST', { message });
        setBtn('reply-btn', false, 'Send');

        if (ok) { input.value = ''; window.location.reload(); }
        else    { showErr('reply-error', data.message ?? 'Failed to send reply.'); }
    }

    document.getElementById('reply-message')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') submitReply();
    });

    // Foreground-only poll, chat-like screen so a shorter interval than the list view.
    // Only reload when the reply count actually changes — avoids yanking the scroll
    // position or the in-progress draft on every silent poll.
    if (complaintId) {
        setInterval(async () => {
            if (document.hidden) return;
            const { ok, data } = await apiFetch(`/complaints/${complaintId}`, 'GET').catch(() => ({ ok: false }));
            if (!ok) return;
            const complaint = data.data ?? data;
            const count = (complaint.replies ?? []).length;
            if (count !== lastReplyCount) {
                window.location.reload();
            }
        }, 12000);
    }
</script>
@endpush