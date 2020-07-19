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
//app('debugbar')->disable();

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:api', 'throttle:10000,1')->group(function() {
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

        // 2stacks routes:
        Route::put('/2stacks/pages/manifest', 'API\PageController@put_2stacks_pages_manifest')->middleware('scope:write-metadata');
        Route::get('/pages/missing/metadata', 'API\PageController@get_pages_missing_metadata')->middleware('scope:read-metadata');
        Route::put('/2stacks/page/metadata', 'API\PageController@put_page_metadata')->middleware('scope:write-metadata');
        Route::put('/2stacks/page/revisions', 'API\RevisionController@put_page_revisions')->middleware('scope:write-revision');
        Route::put('/2stacks/revision/content', 'API\RevisionController@put_revision_content')->middleware('scope:write-revision');
        Route::put('/2stacks/user/metadata', 'API\WikidotUserController@put_wikidot_user_metadata')->middleware('scope:write-metadata');
        Route::put('/2stacks/page/thread', 'API\PageController@put_page_thread_id')->middleware('scope:write-metadata');
        Route::put('/2stacks/page/votes', 'API\PageController@put_page_votes')->middleware('scope:write-votes');
        Route::put('/2stacks/thread/posts', 'API\PostController@put_thread_posts')->middleware('scopes:write-post,write-thread');
        Route::put('/2stacks/page/files', 'API\PageController@put_page_files')->middleware('scope:write-file');
        Route::put('/2stacks/forum/metadata', 'API\ForumController@put_forum_metadata')->middleware('scopes:write-metadata');
        Route::put('/2stacks/scheduled/page/metadata', 'API\PageController@sched_pages_metadata')->middleware('scope:write-metadata');
        Route::put('/2stacks/forum/threads', 'API\ForumController@put_forum_threads')->middleware('scope:write-post');
        Route::delete('/2stacks/page/delete/{id}', 'API\PageController@delete_page')->middleware('scope:write-metadata');

        //API v1 routes:
        Route::prefix('v1')->group(function() {
            // Wiki Namespace
            Route::get('wiki', 'API\v1\WikiController@wiki_get_wiki')->middleware('scope:read-metadata');
            Route::get('wikis', 'API\v1\WikiController@wiki_get_wikis')->middleware('scope:read-metadata');

            // Page Namespace
            Route::get('page', 'API\v1\PageController@page_get_page')->middleware('scope:read-metadata');
            Route::get('page/since/{timestamp}', 'API\v1\PageController@page_get_page_since_TIMESTAMP')->middleware('scope:read-metadata');
            Route::get('page/since/id/{id}', 'API\v1\PageController@page_get_page_since_id_ID')->middleware('scope:read-metadata');
            Route::post('page/since/{timestamp}', 'API\v1\PageController@page_post_page_since_TIMESTAMP')->middleware('scope:read-metadata');
            Route::post('page/since/id/{id}', 'API\v1\PageController@page_post_page_since_id_ID')->middleware('scope:read-metadata');
            Route::get('page/{id}', 'API\v1\PageController@page_get_page_ID')->middleware('scope:read-article');
            Route::get('page/slug/{slug}', 'API\v1\PageController@page_get_page_slug_SLUG')->where(['slug' => '[a-z0-9-:_]{1,60}'])->middleware('scope:read-article');
            Route::get('page/{id}/revisions', 'API\v1\PageController@page_get_page_ID_revisions')->middleware('scope:read-metadata');
            Route::post('page/{id}/revisions', 'API\v1\PageController@page_post_page_ID_revisions')->middleware('scope:read-revision');
            Route::get('page/{id}/votes', 'API\v1\PageController@page_get_page_ID_votes')->middleware('scope:read-metadata');
            Route::get('page/{id}/tags', 'API\v1\PageController@page_get_page_ID_tags')->middleware('scope:read-metadata');
            Route::get('page/{id}/files', 'API\v1\PageController@page_get_page_ID_files')->middleware('scope:read-file');
            Route::get('page/{id}/latestsource', 'API\v1\PageController@page_get_page_ID_latestsource')->middleware('scope:read-revision');

            // Revision Namespace
            Route::get('revision/{id}', 'API\v1\RevisionController@revision_get_revision_ID')->middleware('scope:read-revision');
            Route::get('revision/{id}/full', 'API\v1\RevisionController@revision_get_revision_ID_full')->middleware('scope:read-revision');

            // Forum Namespace
            Route::get('forum', 'API\v1\ForumController@forum_get_forum')->middleware('scope:read-metadata');
            Route::get('forum/{id}', 'API\v1\ForumController@forum_get_forum_ID')->middleware('scope:read-metadata');
            Route::get('forum/{id}/threads', 'API\v1\ForumController@forum_get_forum_ID_threads')->middleware('scope:read-thread');
            Route::post('forum/{id}/since/{timestamp}', 'API\v1\ForumController@forum_post_forum_ID_since_TIMESTAMP')->middleware('scope:read-thread');

            // Thread Namespace
            Route::get('thread/{id}', 'API\v1\ThreadController@thread_get_thread_ID')->middleware('scope:read-thread');
            Route::get('thread/{id}/posts', 'API\v1\ThreadController@thread_get_thread_ID_posts')->middleware('scope:read-thread');
            Route::post('thread/{id}/posts', 'API\v1\ThreadController@thread_post_thread_ID_posts')->middleware('scope:read-post');
            Route::post('thread/{id}/since/{timestamp}', 'API\v1\ThreadController@thread_post_thread_ID_since_TIMESTAMP')->middleware('scope:read-post');

            // Post Namespace
            Route::get('post/{id}', 'API\v1\PostController@post_get_post_ID')->middleware('scope:read-post');
            Route::get('post/{id}/children', 'API\v1\PostController@post_get_post_ID_children')->middleware('scope:read-post');
            Route::get('post/{id}/parent', 'API\v1\PostController@post_get_post_ID_parent')->middleware('scope:read-post');

            // Wikidot User Namespace
            Route::get('wikidotuser/{id}', 'API\v1\WikidotUserController@wikidotuser_get_wikidotuser_ID')->middleware('scope:read-metadata');
            Route::get('wikidotuser/username/{username}', 'API\v1\WikidotUserController@wikidotuser_get_wikidotuser_username_USERNAME')->middleware('scope:read-metadata');
            Route::get('wikidotuser/{id}/avatar', 'API\v1\WikidotUserController@wikidotuser_get_wikidotuser_ID_avatar')->middleware('scope:read-metadata');
            Route::post('wikidotuser/{id}/pages', 'API\v1\WikidotUserController@wikidotuser_post_wikidotuser_ID_pages')->middleware('scope:read-metadata');
            Route::get('wikidotuser/{id}/pages', 'API\v1\WikidotUserController@wikidotuser_get_wikidotuser_ID_pages')->middleware('scope:read-metadata');
            Route::post('wikidotuser/{id}/posts', 'API\v1\WikidotUserController@wikidotuser_post_wikidotuser_ID_posts')->middleware('scope:read-post');
            Route::get('wikidotuser/{id}/posts', 'API\v1\WikidotUserController@wikidotuser_get_wikidotuser_ID_posts')->middleware('scope:read-post');
            Route::post('wikidotuser/{id}/revisions', 'API\v1\WikidotUserController@wikidotuser_post_wikidotuser_ID_revisions')->middleware('scope:read-revision');
            Route::get('wikidotuser/{id}/revisions', 'API\v1\WikidotUserController@wikidotuser_get_wikidotuser_ID_revisions')->middleware('scope:read-revision');
            Route::get('wikidotuser/{id}/votes', 'API\v1\WikidotUserController@wikidotuser_get_wikidotuser_ID_votes')->middleware('scope:read-revision');

            // Tag Namespace
            Route::get('tag', 'API\v1\TagController@tag_get_tag')->middleware('scope:read-metadata');
            Route::get('tag/{name}/pages', 'API\v1\TagController@tag_get_tag_NAME_pages')->middleware('scope:read-metadata');
            Route::post('tag/pages', 'API\v1\TagController@tag_post_tag_pages')->middleware('scope:read-metadata');
        });
    });
});
