<?php

namespace App\Http\Controllers\API;

use App\Domain;
use App\File;
use App\Jobs\SQS\PushPageId;
use App\Jobs\SQS\PushPageSlug;
use App\Jobs\SQS\PushThreadId;
use App\Jobs\SQS\PushWikidotUserId;
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

class PageController extends Controller
{
    public function put_2stacks_pages_manifest(Domain $domain, Request $request)
    {
        if(Gate::allows('write-programmatically')) {
            $reportedpages = $request->toArray(); // Wikidot's list of pages as provided by 2stacks/lambda.
            $scuttlepages = Page::where('wiki_id',$domain->wiki_id)
                ->pluck('slug')
                ->toArray(); // Our list of pages which does *not* include trashed/deleted pages.

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
                $lastmilestone = Page::withTrashed()->where('wiki_id',$domain->wiki_id)->where('slug',$item)->orderBy('milestone','desc')->pluck('milestone')->first();
                if ($lastmilestone === null) { $milestone = 0; }
                else { $milestone = $lastmilestone + 1; }
                $page = new Page([
                    'wiki_id' => $domain->wiki->id,
                    'user_id' => auth()->id(),
                    'slug' => $item,
                    'milestone' => $milestone,
                    'metadata' => json_encode(
                        array(
                            'page_missing_metadata' => true
                        )
                    ),
                    'JsonTimestamp' => Carbon::now()
                ]);
                $page->save();
                // Send an SQS message for 2stacks-lambda to work on.
                $job = new PushPageSlug($page->slug, $domain->wiki_id);
                $job->send('scuttle-pages-missing-metadata');
            }
            // Now, we can also infer pages that have been deleted by taking the opposite diff.
            $deletedpages = leo_array_diff($scuttlepages, $reportedpages);
            foreach($deletedpages as $deletedpage) {
                // Note: We use soft deletes on the Page model, nothing is actually destroyed here, we are just adding a timestamp to the 'deleted_at' field.
                // This will also exclude the page from normal queries, i.e., queries not using Page::withTrashed()->where('blah')
                $page = Page::where('wiki_id', $domain->wiki_id)->where('slug', $deletedpage)->orderBy('milestone','desc')->first()->delete();
            }
        }
    }

    public function sched_pages_metadata(Domain $domain, Request $request)
    {
        if(Gate::allows('write-programmatically')) {
            // Each request is simple metadata about a single page. We one of these a day for each page.

            // Go get the page first.
            $p = Page::where('wiki_id', $domain->wiki->id)
                ->where('slug', $request["fullname"])
                ->orderBy('milestone', 'desc')
                ->get();

            if($p->isEmpty()) {
                // Counterintuitively, this should never happen. All the slugs we got back were for pages we already had,
                // because we initiated this from the SCUTTLE side.
                // Summon the troops.
                Log::error('2stacks sent us metadata about ' . $request->fullname . ' for wiki ' . $domain->wiki->id . ' but SCUTTLE doesn\'t have a matching slug!');
                Log::error('$request: ' . $request);
                return response('I don\'t have a slug to attach that metadata to!', 500)
                    ->header('Content-Type', 'text/plain');
            }
            else {
                // Back on earth...
                $page = $p->first();

                // We want to make sure our created timestamp matches up, otherwise we're dealing with a new page.
                // We also get a revision count here.
                $metadata = json_decode($page->metadata, true);
                if($metadata["wikidot_metadata"]["created_at"] != $request->created_at) {
                    // New page.
                    $np = new Page([
                        'wiki_id' => $domain->wiki->id,
                        'user_id' => auth()->id(),
                        'slug' => $page->slug,
                        'milestone' => $page->milestone + 1,
                        'metadata' => json_encode(
                            array(
                                'page_missing_metadata' => true
                            )
                        ),
                        'JsonTimestamp' => Carbon::now()
                    ]);
                    $np->save();
                    // Send an SQS message for 2stacks-lambda to work on.
                    $job = new PushPageSlug($np->slug, $domain->wiki_id);
                    $job->send('scuttle-pages-missing-metadata');
                }
                else {
                    if($request->revisions + 1 != $page->revisions->count()) {
                        // We're missing revisions.
                        $metadata["page_missing_revisions"] = true;
                        $page->metadata = json_encode($page->metadata);
                        $page->JsonTimestamp = Carbon::now(); // Touch on update.
                        $page->save();
                        // Push the revision gettin' job.
                        $job1 = new PushPageId($page->wd_page_id, $domain->wiki->id);
                        $job1->send('scuttle-pages-missing-revisions');
                    }
                }
            }
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
                    $job1 = new PushPageId($page->wd_page_id, $domain->wiki->id);
                    $job1->send('scuttle-pages-missing-revisions');
                    $job2 = new PushPageId($page->wd_page_id, $domain->wiki->id);
                    $job2->send('scuttle-pages-missing-thread-id');
                    $job3 = new PushPageSlug($page->slug, $domain->wiki->id);
                    $job3->send('scuttle-pages-missing-files');
                    $job4 = new PushPageId($page->wd_page_id, $domain->wiki->id);
                    $job4->send('scuttle-pages-missing-votes');
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

                // Get all the existing votes.
                $allvotes = Vote::where('page_id', $page->id)->get();
                // Filter out the old ones, return active, nonmember, and deleted ones.
                $votes = $allvotes->whereNotIn('status','old');
                // Get all the wd_user_ids.
                $oldvoters = $votes->pluck('wd_user_id')->toArray();
                // Make a collection of users.
                $wikidotusers = WikidotUser::whereIn('wd_user_id',$oldvoters)->get();
                //Quickly, let's go through the request and pull all the new IDs to an array as we'll need them.
                $newvoters = [];
                foreach ($request["votes"] as $vote) {
                    array_push($newvoters, $vote["user_id"]);
                }
                    foreach($request["votes"] as $vote) {
                        // A vote can exist in (currently) one of four status codes.
                        // Active, old (a vote that flipped in the past), deleted (user deleted their account), or nonmember (votes fall off if banned or left of own volition).
                        // We're retrieving all the active ones for now, and will flip them if needed.
                        $oldvotecollection = $votes->where('wd_user_id',$vote["user_id"]);
                        if($oldvotecollection->isEmpty()) {
                            // No existing vote from this user, make a new row.
                            $v = new Vote([
                                'page_id' => $page->id,
                                'user_id' => auth()->id(),
                                'wd_user_id' => $vote["user_id"],
                                'wd_vote_ts' => Carbon::now(),
                                'JsonTimestamp' => Carbon::now()
                            ]);
                            if ($vote["vote"] == "+") {
                                $v->vote = 1;
                            } else if ($vote["vote"] == "-") {
                                $v->vote = -1;
                            } else {
                                $v->vote = 0;
                            }

                            //It's possible a user has voted and then deleted their account, so their status is not yet determined.
                            if(strpos($vote["username"], "Deleted Account ") === 0) {
                                $v->metadata = json_encode(array('status' => 'deleted'));
                            }
                            else {
                                $v->metadata = json_encode(array('status' => 'active'));
                            }

                            $v->save();
                        }
                        else{
                            // We have a vote from this user on this article. This is normal as we're just checking on a
                            // schedule and generally speaking, votes won't change. So let's get some stuff out of the way quickly.
                            $oldvote = $oldvotecollection->first();
                            if($oldvote->status == 'deleted') {
                                // Deleted accounts aren't gonna change their vote, move on.
                                continue;
                            }

                            // Let's figure out if the vote we were sent is an upvote or a downvote,
                            if ($vote["vote"] == "+") {
                                $newvote = 1;
                            } else if ($vote["vote"] == "-") {
                                $newvote = -1;
                            } else {
                                $newvote = 0;
                            }

                            if ($oldvote->vote == $newvote) {
                                // The vote didn't change, but the user could have still left.
                                if(strpos($vote["username"], "Deleted Account ") === 0) {
                                    $oldvote->metadata = json_encode(array('status' => 'deleted'));
                                    $oldvote->JsonTimestamp = Carbon::now();
                                    $oldvote->save();
                                }
                            }
                            else {
                                // The user has flipped their vote. Call the old one, well, old. Otherwise we'll get a
                                // unique index constraint on a triple of wd_user_id, page_id, and status.
                                $oldvote->metadata=json_encode(array('status' => 'old'));
                                $oldvote->JsonTimestamp = Carbon::now();
                                $oldvote->save();
                                // Save the new one.
                                $v = new Vote([
                                    'page_id' => $page->id,
                                    'user_id' => auth()->id(),
                                    'vote' => $newvote,
                                    'wd_user_id' => $vote["user_id"],
                                    'wd_vote_ts' => Carbon::now(),
                                    'JsonTimestamp' => Carbon::now()
                                ]);
                                // It's possible a user has voted and then deleted their account, so their status is not yet determined.
                                if(strpos($vote["username"], "Deleted Account ") === 0) {
                                    $v->metadata = json_encode(array('status' => 'deleted'));
                                }
                                else {
                                    $v->metadata = json_encode(array('status' => 'active'));
                                }
                                $v->save();
                            }
                        }
                        // Let's see if we've seen this user before.
                        if($wikidotusers->contains($vote["user_id"]) == false) {
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
                            $job = new PushWikidotUserId($vote["user_id"], $domain->wiki->id);
                            $job->send('scuttle-users-missing-metadata');
                        }
                    }

                    // Now, let's compare the old and new lists and see who removed their vote (essentially setting a no-vote).
                    $removedvoters = array_values(array_diff($oldvoters,$newvoters));
                    foreach($removedvoters as $rv) {
                        $oldvote = $votes->where('wd_user_id', $rv)->first();
                        $oldvote->JsonTimestamp = Carbon::now();
                        $newvote = $oldvote->replicate();

                        // Old one is old.
                        $oldvote->metadata = json_encode(array('status' => 'old'));
                        $oldvote->save();

                        // New one is 0.
                        $newvote->vote = 0;
                        $newvote->save();
                    }

                    if(isset($oldmetadata["page_missing_votes"])) {
                        unset($oldmetadata["page_missing_votes"]); // Cleanup in case this is the first request.
                        $page->metadata = json_encode($oldmetadata);
                        $page->jsonTimestamp = Carbon::now(); // touch on update
                        $page->save();
                    }
                    return response('saved');
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
                        $job = new PushThreadId($metadata["wd_thread_id"], $domain->wiki->id);
                        $job->send('scuttle-threads-missing-comments');
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
                        $job = new PushThreadId($metadata["wd_thread_id"], $domain->wiki->id);
                        $job->send('scuttle-threads-missing-comments');

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
