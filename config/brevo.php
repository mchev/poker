<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brevo API
    |--------------------------------------------------------------------------
    |
    | Used to sync poker participants into a Brevo contact list.
    | Get your API key at https://app.brevo.com/settings/keys/api
    |
    */

    'api_key' => env('BREVO_API_KEY'),

    'list_id' => (int) env('BREVO_LIST_ID', 65),

    'base_url' => 'https://api.brevo.com/v3',

];
