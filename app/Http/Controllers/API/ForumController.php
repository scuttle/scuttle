<?php

namespace App\Http\Controllers\API;

use App\Forum;
use App\Http\Controllers\Controller;
use App\Jobs\SQS\PushForumId;
use App\Jobs\SQS\PushThreadId;
use App\Thread;
use App\Wiki;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use App\Domain;

class ForumController extends Controller
{
    public function sched_forum_threads(Domain $domain, Request $request)
    {
        if (Gate::allows('write-programmatically')) {
            foreach($request->threads as $thread_id) {
                $thread = new Thread;
                $thread->wd_thread_id = $thread_id;
                $thread->wd_forum_id = $request->wd_forum_id;
                $thread->user_id = auth()->id();
                $thread->metadata = json_encode(array("thread_missing_posts" => true));
                $thread->JsonTimestamp = Carbon::now();
                $thread->save();

                // Queue the job to get comments.
                $job = new PushThreadId($thread_id, $domain->wiki->id);
                $job->send('scuttle-threads-missing-comments');
            }
        }
    }

    public function put_forum_metadata(Domain $domain, Request $request)
    {
        if(Gate::allows('write-programmatically')) {
            $w = Wiki::where('metadata->wd_site', $request["wd_wiki"])->get();
            if($w->isEmpty()) {
                // Well this is awkward.
                // 2stacks just sent us metadata about a user we don't have.
                // Summon the troops.
                Log::error('2stacks sent us metadata about ' . $request["wd_wiki"] . ' forums but SCUTTLE doesn\'t have a matching wiki!');
                Log::error('$request: ' . $request);
                return response('I don\'t have a wikidot user to attach that metadata to!', 500)
                    ->header('Content-Type', 'text/plain');
            }
            else {
                $wiki = $w->first(); // We really only need the wiki ID here, hence this method being a Forum method.

                // 2stacks has sent us a list of forums for this wiki and a lot of metadata. We may already have them.
                foreach($request["forums"] as $forum) {
                    $f = Forum::where('wd_forum_id', $forum["category_id"])->get();
                    if ($f->isEmpty()) {
                        // We're seeing this forum for the first time.
                        $newforum = new Forum([
                            'wd_forum_id' => $forum["category_id"],
                            'wiki_id' => $wiki->id,
                            'title' => $forum["category_name"],
                            'subtitle' => $forum["category_description"],
                            'parent_id' => null, // We probably need to drop this column, this didn't behave like I anticipated.
                            'wd_parent_id' => null, // Ditto.
                            'metadata' => json_encode(array(
                                'wd_metadata' => array(
                                'threads' => $forum["category_threads"],
                                'posts' => $forum["category_posts"],
                                'last_post_ts' => $forum["category_last_post"],
                                'last_poster_wd_user_id' => $forum["category_last_poster_id"],
                                'last_poster_wd_username' => $forum["category_last_poster_username"],
                                'last_post_thread' => $forum["category_last_thread"])
                            )),
                            'JsonTimestamp' => Carbon::now()
                        ]);
                        $newforum->save();
                    }
                    else {
                        // We already know about this forum, let's see if our stuff is up to date.
                        $oldforum = $f->first();
                        $oldmetadata = json_decode($oldforum->metadata, true);
                        if($forum["category_posts"] > $oldmetadata["wd_metadata"]["posts"]) {
                            // We are out of date, let's go get some stuff.
                            $job = new PushForumId($f->wd_forum_id, $wiki->id);
                            $job->send('scuttle-forums-needing-update.fifo');
                        }
                    }
                }
            }
        }
    }
}
