<?php

return [
    'channel_access_token' => env('LINE_ACCESS_TOKEN'),
    'channel_secret' => env('LINE_CHENNEL_SECRET'),

    'liff_urls' => [
        'opponent_create' => env('LIFF_OPPONENT_CREATE_URL'),
        'opponent_edit' => env('LIFF_OPPONENT_EDIT_URL'),
    ],

    'liff_ids' => [
        'opponent_create' => env('LIFF_OPPONENT_CREATE_LIFF_ID'),
        'opponent_edit' => env('LIFF_OPPONENT_EDIT_LIFF_ID'),
    ],
];
