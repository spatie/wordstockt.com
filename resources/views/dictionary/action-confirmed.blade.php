@extends('layouts.app')

@section('title', 'Dictionary - WordStockt')

@section('content')
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center">
            @if($action === 'invalidated')
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6" style="background: rgba(239, 68, 68, 0.15);">
                    <svg class="w-8 h-8" style="color: #ef4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold mb-3">Word Invalidated</h1>

                <p style="color: var(--color-text-secondary); font-size: 1.125rem;">
                    <span class="font-semibold" style="color: var(--color-text-primary);">{{ $word }}</span>
                    ({{ $language }}) has been marked as invalid.
                </p>
            @elseif($action === 'added')
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6" style="background: rgba(34, 197, 94, 0.15);">
                    <svg class="w-8 h-8" style="color: #22c55e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold mb-3">Word Added</h1>

                <p style="color: var(--color-text-secondary); font-size: 1.125rem;">
                    <span class="font-semibold" style="color: var(--color-text-primary);">{{ $word }}</span>
                    ({{ $language }}) has been added to the dictionary.
                </p>
            @else
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6" style="background: rgba(34, 197, 94, 0.15);">
                    <svg class="w-8 h-8" style="color: #22c55e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold mb-3">Report Dismissed</h1>

                <p style="color: var(--color-text-secondary); font-size: 1.125rem;">
                    <span class="font-semibold" style="color: var(--color-text-primary);">{{ $word }}</span>
                    ({{ $language }}) has been kept as valid.
                </p>
            @endif
        </div>
    </div>
@endsection
