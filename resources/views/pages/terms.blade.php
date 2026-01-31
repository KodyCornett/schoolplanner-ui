@extends('pages.layout')

@section('title', 'Terms of Service')

@section('page-content')
<div class="prose prose-blue max-w-none">
    <h1>Terms of Service</h1>
    <p class="text-gray-500">Last updated: {{ date('F j, Y') }}</p>

    <h2>1. Acceptance of Terms</h2>
    <p>By accessing and using {{ config('app.name', 'SchoolPlanner') }} ("the Service"), you accept and agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use the Service.</p>

    <h2>2. Description of Service</h2>
    <p>{{ config('app.name', 'SchoolPlanner') }} is a study scheduling tool that helps students organize their academic workload by:</p>
    <ul>
        <li>Importing calendar data from Canvas Learning Management System</li>
        <li>Generating optimized study schedules based on assignment deadlines</li>
        <li>Allowing users to preview, edit, and export study plans</li>
    </ul>

    <h2>3. User Accounts</h2>
    <p>To use certain features of the Service, you must create an account. You agree to:</p>
    <ul>
        <li>Provide accurate and complete information when creating your account</li>
        <li>Maintain the security of your account credentials</li>
        <li>Notify us immediately of any unauthorized access to your account</li>
        <li>Accept responsibility for all activities that occur under your account</li>
    </ul>

    <h2>4. User Responsibilities</h2>
    <p>When using the Service, you agree to:</p>
    <ul>
        <li>Use the Service only for lawful purposes</li>
        <li>Not attempt to interfere with or disrupt the Service</li>
        <li>Not attempt to access data or features you are not authorized to use</li>
        <li>Comply with all applicable laws and regulations</li>
    </ul>

    <h2>5. Data and Privacy</h2>
    <p>Your use of the Service is also governed by our <a href="{{ route('privacy') }}">Privacy Policy</a>. By using the Service, you consent to the collection and use of information as described in that policy.</p>

    <h2>6. Intellectual Property</h2>
    <p>The Service and its original content, features, and functionality are owned by {{ config('app.name', 'SchoolPlanner') }} and are protected by international copyright, trademark, and other intellectual property laws.</p>

    <h2>7. Disclaimer of Warranties</h2>
    <p>The Service is provided "as is" and "as available" without warranties of any kind, either express or implied. We do not guarantee that:</p>
    <ul>
        <li>The Service will meet your specific requirements</li>
        <li>The Service will be uninterrupted, timely, secure, or error-free</li>
        <li>The results from using the Service will be accurate or reliable</li>
    </ul>

    <h2>8. Limitation of Liability</h2>
    <p>In no event shall {{ config('app.name', 'SchoolPlanner') }}, its directors, employees, or agents be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of the Service.</p>
    <p><strong>Important:</strong> The Service generates study schedules as suggestions only. You are responsible for managing your academic deadlines and should always verify important dates with your institution.</p>

    <h2>9. Service Modifications</h2>
    <p>We reserve the right to modify, suspend, or discontinue the Service at any time, with or without notice. We shall not be liable to you or any third party for any modification, suspension, or discontinuation of the Service.</p>

    <h2>10. Termination</h2>
    <p>We may terminate or suspend your account and access to the Service immediately, without prior notice, for any reason, including breach of these Terms. Upon termination, your right to use the Service will cease immediately.</p>

    <h2>11. Changes to Terms</h2>
    <p>We reserve the right to update these Terms at any time. We will notify users of any material changes by posting the new Terms on this page. Your continued use of the Service after changes constitutes acceptance of the new Terms.</p>

    <h2>12. Contact Us</h2>
    <p>If you have any questions about these Terms, please <a href="{{ route('contact') }}">contact us</a>.</p>
</div>
@endsection
