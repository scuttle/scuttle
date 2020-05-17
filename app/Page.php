<?php

namespace App;

use App\Jobs\SQS\PushPageId;
use App\Jobs\SQS\PushPageSlug;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Page extends Model
{
    use SoftDeletes;

    public $guarded = [];

    public function slug_milestones()
    {
        return Milestone::withTrashed()->where('wiki_id', $this->wiki_id)->where('slug', $this->slug)->get();
    }

    public function page_milestones()
    {
        return Milestone::withTrashed()->where('page_id', $this->id)->get();
    }

    public function page_slug_milestones()
    {
        return Milestone::withTrashed()->where('wiki_id', $this->wiki_id)->where('page_id', $this->id)->where('slug', $this->slug)->pluck('milestone')->toArray();
    }

    public function revisions()
    {
        return $this->hasMany('App\Revision');
    }

    public function wiki()
    {
        return $this->belongsTo('App\Wiki');
    }

    public function latestrevision()
    {
        return $this->revisions()->orderBy('wd_revision_id','desc')->first();
    }

    public function sourcerevisions()
    {
        return $this->revisions()->where('revision_type','S')->get()->pluck('wd_revision_id')->toArray();
    }

    public function latest_source()
    {
        return $this->revisions()->where('revision_type','S')->orderBy('wd_revision_id','desc')->get()->pluck('content')->first();
    }

    public function votes()
    {
        return $this->hasMany('App\Vote');
    }

    public function files()
    {
        return $this->hasMany('App\File');
    }

    public function refresh_votes()
    {
        $job = new PushPageId($this->wd_page_id, $this->wiki_id);
        $job->send('scuttle-pages-missing-votes');
        return $job;
    }

    public function refresh_revisions()
    {
        $job = new PushPageId($this->wd_page_id, $this->wiki_id);
        $job->send('scuttle-pages-missing-revisions');
        return $job;
    }

    public function refresh_files()
    {
        $job = new PushPageSlug($this->slug, $this->wiki_id);
        $job->send('scuttle-pages-missing-files');
        return $job;
    }

    public function refresh_metadata()
    {
        $fifostring = bin2hex(random_bytes(64));
        $job = new PushPageSlug($this->slug, $this->wiki_id);
        $job->send('scuttle-sched-page-updates.fifo', $fifostring);
        return $job;
    }

    public static function latest($wiki_id, $slug)
    {
        $page_id = Milestone::withTrashed()->where('wiki_id',$wiki_id)->where('slug',$slug)->latest()->pluck('page_id')->first();
        return Page::withTrashed()->find($page_id); // returns null on no match.
    }

    public static function find_by_milestone($wiki_id,$slug,$milestone)
    {
        $page_id = Milestone::withTrashed()->where('wiki_id',$wiki_id)->where('slug',$slug)->where('milestone',$milestone)->pluck('page_id')->first();
        return Page::withTrashed()->find($page_id); // returns null on no match
    }

    public static function milestones_array(array $arr)
    {
        return;
    }

    public function milestone() // This will only get the latest milestone for this page as an int.
    {
        return Milestone::withTrashed()->where('page_id',$this->id)->where('slug', $this->slug)->first('milestone')['milestone'];
    }

    public function add_milestone()
    {
        $milestone = DB::table('milestones')->where('slug', $this->slug)->where('wiki_id',$this->wiki_id)->max('milestone');
        if($milestone === null) {
            $newmilestone = 0;
        }
        else {
            $newmilestone = $milestone + 1;
        }
        $m = new Milestone([
            'page_id' => $this->id,
            'user_id' => auth()->id(),
            'wd_user_id' => $this->wd_user_id,
            'wiki_id' => $this->wiki_id,
            'slug' => $this->slug,
            'milestone' => $newmilestone,
        ]);
        $m->save();
    }

    public function tags()
    {
        return $this->belongsToMany('App\Tag','page_tags');
    }

    public function update_tags(array $wdtags, int $revision_number) : void
    {
        // We get a list from Wikidot of tags for a page. When tags are updated, make them match in the database.
        $existingtags = $this->tags->pluck('name')->toArray();

        // These tags were removed.
        $deletedtags = leo_array_diff($existingtags,$wdtags);

        // Remove their pagetag entry.
        $removedtags = Tag::find_by_name($this->wiki_id, $deletedtags);
        foreach ($removedtags as $removedtag) {
            $pagetag = PageTag::where('page_id', $this->id)->where('tag_id',$removedtag->id)->first();
            $pagetag->revision_number_deleted = $revision_number;
            // We do deletes before adds as there's a unique key constraint that includes deleted_at, since a tag could be re-added later.
            // It shouldn't matter either way, but let's play it safe.
            $pagetag->delete();
        }

        // And here are our new tags.
        $newtags = leo_array_diff($wdtags,$existingtags);

        // Get their IDs from the database or new them up.
        foreach ($newtags as $newtag) {
            $tag = Tag::firstOrCreate(['wiki_id' => $this->wiki_id, 'name' => $newtag]);
            $page_tag = new PageTag;
            $page_tag->page_id = $this->id;
            $page_tag->tag_id = $tag->id;
            $page_tag->revision_number_added = $revision_number;
            $page_tag->wiki_id = $this->wiki_id;
            $page_tag->save();
        }

        return;
    }
}
