@extends('layouts.app')

@section('title', 'Access Denied - WordStockt')

@section('content')
    <div class="min-h-screen flex flex-col items-center justify-center px-4 text-center">
        <div class="mb-8">
            <x-logo size="small" />
        </div>

        <div class="mb-8">
            <p class="text-6xl font-bold mb-4 tabular-nums" style="color: var(--color-error);" aria-hidden="true">403</p>
            <h1 class="text-2xl font-semibold mb-2" style="color: var(--color-text-primary);">Access denied</h1>
            <p style="color: var(--color-text-secondary);">
                You don't have permission to access this page.
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
