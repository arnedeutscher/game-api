<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\FavoriteUserGamesController;

use App\Http\Middleware\SearchGameRequestLimiter;
use App\Http\Middleware\FilterGamesRequestLimiter;
use App\Http\Middleware\RetrieveGameDetailsRequestLimiter;

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

Route::controller(UserController::class)->group(function () {
    Route::post('/login', 'login')->name('login');
	Route::get('/user', 'user_details')->middleware('auth:api');
});

Route::controller(GameController::class)->group(function () {
    Route::get('/games/search', 'search')->middleware(SearchGameRequestLimiter::class);
	Route::get('/games/filter', 'filter')->middleware(FilterGamesRequestLimiter::class);
	Route::get('/games/{game_id}', 'retrieve_details')->middleware(RetrieveGameDetailsRequestLimiter::class);
});

Route::controller(FavoriteUserGamesController::class)->group(function () {
	Route::get('/user/games/favorites', 'get');
	Route::post('/user/games/favorites', 'store');
	Route::delete('/user/games/favorites', 'destroy');
})->middleware('auth:api');