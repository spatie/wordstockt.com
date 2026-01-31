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

];
