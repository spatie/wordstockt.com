@props([
    'title',
    'description',
    'delay' => null,
])

<div {{ $attributes->merge(['class' => 'premium-card rounded-2xl p-6 scroll-animate' . ($delay ? " scroll-animate-delay-{$delay}" : '')]) }}>
    <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background-color: var(--color-background-lighter);">
        {{ $icon }}
    </div>
    <h3 class="text-lg font-semibold mb-2">{{ $title }}</h3>
    <p class="text-sm" style="color: var(--color-text-secondary);">
        {{ $description }}
    </p>
</div>
