@extends('layouts.app')

@section('title', 'Alerts — ShaloTrack Fleet')
@section('page-title', 'Alerts')

@section('content')

    @if($error)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ $error }}
            <button onclick="window.location.reload()" class="ml-auto text-red-600 underline text-sm">Retry</button>
        </div>
    @endif

    {{-- Filter bar --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-6">
        <form method="GET" action="/alerts" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-medium text-gray-500 mb-1">Filter by Vehicle</label>
                <select name="vehicle"
                        onchange="this.form.submit()"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent">
                    <option value="">All Vehicles</option>
                    @foreach($vehicles as $vehicle)
                        <option value="{{ $vehicle['vehicleId'] }}"
                                {{ $vehicleFilter === $vehicle['vehicleId'] ? 'selected' : '' }}>
                            {{ $vehicle['vehicleNumber'] }} — {{ $vehicle['make'] }} {{ $vehicle['model'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="text-sm text-gray-400">{{ $totalCount }} alert(s) total</div>
        </form>
    </div>

    {{-- Alerts list --}}
    @if(empty($alerts))
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-16 text-center">
            <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <p class="text-gray-400 font-medium mb-1">No alerts</p>
            <p class="text-gray-300 text-sm">You'll see alerts here when your vehicles trigger events.</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="divide-y divide-gray-50">
                @foreach($alerts as $alert)
                    <div class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition {{ !($alert['isRead'] ?? false) ? 'bg-orange-50/30' : '' }}"
                         id="alert-{{ $alert['alertId'] }}">

                        {{-- Alert type icon --}}
                        <div class="flex-shrink-0 mt-0.5">
                            @php
                                $type = strtolower($alert['alertType'] ?? '');
                                $iconColor = match(true) {
                                    str_contains($type, 'speed')    => 'text-red-500 bg-red-50',
                                    str_contains($type, 'ignition') => 'text-green-500 bg-green-50',
                                    str_contains($type, 'offline')  => 'text-gray-500 bg-gray-100',
                                    str_contains($type, 'geofence') => 'text-blue-500 bg-blue-50',
                                    str_contains($type, 'power')    => 'text-yellow-500 bg-yellow-50',
                                    str_contains($type, 'battery')  => 'text-orange-500 bg-orange-50',
                                    default                         => 'text-gray-500 bg-gray-100',
                                };
                            @endphp
                            <span class="w-8 h-8 rounded-full {{ $iconColor }} flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                            </span>
                        </div>

                        {{-- Alert content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ $alert['vehicleNumber'] ?? '—' }}
                                </p>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">
                                    {{ $alert['alertType'] ?? 'Unknown' }}
                                </span>
                                @if(!($alert['isRead'] ?? false))
                                    <span class="w-2 h-2 bg-[#FA6908] rounded-full flex-shrink-0"></span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600">{{ $alert['message'] ?? '—' }}</p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ \Carbon\Carbon::parse($alert['triggeredAt'])->diffForHumans() }}
                                · {{ \Carbon\Carbon::parse($alert['triggeredAt'])->format('d M Y, H:i') }}
                            </p>
                        </div>

                        {{-- Mark read button --}}
                        @if(!($alert['isRead'] ?? false))
                            <button onclick="markRead({{ $alert['alertId'] }})"
                                    class="flex-shrink-0 text-xs text-gray-400 hover:text-[#FA6908] transition mt-1"
                                    title="Mark as read">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($totalPages > 1)
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-400">Page {{ $currentPage }} of {{ $totalPages }}</p>
                    <div class="flex gap-2">
                        @if($currentPage > 1)
                            <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage - 1]) }}"
                               class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition">
                                ← Previous
                            </a>
                        @endif
                        @if($currentPage < $totalPages)
                            <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage + 1]) }}"
                               class="px-3 py-1.5 text-xs bg-[#FA6908] text-white rounded-lg hover:bg-orange-600 transition">
                                Next →
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif

@endsection

@push('scripts')
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    async function markRead(alertId) {
        const res  = await fetch(`/alerts/${alertId}/read`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        });
        const data = await res.json();
        if (data.success) {
            const row = document.getElementById(`alert-${alertId}`);
            if (row) {
                // Remove unread indicator and mark-read button
                row.classList.remove('bg-orange-50/30');
                row.querySelector('button')?.remove();
                row.querySelector('.bg-\\[\\#FA6908\\].rounded-full')?.remove();
            }
        }
    }
</script>
@endpush