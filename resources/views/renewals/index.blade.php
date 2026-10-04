@extends('layouts.app')

@section('title', 'Renewals — ShaloTrack Fleet')
@section('page-title', 'Renewals')

@section('content')

@php
    $durationLabels = [
        'ThreeMonths' => '3 Months', 'SixMonths' => '6 Months', 'OneYear' => '1 Year',
        'TwoYears' => '2 Years', 'ThreeYears' => '3 Years', 'SixYears' => '6 Years',
    ];
    $statusMeta = [
        'AwaitingSlip'  => ['label' => 'Waiting for your slip', 'class' => 'text-amber-600 bg-amber-50'],
        'PendingReview' => ['label' => 'Under review',          'class' => 'text-blue-600 bg-blue-50'],
        'Approved'      => ['label' => 'Approved',              'class' => 'text-green-600 bg-green-50'],
        'Rejected'      => ['label' => 'Not approved',          'class' => 'text-red-600 bg-red-50'],
        'Cancelled'     => ['label' => 'Cancelled',             'class' => 'text-gray-500 bg-gray-100'],
    ];
    $lkr = function ($amount) {
        $amount = (float) $amount;
        return 'Rs. ' . number_format($amount, abs($amount - round($amount)) < 0.005 ? 0 : 2);
    };
    $hasPackages = !empty($packages);
@endphp

@if($error)
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    {{ $error }}
    <button onclick="window.location.reload()" class="ml-auto text-red-600 underline text-sm">Retry</button>
</div>
@endif

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <h3 class="font-semibold text-gray-800">Subscription Renewals</h3>
            <p class="text-xs text-gray-400 mt-0.5">Pay by bank transfer, then upload a photo or PDF of the slip.</p>
        </div>
        @if(!empty($vehicles))
        <button onclick="openNewModal()"
            class="flex items-center gap-1.5 px-3 py-2 bg-[#FA6908] text-white text-xs font-semibold rounded-lg hover:bg-orange-600 transition flex-shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="hidden sm:inline">Renew Subscription</span>
            <span class="sm:hidden">Renew</span>
        </button>
        @endif
    </div>

    @if(empty($renewals))
    <div class="p-10 text-center">
        <p class="text-gray-400 text-sm">No renewal requests yet.</p>
        @if(empty($vehicles))
        <p class="text-gray-300 text-xs mt-1">Add a vehicle first, then you can renew its subscription here.</p>
        @else
        <p class="text-gray-300 text-xs mt-1">Use "Renew Subscription" to extend a vehicle's tracking.</p>
        @endif
    </div>
    @else
    <div class="divide-y divide-gray-50">
        @foreach($renewals as $r)
        @php
            $status   = $r['status'] ?? '';
            $meta     = $statusMeta[$status] ?? ['label' => $status, 'class' => 'text-gray-500 bg-gray-100'];
            $isOpen   = in_array($status, ['AwaitingSlip', 'PendingReview'], true);
            $hasSlip  = (bool) ($r['hasSlip'] ?? false);
            $dur      = $durationLabels[$r['duration'] ?? ''] ?? ($r['duration'] ?? '');
            $created  = null;
            try { $created = !empty($r['createdAt']) ? \Carbon\Carbon::parse($r['createdAt'])->format('d M Y') : null; } catch (\Throwable $e) {}
            $note = match ($status) {
                'Rejected'      => !empty($r['decisionReason']) ? $r['decisionReason'] : 'Your request was not approved.',
                'AwaitingSlip'  => 'Pay by bank transfer, then upload a photo of the slip.',
                'PendingReview' => 'We have your slip and will confirm shortly.',
                'Approved'      => 'Your subscription has been renewed.',
                default         => null,
            };
        @endphp
        <div class="px-4 md:px-6 py-4" id="renewal-{{ $r['renewalRequestId'] }}">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $r['vehicleNumber'] ?? 'Vehicle' }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $dur }}
                        @if(isset($r['amountLkr']) && $r['amountLkr'] !== null) · {{ $lkr($r['amountLkr']) }} @endif
                        @if($created) · {{ $created }} @endif
                    </p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full flex-shrink-0 {{ $meta['class'] }}">{{ $meta['label'] }}</span>
            </div>
            @if($note)
            <p class="text-xs mt-2 {{ $status === 'Rejected' ? 'text-red-500' : 'text-gray-500' }}">{{ $note }}</p>
            @endif
            @if(!empty($r['paymentReference']))
            <p class="text-xs text-gray-400 mt-1">Reference: <span class="font-mono">{{ $r['paymentReference'] }}</span></p>
            @endif
            @if($isOpen)
            <div class="flex items-center gap-4 mt-3">
                <button onclick="pickSlipFor('{{ $r['renewalRequestId'] }}')"
                    class="text-sm font-semibold text-[#FA6908] hover:text-orange-700 transition">
                    {{ $hasSlip ? 'Replace slip' : 'Upload slip' }}
                </button>
                <button onclick="confirmCancel('{{ $r['renewalRequestId'] }}', @js($r['vehicleNumber'] ?? 'this vehicle'))"
                    class="text-sm font-medium text-gray-400 hover:text-red-500 transition">
                    Cancel request
                </button>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- Hidden slip picker (JPG / PNG / PDF) --}}
<input type="file" id="slip-input" accept="image/jpeg,image/png,application/pdf" class="hidden" />

{{-- ================================================================
         NEW RENEWAL MODAL
    ================================================================ --}}
<div id="new-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40" onclick="closeNewModal()"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-md relative max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Renew Subscription</h3>
                <button onclick="closeNewModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="px-5 py-5 space-y-4">
                <p class="text-xs text-gray-400">Only vehicles you own can be renewed.</p>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Vehicle</label>
                    <select id="new-vehicle" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]">
                        @foreach($vehicles as $v)
                        <option value="{{ $v['vehicleId'] }}">{{ $v['vehicleNumber'] }} — {{ $v['make'] }} {{ $v['model'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Package</label>
                    <select id="new-duration" onchange="updatePriceLine()" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]">
                        @if($hasPackages)
                            @foreach($packages as $p)
                            <option value="{{ $p['duration'] }}"
                                data-line="{{ $p['positioning'] ?? '' }}{{ !empty($p['warrantyMonths']) ? ' · ' . $p['warrantyMonths'] . ' months warranty' : '' }}"
                                @selected(($p['duration'] ?? '') === 'OneYear')>
                                {{ $p['label'] ?? ($durationLabels[$p['duration']] ?? $p['duration']) }} — {{ $lkr($p['priceLkr']) }}
                            </option>
                            @endforeach
                        @else
                            @foreach($durationLabels as $name => $label)
                            <option value="{{ $name }}" data-line="Our team confirms the amount for this package." @selected($name === 'OneYear')>{{ $label }}</option>
                            @endforeach
                        @endif
                    </select>
                    <p id="new-price-line" class="text-xs text-gray-400 mt-1.5"></p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Payment reference <span class="text-gray-300">(optional)</span></label>
                    <input type="text" id="new-reference" maxlength="100" placeholder="Bank transfer reference"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]" />
                </div>
                <p id="new-error" class="text-red-600 text-sm hidden"></p>
            </div>
            <div class="px-5 py-4 border-t border-gray-100 flex gap-3">
                <button onclick="closeNewModal()"
                    class="flex-1 py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50">Cancel</button>
                <button id="new-btn" onclick="submitNewRenewal()"
                    class="flex-1 py-2.5 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600">Continue</button>
            </div>
        </div>
    </div>
</div>

{{-- ================================================================
         REQUEST CREATED MODAL (amount + bank instructions)
    ================================================================ --}}
<div id="created-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-md relative p-6">
            <h3 class="font-semibold text-gray-800 mb-2">Request created</h3>
            <p id="created-amount" class="text-lg font-bold text-[#021F4A] mb-2 hidden"></p>
            <p id="created-text" class="text-sm text-gray-500 whitespace-pre-line mb-6"></p>
            <div class="flex gap-3">
                <button onclick="window.location.reload()"
                    class="flex-1 py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50">Later</button>
                <button id="created-upload-btn"
                    class="flex-1 py-2.5 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600">Upload slip now</button>
            </div>
        </div>
    </div>
</div>

{{-- ================================================================
         CANCEL CONFIRM MODAL
    ================================================================ --}}
<div id="cancel-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40" onclick="closeCancelModal()"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-sm relative p-6 text-center">
            <h3 class="font-semibold text-gray-800 mb-1">Cancel request?</h3>
            <p class="text-sm text-gray-500 mb-6">The renewal request for <strong id="cancel-name"></strong> will be cancelled.</p>
            <input type="hidden" id="cancel-id" />
            <p id="cancel-error" class="text-red-600 text-sm mb-4 hidden"></p>
            <div class="flex gap-3">
                <button onclick="closeCancelModal()"
                    class="flex-1 py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50">Keep it</button>
                <button id="cancel-btn" onclick="submitCancel()"
                    class="flex-1 py-2.5 bg-red-500 text-white text-sm font-semibold rounded-lg hover:bg-red-600">Cancel request</button>
            </div>
        </div>
    </div>
</div>

{{-- Busy overlay + toast --}}
<div id="busy" class="fixed inset-0 z-[60] hidden bg-black/40 flex items-center justify-center">
    <div class="bg-white rounded-xl px-6 py-4 text-sm text-gray-700 shadow-xl" id="busy-text">Please wait…</div>
</div>
<div id="toast" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[70] hidden max-w-[90vw] px-4 py-3 rounded-lg bg-gray-900 text-white text-sm shadow-xl"></div>

<script>
    const CSRF = '{{ csrf_token() }}';
    const MAX_SLIP_BYTES = 2 * 1024 * 1024;
    const MAX_EDGE_PX = 2000;
    let slipRenewalId = null;

    // ── Helpers ──────────────────────────────────────────────────────────────
    function showEl(id) { document.getElementById(id)?.classList.remove('hidden'); }
    function hideEl(id) { document.getElementById(id)?.classList.add('hidden'); }

    function showErr(id, msg) {
        const el = document.getElementById(id);
        if (el) { el.textContent = msg; el.classList.remove('hidden'); }
    }
    function hideErr(id) { document.getElementById(id)?.classList.add('hidden'); }

    function toast(msg, ms = 4000) {
        const el = document.getElementById('toast');
        el.textContent = msg;
        el.classList.remove('hidden');
        clearTimeout(toast._t);
        toast._t = setTimeout(() => el.classList.add('hidden'), ms);
    }

    function busy(text) {
        document.getElementById('busy-text').textContent = text;
        showEl('busy');
    }
    function unbusy() { hideEl('busy'); }

    function fmtLkr(n) {
        const v = Number(n);
        const whole = Math.abs(v - Math.round(v)) < 0.005;
        return 'Rs. ' + v.toLocaleString('en-US', {
            minimumFractionDigits: whole ? 0 : 2,
            maximumFractionDigits: whole ? 0 : 2
        });
    }

    async function apiFetch(url, method, body = null) {
        const isForm = body instanceof FormData;
        const opts = {
            method,
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                ...(isForm ? {} : { 'Content-Type': 'application/json' }),
            },
        };
        if (body) opts.body = isForm ? body : JSON.stringify(body);
        const res = await fetch(url, opts);
        if (res.status === 401) {
            window.location.href = '/login?expired=1';
            return { ok: false, status: 401, data: {} };
        }
        const data = await res.json().catch(() => ({}));
        // Laravel validation errors arrive as { message, errors: { field: [..] } }
        if (!res.ok && data?.errors) {
            const first = Object.values(data.errors)[0];
            if (Array.isArray(first) && first[0]) data.message = first[0];
        }
        return { ok: res.ok, status: res.status, data };
    }

    // ── New renewal ──────────────────────────────────────────────────────────
    function updatePriceLine() {
        const sel = document.getElementById('new-duration');
        const line = sel.options[sel.selectedIndex]?.dataset.line ?? '';
        document.getElementById('new-price-line').textContent = line;
    }

    function openNewModal() {
        hideErr('new-error');
        updatePriceLine();
        showEl('new-modal');
    }
    function closeNewModal() { hideEl('new-modal'); }

    async function submitNewRenewal() {
        hideErr('new-error');
        const vehicleId = document.getElementById('new-vehicle').value;
        const duration = document.getElementById('new-duration').value;
        const ref = document.getElementById('new-reference').value.trim();
        if (!vehicleId || !duration) {
            showErr('new-error', 'Choose a vehicle and a package.');
            return;
        }
        const btn = document.getElementById('new-btn');
        btn.disabled = true;
        const { ok, data } = await apiFetch('/renewals', 'POST', {
            vehicleId,
            duration,
            paymentReference: ref || null,
        });
        btn.disabled = false;
        if (!ok) {
            showErr('new-error', data.message ?? "Couldn't create the renewal request.");
            return;
        }
        closeNewModal();
        const created = data.data ?? {};
        const id = created.renewalRequestId;
        if (!id) { window.location.reload(); return; }

        const amountEl = document.getElementById('created-amount');
        if (created.amountLkr != null) {
            amountEl.textContent = 'Amount to transfer: ' + fmtLkr(created.amountLkr);
            amountEl.classList.remove('hidden');
        } else {
            amountEl.classList.add('hidden');
        }
        document.getElementById('created-text').textContent =
            (created.instructionsMessage && String(created.instructionsMessage).trim())
                ? created.instructionsMessage
                : 'Pay by bank transfer, then upload a photo of the slip.';
        document.getElementById('created-upload-btn').onclick = () => {
            hideEl('created-modal');
            pickSlipFor(id);
        };
        showEl('created-modal');
    }

    // ── Slip upload ──────────────────────────────────────────────────────────
    function pickSlipFor(renewalId) {
        slipRenewalId = renewalId;
        const input = document.getElementById('slip-input');
        input.value = '';
        input.click();
    }

    // Shrink big phone photos to fit the 2 MB cap (same idea as the Android SlipPreparer).
    function shrinkImage(file) {
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => {
                URL.revokeObjectURL(url);
                const scale = Math.min(1, MAX_EDGE_PX / Math.max(img.width, img.height));
                const w = Math.max(1, Math.round(img.width * scale));
                const h = Math.max(1, Math.round(img.height * scale));
                const canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#fff';           // flatten transparency for JPEG
                ctx.fillRect(0, 0, w, h);
                ctx.drawImage(img, 0, 0, w, h);
                let q = 0.9;
                const attempt = () => canvas.toBlob(blob => {
                    if (!blob) return reject(new Error('encode'));
                    if (blob.size <= MAX_SLIP_BYTES || q <= 0.4) {
                        blob.size <= MAX_SLIP_BYTES
                            ? resolve(new File([blob], 'slip.jpg', { type: 'image/jpeg' }))
                            : reject(new Error('too-big'));
                    } else {
                        q -= 0.1;
                        attempt();
                    }
                }, 'image/jpeg', q);
                attempt();
            };
            img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('decode')); };
            img.src = url;
        });
    }

    document.getElementById('slip-input').addEventListener('change', async (e) => {
        const file = e.target.files?.[0];
        const id = slipRenewalId;
        if (!file || !id) return;

        const okTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!okTypes.includes(file.type)) {
            toast('The slip must be a JPG, PNG or PDF.');
            return;
        }

        busy('Preparing your slip…');
        let toSend = file;
        try {
            if (file.type.startsWith('image/') && file.size > MAX_SLIP_BYTES) {
                toSend = await shrinkImage(file);
            } else if (file.size > MAX_SLIP_BYTES) {
                unbusy();
                toast('That PDF is over 2 MB. Please upload a smaller file or a photo of the slip.', 6000);
                return;
            }
        } catch (_) {
            unbusy();
            toast("Couldn't read that image. Try a different photo.");
            return;
        }

        busy('Uploading slip…');
        const form = new FormData();
        form.append('file', toSend);
        const { ok, data } = await apiFetch(`/renewals/${encodeURIComponent(id)}/slip`, 'POST', form);
        unbusy();
        if (ok) {
            toast('Slip uploaded. Our team will review it and renew your subscription.', 2500);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            toast(data.message ?? "Couldn't upload the slip. Please try again.", 6000);
        }
    });

    // ── Cancel ───────────────────────────────────────────────────────────────
    function confirmCancel(id, name) {
        document.getElementById('cancel-id').value = id;
        document.getElementById('cancel-name').textContent = name;
        hideErr('cancel-error');
        showEl('cancel-modal');
    }
    function closeCancelModal() { hideEl('cancel-modal'); }

    async function submitCancel() {
        hideErr('cancel-error');
        const id = document.getElementById('cancel-id').value;
        const btn = document.getElementById('cancel-btn');
        btn.disabled = true;
        const { ok, data } = await apiFetch(`/renewals/${encodeURIComponent(id)}/cancel`, 'POST');
        btn.disabled = false;
        if (ok) { closeCancelModal(); window.location.reload(); }
        else { showErr('cancel-error', data.message ?? "Couldn't cancel the request."); }
    }

    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        ['new-modal', 'cancel-modal'].forEach(hideEl);
    });
</script>

@endsection