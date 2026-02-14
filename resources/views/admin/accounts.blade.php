@extends('layouts.admin')

@section('title', 'Accounts')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/settings.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/accounts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/sweetalert-custom.css') }}">
@endsection

@section('content')
    <div class="main-content">
        <div class="section-header">
            <div>
                <h1 class="section-title">Accounts Management</h1>
            </div>
            <button class="btn-add-account" onclick="openAddAccountModal()">
                <i class="fas fa-user-plus"></i> 
                Add Account
            </button>
        </div>

        <!-- Search Field -->
        <div style="margin-bottom: 1.5rem;">
            <div style="position: relative; max-width: 400px;">
                <input type="text" id="searchInput" placeholder="Search by name or ID..." 
                    style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 2.5rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.875rem; transition: border-color 0.2s;"
                    oninput="searchAccounts(this.value)"
                    onfocus="this.style.borderColor='#284B53'"
                    onblur="this.style.borderColor='#e5e7eb'">
                <i class="fas fa-search" style="position: absolute; left: 0.875rem; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 0.875rem;"></i>
            </div>
        </div>

        <!-- Accounts Table -->
        <table class="accounts-table">
                    <thead>
                        <tr>
                            <th>Account ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Gender</th>
                            <th>    Date Created</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="accountsTableBody">
                        @forelse($users as $user)
                            <tr data-user-id="{{ $user->user_id }}">
                                <td>{{ $user->user_id }}</td>
                                <td class="user-name">{{ trim($user->name . ' ' . ($user->middle_name ?? '')) }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ ucfirst($user->role) }}</td>
                                <td>{{ $user->gender ? substr($user->gender, 0, 1) : '-' }}</td>
                                <td>{{ $user->created_at->format('M d, Y') }} at {{ $user->created_at->format('g:i A') }}</td>
                                <td>
                                    <span class="status-{{ $user->status ?? 'active' }}">
                                        {{ ucfirst($user->status ?? 'Active') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="account-actions">
                                        <button class="btn-edit-account"
                                            data-user-id="{{ $user->user_id }}"
                                            data-first-name="{{ explode(' ', $user->name)[0] ?? '' }}"
                                            data-last-name="{{ implode(' ', array_slice(explode(' ', $user->name), 1)) ?? '' }}"
                                            data-middle-name="{{ $user->middle_name ?? '' }}"
                                            data-gender="{{ $user->gender ?? '' }}"
                                            data-address="{{ $user->address ?? '' }}"
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
                                <td colspan="8" class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <p>No accounts found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
                        <div class="form-row form-row-4">
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
                                <label class="form-label"><i class="fas fa-user"></i> Middle Name</label>
                                <input type="text" name="middle_name" class="form-input" placeholder="Enter middle name"
                                    oninput="capitalizeProper(this)">
                                <span class="error-message" id="error-middle_name"></span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-venus-mars"></i> Gender</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                                <span class="error-message" id="error-gender"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-map-marker-alt"></i> Address</label>
                            <input type="text" name="address" class="form-input" placeholder="Enter complete address"
                                required oninput="capitalizeProper(this)">
                            <span class="error-message" id="error-address"></span>
                        </div>
                    </div>

                    <div class="modal-section">
                        <h4 class="modal-section-title">Account Credentials</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" name="email" class="form-input" placeholder="Enter email address" required>
                                <span class="error-message" id="error-email"></span>
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
                            <i class="fas fa-user-plus"></i> &nbsp; Add Account
                        </button>
                    </div>
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
                        <div class="form-row form-row-4">
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
                                <label class="form-label"><i class="fas fa-user"></i> Middle Name</label>
                                <input type="text" class="form-input" id="editMiddleName" name="middle_name"
                                    oninput="capitalizeProper(this)">
                                <span class="error-message" id="edit-error-middle_name"></span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-venus-mars"></i> Gender</label>
                                <select class="form-select" id="editGender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                                <span class="error-message" id="edit-error-gender"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-map-marker-alt"></i> Address</label>
                            <input type="text" class="form-input" id="editAddress" name="address"
                                placeholder="Enter complete address" required oninput="capitalizeProper(this)">
                            <span class="error-message" id="edit-error-address"></span>
                        </div>
                    </div>

                    <div class="modal-section">
                        <h4 class="modal-section-title">Account Credentials</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" class="form-input" id="editEmail" name="email"
                                    placeholder="Enter email address" required>
                                <span class="error-message" id="edit-error-email"></span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user-tag"></i> Role</label>
                                <input type="text" class="form-input" id="editRole" name="role" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-modal-submit">
                        <i class="fas fa-save"></i> &nbsp; Save Changes
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

        document.addEventListener('DOMContentLoaded', function () {
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
                        const firstName = editBtn.dataset.firstName || '';
                        const lastName = editBtn.dataset.lastName || '';
                        const middleName = editBtn.dataset.middleName || '';
                        const gender = editBtn.dataset.gender || '';
                        const address = editBtn.dataset.address || '';
                        const email = editBtn.dataset.email || '';
                        const role = editBtn.dataset.role || '';
                        openEditAccountModal(uid, firstName, lastName, email, role, middleName, gender, address);
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

            // Close modals when clicking outside
            document.querySelectorAll('.modal-overlay').forEach(overlay => {
                overlay.addEventListener('click', function (e) {
                    if (e.target === this) {
                        this.classList.remove('show');
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
                        showSuccessDialog('Account created successfully!');
                        form.reset();
                        addAccountToTable(data.user);
                        closeAddAccountModal();
                    } else {
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
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Add Account';
                });
        }

        // Function to submit edit account form
        function submitEditAccountForm() {
            const form = document.getElementById('editAccountForm');
            const userId = document.getElementById('editUserId').value;
            const submitBtn = form.querySelector('button[type="submit"]');

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            document.querySelectorAll('#editAccountForm .error-message').forEach(el => el.textContent = '');

            const data = {
                first_name: document.getElementById('editFirstName').value,
                last_name: document.getElementById('editLastName').value,
                middle_name: document.getElementById('editMiddleName').value,
                gender: document.getElementById('editGender').value,
                address: document.getElementById('editAddress').value,
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
            const emptyRow = tbody.querySelector('td[colspan="8"]');
            if (emptyRow) {
                emptyRow.parentElement.remove();
            }

            const uid = escapeAttr(user.user_id);
            const firstName = escapeAttr(user.first_name || '');
            const lastName = escapeAttr(user.last_name || '');
            const middleName = escapeAttr(user.middle_name || '');
            const fullName = escapeAttr(((user.first_name || '') + ' ' + (user.middle_name || '') + ' ' + (user.last_name || '')).trim());
            const email = escapeAttr(user.email);
            const role = escapeAttr((user.role || '').charAt(0).toUpperCase() + (user.role || '').slice(1));
            const gender = escapeAttr((user.gender || '-').charAt(0));
            const createdAt = escapeAttr(user.created_at);
            const status = escapeAttr(user.status || 'active');
            const address = escapeAttr(user.address || '');

            const newRow = `
                <tr data-user-id="${uid}">
                    <td>${uid}</td>
                    <td class="user-name">${fullName}</td>
                    <td>${email}</td>
                    <td>${role}</td>
                    <td>${gender}</td>
                    <td>${createdAt}</td>
                    <td>
                        <span class="status-${status}">
                            ${status.charAt(0).toUpperCase() + status.slice(1)}
                        </span>
                    </td>
                    <td>
                        <div class="account-actions">
                            <button class="btn-edit-account" data-user-id="${uid}" data-first-name="${firstName}" data-last-name="${lastName}" data-middle-name="${middleName}" data-gender="${user.gender || ''}" data-address="${address}" data-email="${email}" data-role="${role}">
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

            const statusSpan = row.querySelector('td:nth-child(7) span');
            const toggleBtn = row.querySelector('.btn-status-toggle');

            statusSpan.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            statusSpan.className = `status-${newStatus}`;

            const oppositeStatus = newStatus === 'active' ? 'disabled' : 'active';
            const isActive = newStatus === 'active';

            toggleBtn.classList.toggle('btn-active', isActive);
            toggleBtn.classList.toggle('btn-disabled', !isActive);

            toggleBtn.innerHTML = `
                <i class="fas fa-${isActive ? 'ban' : 'check'}"></i>
                ${isActive ? 'Disable' : 'Activate'}
            `;

            toggleBtn.title = isActive ? 'Disable this account' : 'Activate this account';
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
            // Only capitalize lowercase letters at word boundaries, leave manually capitalized letters alone
            let v = el.value.replace(/\b[a-z]/g, l => l.toUpperCase());
            el.value = v;
            el.setSelectionRange(v.length, v.length);
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

        function openAddAccountModal() {
            document.getElementById('addAccountModal').classList.add('show');
            document.getElementById('addAccountForm').reset();
            clearErrors();

            const successMsg = document.getElementById('success-password_match');
            if (successMsg) successMsg.style.display = 'none';
        }

        function closeAddAccountModal() {
            document.getElementById('addAccountModal').classList.remove('show');
        }

        function openEditAccountModal(userId, firstName, lastName, email, role, middleName = '', gender = '', address = '') {
            document.getElementById('editUserId').value = userId;
            document.getElementById('editFirstName').value = firstName;
            document.getElementById('editLastName').value = lastName;
            document.getElementById('editMiddleName').value = middleName;
            document.getElementById('editGender').value = gender;
            document.getElementById('editAddress').value = address;
            document.getElementById('editEmail').value = email;
            document.getElementById('editRole').value = role;
            document.querySelectorAll('#editAccountForm .error-message').forEach(el => el.textContent = '');
            document.getElementById('editAccountModal').classList.add('show');
        }

        function closeEditAccountModal() {
            document.getElementById('editAccountModal').classList.remove('show');
        }

        // Search function
        function searchAccounts(query) {
            const searchTerm = query.toLowerCase().trim();
            const rows = document.querySelectorAll('#accountsTableBody tr');
            
            rows.forEach(row => {
                // Skip empty state row
                if (row.querySelector('.empty-state')) {
                    return;
                }
                
                const userId = row.querySelector('td:nth-child(1)')?.textContent.toLowerCase() || '';
                const userName = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
                
                if (userId.includes(searchTerm) || userName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
@endsection
