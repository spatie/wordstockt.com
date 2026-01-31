@props([
    'store' => 'ios', // 'ios' or 'android'
    'size' => 'default', // 'default' or 'large'
])

@php
    $isIos = $store === 'ios';
    $url = $isIos ? config('app.ios_app_store_url') : config('app.android_play_store_url');
    $sizeClasses = $size === 'large' ? 'px-8 py-4 text-lg' : 'px-6 py-4';
    $iconSize = $size === 'large' ? 'w-8 h-8' : 'w-7 h-7';
@endphp

@if($isIos)
    <a
        href="{{ $url }}"
        target="_blank"
        rel="noopener"
        {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-3 {$sizeClasses} rounded-xl text-white btn-primary shadow-lg"]) }}
    >
        <svg class="{{ $iconSize }}" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
        </svg>
        @if($size === 'large')
            <span>App Store</span>
        @else
            <div class="text-left">
                <div class="text-xs opacity-80">Download on the</div>
                <div class="text-lg font-semibold -mt-1">App Store</div>
            </div>
        @endif
    </a>
@else
    <a
        href="{{ $url }}"
        target="_blank"
        rel="noopener"
        {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-3 {$sizeClasses} rounded-xl transition-all hover:scale-105 glass"]) }}
    >
        <svg class="{{ $iconSize }}" viewBox="0 0 24 24" fill="currentColor" style="color: var(--color-text-primary);">
            <path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 0 1-.61-.92V2.734a1 1 0 0 1 .609-.92zm10.89 10.893l2.302 2.302-10.937 6.333 8.635-8.635zm3.199-3.198l2.807 1.626a1 1 0 0 1 0 1.73l-2.808 1.626L15.206 12l2.492-2.491zM5.864 2.658L16.802 8.99l-2.303 2.303-8.635-8.635z"/>
        </svg>
        @if($size === 'large')
            <span>Google Play</span>
        @else
            <div class="text-left">
                <div class="text-xs" style="color: var(--color-text-secondary);">Get it on</div>
                <div class="text-lg font-semibold -mt-1">Google Play</div>
            </div>
        @endif
    </a>
@endif
