<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - Jara's Palm Beach Resort</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('icons/logo.png') }}">

    <!-- Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Admin Top Navigation CSS -->
    <link href="{{ asset('css/admin/topnav.css') }}?v={{ time() }}" rel="stylesheet">
    <!-- Sidebar CSS -->
    <link href="{{ asset('css/admin/sidebar.css') }}?v={{ time() }}" rel="stylesheet">

    <!-- Additional CSS can be included here -->
    @stack('styles')
    @yield('styles')
</head>
<body class="bg-gray-100">
    <!-- Top Navigation (Conditional based on subsystem) -->
    @php $viewerRole = auth()->user()->role ?? '' @endphp
    @if(request()->routeIs('admin.rentals*'))
        @include('admin.partials.topnav-rentals')
    @elseif(request()->routeIs('admin.sales.*') || request()->routeIs('admin.accounts') || request()->routeIs('admin.list-management') || $viewerRole === 'owner')
        {{-- Show Sales topnav for sales routes, accounts, list management, and also for owner users --}}
        @include('admin.partials.topnav-sales')
    @elseif(request()->routeIs('admin.inventory.*'))
        @include('admin.partials.topnav-inventory')
    @else
        @include('admin.partials.topnav')
    @endif

    <!-- Main Container with Sidebar -->
    <div class="admin-container">
        <!-- Sidebar (Default: Small with Icons Only) -->
        <aside id="sidebar" class="admin-sidebar">
            <nav class="sidebar-nav">
                @php $viewerRole = auth()->user()->role ?? '' @endphp

                @if($viewerRole === 'owner')
                    <!-- owner: only Sales, Accounts, and List Management -->
                    <a href="{{ route('admin.sales.dashboard') }}" class="sidebar-item {{ request()->routeIs('admin.sales.*') ? 'active' : '' }}">
                        <i class="fas fa-dollar-sign"></i>
                        <span class="sidebar-text">Sales</span>
                    </a>

                    <a href="{{ route('admin.accounts') }}" class="sidebar-item {{ request()->routeIs('admin.accounts') ? 'active' : '' }}">
                        <i class="fas fa-users-cog"></i>
                        <span class="sidebar-text">Accounts</span>
                    </a>

                    <a href="{{ route('admin.list-management') }}" class="sidebar-item {{ request()->routeIs('admin.list-management') ? 'active' : '' }}">
                        <i class="fas fa-list-ul"></i>
                        <span class="sidebar-text">List Management</span>
                    </a>
                    <a href="{{ route('admin.audit-logs') }}" class="sidebar-item {{ request()->routeIs('admin.audit-logs') ? 'active' : '' }}">
                        <i class="fas fa-history"></i>
                        <span class="sidebar-text">Audit Logs</span>
                    </a>
                @else
                    <!-- admin and staff: all subsystems except Settings -->
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-item {{ request()->routeIs('admin.dashboard') || request()->routeIs('admin.bookings') || request()->routeIs('admin.currently-staying') ? 'active' : '' }}">
                        <i class="fas fa-umbrella-beach"></i>
                        <span class="sidebar-text">Bookings</span>
                    </a>

                    <a href="{{ route('admin.rentals.dashboard') }}" class="sidebar-item {{ request()->routeIs('admin.rentals*') ? 'active' : '' }}">
                        <i class="fas fa-hand-holding-usd"></i>
                        <span class="sidebar-text">Rentals</span>
                    </a>

                    <a href="{{ route('admin.sales.dashboard') }}" class="sidebar-item {{ request()->routeIs('admin.sales.*') ? 'active' : '' }}">
                        <i class="fas fa-dollar-sign"></i>
                        <span class="sidebar-text">Sales</span>
                    </a>

                    <a href="{{ route('admin.inventory.index') }}" class="sidebar-item {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">
                        <i class="fas fa-boxes"></i>
                        <span class="sidebar-text">Inventory</span>
                    </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                @php $viewerRole = auth()->user()->role ?? '' @endphp
                @if($viewerRole === 'owner')
                    <!-- Settings link removed, now Accounts and List Management are in main nav -->
                @endif

                <form method="POST" action="{{ route('admin.logout') }}" class="sidebar-logout-form">
                    @csrf
                    <button type="submit" class="sidebar-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="sidebar-text">Log Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-main-content">
            @yield('content')
        </main>
    </div>

    <!-- Sidebar Overlay -->
    <div id="sidebarOverlay" class="sidebar-overlay"></div>

    <!-- Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarMenuToggle = document.getElementById('sidebarMenuToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            // Desktop sidebar toggle
            if (sidebarMenuToggle) {
                sidebarMenuToggle.addEventListener('click', () => {
                    sidebar.classList.toggle('expanded');
                });
            }

            // Mobile: Close sidebar when clicking overlay
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', () => {
                    sidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.remove('show');
                });
            }

            // Mobile: Close sidebar when clicking a link
            const sidebarLinks = document.querySelectorAll('.sidebar-item');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('mobile-open');
                        sidebarOverlay.classList.remove('show');
                    }
                });
            });

            // Mobile menu toggle (also opens sidebar on mobile)
            const mobileMenuButton = document.querySelector('.mobile-menu-btn');
            if (mobileMenuButton && window.innerWidth <= 768) {
                mobileMenuButton.addEventListener('click', () => {
                    sidebar.classList.toggle('mobile-open');
                    sidebarOverlay.classList.toggle('show');
                });
            }
        });
    </script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Additional Scripts can be included here -->
    @stack('scripts')
    @yield('scripts')
    
    {{-- Inactivity auto-logout script: warns user and logs out after configured idle time. --}}
    <script>
        (function(){
            // timeout in seconds - use the session lifetime (minutes) * 60 as default
            const TIMEOUT_SECONDS = {{ (int) config('session.lifetime', 15) * 60 }};
            const WARNING_SECONDS = 60; // show warning 60s before logout

            let lastEvent = Date.now();
            let warningTimer = null;
            let logoutTimer = null;

            function resetTimers() {
                lastEvent = Date.now();
                if (warningTimer) { clearTimeout(warningTimer); }
                if (logoutTimer) { clearTimeout(logoutTimer); }

                warningTimer = setTimeout(showWarning, (TIMEOUT_SECONDS - WARNING_SECONDS) * 1000);
                logoutTimer = setTimeout(doLogout, TIMEOUT_SECONDS * 1000);
            }

            function showWarning(){
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'You will be logged out soon',
                        text: 'No activity detected. You will be logged out in ' + WARNING_SECONDS + ' seconds.',
                        icon: 'warning',
                        confirmButtonText: 'Stay logged in',
                        showCancelButton: false,
                        allowOutsideClick: false,
                    }).then((result) => {
                        // user clicked the button -> keep session alive by making a heartbeat
                        sendHeartbeat().finally(resetTimers);
                    });
                } else {
                    // fallback: simply reset timers on interaction
                    // do nothing here; user interaction will reset timers
                }
            }

            function sendHeartbeat() {
                // send a lightweight POST to keep session alive
                return fetch(window.location.origin + '/admin/keep-alive', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({heartbeat: true})
                }).catch(()=>{});
            }

            function doLogout(){
                // submit the admin logout form if present, otherwise navigate to login route
                // Use navigator.sendBeacon on unload to hint server
                const logoutForm = document.querySelector('form.sidebar-logout-form');
                if (logoutForm) {
                    logoutForm.submit();
                } else {
                    // fallback: redirect to admin login
                    window.location.href = '{{ route('admin.login') }}?session_expired=1';
                }
            }

            // events that indicate activity
            ['click','mousemove','keydown','scroll','touchstart'].forEach(evt =>
                document.addEventListener(evt, resetTimers, {passive: true})
            );

            // initialize
            resetTimers();

            // Optionally send unload beacon to inform server when user closes tab
            window.addEventListener('unload', function () {
                const url = window.location.origin + '/admin/unload-beacon';
                if (navigator.sendBeacon) {
                    const payload = new Blob([JSON.stringify({unload: true})], {type: 'application/json'});
                    navigator.sendBeacon(url, payload);
                }
            });
        })();
    </script>
</body>
</html>
