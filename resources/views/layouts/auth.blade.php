<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Auth' }} - {{ config('app.name', 'DWD') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Left Side - Form -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Logo -->
                <div class="mb-8">
                    <h1 class="text-4xl font-bold text-gray-900">DWD</h1>
                </div>

                <!-- Content -->
                {{ $slot }}
            </div>
        </div>

        <!-- Right Side - Illustration -->
        <div class="hidden lg:flex flex-1 bg-gray-100 items-center justify-center p-12">
            <div class="max-w-lg">
                {{ $illustration ?? '' }}
            </div>
        </div>
    </div>
</body>
</html>
