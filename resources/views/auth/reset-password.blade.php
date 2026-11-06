@extends('layouts.app')

@section('title', 'Reset Password - WordStockt')

@section('content')
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12 relative z-10">
        <div class="mb-8">
            <x-logo size="small" />
        </div>

        <div class="w-full max-w-sm rounded-2xl p-8 premium-card">
            <h1 class="text-2xl font-bold mb-2 text-center">Choose a new password</h1>
            <p class="text-sm mb-8 text-center" style="color: var(--color-text-secondary);">
                Use at least 8 characters.
            </p>

            @error('email')
                <p class="mb-5 text-sm text-center" style="color: var(--color-error-text);">{{ $message }}</p>
            @enderror

            <form method="POST" action="{{ url('/reset-password/' . $token) }}" class="flex flex-col gap-5">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">

                <div>
                    <label for="password" class="field-label">New password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="field-input"
                        autocomplete="new-password"
                        required
                        minlength="8"
                        autofocus
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                    >
                    @error('password')
                        <p id="password-error" class="flex items-center gap-1.5 mt-2 text-sm" style="color: var(--color-error-text);">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="field-label">Confirm new password</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        class="field-input"
                        autocomplete="new-password"
                        required
                        minlength="8"
                    >
                </div>

                <button type="submit" class="w-full mt-1 py-3 rounded-lg font-semibold btn-primary">
                    Save new password
                </button>
            </form>
        </div>
    </div>
@endsection
