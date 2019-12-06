<?php

namespace App;

use App\Jobs\SQS\PushPageId;
use App\Jobs\SQS\PushPageSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{

    use SoftDeletes;

    public $guarded = [];
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

}
