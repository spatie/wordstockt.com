@extends('layouts.app')

@section('title', $inviterName . ' invited you to play WordStockt')
@section('og_title', $inviterName . ' invited you to play WordStockt!')
@section('og_description', 'Join ' . $inviterName . ' for a word battle! Tap to open the game in WordStockt.')

@section('content')
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12">
        <div class="text-center max-w-md">
            <div class="mb-8">
                @include('components.logo')
            </div>

            <h1 class="text-3xl font-bold mb-4" style="color: var(--color-text-primary);">
                Game Invitation
            </h1>

            <p class="text-lg mb-8" style="color: var(--color-text-secondary);">
                <strong style="color: var(--color-text-primary);">{{ $inviterName }}</strong> has invited you to play a word game!
            </p>

            <div class="space-y-6">
                <a href="{{ $appStoreUrl }}"
                   class="inline-block px-8 py-4 rounded-xl font-semibold text-lg transition-transform hover:scale-105"
                   style="background-color: var(--color-primary); color: var(--color-text-primary);">
                    Open in App
                </a>

                <div>
                    <p class="text-sm mb-3" style="color: var(--color-text-secondary);">
                        Don't have the app yet?
                    </p>

                    <div class="flex gap-4 justify-center">
                        <a href="{{ $iosUrl }}" class="text-sm underline" style="color: var(--color-primary);">
                            Download for iOS
                        </a>
                        <a href="{{ $androidUrl }}" class="text-sm underline" style="color: var(--color-primary);">
                            Download for Android
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($autoRedirect)
    <script>
        setTimeout(function() {
            window.location.href = '{{ $appStoreUrl }}';
        }, 100);
    </script>
    @endif
@endsection
