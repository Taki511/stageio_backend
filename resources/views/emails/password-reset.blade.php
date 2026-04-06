<x-mail::message>
# Password Reset Request

Hello {{ $data['user_name'] }},

You are receiving this email because we received a password reset request for your account.

<x-mail::button :url="$data['reset_url']" color="primary">
Reset Password
</x-mail::button>

This password reset link will expire in **{{ $data['expires_in'] }}**.

If you did not request a password reset, no further action is required.

---

Best regards,<br>
{{ config('app.name') }} Team

<x-mail::subcopy>
If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser: {{ $data['reset_url'] }}
</x-mail::subcopy>
</x-mail::message>
