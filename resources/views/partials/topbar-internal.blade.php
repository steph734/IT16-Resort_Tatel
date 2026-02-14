{{-- resources/views/partials/topbar-internal.blade.php --}}
<header class="bg-resort-primary shadow-md relative h-16" x-data="{ isOpen: false }">
    <!-- Absolute logo box to sit flush at the top-left of the page (wider) -->
    <div class="absolute left-0 top-0 bg-white shadow-sm h-16 flex items-center px-6 pr-20 w-64 lg:w-72" style="clip-path: polygon(0 0, 100% 0, 80% 100%, 0 100%); z-index:60;">
        <div class="flex items-center gap-3">
            <img src="{{ asset('icons/logo.png') }}" alt="Jara's Palm Beach Resort Logo" class="w-16 h-16 object-contain">
            <div class="flex flex-col leading-tight">
                <span class="font-crimson-text text-resort-primary text-2xl font-bold tracking-wide">JARA'S</span>
                <span class="font-poppins text-resort-primary text-[11px] font-semibold tracking-wide whitespace-nowrap">PALM BEACH RESORT</span>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 lg:px-12 h-16 flex items-center" style="padding-left:16rem; padding-right:5px;">
        <nav class="flex items-center justify-between lg:justify-end w-full">
            <!-- Mobile Menu Toggle Button -->
            <div class="lg:hidden">
                <button x-on:click="isOpen = !isOpen" class="text-white focus:outline-none">
                    <svg x-show="!isOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                    </svg>
                    <svg x-show="isOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <ul x-show="isOpen || window.innerWidth >= 1024" 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2" 
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150" 
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2" 
                @click.away="isOpen = false"
                @resize.window="isOpen = false"
                class="absolute lg:static top-full left-0 right-0 lg:top-auto mt-2 lg:mt-0 lg:flex lg:items-center lg:justify-end lg:gap-12 bg-resort-primary p-4 lg:p-0 rounded-b-lg lg:rounded-none shadow-lg lg:shadow-none w-full lg:w-auto z-50 lg:ml-auto">

                <li class="block lg:inline-block">
                    <a href="{{ url('/') }}"
                        class="font-poppins text-white hover:text-resort-accent transition-colors block py-2 lg:py-0 font-medium text-base"
                        x-on:click="isOpen = false">Home</a>
                </li>
                <li class="block lg:inline-block">
                    <a href="{{ url('/booking-policy') }}"
                        class="font-poppins text-white hover:text-resort-accent transition-colors block py-2 lg:py-0 font-medium text-base"
                        x-on:click="isOpen = false">Booking Policy</a>
                </li>
                <li class="block lg:inline-block">
                    <a href="{{ url('/contact') }}"
                        class="font-poppins text-white hover:text-resort-accent transition-colors block py-2 lg:py-0 font-medium text-base"
                        x-on:click="isOpen = false">Contact Us!</a>
                </li>
            </ul>
        </nav>
    </div>
</header>