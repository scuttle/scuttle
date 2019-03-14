<?php

namespace App\Http\Controllers;

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
    public function show(Page $page, $slug)
    {
        $revision = $page->revisions()->latest()->first();
        $pagemetadata = json_decode($page->metadata, true);
        $revisionmetadata = json_decode($revision->metadata, true);
        return view('page.show', compact(['revision','slug','pagemetadata','revisionmetadata']));
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
        $pagemetadata = json_decode($page->metadata, true);
        $revisionmetadata = json_decode($revision->metadata, true);
        return view('page.show', compact(['revision','slug','pagemetadata','revisionmetadata']));
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
