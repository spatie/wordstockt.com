@extends('layouts.app')

@section('title', 'Delete Account - WordStockt')

@section('content')
    <div class="min-h-screen py-12 px-4">
        <div class="max-w-3xl mx-auto">
            {{-- Header --}}
            <div style="margin-bottom: 3rem;">
                <a href="/" class="inline-flex items-center gap-2 text-sm hover:underline" style="color: var(--color-text-secondary); margin-bottom: 1.5rem; display: inline-flex;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to home
                </a>
                <h1 class="text-3xl sm:text-4xl font-bold" style="margin-bottom: 1rem;">Delete Your WordStockt Account</h1>
            </div>

            {{-- Content --}}
            <div style="color: var(--color-text-secondary); line-height: 1.75;">
                <section>
                    <h2 class="text-xl font-semibold" style="color: white; margin-bottom: 1rem;">Delete from the App</h2>
                    <p style="font-size: 1.125rem; margin-bottom: 1rem;">The easiest way to delete your account is directly from the WordStockt app:</p>
                    <ol style="font-size: 1.125rem; padding-left: 1.5rem; list-style-type: decimal;">
                        <li style="margin-bottom: 0.75rem;">Open the WordStockt app</li>
                        <li style="margin-bottom: 0.75rem;">Go to your Profile (tap the menu icon, then "Profile")</li>
                        <li style="margin-bottom: 0.75rem;">Scroll down and tap "Delete account"</li>
                        <li style="margin-bottom: 0.75rem;">Confirm the deletion when prompted</li>
                    </ol>
                    <p style="font-size: 1.125rem; margin-top: 1rem;">Your account will be deleted immediately.</p>
                </section>

                <section style="margin-top: 2.5rem;">
                    <h2 class="text-xl font-semibold" style="color: white; margin-bottom: 1rem;">Alternative: Request via Email</h2>
                    <p style="font-size: 1.125rem; margin-bottom: 1rem;">If you cannot access the app, you can request account deletion by email:</p>
                    <ol style="font-size: 1.125rem; padding-left: 1.5rem; list-style-type: decimal;">
                        <li style="margin-bottom: 0.75rem;">Send an email to <a href="mailto:support@spatie.be?subject=WordStockt%20Account%20Deletion%20Request" class="hover:underline" style="color: var(--color-primary); font-weight: 600;">support@spatie.be</a></li>
                        <li style="margin-bottom: 0.75rem;">Use the subject line: "WordStockt Account Deletion Request"</li>
                        <li style="margin-bottom: 0.75rem;">Include the email address associated with your WordStockt account</li>
                        <li style="margin-bottom: 0.75rem;">We will process your request within 7 days and send you a confirmation email</li>
                    </ol>
                </section>

                <section style="margin-top: 2.5rem;">
                    <h2 class="text-xl font-semibold" style="color: white; margin-bottom: 1rem;">Data That Will Be Deleted</h2>
                    <p style="font-size: 1.125rem; margin-bottom: 1rem;">When your account is deleted, the following data will be permanently removed:</p>
                    <ul style="font-size: 1.125rem; padding-left: 1.5rem; list-style-type: disc;">
                        <li style="margin-bottom: 0.5rem;">Your account profile and email address</li>
                        <li style="margin-bottom: 0.5rem;">All your game history and statistics</li>
                        <li style="margin-bottom: 0.5rem;">Your friends list and pending invitations</li>
                        <li style="margin-bottom: 0.5rem;">Any active or completed games</li>
                    </ul>
                </section>

                <section style="margin-top: 2.5rem;">
                    <h2 class="text-xl font-semibold" style="color: white; margin-bottom: 1rem;">Data Retention</h2>
                    <p style="font-size: 1.125rem;">
                        All your data is deleted immediately upon processing your request. We do not retain any personal data after account deletion. Anonymized, aggregated statistics (such as total games played on the platform) may be retained but cannot be linked back to you.
                    </p>
                </section>
            </div>

            {{-- Footer --}}
            <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--color-border);">
                <a href="/" class="inline-flex items-center gap-2 hover:underline" style="color: var(--color-primary);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to WordStockt
                </a>
            </div>
        </div>
    </div>
@endsection
