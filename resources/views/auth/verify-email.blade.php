@extends('layouts.app')

@section('title', ($success ? 'Email Verified' : 'Verification Failed') . ' - WordStockt')

@section('content')
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12 text-center relative z-10">
        <div class="mb-10">
            <x-logo size="small" />
        </div>

        @if($success)
            <div class="w-16 h-16 rounded-full flex items-center justify-center mb-6" style="background: oklch(0.673 0.162 144.2 / 0.15);">
                <svg class="w-8 h-8" style="color: var(--color-success);" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold mb-3">Email verified</h1>
            <p class="max-w-sm text-lg mb-8" style="color: var(--color-text-secondary);">
                @isset($username)
                    Welcome, <span class="font-semibold" style="color: var(--color-text-primary);">{{ $username }}</span>!
                @endisset
                {{ $message }} Open the app to start playing.
            </p>
        @else
            <div class="w-16 h-16 rounded-full flex items-center justify-center mb-6" style="background: oklch(0.606 0.23 9.6 / 0.15);">
                <svg class="w-8 h-8" style="color: var(--color-error);" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold mb-3">Verification failed</h1>
            <p class="max-w-sm text-lg mb-8" style="color: var(--color-text-secondary);">
                {{ $message }} Request a new verification email from the app.
            </p>
        @endif

        <a href="wordstockt://" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold btn-primary">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            Open WordStockt
        </a>
    </div>
@endsection
