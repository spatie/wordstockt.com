@extends('layouts.app')

@section('title', 'Invalid Link - WordStockt')

@section('content')
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12 text-center relative z-10">
        <div class="mb-10">
            <x-logo size="small" />
        </div>

        <div class="w-16 h-16 rounded-full flex items-center justify-center mb-6" style="background: oklch(0.606 0.23 9.6 / 0.15);">
            <svg class="w-8 h-8" style="color: var(--color-error);" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>

        <h1 class="text-2xl font-bold mb-3">This link has expired</h1>
        <p class="max-w-sm text-lg" style="color: var(--color-text-secondary);">
            Password reset links work once and expire after an hour. Request a new one from the WordStockt app.
        </p>
    </div>
@endsection
