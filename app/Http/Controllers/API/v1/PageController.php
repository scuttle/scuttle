<?php

namespace App\Http\Controllers\API\v1;

use App\Domain;
use App\Http\Controllers\Controller;
use App\Page;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PageController extends Controller
{
    public function validate_page(Domain $domain, $id)
    {
        $rule['id'] = $id;
        Validator::make($rule, [
            'id' => 'required|integer|min:1|max:9999999999'
        ])->validate();
        try {
            $page = Page::withTrashed()->where('wiki_id', $domain->wiki_id)->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return null;
        }
        return $page;
    }

    public function page_get_page(Domain $domain)
    {
        $pages = DB::table('pages')->select('id', 'slug', 'wd_page_id')->where('wiki_id', $domain->wiki_id)->whereNull('deleted_at')->get();
        $payload = $pages->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function page_get_page_ID(Domain $domain, $id)
    {
        $page = $this->validate_page($domain,$id);
        if(!$page) { return response()->json(['message' => 'A page with that ID was not found in this wiki.'])->setStatusCode(404); }

        $page->metadata = json_decode($page->metadata, true);
        $payload = $page->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function page_get_page_slug_SLUG(Domain $domain, $slug)
    {
        // Note we are not searching for trashed items in this search.
        try {
            $page = Page::where('wiki_id', $domain->wiki_id)
                ->where('slug', $slug)
                ->orderBy('milestone', 'desc')
                ->firstorFail();
        }
        catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'A page with that slug was not found in this wiki.'])->setStatusCode(404);
        }
        $page->metadata = json_decode($page->metadata, true);
        $payload = $page->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function page_get_page_ID_revisions(Domain $domain, $id)
    {
        $page = $this->validate_page($domain,$id);
        if(!$page) { return response()->json(['message' => 'A page with that ID was not found in this wiki.'])->setStatusCode(404); }

        $revisions = $page->revisions()->select(['id','wd_revision_id','wd_user_id','revision_type','metadata'])->get();
        foreach($revisions as $revision) { $revision->metadata = json_decode($revision->metadata, true); }
        $payload = $revisions->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function page_post_page_ID_revisions(Domain $domain, Request $request, $id)
    {
        $page = $this->validate_page($domain,$id);
        if(!$page) { return response()->json(['message' => 'A page with that ID was not found in this wiki.'])->setStatusCode(404); }

        Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'direction' => ['nullable', Rule::in(['asc','desc'])],
        ])->validate();

        $limit = $request->limit ?? 20;
        $offset = $request->offset ?? 0;
        $direction = $request->direction ?? 'asc';

        $revisions = $page->revisions()->offset($offset)->limit($limit)->orderBy('wd_revision_id', $direction)->get();
        foreach($revisions as $revision) { $revision->metadata = json_decode($revision->metadata, true); unset($revision->searchtext); }
        $payload = $revisions->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function page_get_page_ID_votes(Domain $domain, $id)
    {
        $page = $this->validate_page($domain,$id);
        if(!$page) { return response()->json(['message' => 'A page with that ID was not found in this wiki.'])->setStatusCode(404); }

        $votes = $page->votes;
        foreach ($votes as $vote) {
            $vote->metadata = json_decode($vote->metadata, true);
        }
        $payload = $votes->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function page_get_page_ID_tags(Domain $domain, $id)
    {
        $page = $this->validate_page($domain,$id);
        if(!$page) { return response()->json(['message' => 'A page with that ID was not found in this wiki.'])->setStatusCode(404); }

        $tags = $page->tags;
        foreach($tags as $tag) { unset($tag->pivot); unset($tag->created_at); unset($tag->updated_at); }
        $payload = $tags->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function page_get_page_ID_files(Domain $domain, $id)
    {
        $page = $this->validate_page($domain,$id);
        if(!$page) { return response()->json(['message' => 'A page with that ID was not found in this wiki.'])->setStatusCode(404); }

        $files = $page->files;
        foreach ($files as $file) {
            $file->metadata = json_decode($file->metadata, true);
        }
        $payload = $files->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function page_get_page_ID_latestsource(Domain $domain, $id)
    {
        $page = $this->validate_page($domain,$id);
        if(!$page) { return response()->json(['message' => 'A page with that ID was not found in this wiki.'])->setStatusCode(404); }

        $revision = $page->revisions()->where('revision_type', 'S')->latest()->limit(1)->first();

        $payload = $revision->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }
}
