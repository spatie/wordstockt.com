@extends('layouts.app')

@section('title', 'Mail Previews - WordStockt Dev')

@section('content')
    <x-logo size="small" />

    <div class="mb-12">
        <h1 class="text-2xl font-bold mb-3" style="color: var(--color-text-primary);">Mail Previews</h1>
        <p class="text-sm" style="color: var(--color-text-secondary);">Preview email templates in development</p>
    </div>

    <div class="flex flex-col gap-5">
        @foreach($mails as $slug => $name)
            <a
                href="/dev/mail/{{ $slug }}"
                class="block px-10 py-8 rounded-xl text-lg font-medium transition-all hover:scale-[1.02]"
                style="background-color: var(--color-background-light); color: var(--color-text-primary);"
            >
                {{ $name }}
            </a>
        @endforeach
    </div>
@endsection
