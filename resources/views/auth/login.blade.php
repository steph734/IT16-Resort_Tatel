@extends('layouts.app')

@section('content')
<div class="flex flex-col min-h-screen bg-resort-background text-gray-900 justify-center items-center">
    <div class="w-full max-w-md bg-white border border-resort-accent rounded-resort p-8 shadow-resort">
        <h2 class="text-3xl font-bold text-center font-crimson-pro text-resort-primary mb-8">Login to Your Account</h2>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email Address -->
            <div class="mb-4">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="w-full mt-1 p-3 border border-resort-gray-text rounded-lg bg-white focus:ring-2 focus:ring-resort-primary focus:outline-none" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mb-4">
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" class="w-full mt-1 p-3 border border-resort-gray-text rounded-lg bg-white focus:ring-2 focus:ring-resort-primary focus:outline-none" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center mb-6">
                <input id="remember_me" type="checkbox" class="rounded border-resort-accent text-resort-primary shadow-sm focus:ring-resort-primary" name="remember">
                <label for="remember_me" class="ml-2 text-sm text-resort-gray-dark">{{ __('Remember me') }}</label>
            </div>

            <div class="flex items-center justify-between">
                @if (Route::has('password.request'))
                    <a class="text-sm text-resort-primary hover:underline" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
                <button type="submit" class="px-6 py-2 bg-resort-primary text-white hover:bg-resort-accent font-semibold rounded-lg shadow-sm transition">
                    {{ __('Log in') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
