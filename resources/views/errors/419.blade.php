@extends('layouts.app')

@section('title', 'Page Expired - WordStockt')

@section('content')
    <div class="min-h-screen flex flex-col items-center justify-center px-4 text-center">
        <div class="mb-8">
            <x-logo size="small" />
        </div>

        <div class="mb-8">
            <p class="text-6xl font-bold mb-4 tabular-nums" style="color: var(--color-warning);" aria-hidden="true">419</p>
            <h1 class="text-2xl font-semibold mb-2" style="color: var(--color-text-primary);">Page expired</h1>
            <p style="color: var(--color-text-secondary);">
                Your session has expired. Please refresh and try again.
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
