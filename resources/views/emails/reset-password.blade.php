<x-mail::message>
# Reset Your Password

Hello {{ $username }},

You requested a password reset for your WordStockt account. Click the button below to set a new password.

<x-mail::button :url="$resetUrl">
Reset Password
</x-mail::button>

This link will expire in 60 minutes.

If you didn't request this password reset, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
