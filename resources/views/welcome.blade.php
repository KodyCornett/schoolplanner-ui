<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Automatically generate study schedules from your Canvas calendar. Plan smarter, not harder.">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:title" content="{{ config('app.name', 'Modulus') }} - Automatic Study Scheduling">
        <meta property="og:description" content="Import your Canvas calendar and let Modulus create an optimized study schedule. Spread your workload evenly, never miss a deadline.">
        <meta property="og:image" content="{{ asset('images/og-image.png') }}">

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:url" content="{{ url('/') }}">
        <meta name="twitter:title" content="{{ config('app.name', 'Modulus') }} - Automatic Study Scheduling">
        <meta name="twitter:description" content="Import your Canvas calendar and let Modulus create an optimized study schedule. Spread your workload evenly, never miss a deadline.">
        <meta name="twitter:image" content="{{ asset('images/og-image.png') }}">

        <title>{{ config('app.name', 'Modulus') }} - Automatic Study Scheduling</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-white">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="/" class="flex items-center space-x-2">
                            <x-application-logo class="block h-9 w-auto text-blue-600" />
                            <span class="font-bold text-xl text-gray-900">{{ config('app.name', 'Modulus') }}</span>
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('billing.pricing') }}" class="text-sm text-gray-600 hover:text-gray-900">Pricing</a>
                        <a href="{{ route('help') }}" class="text-sm text-gray-600 hover:text-gray-900">Help</a>
                        <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900">Log in</a>
                        <a href="{{ route('register') }}" class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Get Started</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-blue-600 to-indigo-700">
            <div class="absolute inset-0 bg-grid-white/[0.05] bg-[size:20px_20px]"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32">
                <div class="text-center">
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white tracking-tight">
                        Plan your study schedule
                        <span class="block text-blue-200">automatically</span>
                    </h1>
                    <p class="mt-6 text-lg sm:text-xl text-blue-100 max-w-2xl mx-auto">
                        Import your Canvas calendar and let Modulus create an optimized study schedule. Spread your workload evenly, never miss a deadline.
                    </p>
                    <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-3 text-base font-medium text-blue-700 bg-white rounded-lg hover:bg-blue-50 transition shadow-lg">
                            Get Started Free
                            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                        <a href="{{ route('help') }}" class="inline-flex items-center justify-center px-6 py-3 text-base font-medium text-white border-2 border-white/30 rounded-lg hover:bg-white/10 transition">
                            Learn More
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900">How it works</h2>
                    <p class="mt-4 text-lg text-gray-600">Four simple steps to a stress-free semester</p>
                </div>
                <div class="grid md:grid-cols-4 gap-8">
                    <!-- Step 1 -->
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>
                        <div class="text-sm font-semibold text-blue-600 mb-2">Step 1</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Import</h3>
                        <p class="text-gray-600">Upload your Canvas calendar or paste the feed URL</p>
                    </div>
                    <!-- Step 2 -->
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div class="text-sm font-semibold text-blue-600 mb-2">Step 2</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Generate</h3>
                        <p class="text-gray-600">We analyze deadlines and create an optimized study plan</p>
                    </div>
                    <!-- Step 3 -->
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div class="text-sm font-semibold text-blue-600 mb-2">Step 3</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Preview</h3>
                        <p class="text-gray-600">Review and adjust work blocks with drag-and-drop</p>
                    </div>
                    <!-- Step 4 -->
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </div>
                        <div class="text-sm font-semibold text-blue-600 mb-2">Step 4</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Download</h3>
                        <p class="text-gray-600">Export to your calendar app and start studying</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900">Why students love Modulus</h2>
                    <p class="mt-4 text-lg text-gray-600">Built by a student, for students</p>
                </div>
                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="bg-white rounded-xl p-6 border border-gray-200">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Never miss a deadline</h3>
                        <p class="text-gray-600">Smart scheduling ensures you start early enough to finish every assignment on time.</p>
                    </div>
                    <!-- Feature 2 -->
                    <div class="bg-white rounded-xl p-6 border border-gray-200">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Balanced workload</h3>
                        <p class="text-gray-600">Work is spread evenly across days. No more cramming before due dates.</p>
                    </div>
                    <!-- Feature 3 -->
                    <div class="bg-white rounded-xl p-6 border border-gray-200">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Full control</h3>
                        <p class="text-gray-600">Drag, resize, and lock blocks. Your schedule, your way.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-20 bg-blue-600">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold text-white mb-4">Ready to plan smarter?</h2>
                <p class="text-xl text-blue-100 mb-8">Join thousands of students who've taken control of their study schedule.</p>
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-medium text-blue-700 bg-white rounded-lg hover:bg-blue-50 transition shadow-lg">
                    Get Started Free
                    <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 text-gray-400 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex items-center space-x-2 mb-4 md:mb-0">
                        <x-application-logo class="h-8 w-auto text-white" />
                        <span class="font-semibold text-white">{{ config('app.name', 'Modulus') }}</span>
                    </div>
                    <div class="flex flex-wrap justify-center gap-6 text-sm">
                        <a href="{{ route('billing.pricing') }}" class="hover:text-white transition">Pricing</a>
                        <a href="{{ route('help') }}" class="hover:text-white transition">Help</a>
                        <a href="{{ route('terms') }}" class="hover:text-white transition">Terms of Service</a>
                        <a href="{{ route('privacy') }}" class="hover:text-white transition">Privacy Policy</a>
                        <a href="{{ route('contact') }}" class="hover:text-white transition">Contact</a>
                    </div>
                </div>
                <div class="mt-8 pt-8 border-t border-gray-800 text-center text-sm">
                    <p>&copy; {{ date('Y') }} {{ config('app.name', 'Modulus') }}. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </body>
</html>
