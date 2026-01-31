@props([
    'text',
])

<h3 class="flex items-center gap-1.5 text-2xl sm:text-3xl font-bold mb-4">
    {{-- Tile with first letter and 3D embossed effect --}}
    <div class="tile-wiggle w-8 h-8 sm:w-9 sm:h-9 flex-shrink-0 rounded flex items-center justify-center text-lg sm:text-xl font-bold select-none"
         style="background-color: #E8E4DC;
                color: #1A1A1A;
                border: 2px solid;
                border-top-color: #F5F3EF;
                border-left-color: #F5F3EF;
                border-bottom-color: #B8B4AA;
                border-right-color: #B8B4AA;
                box-shadow: 1px 2px 4px rgba(0,0,0,0.3);"
         onclick="this.classList.add('wiggling'); setTimeout(() => this.classList.remove('wiggling'), 500);">
        {{ strtoupper(substr($text, 0, 1)) }}
    </div>{{ substr($text, 1) }}
</h3>

<style>
    @keyframes wiggle {
        0%, 100% { transform: rotate(0deg); }
        20% { transform: rotate(-12deg); }
        40% { transform: rotate(10deg); }
        60% { transform: rotate(-8deg); }
        80% { transform: rotate(5deg); }
    }
    .tile-wiggle.wiggling {
        animation: wiggle 0.5s ease-in-out;
    }
</style>
