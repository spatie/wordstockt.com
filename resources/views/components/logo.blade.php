@props(['size' => 'large', 'align' => 'center'])

@php
    $alignClass = $align === 'left' ? 'lg:justify-start' : '';
@endphp

<div class="flex justify-center {{ $alignClass }} gap-1 mb-0">
    @if($size === 'small')
        <div class="w-6 h-6 text-[14px] rounded flex items-center justify-center font-bold text-white shadow-md" style="background-color: var(--color-primary);">W</div>
        <div class="w-6 h-6 text-[14px] rounded flex items-center justify-center font-bold shadow-md" style="background-color: var(--color-tile); color: var(--color-tile-text);">O</div>
        <div class="w-6 h-6 text-[14px] rounded flex items-center justify-center font-bold shadow-md" style="background-color: var(--color-tile); color: var(--color-tile-text);">R</div>
        <div class="w-6 h-6 text-[14px] rounded flex items-center justify-center font-bold shadow-md" style="background-color: var(--color-tile); color: var(--color-tile-text);">D</div>
        <div class="w-6 h-6 text-[14px] rounded flex items-center justify-center font-bold text-white shadow-md" style="background-color: var(--color-primary);">S</div>
        <div class="w-6 h-6 text-[14px] rounded flex items-center justify-center font-bold shadow-md" style="background-color: var(--color-tile); color: var(--color-tile-text);">T</div>
        <div class="w-6 h-6 text-[14px] rounded flex items-center justify-center font-bold shadow-md" style="background-color: var(--color-tile); color: var(--color-tile-text);">O</div>
        <div class="w-6 h-6 text-[14px] rounded flex items-center justify-center font-bold shadow-md" style="background-color: var(--color-tile); color: var(--color-tile-text);">C</div>
        <div class="w-6 h-6 text-[14px] rounded flex items-center justify-center font-bold shadow-md" style="background-color: var(--color-tile); color: var(--color-tile-text);">K</div>
        <div class="w-6 h-6 text-[14px] rounded flex items-center justify-center font-bold shadow-md" style="background-color: var(--color-tile); color: var(--color-tile-text);">T</div>
    @else
        <div class="tile size-8 text-base sm:size-12 sm:text-[28px] rounded flex items-center justify-center font-bold text-white shadow-md" style="background-color: var(--color-primary);">W</div>
        <div class="tile size-8 text-base sm:size-12 sm:text-[28px] rounded flex items-center justify-center font-bold shadow-md" style="background-color: var(--color-tile); color: var(--color-tile-text);">O</div>
        <div class="tile size-8 text-base sm:size-12 sm:text-[28px] rounded flex items-center justify-center font-bold shadow-md" style="background-color: var(--color-tile); color: var(--color-tile-text);">R</div>
        <div class="tile size-8 text-base sm:size-12 sm:text-[28px] rounded flex items-center justify-center font-bold shadow-md" style="background-color: var(--color-tile); color: var(--color-tile-text);">D</div>
        <div class="tile size-8 text-base sm:size-12 sm:text-[28px] rounded flex items-center justify-center font-bold text-white shadow-md" style="background-color: var(--color-primary);">S</div>
        <div class="tile size-8 text-base sm:size-12 sm:text-[28px] rounded flex items-center justify-center font-bold shadow-md" style="background-color: var(--color-tile); color: var(--color-tile-text);">T</div>
        <div class="tile size-8 text-base sm:size-12 sm:text-[28px] rounded flex items-center justify-center font-bold shadow-md" style="background-color: var(--color-tile); color: var(--color-tile-text);">O</div>
        <div class="tile size-8 text-base sm:size-12 sm:text-[28px] rounded flex items-center justify-center font-bold shadow-md" style="background-color: var(--color-tile); color: var(--color-tile-text);">C</div>
        <div class="tile size-8 text-base sm:size-12 sm:text-[28px] rounded flex items-center justify-center font-bold shadow-md" style="background-color: var(--color-tile); color: var(--color-tile-text);">K</div>
        <div class="tile size-8 text-base sm:size-12 sm:text-[28px] rounded flex items-center justify-center font-bold shadow-md" style="background-color: var(--color-tile); color: var(--color-tile-text);">T</div>
    @endif
</div>
