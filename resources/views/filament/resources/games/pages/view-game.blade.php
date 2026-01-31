<x-filament-panels::page>
    @php
        $players = $record->players;
        $player1 = $players->get(0);
        $player2 = $players->get(1);
        $player1GamePlayer = $player1 ? $record->getGamePlayer($player1) : null;
        $player2GamePlayer = $player2 ? $record->getGamePlayer($player2) : null;
        $lastMoveTiles = $record->latestMove?->tiles ?? [];
        $moves = $record->moves()->with('user')->latest()->get();

        // Helper function for avatar initials
        function getInitials($name) {
            $parts = array_filter(explode(' ', trim($name)));
            if (count($parts) >= 2) {
                return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
            }
            return strtoupper(substr($name, 0, 2));
        }

        // Helper function for avatar color
        function getAvatarColor($name) {
            $colors = ['#4A90D9', '#9B59B6', '#27AE60', '#E67E22', '#E74C3C', '#1ABC9C', '#F39C12', '#8E44AD'];
            $hash = 0;
            for ($i = 0; $i < strlen($name); $i++) {
                $hash = ord($name[$i]) + (($hash << 5) - $hash);
            }
            return $colors[abs($hash) % count($colors)];
        }
    @endphp

    <div class="space-y-6">
        {{-- Player Score Bar with Racks (like in the game) --}}
        <div style="background: rgba(27, 40, 56, 0.95); border-radius: 12px; padding: 12px 16px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 20px;">
                {{-- Player 1 Section with Rack --}}
                <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0;">
                    @if($player1)
                        <a href="{{ \App\Filament\Resources\Users\UserResource::getUrl('view', ['record' => $player1->ulid]) }}" style="position: relative; flex-shrink: 0; cursor: pointer;">
                            @if($record->isCurrentTurn($player1))
                                <div style="position: absolute; inset: -3px; border-radius: 50%; border: 3px solid #FF9800; box-shadow: 0 0 12px rgba(255, 152, 0, 0.6);"></div>
                            @endif
                            @if($player1->avatar)
                                <img src="{{ $player1->avatar }}" alt="{{ $player1->username }}" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
                            @else
                                <div style="width: 48px; height: 48px; border-radius: 50%; background-color: {{ getAvatarColor($player1->username) }}; display: flex; align-items: center; justify-content: center;">
                                    <span style="color: white; font-weight: 600; font-size: 19px;">{{ getInitials($player1->username) }}</span>
                                </div>
                            @endif
                        </a>
                    @else
                        <div style="position: relative; flex-shrink: 0;">
                            <div style="width: 48px; height: 48px; border-radius: 50%; background-color: {{ getAvatarColor('P1') }}; display: flex; align-items: center; justify-content: center;">
                                <span style="color: white; font-weight: 600; font-size: 19px;">{{ getInitials('P1') }}</span>
                            </div>
                        </div>
                    @endif
                    <div style="min-width: 0; flex: 1;">
                        @if($player1)
                            <a href="{{ \App\Filament\Resources\Users\UserResource::getUrl('view', ['record' => $player1->ulid]) }}" style="color: {{ $record->isCurrentTurn($player1) ? '#FFFFFF' : '#8B9DC3' }}; font-size: 13px; font-weight: {{ $record->isCurrentTurn($player1) ? '600' : '500' }}; text-decoration: none; cursor: pointer;">
                                {{ $player1->username }}
                            </a>
                        @else
                            <div style="color: #8B9DC3; font-size: 13px; font-weight: 500;">
                                Player 1
                            </div>
                        @endif
                        <div style="color: #FFFFFF; font-size: 20px; font-weight: 700; margin-top: 2px;">
                            {{ $player1GamePlayer?->score ?? 0 }}
                        </div>
                        @if($player1GamePlayer?->has_free_swap)
                            <div style="display: flex; gap: 3px; margin-top: 2px;">
                                <div style="width: 5px; height: 5px; background-color: #4A90D9; border-radius: 50%;"></div>
                            </div>
                        @endif
                        {{-- Player 1 Rack --}}
                        <div style="display: flex; gap: 4px; flex-wrap: wrap; margin-top: 8px;">
                            @forelse($player1GamePlayer?->rack_tiles ?? [] as $tile)
                                <div style="
                                    width: 40px; height: 40px;
                                    position: relative;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    background-color: #E8E4DC;
                                    border-radius: 4px;
                                    border-top: 2px solid #F5F3EF;
                                    border-left: 2px solid #F5F3EF;
                                    border-bottom: 2px solid #B8B4AA;
                                    border-right: 2px solid #B8B4AA;
                                    box-shadow: 1px 2px 3px rgba(0, 0, 0, 0.3);
                                ">
                                    <span style="position: absolute; font-size: 18px; font-weight: 700; color: #1A1A1A; margin-right: 15%;">
                                        {{ $tile['is_blank'] ?? false ? '' : $tile['letter'] }}
                                    </span>
                                    <span style="position: absolute; bottom: -1px; right: 2px; font-size: 10px; font-weight: 600; color: #1A1A1A;">
                                        {{ $tile['points'] }}
                                    </span>
                                </div>
                            @empty
                                <span style="color: #8B9DC3; font-size: 11px;">No tiles</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Center Section --}}
                <div style="text-align: center; flex-shrink: 0;">
                    <div style="background-color: #4A90D9; padding: 4px 10px; border-radius: 10px; display: inline-block;">
                        <span style="color: white; font-weight: 700; font-size: 14px;">{{ $record->tiles_remaining ?? 0 }}</span>
                        <div style="color: white; font-size: 9px; opacity: 0.8; margin-top: -2px;">tiles</div>
                    </div>
                    @if($record->latestMove)
                        <div style="color: #8B9DC3; font-size: 11px; font-style: italic; margin-top: 6px; white-space: nowrap;">
                            {{ $record->latestMove->user->username }} played
                            @if($record->latestMove->words)
                                '{{ strtoupper(is_array($record->latestMove->words[0]) ? $record->latestMove->words[0]['word'] : $record->latestMove->words[0]) }}'
                            @endif
                            for {{ $record->latestMove->score }} pts
                        </div>
                    @endif
                    @if($record->status === App\Domain\Game\Enums\GameStatus::Finished && $record->winner)
                        <div style="background: {{ $record->winner_id === $player1?->id ? '#27AE60' : '#E74C3C' }}; padding: 4px 10px; border-radius: 8px; margin-top: 6px; display: inline-block;">
                            <span style="color: white; font-size: 11px; font-weight: 700;">{{ $record->winner_id === $player1?->id ? 'Won' : 'Lost' }}</span>
                        </div>
                    @endif
                </div>

                {{-- Player 2 Section with Rack --}}
                <div style="display: flex; align-items: center; gap: 12px; flex: 1; justify-content: flex-end; min-width: 0;">
                    <div style="min-width: 0; flex: 1; text-align: right;">
                        @if($player2)
                            <a href="{{ \App\Filament\Resources\Users\UserResource::getUrl('view', ['record' => $player2->ulid]) }}" style="color: {{ $record->isCurrentTurn($player2) ? '#FFFFFF' : '#8B9DC3' }}; font-size: 13px; font-weight: {{ $record->isCurrentTurn($player2) ? '600' : '500' }}; text-decoration: none; cursor: pointer;">
                                {{ $player2->username }}
                            </a>
                        @else
                            <div style="color: #8B9DC3; font-size: 13px; font-weight: 500;">
                                Player 2
                            </div>
                        @endif
                        <div style="color: #FFFFFF; font-size: 20px; font-weight: 700; margin-top: 2px;">
                            {{ $player2GamePlayer?->score ?? 0 }}
                        </div>
                        @if($player2GamePlayer?->has_free_swap)
                            <div style="display: flex; gap: 3px; margin-top: 2px; justify-content: flex-end;">
                                <div style="width: 5px; height: 5px; background-color: #4A90D9; border-radius: 50%;"></div>
                            </div>
                        @endif
                        {{-- Player 2 Rack --}}
                        <div style="display: flex; gap: 4px; flex-wrap: wrap; margin-top: 8px; justify-content: flex-end;">
                            @forelse($player2GamePlayer?->rack_tiles ?? [] as $tile)
                                <div style="
                                    width: 40px; height: 40px;
                                    position: relative;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    background-color: #E8E4DC;
                                    border-radius: 4px;
                                    border-top: 2px solid #F5F3EF;
                                    border-left: 2px solid #F5F3EF;
                                    border-bottom: 2px solid #B8B4AA;
                                    border-right: 2px solid #B8B4AA;
                                    box-shadow: 1px 2px 3px rgba(0, 0, 0, 0.3);
                                ">
                                    <span style="position: absolute; font-size: 18px; font-weight: 700; color: #1A1A1A; margin-right: 15%;">
                                        {{ $tile['is_blank'] ?? false ? '' : $tile['letter'] }}
                                    </span>
                                    <span style="position: absolute; bottom: -1px; right: 2px; font-size: 10px; font-weight: 600; color: #1A1A1A;">
                                        {{ $tile['points'] }}
                                    </span>
                                </div>
                            @empty
                                <span style="color: #8B9DC3; font-size: 11px;">No tiles</span>
                            @endforelse
                        </div>
                    </div>
                    @if($player2)
                        <a href="{{ \App\Filament\Resources\Users\UserResource::getUrl('view', ['record' => $player2->ulid]) }}" style="position: relative; flex-shrink: 0; cursor: pointer;">
                            @if($record->isCurrentTurn($player2))
                                <div style="position: absolute; inset: -3px; border-radius: 50%; border: 3px solid #FF9800; box-shadow: 0 0 12px rgba(255, 152, 0, 0.6);"></div>
                            @endif
                            @if($player2->avatar)
                                <img src="{{ $player2->avatar }}" alt="{{ $player2->username }}" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
                            @else
                                <div style="width: 48px; height: 48px; border-radius: 50%; background-color: {{ getAvatarColor($player2->username) }}; display: flex; align-items: center; justify-content: center;">
                                    <span style="color: white; font-weight: 600; font-size: 19px;">{{ getInitials($player2->username) }}</span>
                                </div>
                            @endif
                        </a>
                    @else
                        <div style="position: relative; flex-shrink: 0;">
                            <div style="width: 48px; height: 48px; border-radius: 50%; background-color: {{ getAvatarColor('P2') }}; display: flex; align-items: center; justify-content: center;">
                                <span style="color: white; font-weight: 600; font-size: 19px;">{{ getInitials('P2') }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Board Section - Centered --}}
        <div style="display: flex; justify-content: center; margin: 20px 0;">
            <x-game.board
                :boardState="$record->board_state ?? []"
                :boardTemplate="$record->board_template"
                :lastMoveTiles="$lastMoveTiles"
            />
        </div>

        {{-- Move History Section --}}
        <div style="background: rgba(27, 40, 56, 0.95); border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);">
            <h3 style="color: #FFFFFF; font-size: 18px; font-weight: 700; margin-bottom: 16px;">Move History</h3>
            @if($moves->isEmpty())
                <p style="color: #8B9DC3; text-align: center; padding: 32px 0;">No moves yet</p>
            @else
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @foreach($moves as $move)
                        <div style="background: rgba(44, 62, 80, 0.5); border-radius: 8px; padding: 12px 16px; border-left: 3px solid {{ $move->user_id === $player1?->id ? '#4A90D9' : '#8B9DC3' }};">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    {{-- Avatar for move --}}
                                    <a href="{{ \App\Filament\Resources\Users\UserResource::getUrl('view', ['record' => $move->user->ulid]) }}" style="flex-shrink: 0; cursor: pointer;">
                                        @if($move->user->avatar)
                                            <img src="{{ $move->user->avatar }}" alt="{{ $move->user->username }}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                        @else
                                            <div style="width: 32px; height: 32px; border-radius: 50%; background-color: {{ getAvatarColor($move->user->username) }}; display: flex; align-items: center; justify-content: center;">
                                                <span style="color: white; font-weight: 600; font-size: 13px;">{{ getInitials($move->user->username) }}</span>
                                            </div>
                                        @endif
                                    </a>
                                    <a href="{{ \App\Filament\Resources\Users\UserResource::getUrl('view', ['record' => $move->user->ulid]) }}" style="color: #FFFFFF; font-weight: 600; font-size: 14px; text-decoration: none; cursor: pointer;">
                                        {{ $move->user->username }}
                                    </a>
                                    <span style="color: #8B9DC3; font-size: 12px;">
                                        {{ $move->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    @if($move->type === App\Domain\Game\Enums\MoveType::Play)
                                        <span style="color: #27AE60; font-size: 18px; font-weight: 700;">
                                            +{{ $move->score }} pts
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div style="margin-left: 44px;">
                                @if($move->type === App\Domain\Game\Enums\MoveType::Play)
                                    @if($move->words)
                                        <div style="color: #FFFFFF; font-size: 13px;">
                                            Played:
                                            @foreach($move->words as $word)
                                                <span style="font-family: monospace; font-weight: 700; color: #4A90D9;">
                                                    {{ strtoupper(is_array($word) ? $word['word'] : $word) }}
                                                </span>
                                                @if(!$loop->last)<span style="color: #8B9DC3;">, </span>@endif
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color: #8B9DC3; font-size: 13px;">Played tiles</span>
                                    @endif
                                @elseif($move->type === App\Domain\Game\Enums\MoveType::Pass)
                                    <span style="color: #8B9DC3; font-size: 13px; font-style: italic;">Passed turn</span>
                                @elseif($move->type === App\Domain\Game\Enums\MoveType::Swap)
                                    <span style="color: #8B9DC3; font-size: 13px; font-style: italic;">Swapped {{ count($move->tiles ?? []) }} tiles</span>
                                @elseif($move->type === App\Domain\Game\Enums\MoveType::Resign)
                                    <span style="color: #E74C3C; font-size: 13px; font-weight: 600;">Resigned from game</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
