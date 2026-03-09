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

    @stack('styles')
    @yield('styles')
</head>
<body class="bg-gray-100">
    <!-- Top Navigation (Conditional based on subsystem) -->
    @php 
        $viewerRole = strtolower(auth()->user()->role ?? ''); 
    @endphp

    @if(request()->routeIs('admin.rentals*'))
        @include('admin.partials.topnav-rentals')
    @elseif(request()->routeIs('admin.sales.*') || request()->routeIs('admin.accounts') || request()->routeIs('admin.list-management'))
        @include('admin.partials.topnav-sales')
    @elseif(request()->routeIs('admin.inventory.*'))
        @include('admin.partials.topnav-inventory')
    @else
        @include('admin.partials.topnav')
    @endif

    <!-- Main Container with Sidebar -->
    <div class="admin-container">
        <!-- Sidebar -->
        <aside id="sidebar" class="admin-sidebar">
            <nav class="sidebar-nav">
                @if($viewerRole === 'owner')
                    <!-- Owner view -->
                    <a href="{{ route('admin.sales.dashboard') }}" 
                       class="sidebar-item {{ request()->routeIs('admin.sales.*') ? 'active' : '' }}">
                        <i class="fas fa-dollar-sign"></i>
                        <span class="sidebar-text">Sales</span>
                    </a>

                    <a href="{{ route('admin.inventory.index') }}" 
                       class="sidebar-item {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">
                        <i class="fas fa-boxes"></i>
                        <span class="sidebar-text">Inventory</span>
                    </a>

                    <a href="{{ route('admin.accounts') }}" 
                       class="sidebar-item {{ request()->routeIs('admin.accounts') ? 'active' : '' }}">
                        <i class="fas fa-users-cog"></i>
                        <span class="sidebar-text">Accounts</span>
                    </a>

                    <a href="{{ route('admin.list-management') }}" 
                       class="sidebar-item {{ request()->routeIs('admin.list-management') ? 'active' : '' }}">
                        <i class="fas fa-list-ul"></i>
                        <span class="sidebar-text">List Management</span>
                    </a>

                    <a href="{{ route('admin.audit-logs') }}" 
                       class="sidebar-item {{ request()->routeIs('admin.audit-logs') ? 'active' : '' }}">
                        <i class="fas fa-history"></i>
                        <span class="sidebar-text">Audit Logs</span>
                    </a>
                @else
                    <!-- Admin & Staff view -->
                    <a href="{{ route('admin.dashboard') }}" 
                       class="sidebar-item {{ request()->routeIs(['admin.dashboard', 'admin.bookings', 'admin.currently-staying']) ? 'active' : '' }}">
                        <i class="fas fa-umbrella-beach"></i>
                        <span class="sidebar-text">Bookings</span>
                    </a>

                    <a href="{{ route('admin.rentals.dashboard') }}" 
                       class="sidebar-item {{ request()->routeIs('admin.rentals.*') ? 'active' : '' }}">
                        <i class="fas fa-hand-holding-usd"></i>
                        <span class="sidebar-text">Rentals</span>
                    </a>

                    <a href="{{ route('admin.sales.dashboard') }}" 
                       class="sidebar-item {{ request()->routeIs('admin.sales.*') ? 'active' : '' }}">
                        <i class="fas fa-dollar-sign"></i>
                        <span class="sidebar-text">Sales</span>
                    </a>

                    <a href="{{ route('admin.inventory.index') }}" 
                       class="sidebar-item {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">
                        <i class="fas fa-boxes"></i>
                        <span class="sidebar-text">Inventory</span>
                    </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                <form method="POST" action="{{ route('admin.logout') }}" class="sidebar-logout-form">
                    @csrf
                    <button type="submit" class="sidebar-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="sidebar-text">Log Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-main-content">
            @yield('content')
        </main>
    </div>

    <!-- Sidebar Overlay (mobile) -->
    <div id="sidebarOverlay" class="sidebar-overlay"></div>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');

            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('mobile-open');
                    sidebarOverlay?.classList.toggle('show');
                });
            }

            sidebarOverlay?.addEventListener('click', () => {
                sidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('show');
            });

            // Close sidebar on link click in mobile view
            document.querySelectorAll('.sidebar-item').forEach(item => {
                item.addEventListener('click', () => {
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('mobile-open');
                        sidebarOverlay?.classList.remove('show');
                    }
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @stack('scripts')
    @yield('scripts')

    <!-- Inactivity logout -->
    <script>
        (function(){
            const TIMEOUT_SECONDS = {{ (int) config('session.lifetime', 15) * 60 }};
            const WARNING_SECONDS = 60;

            let lastEvent = Date.now();
            let warningTimer, logoutTimer;

            function resetTimers() {
                lastEvent = Date.now();
                clearTimeout(warningTimer);
                clearTimeout(logoutTimer);
                warningTimer = setTimeout(showWarning, (TIMEOUT_SECONDS - WARNING_SECONDS) * 1000);
                logoutTimer = setTimeout(doLogout, TIMEOUT_SECONDS * 1000);
            }

            function showWarning() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'You will be logged out soon',
                        text: `No activity detected. Logging out in ${WARNING_SECONDS} seconds.`,
                        icon: 'warning',
                        confirmButtonText: 'Stay logged in',
                        allowOutsideClick: false,
                    }).then(result => {
                        if (result.isConfirmed) sendHeartbeat().finally(resetTimers);
                    });
                }
            }

            
            function doLogout() {
                const form = document.querySelector('form.sidebar-logout-form');
                if (form) form.submit();
                else window.location.href = '{{ route('admin.login') }}?session_expired=1';
            }

            ['click','mousemove','keydown','scroll','touchstart'].forEach(e => 
                document.addEventListener(e, resetTimers, {passive: true})
            );

            resetTimers();
        })();
    </script>
</body>
</html>