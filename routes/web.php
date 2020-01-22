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
        $page = Page::withTrashed()->where('wiki_id', $domain->wiki->id)->where('slug', $slug)->orderBy('milestone','desc')->first();
        $thisrevision = Revision::where('page_id', $page->id)->where('metadata->wikidot_metadata->revision_number', intval($revision))->first();
        return app()->call('App\Http\Controllers\PageController@showrevision', ['revision' => $thisrevision, 'page' => $page]);
    });
    Route::get('{slug}/milestone/{milestone}', function(Domain $domain, $slug, $milestone) {
        $page = Page::withTrashed()->where('wiki_id', $domain->wiki->id)->where('slug', $slug)->where('milestone', intval($milestone))->first();
        $thisrevision = Revision::where('page_id', $page->id)->orderBy('metadata->wikidot_metadata->revision_number','desc')->first();
        return app()->call('App\Http\Controllers\PageController@showrevision', ['revision' => $thisrevision, 'page' => $page]);
    });
    Route::get('{slug}/milestone/{milestone}/revision/{revision}', function(Domain $domain, $slug, $milestone, $revision) {
        $page = Page::withTrashed()->where('wiki_id', $domain->wiki->id)->where('slug', $slug)->where('milestone', intval($milestone))->first();
        $thisrevision = Revision::where('page_id', $page->id)->where('metadata->wikidot_metadata->revision_number', intval($revision))->first();
        return app()->call('App\Http\Controllers\PageController@showrevision', ['revision' => $thisrevision, 'page' => $page]);
    });

    // New Open API Routes
    // Temporary route for user search. This will be replaced by the v1 api.
    Route::get('open-api/user/{username}', function(Domain $domain, $username) {
       $users = DB::table('wikidot_users')->where('username', $username)->get();
        return response($users, '200', ['Content-Type' => 'application/json']);
    });

    // Temporary route for contest stuff.
    Route::get('open-api/slug/{slug}', function(Domain $domain, $slug) {
        $pages = DB::table('pages')->where('wiki_id', $domain->wiki->id)->where('slug', $slug)->get();
        $output = "Found ". $pages->count() . " pages.<br><hr>";
        foreach ($pages as $page) {
            $output .= "Page ID: " . $page->id . " (Wikidot Page ID: " . $page->wd_page_id . ")<br>";
            $output .= "Created By: " . json_decode($page->metadata)->wikidot_metadata->created_by;
            $output .= "<br>Created At: " . $page->created_at . ", Deleted At: " . $page->deleted_at;
            $output .= "<br><hr>";
        }
    return $output;
    });
    Route::get('open-api/slug/{slug}/all', function(Domain $domain, $slug) {
        $pages = DB::table('pages')->where('wiki_id', $domain->wiki->id)->where('slug', $slug)->get();
        $output = "Found ". $pages->count() . " pages.<br><hr>";
        echo $output;
        foreach ($pages as $page) {
            print_r($page);
            echo "<br><hr><br>";
        }
        return "Done.";
    });

    Route::get('open-api/thread/{needle}', function(Domain $domain, $needle) {
        // This is a proof-of-concept, not a permanent endpoint. Restrict it thus.
        if($domain->wiki_id == 4) {
            $forums = [55,56,59]; // Disc, Non-Disc, and Chat Users respectively.
            $payload = DB::table('threads')->select('title','subtitle','wd_thread_id','forum_id')
                ->whereIn('forum_id',$forums)->where('title','LIKE','%'.$needle.'%')
                ->orderBy('wd_thread_id','DESC')->limit(10)
                ->get()->toJson();
            return response($payload, '200', ['Content-Type' => 'application/json']);
        }
        else abort(401);
    });

    Route::get('open-api/tag/{tag}', function(Domain $domain, $tag) {
        $taggedpages = DB::table('pages')->where('wiki_id', $domain->wiki->id)->whereJsonContains('metadata->wikidot_metadata->tags', $tag)->get();
        $results = [];
        foreach($taggedpages as $taggedpage) {
            $results[$taggedpage->slug] = json_decode($taggedpage->metadata)->wikidot_metadata->created_by;
        }
        $uniques = array_unique($results);
        $output = "Found " . $taggedpages->count() . " pages by " . count($uniques) . " authors.<br><br>" . str_replace('=',':', http_build_query($results, null, '<br>'));
        return response($output);
    });
    Route::get('open-api/search/{search}', function(Domain $domain, $search) {
       $terms = explode(' ', $search);
       $results = DB::table('pages')->where('wiki_id', $domain->wiki_id)->where(function($results) use ($terms) {
               foreach($terms as $term) {
                   $term = '"%'.$term.'%"';
                   $results->whereRaw('metadata->"$.wikidot_metadata.title" COLLATE UTF8MB4_UNICODE_CI LIKE ?', [$term]);
               }
           })->limit(10)->get();

       $output = array();
       $output["count"] = $results->count();
       $output["titles"] = array();
       $output["results"] = array();
       foreach($results as $result) {
            $metadata = json_decode($result->metadata, true);
            $title = $metadata["wikidot_metadata"]["title"];
            $output["titles"][] = $title;
            $output["results"][$title]["page_id"] = $result->wd_page_id;
            $output["results"][$title]["slug"] = $result->slug;
            $output["results"][$title]["rating"] = $metadata["wikidot_metadata"]["rating"];
            $output["results"][$title]["author"] = $metadata["wikidot_metadata"]["created_by"];
            $output["results"][$title]["tags"] = $metadata["wikidot_metadata"]["tags"];
       }
       return response($output);
    });

    // Route of last resort: Used for creating pages.
    // This will need validators to make sure they're valid slugs and not in reserved namespace.
   Route::fallback(function(Domain $domain) {
       $route = Route::current();
       $page = Page::where('wiki_id', $domain->wiki->id)->where('slug', $route->fallbackPlaceholder)->orderBy('milestone','desc')->first();

       if ($page == null) { return $domain->domain . '/' . $route->fallbackPlaceholder . ' doesn\'t exist. This will be a create page someday.'; }
       else return app()->call('App\Http\Controllers\PageController@show', ['page' => $page]);
   });
});
