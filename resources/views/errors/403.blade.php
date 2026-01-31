@extends('layouts.app')

@section('title', 'Access Denied - WordStockt')

@section('content')
    <div class="min-h-screen flex flex-col items-center justify-center px-4 text-center">
        <div class="mb-8">
            <x-logo size="small" />
        </div>

        <div class="mb-8">
            <h1 class="text-6xl font-bold mb-4" style="color: var(--color-error);">403</h1>
            <h2 class="text-2xl font-semibold mb-2" style="color: var(--color-text-primary);">Access Denied</h2>
            <p style="color: var(--color-text-secondary);">
                You don't have permission to access this page.
            </p>
        </div>

        <a
            href="/"
            class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-sm font-medium transition-colors hover:opacity-80"
            style="background-color: var(--color-primary); color: var(--color-text-primary);"
        >
            &larr; Back to Home
        </a>
    </div>
@endsection
