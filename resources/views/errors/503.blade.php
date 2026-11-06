@extends('layouts.app')

@section('title', 'Service Unavailable - WordStockt')

@section('content')
    <div class="min-h-screen flex flex-col items-center justify-center px-4 text-center">
        <div class="mb-8">
            <x-logo size="small" />
        </div>

        <div class="mb-8">
            <p class="text-6xl font-bold mb-4 tabular-nums" style="color: var(--color-warning);" aria-hidden="true">503</p>
            <h1 class="text-2xl font-semibold mb-2" style="color: var(--color-text-primary);">Service unavailable</h1>
            <p style="color: var(--color-text-secondary);">
                We're currently performing maintenance. Please check back soon.
            </p>
        </div>
    </div>
@endsection
