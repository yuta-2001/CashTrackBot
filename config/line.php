<?php

return [
    'channel_access_token' => env('LINE_ACCESS_TOKEN'),
    'channel_secret' => env('LINE_CHENNEL_SECRET'),

    'text_from_rich_menu' => [
        'lending_and_borrowing' => '貸借り管理',
        'opponent' => '相手管理',
        'how_to_use' => '使い方',
    ],

    'liff_urls' => [
        'opponent_create' => env('LIFF_OPPONENT_CREATE_URL'),
        'opponent_edit' => env('LIFF_OPPONENT_EDIT_URL'),
        'lending_and_borrowing_create' => env('LIFF_LENDING_AND_BORROWING_CREATE_URL'),
    ],

    'liff_ids' => [
        'opponent_create' => env('LIFF_OPPONENT_CREATE_LIFF_ID'),
        'opponent_edit' => env('LIFF_OPPONENT_EDIT_LIFF_ID'),
        'lending_and_borrowing_create' => env('LIFF_LENDING_AND_BORROWING_CREATE_LIFF_ID'),
    ],

    'explanation' => [
        'overview' => [
            'title' => 'LINE BOTの概要',
            'content' => "このLINE BOTは、貸し借りの記録を管理するためのBOTです。\nリッチメニューの「相手管理」から相手を作成=>「貸し借り管理」から貸し借りの記録を作成することができます。",
        ],
        'how_to_manage_lending_and_borrowing' => [
            'title' => '記録の作成・確認方法',
            'content' => "リッチメニューの「貸し借り管理」から貸し借りの記録を作成することができます。\nまた、あらかじめリッチメニューの「相手管理」から相手を作成しておく必要があります。\n記録を作成する際は、リッチメニューの「貸借り管理」=>「新規作成」から作成することができます。\n記録を確認する際は、リッチメニューの「貸借り管理」=>「全て」「貸し(未清算)」「借り(未清算)」から確認することができます。\n貸し借りの記録は、「作成」「編集」「削除」が可能です。\n一度削除した記録を復元することはできませんので、ご注意ください。",
        ],
        'how_to_manage_opponent' => [
            'title' => '相手管理方法',
            'content' => 'リッチメニューの「相手管理」から相手の作成、編集、削除が可能です。相手の削除を行うと、その相手との貸し借りの記録も削除されます。',
        ],
        'caution' => [
            'title' => '注意事項',
            'content' => "1️⃣相手を削除した場合、その相手との貸し借りの記録も削除されます。\n2️⃣このアカウントをブロックした場合、これまで作成した記録は失われます。\n3️⃣一度「削除」や「編集」をしたデータについてはもとに戻すことができません。",
        ],
    ],

    'text_from_liff' => [
        'opponent_create' => '相手の新規作成が完了しました！',
        'opponent_create_error' => '相手の新規作成に失敗しました。',
        'opponent_edit' => '相手の編集が完了しました！',
        'opponent_edit_error' => '相手の編集に失敗しました。',
        'lending_and_borrowing_create' => '貸借り記録の作成が完了しました！',
        'lending_and_borrowing_create_error' => '貸借り記録の新規作成に失敗しました。',
    ],

    'liff_token_expiration_minutes' => 20,

    'liff_channel_id' => env('LIFF_CHANNEL_ID'),
];
