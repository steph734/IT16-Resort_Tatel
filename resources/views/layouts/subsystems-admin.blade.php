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

                <!-- Optional centered nav (can be removed or used later) -->
                <nav class="hidden lg:flex items-center gap-8">
                    <!-- Add links here if needed -->
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

    <!-- Mobile Sidebar Overlay -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 transition-opacity lg:hidden pointer-events-none opacity-0"></div>

    <!-- Main Wrapper -->
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

                    <!-- Sales → visible ONLY to owner / superadmin -->
                    @if (auth()->check() && in_array(auth()->user()->role, ['owner', 'superadmin']))
                        <a href="{{ route('admin.sales') }}"
                           class="sidebar-item flex items-center px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.sales', 'admin.sales.*') ? 'bg-blue-50 text-blue-700' : '' }}">
                            <i class="fas fa-chart-line w-6 text-center"></i>
                            <span class="ml-4 sidebar-text">Sales</span>
                        </a>
                    @endif

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

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto bg-gray-100 p-5 lg:p-8">
            @yield('content')
        </main>
    </div>

    <!-- Mobile Sidebar Toggle Script -->
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

            document.querySelectorAll('.sidebar-item').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 1024) {
                        closeSidebar();
                    }
                });
            });

            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
                    closeSidebar();
                }
            });
        });
    </script>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Restrict "Transactions" top bar link click for non-owner users -->
    @auth
        @if (!in_array(auth()->user()->role, ['owner', 'superadmin']))
            <script>
            document.addEventListener('DOMContentLoaded', function () {

                // Find all "Transactions" links in top nav that need protection
                // Adjust selector if your top nav uses different classes/ids
                const transactionLinks = document.querySelectorAll('a[href*="/transactions"], a[href*="/sales/transactions"], .nav-transactions, .topnav-transactions');

                transactionLinks.forEach(link => {
                    // Only intercept if it's not already the active page
                    if (window.location.pathname.includes('/transactions')) return;

                    link.addEventListener('click', function(e) {
                        e.preventDefault();

                        const userId = "{{ auth()->id() }}";

                        Swal.fire({
                            title: 'Restricted Access',
                            html: `
                                <div class="text-left">
                                    <p class="mb-4">You need to verify your identity before accessing Transactions.</p>
                                    <p class="mb-2">Please enter your <strong>User ID</strong></p>
                                    <p class="text-sm text-gray-600">(Your ID: <strong>${userId}</strong>)</p>
                                </div>
                            `,
                            input: 'text',
                            inputPlaceholder: 'Enter your user ID',
                            inputAttributes: {
                                'inputmode': 'numeric',
                                'pattern': '[0-9]+',
                                'autocomplete': 'off'
                            },
                            showCancelButton: true,
                            confirmButtonText: 'Verify',
                            cancelButtonText: 'Cancel',
                            allowOutsideClick: false,
                            inputValidator: (value) => {
                                if (!value) {
                                    return 'Please enter your User ID';
                                }
                                if (value.trim() !== userId) {
                                    return 'Incorrect User ID';
                                }
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Correct ID → redirect to Transactions
                                window.location.href = "{{ route('admin.sales.transactions') ?? '/admin/sales/transactions' }}";
                            }
                            // Cancel or wrong ID → stay on current page (dashboard)
                        });
                    });
                });
            });
            </script>
        @endif
    @endauth

    @stack('scripts')
    @yield('scripts')
</body>
</html>