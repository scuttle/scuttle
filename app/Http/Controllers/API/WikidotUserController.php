<?php

namespace App\Http\Controllers\API;

use App\WikidotUser;
use Illuminate\Http\Request;
use App\Domain;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class WikidotUserController extends Controller
{
    public function put_wikidot_user_metadata(Domain $domain, Request $request)
    {
        if(Gate::allows('write-programmatically')) {
            $p = WikidotUser::find($request["wd_user_id"])
                ->get();
            if($p->isEmpty()) {
                // Well this is awkward.
                // 2stacks just sent us metadata about a user we don't have.
                // Summon the troops.
                Log::error('2stacks sent us metadata about ' . $request["username"] . ' (id ' . $request["wd_user_id"] . ') but SCUTTLE doesn\'t have a matching user!');
                Log::error('$request: ' . $request);
                return response('I don\'t have a wikidot user to attach that metadata to!', 500)
                    ->header('Content-Type', 'text/plain');
            }
            else {
                $wduser = $p->first();
                $oldmetadata = json_decode($wduser->metadata, true);
                if(isset($oldmetadata["user_missing_metadata"]) && $oldmetadata["user_missing_metadata"] == true) {
                    // This is the default use case, responding to the initial SQS message on a new user arriving.
                    // SQS queues can send a message more than once so we need to make sure we're handling all possibilities.
                    $wduser->username = $request["username"];
                    $wduser->wd_user_since = $request["wd_registration_timestamp"];
                    $wduser->avatar_path = $request["avatar_path"];
                    $wduser->metadata = json_encode(array(
                        // We're overwriting the old metadata entirely as the only thing it had was "needs metadata".
                        'wiki_membership_timestamps' => array(
                            $domain->wiki->id => $request["wiki_member_since"]
                        )
                    ));
                    $wduser->jsonTimestamp = Carbon::now(); // touch on update
                    $wduser->save();

                    return response('saved');
                }
                else { return response('had that one already'); }
            }
        }
    }
}
