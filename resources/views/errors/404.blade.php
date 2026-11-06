@extends('layouts.app')

@section('title', 'Page Not Found - WordStockt')

@section('content')
    <div class="min-h-screen flex flex-col items-center justify-center px-4 text-center">
        <div class="mb-8">
            <x-logo size="small" />
        </div>

        <div class="mb-8">
            <p class="text-6xl font-bold mb-4 tabular-nums" style="color: var(--color-primary);" aria-hidden="true">404</p>
            <h1 class="text-2xl font-semibold mb-2" style="color: var(--color-text-primary);">Page not found</h1>
            <p style="color: var(--color-text-secondary);">
                The page you're looking for doesn't exist or has been moved.
            </p>
        </div>

        <a
            href="/"
            class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-sm font-medium btn-primary"
        >
            &larr; Back to home
        </a>
    </div>
@endsection
