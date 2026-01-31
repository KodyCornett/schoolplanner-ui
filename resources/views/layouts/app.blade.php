<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SchoolPlanner') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 flex flex-col">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-1">
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-100 mt-auto">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-sm text-gray-500">
                            &copy; {{ date('Y') }} {{ config('app.name', 'SchoolPlanner') }}
                        </div>
                        <div class="flex flex-wrap justify-center gap-6 text-sm text-gray-500">
                            <a href="{{ route('help') }}" class="hover:text-gray-900 transition">Help</a>
                            <a href="{{ route('terms') }}" class="hover:text-gray-900 transition">Terms</a>
                            <a href="{{ route('privacy') }}" class="hover:text-gray-900 transition">Privacy</a>
                            <a href="{{ route('contact') }}" class="hover:text-gray-900 transition">Contact</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
