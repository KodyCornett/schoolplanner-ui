<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title') - {{ config('app.name', 'SchoolPlanner') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-50">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-100">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="{{ route('home') }}" class="flex items-center space-x-2">
                            <x-application-logo class="block h-9 w-auto text-blue-600" />
                            <span class="font-bold text-lg text-gray-800">{{ config('app.name', 'SchoolPlanner') }}</span>
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900">Log in</a>
                            <a href="{{ route('register') }}" class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Get Started</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 sm:p-8">
                        @yield('page-content')
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-100 mt-auto">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-500">
                        &copy; {{ date('Y') }} {{ config('app.name', 'SchoolPlanner') }}
                    </div>
                    <div class="flex flex-wrap justify-center gap-6 text-sm text-gray-500">
                        <a href="{{ route('home') }}" class="hover:text-gray-900 transition">Home</a>
                        <a href="{{ route('help') }}" class="hover:text-gray-900 transition">Help</a>
                        <a href="{{ route('terms') }}" class="hover:text-gray-900 transition">Terms</a>
                        <a href="{{ route('privacy') }}" class="hover:text-gray-900 transition">Privacy</a>
                        <a href="{{ route('contact') }}" class="hover:text-gray-900 transition">Contact</a>
                    </div>
                </div>
            </div>
        </footer>
    </body>
</html>
