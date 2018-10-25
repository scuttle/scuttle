<?php

use App\Domain;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::view('/', 'welcome');

Auth::routes();

Route::get('/main', 'HomeController@index')->name('main');

// We're using Apache and Nginx rules to force any requests for the root domain to www.
// Thus, every request should fall in this route group.
Route::domain('{domain}')->group(function () {
   Route::get('test', 'TestController@show');
    Route::get('pages', 'API\PageController@index');
    // Route of last resort: Used for creating pages.
    // This will need validators to make sure they're valid slugs and not in reserved namespace.
   Route::fallback(function($domain) {
       $route = Route::current();
       return $domain . '/' . $route->fallbackPlaceholder;
   });
});