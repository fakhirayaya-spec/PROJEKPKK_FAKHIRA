<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Potato Curly Crunch</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('asset_login/images/logopotato.jpg') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- CSS Asset -->
    <link rel="stylesheet" href="{{ asset('asset_login/style.css') }}?v={{ time() }}">
</head>
<body>

    <div class="login-container">
        <div class="login-card">

            <!-- HEADER SECTION -->
            <div class="login-header">
                <div class="logo-wrapper">
                    <img src="{{ asset('asset_login/images/logopotato.jpg') }}" alt="Potato Curly Crunch">
                </div>
                <h1>Welcome Back!</h1>
                <p>Silakan login untuk melanjutkan<br>ke dashboard Potato Curly Crunch</p>
            </div>

            <!-- Session Status Laravel -->
            <x-auth-session-status class="mb-2" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="login-form" id="loginForm" novalidate>
                @csrf

                <!-- Email Address -->
                <div class="form-group @error('email') error @enderror">
                    <div class="input-container">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8c8c8c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        </span>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Email Address" required autofocus autocomplete="username">
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <!-- Password + Toggle Eye Icon -->
                <div class="form-group password-group @error('password') error @enderror">
                    <div class="input-container">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8c8c8c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </span>
                        <input type="password" id="password" name="password" placeholder="Password" required autocomplete="current-password">

                        <button type="button" class="toggle-password" id="togglePassword" onclick="togglePasswordVisibility()" aria-label="Toggle password visibility">
                            <!-- Eye Icon (Hidden/Closed) -->
                            <svg class="eye-icon eye-off" id="eyeOff" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8c8c8c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                            <!-- Eye Icon (Open) -->
                            <svg class="eye-icon eye-on" id="eyeOn" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8c8c8c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="form-options">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" id="remember" name="remember">
                        <span class="checkmark"></span>
                        Keep me signed in
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
                    @else
                        <a href="#" class="forgot-link">Forgot password?</a>
                    @endif
                </div>

                <!-- Tombol Submit -->
                <button type="submit" class="login-btn">
                    <span class="btn-text">Sign In &rarr;</span>
                </button>
            </form>

            <div class="divider">
                <span>OR SIGN IN WITH</span>
            </div>

            <div class="social-login">
                <button type="button" class="social-btn">
                    <svg width="16" height="16" viewBox="0 0 20 20">
                        <path fill="#4285F4" d="M18.12 8.89H10.22v2.22h4.54c-.2 1.11-.82 1.64-1.78 2.22v1.78h2.89c1.69-1.56 2.67-3.85 2.67-6.58 0-.58-.05-.71-.17-1.31l.05-.33z"/>
                        <path fill="#34A853" d="M10.22 18c2.4 0 4.41-.8 5.88-2.17l-2.89-1.78c-.8.56-1.82.89-2.99.89-2.3 0-4.25-1.55-4.94-3.64H2.17v1.83A8.22 8.22 0 0010.22 18z"/>
                        <path fill="#FBBC05" d="M5.28 11.3a4.9 4.9 0 010-3.14V6.33H2.17a8.22 8.22 0 000 7.8l3.11-2.83z"/>
                        <path fill="#EA4335" d="M10.22 3.58c1.46 0 2.78.5 3.82 1.5l2.83-2.83A8.22 8.22 0 002.17 6.33l3.11 2.83c.69-2.09 2.64-3.64 4.94-3.58z"/>
                    </svg>
                    Google
                </button>

                <button type="button" class="social-btn">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="#1877F2">
                        <path d="M20 10.04C20 4.5 15.52.02 10 .02S0 4.5 0 10.04c0 5.01 3.66 9.17 8.44 9.9v-7h-2.54v-2.9h2.54V7.85c0-2.51 1.49-3.89 3.78-3.89 1.09 0 2.23.19 2.23.19v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 2.9h-2.34v7C16.34 19.21 20 15.05 20 10.04z"/>
                    </svg>
                    Facebook
                </button>
            </div>

            <div class="signup-link">
                <p>New to Potato Curly Crunch? <a href="#">Create an account</a></p>
            </div>
        </div>
    </div>

    <!-- Script Javascript -->
    <script src="{{ asset('asset_login/script.js') }}?v={{ time() }}"></script>
</body>
</html>
