<?php

use App\Http\Controllers\Liff\OpponentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// LIFFアプリケーションURL
Route::group([
    'prefix' => 'liff',
    'as' => 'liff.',
], function () {
    Route::group([
        'prefix' => 'opponent',
        'as' => 'opponent.',
    ], function () {
        Route::get('/create-screen', [OpponentController::class, 'showCreateScreen'])->name('createScreen');
        Route::post('/store', [OpponentController::class, 'store'])->name('store');
        Route::get('/edit-screen', [OpponentController::class, 'showEditScreen'])->name('editScreen');
        Route::post('/update', [OpponentController::class, 'update'])->name('update');
    });
});
