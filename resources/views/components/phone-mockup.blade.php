@props([
    'width' => 'w-48 sm:w-56',
    'class' => '',
    'id' => null,
    'carousel' => false,
])

<div
    class="phone-mockup {{ $width }} {{ $class }}"
    @if($id) id="{{ $id }}" @endif
>
    <div class="phone-screen @if($carousel) screenshot-carousel @endif" @if($id && $carousel) id="{{ $id }}" @endif>
        {{ $slot }}
    </div>
</div>
