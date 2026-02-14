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
    <link href="{{ asset('css/admin/topnav.css') }}" rel="stylesheet">
    <!-- Sidebar CSS -->
    <link href="{{ asset('css/admin/sidebar.css') }}" rel="stylesheet">

    <!-- Additional CSS can be included here -->
    @stack('styles')
    @yield('styles')
</head>
<body class="bg-gray-100">
    <!-- Top Navigation (Placeholder for other subsystems) -->
    <nav class="admin-topnav">
        <div class="nav-container">
            <div class="nav-content">
                <!-- Sidebar Toggle + Logo and Brand -->
                <div class="logo-section">
                    <!-- Sidebar Menu Toggle Button -->
                    <button id="sidebarMenuToggle" class="sidebar-menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>

                    <div class="logo-container">
                        <img src="{{ asset('icons/cropped_logo.png') }}" alt="Jara's Logo" class="logo-img">
                        <div class="brand-text">
                            <h1 class="brand-title">JARA'S</h1>
                            <p class="brand-subtitle">PALM BEACH RESORT</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation Links - Centered -->
                <div class="nav-links">
                    <a href="#" class="nav-link active">Sidebar 1</a>
                    <a href="#" class="nav-link">Sidebar 2</a>
                    <a href="#" class="nav-link">Sidebar 3</a>
                </div>

                <!-- Logout Button - Right Side -->
                <div class="logout-section">
                    <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="logout-btn">
                            <i class="fas fa-sign-out-alt logout-icon"></i>
                        </button>
                    </form>
                </div>

                <!-- Mobile menu button -->
                <div class="mobile-menu-btn-container">
                    <button type="button"
                            class="mobile-menu-btn"
                            aria-controls="mobile-menu"
                            aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="mobile-menu" id="mobile-menu">
            <a href="#" class="nav-link active">Sidebar 1</a>
            <a href="#" class="nav-link">Sidebar 2</a>
            <a href="#" class="nav-link">Sidebar 3</a>

            <!-- Mobile Logout Button -->
            <form method="POST" action="{{ route('admin.logout') }}" class="block">
                @csrf
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt logout-icon"></i>
                    Logout
                </button>
            </form>
        </div>
    </nav>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.querySelector('.mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');

            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('show');
                });
            }
        });
    </script>

    <!-- Mobile Menu Overlay -->
    <div id="sidebarOverlay" class="sidebar-overlay"></div>

    <!-- Main Container with Sidebar -->
    <div class="admin-container">
        <!-- Sidebar (Default: Small with Icons Only) -->
        <aside id="sidebar" class="admin-sidebar">
            <nav class="sidebar-nav">
                <a href="{{ route('admin.dashboard') }}" class="sidebar-item {{ request()->routeIs('admin.dashboard') || request()->routeIs('admin.bookings') || request()->routeIs('admin.currently-staying') ? 'active' : '' }}">
                    <i class="fas fa-umbrella-beach"></i>
                    <span class="sidebar-text">Bookings</span>
                </a>

                <a href="{{ route('admin.rentals') }}" class="sidebar-item {{ request()->routeIs('admin.rentals') ? 'active' : '' }}">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span class="sidebar-text">Rentals</span>
                </a>

                <a href="{{ route('admin.sales') }}" class="sidebar-item {{ request()->routeIs('admin.sales') ? 'active' : '' }}">
                    <i class="fas fa-dollar-sign"></i>
                    <span class="sidebar-text">Sales</span>
                </a>

                <a href="{{ route('admin.inventory') }}" class="sidebar-item {{ request()->routeIs('admin.inventory') ? 'active' : '' }}">
                    <i class="fas fa-boxes"></i>
                    <span class="sidebar-text">Inventory</span>
                </a>

                <a href="{{ route('admin.payroll') }}" class="sidebar-item {{ request()->routeIs('admin.payroll') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span class="sidebar-text">Payroll</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="{{ route('admin.settings') }}" class="sidebar-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i>
                    <span class="sidebar-text">Settings</span>
                </a>

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

    <!-- Additional Scripts can be included here -->
    @stack('scripts')
    @yield('scripts')
</body>
</html>
