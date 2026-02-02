@extends('pages.layout')

@section('title', 'Privacy Policy')

@section('page-content')
<div class="prose prose-blue max-w-none">
    <h1>Privacy Policy</h1>
    <p class="text-gray-500">Last updated: {{ date('F j, Y') }}</p>

    <h2>1. Introduction</h2>
    <p>{{ config('app.name', 'Modulus') }} ("we", "our", or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, and safeguard your information when you use our service.</p>

    <h2>2. Information We Collect</h2>

    <h3>2.1 Account Information</h3>
    <p>When you create an account, we collect:</p>
    <ul>
        <li>Email address</li>
        <li>Password (stored securely using industry-standard hashing)</li>
        <li>Name (optional)</li>
    </ul>

    <h3>2.2 Calendar Data</h3>
    <p>When you use the Service, we process:</p>
    <ul>
        <li>Canvas calendar feed URLs you provide</li>
        <li>Assignment names, due dates, and course information from your Canvas calendar</li>
        <li>Busy time calendar data (if you choose to provide it)</li>
        <li>Study plans and work blocks you create or modify</li>
    </ul>

    <h3>2.3 Usage Information</h3>
    <p>We automatically collect:</p>
    <ul>
        <li>Log data (IP address, browser type, pages visited)</li>
        <li>Device information</li>
        <li>Service usage patterns</li>
    </ul>

    <h2>3. How We Use Your Information</h2>
    <p>We use collected information to:</p>
    <ul>
        <li>Provide and maintain the Service</li>
        <li>Generate personalized study schedules</li>
        <li>Store your study plans for future access</li>
        <li>Improve and optimize the Service</li>
        <li>Communicate with you about Service updates</li>
        <li>Respond to your inquiries and support requests</li>
    </ul>

    <h2>4. Data Storage and Retention</h2>
    <ul>
        <li>Your account data is stored securely and retained while your account is active</li>
        <li>We keep your 3 most recent study plans; older plans are automatically deleted</li>
        <li>Calendar data is processed temporarily and not permanently stored beyond what's needed for your study plans</li>
        <li>You can request deletion of your account and all associated data at any time</li>
    </ul>

    <h2>5. Data Sharing</h2>
    <p><strong>We do not sell your data.</strong></p>
    <p>We may share your information only in these limited circumstances:</p>
    <ul>
        <li><strong>Service providers:</strong> Third-party services that help us operate (e.g., hosting, email delivery), bound by confidentiality agreements</li>
        <li><strong>Legal requirements:</strong> When required by law, subpoena, or legal process</li>
        <li><strong>Safety:</strong> To protect the rights, property, or safety of users or the public</li>
    </ul>

    <h2>6. Data Security</h2>
    <p>We implement appropriate security measures including:</p>
    <ul>
        <li>HTTPS encryption for all data transmission</li>
        <li>Secure password hashing</li>
        <li>Regular security updates</li>
        <li>Limited access to personal data</li>
    </ul>
    <p>However, no method of transmission over the Internet is 100% secure, and we cannot guarantee absolute security.</p>

    <h2>7. Your Rights</h2>
    <p>You have the right to:</p>
    <ul>
        <li><strong>Access:</strong> Request a copy of your personal data</li>
        <li><strong>Correction:</strong> Update or correct your information</li>
        <li><strong>Deletion:</strong> Request deletion of your account and data</li>
        <li><strong>Export:</strong> Download your study plans in standard formats</li>
    </ul>
    <p>To exercise these rights, please <a href="{{ route('contact') }}">contact us</a>.</p>

    <h2>8. Cookies</h2>
    <p>We use essential cookies to:</p>
    <ul>
        <li>Maintain your login session</li>
        <li>Remember your preferences</li>
        <li>Ensure security (CSRF protection)</li>
    </ul>
    <p>We do not use tracking cookies or third-party advertising cookies.</p>

    <h2>9. Third-Party Links</h2>
    <p>The Service may contain links to third-party websites. We are not responsible for the privacy practices of these external sites. We encourage you to review their privacy policies.</p>

    <h2>10. Children's Privacy</h2>
    <p>The Service is intended for users who are at least 13 years old. We do not knowingly collect personal information from children under 13. If we become aware that we have collected data from a child under 13, we will take steps to delete it.</p>

    <h2>11. Changes to This Policy</h2>
    <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last updated" date. Continued use of the Service after changes constitutes acceptance of the updated policy.</p>

    <h2>12. Contact Us</h2>
    <p>If you have questions about this Privacy Policy or our data practices, please <a href="{{ route('contact') }}">contact us</a>.</p>
</div>
@endsection
