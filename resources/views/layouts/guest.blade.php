<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Modulus') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-blue-500 to-indigo-600">
            <div class="text-center">
                <a href="/" class="flex flex-col items-center">
                    <x-application-logo class="w-16 h-16 text-white" />
                    <span class="mt-2 text-2xl font-bold text-white">{{ config('app.name', 'Modulus') }}</span>
                    <span class="mt-1 text-sm text-blue-100">Plan your study schedule</span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white shadow-xl overflow-hidden sm:rounded-xl">
                {{ $slot }}
            </div>

            <div class="mt-4">
                <a href="{{ route('help') }}" class="text-sm text-blue-100 hover:text-white transition">
                    Need help? Learn how Modulus works
                </a>
            </div>

            <div class="mt-8 flex flex-wrap justify-center gap-4 text-sm text-blue-200">
                <a href="{{ route('terms') }}" class="hover:text-white transition">Terms</a>
                <span class="text-blue-400">|</span>
                <a href="{{ route('privacy') }}" class="hover:text-white transition">Privacy</a>
                <span class="text-blue-400">|</span>
                <a href="{{ route('contact') }}" class="hover:text-white transition">Contact</a>
            </div>
        </div>
    </body>
</html>
