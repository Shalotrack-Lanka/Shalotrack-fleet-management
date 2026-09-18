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
                    <select id="period-select" onchange="loadStats()"
                            class="px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]">
                        <option value="day">Today</option>
                        <option value="week" selected>This Week</option>
                        <option value="month">This Month</option>
                    </select>
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

    async function loadStats() {
        const vehicleId = document.getElementById('vehicle-select')?.value;
        const period    = document.getElementById('period-select')?.value ?? 'week';
        if (!vehicleId) return;

        document.getElementById('stats-empty').classList.add('hidden');
        document.getElementById('stats-content').classList.add('hidden');
        document.getElementById('stats-loading').classList.remove('hidden');

        try {
            const res  = await fetch(`/stats/${vehicleId}?period=${period}`, {
                credentials: 'include',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            });
            const json = await res.json();
            document.getElementById('stats-loading').classList.add('hidden');

            if (!json.success || !json.data) {
                document.getElementById('stats-empty').classList.remove('hidden');
                document.getElementById('stats-empty').querySelector('p').textContent = 'No data available for this period.';
                return;
            }

            renderStats(json.data);
        } catch (e) {
            document.getElementById('stats-loading').classList.add('hidden');
            document.getElementById('stats-empty').classList.remove('hidden');
            document.getElementById('stats-empty').querySelector('p').textContent = 'Failed to load statistics. Please try again.';
        }
    }

    function renderStats(stats) {
        // Summary cards
        const cards = [
            { label: 'Total Distance',  value: `${parseFloat(stats.totalDistanceKm ?? 0).toFixed(1)} km`,  color: 'text-[#021F4A]' },
            { label: 'Total Trips',     value: stats.totalTripCount ?? 0,                                   color: 'text-[#FA6908]' },
            { label: 'Average Speed',   value: `${Math.round(stats.averageSpeed ?? 0)} km/h`,              color: 'text-blue-600' },
            { label: 'Max Speed',       value: `${Math.round(stats.maxSpeed ?? 0)} km/h`,                  color: 'text-red-500' },
            { label: 'Drive Time',      value: `${Math.round((stats.totalDrivingMinutes ?? 0) / 60)}h ${Math.round((stats.totalDrivingMinutes ?? 0) % 60)}m`, color: 'text-green-600' },
            { label: 'Idle Time',       value: `${Math.round((stats.totalIdleMinutes ?? 0) / 60)}h ${Math.round((stats.totalIdleMinutes ?? 0) % 60)}m`,       color: 'text-gray-500' },
            { label: 'Stops',           value: stats.totalStopCount ?? 0,                                   color: 'text-purple-600' },
            { label: 'Overspeed Events',value: stats.overspeedIncidentCount ?? 0,                          color: 'text-red-500' },
        ];

        document.getElementById('summary-cards').innerHTML = cards.map(c => `
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <p class="text-xs text-gray-400 mb-1">${c.label}</p>
                <p class="text-2xl font-black ${c.color}">${c.value}</p>
            </div>
        `).join('');

        // Daily breakdown
        const daily = stats.dailyBreakdown ?? [];
        document.getElementById('daily-table').innerHTML = daily.length === 0
            ? `<tr><td colspan="6" class="px-6 py-6 text-center text-gray-400 text-sm">No daily data available.</td></tr>`
            : daily.map(d => {
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