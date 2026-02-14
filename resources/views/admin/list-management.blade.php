@extends('layouts.admin')

@section('title', 'List Management')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/settings.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/list-management.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/sweetalert-custom.css') }}">
@endsection

@section('content')
    <div class="main-content">
         <h1 class="section-title">Package List</h1>
        <div class="list-management">
                    <!-- Package List -->
                    <div class="package-list-section">
                        <button class="add-package-btn" onclick="openAddPackageModal()">
                            <i class="fas fa-plus"></i>&nbsp;&nbsp;Add Package
                        </button>

                        <table class="package-table">
                            <thead>
                                <tr>
                                    <th>Package ID</th>
                                    <th>Package Name</th>
                                    <th>Price</th>
                                    <th>Max Persons</th>
                                </tr>
                            </thead>
                            <tbody id="packageTableBody">
                                @forelse($packages as $index => $package)
                                    <tr class="package-row {{ $index === 0 ? 'selected' : '' }}"
                                        data-package-id="{{ $package->PackageID }}"
                                        onclick="selectPackage('{{ $package->PackageID }}', '{{ $package->Name }}', {{ $package->Price }}, {{ $package->max_guests }}, {{ json_encode($package->amenities_array) }})">
                                        <td>{{ $package->PackageID }}</td>
                                        <td>{{ $package->Name }}</td>
                                        <td>Php {{ number_format($package->Price, 2) }}</td>
                                        <td>{{ $package->max_guests }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 20px;">No packages found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Package Information -->
                    <div class="package-info-section">
                        <h3 class="package-info-title">Package Information</h3>

                        @if($packages->count() > 0)
                            @php
                                $firstPackage = $packages->first();
                            @endphp

                            <div class="package-card">
                                <i class="fas fa-box"></i>
                                <div class="package-card-content">
                                    <h4 class="package-card-title" id="selectedPackageName">{{ $firstPackage->Name }}</h4>
                                </div>
                            </div>

                            <div class="package-price-card">
                                <i class="fas fa-tag"></i>
                                <div class="package-card-content">
                                    <h4 class="package-card-title" id="selectedPackagePrice">Php {{ number_format($firstPackage->Price, 2) }}</h4>
                                </div>
                            </div>

                            <div class="package-pax-card">
                                <i class="fas fa-users"></i>
                                <div class="package-card-content">
                                    <h4 class="package-card-title" id="selectedPackagePax">{{ $firstPackage->max_guests }} Pax</h4>
                                </div>
                            </div>

                            <div class="amenities-section">
                                <h4>Amenities</h4>
                                <ul class="amenities-list" id="selectedPackageAmenities">
                                    @foreach($firstPackage->amenities_array as $amenity)
                                        <li><i class="fas fa-check" style="color: #10b981; margin-right: 8px;"></i>{{ $amenity }}</li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="package-actions">
                                <button type="button" class="btn-edit" onclick="openEditPackageModal()">
                                    <i class="fas fa-edit"></i>&nbsp;&nbsp;Edit Package
                                </button>
                                <button type="button" class="btn-delete" onclick="deletePackage()">
                                    <i class="fas fa-trash"></i>&nbsp;&nbsp;Delete Package
                                </button>
                            </div>

                        @else

                            <div style="text-align: center; padding: 40px; color: #6b7280;">
                                <i class="fas fa-box" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                                <p>No packages available. Click "Add Package" to create one.</p>
                            </div>

                        @endif
                    </div>
                </div>
        </div>
    </div>

    <!-- Add Package Modal -->
    <div class="modal-overlay" id="addPackageModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add Package</h3>
                <button class="modal-close" onclick="closeAddPackageModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="addPackageForm">
                @csrf
                <div class="modal-body">
                    <div class="modal-section">
                        <h4 class="modal-section-title">Package Details</h4>
                        <div class="package-details-row">
                            <div class="form-group">
                                <label class="form-label">Package Name</label>
                                <input type="text" name="name" class="form-input" placeholder="Package Name" required
                                    oninput="capitalizeProper(this)">
                                <div class="error-message" id="add-error-name"></div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Price</label>
                                <input type="number" name="price" class="form-input" placeholder="Price" step="0.01" min="0"
                                    required>
                                <div class="error-message" id="add-error-price"></div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Max Person</label>
                                <input type="number" name="max_guests" class="form-input" placeholder="0" min="1" required>
                                <div class="error-message" id="add-error-max_guests"></div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-section">
                        <h4 class="modal-section-title">Amenities</h4>
                        <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem;">Please check all the amenities included in this package.</p>
                        <div class="error-message" id="add-error-amenities" style="margin-bottom: 0.5rem;"></div>

                        <!-- Add Amenity Section -->
                        <div class="add-amenity-section" style="display: flex; align-items: center; gap: 16px; margin: 20px 0;">
                            <label class="add-amenity-label" style="font-weight: 500; white-space: nowrap; min-width: 100px;">Add Amenity:</label>
                            <input type="text" class="form-input" id="newAmenityInput" placeholder="Amenity Name"
                                style="flex: 1; min-width: 250px;" oninput="capitalizeProper(this)">
                            <button type="button" class="btn-add-amenity" onclick="addCustomAmenity()">
                                <i class="fas fa-plus"></i>&nbsp;&nbsp;Add
                            </button>
                        </div>

                        <div class="amenities-grid" id="addAmenitiesGrid">
                            <!-- Amenities will be populated dynamically by JavaScript -->
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i>&nbsp;&nbsp;Save Package
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Package Modal -->
    <div class="modal-overlay" id="editPackageModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Edit Package</h3>
                <button class="modal-close" onclick="closeEditPackageModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="editPackageForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="editPackageId" name="package_id">
                <div class="modal-body">
                    <div class="modal-section">
                        <h4 class="modal-section-title">Package Details</h4>
                        <div class="package-details-row">
                            <div class="form-group">
                                <label class="form-label">Package Name</label>
                                <input type="text" name="name" class="form-input" id="editPackageName"
                                    placeholder="Package Name" required oninput="capitalizeProper(this)">
                                <div class="error-message" id="edit-error-name"></div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Price</label>
                                <input type="number" name="price" class="form-input" id="editPackagePrice"
                                    placeholder="Price" step="0.01" min="0" required>
                                <div class="error-message" id="edit-error-price"></div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Max Person</label>
                                <input type="number" name="max_guests" class="form-input" id="editPackageMaxPerson"
                                    placeholder="0" min="1" required>
                                <div class="error-message" id="edit-error-max_guests"></div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-section">
                        <h4 class="modal-section-title">Amenities</h4>
                        <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem;">Please check all the amenities included in this package.</p>
                        <div class="error-message" id="edit-error-amenities" style="margin-bottom: 0.5rem;"></div>

                        <div class="amenities-grid" id="editAmenitiesGrid">
                            <!-- Amenities will be populated dynamically by JavaScript -->
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i>&nbsp;&nbsp;Save Edit
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Package selection functionality
        @if($packages->count() > 0)
            let currentSelectedPackageId = '{{ $packages->first()->PackageID }}';
            let currentPackageAmenities = @json($packages->first()->amenities_array);
        @else
            let currentSelectedPackageId = null;
            let currentPackageAmenities = [];
        @endif

        const defaultAmenities = @json($amenities->pluck('name'));

        function selectPackage(id, name, price, maxPersons, amenities) {
            currentSelectedPackageId = id;
            currentPackageAmenities = amenities || [];

            document.querySelectorAll('.package-row').forEach(row => {
                row.classList.remove('selected');
            });
            event.target.closest('.package-row').classList.add('selected');

            document.getElementById('selectedPackageName').textContent = name;
            document.getElementById('selectedPackagePrice').textContent = 'Php ' + parseFloat(price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('selectedPackagePax').textContent = maxPersons + ' Pax';

            const amenitiesList = document.getElementById('selectedPackageAmenities');
            amenitiesList.innerHTML = '';
            currentPackageAmenities.forEach(amenity => {
                const li = document.createElement('li');
                li.innerHTML = '<i class="fas fa-check" style="color: #10b981; margin-right: 8px;"></i>' + amenity;
                amenitiesList.appendChild(li);
            });
        }

        function openAddPackageModal() {
            document.getElementById('editPackageModal').classList.remove('show');
            document.getElementById('addPackageForm').reset();
            clearPackageErrors('add');
            populateAmenities('add', []);
            document.getElementById('addPackageModal').classList.add('show');
        }

        function closeAddPackageModal() {
            const addModal = document.getElementById('addPackageModal');
            addModal.classList.remove('show');
            addModal.style.display = '';
            addModal.style.zIndex = '';
        }

        function openEditPackageModal() {
            if (!currentSelectedPackageId) {
                alert('Please select a package to edit');
                return;
            }

            document.getElementById('addPackageModal').classList.remove('show');
            clearPackageErrors('edit');

            let packageName = document.getElementById('selectedPackageName').textContent.trim();
            packageName = capitalizeString(packageName);

            document.getElementById('editPackageId').value = currentSelectedPackageId;
            document.getElementById('editPackageName').value = packageName;
            document.getElementById('editPackagePrice').value = document.getElementById('selectedPackagePrice').textContent.replace(/[^\d.]/g, '');
            document.getElementById('editPackageMaxPerson').value = document.getElementById('selectedPackagePax').textContent.replace(/\D/g, '');

            populateAmenities('edit', currentPackageAmenities);
            document.getElementById('editPackageModal').classList.add('show');
        }

        function closeEditPackageModal() {
            const editModal = document.getElementById('editPackageModal');
            editModal.classList.remove('show');
            editModal.style.display = '';
            editModal.style.zIndex = '';
        }

        function capitalizeString(str) {
            if (!str) return '';
            return str.toLowerCase()
                .split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        }

        function populateAmenities(mode, selectedAmenities) {
            const gridId = mode === 'add' ? 'addAmenitiesGrid' : 'editAmenitiesGrid';
            const grid = document.getElementById(gridId);
            grid.innerHTML = '';

            defaultAmenities.forEach((amenity, index) => {
                const isChecked = selectedAmenities.includes(amenity);
                const checkboxId = `${mode}-amenity-${index}`;

                const amenityDiv = document.createElement('div');
                amenityDiv.className = 'amenity-checkbox';
                amenityDiv.innerHTML = `
                    <input type="checkbox" id="${checkboxId}" name="amenities[]" value="${amenity}" ${isChecked ? 'checked' : ''}>
                    <label for="${checkboxId}">${amenity}</label>
                    <button type="button" class="delete-amenity-btn" onclick="deleteAmenity('${amenity}')" title="Delete this amenity">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                `;
                grid.appendChild(amenityDiv);
            });
        }

        function clearPackageErrors(mode) {
            const prefix = mode === 'add' ? 'add' : 'edit';
            document.getElementById(`${prefix}-error-name`).textContent = '';
            document.getElementById(`${prefix}-error-price`).textContent = '';
            document.getElementById(`${prefix}-error-max_guests`).textContent = '';
            document.getElementById(`${prefix}-error-amenities`).textContent = '';
        }

        function displayPackageErrors(mode, errors) {
            const prefix = mode === 'add' ? 'add' : 'edit';
            for (const [field, messages] of Object.entries(errors)) {
                const errorEl = document.getElementById(`${prefix}-error-${field}`);
                if (errorEl) {
                    errorEl.textContent = messages[0];
                }
            }
        }

        function deletePackage() {
            if (!currentSelectedPackageId) {
                alert('Please select a package to delete');
                return;
            }

            if (!confirm('Are you sure you want to delete this package? This action cannot be undone.')) {
                return;
            }

            fetch(`{{ url('admin/packages') }}/${currentSelectedPackageId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccessDialog(data.message);
                        window.location.reload();
                    } else {
                        showErrorDialog(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorDialog('An error occurred while deleting the package');
                });
        }

        function deleteAmenity(amenityName) {
            if (!confirm(`Are you sure you want to delete the amenity "${amenityName}"? This will remove it from all future package selections.`)) {
                return;
            }

            fetch(`{{ url('admin/amenities/delete') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ name: amenityName })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccessDialog(data.message);
                        window.location.reload();
                    } else {
                        showErrorDialog(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorDialog('An error occurred while deleting the amenity');
                });
        }

        function addCustomAmenity() {
            const input = document.getElementById('newAmenityInput');
            let amenityName = input.value.trim();

            if (amenityName === '') {
                alert('Please enter an amenity name');
                return;
            }

            amenityName = amenityName
                .toLowerCase()
                .split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ')
                .replace(/\s+/g, ' ')
                .trim();

            input.value = amenityName;

            if (defaultAmenities.map(a => a.toLowerCase()).includes(amenityName.toLowerCase())) {
                alert('This amenity already exists');
                return;
            }

            fetch('{{ url('admin/amenities/add') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ name: amenityName })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        defaultAmenities.push(amenityName);
                        populateAmenities('add', [amenityName]);
                        input.value = '';
                        showSuccessDialog(data.message);
                    } else {
                        showErrorDialog(data.message || 'Failed to add amenity');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorDialog('An error occurred while adding the amenity');
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const newAmenityInput = document.getElementById('newAmenityInput');
            if (newAmenityInput) {
                newAmenityInput.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        addCustomAmenity();
                    }
                });
            }

            const addPackageForm = document.getElementById('addPackageForm');
            if (addPackageForm) {
                addPackageForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    submitAddPackageForm();
                });
            }

            const editPackageForm = document.getElementById('editPackageForm');
            if (editPackageForm) {
                editPackageForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    submitEditPackageForm();
                });
            }

            document.querySelectorAll('.modal-overlay').forEach(overlay => {
                overlay.addEventListener('click', function (e) {
                    if (e.target === this) {
                        this.classList.remove('show');
                        this.style.display = '';
                        this.style.zIndex = '';
                    }
                });
            });

            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            });
        });

        function submitAddPackageForm() {
            const form = document.getElementById('addPackageForm');
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');

            const amenities = [];
            form.querySelectorAll('input[name="amenities[]"]:checked').forEach(checkbox => {
                amenities.push(checkbox.value);
            });

            const data = {
                name: formData.get('name'),
                price: formData.get('price'),
                max_guests: formData.get('max_guests'),
                amenities: amenities
            };

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp;&nbsp;Saving...';

            clearPackageErrors('add');

            fetch('{{ route("admin.packages.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccessDialog(data.message);
                        closeAddPackageModal();
                        window.location.reload();
                    } else {
                        if (data.errors) {
                            displayPackageErrors('add', data.errors);
                        } else {
                            showErrorDialog(data.message || 'Failed to create package');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorDialog('An error occurred while creating the package');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i>&nbsp;&nbsp;Save Package';
                });
        }

        function submitEditPackageForm() {
            const form = document.getElementById('editPackageForm');
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const packageId = document.getElementById('editPackageId').value;

            const amenities = [];
            form.querySelectorAll('input[name="amenities[]"]:checked').forEach(checkbox => {
                amenities.push(checkbox.value);
            });

            const data = {
                name: formData.get('name'),
                price: formData.get('price'),
                max_guests: formData.get('max_guests'),
                amenities: amenities
            };

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp;&nbsp;Saving...';

            clearPackageErrors('edit');

            fetch(`{{ url('admin/packages') }}/${packageId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccessDialog(data.message);
                        closeEditPackageModal();
                        window.location.reload();
                    } else {
                        if (data.errors) {
                            displayPackageErrors('edit', data.errors);
                        } else {
                            showErrorDialog(data.message || 'Failed to update package');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorDialog('An error occurred while updating the package');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i>&nbsp;&nbsp;Save Edit';
                });
        }

        function showSuccessDialog(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message,
                confirmButtonColor: '#284B53',
                customClass: {
                    popup: 'swal-custom-popup',
                    confirmButton: 'swal-custom-confirm'
                }
            });
        }

        function showErrorDialog(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#284B53',
                customClass: {
                    popup: 'swal-custom-popup',
                    confirmButton: 'swal-custom-confirm'
                }
            });
        }

        function capitalizeProper(el) {
            // Only capitalize lowercase letters at word boundaries, leave manually capitalized letters alone
            let v = el.value.replace(/\b[a-z]/g, l => l.toUpperCase());
            el.value = v;
            el.setSelectionRange(v.length, v.length);
        }
    </script>
@endsection
