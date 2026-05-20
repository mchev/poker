<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Minimum Participants
    |--------------------------------------------------------------------------
    |
    | Number of "yes" votes required on a proposed date before the next
    | tournament is automatically confirmed and emails are sent.
    |
    */

    'min_participants' => (int) env('POKER_MIN_PARTICIPANTS', 4),

    /*
    |--------------------------------------------------------------------------
    | Location (email only)
    |--------------------------------------------------------------------------
    |
    | The meeting location is never shown on the public page. It is included
    | only in confirmation emails sent to subscribed participants.
    |
    */

    'location' => env('POKER_LOCATION'),

    /*
    |--------------------------------------------------------------------------
    | Participant Cookie
    |--------------------------------------------------------------------------
    */

    'cookie_name' => 'poker_token',

    'cookie_lifetime' => (int) env('POKER_COOKIE_LIFETIME', 525600),

];
