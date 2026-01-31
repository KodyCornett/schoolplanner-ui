<div class="text-center mb-12">
    <h1 class="text-3xl font-bold text-gray-900">Simple, transparent pricing</h1>
    <p class="mt-4 text-lg text-gray-600">Start free, upgrade when you need more planning power</p>
</div>

@if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
@endif

<div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
    <!-- Free Plan -->
    <div class="bg-white rounded-2xl border-2 border-gray-200 p-8">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Free</h3>
            <div class="mt-4">
                <span class="text-4xl font-bold text-gray-900">$0</span>
                <span class="text-gray-500">/month</span>
            </div>
            <p class="mt-2 text-sm text-gray-500">Perfect for getting started</p>
        </div>

        <ul class="space-y-4 mb-8">
            <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-gray-600"><strong>14-day</strong> planning horizon</span>
            </li>
            <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-gray-600">Canvas calendar import</span>
            </li>
            <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-gray-600">Smart study scheduling</span>
            </li>
            <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-gray-600">Drag & drop editing</span>
            </li>
            <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-gray-600">ICS export</span>
            </li>
            <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-gray-600">3 saved plans</span>
            </li>
        </ul>

        @auth
            @if(!auth()->user()->isPro())
                <div class="text-center py-3 text-sm text-green-600 font-medium bg-green-50 rounded-lg">
                    Your current plan
                </div>
            @else
                <a href="{{ route('dashboard') }}" class="block w-full text-center py-3 px-4 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition">
                    Go to Dashboard
                </a>
            @endif
        @else
            <a href="{{ route('register') }}" class="block w-full text-center py-3 px-4 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition">
                Get Started
            </a>
        @endauth
    </div>

    <!-- Pro Plan -->
    <div class="bg-white rounded-2xl border-2 border-blue-500 p-8 relative">
        <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
            <span class="bg-blue-500 text-white text-sm font-medium px-4 py-1 rounded-full">Most Popular</span>
        </div>

        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Pro</h3>
            <div class="mt-4">
                <span class="text-4xl font-bold text-gray-900">$5</span>
                <span class="text-gray-500">/month</span>
            </div>
            <p class="mt-2 text-sm text-gray-500">For serious students</p>
        </div>

        <ul class="space-y-4 mb-8">
            <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-gray-600"><strong>30-day</strong> planning horizon</span>
            </li>
            <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-gray-600">Everything in Free</span>
            </li>
            <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-gray-600">Busy time calendar support</span>
            </li>
            <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-gray-600">Priority support</span>
            </li>
        </ul>

        @auth
            @if(auth()->user()->isPro())
                <div class="space-y-3">
                    <div class="text-center py-3 text-sm text-blue-600 font-medium bg-blue-50 rounded-lg">
                        Your current plan
                    </div>
                    <a href="{{ route('billing.portal') }}" class="block w-full text-center py-3 px-4 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition">
                        Manage Subscription
                    </a>
                </div>
            @else
                <form action="{{ route('billing.checkout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-3 px-4 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">
                        Upgrade to Pro
                    </button>
                </form>
            @endif
        @else
            <a href="{{ route('register') }}" class="block w-full text-center py-3 px-4 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">
                Get Started
            </a>
        @endauth
    </div>
</div>

<!-- FAQ -->
<div class="mt-16 max-w-3xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-900 text-center mb-8">Frequently Asked Questions</h2>

    <div class="space-y-6">
        <div>
            <h3 class="text-lg font-medium text-gray-900">What's the difference between 14-day and 30-day horizon?</h3>
            <p class="mt-2 text-gray-600">The planning horizon determines how far ahead your study schedule extends. Free users can plan up to 14 days in advance, while Pro users can plan up to 30 days, helping you prepare for exams and major projects further in advance.</p>
        </div>

        <div>
            <h3 class="text-lg font-medium text-gray-900">Can I cancel anytime?</h3>
            <p class="mt-2 text-gray-600">Yes! You can cancel your Pro subscription anytime through the billing portal. You'll continue to have access to Pro features until the end of your billing period.</p>
        </div>

        <div>
            <h3 class="text-lg font-medium text-gray-900">What payment methods do you accept?</h3>
            <p class="mt-2 text-gray-600">We accept all major credit cards through Stripe's secure payment processing.</p>
        </div>

        <div>
            <h3 class="text-lg font-medium text-gray-900">Is my payment information secure?</h3>
            <p class="mt-2 text-gray-600">Absolutely. All payments are processed by Stripe, a PCI-compliant payment processor. We never store your credit card details on our servers.</p>
        </div>
    </div>
</div>
