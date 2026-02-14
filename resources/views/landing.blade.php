{{-- resources/views/landing.blade.php --}}
@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/landing.css') }}">
@endpush

@section('content')
<div class="min-h-screen bg-white">

    <!-- Landing Topbar -->
    @include('partials.topbar')

    <!-- Hero Section -->
    <section class="relative h-screen min-h-[600px] overflow-hidden">
        <div class="absolute inset-0">
            <img src="https://api.builder.io/api/v1/image/assets/TEMP/7c21ac8be68ffbce909034b19c53cd3e89fc678e?width=2854" 
                 alt="Beach view at Jara's Palm Beach Resort" 
                 class="w-full h-full object-cover">
        </div>
        <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-black/30 to-black/60"></div>
        
        <div class="relative z-10 flex flex-col items-center justify-center h-full text-center px-6 lg:px-12">
            <div class="max-w-5xl mx-auto space-y-8">
                <h1 class="text-white font-poppins text-xl md:text-2xl lg:text-3xl font-medium tracking-wide">
                    Experience the serenity of nature at
                </h1>
                <h2 class="text-resort-accent font-crimson-text text-5xl md:text-7xl lg:text-8xl font-bold leading-tight">
                    Jara's Palm Beach Resort
                </h2>
                <div class="flex flex-col sm:flex-row gap-4 lg:gap-6 justify-center items-center pt-6">
                    <a href="{{ route('bookings.check-availability') }}" 
                       class="group relative inline-flex items-center gap-2 bg-resort-beige hover:bg-resort-beige/90 text-resort-primary font-poppins font-semibold text-base lg:text-lg px-8 py-3 rounded-lg transition-all duration-300 shadow-lg hover:shadow-xl">
                        Book Now!
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                    <a href="#packages" 
                       class="group inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white font-poppins font-semibold text-base lg:text-lg px-8 py-3 rounded-lg border-2 border-white/50 transition-all duration-300">
                        See Packages
                        <svg class="w-5 h-5 group-hover:translate-y-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 lg:py-28 px-6 lg:px-12 bg-gradient-to-b from-white to-gray-50">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <!-- Image -->
                <div class="order-2 lg:order-1" data-aos="fade-right">
                    <div class="relative">
                        <div class="absolute -inset-4 bg-resort-accent/10 rounded-3xl -z-10"></div>
                        <img src="https://api.builder.io/api/v1/image/assets/TEMP/2d3c130de995806725c2dfd8df8069c182ba0013?width=806" 
                             alt="Resort accommodation at Jara's Palm Beach Resort" 
                             class="w-full h-auto rounded-3xl object-cover shadow-2xl">
                    </div>
                </div>
                
                <!-- Content -->
                <div class="order-1 lg:order-2 space-y-6" data-aos="fade-left">
                    <div class="inline-block">
                        <span class="bg-resort-background text-resort-primary font-poppins text-sm font-semibold px-4 py-2 rounded-full tracking-wider">
                            ABOUT JARA'S PALM BEACH RESORT
                        </span>
                    </div>
                    
                    <h2 class="text-resort-primary font-crimson-text text-4xl lg:text-5xl font-bold leading-tight">
                        Experience the serenity of nature with an exclusive stay!
                    </h2>
                    
                    <p class="text-resort-gray-dark font-poppins text-base lg:text-lg leading-relaxed">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis.
                    </p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-resort-accent flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-resort-primary font-poppins text-base font-medium">Full Resort Access</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-resort-accent flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-resort-primary font-poppins text-base font-medium">No Corkage</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-resort-accent flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-resort-primary font-poppins text-base font-medium">Accessible Parking</span>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-resort-accent flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-resort-primary font-poppins text-base font-medium">Up to 30 pax</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-resort-accent flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-resort-primary font-poppins text-base font-medium">Pet Friendly</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Packages/Rooms Section -->
    <section id="packages" class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-12">
            <!-- Section Header -->
            <div class="text-center mb-12 lg:mb-16 space-y-3" data-aos="fade-up">
                <h2 class="text-resort-primary font-crimson-text text-3xl lg:text-5xl font-bold leading-tight">
                    The Best Deals Rooms and Amenities!
                </h2>
                <div class="w-20 h-1 bg-resort-accent mx-auto rounded-full"></div>
            </div>

            <!-- Packages Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-8 lg:gap-10 max-w-5xl mx-auto">
                @php
                    $packages = \App\Models\Package::all();
                @endphp
                @forelse ($packages as $index => $package)
                    <div class="group bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition-all duration-300 transform hover:-translate-y-2 flex flex-col" data-aos="fade-up" data-aos-delay="{{ $index * 100 }}">
                        <!-- Image -->
                        <div class="relative overflow-hidden h-56">
                            @if(file_exists(public_path('images/package-' . strtolower(str_replace(' ', '-', $package->Name)) . '.jpg')))
                                <img src="{{ asset('images/package-' . strtolower(str_replace(' ', '-', $package->Name)) . '.jpg') }}" 
                                     alt="{{ $package->Name }} at Jara's Palm Beach Resort" 
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-resort-primary via-resort-accent to-resort-beige flex items-center justify-center">
                                    <div class="text-center text-white p-6">
                                        <svg class="w-14 h-14 mx-auto mb-2 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                        </svg>
                                        <p class="font-crimson-text text-lg font-bold">{{ $package->Name }}</p>
                                    </div>
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        </div>
                        
                        <!-- Content - Flex Column with flex-1 to push bottom items down -->
                        <div class="p-6 flex flex-col flex-1">
                            <h3 class="font-crimson-text text-2xl lg:text-3xl font-bold text-resort-primary text-center mb-4">
                                {{ $package->Name }}
                            </h3>
                            
                            <!-- Amenities/Features in Vertical List - flex-1 to take remaining space -->
                            @php
                                $amenities = $package->amenities_array ?? [];
                            @endphp
                            <div class="flex-1">
                                @if(count($amenities) > 0)
                                    <div class="flex flex-col gap-y-1.5 py-3 border-y border-gray-100">
                                        @foreach($amenities as $amenity)
                                            <div class="flex items-start gap-2">
                                                <svg class="w-4 h-4 text-resort-accent flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span class="text-xs text-resort-gray-dark font-poppins leading-tight">{{ $amenity }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Bottom Section - Always at the bottom -->
                            <div class="mt-4 space-y-3">
                                <!-- Maximum Guests -->
                                <div class="text-center py-2">
                                    <span class="text-resort-primary font-poppins text-sm font-medium">
                                        Maximum Guests: <span class="font-bold">{{ $package->max_guests ?? 30 }}</span>
                                    </span>
                                </div>
                                
                                <!-- Price -->
                                <div class="pt-3 border-t border-gray-100">
                                    <p class="text-resort-primary font-poppins text-3xl font-bold text-center">
                                        ₱{{ number_format($package->Price, 2) }}
                                        <span class="text-sm font-normal text-resort-gray-dark">/ day</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12">
                        <p class="text-resort-gray-dark font-poppins text-lg">No packages available at the moment.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Footer -->
    @include('partials.footer')
</div>
@endsection
