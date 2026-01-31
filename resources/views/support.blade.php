@extends('layouts.app')

@section('title', 'Support - WordStockt')

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
                <h1 class="text-3xl sm:text-4xl font-bold" style="margin-bottom: 1rem;">Support</h1>
            </div>

            {{-- Content --}}
            <div style="color: var(--color-text-secondary); line-height: 1.75;">
                <section>
                    <p style="font-size: 1.125rem;">
                        Have a question, feedback, or need help with WordStockt? We're here to help!
                    </p>
                    <p style="margin-top: 1.5rem; font-size: 1.125rem;">
                        Please send all support questions to
                        <a href="mailto:support@spatie.be" class="hover:underline" style="color: var(--color-primary); font-weight: 600;">support@spatie.be</a>
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
