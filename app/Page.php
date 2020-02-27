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

    public function milestones()
    {
        return Milestone::withTrashed()->where('wiki_id', $this->wiki_id)->where('slug', $this->slug)->pluck('milestone')->toArray();
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

    public function votes()
    {
        return $this->hasMany('App\Vote');
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
        if ($page_id != null) {
            return Page::withTrashed()->find($page_id);
        }
        else { return null; }
    }

    public static function find_by_milestone($wiki_id,$slug,$milestone)
    {
        $page_id = Milestone::withTrashed()->where('wiki_id',$wiki_id)->where('slug',$slug)->where('milestone',$milestone)->pluck('page_id')->first();
        if ($page_id != null) {
            return Page::find($page_id);
        }
        else { return null; }
    }

    public function milestone()
    {
        return Milestone::withTrashed()->where('page_id',$this->id)->pluck('milestone');
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

}
