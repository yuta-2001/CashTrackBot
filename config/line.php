<?php

return [
    'channel_access_token' => env('LINE_ACCESS_TOKEN'),
    'channel_secret' => env('LINE_CHENNEL_SECRET'),

    'text_from_rich_menu' => [
        'lending_and_borrowing' => '貸借り管理',
        'how_to_use' => '使い方',
    ],

    'explanation' => [
        'overview' => [
            'title' => 'LINE BOTの概要',
            'content' => "このアカウントはお金の貸し借りを管理するためのLINE BOTです。\nこのアカウントを友達登録すると、LINEのリッチメニューから貸し借りの記録を作成することができます。\nお金貸し借りの記録はLINEのブラウザ上で管理することができます。\n貸借り状況の簡単な確認等はLINE BOTとのメッセージ内で確認することができます。",
        ],
        'how_to_manage_lending_and_borrowing' => [
            'title' => '記録の作成・確認方法',
            'content' => "1️⃣記録の作成・編集・削除\nLINEのリッチメニューから「貸借り管理」を選択します。\n選択すると「貸借り管理ページ」と「メッセージで確認」という2つの選択肢が表示されます。\n「貸借り管理ページ」を選択すると、LINEのブラウザに遷移し、そちらで作成・編集・削除等を行うことができます。\n\n2️⃣記録の確認\n記録の確認方法は2つあります。\n1つ目は「貸借り管理ページ」で確認する方法です。\n2つ目は「メッセージで確認」を選択する方法です。\n「メッセージで確認」を選択すると、未清算の貸借り記録がある相手の一覧が表示されます。\nまた、貸借りの状況を確認でき、一括で清算することもできます。",
        ],
        'how_to_manage_opponent' => [
            'title' => '相手管理方法',
            'content' => "リッチメニューの「相手管理」を選択します。\n選択すると「相手管理ページ」という選択肢が表示されます。\n「相手管理ページ」を選択すると、LINEのブラウザに遷移し、そちらで相手の作成や編集等を行うことができます。",
        ],
        'caution' => [
            'title' => '注意事項',
            'content' => "1️⃣相手の削除を行うと、その相手との貸借り記録も全て削除されます。\n2️⃣清算済みにした貸借りの記録は３日間で自動的に削除されます。\n3️⃣このアカウントをブロックした場合、これまでの記録はすべて削除されます。",
        ],
    ],

    'text_from_liff' => [
        'error' => 'エラーが発生しました。時間を空けて再度お試しください。',
    ],

    'liff_channel_id' => env('LIFF_CHANNEL_ID'),

    'paginate_per_page' => 5,

    'liff_url' => env('LIFF_URL'),
    'frontend_domain' => env('FRONTEND_DOMAIN'),
];
