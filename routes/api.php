<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LineBotController;
use App\Http\Controllers\Api\Liff\OpponentController;
use App\Http\Controllers\Api\Liff\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/line/callback', [LineBotController::class, 'callback'])->name('line.callback');

Route::group([
    'prefix' => 'liff',
    'as' => 'liff.',
    'middleware' => ['auth.liff'],
], function () {
    Route::group([
        'prefix' => 'opponents',
        'as' => 'opponent.',
    ], function () {
        Route::get('/', [OpponentController::class, 'index'])->name('index');
    });

    Route::group([
        'prefix' => 'transactions',
        'as' => 'transaction.',
    ], function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::post('/', [TransactionController::class, 'store'])->name('store');
        Route::put('/{id}', [TransactionController::class, 'update'])->name('update');
        Route::delete('/{id}', [TransactionController::class, 'delete'])->name('delete');
    });
});
