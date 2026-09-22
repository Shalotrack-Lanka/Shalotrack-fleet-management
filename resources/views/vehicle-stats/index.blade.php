@extends('layouts.app')
@section('title', 'Vehicle Statistics — ShaloTrack Fleet')
@section('page-title', 'Vehicle Statistics')

@section('content')

@if($error)
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">{{ $error }}</div>
@endif

@if(empty($vehicles))
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-16 text-center">
    <p class="text-gray-400 font-medium mb-2">No GPS-enabled vehicles</p>
    <p class="text-gray-300 text-sm mb-6">Link a GPS device to a vehicle to view statistics.</p>
    <a href="/vehicles" class="px-6 py-2.5 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition">Go to Vehicles</a>
</div>
@else

{{-- Vehicle + period selector --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-6">
    <div class="flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-medium text-gray-500 mb-1">Vehicle</label>
            <select id="vehicle-select" onchange="loadStats()"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]">
                <option value="">Select a vehicle</option>
                @foreach($vehicles as $vehicle)
                <option value="{{ $vehicle['vehicleId'] }}">{{ $vehicle['vehicleNumber'] }} — {{ $vehicle['make'] }} {{ $vehicle['model'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Period</label>
            <select id="period-select" onchange="onPeriodChange()"
                class="px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]">
                <option value="day">Today</option>
                <option value="week" selected>This Week</option>
                <option value="month">This Month</option>
                <option value="custom">Custom Range…</option>
            </select>
        </div>
    </div>

    {{-- Custom date-range row — hidden until "Custom Range…" is selected --}}
    <div id="custom-range-row" class="hidden mt-4 pt-4 border-t border-gray-100">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" id="date-from"
                    class="px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" id="date-to"
                    class="px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]">
            </div>
            <button onclick="loadCustomRange()"
                class="px-5 py-2 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600 active:scale-95 transition">
                Load
            </button>
            <p id="custom-range-error" class="hidden text-xs text-red-500 self-center"></p>
        </div>
    </div>
</div>

{{-- Loading --}}
<div id="stats-loading" class="hidden bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
    <div class="w-8 h-8 border-4 border-[#FA6908] border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
    <p class="text-gray-400 text-sm">Loading statistics...</p>
</div>

{{-- Empty --}}
<div id="stats-empty" class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
    <p class="text-gray-400 text-sm">Select a vehicle to view its statistics.</p>
</div>

{{-- Stats content --}}
<div id="stats-content" class="hidden space-y-6">

    {{-- Summary cards --}}
    <div class="grid grid-cols-4 gap-4" id="summary-cards"></div>

    {{-- Daily breakdown table --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Daily Breakdown</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs font-medium">
                        <th class="text-left px-6 py-3">Date</th>
                        <th class="text-right px-4 py-3">Distance</th>
                        <th class="text-right px-4 py-3">Trips</th>
                        <th class="text-right px-4 py-3">Avg Speed</th>
                        <th class="text-right px-4 py-3">Max Speed</th>
                        <th class="text-right px-6 py-3">Drive Time</th>
                    </tr>
                </thead>
                <tbody id="daily-table" class="divide-y divide-gray-50"></tbody>
            </table>
        </div>
    </div>
</div>

@endif

@endsection

@push('scripts')
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // ── Helpers ──────────────────────────────────────────────────────────────

    function toYMD(date) {
        // Returns "YYYY-MM-DD" in local time (same as <input type="date"> value).
        return date.toLocaleDateString('en-CA'); // en-CA uses YYYY-MM-DD natively
    }

    // ── Period selector change ────────────────────────────────────────────────

    function onPeriodChange() {
        const period = document.getElementById('period-select').value;
        const row = document.getElementById('custom-range-row');
        const errEl = document.getElementById('custom-range-error');

        if (period === 'custom') {
            // Populate defaults on first reveal: From = 7 days ago, To = today.
            const fromEl = document.getElementById('date-from');
            const toEl = document.getElementById('date-to');
            if (!fromEl.value) {
                const today = new Date();
                const weekAgo = new Date(today);
                weekAgo.setDate(today.getDate() - 7);
                fromEl.value = toYMD(weekAgo);
                toEl.value = toYMD(today);
            }
            row.classList.remove('hidden');
            errEl.classList.add('hidden');
            // Don't auto-fetch — wait for the user to click Load.
        } else {
            row.classList.add('hidden');
            loadStats(); // preset changed → fetch immediately
        }
    }

    // ── Custom range load (Load button) ──────────────────────────────────────

    function loadCustomRange() {
        const vehicleId = document.getElementById('vehicle-select').value;
        const from = document.getElementById('date-from').value;
        const to = document.getElementById('date-to').value;
        const errEl = document.getElementById('custom-range-error');

        errEl.classList.add('hidden');

        if (!vehicleId) {
            showEmpty('Select a vehicle first.');
            return;
        }
        if (!from || !to) {
            errEl.textContent = 'Please choose both a From and To date.';
            errEl.classList.remove('hidden');
            return;
        }
        if (from > to) {
            errEl.textContent = '"From" must be on or before "To".';
            errEl.classList.remove('hidden');
            return;
        }

        // Diff in days — mirror the server-side 366-day cap as a client hint.
        const diffMs = new Date(to) - new Date(from);
        const diffDays = diffMs / 86_400_000;
        if (diffDays > 366) {
            errEl.textContent = 'Date range cannot exceed 366 days.';
            errEl.classList.remove('hidden');
            return;
        }

        fetchStats(`/stats/${vehicleId}?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`);
    }

    // ── Preset load ──────────────────────────────────────────────────────────

    async function loadStats() {
        const vehicleId = document.getElementById('vehicle-select')?.value;
        const period = document.getElementById('period-select')?.value ?? 'week';
        if (!vehicleId || period === 'custom') return;

        fetchStats(`/stats/${vehicleId}?period=${period}`);
    }

    // ── Core fetch ───────────────────────────────────────────────────────────

    async function fetchStats(url) {
        document.getElementById('stats-empty').classList.add('hidden');
        document.getElementById('stats-content').classList.add('hidden');
        document.getElementById('stats-loading').classList.remove('hidden');

        try {
            const res = await fetch(url, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF
                },
            });
            const json = await res.json();
            document.getElementById('stats-loading').classList.add('hidden');

            if (!json.success || !json.data) {
                showEmpty(json.message ?? 'No data available for this period.');
                return;
            }

            renderStats(json.data);
        } catch (e) {
            document.getElementById('stats-loading').classList.add('hidden');
            showEmpty('Failed to load statistics. Please try again.');
        }
    }

    function showEmpty(msg) {
        const el = document.getElementById('stats-empty');
        el.querySelector('p').textContent = msg;
        el.classList.remove('hidden');
    }

    function renderStats(stats) {
        // Summary cards
        const cards = [{
                label: 'Total Distance',
                value: `${parseFloat(stats.totalDistanceKm ?? 0).toFixed(1)} km`,
                color: 'text-[#021F4A]'
            },
            {
                label: 'Total Trips',
                value: stats.totalTripCount ?? 0,
                color: 'text-[#FA6908]'
            },
            {
                label: 'Average Speed',
                value: `${Math.round(stats.averageSpeed ?? 0)} km/h`,
                color: 'text-blue-600'
            },
            {
                label: 'Max Speed',
                value: `${Math.round(stats.maxSpeed ?? 0)} km/h`,
                color: 'text-red-500'
            },
            {
                label: 'Drive Time',
                value: `${Math.round((stats.totalDrivingMinutes ?? 0) / 60)}h ${Math.round((stats.totalDrivingMinutes ?? 0) % 60)}m`,
                color: 'text-green-600'
            },
            {
                label: 'Idle Time',
                value: `${Math.round((stats.totalIdleMinutes ?? 0) / 60)}h ${Math.round((stats.totalIdleMinutes ?? 0) % 60)}m`,
                color: 'text-gray-500'
            },
            {
                label: 'Stops',
                value: stats.totalStopCount ?? 0,
                color: 'text-purple-600'
            },
            {
                label: 'Overspeed Events',
                value: stats.overspeedIncidentCount ?? 0,
                color: 'text-red-500'
            },
        ];

        document.getElementById('summary-cards').innerHTML = cards.map(c => `
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <p class="text-xs text-gray-400 mb-1">${c.label}</p>
                <p class="text-2xl font-black ${c.color}">${c.value}</p>
            </div>
        `).join('');

        // Daily breakdown
        const daily = stats.dailyBreakdown ?? [];
        document.getElementById('daily-table').innerHTML = daily.length === 0 ?
            `<tr><td colspan="6" class="px-6 py-6 text-center text-gray-400 text-sm">No daily data available.</td></tr>` :
            daily.map(d => {
                const driveH = Math.floor((d.ignitionOnMinutes ?? 0) / 60);
                const driveM = Math.round((d.ignitionOnMinutes ?? 0) % 60);
                return `<tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-3 text-gray-700">${new Date(d.date).toLocaleDateString('en-GB', {weekday:'short', day:'2-digit', month:'short'})}</td>
                    <td class="px-4 py-3 text-right text-gray-700">${parseFloat(d.distanceKm ?? 0).toFixed(1)} km</td>
                    <td class="px-4 py-3 text-right text-gray-700">${d.tripCount ?? 0}</td>
                    <td class="px-4 py-3 text-right text-gray-700">${Math.round(d.averageSpeed ?? 0)} km/h</td>
                    <td class="px-4 py-3 text-right text-gray-700">${Math.round(d.maxSpeed ?? 0)} km/h</td>
                    <td class="px-6 py-3 text-right text-gray-700">${driveH}h ${driveM}m</td>
                </tr>`;
            }).join('');

        document.getElementById('stats-content').classList.remove('hidden');
    }
</script>
@endpush