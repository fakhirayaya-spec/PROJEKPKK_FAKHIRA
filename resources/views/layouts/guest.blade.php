<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Potato Curly Crunch - Login</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800&display=swap" rel="stylesheet">

    <!-- Panggil CSS Template Kamu di Sini -->
    <link rel="stylesheet" href="{{ asset('asset_login/css/login.css') }}">
</head>

<body>

    {{ $slot }}

    <!-- Panggil JS Template Kamu di Sini -->
    <script src="{{ asset('asset_login/js/script.js') }}"></script>
</body>

</html>
