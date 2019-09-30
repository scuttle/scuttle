<?php

namespace App\Http\Controllers\API;

use App\Domain;
use App\Jobs\PushRevisionId;
use App\Jobs\PushWikidotUserId;
use App\Page;
use App\Revision;
use App\WikidotUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class RevisionController extends Controller
{
    public function put_page_revision(Domain $domain, Request $request)
    {
        if(Gate::allows('write-programmatically')) {
            $p = Page::where('wiki_id', $domain->wiki->id)
                ->where('wd_page_id', $request["wd_page_id"])
                ->orderBy('milestone', 'desc')
                ->get();
            if($p->isEmpty()) {
                // Well this is awkward.
                // 2stacks just sent us revisions for a page we don't have.
                // Summon the troops.
                Log::error('2stacks sent us revisions for ' . $request["wd_page_id"] . ' for wiki ' . $domain->wiki->id . ' but SCUTTLE doesn\'t have a matching wd_page_id!');
                Log::error('$request: ' . $request);
                return response('I don\'t have a wd_page_id to attach these revisions to!', 500)
                    ->header('Content-Type', 'text/plain');
            }
            else {
                $page = $p->first();
                $oldmetadata = json_decode($page->metadata, true);
                if($oldmetadata["page_missing_revisions"] == true) {
                    foreach($request["revisions"] as $revision) {
                        $r = new Revision([
                            'wd_revision_id' => $revision["revision_id"],
                            'wd_user_id' => $revision["user_id"],
                            'revision_type' => $revision["revision_type"],
                            'page_id' => $page->id,
                            'user_id' => auth()->id(),
                            'content' => null,
                            'metadata' => json_encode(array(
                                'revision_missing_content' => true,
                                'wikidot_metadata' => array(
                                    'timestamp' => $revision["timestamp"],
                                    'username' => $revision["username"],
                                    'revision_number' => $revision["revision_number"],
                                    'comments' => $revision["comments"]
                                )
                            )),
                            'JsonTimestamp' => Carbon::now()
                        ]);
                        $r->save();
                        // Dispatch a 'get revision content' job.
                        PushRevisionId::dispatch($r->id)->onQueue('scuttle-revisions-missing-content');

                        // Do we have info on the users here?
                        $u = WikidotUser::where('wd_user_id', $revision["user_id"])->get();
                        if($u->isEmpty()) {
                            // We haven't seen this ID before, store what we know and queue a job for the rest.
                            $wu = new WikidotUser([
                                'wd_user_id' => $revision["user_id"],
                                'username' => $revision["username"],
                                'metadata' => json_encode(array(
                                    'user_missing_metadata' => true,
                                )),
                                'JsonTimestamp' => Carbon::now()
                            ]);
                            $wu->save();
                        PushWikidotUserId::dispatch($revision["user_id"])->onQueue('scuttle-users-missing-metadata');
                        }
                    }
                    return response(json_encode(array('status' => 'completed')));
                }
            }
        }
    }
}
