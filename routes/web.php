<?php

use App\Http\Controllers\LineLiffController;
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
    Route::get('/opponent/create-screen', [LineLiffController::class, 'showOpponentCreateScreen'])->name('opponent.createScreen');
    Route::post('/opponent/store', [LineLiffController::class, 'createOpponent'])->name('opponent.store');
    Route::get('/opponent/edit-screen', [LineLiffController::class, 'showOpponentEditScreen'])->name('opponent.editScreen');
    Route::post('/opponent/update', [LineLiffController::class, 'updateOpponent'])->name('opponent.update');
});
