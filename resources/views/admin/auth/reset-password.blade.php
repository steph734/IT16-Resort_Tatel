<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - {{ config('app.name') }}</title>
    <!-- Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" rel="stylesheet">
    <!-- Reset Password CSS -->
    <link href="{{ asset('css/admin/reset-password.css') }}" rel="stylesheet">
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="reset-container">
        <div class="reset-logo-container">
            <!-- Logo -->
            <img src="{{ asset('images/jara_logo.png') }}" alt="Jara's Palm Beach Resort Logo">
        </div>

        <!-- Reset Password Box -->
        <div class="reset-box-extended">
            <form method="POST" action="{{ route('admin.password.store') }}">
                @csrf

                <!-- Password Reset Token -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <!-- Email Address -->
                <div class="reset-form-group">
                    <label for="email" class="reset-form-label">Email:</label>
                    <input type="email" id="email" name="email" class="reset-form-input"
                           placeholder="Enter Email Address"
                           value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
                    @error('email')
                        <span class="reset-text-red-500 reset-text-sm reset-mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password -->
                <div class="reset-form-group">
                    <label for="password" class="reset-form-label">New Password:</label>
                    <div class="reset-relative">
                        <input type="password" id="password" name="password" class="reset-form-input pr-12"
                               placeholder="Enter New Password" required autocomplete="new-password">
                        <button type="button" onclick="togglePassword('password')" class="reset-absolute reset-inset-y-0 reset-right-0 reset-flex reset-items-center reset-pr-3 reset-button">
                            <svg id="eye-open-password" class="reset-h-5 reset-w-5 reset-text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg id="eye-closed-password" class="reset-h-5 reset-w-5 reset-text-gray-400 reset-hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L8.465 8.465M18.314 18.314L8.465 8.465M18.314 18.314l-1.413-1.414M18.314 18.314l1.414 1.414" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <span class="reset-text-red-500 reset-text-sm reset-mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="reset-form-group">
                    <label for="password_confirmation" class="reset-form-label">Confirm Password:</label>
                    <div class="reset-relative">
                        <input type="password" id="password_confirmation" name="password_confirmation" class="reset-form-input pr-12"
                               placeholder="Confirm New Password" required autocomplete="new-password">
                        <button type="button" onclick="togglePassword('password_confirmation')" class="reset-absolute reset-inset-y-0 reset-right-0 reset-flex reset-items-center reset-pr-3 reset-button">
                            <svg id="eye-open-confirmation" class="reset-h-5 reset-w-5 reset-text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg id="eye-closed-confirmation" class="reset-h-5 reset-w-5 reset-text-gray-400 reset-hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L8.465 8.465M18.314 18.314L8.465 8.465M18.314 18.314l-1.413-1.414M18.314 18.314l1.414 1.414" />
                            </svg>
                        </button>
                    </div>
                    @error('password_confirmation')
                        <span class="reset-text-red-500 reset-text-sm reset-mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="reset-form-group-spaced">
                    <button type="submit" class="reset-submit-button">Reset Password</button>
                </div>

                <div class="reset-text-center reset-mt-4">
                    <a href="{{ route('admin.login') }}" class="reset-back-link">Back to Login</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const eyeOpen = document.getElementById('eye-open-' + (fieldId === 'password' ? 'password' : 'confirmation'));
            const eyeClosed = document.getElementById('eye-closed-' + (fieldId === 'password' ? 'password' : 'confirmation'));

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.classList.add('reset-hidden');
                eyeClosed.classList.remove('reset-hidden');
            } else {
                passwordInput.type = 'password';
                eyeOpen.classList.remove('reset-hidden');
                eyeClosed.classList.add('reset-hidden');
            }
        }
    </script>
</body>
</html>
