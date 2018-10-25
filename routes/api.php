<?php

use Illuminate\Http\Request;
use App\Wiki;
use App\Domain;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
app('debugbar')->disable();

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:api', 'throttle:60,1')->group(function() {
    Route::domain('{domain}')->group(function() {
        Route::get('pages', 'API\PageController@index');
        Route::put('wikidot', 'API\PageController@wdstore');
    });
});