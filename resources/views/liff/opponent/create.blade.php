<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        @vite('resources/css/app.css')
    </head>
    <body class="bg-gray-100 flex items-center justify-center h-screen">
        <div class="max-w-xs w-full m-auto bg-white rounded-lg border border-primaryBorder shadow-default py-10 px-8">
            <h1 class="text-xl font-bold text-primary mt-4 mb-8 text-center">
                新規作成
            </h1>
    
            <form action="">
                <div>
                    <label for="username" class="text-left block pb-2">名前</label>
                    <input 
                        type="text" 
                        class="w-full p-2 text-primary border rounded-md outline-none text-sm transition duration-150 ease-in-out mb-4" 
                        id="username" 
                        placeholder="相手の名前を入力"
                    />
                </div>
    
                <div class="flex justify-center items-center mt-6">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        作成
                    </button>
                </div>
            </form>
        </div>
    </body>
</html>
