<x-filament-panels::page>
    @php
        use App\Domain\Game\Enums\GameStatus;
        use App\Domain\Game\Enums\MoveType;
    @endphp

    <div class="space-y-6">
        {{-- Game Header --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold">
                        {{ $user->username }} vs {{ $opponent?->username ?? 'Waiting...' }}
                    </h2>
                    <div class="mt-2 flex items-center gap-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            @if($game->status === GameStatus::Pending) bg-yellow-100 text-yellow-800
                            @elseif($game->status === GameStatus::Active) bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ $game->status->value }}
                        </span>
                        <span class="text-sm text-gray-600">
                            Language: <strong>{{ strtoupper($game->language) }}</strong>
                        </span>
                        @if($game->turn_expires_at && $game->status === GameStatus::Active)
                            <span class="text-sm text-gray-600">
                                Turn expires: <strong>{{ $game->turn_expires_at->diffForHumans() }}</strong>
                            </span>
                        @endif
                    </div>
                </div>
                @if($game->winner)
                    <div class="text-right">
                        <div class="text-lg font-semibold text-green-600">
                            @if($game->isWinner($user))
                                You won! 🎉
                            @else
                                {{ $game->winner->username }} won
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Players Section --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-game.player-rack
                :tiles="$userGamePlayer?->rack_tiles ?? []"
                :playerName="$user->username"
                :score="$userGamePlayer?->score ?? 0"
                :isCurrentTurn="$game->isCurrentTurn($user)"
                :hasFreeSwap="$userGamePlayer?->has_free_swap ?? false"
            />

            @if($opponent)
                <x-game.player-rack
                    :tiles="$opponentGamePlayer?->rack_tiles ?? []"
                    :playerName="$opponent->username"
                    :score="$opponentGamePlayer?->score ?? 0"
                    :isCurrentTurn="$game->isCurrentTurn($opponent)"
                    :hasFreeSwap="$opponentGamePlayer?->has_free_swap ?? false"
                />
            @endif
        </div>

        {{-- Board Section --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Game Board</h3>
            <div class="flex justify-center">
                <x-game.board
                    :boardState="$game->board_state ?? []"
                    :boardTemplate="$game->board_template"
                    :lastMoveTiles="$lastMoveTiles"
                />
            </div>
        </div>

        {{-- Move History Section --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Move History</h3>
            @if($moves->isEmpty())
                <p class="text-gray-500 text-center py-8">No moves yet</p>
            @else
                <div class="space-y-3">
                    @foreach($moves as $move)
                        <div class="border-l-4 pl-4 py-2
                            @if($move->user_id === $user->id) border-blue-500
                            @else border-gray-300
                            @endif">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="font-semibold">
                                        {{ $move->user->username }}
                                    </span>
                                    <span class="text-sm text-gray-600">
                                        {{ $move->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($move->type === MoveType::Play)
                                        <span class="text-lg font-bold text-green-600">
                                            +{{ $move->score }} pts
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-1">
                                @if($move->type === MoveType::Play)
                                    @if($move->words)
                                        <div class="text-sm">
                                            Played:
                                            @foreach($move->words as $word)
                                                <span class="font-mono font-bold">
                                                    {{ strtoupper(is_array($word) ? $word['word'] : $word) }}
                                                </span>
                                                @if(!$loop->last), @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-600">Played tiles</span>
                                    @endif
                                @elseif($move->type === MoveType::Pass)
                                    <span class="text-sm text-gray-600">Passed turn</span>
                                @elseif($move->type === MoveType::Swap)
                                    <span class="text-sm text-gray-600">Swapped {{ count($move->tiles ?? []) }} tiles</span>
                                @elseif($move->type === MoveType::Resign)
                                    <span class="text-sm text-red-600">Resigned from game</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
