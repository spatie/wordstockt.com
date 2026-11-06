@extends('layouts.app')

@section('title', 'Password Reset - WordStockt')

@section('content')
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12 text-center relative z-10">
        <div class="mb-10">
            <x-logo size="small" />
        </div>

        <div class="w-16 h-16 rounded-full flex items-center justify-center mb-6" style="background: oklch(0.673 0.162 144.2 / 0.15);">
            <svg class="w-8 h-8" style="color: var(--color-success);" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <h1 class="text-2xl font-bold mb-3">Password changed</h1>
        <p class="max-w-sm text-lg" style="color: var(--color-text-secondary);">
            You can now log in to the WordStockt app with your new password.
        </p>
    </div>
@endsection
