settings

@extends('layouts.admin')

@section('title', 'Settings')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/settings.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/sweetalert-custom.css') }}">
    <style>
        /* Toggle Button (Activate/Disable) */
        .btn-status-toggle {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 110px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-status-toggle i {
            margin-right: 8px;
        }

        .btn-status-toggle.btn-active {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn-status-toggle.btn-active:hover {
            background-color: #fecaca;
        }

        .btn-status-toggle.btn-disabled {
            background-color: #d1fae5;
            color: #10b981;
            border: 1px solid #a7f3d0;
        }

        .btn-status-toggle.btn-disabled:hover {
            background-color: #a7f3d0;
        }

        .account-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
    </style>
@endsection

@section('content')
    <div class="main-content">


        <!-- Toggle Navigation -->
        <div class="settings-toggle">
            <button class="toggle-option active" onclick="switchTab('account-management')">Account Management</button>
            <button class="toggle-option" onclick="switchTab('list-management')">List Management</button>
        </div>

        <!-- Settings Content -->
        <div class="settings-content">
            <!-- Account Management Section -->
            <div id="account-management" class="content-section active">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">User Accounts</h2>
                        <p class="section-subtitle">Manage system users and their access</p>
                    </div>
                    <button class="btn-add-account" onclick="openAddAccountModal()">
                        <i class="fas fa-user-plus"></i>
                        Add Account
                    </button>
                </div>

             
                    <!-- Accounts Table -->
                    <table class="accounts-table">
                        <thead>
                            <tr>
                                <th>Account ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Date Created</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="accountsTableBody">
                            @forelse($users as $user)
                                <tr data-user-id="{{ $user->user_id }}">
                                    <td>{{ $user->user_id }}</td>
                                    <td class="user-name">{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ ucfirst($user->role) }}</td>
                                    <td>{{ $user->created_at->format('M d, Y') }} at {{ $user->created_at->format('g:i A') }}
                                    </td>
                                    <td>
                                        <span class="status-{{ $user->status ?? 'active' }}">
                                            {{ ucfirst($user->status ?? 'Active') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="account-actions">
                                            <button class="btn-edit-account"
                                                data-user-id="{{ $user->user_id }}"
                                                data-name="{{ $user->name }}"
                                                data-email="{{ $user->email }}"
                                                data-role="{{ ucfirst($user->role) }}">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button
                                                class="btn-status-toggle {{ ($user->status ?? 'active') === 'active' ? 'btn-active' : 'btn-disabled' }}"
                                                data-user-id="{{ $user->user_id }}"
                                                data-status="{{ $user->status ?? 'active' }}"
                                                title="{{ ($user->status ?? 'active') === 'active' ? 'Disable this account' : 'Activate this account' }}">
                                                <i class="fas {{ ($user->status ?? 'active') === 'active' ? 'fa-ban' : 'fa-check' }}"></i>
                                                {{ ($user->status ?? 'active') === 'active' ? 'Disable' : 'Activate' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        <i class="fas fa-users"></i>
                                        <p>No accounts found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- List Management Section -->
            <div id="list-management" class="content-section">
                <div class="list-management">
                    <!-- Package List -->
                    <div class="package-list-section">
                        <h3>Package List</h3>
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
                                    <h4 class="package-card-title" id="selectedPackagePrice">Php
                                        {{ number_format($firstPackage->Price, 2) }}
                                    </h4>
                                </div>
                            </div>

                            <div class="package-pax-card">
                                <i class="fas fa-users"></i>
                                <div class="package-card-content">
                                    <h4 class="package-card-title" id="selectedPackagePax">{{ $firstPackage->max_guests }} Pax
                                    </h4>
                                </div>
                            </div>

                            <div class="amenities-section">
                                <h4>Amenities</h4>
                                <ul class="amenities-list" id="selectedPackageAmenities">
                                    @foreach($firstPackage->amenities_array as $amenity)
                                        <li><i class="fas fa-check" style="color: #10b981; margin-right: 8px;"></i>{{ $amenity }}
                                        </li>
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
                        <div class="form-row">
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
                        <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem;">Please check all the amenities
                            included in this package.</p>
                        <div class="error-message" id="add-error-amenities" style="margin-bottom: 0.5rem;"></div>

                        <!-- Add Amenity Section -->
                        <div class="add-amenity-section"
                            style="display: flex; align-items: center; gap: 16px; margin: 20px 0;">
                            <label class="add-amenity-label"
                                style="font-weight: 500; white-space: nowrap; min-width: 100px;">Add
                                Amenity:</label>
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
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Package Name</label>
                                <input type="text" name="name" class="form-input" id="editPackageName"
                                    placeholder="Package Name" required>
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
                        <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem;">Please check all the amenities
                            included in this package.</p>
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

    <!-- Add Account Modal -->
    <div class="modal-overlay" id="addAccountModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add New Account</h3>
                <button class="modal-close" onclick="closeAddAccountModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="addAccountForm">
                @csrf
                <div class="modal-body">
                    <div class="modal-section">
                        <h4 class="modal-section-title">Personal Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user"></i> First Name</label>
                                <input type="text" name="first_name" class="form-input" placeholder="Enter first name"
                                    required oninput="capitalizeProper(this)">
                                <span class="error-message" id="error-first_name"></span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user"></i> Last Name</label>
                                <input type="text" name="last_name" class="form-input" placeholder="Enter last name"
                                    required oninput="capitalizeProper(this)">
                                <span class="error-message" id="error-last_name"></span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user-tag"></i> Role</label>
                                <select name="role" class="form-select" required>
                                    <option value="">Select Role</option>
                                    <option value="admin">Admin</option>
                                    <option value="staff">Staff</option>
                                </select>
                                <span class="error-message" id="error-role"></span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-section">
                        <h4 class="modal-section-title">Account Credentials</h4>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="email" class="form-input" placeholder="Enter email address" required>
                            <span class="error-message" id="error-email"></span>
                        </div>
                        <div class="form-row form-row-password">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="password" id="password" class="form-input"
                                        placeholder="Enter password" required minlength="12"
                                        oninput="validatePasswordFields()">
                                    <button type="button" class="password-toggle" onclick="togglePassword(this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <span class="error-message" id="error-password"></span>
                            </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-lock"></i> Confirm Password</label>
                            <div class="password-input-wrapper">
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-input"
                                    placeholder="Confirm password" required oninput="validatePasswordFields()">
                                <button type="button" class="password-toggle" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <span class="error-message" id="error-password_confirmation"></span>

                            <!-- Success message -->
                            <div id="success-password_match"
                                style="display: none; color: #10b981; align-items: center; margin-top: 8px; font-size: 0.875rem;">
                                <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                                <span>Passwords match and are strong!</span>
                            </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="submit" class="btn-modal-submit">
                            <i class="fas fa-user-plus"></i> Add Account
                        </button>
                    </div>
            </form>
        </div>
    </div>

    <!-- Edit Account Modal -->
    <div class="modal-overlay" id="editAccountModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Edit Account</h3>
                <button class="modal-close" onclick="closeEditAccountModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editAccountForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="editUserId" name="user_id">

                <div class="modal-body">
                    <div class="modal-section">
                        <h4 class="modal-section-title">Personal Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user"></i> First Name</label>
                                <input type="text" class="form-input" id="editFirstName" name="first_name" required
                                    oninput="capitalizeProper(this)">
                                <span class="error-message" id="edit-error-first_name"></span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user"></i> Last Name</label>
                                <input type="text" class="form-input" id="editLastName" name="last_name" required
                                    oninput="capitalizeProper(this)">
                                <span class="error-message" id="edit-error-last_name"></span>
                            </div>

                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user-tag"></i> Role</label>
                                <input type="text" class="form-input" id="editRole" name="role" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="modal-section">
                        <h4 class="modal-section-title">Account Credentials</h4>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" class="form-input" id="editEmail" name="email"
                                placeholder="Enter email address" required>
                            <span class="error-message" id="edit-error-email"></span>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-modal-submit">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
        <script>
            // === PASSWORD VALIDATION FUNCTION ===
       function validatePasswordFields() {
            const password = document.getElementById('password');
            const confirmation = document.getElementById('password_confirmation');
            const errorPassword = document.getElementById('error-password');
            const errorConfirmation = document.getElementById('error-password_confirmation');
            const successMatch = document.getElementById('success-password_match');

            // Reset everything
            if (successMatch) successMatch.style.display = 'none';
            if (errorPassword) errorPassword.textContent = '';
            if (errorConfirmation) errorConfirmation.textContent = '';

            password.setCustomValidity('');
            confirmation.setCustomValidity('');

            const pwd = password.value;
            const confirm = confirmation.value;

            if (pwd === '' || confirm === '') return;

            let hasError = false;

            if (pwd.length < 12) {
                errorPassword.textContent = 'Password must be at least 12 characters long.';
                password.setCustomValidity('Too short');
                hasError = true;
            }

            if (confirm.length < 12) {
                errorConfirmation.textContent = 'Confirm password must be at least 12 characters long.';
                confirmation.setCustomValidity('Too short');
                hasError = true;
            } else if (pwd !== confirm) {
                errorConfirmation.textContent = 'Passwords do not match.';
                confirmation.setCustomValidity('Do not match');
                hasError = true;
            }

            if (!hasError) {
                successMatch.style.display = 'flex';
            }
        }
            // Tab switching functionality
            function switchTab(tabName) {
                // Update toggle buttons
                document.querySelectorAll('.toggle-option').forEach(btn => {
                    btn.classList.remove('active');
                });
                event.target.classList.add('active');

                // Update content sections
                document.querySelectorAll('.content-section').forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById(tabName).classList.add('active');

                // Save current tab to localStorage
                localStorage.setItem('activeSettingsTab', tabName);
            }

            // Restore active tab on page load
            window.addEventListener('DOMContentLoaded', function () {
                const savedTab = localStorage.getItem('activeSettingsTab');
                if (savedTab) {
                    // Update content sections
                    document.querySelectorAll('.content-section').forEach(section => {
                        section.classList.remove('active');
                    });
                    document.getElementById(savedTab).classList.add('active');

                    // Update toggle buttons
                    document.querySelectorAll('.toggle-option').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    const activeButton = savedTab === 'account-management' ?
                        document.querySelectorAll('.toggle-option')[0] :
                        document.querySelectorAll('.toggle-option')[1];
                    activeButton.classList.add('active');
                }
            });

            // Password toggle functionality
            function togglePassword(button) {
                const input = button.previousElementSibling;
                const icon = button.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }



            // Package selection functionality
            @if($packages->count() > 0)
                let currentSelectedPackageId = '{{ $packages->first()->PackageID }}';
                let currentPackageAmenities = @json($packages->first()->amenities_array);
            @else
                let currentSelectedPackageId = null;
                let currentPackageAmenities = [];
            @endif

                function selectPackage(id, name, price, maxPersons, amenities) {
                    // Store current selection
                    currentSelectedPackageId = id;
                    currentPackageAmenities = amenities || [];

                    // Update visual selection
                    document.querySelectorAll('.package-row').forEach(row => {
                        row.classList.remove('selected');
                    });
                    event.target.closest('.package-row').classList.add('selected');

                    // Update package information
                    document.getElementById('selectedPackageName').textContent = name;
                    document.getElementById('selectedPackagePrice').textContent = 'Php ' + parseFloat(price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    document.getElementById('selectedPackagePax').textContent = maxPersons + ' Pax';

                    // Update amenities list
                    const amenitiesList = document.getElementById('selectedPackageAmenities');
                    amenitiesList.innerHTML = '';
                    currentPackageAmenities.forEach(amenity => {
                        const li = document.createElement('li');
                        li.innerHTML = '<i class="fas fa-check" style="color: #10b981; margin-right: 8px;"></i>' + amenity;
                        amenitiesList.appendChild(li);
                    });
                }

            // Modal functionality
            const defaultAmenities = @json($amenities->pluck('name'));

            function openAddPackageModal() {
                // Close any other open modals first
                document.getElementById('editPackageModal').classList.remove('show');

                // Clear form
                document.getElementById('addPackageForm').reset();
                clearPackageErrors('add');

                // Populate amenities checkboxes
                populateAmenities('add', []);

                document.getElementById('addPackageModal').classList.add('show');
            }

            function closeAddPackageModal() {
                const addModal = document.getElementById('addPackageModal');
                addModal.classList.remove('show');
                // Remove any inline styles that might have been added
                addModal.style.display = '';
                addModal.style.zIndex = '';
            }

            function openEditPackageModal() {
                if (!currentSelectedPackageId) {
                    alert('Please select a package to edit');
                    return;
                }

                // Close add modal if open
                document.getElementById('addPackageModal').classList.remove('show');

                // Clear errors
                clearPackageErrors('edit');

                // Get current package name and capitalize it
                let packageName = document.getElementById('selectedPackageName').textContent.trim();
                packageName = capitalizeString(packageName); // Capitalize properly

                // Populate fields
                document.getElementById('editPackageId').value = currentSelectedPackageId;
                document.getElementById('editPackageName').value = packageName;
                document.getElementById('editPackagePrice').value = document.getElementById('selectedPackagePrice').textContent.replace(/[^\d.]/g, '');
                document.getElementById('editPackageMaxPerson').value = document.getElementById('selectedPackagePax').textContent.replace(/\D/g, '');

                // Populate amenities
                populateAmenities('edit', currentPackageAmenities);

                // Show modal
                document.getElementById('editPackageModal').classList.add('show');
            }

            // Helper function to capitalize first letter of each word
            function capitalizeString(str) {
                if (!str) return '';
                return str.toLowerCase()
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');
            }

            function closeEditPackageModal() {
                const editModal = document.getElementById('editPackageModal');
                editModal.classList.remove('show');
                // Remove any inline styles that might have been added
                editModal.style.display = '';
                editModal.style.zIndex = '';
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

            // Delete package
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
                            // Reload page to refresh package list
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

            // Delete amenity from database
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
                            // Reload page to refresh amenities
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

            // Add custom amenity functionality
            function addCustomAmenity() {
                const input = document.getElementById('newAmenityInput');
                let amenityName = input.value.trim();

                if (amenityName === '') {
                    alert('Please enter an amenity name');
                    return;
                }

                // Force proper capitalization: First letter of each word capitalized
                amenityName = amenityName
                    .toLowerCase()
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ')
                    .replace(/\s+/g, ' ')  // ← Add this line to collapse multiple spaces
                    .trim();
                // Update the input field to show the capitalized version
                input.value = amenityName;

                // Check if amenity already exists (case-insensitive, but stored capitalized)
                if (defaultAmenities.map(a => a.toLowerCase()).includes(amenityName.toLowerCase())) {
                    alert('This amenity already exists');
                    return;
                }

                // Save to database
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
                            // Add the properly capitalized name to the array
                            defaultAmenities.push(amenityName);

                            // Refresh the amenities grid (shows the new one checked in Add modal)
                            populateAmenities('add', [amenityName]);

                            // Clear the input
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
            // Remove custom amenity functionality
            function removeCustomAmenity(button) {
                button.parentElement.remove();
            }

            // Allow adding amenity by pressing Enter
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

                // Handle add account form submission
                const addAccountForm = document.getElementById('addAccountForm');
                if (addAccountForm) {
                    addAccountForm.addEventListener('submit', function (e) {
                        e.preventDefault();
                        submitAddAccountForm();
                    });
                }

                // Handle edit account form submission
                const editAccountForm = document.getElementById('editAccountForm');
                if (editAccountForm) {
                    editAccountForm.addEventListener('submit', function (e) {
                        e.preventDefault();
                        submitEditAccountForm();
                    });
                }

                // Event delegation for dynamically added Edit / Status buttons
                const accountsTableBody = document.getElementById('accountsTableBody');
                if (accountsTableBody) {
                    accountsTableBody.addEventListener('click', function (e) {
                        const editBtn = e.target.closest('.btn-edit-account');
                        if (editBtn) {
                            const uid = editBtn.dataset.userId;
                            const name = editBtn.dataset.name || '';
                            const email = editBtn.dataset.email || '';
                            const role = editBtn.dataset.role || '';
                            openEditAccountModal(uid, name, email, role);
                            return;
                        }

                        const statusBtn = e.target.closest('.btn-status-toggle');
                        if (statusBtn) {
                            const uid = statusBtn.dataset.userId;
                            const current = statusBtn.dataset.status || (statusBtn.classList.contains('btn-active') ? 'active' : 'disabled');
                            const newStatus = current === 'active' ? 'disabled' : 'active';
                            updateAccountStatus(uid, newStatus);
                            return;
                        }
                    });
                }

                // Handle add package form submission
                const addPackageForm = document.getElementById('addPackageForm');
                if (addPackageForm) {
                    addPackageForm.addEventListener('submit', function (e) {
                        e.preventDefault();
                        submitAddPackageForm();
                    });
                }

                // Handle edit package form submission
                const editPackageForm = document.getElementById('editPackageForm');
                if (editPackageForm) {
                    editPackageForm.addEventListener('submit', function (e) {
                        e.preventDefault();
                        submitEditPackageForm();
                    });
                }

                // Close modals when clicking outside
                document.querySelectorAll('.modal-overlay').forEach(overlay => {
                    overlay.addEventListener('click', function (e) {
                        if (e.target === this) {
                            this.classList.remove('show');
                            // Remove any inline styles
                            this.style.display = '';
                            this.style.zIndex = '';
                        }
                    });
                });

                // Prevent modal from closing when clicking inside the modal content
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.addEventListener('click', function (e) {
                        e.stopPropagation();
                    });
                });
            });

            // Function to submit add package form
            function submitAddPackageForm() {
                const form = document.getElementById('addPackageForm');
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');

                // Get selected amenities
                const amenities = [];
                form.querySelectorAll('input[name="amenities[]"]:checked').forEach(checkbox => {
                    amenities.push(checkbox.value);
                });

                // Clear FormData and rebuild with amenities array
                const data = {
                    name: formData.get('name'),
                    price: formData.get('price'),
                    max_guests: formData.get('max_guests'),
                    amenities: amenities
                };

                // Disable submit button
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp;&nbsp;Saving...';

                // Clear previous errors
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
                            // Reload page to refresh package list
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

            // Function to submit edit package form
            function submitEditPackageForm() {
                const form = document.getElementById('editPackageForm');
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');
                const packageId = document.getElementById('editPackageId').value;

                // Get selected amenities
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

                // Disable submit button
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp;&nbsp;Saving...';

                // Clear previous errors
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
                            // Reload page to refresh package list
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

            // Function to submit add account form
            function submitAddAccountForm() {
                const form = document.getElementById('addAccountForm');
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');

                // Disable submit button
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

                // Clear previous errors
                clearErrors();

                fetch('{{ route("admin.settings.store-account") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            showSuccessDialog('Account created successfully!');

                            // Reset form
                            form.reset();

                            // Add new row to table
                            addAccountToTable(data.user);

                            // Close modal
                            closeAddAccountModal();
                        } else {
                            // Show validation errors
                            if (data.errors) {
                                displayErrors(data.errors);
                            } else {
                                showErrorDialog(data.message || 'Failed to create account');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showErrorDialog('An error occurred while creating the account');
                    })
                    .finally(() => {
                        // Re-enable submit button
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Add Account';
                    });
            }

            // Function to submit edit account form
            function submitEditAccountForm() {
                const form = document.getElementById('editAccountForm');
                const userId = document.getElementById('editUserId').value;
                const submitBtn = form.querySelector('button[type="submit"]');

                // Disable submit button
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

                // Clear previous errors
                document.querySelectorAll('#editAccountForm .error-message').forEach(el => el.textContent = '');

                // Get form data as JSON
                const data = {
                    first_name: document.getElementById('editFirstName').value,
                    last_name: document.getElementById('editLastName').value,
                    email: document.getElementById('editEmail').value
                };

                fetch(`{{ route("admin.settings.update-account", ":userId") }}`.replace(':userId', userId), {
                    method: 'PUT',
                    body: JSON.stringify(data),
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            showSuccessDialog('Account updated successfully!');

                            // Close modal
                            closeEditAccountModal();

                            // Reload page to show updated data
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            // Show validation errors
                            if (data.errors) {
                                for (const [field, messages] of Object.entries(data.errors)) {
                                    const errorEl = document.getElementById(`edit-error-${field}`);
                                    if (errorEl) {
                                        errorEl.textContent = messages[0];
                                    }
                                }
                            } else {
                                showErrorDialog(data.message || 'Failed to update account');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showErrorDialog('An error occurred while updating the account');
                    })
                    .finally(() => {
                        // Re-enable submit button
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                    });
            }

            // Function to update account status
            function updateAccountStatus(userId, status) {
                const action = status === 'active' ? 'activate' : 'disable';
                if (!confirm(`Are you sure you want to ${action} this account?`)) return;

                fetch(`{{ route("admin.settings.update-account-status", ":userId") }}`.replace(':userId', userId), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status })
                })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) {
                            updateAccountStatusInTable(userId, status);
                            showSuccessDialog(`Account ${action}d successfully!`);
                        } else {
                            showErrorDialog(d.message || 'Failed');
                        }
                    });
            }

            // Helper functions
            function clearErrors() {
                document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
            }

            function displayErrors(errors) {
                for (const [field, messages] of Object.entries(errors)) {
                    const errorEl = document.getElementById(`error-${field}`);
                    if (errorEl) {
                        errorEl.textContent = messages[0];
                    }
                }
            }

            function escapeAttr(s) {
                return String(s ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }

            function addAccountToTable(user) {
                const tbody = document.getElementById('accountsTableBody');
                const emptyRow = tbody.querySelector('td[colspan="7"]');
                if (emptyRow) {
                    emptyRow.parentElement.remove();
                }

                const uid = escapeAttr(user.user_id);
                const name = escapeAttr(user.name);
                const email = escapeAttr(user.email);
                const role = escapeAttr((user.role || '').charAt(0).toUpperCase() + (user.role || '').slice(1));
                const createdAt = escapeAttr(user.created_at);
                const status = escapeAttr(user.status || 'active');

                const newRow = `
                    <tr data-user-id="${uid}">
                        <td>${uid}</td>
                        <td class="user-name">${name}</td>
                        <td>${email}</td>
                        <td>${role}</td>
                        <td>${createdAt}</td>
                        <td>
                            <span class="status-${status}">
                                ${status.charAt(0).toUpperCase() + status.slice(1)}
                            </span>
                        </td>
                        <td>
                            <div class="account-actions">
                                <button class="btn-edit-account" data-user-id="${uid}" data-name="${name}" data-email="${email}" data-role="${role}">
                                    <i class='fas fa-edit'></i> Edit
                                </button>
                                <button class="btn-status-toggle ${status === 'active' ? 'btn-active' : 'btn-disabled'}" data-user-id="${uid}" data-status="${status}" title="${status === 'active' ? 'Disable this account' : 'Activate this account'}">
                                    <i class="fas ${status === 'active' ? 'fa-ban' : 'fa-check'}"></i>
                                    ${status === 'active' ? 'Disable' : 'Activate'}
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('afterbegin', newRow);
            }
            function updateAccountStatusInTable(userId, newStatus) {
                const row = document.querySelector(`tr[data-user-id="${userId}"]`);
                if (!row) return;

                const statusSpan = row.querySelector('td:nth-child(6) span');
                const toggleBtn = row.querySelector('.btn-status-toggle');

                // Update status text and class
                statusSpan.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                statusSpan.className = `status-${newStatus}`;

                // Determine opposite status for next click
                const oppositeStatus = newStatus === 'active' ? 'disabled' : 'active';
                const isActive = newStatus === 'active';

                // Update button appearance and behavior
                toggleBtn.classList.toggle('btn-active', isActive);
                toggleBtn.classList.toggle('btn-disabled', !isActive);

                toggleBtn.innerHTML = `
                                            <i class="fas fa-${isActive ? 'ban' : 'check'}"></i>
                                            ${isActive ? 'Disable' : 'Activate'}
                                        `;

                toggleBtn.title = isActive ? 'Disable this account' : 'Activate this account';

                // Update onclick to toggle to the opposite status
                toggleBtn.onclick = () => updateAccountStatus(userId, oppositeStatus);
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
            function capitalizeProper(el) {
                // Only capitalize the first letter of each word
                // Do NOT collapse spaces — keep everything as typed
                let v = el.value.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());

                el.value = v;
                el.setSelectionRange(v.length, v.length); // Keep cursor at the end
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

            // Add Account Modal functions
         function openAddAccountModal() {
                document.getElementById('addAccountModal').classList.add('show');
                document.getElementById('addAccountForm').reset();
                clearErrors();

                // Reset success message
                const successMsg = document.getElementById('success-password_match');
                if (successMsg) successMsg.style.display = 'none';
            }

            function closeAddAccountModal() {
                document.getElementById('addAccountModal').classList.remove('show');
            }

            function openEditAccountModal(userId, name, email, role) {
                document.getElementById('editUserId').value = userId;
                // Split the full name (e.g., "John Doe") into first and last name
                const nameParts = name.trim().split(' ');
                const firstName = nameParts[0] || '';
                const lastName = nameParts.slice(1).join(' ') || '';
                document.getElementById('editFirstName').value = firstName;
                document.getElementById('editLastName').value = lastName;
                document.getElementById('editEmail').value = email;
                document.getElementById('editRole').value = role;
                // Clear any previous error messages
                document.querySelectorAll('#editAccountForm .error-message').forEach(el => el.textContent = '');
                // Show the modal
                document.getElementById('editAccountModal').classList.add('show');
            }

            function closeEditAccountModal() {
                document.getElementById('editAccountModal').classList.remove('show');
            }

            function submitEditAccountForm() {
                const form = document.getElementById('editAccountForm');
                const userId = document.getElementById('editUserId').value;
                const submitBtn = form.querySelector('button[type="submit"]');

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

                // Clear previous errors
                document.querySelectorAll('#editAccountForm .error-message').forEach(el => el.textContent = '');

                const data = {
                    first_name: document.getElementById('editFirstName').value,
                    last_name: document.getElementById('editLastName').value,
                    email: document.getElementById('editEmail').value
                };

                fetch(`{{ route("admin.settings.update-account", ":userId") }}`.replace(':userId', userId), {
                    method: 'PUT',
                    body: JSON.stringify(data),
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccessDialog('Account updated successfully!');
                            closeEditAccountModal();
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            if (data.errors) {
                                for (const [field, messages] of Object.entries(data.errors)) {
                                    const errorEl = document.getElementById(`edit-error-${field}`);
                                    if (errorEl) errorEl.textContent = messages[0];
                                }
                            } else {
                                showErrorDialog(data.message || 'Failed to update account');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showErrorDialog('An error occurred while updating the account');
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                    });
            }
        </script>

        <style>
            .delete-amenity-btn {
                background: transparent;
                border: none;
                color: #dc2626;
                cursor: pointer;
                padding: 4px 8px;
                margin-left: 8px;
                border-radius: 4px;
                transition: all 0.2s;
                font-size: 0.875rem;
            }

            .delete-amenity-btn:hover {
                background: #fee2e2;
                color: #b91c1c;
            }

            .amenity-checkbox {
                display: flex;
                align-items: center;
                position: relative;
            }

            .amenity-checkbox label {
                flex: 1;
            }

            .amenities-list li {
                list-style: none;
                padding: 8px 0;
            }
        </style>
@endsection