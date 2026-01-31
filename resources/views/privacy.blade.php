@extends('layouts.app')

@section('title', 'Privacy Policy - WordStockt')

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
                <h1 class="text-3xl sm:text-4xl font-bold" style="margin-bottom: 1rem;">Privacy Policy</h1>
                <p style="color: var(--color-text-secondary);">Last updated: {{ date('F j, Y') }}</p>
            </div>

            {{-- Content --}}
            <div style="color: var(--color-text-secondary); line-height: 1.75;">
                <section style="margin-bottom: 3rem;">
                    <h2 class="text-2xl font-bold" style="color: var(--color-primary); margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--color-border);">Introduction</h2>
                    <p>
                        WordStockt ("we", "our", or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, and safeguard your information when you use our mobile application and website.
                    </p>
                </section>

                <section style="margin-bottom: 3rem;">
                    <h2 class="text-2xl font-bold" style="color: var(--color-primary); margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--color-border);">Information We Collect</h2>
                    <p style="margin-bottom: 1.5rem;">We collect information that you provide directly to us:</p>
                    <ul style="list-style-type: disc; padding-left: 1.5rem;">
                        <li style="margin-bottom: 1rem;"><strong style="color: var(--color-text-primary);">Account Information:</strong> When you create an account, we collect your email address, username, and password.</li>
                        <li style="margin-bottom: 1rem;"><strong style="color: var(--color-text-primary);">Game Data:</strong> We store your game history, scores, and statistics to provide the game experience.</li>
                        <li style="margin-bottom: 1rem;"><strong style="color: var(--color-text-primary);">Communications:</strong> If you contact us, we may keep a record of that correspondence.</li>
                    </ul>
                </section>

                <section style="margin-bottom: 3rem;">
                    <h2 class="text-2xl font-bold" style="color: var(--color-primary); margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--color-border);">How We Use Your Information</h2>
                    <p style="margin-bottom: 1.5rem;">We use the information we collect to:</p>
                    <ul style="list-style-type: disc; padding-left: 1.5rem;">
                        <li style="margin-bottom: 0.5rem;">Provide, maintain, and improve our services</li>
                        <li style="margin-bottom: 0.5rem;">Create and manage your account</li>
                        <li style="margin-bottom: 0.5rem;">Enable you to play games with other users</li>
                        <li style="margin-bottom: 0.5rem;">Send you notifications about game updates and your turns</li>
                        <li style="margin-bottom: 0.5rem;">Respond to your comments and questions</li>
                        <li style="margin-bottom: 0.5rem;">Protect against fraud and abuse</li>
                    </ul>
                </section>

                <section style="margin-bottom: 3rem;">
                    <h2 class="text-2xl font-bold" style="color: var(--color-primary); margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--color-border);">Leaderboards & Public Scores</h2>
                    <p style="margin-bottom: 1.5rem;">
                        By creating an account and playing games on WordStockt, you consent to the following information being displayed publicly on our global leaderboards:
                    </p>
                    <ul style="list-style-type: disc; padding-left: 1.5rem;">
                        <li style="margin-bottom: 1rem;"><strong style="color: var(--color-text-primary);">Username:</strong> Your chosen username is visible on leaderboards and to other players.</li>
                        <li style="margin-bottom: 1rem;"><strong style="color: var(--color-text-primary);">Game Scores:</strong> Your scores from completed games are uploaded to and displayed on global leaderboards.</li>
                        <li style="margin-bottom: 1rem;"><strong style="color: var(--color-text-primary);">Game Statistics:</strong> Aggregated statistics such as games played, wins, losses, and highest scores may be displayed publicly.</li>
                    </ul>
                    <p>
                        This public display is a core feature of WordStockt that enables competitive play and community engagement. If you do not wish to have your scores displayed publicly, please do not create an account.
                    </p>
                </section>

                <section style="margin-bottom: 3rem;">
                    <h2 class="text-2xl font-bold" style="color: var(--color-primary); margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--color-border);">Information Sharing</h2>
                    <p style="margin-bottom: 1.5rem;">We do not sell your personal information. We may share your information in the following circumstances:</p>
                    <ul style="list-style-type: disc; padding-left: 1.5rem;">
                        <li style="margin-bottom: 1rem;"><strong style="color: var(--color-text-primary);">With Other Players:</strong> Your username and game statistics are visible to players you compete with.</li>
                        <li style="margin-bottom: 1rem;"><strong style="color: var(--color-text-primary);">Service Providers:</strong> We may share information with third-party service providers who help us operate our services.</li>
                        <li style="margin-bottom: 1rem;"><strong style="color: var(--color-text-primary);">Legal Requirements:</strong> We may disclose information if required by law or to protect our rights.</li>
                    </ul>
                </section>

                <section style="margin-bottom: 3rem;">
                    <h2 class="text-2xl font-bold" style="color: var(--color-primary); margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--color-border);">Data Security</h2>
                    <p>
                        We implement appropriate security measures to protect your personal information. However, no method of transmission over the Internet is 100% secure, and we cannot guarantee absolute security.
                    </p>
                </section>

                <section style="margin-bottom: 3rem;">
                    <h2 class="text-2xl font-bold" style="color: var(--color-primary); margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--color-border);">Data Retention</h2>
                    <p>
                        We retain your information for as long as your account is active or as needed to provide you services. You can request deletion of your account and associated data at any time by contacting us.
                    </p>
                </section>

                <section style="margin-bottom: 3rem;">
                    <h2 class="text-2xl font-bold" style="color: var(--color-primary); margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--color-border);">Your Rights</h2>
                    <p style="margin-bottom: 1.5rem;">You have certain rights regarding your personal information:</p>
                    <ul style="list-style-type: disc; padding-left: 1.5rem;">
                        <li style="margin-bottom: 0.5rem;">Access your personal data</li>
                        <li style="margin-bottom: 0.5rem;">Correct inaccurate data</li>
                        <li style="margin-bottom: 0.5rem;">Request deletion of your data</li>
                        <li style="margin-bottom: 0.5rem;">Object to processing of your data</li>
                        <li style="margin-bottom: 0.5rem;">Data portability</li>
                    </ul>
                </section>

                <section style="margin-bottom: 3rem;">
                    <h2 class="text-2xl font-bold" style="color: var(--color-primary); margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--color-border);">Changes to This Policy</h2>
                    <p>
                        We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date.
                    </p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold" style="color: var(--color-primary); margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--color-border);">Contact Us</h2>
                    <p>
                        If you have any questions about this Privacy Policy, please contact us at
                        <a href="mailto:{{ config('app.support_email') }}" class="hover:underline" style="color: var(--color-primary);">{{ config('app.support_email') }}</a>.
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
