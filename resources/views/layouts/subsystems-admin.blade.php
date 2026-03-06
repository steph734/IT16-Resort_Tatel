<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - Jara's Palm Beach Resort</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('icons/logo.png') }}">

    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Custom CSS -->
    <link href="{{ asset('css/admin/topnav.css') }}" rel="stylesheet">
    <link href="{{ asset('css/admin/sidebar.css') }}" rel="stylesheet">

    @stack('styles')
    @yield('styles')
</head>
<body class="h-full bg-gray-100 antialiased">

    <!-- Top Navigation -->
    <header class="admin-topnav bg-white shadow-sm fixed top-0 left-0 right-0 z-30">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">

                <!-- Logo + Toggle -->
                <div class="flex items-center gap-4">
                    <button id="sidebar-toggle" type="button" class="text-gray-500 focus:outline-none lg:hidden">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div class="flex items-center">
                        <img src="{{ asset('icons/cropped_logo.png') }}" alt="Jara's Logo" class="h-10 w-auto">
                        <div class="ml-3 hidden sm:block">
                            <h1 class="text-xl font-bold text-gray-900">JARA'S</h1>
                            <p class="text-xs text-gray-600 tracking-wide">PALM BEACH RESORT</p>
                        </div>
                    </div>
                </div>

                <!-- Optional centered nav (remove or customize) -->
                <nav class="hidden lg:flex items-center gap-8">
                    <!-- <a href="#" class="text-gray-700 hover:text-blue-600 font-medium">Dashboard</a> -->
                    <!-- Add real links here if needed -->
                </nav>

                <!-- Logout -->
                <div class="flex items-center">
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center text-red-600 hover:text-red-800 font-medium focus:outline-none">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            <span class="hidden sm:inline">Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Overlay (mobile sidebar backdrop) -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 transition-opacity lg:hidden pointer-events-none opacity-0"></div>

    <!-- Wrapper (push content down by header height) -->
    <div class="pt-16 flex min-h-screen">

        <!-- Sidebar -->
        <aside id="sidebar"
              class="admin-sidebar fixed inset-y-0 left-0 z-40 w-72 bg-white border-r border-gray-200 transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto lg:border-r lg:shadow-sm">
            <div class="flex flex-col h-full">
                <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto">
                    <a href="{{ route('admin.dashboard') }}"
                       class="sidebar-item flex items-center px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.dashboard', 'admin.bookings', 'admin.currently-staying') ? 'bg-blue-50 text-blue-700' : '' }}">
                        <i class="fas fa-umbrella-beach w-6 text-center"></i>
                        <span class="ml-4 sidebar-text">Bookings</span>
                    </a>

                    <a href="{{ route('admin.rentals') }}"
                       class="sidebar-item flex items-center px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.rentals') ? 'bg-blue-50 text-blue-700' : '' }}">
                        <i class="fas fa-hand-holding-usd w-6 text-center"></i>
                        <span class="ml-4 sidebar-text">Rentals</span>
                    </a>

                    <a href="{{ route('admin.sales') }}"
                       class="sidebar-item flex items-center px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.sales') ? 'bg-blue-50 text-blue-700' : '' }}">
                        <i class="fas fa-dollar-sign w-6 text-center"></i>
                        <span class="ml-4 sidebar-text">Sales</span>
                    </a>

                    <a href="{{ route('admin.inventory') }}"
                       class="sidebar-item flex items-center px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.inventory') ? 'bg-blue-50 text-blue-700' : '' }}">
                        <i class="fas fa-boxes w-6 text-center"></i>
                        <span class="ml-4 sidebar-text">Inventory</span>
                    </a>

                    <a href="{{ route('admin.payroll') }}"
                       class="sidebar-item flex items-center px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.payroll') ? 'bg-blue-50 text-blue-700' : '' }}">
                        <i class="fas fa-users w-6 text-center"></i>
                        <span class="ml-4 sidebar-text">Payroll</span>
                    </a>
                </nav>

                <div class="border-t p-4 mt-auto">
                    <a href="{{ route('admin.settings') }}"
                       class="sidebar-item flex items-center px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 mb-2 {{ request()->routeIs('admin.settings') ? 'bg-blue-50 text-blue-700' : '' }}">
                        <i class="fas fa-cog w-6 text-center"></i>
                        <span class="ml-4 sidebar-text">Settings</span>
                    </a>

                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="sidebar-item flex items-center w-full px-4 py-3 text-red-700 hover:bg-red-50 rounded-lg">
                            <i class="fas fa-sign-out-alt w-6 text-center"></i>
                            <span class="ml-4 sidebar-text">Log Out</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto bg-gray-100 p-5 lg:p-8">
            @yield('content')
        </main>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar   = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebar-toggle');
            const overlay   = document.getElementById('overlay');

            function openSidebar() {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('pointer-events-none', 'opacity-0');
                overlay.classList.add('pointer-events-auto', 'opacity-100');
            }

            function closeSidebar() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('pointer-events-none', 'opacity-0');
                overlay.classList.remove('pointer-events-auto', 'opacity-100');
            }

            toggleBtn?.addEventListener('click', () => {
                if (sidebar.classList.contains('-translate-x-full')) {
                    openSidebar();
                } else {
                    closeSidebar();
                }
            });

            overlay?.addEventListener('click', closeSidebar);

            // Close on link click (mobile only)
            document.querySelectorAll('.sidebar-item').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 1024) { // lg breakpoint
                        closeSidebar();
                    }
                });
            });

            // Optional: close on escape key
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
                    closeSidebar();
                }
            });
        });
    </script>

    @stack('scripts')
    @yield('scripts')
</body>
</html>