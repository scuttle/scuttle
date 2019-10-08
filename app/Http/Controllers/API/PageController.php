<?php

namespace App\Http\Controllers\API;

use App\Domain;
use App\File;
use App\Jobs\PushPageId;
use App\Jobs\PushThreadId;
use App\Jobs\PushWikidotUserId;
use App\Page;
use App\Revision;
use App\Thread;
use App\Vote;
use App\WikidotUser;
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
                    PushPageId::dispatch($page->wd_page_id)->onQueue('scuttle-pages-missing-thread-id');
                    PushPageId::dispatch($page->wd_page_id)->onQueue('scuttle-pages-missing-files');
                    PushPageId::dispatch($page->wd_page_id)->onQueue('scuttle-pages-missing-votes');
                    return response('saved');
                }
                else { return response('had that one already'); }
            }
        }
    }

    public function put_page_votes(Domain $domain, Request $request)
    {
        if(Gate::allows('write-programmatically')) {
            $p = Page::where('wiki_id', $domain->wiki->id)
                ->where('wd_page_id', $request["wd_page_id"])
                ->get();
            if($p->isEmpty()) {
                // Well this is awkward.
                // 2stacks just sent us metadata about a slug we don't have.
                // Summon the troops.
                Log::error('2stacks sent us votes on ' . $request->slug . ' for wiki ' . $domain->wiki->id . ' but SCUTTLE doesn\'t have a matching slug!');
                Log::error('$request: ' . $request);
                return response('I don\'t have a page to attach those votes to!', 500)
                    ->header('Content-Type', 'text/plain');
            }
            else {
                $page = $p->first();
                $oldmetadata = json_decode($page->metadata, true);
                if(isset($oldmetadata["page_missing_votes"]) && $oldmetadata["page_missing_votes"] == true) {
                    // This is the default use case, responding to the initial SQS message on a new page arriving.
                    // SQS queues can send a message more than once so we need to make sure we're handling all possibilities.
                    foreach($request["votes"] as $vote) {
                        // A vote can exist in (currently) one of four status codes.
                        // Active, old (a vote that flipped in the past), deleted (user account is gone), or banned (votes fall off).
                        // Since we're running under the "page_missing_votes" routine we don't need to worry about that yet.
                        $v = new Vote([
                            'page_id' => $page->id,
                            'user_id' => auth()->id(),
                            'wd_user_id' => $vote["user_id"],
                            'wd_vote_ts' => Carbon::now(),
                            'metadata' => json_encode(array('status' => 'active')),
                            'JsonTimestamp' => Carbon::now()
                        ]);
                        if($vote["vote"] == "+"){
                            $v->vote = 1;
                        }
                        else if($vote["vote"] == "-") {
                            $v->vote = -1;
                        }
                        else {$v->vote = 0;}

                        $v->save();

                        // Let's see if we've seen this user before.
                        $u = WikidotUser::where('wd_user_id', $vote["user_id"])->get();
                        if($u->isEmpty()) {
                            // We haven't seen this ID before, store what we know and queue a job for the rest.
                            $wu = new WikidotUser([
                                'wd_user_id' => $vote["user_id"],
                                'username' => $vote["username"],
                                'metadata' => json_encode(array(
                                    'user_missing_metadata' => true,
                                )),
                                'JsonTimestamp' => Carbon::now()
                            ]);
                            $wu->save();
                            PushWikidotUserId::dispatch($vote["user_id"])->onQueue('scuttle-users-missing-metadata');
                        }
                    }
                    unset($oldmetadata["page_missing_votes"]);
                    $page->metadata = json_encode($oldmetadata);
                    $page->jsonTimestamp = Carbon::now(); // touch on update
                    $page->save();
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

    public function put_page_thread_id(Domain $domain, Request $request)
    {
        if(Gate::allows('write-programmatically')) {
            $p = Page::where('wd_page_id', $request["wd_page_id"])->get();
            if($p->isEmpty()) {
                // Well this is awkward.
                // 2stacks just sent us metadata about a page we don't have.
                // Summon the troops.
                Log::error('2stacks sent us a thread id for ' . $request["wd_page_id"]. ' for wiki ' . $domain->wiki->id . ' but SCUTTLE doesn\'t have a matching page!');
                Log::error('$request: ' . $request);
                return response('I don\'t have a page to attach this data to!', 500)
                    ->header('Content-Type', 'text/plain');
            }
            else {
                $page = $p->first();
                $metadata = json_decode($page->metadata, true);
                if(isset($metadata["page_missing_comments"]) && $metadata["page_missing_comments"] == true) {
                    if(isset($metadata["wd_thread_id"])) {
                        // We already had the thread ID, let's requeue the job to extract comments.
                        PushThreadId::dispatch($metadata["wd_thread_id"])->onQueue('scuttle-threads-missing-comments');
                        return response('had that one already');
                    }
                    else {
                        // This is our expected condition for having this method run.

                        // Attach the thread to page metadata.
                        $metadata["wd_thread_id"] = $request["wd_thread_id"];

                        // Stub out the thread with the wd_thread_id.
                        $thread = new Thread;
                        $thread->wd_thread_id = $request["wd_thread_id"];
                        $thread->user_id = auth()->id();
                        $thread->metadata = json_encode(array("thread_missing_posts" => true));
                        $thread->JsonTimestamp = Carbon::now();
                        $thread->save();

                        // Queue the job to get comments.
                        PushThreadId::dispatch($metadata["wd_thread_id"])->onQueue('scuttle-threads-missing-comments');

                        // Save the changes and return.
                        $page->metadata = json_encode($metadata);
                        $page->JsonTimestamp = Carbon::now();
                        $page->save();
                        return response('saved', 200);
                    }
                }
            }


        }
    }

    public function put_page_files(Domain $domain, Request $request)
    {
        if (Gate::allows('write-programmatically')) {
            $page = Page::where('slug', $request["slug"])->orderBy('milestone', 'desc')->first();
            if($page == null) {
                // Well this is awkward.
                // 2stacks just sent us files for a page we don't have.
                // Summon the troops.
                Log::error('2stacks sent us a file for ' . $request["wd_page_id"]. ' for wiki ' . $domain->wiki->id . ' but SCUTTLE doesn\'t have a matching page!');
                Log::error('$request: ' . $request);
                return response('I don\'t have a page to attach this file to!', 500)
                    ->header('Content-Type', 'text/plain');
            }
            // We will have a lot of pages with no files to attach but they'll all let us know one way or another.
            // We could do this at any time and it was simpler to fire this once a page had metadata attached.
            if ($request["has_files"] == false) {
                $metadata = json_decode($page->metadata, true);
                unset($metadata["page_missing_files"]);
                $page->metadata = json_encode($metadata);
                $page->JsonTimestamp = Carbon::now();
                $page->save();
            } else {
                // 2stacks has sent us a link to a file and some metadata about it. We know there's only one file in the payload.
                $file = new File([
                    'page_id' => $page->id,
                    'filename' => $request["filename"],
                    'path' => $request["path"],
                    'size' => $request["size"],
                    'metadata' => json_encode($request["metadata"]),
                    'JsonTimestamp' => Carbon::now()
                ]);
                $file->save();
            }
        }
    }
}
