<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'School Planner') }}</title>
    @vite(['resources/css/app.css'])
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-14">
                <a href="{{ route('plan.import') }}" class="flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="font-semibold text-gray-900">School Planner</span>
                </a>
                <nav class="flex items-center gap-6">
                    <a href="{{ route('plan.import') }}"
                       class="text-sm {{ request()->routeIs('plan.import*') ? 'text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-900' }}">
                        Import
                    </a>
                    <a href="{{ route('plan.preview') }}"
                       class="text-sm {{ request()->routeIs('plan.preview') ? 'text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-900' }}">
                        Preview
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <div id="app" class="flex-1">
        <main>
            @yield('content')
        </main>
    </div>

    <footer class="bg-gray-100 border-t border-gray-200 py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-center gap-6 text-sm text-gray-500">
                <a href="#" class="hover:text-gray-700">About</a>
                <span class="text-gray-300">•</span>
                <a href="#" class="hover:text-gray-700">Feedback</a>
                <span class="text-gray-300">•</span>
                <a href="https://github.com" target="_blank" rel="noopener" class="hover:text-gray-700">GitHub</a>
            </div>
        </div>
    </footer>
</body>
</html>
