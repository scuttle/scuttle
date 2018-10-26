<?php

namespace App\Http\Controllers\API;

use App\Domain;
use App\Page;
use App\Revision;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \App\Domain  $domain
     * @return \Illuminate\Http\Response
     */
    public function index(Domain $domain)
    {
        return $domain->wiki->pages->toJson();
    }

    /**
     * Dump a listing of all pages and their latest revision number for a wiki.
     *
     * @param  \App\Domain  $domain
     * @return \Illuminate\Http\Response
     */
    public function revisions(Domain $domain)
    {
        $pages = Page::where('wiki_id', $domain->wiki->id)->get();
        $arr = [];
        foreach($pages as $page) {
            $metadata = json_decode($page->metadata);
            $arr[$page->slug] = $metadata->revisions;
        }

        return json_encode($arr);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Domain  $domain
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function wdstore(Domain $domain, Request $request)
    {
        $page = new Page([
            'wiki_id' => $domain->wiki->id,
            'slug' => $request->fullname,
            'metadata' => json_encode(array(
                'updated_by' => array(
                    'author_type' => 'import',
                    'author' => $request->updated_by,
                ),
                'updated_at' => Carbon::now()->timestamp,
                'rating' => $request->rating,
                'parent_slug' => $request->parent_fullname,
                'parent_title' => $request->parent_title,
                'revisions' => $request->revisions,
                'tags' => $request->tags,
                'title' => $request->title,
                'wd_title_shown' => $request->title_shown,
                'commentcount' => $request->comments,
                'created_at' => Carbon::parse($request->created_at)->timestamp,
                'created_by' => array(
                    'author_type' => 'import',
                    'author' => $request->created_by,
                ),
            )),
            'JsonTimestamp' => Carbon::now()
        ]);
        $page->save();
        
        $revision = new Revision([
            'page_id' => $page->id,
            'user_id' => 0,
            'content' => $request->content, // This IS (or should be) a valid member of $request despite the error.
            'metadata' => json_encode(array(
                'description' => "Imported by 2stacks.",
                'major' => true,
                'rating' => $request->rating,
                'display_author' => $request->updated_by
            ))
        ]);
        $revision->save();
        return response("ok");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Domain  $domain
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function show(Domain $domain, $slug)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Domain  $domain
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Domain $domain, Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Domain  $domain
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Domain $domain, $id)
    {
        //
    }
}
