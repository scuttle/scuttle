<?php


namespace App\Http\Controllers\API\v1;

use App\Thread;
use App\Http\Controllers\Controller;
use App\Domain;
use App\Forum;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ThreadController extends Controller
{
    public function validate_thread(Domain $domain, $id)
    {
        $rule['id'] = $id;
        Validator::make($rule, [
            'id' => 'required|integer|min:1|max:9999999999'
        ])->validate();
        try {
            $thread = Thread::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return null;
        }
        // Little check to see if this thread is within the scope of this domain.
        // We're not returning a different status in the interest of customer privacy.
        try { if($thread->forum->wiki_id != $domain->wiki_id) { return null; } }
        catch (\ErrorException $e) { // Forum is null, needs to be updated.
            $forum = Forum::where('wd_forum_id',$thread->wd_forum_id)->first();
            if (!empty($forum)) { $thread->forum_id = $forum->id; $thread->save(); }
            if($forum->wiki_id != $domain->wiki_id) { return null; }
        }
        $thread->metadata = json_decode($thread->metadata, true);
        return $thread;
    }

    public function thread_get_thread_ID(Domain $domain, $id)
    {
        $thread = $this->validate_thread($domain, $id);
        if(!$thread) { return response()->json(['message' => 'A thread with that ID was not found in this wiki.'])->setStatusCode(404); }
        unset($thread->forum);
        $payload = $thread->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function thread_get_thread_ID_posts(Domain $domain, $id)
    {
        $thread = $this->validate_thread($domain, $id);
        if(!$thread) { return response()->json(['message' => 'A thread with that ID was not found in this wiki.'])->setStatusCode(404); }
        $posts = $thread->posts()->select('id', 'parent_id','wd_post_id','wd_parent_id')->get()->toJson();
        return response($posts)->header('Content-Type', 'application/json');
    }

    public function thread_post_thread_ID_posts(Domain $domain, Request $request, $id)
    {
        $thread = $this->validate_thread($domain, $id);
        if(!$thread) { return response()->json(['message' => 'A thread with that ID was not found in this wiki.'])->setStatusCode(404); }

        Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'direction' => ['nullable', Rule::in(['asc','desc'])],
        ])->validate();

        $limit = $request->limit ?? 20;
        $offset = $request->offset ?? 0;
        $direction = $request->direction ?? 'asc';

        $posts = $thread->posts()->limit($limit)->offset($offset)->orderBy('wd_post_id',$direction)->get();
        foreach($posts as $post) {
            $post->metadata = json_encode($post->metadata, true);
        }
        return response($posts->toJson())->header('Content-Type', 'application/json');
    }

    public function thread_post_thread_ID_since_TIMESTAMP(Domain $domain, Request $request, $id, $timestamp)
    {
        $thread = $this->validate_thread($domain, $id);
        if(!$thread) { return response()->json(['message' => 'A thread with that ID was not found in this wiki.'])->setStatusCode(404); }

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

        $posts = $thread->posts()->where('metadata->wd_timestamp','>',$timestamp)->orderBy('wd_post_id',$direction)->limit($limit)->offset($offset)->get();
        foreach($posts as $post) {
            $post->metadata = json_decode($post->metadata, true);
        }
        return response($posts)->header('Content-Type', 'application/json');
    }
}
