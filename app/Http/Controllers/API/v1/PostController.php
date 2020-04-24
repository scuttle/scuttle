<?php

namespace App\Http\Controllers\API\v1;

use App\Post;
use App\Http\Controllers\Controller;
use App\Domain;
use App\Forum;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
