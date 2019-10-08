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
        Route::get('/pages/get/wikidotid', 'API\PageController@getwikidotids');
        Route::get('/pages/get/wikidotid/last', 'API\PageController@lastwikidotid');

        // New routes:
        Route::put('/2stacks/pages/manifest', 'API\PageController@put_2stacks_pages_manifest')->middleware('scope:write-metadata');
        Route::get('/pages/missing/metadata', 'API\PageController@get_pages_missing_metadata')->middleware('scope:read-metadata');
        Route::put('/2stacks/page/metadata', 'API\PageController@put_page_metadata')->middleware('scope:write-metadata');
        Route::put('/2stacks/page/revisions', 'API\RevisionController@put_page_revisions')->middleware('scope:write-revision');
        Route::put('/2stacks/revision/content', 'API\RevisionController@put_revision_content')->middleware('scope:write-revision');
        Route::put('/2stacks/user/metadata', 'API\WikidotUserController@put_wikidot_user_metadata')->middleware('scope:write-metadata');
        Route::put('/2stacks/page/thread', 'API\PageController@put_page_thread_id')->middleware('scope:write-metadata');
        Route::put('/2stacks/page/votes', 'API\PageController@put_page_votes')->middleware('scope:write-votes');
        Route::put('/2stacks/thread/posts', 'API\PostController@put_thread_posts')->middleware('scopes:write-post,write-thread');
    });
});
