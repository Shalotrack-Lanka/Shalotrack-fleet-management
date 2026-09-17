@extends('layouts.app')

@section('title', 'Vehicles — ShaloTrack Fleet')
@section('page-title', 'Vehicles')

@section('content')

    {{-- Error state --}}
    @if($error)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ $error }}
            <button onclick="window.location.reload()" class="ml-auto text-red-600 underline text-sm">Retry</button>
        </div>
    @endif

    {{-- Header row --}}
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">{{ count($vehicles ?? []) }} vehicle(s) registered</p>
        @if($customerId)
            <button onclick="openAddModal()"
                    class="flex items-center gap-2 px-4 py-2 bg-[#FA6908] hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Vehicle
            </button>
        @endif
    </div>

    {{-- Empty state --}}
    @if(empty($vehicles))
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-16 text-center">
            <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h8l2-2h2a1 1 0 000-2h-1"/>
            </svg>
            <p class="text-gray-400 font-medium mb-2">No vehicles yet</p>
            <p class="text-gray-300 text-sm mb-6">Add your first vehicle to start tracking.</p>
            @if($customerId)
                <button onclick="openAddModal()"
                        class="px-6 py-2.5 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition">
                    Add Your First Vehicle
                </button>
            @endif
        </div>
    @else
        {{-- Vehicle grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" id="vehicles-grid">
            @foreach($vehicles as $vehicle)
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition overflow-hidden"
                     id="vehicle-card-{{ $vehicle['vehicleId'] }}">

                    {{-- Card header --}}
                    <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                        <div>
                            <p class="font-bold text-gray-800">{{ $vehicle['vehicleNumber'] }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $vehicle['make'] }} {{ $vehicle['model'] }} · {{ $vehicle['year'] }}</p>
                        </div>
                        {{-- GPS device badge --}}
                        @if($vehicle['hasGpsDevice'] ?? false)
                            <span class="flex items-center gap-1 text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full font-medium">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                GPS Linked
                            </span>
                        @else
                            <span class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded-full">No GPS</span>
                        @endif
                    </div>

                    {{-- Card body --}}
                    <div class="px-5 py-4 space-y-2">
                        @if($vehicle['color'] ?? null)
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <span class="text-gray-300">Color</span>
                                <span class="text-gray-700">{{ $vehicle['color'] }}</span>
                            </div>
                        @endif
                        @if($vehicle['vehicleType'] ?? null)
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <span class="text-gray-300">Type</span>
                                <span class="text-gray-700">{{ $vehicle['vehicleType'] }}</span>
                            </div>
                        @endif
                        @if($vehicle['fuelType'] ?? null)
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <span class="text-gray-300">Fuel</span>
                                <span class="text-gray-700">{{ $vehicle['fuelType'] }}</span>
                            </div>
                        @endif
                        @if($vehicle['imei'] ?? null)
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <span class="text-gray-300">IMEI</span>
                                <span class="text-gray-700 font-mono text-xs">{{ $vehicle['imei'] }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Card actions --}}
                    <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex items-center gap-2">
                        <a href="/vehicles/{{ $vehicle['vehicleId'] }}"
                           class="flex-1 text-center py-1.5 text-sm text-[#021F4A] font-medium hover:text-[#FA6908] transition">
                            View Details
                        </a>
                        <button onclick="openEditModal({{ json_encode($vehicle) }})"
                                class="flex-1 text-center py-1.5 text-sm text-gray-600 font-medium hover:text-[#FA6908] transition">
                            Edit
                        </button>
                        @if($vehicle['hasGpsDevice'] ?? false)
                            <button onclick="openUnlinkModal('{{ $vehicle['vehicleId'] }}', '{{ $vehicle['vehicleNumber'] }}')"
                                    class="flex-1 text-center py-1.5 text-sm text-gray-600 font-medium hover:text-red-500 transition">
                                Unlink GPS
                            </button>
                        @else
                            <button onclick="openLinkModal('{{ $vehicle['vehicleId'] }}', '{{ $vehicle['vehicleNumber'] }}')"
                                    class="flex-1 text-center py-1.5 text-sm text-[#FA6908] font-medium hover:text-orange-700 transition">
                                Link GPS
                            </button>
                        @endif
                        <button onclick="confirmDelete('{{ $vehicle['vehicleId'] }}', '{{ $vehicle['vehicleNumber'] }}')"
                                class="py-1.5 px-2 text-gray-300 hover:text-red-500 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ================================================================
         ADD VEHICLE MODAL
    ================================================================ --}}
    <div id="add-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40" onclick="closeAddModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg relative">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Add Vehicle</h3>
                    <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Vehicle Number *</label>
                            <input type="text" id="add-vehicleNumber" placeholder="WP BGU 1212"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent uppercase" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Make *</label>
                            <input type="text" id="add-make" placeholder="Toyota"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Model *</label>
                            <input type="text" id="add-model" placeholder="Prius"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Year *</label>
                            <input type="number" id="add-year" placeholder="{{ date('Y') }}" min="1900" max="2100"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Color</label>
                            <input type="text" id="add-color" placeholder="White"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Vehicle Type</label>
                            <select id="add-vehicleType" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent">
                                <option value="">Select type</option>
                                <option>Car</option>
                                <option>SUV</option>
                                <option>Van</option>
                                <option>Truck</option>
                                <option>Motorcycle</option>
                                <option>Three-Wheeler</option>
                                <option>Bus</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Fuel Type</label>
                            <select id="add-fuelType" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent">
                                <option value="">Select fuel</option>
                                <option>Petrol</option>
                                <option>Diesel</option>
                                <option>Electric</option>
                                <option>Hybrid</option>
                                <option>CNG</option>
                            </select>
                        </div>
                    </div>
                    <p id="add-error" class="text-red-600 text-sm hidden"></p>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
                    <button onclick="closeAddModal()"
                            class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button id="add-submit-btn" onclick="submitAddVehicle()"
                            class="flex-1 py-2 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition">
                        Add Vehicle
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================
         EDIT VEHICLE MODAL
    ================================================================ --}}
    <div id="edit-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40" onclick="closeEditModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg relative">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Edit Vehicle</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <input type="hidden" id="edit-vehicleId" />
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Vehicle Number *</label>
                            <input type="text" id="edit-vehicleNumber"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent uppercase" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Make *</label>
                            <input type="text" id="edit-make"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Model *</label>
                            <input type="text" id="edit-model"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Year *</label>
                            <input type="number" id="edit-year" min="1900" max="2100"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Color</label>
                            <input type="text" id="edit-color"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Vehicle Type</label>
                            <select id="edit-vehicleType" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent">
                                <option value="">Select type</option>
                                <option>Car</option><option>SUV</option><option>Van</option>
                                <option>Truck</option><option>Motorcycle</option>
                                <option>Three-Wheeler</option><option>Bus</option><option>Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Fuel Type</label>
                            <select id="edit-fuelType" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent">
                                <option value="">Select fuel</option>
                                <option>Petrol</option><option>Diesel</option><option>Electric</option>
                                <option>Hybrid</option><option>CNG</option>
                            </select>
                        </div>
                    </div>
                    <p id="edit-error" class="text-red-600 text-sm hidden"></p>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
                    <button onclick="closeEditModal()"
                            class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button id="edit-submit-btn" onclick="submitEditVehicle()"
                            class="flex-1 py-2 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================
         LINK GPS DEVICE MODAL
    ================================================================ --}}
    <div id="link-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40" onclick="closeLinkModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Link GPS Device</h3>
                    <button onclick="closeLinkModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <p class="text-sm text-gray-500">Enter the IMEI number printed on the GPS device to link it to <strong id="link-vehicle-name"></strong>.</p>
                    <input type="hidden" id="link-vehicleId" />
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">IMEI Number</label>
                        <input type="text" id="link-imei" placeholder="355172106043787" maxlength="17"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent font-mono" />
                    </div>
                    <p id="link-error" class="text-red-600 text-sm hidden"></p>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
                    <button onclick="closeLinkModal()"
                            class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button id="link-submit-btn" onclick="submitLinkDevice()"
                            class="flex-1 py-2 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition">Link Device</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================
         DELETE CONFIRM MODAL
    ================================================================ --}}
    <div id="delete-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40" onclick="closeDeleteModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative p-6 text-center">
                <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-800 mb-1">Delete Vehicle</h3>
                <p class="text-sm text-gray-500 mb-6">
                    Are you sure you want to delete <strong id="delete-vehicle-name"></strong>?
                    This action cannot be undone.
                </p>
                <input type="hidden" id="delete-vehicleId" />
                <p id="delete-error" class="text-red-600 text-sm mb-4 hidden"></p>
                <div class="flex gap-3">
                    <button onclick="closeDeleteModal()"
                            class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button id="delete-submit-btn" onclick="submitDelete()"
                            class="flex-1 py-2 bg-red-500 text-white text-sm font-semibold rounded-lg hover:bg-red-600 transition">Delete</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    const CSRF  = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const customerId = '{{ $customerId }}';

    // ---- Helpers ----
    function showEl(id)  { document.getElementById(id).classList.remove('hidden'); }
    function hideEl(id)  { document.getElementById(id).classList.add('hidden'); }
    function showErr(id, msg) { const el = document.getElementById(id); el.textContent = msg; el.classList.remove('hidden'); }
    function hideErr(id) { document.getElementById(id).classList.add('hidden'); }
    function setBtn(id, loading, label) {
        const btn = document.getElementById(id);
        btn.disabled = loading;
        btn.textContent = loading ? 'Please wait...' : label;
    }

    async function apiFetch(url, method, body = null) {
        const opts = {
            method,
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        };
        if (body) opts.body = JSON.stringify(body);
        const res = await fetch(url, opts);
        const data = await res.json().catch(() => ({}));
        return { ok: res.ok, status: res.status, data };
    }

    // ================================================================
    // ADD MODAL
    // ================================================================
    function openAddModal() { showEl('add-modal'); document.getElementById('add-vehicleNumber').focus(); }
    function closeAddModal() {
        hideEl('add-modal');
        hideErr('add-error');
        ['add-vehicleNumber','add-make','add-model','add-year','add-color'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('add-vehicleType').value = '';
        document.getElementById('add-fuelType').value = '';
    }

    async function submitAddVehicle() {
        hideErr('add-error');
        const vehicleNumber = document.getElementById('add-vehicleNumber').value.trim();
        const make          = document.getElementById('add-make').value.trim();
        const model         = document.getElementById('add-model').value.trim();
        const year          = document.getElementById('add-year').value.trim();

        if (!vehicleNumber || !make || !model || !year) {
            showErr('add-error', 'Vehicle number, make, model and year are required.');
            return;
        }

        setBtn('add-submit-btn', true, 'Add Vehicle');

        const { ok, data } = await apiFetch('/vehicles', 'POST', {
            customerId,
            vehicleNumber,
            make,
            model,
            year: parseInt(year),
            color:       document.getElementById('add-color').value.trim() || null,
            vehicleType: document.getElementById('add-vehicleType').value || null,
            fuelType:    document.getElementById('add-fuelType').value || null,
        });

        setBtn('add-submit-btn', false, 'Add Vehicle');

        if (ok) {
            closeAddModal();
            window.location.reload();
        } else {
            showErr('add-error', data.message ?? 'Failed to add vehicle.');
        }
    }

    // ================================================================
    // EDIT MODAL
    // ================================================================
    function openEditModal(vehicle) {
        document.getElementById('edit-vehicleId').value    = vehicle.vehicleId;
        document.getElementById('edit-vehicleNumber').value = vehicle.vehicleNumber;
        document.getElementById('edit-make').value         = vehicle.make;
        document.getElementById('edit-model').value        = vehicle.model;
        document.getElementById('edit-year').value         = vehicle.year;
        document.getElementById('edit-color').value        = vehicle.color ?? '';
        document.getElementById('edit-vehicleType').value  = vehicle.vehicleType ?? '';
        document.getElementById('edit-fuelType').value     = vehicle.fuelType ?? '';
        showEl('edit-modal');
    }
    function closeEditModal() { hideEl('edit-modal'); hideErr('edit-error'); }

    async function submitEditVehicle() {
        hideErr('edit-error');
        const id            = document.getElementById('edit-vehicleId').value;
        const vehicleNumber = document.getElementById('edit-vehicleNumber').value.trim();
        const make          = document.getElementById('edit-make').value.trim();
        const model         = document.getElementById('edit-model').value.trim();
        const year          = document.getElementById('edit-year').value.trim();

        if (!vehicleNumber || !make || !model || !year) {
            showErr('edit-error', 'Vehicle number, make, model and year are required.');
            return;
        }

        setBtn('edit-submit-btn', true, 'Save Changes');

        const { ok, data } = await apiFetch(`/vehicles/${id}`, 'PUT', {
            vehicleNumber,
            make,
            model,
            year: parseInt(year),
            color:       document.getElementById('edit-color').value.trim() || null,
            vehicleType: document.getElementById('edit-vehicleType').value || null,
            fuelType:    document.getElementById('edit-fuelType').value || null,
        });

        setBtn('edit-submit-btn', false, 'Save Changes');

        if (ok) {
            closeEditModal();
            window.location.reload();
        } else {
            showErr('edit-error', data.message ?? 'Failed to update vehicle.');
        }
    }

    // ================================================================
    // LINK GPS MODAL
    // ================================================================
    function openLinkModal(vehicleId, vehicleName) {
        document.getElementById('link-vehicleId').value = vehicleId;
        document.getElementById('link-vehicle-name').textContent = vehicleName;
        document.getElementById('link-imei').value = '';
        hideErr('link-error');
        showEl('link-modal');
        document.getElementById('link-imei').focus();
    }
    function closeLinkModal() { hideEl('link-modal'); hideErr('link-error'); }

    async function submitLinkDevice() {
        hideErr('link-error');
        const vehicleId = document.getElementById('link-vehicleId').value;
        const imei      = document.getElementById('link-imei').value.trim().replace(/\D/g,'');

        if (imei.length < 15) {
            showErr('link-error', 'Enter a valid 15-digit IMEI number.');
            return;
        }

        setBtn('link-submit-btn', true, 'Link Device');

        const { ok, data } = await apiFetch(`/vehicles/${vehicleId}/link-device`, 'POST', { imei });

        setBtn('link-submit-btn', false, 'Link Device');

        if (ok) {
            closeLinkModal();
            window.location.reload();
        } else {
            showErr('link-error', data.message ?? 'Failed to link device.');
        }
    }

    // ================================================================
    // UNLINK GPS
    // ================================================================
    function openUnlinkModal(vehicleId, vehicleName) {
        if (!confirm(`Unlink the GPS device from ${vehicleName}?`)) return;
        // Need assignmentId — fetch vehicle detail first
        fetch(`/api/vehicles/${vehicleId}`, { credentials: 'include', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
            .then(r => r.json())
            .then(data => {
                const assignmentId = data?.data?.currentAssignmentId ?? data?.currentAssignmentId ?? null;
                if (!assignmentId) { alert('Could not find assignment ID. Please refresh.'); return; }
                apiFetch(`/vehicles/${vehicleId}/unlink-device`, 'POST', { assignmentId })
                    .then(({ ok, data }) => {
                        if (ok) window.location.reload();
                        else alert(data.message ?? 'Failed to unlink device.');
                    });
            })
            .catch(() => alert('Failed to load vehicle details.'));
    }

    // ================================================================
    // DELETE
    // ================================================================
    function confirmDelete(vehicleId, vehicleName) {
        document.getElementById('delete-vehicleId').value = vehicleId;
        document.getElementById('delete-vehicle-name').textContent = vehicleName;
        hideErr('delete-error');
        showEl('delete-modal');
    }
    function closeDeleteModal() { hideEl('delete-modal'); }

    async function submitDelete() {
        hideErr('delete-error');
        const id = document.getElementById('delete-vehicleId').value;
        setBtn('delete-submit-btn', true, 'Delete');

        const { ok, data } = await apiFetch(`/vehicles/${id}`, 'DELETE');

        setBtn('delete-submit-btn', false, 'Delete');

        if (ok) {
            closeDeleteModal();
            document.getElementById(`vehicle-card-${id}`)?.remove();
            // If no vehicles left, reload to show empty state
            if (document.querySelectorAll('[id^="vehicle-card-"]').length === 0) {
                window.location.reload();
            }
        } else {
            showErr('delete-error', data.message ?? 'Failed to delete vehicle.');
        }
    }
</script>
@endpush