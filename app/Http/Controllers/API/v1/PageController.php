<?php

namespace App\Http\Controllers\API\v1;

use App\Domain;
use App\Http\Controllers\Controller;
use App\Page;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PageController extends Controller
{
    public function page_get_page(Domain $domain)
    {
        $pages = DB::table('pages')->select('id', 'slug', 'wd_page_id')->where('wiki_id', $domain->wiki_id)->whereNull('deleted_at')->get();
        $payload = $pages->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function page_get_page_ID(Domain $domain, $id)
    {
        $page = Page::withTrashed()->where('wiki_id',$domain->wiki_id)->findOrFail($id);
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
            return response()->json([
               'error' => 'Page not found',
            ], 404);
        }
        $page->metadata = json_decode($page->metadata, true);
        $payload = $page->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function page_get_page_ID_revisions(Domain $domain, $id)
    {
        $page = Page::withTrashed()->where('wiki_id',$domain->wiki_id)->findOrFail($id);
        $revisions = $page->revisions()->select(['id','wd_revision_id','wd_user_id','revision_type','metadata'])->get();
        foreach($revisions as $revision) { $revision->metadata = json_decode($revision->metadata, true); }
        $payload = $revisions->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function page_post_page_revisions(Domain $domain, Request $request)
    {
        Validator::make($request->all(), [
           'id' => 'required|numeric|min:1|max:9999999999',
            'limit' => 'nullable|numeric|min:1|max:100',
            'offset' => 'nullable|numeric|min:0'
        ])->validate();

        $limit = $request->limit ?? 20;
        $offset = $request->offset ?? 0;

        $page = Page::withTrashed()->where('wiki_id',$domain->wiki_id)->findOrFail($request->id);
        $revisions = $page->revisions()->offset($offset)->limit($limit)->get();
        foreach($revisions as $revision) { $revision->metadata = json_decode($revision->metadata, true); }
        $payload = $revisions->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function page_get_page_ID_votes(Domain $domain, $id)
    {
        $page = Page::withTrashed()->where('wiki_id',$domain->wiki_id)->findOrFail($id);
        $votes = $page->votes;
        foreach ($votes as $vote) {
            $vote->metadata = json_decode($vote->metadata, true);
        }
        $payload = $votes->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }
}
