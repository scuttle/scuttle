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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Jobs\PushPageSlug;

class PageController extends Controller
{
    public function put_2stacks_pages_manifest(Domain $domain, Request $request)
    {
        if(Gate::allows('write-programmatically')) {
            $reportedpages = $request->toArray(); // Wikidot's list of pages as provided by 2stacks/lambda.
            $scuttlepages = DB::table('pages')
                ->select('slug')
                ->where('wiki_id',$domain->wiki->id)
                ->pluck('slug')->toArray(); // Our list of pages which will include some deleted slugs.

            // This is an alternative to array_diff that works about 1400x faster.
            // It will find a single mismatch in a pair of 11,000+ item lists in under 2 seconds on a single core.
            // https://stackoverflow.com/a/6700430/3946227
            function leo_array_diff($a, $b) {
                $map = array();
                foreach($a as $val) $map[$val] = 1;
                foreach($b as $val) unset($map[$val]);
                return array_keys($map);
            }

            $unaccountedpages = leo_array_diff($reportedpages, $scuttlepages);

            // Let's stub out the page and note that we need metadata for the page.
            foreach ($unaccountedpages as $item) {
                $page = new Page([
                    'wiki_id' => $domain->wiki->id,
                    'user_id' => auth()->id(),
                    'slug' => $item,
                    'milestone' => 0,
                    'metadata' => json_encode(
                        array(
                            'page_missing_metadata' => true
                        )
                    ),
                    'JsonTimestamp' => Carbon::now()
                ]);
                $page->save();
                // Send an SQS message for 2stacks-lambda to work on.
                PushPageSlug::dispatch($page->slug)->onQueue('scuttle-pages-missing-metadata');
            }
            return response(json_encode($unaccountedpages)); // give the submitter a note of which ones were new.
        }
    }

    public function put_page_metadata(Domain $domain, Request $request)
    {
        if(Gate::allows('write-programmatically')) {
            $p = Page::where('wiki_id', $domain->wiki->id)
                ->where('slug', $request["slug"])
                ->orderBy('milestone', 'desc')
                ->get();
            if($p->isEmpty()) {
                // Well this is awkward.
                // 2stacks just sent us metadata about a slug we don't have.
                // Summon the troops.
                Log::error('2stacks sent us metadata about ' . $request->slug . ' for wiki ' . $domain->wiki->id . ' but SCUTTLE doesn\'t have a matching slug!');
                return response('I don\'t have a slug to attach that metadata to!', 500)
                    ->header('Content-Type', 'text/plain');
            }
            else {
                $page = $p->first();
                $timestamp = Carbon::parse($request["wikidot_metadata"]["created_at"])->timestamp;
                $oldmetadata = json_decode($page->metadata, true);
                if($oldmetadata["page_missing_metadata"] == true) {
                    // This is the default use case, responding to the initial SQS message on a new page arriving.
                    $page->wd_page_id = $request["wd_page_id"];
                    $page->latest_revision = $request["latest_revision"];
                    $page->metadata = json_encode(array(
                        'wikidot_metadata' => $request["wikidot_metadata"],
                        'page_needs_votes' => true,
                        'page_needs_files' => true,
                        'page_needs_revisions' => true,
                        'page_needs_comments' => true,
                        'wd_page_created_at' => $timestamp
                    ));
                    $page->jsonTimestamp = Carbon::now(); // touch on update
                    $page->save();
                    return response('saved');
                }
            }
        }
    }

    public function get_pages_missing_metadata(Domain $domain)
    {
        $pages = DB::table('pages')
            ->select('slug')
            ->where('wiki_id',$domain->wiki->id)
            ->whereRaw('JSON_CONTAINS_PATH(metadata, "one", "$.page_missing_metadata")')
            ->pluck('slug');
        return response($pages->toJson());
    }

    public function putwikidotmetadata(Domain $domain, Request $request)
    {
        $json = $request->json()->all();
        Storage::put('/wikidot/metadata/'.$domain->wiki->id.'.json', json_encode($json));
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
        $pages = Page::where('wiki_id', $domain->wiki->id)
            ->orderBy('slug','asc')
            ->orderBy('metadata->milestone','desc')
            ->get()
            ->unique('slug'); // We only want to send the most recent milestone.
        $arr = [];
        foreach($pages as $page) {
            $scraped = array();
                $metadata = json_decode($page->metadata);
                if (isset($metadata->wd_scraped_revisions)) {
                    $scraped[] = $metadata->wd_scraped_revisions;
                }

            $arr[$page->slug]['id'] = $page->id;
            $arr[$page->slug]['revisions'] = $metadata->revisions;
            $arr[$page->slug]['wd_scraped_revisions'] = $scraped;
            $arr[$page->slug]['timestamp'] = $metadata->updated_at;
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
        // Look for a page matching this wiki and slug, sort by milestone.
        $p = Page::where('wiki_id',$domain->wiki->id)->where('slug', $request->fullname)->orderBy('metadata->milestone','desc')->get();

        // If we haven't seen this slug before for this wiki, store it as new.
        if ($p->isEmpty()) {
            // Prep the rating history array before we build the metadata.
            $ratinghistory = array();
            $timestamp = Carbon::parse($request->created_at)->timestamp;
            $ratinghistory[$timestamp] = $request->rating;
            $page = new Page([
                'wiki_id' => $domain->wiki->id,
                'slug' => $request->fullname,
                'metadata' => json_encode(array(
                    'updated_by' => array(
                        'author_type' => 'import',
                        'author' => $request->updated_by,
                    ),
                    'updated_at' => Carbon::now()->timestamp,
                    'milestone' => 0,
                    'rating' => $request->rating,
                    'parent_slug' => $request->parent_fullname,
                    'parent_title' => $request->parent_title,
                    'revisions' => $request->revisions,
                    'tags' => $request->tags,
                    'title' => $request->title,
                    'wd_title_shown' => $request->title_shown,
                    'commentcount' => $request->comments,
                    'created_at' => Carbon::parse($request->created_at)->timestamp,
                    'wd_scraped_revisions' => array($request->revisions), // We're going to immediately store this revision we're pulling.
                    'created_by' => array(
                        'author_type' => 'import',
                        'author' => $request->created_by,
                    ),
                    'rating_history' => $ratinghistory,
                )),
                'JsonTimestamp' => Carbon::now()
            ]);
            $page->save();
        }

        // Otherwise...
        else {
            // Grab the most recent milestone and unpack the metadata.
            $page = $p->first();
            $oldmetadata = json_decode($page->metadata, true);

            $timestamp = Carbon::parse($request->created_at)->timestamp;
            // If the timestamp matches, we're still on the same milestone.
            if ($oldmetadata["created_at"] == $timestamp) {
                // Update scraped revision list.
                $revs = $oldmetadata["wd_scraped_revisions"];
                $revs[] = $request->revisions;

                // Update point-in-time rating history.
                if (!isset($oldmetadata["rating_history"])) {
                    $oldmetadata["rating_history"] = array();
                }
                $ratinghistory = $oldmetadata["rating_history"];
                $ratinghistory[$timestamp] = $request->rating;

                // Re-encode metadata to JSON with existing milestone number.
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
                    'milestone' => $oldmetadata["milestone"],
                    'title' => $request->title,
                    'rating_history' => $ratinghistory,
                    'wd_title_shown' => $request->title_shown,
                    'commentcount' => $request->comments,
                    'created_at' => $timestamp,
                    'created_by' => array(
                        'author_type' => 'import',
                        'author' => $request->created_by,
                    ),
                    'wd_scraped_revisions' => $revs,
                ));
                $page->JsonTimestamp = Carbon::now();
                $page->save();
            }

            // If not, this is a new milestone for the page.
            // Save it as a new page with an incremented milestone number.
            else {
                $newmilestone = $oldmetadata['milestone'] + 1;
                $page = new Page([
                    'wiki_id' => $domain->wiki->id,
                    'slug' => $request->fullname,
                    'metadata' => json_encode(array(
                        'updated_by' => array(
                            'author_type' => 'import',
                            'author' => $request->updated_by,
                        ),
                        'updated_at' => Carbon::now()->timestamp,
                        'milestone' => $newmilestone,
                        'rating' => $request->rating,
                        'parent_slug' => $request->parent_fullname,
                        'parent_title' => $request->parent_title,
                        'revisions' => $request->revisions,
                        'tags' => $request->tags,
                        'title' => $request->title,
                        'wd_title_shown' => $request->title_shown,
                        'commentcount' => $request->comments,
                        'created_at' => $timestamp,
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
        }

            // Process the revision.
            if (strlen($request->payload) == 0) {
                $request->payload = "#";
            }
            $revision = new Revision([
                'page_id' => $page->id,
                'user_id' => 0,
                'content' => $request->payload
            ]);

            # The diff() method will calculate the diff of the revision versus the last major.
            # If it's more than half the size of the current payload, it becomes a major revision and returns true.
            # If not, the payload is transformed to opcodes and returns false.
            # We adjust the content/payload from within the method if needed.
            $major = $revision->diff();

            $revision->metadata = json_encode(array(
                'description' => "Imported by 2stacks.",
                'major' => $major,
                'rating' => $request->rating,
                'wd_type' => 'S',
                'display_author' => $request->updated_by,
                'updated_at' => Carbon::parse($request->updated_at)->timestamp,
                'wd_revision_id' => $request->revisions,
            ));
            $revision->save();
            return response("ok");
    }

    /**
     * Recalculate diffs between revisions in a work after being notified by 2stacks that all old revisions are onboard.
     *
     * @param  \App\Domain  $domain
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function recalculatediffs(Domain $domain, Request $request)
    {
        # Since this is coming from 2stacks on a live scrape, we can safely assume we're working with the latest milestone.
//        $page = Page::where('wiki_id', $domain->wiki->id)->where('slug', $request->slug)
//            ->orderBy('metadata->milestone', 'desc')->first();
        # Get all revisions of this page in chronological order.
//        $revisions = Revision::where('page_id', $page->id)->orderBy('metadata->wd_revision_id')->get();

        # We're entering this method from the scrape module of 2stacks, which means we're storing whole (non-diffed)
        # copies of the content. We want to be careful not to corrupt the integrity of the content in the name of space
        # saving with the diffs. This requires a great deal of care as we iterate through them. We need to keep track of
        # a few pieces of information:
        # * A pointer to the most recent major revision.
        # * A pointer to the most recent minor revision that WAS a major REVISION.
        # * A list of all minor revisions that BECAME major revisions.
        # The difference here is that many majors will become minors in this process, and when we hit a natural minor,
        # it is likely getting its diff from a revision that has since been diff()'ed. So the most recent dead
        # major will need to be rehydrated to a full copy so the diff can be recalculated from the most recent live
        # major. This sounds cumbersome, and it is, but this is due to wikidot not allowing us to pull the content of
        # old revisions programmatically, so we're getting them two different ways.
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

        // Ratings from a 2stacks scrape will have a leading + or -, whereas from the API will only have a leading -, but not a +.
        $rating = $request->rating;
        if($rating[0] == '+') { $rating = substr($rating,1); }

        $revision = new Revision([
            'page_id' => $request->page_id,
            'user_id' => 0,
            'content' => $request->payload
            ]);

        # Rather than calculating diffs here, we'll store them intact and recalculate them on the fly when we get the
        # 'scrape complete' signal from 2stacks, which will fire recalculatediffs() on this page and revision set.
        if($request->type == 'S') {
            $major = true;
        }
        else { $major = false; }
        $revision->metadata = json_encode(array(
            'wd_revision_id' => $request->wd_revision_id,
            'description' => "Imported by 2stacks.",
            'wd_type' => $request->type,
            'major' => $major,
            'rating' => $rating,
            'display_author' => $request->updated_by,
            'wd_user_id' => $request->wd_user_id,
            'comment' => $request->comment,
            'updated_at' => $request->timestamp,
        ));
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
     * Store wikidot's unique identifiers for pages, revisions, etc.
     *
     * @param  \App\Domain  $domain
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function putwikidotids(Domain $domain, Request $request)
    {
        # Since this is coming from 2stacks on a live scrape, we can safely assume we're working with the latest milestone.
        $page = Page::where('wiki_id', $domain->wiki->id)->where('slug', $request->slug)
            ->orderBy('metadata->milestone', 'desc')->first();
        $metadata = json_decode($page->metadata, true);
        $metadata["wd_page_id"] = $request->wd_page_id;
        $updated = json_encode($metadata);
        $page->metadata = $updated;
        $page->save();
        return response("ok");
    }

    /**
     * Get wikidot's unique identifiers for pages
     *
     * @param  \App\Domain  $domain
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getwikidotids(Domain $domain, Request $request)
    {
        if($request->all === true) {
            $response = DB::table('pages')->select('slug','metadata->wd_page_id as wd_page_id')->where([['wiki_id',$domain->wiki->id],['metadata->wd_page_id','!=',NULL]])->get();
        }
        else {
            $response = DB::table('pages')->select('slug','metadata->wd_page_id as wd_page_id')->whereIn('pages.slug',$request->pages)->where([['wiki_id',$domain->wiki->id],['metadata->wd_page_id','!=',NULL]])->get();
        }
        return response($response->toJson());
    }

    /**
     * Get the last Wikidot page ID captured for this wiki.
     *
     * @param  \App\Domain  $domain
     * @return \Illuminate\Http\Response
     */
    public function lastwikidotid(Domain $domain)
    {
        return response(DB::table('pages')->select(DB::raw('MAX(JSON_EXTRACT(metadata,"$.wd_page_id")) as max'))->pluck('max')->first());
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
