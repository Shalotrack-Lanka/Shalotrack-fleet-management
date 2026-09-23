@extends('layouts.app')

@section('title', 'Profile — ShaloTrack Fleet')
@section('page-title', 'My Profile')

@section('content')

@if($error || !$profile)
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    {{ $error ?? 'Could not load your profile. Please refresh.' }}
    <button onclick="window.location.reload()" class="ml-auto text-red-600 underline text-sm">Retry</button>
</div>
@else

@php
// Normalise: controller returns $response['data'] ?? $response — could be array or object
$p = is_array($profile) ? $profile : (is_object($profile) ? (array) $profile : []);
$nameParts = array_filter(explode(' ', trim($p['fullName'] ?? '')));
$initials = implode('', array_map(fn($w) => strtoupper(substr($w, 0, 1)), array_slice($nameParts, 0, 2)));
if (!$initials) $initials = 'U';
$customerId = $p['customerId'] ?? null;
$vehicleCount = intval($p['vehicleCount'] ?? 0);
$fields = ['fullName', 'email', 'phoneNumber', 'nicNumber', 'address'];
$filled = count(array_filter($fields, fn($f) => !empty($p[$f])));
$completeness = (int) round(($filled / count($fields)) * 100);
@endphp

{{-- ── Toast ──────────────────────────────────────────────────────────────── --}}
<div id="toast" class="fixed top-5 right-5 z-50 hidden transition-all duration-300">
    <div id="toast-inner" class="flex items-center gap-3 px-5 py-3 rounded-xl shadow-xl text-sm font-medium min-w-64">
        <span id="toast-icon" class="text-lg"></span>
        <span id="toast-msg"></span>
    </div>
</div>

<div class="max-w-2xl mx-auto space-y-5">

    {{-- ── Hero card ──────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

        {{-- Gradient banner --}}
        <div class="h-28 bg-gradient-to-r from-[#021F4A] via-[#0a3570] to-[#FA6908] relative">
            {{-- Decorative circles --}}
            <div class="absolute right-10 top-4 w-20 h-20 rounded-full bg-white/5"></div>
            <div class="absolute right-24 top-8 w-10 h-10 rounded-full bg-white/5"></div>
        </div>

        {{-- Avatar (overlapping banner) --}}
        <div class="px-6 pb-5">
            <div class="flex items-end justify-between -mt-10 mb-4">
                <div class="relative">
                    <div class="w-20 h-20 rounded-2xl bg-[#FA6908] text-white flex items-center justify-center text-2xl font-bold ring-4 ring-white shadow-lg select-none">
                        {{ $initials }}
                    </div>
                    <span class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-400 border-2 border-white rounded-full" title="Active"></span>
                </div>
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit"
                        class="flex items-center gap-2 px-4 py-2 text-sm text-gray-500 hover:text-red-500 border border-gray-200 hover:border-red-200 rounded-xl transition font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Sign Out
                    </button>
                </form>
            </div>

            <h2 class="text-xl font-bold text-[#021F4A]" id="display-fullName">{{ $p['fullName'] ?? '—' }}</h2>
            <p class="text-sm text-gray-400 mt-0.5">{{ $p['email'] ?? '—' }}</p>

            {{-- Profile completeness --}}
            <div class="mt-4">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-xs font-medium text-gray-400">Profile completeness</span>
                    <span class="text-xs font-bold {{ $completeness === 100 ? 'text-green-500' : 'text-[#FA6908]' }}">{{ $completeness }}%</span>
                </div>
                <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700 {{ $completeness === 100 ? 'bg-green-400' : 'bg-[#FA6908]' }}"
                        style="width: {{ $completeness }}%"></div>
                </div>
                @if($completeness < 100)
                    <p class="text-xs text-gray-400 mt-1">Fill in all your details to complete your profile.</p>
                    @endif
            </div>
        </div>
    </div>

    {{-- ── Stats strip ─────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4 flex items-center gap-4">
            <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-[#FA6908]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 1h8z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 6h4l3 5v5h-2" />
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-[#021F4A]">{{ $vehicleCount }}</p>
                <p class="text-xs text-gray-400 font-medium">{{ $vehicleCount === 1 ? 'Vehicle' : 'Vehicles' }} Registered</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4 flex items-center gap-4">
            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-green-500">Active</p>
                <p class="text-xs text-gray-400 font-medium">Account Status</p>
            </div>
        </div>
    </div>

    {{-- ── Editable info ───────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-50">
            <h3 class="font-semibold text-gray-800 text-sm">Personal Information</h3>
            <p class="text-xs text-gray-400 mt-0.5">Click the pencil icon on any field to edit it.</p>
        </div>

        {{-- Full Name --}}
        <div class="flex items-start justify-between px-6 py-4 border-b border-gray-50 hover:bg-gray-50/50 transition group">
            <div class="flex items-start gap-3 min-w-0 flex-1">
                <div class="w-8 h-8 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-gray-400 font-medium mb-0.5">Full Name</p>
                    {{-- View mode --}}
                    <div id="view-fullName" class="flex items-center gap-2">
                        <p class="text-sm font-semibold text-gray-800" id="text-fullName">{{ $p['fullName'] ?? '—' }}</p>
                    </div>
                    {{-- Edit mode --}}
                    <div id="edit-fullName" class="hidden">
                        <input type="text" id="input-fullName"
                            value="{{ e($p['fullName'] ?? '') }}"
                            class="w-full px-3 py-1.5 border border-[#FA6908] rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]/30 font-semibold" />
                        <p id="err-fullName" class="text-red-500 text-xs mt-1 hidden"></p>
                    </div>
                </div>
            </div>
            <div class="flex gap-1.5 flex-shrink-0 ml-3">
                {{-- Pencil --}}
                <button id="pen-fullName" onclick="startEdit('fullName')"
                    class="opacity-0 group-hover:opacity-100 transition w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 hover:text-[#FA6908]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </button>
                {{-- Save --}}
                <button id="save-fullName" onclick="saveField('fullName')"
                    class="hidden w-7 h-7 flex items-center justify-center rounded-lg bg-green-50 hover:bg-green-100 text-green-600" title="Save">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </button>
                {{-- Cancel --}}
                <button id="cancel-fullName" onclick="cancelEdit('fullName')"
                    class="hidden w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500" title="Cancel">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Phone --}}
        <div class="flex items-start justify-between px-6 py-4 border-b border-gray-50 hover:bg-gray-50/50 transition group">
            <div class="flex items-start gap-3 min-w-0 flex-1">
                <div class="w-8 h-8 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-gray-400 font-medium mb-0.5">Phone Number</p>
                    <div id="view-phoneNumber">
                        <p class="text-sm font-semibold text-gray-800" id="text-phoneNumber">{{ $p['phoneNumber'] ?? '—' }}</p>
                    </div>
                    <div id="edit-phoneNumber" class="hidden">
                        <input type="tel" id="input-phoneNumber"
                            value="{{ e($p['phoneNumber'] ?? '') }}"
                            class="w-full px-3 py-1.5 border border-[#FA6908] rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]/30 font-semibold" />
                        <p id="err-phoneNumber" class="text-red-500 text-xs mt-1 hidden"></p>
                    </div>
                </div>
            </div>
            <div class="flex gap-1.5 flex-shrink-0 ml-3">
                <button id="pen-phoneNumber" onclick="startEdit('phoneNumber')"
                    class="opacity-0 group-hover:opacity-100 transition w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 hover:text-[#FA6908]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </button>
                <button id="save-phoneNumber" onclick="saveField('phoneNumber')"
                    class="hidden w-7 h-7 flex items-center justify-center rounded-lg bg-green-50 hover:bg-green-100 text-green-600" title="Save">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </button>
                <button id="cancel-phoneNumber" onclick="cancelEdit('phoneNumber')"
                    class="hidden w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500" title="Cancel">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Address --}}
        <div class="flex items-start justify-between px-6 py-4 border-b border-gray-50 hover:bg-gray-50/50 transition group">
            <div class="flex items-start gap-3 min-w-0 flex-1">
                <div class="w-8 h-8 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-gray-400 font-medium mb-0.5">Address</p>
                    <div id="view-address">
                        <p class="text-sm font-semibold text-gray-800 leading-relaxed" id="text-address">{{ $p['address'] ?? '—' }}</p>
                    </div>
                    <div id="edit-address" class="hidden">
                        <textarea id="input-address" rows="2"
                            class="w-full px-3 py-1.5 border border-[#FA6908] rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]/30 font-semibold resize-none">{{ e($p['address'] ?? '') }}</textarea>
                        <p id="err-address" class="text-red-500 text-xs mt-1 hidden"></p>
                    </div>
                </div>
            </div>
            <div class="flex gap-1.5 flex-shrink-0 ml-3">
                <button id="pen-address" onclick="startEdit('address')"
                    class="opacity-0 group-hover:opacity-100 transition w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 hover:text-[#FA6908]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </button>
                <button id="save-address" onclick="saveField('address')"
                    class="hidden w-7 h-7 flex items-center justify-center rounded-lg bg-green-50 hover:bg-green-100 text-green-600" title="Save">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </button>
                <button id="cancel-address" onclick="cancelEdit('address')"
                    class="hidden w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500" title="Cancel">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Email — read only --}}
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-50">
            <div class="w-8 h-8 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs text-gray-400 font-medium mb-0.5">Email Address</p>
                <p class="text-sm font-semibold text-gray-800 truncate">{{ $p['email'] ?? '—' }}</p>
            </div>
            <span class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded-lg flex-shrink-0">Read only</span>
        </div>

        {{-- NIC — read only --}}
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-50">
            <div class="w-8 h-8 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs text-gray-400 font-medium mb-0.5">NIC Number</p>
                <p class="text-sm font-semibold text-gray-800 font-mono">{{ $p['nicNumber'] ?? '—' }}</p>
            </div>
            <span class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded-lg flex-shrink-0">Read only</span>
        </div>

        {{-- Customer ID — copyable --}}
        @if($customerId)
        <div class="flex items-center gap-3 px-6 py-4">
            <div class="w-8 h-8 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs text-gray-400 font-medium mb-0.5">Customer ID</p>
                <p class="text-xs font-mono text-gray-600 truncate" id="customer-id-text">{{ $customerId }}</p>
            </div>
            <button onclick="copyCustomerId()"
                id="copy-btn"
                class="flex items-center gap-1.5 text-xs text-gray-400 hover:text-[#FA6908] bg-gray-50 hover:bg-orange-50 border border-gray-100 hover:border-orange-200 px-2.5 py-1.5 rounded-lg transition flex-shrink-0"
                title="Copy to clipboard">
                <svg id="copy-icon" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <span id="copy-label">Copy</span>
            </button>
        </div>
        @endif
    </div>

</div>

@endif

<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const origVal = {}; // track original values for cancel

    // ── Toast ─────────────────────────────────────────────────────────────────
    let toastTimer;

    function showToast(msg, type = 'success') {
        clearTimeout(toastTimer);
        const toast = document.getElementById('toast');
        const inner = document.getElementById('toast-inner');
        const icon = document.getElementById('toast-icon');
        const text = document.getElementById('toast-msg');

        text.textContent = msg;
        icon.textContent = type === 'success' ? '✓' : '✕';
        inner.className = `flex items-center gap-3 px-5 py-3 rounded-xl shadow-xl text-sm font-medium min-w-64 ${
            type === 'success'
                ? 'bg-[#021F4A] text-white'
                : 'bg-red-500 text-white'
        }`;

        toast.classList.remove('hidden');
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-8px)';
        requestAnimationFrame(() => {
            toast.style.transition = 'opacity 0.2s, transform 0.2s';
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        });

        toastTimer = setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-8px)';
            setTimeout(() => toast.classList.add('hidden'), 200);
        }, 3000);
    }

    // ── Inline edit helpers ──────────────────────────────────────────────────
    function startEdit(field) {
        origVal[field] = document.getElementById(`input-${field}`).value;

        document.getElementById(`view-${field}`)?.classList.add('hidden');
        document.getElementById(`edit-${field}`)?.classList.remove('hidden');
        document.getElementById(`pen-${field}`)?.classList.add('hidden');
        document.getElementById(`save-${field}`)?.classList.remove('hidden');
        document.getElementById(`cancel-${field}`)?.classList.remove('hidden');
        document.getElementById(`err-${field}`)?.classList.add('hidden');

        const input = document.getElementById(`input-${field}`);
        input?.focus();
        if (input?.setSelectionRange) {
            const len = input.value.length;
            input.setSelectionRange(len, len);
        }

        // Save on Enter (but not for textarea)
        if (input && input.tagName !== 'TEXTAREA') {
            input.onkeydown = (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveField(field);
                }
                if (e.key === 'Escape') cancelEdit(field);
            };
        }
    }

    function cancelEdit(field) {
        const input = document.getElementById(`input-${field}`);
        if (input && origVal[field] !== undefined) input.value = origVal[field];

        document.getElementById(`edit-${field}`)?.classList.add('hidden');
        document.getElementById(`view-${field}`)?.classList.remove('hidden');
        document.getElementById(`save-${field}`)?.classList.add('hidden');
        document.getElementById(`cancel-${field}`)?.classList.add('hidden');
        document.getElementById(`pen-${field}`)?.classList.remove('hidden');
        document.getElementById(`err-${field}`)?.classList.add('hidden');
    }

    function showFieldErr(field, msg) {
        const el = document.getElementById(`err-${field}`);
        if (el) {
            el.textContent = msg;
            el.classList.remove('hidden');
        }
    }

    async function saveField(field) {
        const input = document.getElementById(`input-${field}`);
        const val = input?.value.trim() ?? '';

        // Validation
        if (field === 'fullName' && !val) {
            showFieldErr(field, 'Name cannot be empty.');
            return;
        }
        if (field === 'phoneNumber' && !val) {
            showFieldErr(field, 'Phone cannot be empty.');
            return;
        }

        // Build payload with current values of all 3 editable fields
        const fullName = field === 'fullName' ? val : (document.getElementById('text-fullName')?.textContent.trim() || origVal['fullName'] || '');
        const phoneNumber = field === 'phoneNumber' ? val : (document.getElementById('text-phoneNumber')?.textContent.trim() || origVal['phoneNumber'] || '');
        const address = field === 'address' ? (val || null) : (document.getElementById('text-address')?.textContent.trim() || null);

        // Disable save btn
        const saveBtn = document.getElementById(`save-${field}`);
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.style.opacity = '0.5';
        }

        try {
            const res = await fetch('/profile', {
                method: 'PUT',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({
                    fullName,
                    phoneNumber,
                    address
                }),
            });

            if (res.status === 401) {
                window.location.href = '/login?expired=1';
                return;
            }

            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                // Update display text
                document.getElementById(`text-${field}`)?.replaceWith(
                    Object.assign(document.createElement(field === 'address' ? 'p' : 'p'), {
                        id: `text-${field}`,
                        textContent: val || '—',
                        className: field === 'address' ?
                            'text-sm font-semibold text-gray-800 leading-relaxed' :
                            'text-sm font-semibold text-gray-800',
                    })
                );
                // Update hero name if editing full name
                if (field === 'fullName') {
                    const heroName = document.getElementById('display-fullName');
                    if (heroName) heroName.textContent = val || '—';
                    // Update initials
                    const parts = (val || '').trim().split(' ').filter(Boolean);
                    const newInits = parts.slice(0, 2).map(w => w[0].toUpperCase()).join('');
                    const avatar = document.querySelector('.profile-avatar-text');
                    if (avatar) avatar.textContent = newInits || 'U';
                }
                cancelEdit(field);
                showToast('Profile updated successfully.');
            } else {
                showFieldErr(field, data.message ?? 'Update failed. Please try again.');
                showToast(data.message ?? 'Failed to update profile.', 'error');
            }
        } catch {
            showFieldErr(field, 'Network error. Please try again.');
            showToast('Network error. Please check your connection.', 'error');
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.style.opacity = '1';
            }
        }
    }

    // ── Copy Customer ID ─────────────────────────────────────────────────────
    async function copyCustomerId() {
        const text = document.getElementById('customer-id-text')?.textContent?.trim();
        if (!text) return;
        try {
            await navigator.clipboard.writeText(text);
            const label = document.getElementById('copy-label');
            const icon = document.getElementById('copy-icon');
            label.textContent = 'Copied!';
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';
            document.getElementById('copy-btn').classList.add('text-green-500', 'border-green-200', 'bg-green-50');
            showToast('Customer ID copied to clipboard.');
            setTimeout(() => {
                label.textContent = 'Copy';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>';
                document.getElementById('copy-btn').classList.remove('text-green-500', 'border-green-200', 'bg-green-50');
            }, 2000);
        } catch {
            showToast('Could not copy. Please copy manually.', 'error');
        }
    }
</script>

@endsection