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
                    <th>Date Created</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="accountsTableBody">
                @forelse($users as $user)
                    @php
                        $userData = [
                            'id'      => $user->user_id,
                            'first'   => explode(' ', $user->name)[0] ?? '',
                            'last'    => implode(' ', array_slice(explode(' ', $user->name), 1)) ?? '',
                            'middle'  => $user->middle_name ?? '',
                            'gender'  => $user->gender ?? '',
                            'address' => $user->address ?? '',
                            'email'   => $user->email,
                            'role'    => ucfirst($user->role ?? ''),
                        ];
                    @endphp

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
                                <button class="btn-edit-account js-edit-account"
                                        data-user="{{ json_encode($userData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-status-toggle {{ ($user->status ?? 'active') === 'active' ? 'btn-active' : 'btn-disabled' }}"
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
                                <label class="form-label"><i class="fas fa-user"></i> First Name <span class="required">*</span></label>
                                <input type="text" name="first_name" class="form-input" placeholder="Enter first name" required oninput="capitalizeProper(this)">
                                <span class="error-message" id="error-first_name"></span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user"></i> Last Name <span class="required">*</span></label>
                                <input type="text" name="last_name" class="form-input" placeholder="Enter last name" required oninput="capitalizeProper(this)">
                                <span class="error-message" id="error-last_name"></span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user"></i> Middle Name<small class="optional-text">(optional)</small></label>
                                <input type="text" name="middle_name" class="form-input" placeholder="Enter middle name" oninput="capitalizeProper(this)">
                                <span class="error-message" id="error-middle_name"></span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-venus-mars"></i> Gender <span class="required">*</span></label>
                                <select name="gender" class="form-select">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                                <span class="error-message" id="error-gender"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-map-marker-alt"></i> Address <span class="required">*</span></label>
                            <input type="text" name="address" class="form-input" placeholder="Enter complete address" oninput="capitalizeProper(this)">
                            <span class="error-message" id="error-address"></span>
                        </div>
                    </div>

                    <div class="modal-section">
                        <h4 class="modal-section-title">Account Credentials</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-envelope"></i> Email <span class="required">*</span></label>
                                <input type="email" 
                                       name="email" 
                                       class="form-input" 
                                       placeholder="e.g. juan123@gmail.com" 
                                       required 
                                       pattern="^[a-zA-Z][a-zA-Z0-9._%+-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                                       title="Email must start with a letter. Pure numeric usernames (e.g. 123456@domain.com) are not allowed."
                                       autocomplete="email">
                                <span class="error-message" id="error-email"></span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user-tag"></i> Role <span class="required">*</span></label>
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
                                <label class="form-label"><i class="fas fa-lock"></i> Password <span class="required">*</span></label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="password" id="password" class="form-input"
                                           placeholder="Enter password" required minlength="12" oninput="validatePasswordFields()">
                                    <button type="button" class="password-toggle" onclick="togglePassword(this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <span class="error-message" id="error-password"></span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-lock"></i> Confirm Password <span class="required">*</span></label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-input"
                                           placeholder="Confirm password" required oninput="validatePasswordFields()">
                                    <button type="button" class="password-toggle" onclick="togglePassword(this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <span class="error-message" id="error-password_confirmation"></span>

                                <div id="success-password_match" style="display:none; color:#10b981; font-size:0.875rem; margin-top:6px;">
                                    <i class="fas fa-check-circle"></i> Passwords match!
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="submit" class="btn-modal-submit">
                            <i class="fas fa-user-plus"></i> Add Account
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
                                <label class="form-label"><i class="fas fa-user"></i> First Name <span class="required">*</span></label>
                                <input type="text" id="editFirstName" name="first_name" class="form-input" required oninput="capitalizeProper(this)">
                                <span class="error-message" id="edit-error-first_name"></span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user"></i> Last Name <span class="required">*</span></label>
                                <input type="text" id="editLastName" name="last_name" class="form-input" required oninput="capitalizeProper(this)">
                                <span class="error-message" id="edit-error-last_name"></span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user"></i> Middle Name<small class="optional-text">(optional)</small></label>
                                <input type="text" id="editMiddleName" name="middle_name" class="form-input" oninput="capitalizeProper(this)">
                                <span class="error-message" id="edit-error-middle_name"></span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-venus-mars"></i> Gender <span class="required">*</span></label>
                                <select id="editGender" name="gender" class="form-select">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                                <span class="error-message" id="edit-error-gender"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-map-marker-alt"></i> Address <span class="required">*</span></label>
                            <input type="text" id="editAddress" name="address" class="form-input" oninput="capitalizeProper(this)">
                            <span class="error-message" id="edit-error-address"></span>
                        </div>
                    </div>

                    <div class="modal-section">
                        <h4 class="modal-section-title">Account Credentials</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-envelope"></i> Email <span class="required">*</span></label>
                                <input type="email" 
                                       id="editEmail" 
                                       name="email" 
                                       class="form-input" 
                                       placeholder="e.g. maria456@yahoo.com" 
                                       required 
                                       pattern="^[a-zA-Z][a-zA-Z0-9._%+-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                                       title="Email must start with a letter. Pure numeric usernames (e.g. 123456@domain.com) are not allowed."
                                       autocomplete="email">
                                <span class="error-message" id="edit-error-email"></span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user-tag"></i> Role<small class="optional-text">(cannot be edited)</small></label>
                                <input type="text" id="editRole" name="role" class="form-input" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="submit" class="btn-modal-submit">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
// ────────────────────────────────────────────────
// Password & helpers
// ────────────────────────────────────────────────
function validatePasswordFields() {
    const p = document.getElementById('password');
    const c = document.getElementById('password_confirmation');
    if (!p || !c) return;

    const ep = document.getElementById('error-password');
    const ec = document.getElementById('error-password_confirmation');
    const success = document.getElementById('success-password_match');

    ep.textContent = ''; ec.textContent = ''; success.style.display = 'none';

    const pv = p.value.trim();
    const cv = c.value.trim();

    if (!pv && !cv) return;

    let err = false;

    if (pv.length < 12) { ep.textContent = 'Password must be at least 12 characters.'; err = true; }
    if (cv.length < 12) { ec.textContent = 'Confirm password must be at least 12 characters.'; err = true; }
    else if (pv !== cv) { ec.textContent = 'Passwords do not match.'; err = true; }

    if (!err && pv && cv) success.style.display = 'block';
}

function togglePassword(btn) {
    const input = btn.previousElementSibling;
    const icon = btn.querySelector('i');
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
}

function capitalizeProper(el) {
    el.value = el.value.replace(/\b[a-z]/g, l => l.toUpperCase());
}

// ────────────────────────────────────────────────
// Email validation (no all-numeric local part)
// ────────────────────────────────────────────────
function validateEmail(inputEl, errorId) {
    const val = inputEl.value.trim();
    const errEl = document.getElementById(errorId);
    if (!errEl) return true;

    errEl.textContent = '';

    if (!val) {
        errEl.textContent = 'Email is required.';
        return false;
    }

    // Basic format: starts with letter
    const formatRegex = /^[a-zA-Z][a-zA-Z0-9._%+-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!formatRegex.test(val)) {
        errEl.textContent = 'Invalid email format. Must start with a letter.';
        return false;
    }

    // NEW RULE: local part must NOT be purely numeric
    const localPart = val.split('@')[0];
    if (/^\d+$/.test(localPart)) {
        errEl.textContent = 'Email username cannot be only numbers (e.g. 123456@domain.com is not allowed).';
        return false;
    }

    if (val.length > 254) {
        errEl.textContent = 'Email is too long (max 254 characters).';
        return false;
    }

    return true;
}

function clearFormErrors(formId) {
    document.querySelectorAll(`#${formId} .error-message`).forEach(el => el.textContent = '');
}

// ────────────────────────────────────────────────
// Modal controls
// ────────────────────────────────────────────────
function openAddAccountModal() {
    document.getElementById('addAccountModal').classList.add('show');
    document.getElementById('addAccountForm').reset();
    clearFormErrors('addAccountForm');
    document.getElementById('success-password_match').style.display = 'none';
}

function closeAddAccountModal() {
    document.getElementById('addAccountModal').classList.remove('show');
}

function openEditAccountModal(id, first, last, email, role, middle='', gender='', address='') {
    document.getElementById('editUserId').value     = id;
    document.getElementById('editFirstName').value  = first;
    document.getElementById('editLastName').value   = last;
    document.getElementById('editMiddleName').value = middle;
    document.getElementById('editGender').value     = gender;
    document.getElementById('editAddress').value    = address;
    document.getElementById('editEmail').value      = email;
    document.getElementById('editRole').value       = role;

    clearFormErrors('editAccountForm');
    document.getElementById('editAccountModal').classList.add('show');
}

function closeEditAccountModal() {
    document.getElementById('editAccountModal').classList.remove('show');
}

function searchAccounts(query) {
    const term = query.toLowerCase().trim();
    document.querySelectorAll('#accountsTableBody tr').forEach(row => {
        if (row.querySelector('.empty-state')) return;
        const id   = row.cells[0]?.textContent.toLowerCase() || '';
        const name = row.cells[1]?.textContent.toLowerCase() || '';
        row.style.display = (id.includes(term) || name.includes(term)) ? '' : 'none';
    });
}

// ────────────────────────────────────────────────
// Event listeners
// ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('accountsTableBody');
    if (tbody) {
        tbody.addEventListener('click', e => {
            const editBtn = e.target.closest('.js-edit-account');
            if (editBtn) {
                try {
                    const data = JSON.parse(editBtn.dataset.user);
                    openEditAccountModal(
                        data.id, data.first, data.last, data.email, data.role,
                        data.middle, data.gender, data.address
                    );
                } catch (err) {
                    console.error('Invalid edit data');
                }
                return;
            }

            const statusBtn = e.target.closest('.btn-status-toggle');
            if (statusBtn) {
                const uid = statusBtn.dataset.userId;
                let cur = statusBtn.dataset.status || (statusBtn.classList.contains('btn-active') ? 'active' : 'disabled');
                const next = cur === 'active' ? 'disabled' : 'active';
                updateAccountStatus(uid, next);
            }
        });
    }

    // Real-time email validation
    const addEmail = document.querySelector('#addAccountForm input[name="email"]');
    if (addEmail) {
        ['input', 'blur'].forEach(ev => addEmail.addEventListener(ev, () => validateEmail(addEmail, 'error-email')));
    }

    const editEmail = document.getElementById('editEmail');
    if (editEmail) {
        ['input', 'blur'].forEach(ev => editEmail.addEventListener(ev, () => validateEmail(editEmail, 'edit-error-email')));
    }

    document.getElementById('addAccountForm')?.addEventListener('submit', e => {
        e.preventDefault();
        const emailInput = e.target.querySelector('input[name="email"]');
        if (validateEmail(emailInput, 'error-email')) submitAddAccountForm();
    });

    document.getElementById('editAccountForm')?.addEventListener('submit', e => {
        e.preventDefault();
        if (validateEmail(document.getElementById('editEmail'), 'edit-error-email')) submitEditAccountForm();
    });
});

// ────────────────────────────────────────────────
// Submit / status functions (fetch)
// ────────────────────────────────────────────────
async function submitAddAccountForm() {
    const form = document.getElementById('addAccountForm');
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

    try {
        const res = await fetch('{{ route("admin.settings.store-account") }}', {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();

        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Success', text: 'Account created!' });
            form.reset();
            addAccountToTable(data.user);
            closeAddAccountModal();
        } else if (data.errors) {
            Object.entries(data.errors).forEach(([k, msgs]) => {
                document.getElementById(`error-${k}`)?.setAttribute('textContent', msgs[0]);
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed' });
        }
    } catch {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Network error' });
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-user-plus"></i> Add Account';
    }
}

async function submitEditAccountForm() {
    const form = document.getElementById('editAccountForm');
    const id = document.getElementById('editUserId').value;
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    const payload = {
        first_name:  document.getElementById('editFirstName').value.trim(),
        last_name:   document.getElementById('editLastName').value.trim(),
        middle_name: document.getElementById('editMiddleName').value.trim(),
        gender:      document.getElementById('editGender').value,
        address:     document.getElementById('editAddress').value.trim(),
        email:       document.getElementById('editEmail').value.trim()
    };

    try {
        const res = await fetch(`{{ route("admin.settings.update-account", ":id") }}`.replace(':id', id), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Success', text: 'Account updated!' });
            closeEditAccountModal();
            setTimeout(() => location.reload(), 1200);
        } else if (data.errors) {
            Object.entries(data.errors).forEach(([k, msgs]) => {
                document.getElementById(`edit-error-${k}`)?.setAttribute('textContent', msgs[0]);
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed' });
        }
    } catch {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Network error' });
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
    }
}

async function updateAccountStatus(uid, newStatus) {
    if (!confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'disable'} this account?`)) return;

    try {
        const res = await fetch(`{{ route("admin.settings.update-account-status", ":id") }}`.replace(':id', uid), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ status: newStatus })
        });
        const data = await res.json();

        if (data.success) {
            const row = document.querySelector(`tr[data-user-id="${uid}"]`);
            if (row) {
                const span = row.querySelector('td:nth-child(7) span');
                span.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                span.className = `status-${newStatus}`;

                const btn = row.querySelector('.btn-status-toggle');
                btn.className = `btn-status-toggle ${newStatus === 'active' ? 'btn-active' : 'btn-disabled'}`;
                btn.dataset.status = newStatus;
                btn.title = newStatus === 'active' ? 'Disable this account' : 'Activate this account';
                btn.innerHTML = `<i class="fas ${newStatus === 'active' ? 'fa-ban' : 'fa-check'}"></i> ${newStatus === 'active' ? 'Disable' : 'Activate'}`;
            }
            Swal.fire({ icon: 'success', title: 'Success', text: `Account ${newStatus}d` });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed' });
        }
    } catch {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Network error' });
    }
}

function addAccountToTable(user) {
    const tbody = document.getElementById('accountsTableBody');
    if (!tbody) return;

    tbody.querySelector('tr td[colspan="8"]')?.closest('tr')?.remove();

    const tr = document.createElement('tr');
    tr.dataset.userId = user.user_id;

    const cell = (text, cls = '') => {
        const td = document.createElement('td');
        if (cls) td.className = cls;
        td.textContent = text ?? '';
        return td;
    };

    tr.appendChild(cell(user.user_id));
    const name = [user.first_name, user.middle_name, user.last_name].filter(Boolean).join(' ').trim();
    tr.appendChild(cell(name, 'user-name'));
    tr.appendChild(cell(user.email));
    const role = (user.role || '').charAt(0).toUpperCase() + (user.role || '').slice(1);
    tr.appendChild(cell(role));
    tr.appendChild(cell(user.gender ? user.gender.charAt(0) : '-'));

    const date = new Date(user.created_at);
    const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) +
                    ' at ' + date.toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
    tr.appendChild(cell(dateStr));

    const statusTd = document.createElement('td');
    const span = document.createElement('span');
    const status = user.status || 'active';
    span.className = `status-${status}`;
    span.textContent = status.charAt(0).toUpperCase() + status.slice(1);
    statusTd.appendChild(span);
    tr.appendChild(statusTd);

    const actionsTd = document.createElement('td');
    const div = document.createElement('div');
    div.className = 'account-actions';

    const editBtn = document.createElement('button');
    editBtn.className = 'btn-edit-account js-edit-account';
    editBtn.innerHTML = '<i class="fas fa-edit"></i> Edit';
    editBtn.dataset.user = JSON.stringify({
        id: user.user_id,
        first: user.first_name || '',
        last: user.last_name || '',
        middle: user.middle_name || '',
        gender: user.gender || '',
        address: user.address || '',
        email: user.email || '',
        role
    });

    const statusBtn = document.createElement('button');
    statusBtn.className = `btn-status-toggle ${status === 'active' ? 'btn-active' : 'btn-disabled'}`;
    statusBtn.dataset.userId = user.user_id;
    statusBtn.dataset.status = status;
    statusBtn.title = status === 'active' ? 'Disable this account' : 'Activate this account';
    statusBtn.innerHTML = `<i class="fas ${status === 'active' ? 'fa-ban' : 'fa-check'}"></i> ${status === 'active' ? 'Disable' : 'Activate'}`;

    div.append(editBtn, statusBtn);
    actionsTd.appendChild(div);
    tr.appendChild(actionsTd);

    tbody.insertBefore(tr, tbody.firstChild);
}
</script>
@endsection