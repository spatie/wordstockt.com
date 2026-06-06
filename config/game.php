<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ELO Rating Configuration
    |--------------------------------------------------------------------------
    |
    | These values configure how ELO ratings are calculated after each game.
    | The K-factor determines how much ratings change per game (higher = more volatile).
    | The scale factor is used in the expected score calculation (standard is 400).
    |
    */

    'elo' => [
        'k_factor' => 32,
        'scale_factor' => 400,
        'default_rating' => 1200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimum app version for 3-4 player games
    |--------------------------------------------------------------------------
    |
    | Clients below this version (or that don't send an X-App-Version header,
    | i.e. the pre-multiplayer release) cannot create games with more than two
    | players, and do not see 3-4 player games in the public games list.
    |
    */

    'min_multiplayer_app_version' => env('MIN_MULTIPLAYER_APP_VERSION', '1.7.0'),

];
