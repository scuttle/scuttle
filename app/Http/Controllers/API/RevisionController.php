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
    public function put_page_revisions(Domain $domain, Request $request)
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
                if(isset($oldmetadata["page_missing_revisions"]) && $oldmetadata["page_missing_revisions"] == true) {
                    foreach($request["revisions"] as $revision) {
//                        Log::info($request["revisions"]);
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
                                ),
                            )),
                        ]);
                        $r->save();

                        // Now handle the content field depending on revision type.

                        $metadata = json_decode($r->metadata, true);
                        if($revision["revision_type"] == "S" || $revision["revision_type"] == "N") {
                            // Dispatch a 'get revision content' job if it's a source revision or the first revision.
                            $metadata["revision_missing_content"] = 1;
                            $r->metadata = json_encode($metadata);
                            PushRevisionId::dispatch($r->wd_revision_id)->onQueue('scuttle-revisions-missing-content');
                        }
                        else {
                            // Move the programmatically created comment for the revision into content.
                            $r->content = $metadata["wikidot_metadata"]["comments"];
                        }
                        $r->save();


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
                    // Update the metadata for the page.
                    unset($oldmetadata["page_missing_revisions"]);
                    $page->metadata = json_encode($oldmetadata);
                    $page->jsonTimestamp = Carbon::now(); // touch on update
                    $page->save();

                    // We out.
                    return response(json_encode(array('status' => 'completed')));
                }
            }
        }
    }

    public function put_revision_content(Domain $domain, Request $request)
    {
        if(Gate::allows('write-programmatically')) {
            $r = Revision::where('wd_revision_id', $request["wd_revision_id"])->get();
            if ($r->isEmpty()) {
                // Well this is awkward.
                // 2stacks just sent us content for a revision we don't have.
                // Summon the troops.
                Log::error('2stacks sent us content for revision ' . $request["wd_revision_id"] . ' but SCUTTLE doesn\'t have a matching wd_revision_id!');
                Log::error('$request: ' . $request);
                return response('I don\'t have a wd_revision_id to attach this content to!', 500)
                    ->header('Content-Type', 'text/plain');
            }
            else {
                $revision = $r->first();
                // Verify we still need this content.
                $metadata = json_decode($revision->metadata, true);
                if(isset($metadata["revision_missing_content"]) && $metadata["revision_missing_content"] == 1) {
                    $revision->content = $request["content"];
                    unset($metadata["revision_missing_content"]);
                    $revision-> metadata = json_encode($metadata);
                    $revision->save();
                }
            }
            return response('thank');
        }
    }
}
