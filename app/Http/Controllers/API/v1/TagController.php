<?php


namespace App\Http\Controllers\API\v1;

use App\Domain;
use App\Http\Controllers\Controller;
use App\Page;
use App\Tag;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    public function validate_tag(Domain $domain, $id)  {
        $rule['id'] = $id;
        Validator::make($rule, [
            'id' => 'required|integer|min:1|max:9999999999'
        ])->validate();
        try {
            $tag = Tag::withTrashed()->where('wiki_id', $domain->wiki_id)->findOrFail($id);
        } catch (ModelNotFoundException $e) { return null; }

        return $tag;
    }

    public function tag_get_tag(Domain $domain)
    {
        $tags = DB::table('tags')->select('id', 'wiki_id', 'name')->where('wiki_id', $domain->wiki_id)->get();
        $payload = $tags->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function tag_get_tag_NAME_pages(Domain $domain, $name)
    {
        $rule['name'] = $name;
        Validator::make($rule, [
            'name' => 'required|string|max:120'
        ])->validate();

        $tag = Tag::where('wiki_id', $domain->wiki_id)->where('name',$name)->first();
        if(!$tag) { return response()->json(['message' => 'A tag with that name has not been recorded for this wiki.'])->setStatusCode(404); }
        else {
            $pages = $tag->pages()->get();
            foreach($pages as $page) {
                unset($page->latest_revision);
                $page->metadata = json_decode($page->metadata, true);
            }
        }
        $payload = $pages->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function tag_post_tag_pages(Domain $domain, Request $request)
    {
        Validator::make($request->all(), [
            'ids.*' => 'required_without:names|integer|min:0|max:999999',
            'names.*' => 'required_without:ids|string|min:1|max:120',
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'operator' => ['nullable', Rule::in(['and', 'or'])],
            'direction' => ['nullable', Rule::in(['asc','desc'])],
        ])->validate();

        $limit = $request->limit ?? 20;
        $offset = $request->offset ?? 0;
        $operator = $request->operator ?? 'and';
        $direction = $request->direction ?? 'asc';

        $page_ids = [];

        if(isset($request['ids'])) {
            $tag_ids = $request['ids'];
        }
        else {
            $tag_ids = Tag::where('wiki_id',$domain->wiki_id)->whereIn('name',$request['names'])->pluck('id')->toArray();
        }

        if($operator == 'and') {
            $page_ids = DB::table('page_tags')->
                join('pages','page_tags.page_id','=','pages.id')->
                select('page_tags.page_id')->
                where('page_tags.wiki_id', $domain->wiki_id)->
                whereIn('page_tags.tag_id', $tag_ids)->
                GroupBy('page_tags.page_id')->
                havingRaw('COUNT(*) = '.count($tag_ids))->
                get()->
                pluck('page_id')->
                toArray();
        }
        else { // $operator == 'or'
            $page_ids = PageTag::where('wiki_id',$domain->wiki_id)->
                whereIn('tag_id', $tag_ids)->
                pluck('page_id')->
                toArray();
        }

        // We've got page IDs, let's send back the pages.
        $pages = Page::whereIn('id', $page_ids)->get();
        foreach($pages as $page) {
            unset($page->latest_revision);
            $page->metadata = json_decode($page->metadata, true);
        }
        $payload = $pages->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }
}
