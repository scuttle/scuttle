<?php

use App\Domain;
use App\Page;
use App\Revision;
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
   Route::get('open-api/votes', 'PageController@jsonVotes');
    Route::get('pages', 'API\PageController@index');
    Route::get('{slug}/revision/{revision}', function(Domain $domain, $slug, $revision) {
        $page = Page::where('wiki_id', $domain->wiki->id)->where('slug', $slug)->orderBy('metadata->milestone','desc')->first();
        $thisrevision = Revision::where('page_id', $page->id)->where('metadata->wd_revision_id', intval($revision))->first();
        return app()->call('App\Http\Controllers\PageController@showrevision', ['revision' => $thisrevision, 'slug' => $slug]);
    });
    Route::get('{slug}/milestone/{milestone}', function(Domain $domain, $slug, $milestone) {
        $page = Page::where('wiki_id', $domain->wiki->id)->where('slug', $slug)->where('metadata->milestone', intval($milestone))->first();
        $thisrevision = Revision::where('page_id', $page->id)->orderBy('metadata->wd_revision_id','desc')->first();
        return app()->call('App\Http\Controllers\PageController@showrevision', ['revision' => $thisrevision, 'slug' => $slug]);
    });
    Route::get('{slug}/milestone/{milestone}/revision/{revision}', function(Domain $domain, $slug, $milestone, $revision) {
        $page = Page::where('wiki_id', $domain->wiki->id)->where('slug', $slug)->where('metadata->milestone', intval($milestone))->first();
        $milestonecount = Page::where('wiki_id', $domain->wiki->id)->where('slug', $slug)->count();
        $thisrevision = Revision::where('page_id', $page->id)->where('metadata->wd_revision_id', intval($revision))->first();
        return app()->call('App\Http\Controllers\PageController@showrevision', ['revision' => $thisrevision, 'slug' => $slug]);
    });
    // Route of last resort: Used for creating pages.
    // This will need validators to make sure they're valid slugs and not in reserved namespace.
   Route::fallback(function(Domain $domain) {
       $route = Route::current();
       $page = Page::where('wiki_id', $domain->wiki->id)->where('slug', $route->fallbackPlaceholder)->orderBy('metadata->milestone','desc')->first();

       if ($page == null) { return $domain->domain . '/' . $route->fallbackPlaceholder . ' doesn\'t exist. This will be a create page someday.'; }
       else return app()->call('App\Http\Controllers\PageController@show', ['page' => $page, 'slug' => $page->slug]);
   });
});