<?php

namespace App\Http\Controllers;

use App\Milestone;
use App\Page;
use App\Revision;
use Illuminate\Http\Request;
use App\Parser;
use Illuminate\Support\Collection;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function show(Page $page)
    {
        $metadata = json_decode($page->metadata, true);
        $page->milestone = $page->milestone();
        $milestones = $page->slug_milestones();
        return view('page.show', compact(['page','metadata','milestones']));
    }

    /**
     * Display the 'not found' blade.
     *
     * @param  String  $slug
     * @param  \App\Domain  $domain
     * @return \Illuminate\View\View
     */
    public function notfound($slug, $domain)
    {
        // We know there isn't a live page at this location or we'd have not wound up here, let's see if there are trashed ones.
        $milestone = Milestone::where('wiki_id',$domain->wiki_id)->where('slug',$slug)->max('milestone');

        return view('page.notfound', compact(['slug','milestone']));
    }

    /**
     * Display the specified revision of the resource.
     *
     * @param  \App\Page  $page
     * @param  \App\Revision  $revision
     * @return \Illuminate\View\View
     */
    public function showrevision(Revision $revision, Page $page)
    {
        $page_milestones = $page->page_milestones();
        $slug_milestones = $page->slug_milestones();
        $page->milestone = $page->milestone();
        $pagemetadata = json_decode($page->metadata, true);
        $revisionmetadata = json_decode($revision->metadata, true);
        return view('page.showrevision', compact(['page','revision','pagemetadata','revisionmetadata', 'page_milestones', 'slug_milestones']));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function edit(Page $page)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Page $page)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function destroy(Page $page)
    {
        //
    }

    public function jsonVotes()
    {
        $pages = Page::all();
        $out = new Collection();

        foreach($pages as $page) {
            $metadata = json_decode($page->metadata, true);
            $out->push(['title' => $page->slug, 'rating' => $metadata['rating'], 'created_at' => $metadata['created_at'], 'tags' => $metadata['tags']]);
        }

        return $out->toJson();
    }

    public function diff_basic($page1, $page2)
    {
        $diff = \App\Diff::toTable(\App\Diff::compare($page1, $page2), '', '<br>');
        return view('page.diff', compact(['diff']));
    }
}
