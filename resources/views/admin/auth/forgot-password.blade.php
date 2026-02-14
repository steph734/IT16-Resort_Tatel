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
        <div class="reset-box">
            <form method="POST" action="{{ route('admin.password.email') }}">
                @csrf

                <!-- Account ID -->
                <div class="reset-form-group">
                    <label for="account_id" class="reset-form-label">Account ID:</label>
                    <input type="text" id="account_id" name="account_id" class="reset-form-input"
                           placeholder="Enter Account ID" value="{{ old('account_id') }}" required autofocus>
                    @error('account_id')
                        <span class="reset-text-red-500 reset-text-sm reset-mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="reset-form-group-spaced">
                    <button type="submit" class="reset-submit-button">
                       Send Reset Password Mail
                    </button>
                </div>

                <div class="reset-text-center reset-mt-4">
                    <a href="{{ route('admin.login') }}" class="reset-back-link">Back to Login</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        @if (session('status'))
            alert('{{ session('status') }}');
        @endif

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
