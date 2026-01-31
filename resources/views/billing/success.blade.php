<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Welcome to Pro!') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-center">
                    <!-- Success Icon -->
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>

                    <h1 class="text-2xl font-bold text-gray-900 mb-4">Thank you for upgrading!</h1>
                    <p class="text-gray-600 mb-8">
                        Your Pro subscription is now active. You can now plan up to 30 days ahead and access all premium features.
                    </p>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
                        <h3 class="font-medium text-blue-900 mb-2">What's new with Pro:</h3>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li>30-day planning horizon (up from 14 days)</li>
                            <li>Busy time calendar support</li>
                            <li>Priority support</li>
                        </ul>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('plan.import') }}" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Create a New Plan
                        </a>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 transition">
                            Go to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
