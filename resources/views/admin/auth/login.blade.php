@extends('layouts.app')

@section('title', 'Login')

@section('content')
    @include('alerts.success')

    <div class="flex flex-col min-h-screen bg-resort-background text-gray-900">

        <!-- Logo header -->
        <header class="w-full">
            <div class="flex items-center justify-center py-3">
                <img src="{{ asset('images/jara_logo.png') }}" 
                     alt="Jara's Palm Beach Resort Logo" 
                     class="h-80 w-auto mx-auto">
            </div>
        </header>

        <!-- Main login area -->
        <main class="flex-1 flex flex-col items-center justify-center px-4 py-3">
            <div class="w-full max-w-md bg-white border border-resort-accent rounded-resort shadow-resort p-8">

                <h2 class="text-2xl font-bold text-center font-crimson-pro text-resort-primary mb-8">
                    Login
                </h2>

                <form method="POST" action="{{ route('admin.login') }}" class="space-y-6" id="login-form">
                    @csrf

                    <!-- Session warning -->
                    @if (session('status') === 'warning' && session('message'))
                        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded text-yellow-800 text-sm">
                            <svg class="inline h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" 
                                      d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" 
                                      clip-rule="evenodd" />
                            </svg>
                            {{ session('message') }}
                        </div>
                    @endif

                    <!-- General success/info message -->
                    @if (session('status') && session('status') !== 'warning')
                        <div class="mb-4 font-medium text-sm text-green-600 text-center">
                            {{ session('status') }}
                        </div>
                    @endif

                    <!-- reCAPTCHA error (server-side) -->
                    @error('recaptcha')
                        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-800 text-sm text-center">
                            {{ $message }}
                        </div>
                    @enderror

                    <!-- Email field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-resort-gray-dark mb-1">
                            Email
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email"
                               class="block w-full p-3 border border-resort-gray-text rounded-lg bg-white focus:ring-2 focus:ring-resort-primary focus:outline-none"
                               placeholder="example@gmail.com"
                               value="{{ old('email') }}"
                               required 
                               autofocus 
                               autocomplete="username">
                        @error('email')
                            <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Password field with toggle -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-resort-gray-dark mb-1">
                            Password
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="password" 
                                   name="password"
                                   class="block w-full p-3 border border-resort-gray-text rounded-lg bg-white focus:ring-2 focus:ring-resort-primary focus:outline-none pr-12"
                                   placeholder="************"
                                   required 
                                   autocomplete="current-password">
                            <button type="button" 
                                    onclick="togglePassword()"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                <svg id="eye-open" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg id="eye-closed" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L8.465 8.465M18.314 18.314L8.465 8.465M18.314 18.314l-1.413-1.414M18.314 18.314l1.414 1.414" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Forgot password link -->
                    @if (Route::has('admin.password.request'))
                        <div class="text-right">
                            <a href="{{ route('admin.password.request') }}"
                               class="text-resort-primary hover:underline text-sm">
                                Forgot Password?
                            </a>
                        </div>
                    @endif

                    <!-- Google reCAPTCHA v2 -->
                    <div class="flex justify-center my-6">
                        <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}"></div>
                    </div>

                    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

                    <!-- Submit button -->
                    <button type="submit"
                            class="btn-resort-primary w-full py-3 font-semibold rounded-lg">
                        Log In
                    </button>
                </form>
            </div>
        </main>
    </div>

    <!-- Password visibility toggle script -->
    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const eyeOpen = document.getElementById('eye-open');
            const eyeClosed = document.getElementById('eye-closed');

            if (input.type === 'password') {
                input.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        }
    </script>

    <!-- Google reCAPTCHA script + client-side validation -->
   <script src="https://www.google.com/recaptcha/api.js" async defer></script>

<script>
document.getElementById('login-form').addEventListener('submit', function(e) {
    const response = grecaptcha.getResponse();

    if (response.length === 0) {
        e.preventDefault();
        alert("Please complete the reCAPTCHA verification (check 'I'm not a robot').");
        
        // Optional: scroll to reCAPTCHA
        document.querySelector('.g-recaptcha').scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
        
        return false;
    }
    
    // If checked → form submits normally
});
    </script>

@endsection