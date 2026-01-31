@props([
    'title',
    'description',
    'delay' => null,
])

<div {{ $attributes->merge(['class' => 'text-center p-8 rounded-2xl scroll-animate rule-card' . ($delay ? " scroll-animate-delay-{$delay}" : '')]) }}>
    <div class="w-16 h-16 rounded-2xl mx-auto mb-5 flex items-center justify-center rule-icon">
        {{ $icon }}
    </div>
    <h3 class="text-lg font-semibold mb-3">{{ $title }}</h3>
    <p class="text-sm" style="color: var(--color-text-secondary);">
        {{ $description }}
    </p>
</div>
