@props([
    'tile' => null,
    'squareType' => null,
    'isCenter' => false,
    'isLastMove' => false,
])

@php
    // Square background styling matching the app
    $squareStyles = match($squareType) {
        '3W' => 'background-color: #C0392B;', // Deep crimson
        '2W' => 'background-color: #E67E22;', // Carrot orange
        '3L' => 'background-color: #1A5276;', // Deep navy
        '2L' => 'background-color: #3498DB;', // Bright cerulean
        'STAR' => 'background-color: #F39C12;', // Golden
        default => 'background-color: #2C3E50;', // Empty cell
    };

    $squareTextClasses = match($squareType) {
        '3W', '2W', '3L', '2L', 'STAR' => 'text-white text-xs font-semibold',
        default => '',
    };
@endphp

<div style="{{ $squareStyles }} width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; position: relative; border-radius: 5px;">
    @if($tile)
        {{-- Tile with 3D effect matching app --}}
        <div style="
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
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
                font-size: 22px;
                font-weight: 700;
                color: #1A1A1A;
                margin-right: 15%;
            ">{{ $tile['is_blank'] ?? false ? '' : $tile['letter'] }}</span>

            {{-- Points in bottom right corner --}}
            <span style="
                position: absolute;
                bottom: -2px;
                right: 3px;
                font-size: 13px;
                font-weight: 600;
                color: #1A1A1A;
            ">{{ $tile['points'] }}</span>

            @if($isLastMove)
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255, 193, 7, 0.25); border-radius: 5px;"></div>
            @endif
        </div>
    @elseif($isCenter)
        {{-- Star for center --}}
        <x-filament::icon
            icon="heroicon-o-star"
            style="width: 16px; height: 16px; color: white;"
        />
    @elseif($squareType)
        {{-- Multiplier label --}}
        <span style="color: white; font-size: 12px; font-weight: 600;">{{ $squareType }}</span>
    @endif
</div>
