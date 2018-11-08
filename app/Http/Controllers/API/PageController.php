<?php

namespace App\Http\Controllers\API;

use App\Domain;
use App\Page;
use App\Revision;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PageController extends Controller
{
    public function putwikidotmetadata(Domain $domain, Request $request)
    {
        $json = $request->json()->all();
        Storage::put('/wikidot/metadata/'.$domain->wiki->id.'.json', $json);
        return response('ok');
    }
    
    public function getwikidotmetadata(Domain $domain)
    {
        $metadata = Storage::get('/wikidot/metadata/'.$domain->wiki->id.'.json');
        return $metadata;
    }
    
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
     * Send a listing of pages and their properly scraped revisions to a 2stacks client.
     *
     * @param  \App\Domain  $domain
     * @return \Illuminate\Http\Response
     */
    public function getscrapemanifest(Domain $domain)
    {
        $pages = Page::where('wiki_id', $domain->wiki->id)->get();
        $arr = [];
        foreach($pages as $page) {
            $scraped = array();
                $metadata = json_decode($page->metadata);
                if (isset($metadata->wd_scraped_revisions)) {
                    $scraped[] = $metadata->wd_scraped_revisions;
                }

            $arr[$page->slug]['id'] = $page->id;
            $arr[$page->slug]['revisions'] = $scraped;
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
        $p = Page::where('wiki_id',$domain->wiki->id)->where('slug', $request->fullname)->get();
        if ($p->isEmpty()) {
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
                    'wd_scraped_revisions' => array($request->revisions),
                    'created_by' => array(
                        'author_type' => 'import',
                        'author' => $request->created_by,
                    ),
                )),
                'JsonTimestamp' => Carbon::now()
            ]);
            $page->save();
        }

        else {
            $page = $p->first();
            $oldmetadata = json_decode($page->metadata, true);
            if(isset($oldmetadata["wd_scraped_revisions"])) {
                $revs = $oldmetadata["wd_scraped_revisions"];
            }
            $revs[] = $request->revisions;
            $page->metadata = json_encode(array(
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
                'wd_scraped_revisions' => $revs,
            ));
                $page->JsonTimestamp = Carbon::now();
                $page->save();
        }
        if(strlen($request->payload) == 0) { $request->payload = "#"; }
        $revision = new Revision([
            'page_id' => $page->id,
            'user_id' => 0,
            'content' => $request->payload,
            'metadata' => json_encode(array(
                'description' => "Imported by 2stacks.",
                'major' => true,
                'rating' => $request->rating,
                'display_author' => $request->updated_by,
                'updated_at' => Carbon::parse($request->updated_at)->timestamp,
                'wd_revision_id' => $request->revisions,
            ))
        ]);
        $revision->save();
        return response("ok");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Domain  $domain
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function putscraperevision(Domain $domain, Request $request)
    {
        if(strlen($request->payload) == 0) { $request->payload = "#"; }
        $revision = new Revision([
            'page_id' => $request->page_id,
            'user_id' => 0,
            'content' => $request->payload,
            'metadata' => json_encode(array(
                'wd_revision_id' => $request->wd_revision_id,
                'description' => "Imported by 2stacks.",
                'wd_type' => $request->type,
                'display_author' => $request->updated_by,
                'wd_user_id' => $request->wd_user_id,
                'comment' => $request->comment,
                'updated_at' => $request->timestamp,
            ))
        ]);
        $revision->save();
        $page = Page::where('id',$request->page_id)->first();
        $metadata = json_decode($page->metadata, true);
        $metadata["wd_scraped_revisions"][] = $request->wd_revision_id;
        $updated = json_encode($metadata);
        $page->metadata = $updated;
        $page->save();
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
