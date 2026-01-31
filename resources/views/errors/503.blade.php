@extends('layouts.app')

@section('title', 'Service Unavailable - WordStockt')

@section('content')
    <div class="min-h-screen flex flex-col items-center justify-center px-4 text-center">
        <div class="mb-8">
            <x-logo size="small" />
        </div>

        <div class="mb-8">
            <h1 class="text-6xl font-bold mb-4" style="color: var(--color-warning);">503</h1>
            <h2 class="text-2xl font-semibold mb-2" style="color: var(--color-text-primary);">Service Unavailable</h2>
            <p style="color: var(--color-text-secondary);">
                We're currently performing maintenance. Please check back soon.
            </p>
        </div>

        <div class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-sm" style="background-color: var(--color-background-light); color: var(--color-text-secondary);">
            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Please wait...
        </div>
    </div>
@endsection
