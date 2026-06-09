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

    'min_participants' => (int) env('POKER_MIN_PARTICIPANTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Location fallback
    |--------------------------------------------------------------------------
    |
    | Used when a proposed date has no location and in calendar exports.
    |
    */

    'location' => env('POKER_LOCATION'),

    /*
    |--------------------------------------------------------------------------
    | Local mail safety (Brevo / prod list)
    |--------------------------------------------------------------------------
    |
    | In local, participant notification e-mails are redirected to a single
    | inbox so the production Brevo list is never spammed during dev.
    |
    */

    'redirect_mail_in_local' => (bool) env('POKER_REDIRECT_MAIL_IN_LOCAL', true),

    'local_mail_redirect' => env('POKER_LOCAL_MAIL_REDIRECT', 'martin@pegase.io'),

    /*
    |--------------------------------------------------------------------------
    | Participant Cookie
    |--------------------------------------------------------------------------
    */

    'cookie_name' => 'poker_token',

    'cookie_lifetime' => (int) env('POKER_COOKIE_LIFETIME', 525600),

];
