<?php

namespace App\Http\Controllers;

use App\Page;
use App\Revision;
use Illuminate\Http\Request;
use App\Parser;
use Illuminate\Support\Collection;
use cogpowered\FineDiff\Diff;
use cogpowered\FineDiff\Granularity\Word;
use cogpowered\FineDiff\Render\Text;

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
    public function show(Page $page, $slug)
    {
        $revision = $page->latestrevision();
        $lastmajor = $page->lastmajor();
        if($revision->id != $lastmajor->id) {
            $granularity = new Word;
            $diff = new Diff($granularity);
            $render = new Text;
            $output = $render->process($lastmajor->content, $revision->content);
            $revision->content = $output;
        }
        $pagemetadata = json_decode($page->metadata, true);
        $milestonecount = Page::where('wiki_id', $page->wiki->id)->where('slug', $slug)->count();
        $revisionmetadata = json_decode($revision->metadata, true);
        $sourcerevisions = $page->sourcerevisions();
        return view('page.show', compact(['revision','slug','pagemetadata','revisionmetadata','sourcerevisions', 'milestonecount']));
    }

    /**
     * Display the specified revision of the resource.
     *
     * @param  \App\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function showrevision(Revision $revision, $slug)
    {
        $page = $revision->page()->first();
        $lastmajor = $page->lastmajor();
        $milestonecount = Page::where('wiki_id', $page->wiki->id)->where('slug', $slug)->count();
        if($revision->id != $lastmajor->id) {
            $granularity = new Word;
            $diff = new Diff($granularity);
            $render = new Text;
            $output = $render->process($lastmajor->content, $revision->content);
            $revision->content = $output;
        }
        $pagemetadata = json_decode($page->metadata, true);
        $revisionmetadata = json_decode($revision->metadata, true);
        $sourcerevisions = $page->sourcerevisions();
        return view('page.show', compact(['revision','slug','pagemetadata','revisionmetadata','sourcerevisions', 'milestonecount']));
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
}
