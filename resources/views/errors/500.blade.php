@extends('layouts.app')

@section('title', 'Server Error - WordStockt')

@section('content')
    <div class="min-h-screen flex flex-col items-center justify-center px-4 text-center">
        <div class="mb-8">
            <x-logo size="small" />
        </div>

        <div class="mb-8">
            <p class="text-6xl font-bold mb-4 tabular-nums" style="color: var(--color-error);" aria-hidden="true">500</p>
            <h1 class="text-2xl font-semibold mb-2" style="color: var(--color-text-primary);">Server error</h1>
            <p style="color: var(--color-text-secondary);">
                Something went wrong on our end. Please try again later.
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
