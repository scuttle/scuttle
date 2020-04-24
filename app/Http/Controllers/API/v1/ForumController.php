<?php


namespace App\Http\Controllers\API\v1;

use App\Forum;
use App\Http\Controllers\Controller;
use App\Domain;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ForumController extends Controller
{
    public function validate_forum(Domain $domain, $id)
    {
        $rule['id'] = $id;
        Validator::make($rule, [
            'id' => 'required|integer|min:1|max:999999'
        ])->validate();
        try {
            $forum = Forum::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return null;
        }
        // Little check to see if this forum is within the scope of this domain.
        // We're not returning a different status in the interest of customer privacy.
        if($forum->wiki_id != $domain->wiki_id) { return null; }
        $forum->metadata = json_decode($forum->metadata, true);
        return $forum;
    }

    public function forum_get_forum(Domain $domain)
    {
        $forums = DB::table('forums')->where('wiki_id',$domain->wiki_id)->get();
        foreach($forums as $forum) {
            $forum->metadata = json_decode($forum->metadata, true);
        }
        $payload = $forums->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function forum_get_forum_ID(Domain $domain, $id)
    {
        $forum = $this->validate_forum($domain, $id);
        if(!$forum) { return response()->json(['message' => 'A forum with that ID was not found in this wiki.'])->setStatusCode(404); }
        $payload = $forum->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function forum_get_forum_ID_threads(Domain $domain, $id)
    {
        $forum = $this->validate_forum($domain, $id);
        if(!$forum) { return response()->json(['message' => 'A forum with that ID was not found in this wiki.'])->setStatusCode(404); }
        $threads = $forum->threads()->select('id','wd_thread_id')->get()->toJson();
        return response($threads)->header('Content-Type', 'application/json');
    }

    public function forum_post_forum_ID_since_TIMESTAMP(Domain $domain, Request $request, $id, $timestamp)
    {
        // Validate
        $forum = $this->validate_forum($domain, $id);
        $request['timestamp'] = $timestamp;
        Validator::make($request->all(), [
            'timestamp' => 'required|integer|min:0',
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'direction' => ['nullable', Rule::in(['asc','desc'])],
        ])->validate();

        $limit = $request->limit ?? 20;
        $offset = $request->offset ?? 0;
        $direction = $request->direction ?? 'asc';

        if(!$forum) { return response()->json(['message' => 'A forum with that ID was not found in this wiki.'])->setStatusCode(404); }
        $threads = $forum->threads()->where('metadata->wd_created_at','>',$timestamp)->offset($offset)->limit($limit)->orderBy('wd_thread_id',$direction)->get();
        foreach ($threads as $thread) {
            $thread->metadata = json_decode($thread->metadata, true);
        }
        return response($threads)->header('Content-Type', 'application/json');
    }
}
