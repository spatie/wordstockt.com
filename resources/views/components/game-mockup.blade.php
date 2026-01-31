{{-- Game Board Mockup - Replace with real screenshot later --}}
{{-- To replace: swap this component with an <img> tag pointing to your screenshot --}}

<div class="relative">
    {{-- Phone frame --}}
    <div class="relative mx-auto w-[280px] sm:w-[320px] h-[560px] sm:h-[640px] rounded-[3rem] p-3 shadow-2xl animate-float" style="background: linear-gradient(145deg, #2C3E50 0%, #1B2838 100%); border: 3px solid var(--color-background-lighter);">
        {{-- Screen --}}
        <div class="w-full h-full rounded-[2.5rem] overflow-hidden relative" style="background-color: var(--color-background);">
            {{-- Status bar --}}
            <div class="h-8 flex items-center justify-between px-6 text-xs" style="color: var(--color-text-secondary);">
                <span>9:41</span>
                <div class="flex gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.5c5.25 0 9.5-4.25 9.5-9.5S17.25 2.5 12 2.5 2.5 6.75 2.5 12s4.25 9.5 9.5 9.5z" opacity="0.3"/></svg>
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M2 17h20v2H2v-2zm0-4h20v2H2v-2zm0-4h20v2H2V9z" opacity="0.5"/></svg>
                </div>
            </div>

            {{-- Game header --}}
            <div class="px-4 py-2 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold" style="background-color: var(--color-primary);">J</div>
                    <div>
                        <div class="text-xs font-medium">Jessica</div>
                        <div class="text-lg font-bold gradient-text">127</div>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-[10px] uppercase tracking-wider" style="color: var(--color-text-secondary);">Your turn</div>
                    <div class="w-2 h-2 rounded-full mx-auto mt-1 animate-pulse" style="background-color: var(--color-success);"></div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="text-right">
                        <div class="text-xs font-medium">Freek</div>
                        <div class="text-lg font-bold" style="color: var(--color-text-secondary);">118</div>
                    </div>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold" style="background-color: var(--color-double-word);">F</div>
                </div>
            </div>

            {{-- Mini game board --}}
            <div class="px-3 py-2">
                <div class="grid grid-cols-9 gap-[2px] p-2 rounded-lg" style="background-color: var(--color-background-light);">
                    @php
                        $board = [
                            ['TW', '', '', '', 'TW', '', '', '', 'TW'],
                            ['', 'DW', '', '', '', '', '', 'DW', ''],
                            ['', '', 'DL', '', '', '', 'DL', '', ''],
                            ['', '', '', 'H', 'U', 'I', 'S', '', ''],
                            ['TW', '', '', '', 'E', '', '', '', 'TW'],
                            ['', '', '', '', 'E', '', '', '', ''],
                            ['', '', 'DL', '', 'N', '', 'DL', '', ''],
                            ['', 'DW', '', '', '', '', '', 'DW', ''],
                            ['TW', '', '', '', 'TW', '', '', '', 'TW'],
                        ];
                        $letters = ['H' => 4, 'U' => 2, 'I' => 1, 'S' => 2, 'E' => 1, 'N' => 1];
                    @endphp
                    @foreach($board as $row)
                        @foreach($row as $cell)
                            @if(strlen($cell) === 1 && ctype_alpha($cell))
                                <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-sm flex items-center justify-center text-[10px] sm:text-xs font-bold shadow-sm" style="background-color: var(--color-tile); color: var(--color-tile-text);">
                                    {{ $cell }}
                                </div>
                            @elseif($cell === 'TW')
                                <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-sm flex items-center justify-center text-[6px] font-medium" style="background-color: var(--color-triple-word); color: white;">3W</div>
                            @elseif($cell === 'DW')
                                <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-sm flex items-center justify-center text-[6px] font-medium" style="background-color: var(--color-double-word); color: white;">2W</div>
                            @elseif($cell === 'TL')
                                <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-sm flex items-center justify-center text-[6px] font-medium" style="background-color: var(--color-triple-letter); color: white;">3L</div>
                            @elseif($cell === 'DL')
                                <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-sm flex items-center justify-center text-[6px] font-medium" style="background-color: var(--color-double-letter); color: white;">2L</div>
                            @else
                                <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-sm" style="background-color: var(--color-background-lighter);"></div>
                            @endif
                        @endforeach
                    @endforeach
                </div>
            </div>

            {{-- Tile rack --}}
            <div class="px-4 py-3">
                <div class="flex justify-center gap-1">
                    @foreach(['W', 'O', 'R', 'D', 'S', 'T', 'K'] as $index => $letter)
                        <div class="tile w-8 h-8 sm:w-10 sm:h-10 rounded flex flex-col items-center justify-center font-bold shadow-md cursor-pointer" style="background-color: var(--color-tile); color: var(--color-tile-text); animation-delay: {{ $index * 0.1 }}s;">
                            <span class="text-sm sm:text-base">{{ $letter }}</span>
                            <span class="text-[8px] sm:text-[10px]" style="color: var(--color-text-secondary);">{{ rand(1, 10) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="px-4 flex gap-2">
                <button class="flex-1 py-2 rounded-lg text-xs font-medium" style="background-color: var(--color-background-light); color: var(--color-text-secondary);">
                    Swap
                </button>
                <button class="flex-1 py-2 rounded-lg text-xs font-medium" style="background-color: var(--color-background-light); color: var(--color-text-secondary);">
                    Pass
                </button>
                <button class="flex-1 py-2 rounded-lg text-xs font-medium btn-primary text-white">
                    Play
                </button>
            </div>
        </div>
    </div>

    {{-- Decorative floating tiles --}}
    <div class="absolute -top-4 -left-8 w-12 h-12 rounded-lg flex items-center justify-center font-bold text-xl shadow-lg animate-float-delayed hidden sm:flex" style="background-color: var(--color-tile); color: var(--color-tile-text);">
        A<sub class="text-xs ml-0.5">1</sub>
    </div>
    <div class="absolute -bottom-6 -right-6 w-10 h-10 rounded-lg flex items-center justify-center font-bold shadow-lg animate-float hidden sm:flex" style="background-color: var(--color-primary); color: white;">
        Z<sub class="text-xs ml-0.5">8</sub>
    </div>
    <div class="absolute top-1/3 -right-10 w-8 h-8 rounded flex items-center justify-center font-bold text-sm shadow-lg animate-float-delayed hidden lg:flex" style="background-color: var(--color-accent); color: var(--color-tile-text);">
        Q
    </div>
</div>
