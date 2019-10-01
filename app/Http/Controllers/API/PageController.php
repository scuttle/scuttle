<?php

namespace App\Http\Controllers\API;

use App\Domain;
use App\Jobs\PushPageId;
use App\Page;
use App\Revision;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Jobs\PushPageSlug;

class PageController extends Controller
{
    public function put_2stacks_pages_manifest(Domain $domain, Request $request)
    {
        if(Gate::allows('write-programmatically')) {
            $reportedpages = $request->toArray(); // Wikidot's list of pages as provided by 2stacks/lambda.
            $scuttlepages = DB::table('pages')
                ->select('slug')
                ->where('wiki_id',$domain->wiki->id)
                ->pluck('slug')->toArray(); // Our list of pages which will include some deleted slugs.

            // This is an alternative to array_diff that works about 1400x faster.
            // It will find a single mismatch in a pair of 11,000+ item lists in under 2 seconds on a single core.
            // https://stackoverflow.com/a/6700430/3946227
            function leo_array_diff($a, $b) {
                $map = array();
                foreach($a as $val) $map[$val] = 1;
                foreach($b as $val) unset($map[$val]);
                return array_keys($map);
            }

            $unaccountedpages = leo_array_diff($reportedpages, $scuttlepages);

            if(empty($scuttlepages)) {
                // We're working with an empty set, either because of a rollback or because we're tracking a new wiki for the first time.
                $unaccountedpages = $reportedpages;
            }

            // Let's stub out the page and note that we need metadata for the page.
            foreach ($unaccountedpages as $item) {
                $page = new Page([
                    'wiki_id' => $domain->wiki->id,
                    'user_id' => auth()->id(),
                    'slug' => $item,
                    'milestone' => 0,
                    'metadata' => json_encode(
                        array(
                            'page_missing_metadata' => true
                        )
                    ),
                    'JsonTimestamp' => Carbon::now()
                ]);
                $page->save();
                // Send an SQS message for 2stacks-lambda to work on.
                PushPageSlug::dispatch($page->slug)->onQueue('scuttle-pages-missing-metadata');
            }
            return response(json_encode($unaccountedpages)); // give the submitter a note of which ones were new.
        }
    }

    public function put_page_metadata(Domain $domain, Request $request)
    {
        if(Gate::allows('write-programmatically')) {
            $p = Page::where('wiki_id', $domain->wiki->id)
                ->where('slug', $request["slug"])
                ->orderBy('milestone', 'desc')
                ->get();
            if($p->isEmpty()) {
                // Well this is awkward.
                // 2stacks just sent us metadata about a slug we don't have.
                // Summon the troops.
                Log::error('2stacks sent us metadata about ' . $request->slug . ' for wiki ' . $domain->wiki->id . ' but SCUTTLE doesn\'t have a matching slug!');
                Log::error('$request: ' . $request);
                return response('I don\'t have a slug to attach that metadata to!', 500)
                    ->header('Content-Type', 'text/plain');
            }
            else {
                $page = $p->first();
                $timestamp = Carbon::parse($request["wikidot_metadata"]["created_at"])->timestamp;
                $oldmetadata = json_decode($page->metadata, true);
                if(isset($oldmetadata["page_missing_metadata"]) && $oldmetadata["page_missing_metadata"] == true) {
                    // This is the default use case, responding to the initial SQS message on a new page arriving.
                    // SQS queues can send a message more than once so we need to make sure we're handling all possibilities.
                    $page->wd_page_id = $request["wd_page_id"];
                    $page->latest_revision = $request["latest_revision"];
                    $page->metadata = json_encode(array(
                        // We're overwriting the old metadata entirely as the only thing it had was "needs metadata".
                        'wikidot_metadata' => $request["wikidot_metadata"],
                        // We only include these now instead of on initial write because we need the page_id to fire the event.
                        'page_missing_votes' => true,
                        'page_missing_files' => true,
                        'page_missing_revisions' => true,
                        'page_missing_comments' => true,
                        'wd_page_created_at' => $timestamp
                    ));
                    $page->jsonTimestamp = Carbon::now(); // touch on update
                    $page->save();
                    // Go notify the other workers.
                    PushPageId::dispatch($page->wd_page_id)->onQueue('scuttle-pages-missing-revisions');
//                    PushPageId::dispatch($page->wd_page_id)->onQueue('scuttle-pages-missing-comments');
//                    PushPageId::dispatch($page->wd_page_id)->onQueue('scuttle-pages-missing-files');
//                    PushPageId::dispatch($page->wd_page_id)->onQueue('scuttle-pages-missing-votes');
                    return response('saved');
                }
                else { return response('had that one already'); }
            }
        }
    }

    public function get_pages_missing_metadata(Domain $domain)
    {
        $pages = DB::table('pages')
            ->select('slug')
            ->where('wiki_id',$domain->wiki->id)
            ->whereRaw('JSON_CONTAINS_PATH(metadata, "one", "$.page_missing_metadata")')
            ->pluck('slug');
        return response($pages->toJson());
    }

}
