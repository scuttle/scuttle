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
        $page = Page::latest($domain->wiki_id, $slug);
        $thisrevision = Revision::where('page_id', $page->id)->where('metadata->wikidot_metadata->revision_number', intval($revision))->first();
        return app()->call('App\Http\Controllers\PageController@showrevision', ['revision' => $thisrevision, 'page' => $page]);
    });
    Route::get('{slug}/milestone/{milestone}', function(Domain $domain, $slug, $milestone) {
        $page = Page::find_by_milestone($domain->wiki_id,$slug,intval($milestone));
        $thisrevision = Revision::where('page_id', $page->id)->orderBy('metadata->wikidot_metadata->revision_number','desc')->first();
        if($thisrevision == null) {
            return app()->call('App\Http\Controllers\PageController@pagemissing', ['page' => $page]);
        }
        return app()->call('App\Http\Controllers\PageController@showrevision', ['revision' => $thisrevision, 'page' => $page]);
    });
    Route::get('{slug}/milestone/{milestone}/revision/{revision}', function(Domain $domain, $slug, $milestone, $revision) {
        $page = Page::find_by_milestone($domain->wiki_id,$slug,intval($milestone));
        $thisrevision = Revision::where('page_id', $page->id)->where('metadata->wikidot_metadata->revision_number', intval($revision))->first();
        return app()->call('App\Http\Controllers\PageController@showrevision', ['revision' => $thisrevision, 'page' => $page]);
    });
    Route::get('{slug}/vs/{slug2}', function(Domain $domain, $slug, $slug2) {
        $page1 = Page::latest($domain->wiki_id, $slug)->latest_source();
        $page2 = Page::latest($domain->wiki_id, $slug2)->latest_source();
        if(!$page1 or !$page2) { abort(404); }
        $page1 = str_replace('<br />','',$page1);
        $page2 = str_replace('<br />','',$page2);
        return app()->call('App\Http\Controllers\PageController@diff_basic', ['page1' => $page1, 'page2' => $page2]);
    });
    Route::get('{slug1}/{milestone1}/{revision1}/vs/{slug2}/{milestone2}/{revision2}', function(Domain $domain, $slug1, $milestone1, $revision1, $slug2, $milestone2, $revision2) {
        $m1 = \App\Milestone::where('wiki_id',$domain->wiki_id)->where('slug',$slug1)->where('milestone',$milestone1)->first();
        $m2 = \App\Milestone::where('wiki_id',$domain->wiki_id)->where('slug',$slug2)->where('milestone',$milestone2)->first();
        if(!$m1 or !$m2) { abort(404); }
        $r1 = Revision::where('page_id',$m1->page_id)->where('metadata->wikidot_metadata->revision_number', intval($revision1))->first();
        $r2 = Revision::where('page_id',$m2->page_id)->where('metadata->wikidot_metadata->revision_number', intval($revision2))->first();
        if(!$r1 or !$r2) { abort(404); }
        $page1 = str_replace('<br />','',$r1->content);
        $page2 = str_replace('<br />','',$r2->content);
        return app()->call('App\Http\Controllers\PageController@diff_basic', ['page1' => $page1, 'page2' => $page2]);
    });

    // New Open API Routes

    // Utility API Routes
    Route::get('open-api/ip', function ()  { return response($_SERVER['REMOTE_ADDR'], 200, ['Content-Type' => 'text/plain']); });

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

            // Look for an exact match first.
            $payload = DB::table('threads')->select('title','subtitle','wd_thread_id','forum_id')
                ->whereIn('forum_id',$forums)->where('title','ILIKE','%'.$needle.'%')
                ->orderBy('wd_thread_id','DESC')->limit(10)
                ->get();
            if ($payload->count() == 1) {
                return response($payload->toJson(), '200', ['Content-Type' => 'application/json']);
            }

            // Otherwise...
            $payload = DB::table('threads')->select('title','subtitle','wd_thread_id','forum_id')
                ->whereIn('forum_id',$forums)->where('title','ILIKE','%'.$needle.'%')
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

    // Frontend Routes
    Route::get('user/{username}', function(Domain $domain, $username) {
       $user = \App\WikidotUser::where('username', $username)->first() ?? abort(404);
       $pages = $user->pages()->withTrashed()->where('wiki_id',$domain->wiki_id)->latest()->limit(10)->with('milestones')->get();
       $revisions = $user->revisions()->where('wiki_id',$domain->wiki_id)->latest()->with('page.milestones')->limit(10)->get();
       $votes = $user->votes()->withTrashed()->where('wiki_id', $domain->wiki_id)->latest()->with('page.milestones')->limit(10)->get();
       foreach($pages as $page) {
           $page->metadata = json_decode($page->metadata, true);
       }
       foreach ($revisions as $revision) {
           $revision->metadata = json_decode($revision->metadata, true);
           $revision->page_metadata = json_decode($revision->page->metadata, true);
       }
       foreach ($votes as $vote) {
           $vote->page->metadata = json_decode($vote->page->metadata, true);
       }
       return view('wikidotuser.show', compact(['user', 'pages', 'votes', 'revisions']));
    });

    Route::get('user/{username}/votes/{page?}', function(Domain $domain, $username, int $page = 1) {
        $offset = ($page - 1) * 100;
        $user = \App\WikidotUser::where('username', $username)->first() ?? abort(404);
        $votes = $user->votes()->withTrashed()->where('wiki_id', $domain->wiki_id)->latest()->with('page.milestones')->with('page:id,metadata,slug')->limit(100)->offset($offset)->get();

        foreach ($votes as $vote) {
            $vote->page->milestone = $vote->page->milestones[0]->milestone;
            $vote->page->metadata = json_decode($vote->page->metadata, true);
        }
        return view('wikidotuser.votes', compact(['user', 'votes', 'page']));
    });
    // Route of last resort: Used for creating pages.
    // This will need validators to make sure they're valid slugs and not in reserved namespace.
   Route::fallback(function(Domain $domain) {
       $route = Route::current();
       $page = Page::latest($domain->wiki_id, $route->fallbackPlaceholder);

       if ($page == null) { return app()->call('App\Http\Controllers\PageController@notfound', ['slug' => $route->fallbackPlaceholder, 'domain' => $domain]); }
       else return app()->call('App\Http\Controllers\PageController@show', ['page' => $page]);
   });
});
