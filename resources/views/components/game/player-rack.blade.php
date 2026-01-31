@props([
    'tiles' => [],
    'playerName' => '',
    'score' => 0,
    'isCurrentTurn' => false,
    'hasFreeSwap' => false,
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-lg shadow-md p-5 border border-gray-200 dark:border-gray-700']) }}>
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <h3 class="text-lg font-semibold dark:text-white">{{ $playerName }}</h3>
            @if($isCurrentTurn)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    Your Turn
                </span>
            @endif
            @if($hasFreeSwap)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    Free Swap
                </span>
            @endif
        </div>
        <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ $score }} <span class="text-sm font-normal text-gray-600 dark:text-gray-400">pts</span>
        </div>
    </div>

    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        @forelse($tiles as $tile)
            <div style="
                width: 56px;
                height: 56px;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: #E8E4DC;
                border-radius: 5px;
                border-top: 2px solid #F5F3EF;
                border-left: 2px solid #F5F3EF;
                border-bottom: 2px solid #B8B4AA;
                border-right: 2px solid #B8B4AA;
                box-shadow: 1px 2px 3px rgba(0, 0, 0, 0.3);
            ">
                {{-- Letter positioned slightly left of center --}}
                <span style="
                    position: absolute;
                    font-size: 28px;
                    font-weight: 700;
                    color: #1A1A1A;
                    margin-right: 15%;
                ">{{ $tile['is_blank'] ?? false ? '' : $tile['letter'] }}</span>

                {{-- Points in bottom right corner --}}
                <span style="
                    position: absolute;
                    bottom: 0px;
                    right: 4px;
                    font-size: 16px;
                    font-weight: 600;
                    color: #1A1A1A;
                ">{{ $tile['points'] }}</span>
            </div>
        @empty
            <span class="text-sm text-gray-500 dark:text-gray-400">No tiles</span>
        @endforelse
    </div>
</div>
