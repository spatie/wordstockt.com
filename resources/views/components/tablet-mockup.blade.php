@props([
    'width' => '600px',
    'class' => '',
    'landscape' => true,
])

<div
    class="tablet-mockup-landscape {{ $class }}"
    style="width: {{ $width }};"
>
    <div class="tablet-screen-landscape">
        {{ $slot }}
    </div>
</div>
