<?php

namespace App\Http\Controllers\API\v1;

use App\Post;
use App\Http\Controllers\Controller;
use App\Domain;
use App\Forum;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function validate_post(Domain $domain, $id)
    {
        $rule['id'] = $id;
        Validator::make($rule, [
            'id' => 'required|integer|min:1|max:9999999999'
        ])->validate();
        try {
            $post = Post::where('wiki_id', $domain->wiki_id)->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return null;
        }
        // Little check to see if this post is within the scope of this domain.
        // We're not returning a different status in the interest of customer privacy.
        $post->metadata = json_decode($post->metadata, true);
        return $post;
    }

    public function post_get_post_since_TIMESTAMP(Domain $domain, $timestamp)
    {
        $rule['timestamp'] = $timestamp;
        Validator::make($rule, [
            'timestamp' => 'required|integer|min:0',
        ])->validate();

        $posts = DB::table('posts')
            ->select('id', 'parent_id', 'wd_post_id', 'wd_parent_id')
            ->where('wiki_id', $domain->wiki_id)
            ->where('metadata->wd_timestamp', '>', $timestamp)
            ->whereNull('deleted_at')
            ->orderBy('metadata->wd_timestamp')
            ->get();
        $payload = $posts->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function post_post_post_since_TIMESTAMP(Domain $domain, Request $request, $timestamp)
    {
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

        $posts = DB::table('posts')
            ->where('wiki_id', $domain->wiki_id)
            ->where('metadata->wd_timestamp', '>', $timestamp)
            ->whereNull('deleted_at')
            ->orderBy('metadata->wd_timestamp', $direction)
            ->limit($limit)
            ->offset($offset)
            ->get();
        foreach($posts as $post) {
            $post->metadata = json_decode($post->metadata, true);
        }
        $payload = $posts->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function post_get_post_ID(Domain $domain, $id)
    {
        $post = $this->validate_post($domain, $id);
        if(!$post) { return response()->json(['message' => 'A post with that ID was not found in this wiki.'])->setStatusCode(404); }
        $payload = $post->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function post_get_post_ID_children(Domain $domain, $id)
    {
        $post = $this->validate_post($domain, $id);
        if(!$post) { return response()->json(['message' => 'A post with that ID was not found in this wiki.'])->setStatusCode(404); }
        $children = $post->children()->get();
        foreach($children as $child) {
            $child->metadata = json_decode($child->metadata, true);
        }
        return response($children->toJson())->header('Content-Type', 'application/json');
    }

    public function post_get_post_ID_parent(Domain $domain, $id)
    {
        $post = $this->validate_post($domain, $id);
        if(!$post) { return response()->json(['message' => 'A post with that ID was not found in this wiki.'])->setStatusCode(404); }
        $parent = $post->parent()->first();
        if(!$parent) { return response(array())->header('Content-Type', 'application/json'); }
        $parent->metadata = json_decode($parent->metadata, true);
        return response($parent->toJson())->header('Content-Type', 'application/json');
    }
}
