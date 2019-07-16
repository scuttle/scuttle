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

Route::middleware('auth:api', 'throttle:480,1')->group(function() {
    Route::domain('{domain}')->group(function() {
        Route::get('pages', 'API\PageController@index');
        Route::put('wikidot', 'API\PageController@wdstore');
        Route::get('/wikidot/metadata', 'API\PageController@getwikidotmetadata');
        Route::put('/wikidot/metadata', 'API\PageController@putwikidotmetadata');
        Route::get('revisions', 'API\PageController@revisions');
        Route::get('scrape/revisions/manifest', 'API\PageController@getscrapemanifest');
        Route::put('/scrape/revisions', 'API\PageController@putscraperevision');
        Route::put('/scrape/complete', 'API\PageController@recalculatediffs');
        Route::put('/pages/wikidotids', 'API\PageController@putwikidotids');
    });
});