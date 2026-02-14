<!-- Top Navigation for Inventory Subsystem -->
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
                <a href="{{ route('admin.inventory.index') }}" 
                   class="nav-link {{ request()->routeIs('admin.inventory.index') ? 'active' : '' }}">
                    Dashboard
                </a>
                <a href="{{ route('admin.inventory.list') }}" 
                   class="nav-link {{ request()->routeIs('admin.inventory.list') ? 'active' : '' }}">
                    Inventory
                </a>
                <a href="{{ route('admin.inventory.purchases') }}" 
                   class="nav-link {{ request()->routeIs('admin.inventory.purchases*') ? 'active' : '' }}">
                    Purchases
                </a>
                <a href="{{ route('admin.inventory.stock-movements') }}" 
                   class="nav-link {{ request()->routeIs('admin.inventory.stock-movements*') ? 'active' : '' }}">
                    Stock Movements
                </a>
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
        <a href="{{ route('admin.inventory.index') }}" 
           class="nav-link {{ request()->routeIs('admin.inventory.index') ? 'active' : '' }}">
            Dashboard
        </a>
        <a href="{{ route('admin.inventory.list') }}" 
           class="nav-link {{ request()->routeIs('admin.inventory.list') ? 'active' : '' }}">
            Inventory
        </a>
        <a href="{{ route('admin.inventory.purchases') }}" 
           class="nav-link {{ request()->routeIs('admin.inventory.purchases*') ? 'active' : '' }}">
            Purchases
        </a>
        <a href="{{ route('admin.inventory.stock-movements') }}" 
           class="nav-link {{ request()->routeIs('admin.inventory.stock-movements*') ? 'active' : '' }}">
            Stock Movements
        </a>
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
