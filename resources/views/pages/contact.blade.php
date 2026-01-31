@extends('pages.layout')

@section('title', 'Contact Us')

@section('page-content')
<div class="prose prose-blue max-w-none">
    <h1>Contact Us</h1>
    <p class="lead">Have questions, feedback, or need help? We'd love to hear from you.</p>

    <div class="not-prose mt-8 grid md:grid-cols-2 gap-6">
        <!-- Support -->
        <div class="bg-blue-50 rounded-xl p-6">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Support</h3>
            <p class="text-gray-600 mb-4">Need help using {{ config('app.name', 'SchoolPlanner') }}? Check out our help documentation first.</p>
            <a href="{{ route('help') }}" class="text-blue-600 font-medium hover:text-blue-700">View Help Guide &rarr;</a>
        </div>

        <!-- Email -->
        <div class="bg-green-50 rounded-xl p-6">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Email</h3>
            <p class="text-gray-600 mb-4">For general inquiries, feedback, or issues not covered in the help guide.</p>
            <a href="mailto:support@schoolplanner.app" class="text-green-600 font-medium hover:text-green-700">support@schoolplanner.app</a>
        </div>
    </div>

    <h2>Frequently Asked Questions</h2>

    <h3>How do I find my Canvas calendar URL?</h3>
    <p>In Canvas, go to <strong>Calendar</strong> &rarr; <strong>Calendar Feed</strong> (bottom of the page). Copy the URL that appears. For detailed instructions, see our <a href="{{ route('help') }}">Help Guide</a>.</p>

    <h3>Can I use SchoolPlanner with other LMS platforms?</h3>
    <p>Currently, {{ config('app.name', 'SchoolPlanner') }} is optimized for Canvas LMS. Support for other platforms may be added in the future based on user demand.</p>

    <h3>Is my data secure?</h3>
    <p>Yes. We use HTTPS encryption, secure password hashing, and do not sell your data. See our <a href="{{ route('privacy') }}">Privacy Policy</a> for details.</p>

    <h3>How do I delete my account?</h3>
    <p>You can delete your account from your Profile settings. This will permanently remove all your data, including saved study plans.</p>

    <h2>Bug Reports</h2>
    <p>Found a bug? Please include:</p>
    <ul>
        <li>What you were trying to do</li>
        <li>What happened instead</li>
        <li>Your browser and device type</li>
        <li>Screenshots if possible</li>
    </ul>
    <p>Send bug reports to <a href="mailto:support@schoolplanner.app">support@schoolplanner.app</a> with "Bug Report" in the subject line.</p>
</div>
@endsection
