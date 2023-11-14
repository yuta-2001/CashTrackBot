<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta http-equiv="Cache-Control" content="no-cache">

        <title>貸借り管理BOT | 相手編集</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
    
            div {
                background-color: white;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                width: 85%;
                margin: 0 auto;
                max-width: 400px;
            }
    
            h1 {
                font-size: 24px;
                color: #333;
                text-align: center;
            }
    
            label {
                display: block;
                margin-bottom: 5px;
                font-size: 18px;
            }
    
            input[type="text"] {
                display: block;
                width: 100%;
                padding: 10px;
                box-sizing:border-box;
                margin-bottom: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
                font-size: 16px;
            }
    
            button {
                width: 100%;
                padding: 10px;
                background-color: #5cb85c;
                color: white;
                border: none;
                border-radius: 5px;
                font-size: 18px;
                cursor: pointer;
            }
    
            button:hover {
                background-color: #4cae4c;
            }
        </style>
    </head>

    <body>
        <div>
            <h1>
                編集
            </h1>
    
            <form id="edit-opponent">
                <label for="name">名前</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name"
                    placeholder="相手の名前を入力"
                    value="{{ $opponent->name }}"
                    required
                />
                <button type="submit">
                    編集
                </button>
            </form>
        </div>

        <input type="hidden" id="liff_id" value="{{ config('line.liff_ids.opponent_edit') }}">
        <input type="hidden" id="endpoint" value="{{ route('liff.opponent.update') }}">
        <input type="hidden" id="opponent_id" value="{{ $opponent->id }}">

        <script charset="utf-8" src="https://static.line-scdn.net/liff/edge/versions/2.22.3/sdk.js"></script>
        <script type="text/javascript">
            const opponentId = document.getElementById('opponent_id').value;
            const endpoint = document.getElementById('endpoint').value;
            const liffId = document.getElementById('liff_id').value;

            let liffToken = '';
            let accessToken = '';

            document.addEventListener("DOMContentLoaded", function() {
                liff.init({ liffId: liffId })
                    .then(() => {
                        console.log("Success! you can do something with LIFF API here.")

                        // 本番環境はこちらを使用
                        if (!liff.isInClient() || !liff.getFriendship()) {
                            liff.closeWindow();
                        }

                        // ブラウザでの動作確認時にのみ使用
                        // if (!liff.isLoggedIn()) {
                        //     liff.login();
                        // }
                    })
                    .then(() => {
                        const params = (new URL(document.location)).searchParams;
                        liffToken = params.get('liff_token');
                        accessToken = liff.getAccessToken();
                    })
                    .catch((error) => {
                        console.log(error)
                    })
            });

            document.getElementById('edit-opponent').addEventListener('submit', function(e) {
                e.preventDefault();
                const name = document.getElementById('name').value;

                const requestOptions = {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        name: name,
                        opponent_id: opponentId,
                        liff_token: liffToken,
                        access_token: accessToken
                    })
                };

                fetch(endpoint, requestOptions)
                    .then(response => {
                        if (response.status === 200) {
                            replyText = '相手の編集が完了しました！';
                        } else {
                            replyText = '相手の編集に失敗しました。';
                        }

                        liff.sendMessages([
                                {
                                    type: 'text',
                                    text: replyText
                                }
                            ])
                            .then(() => {
                                liff.closeWindow();
                            })
                            .catch((err) => {
                                console.log('error', err);
                                liff.closeWindow();
                            });
                    })
            });
        </script>
    </body>
</html>
