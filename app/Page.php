<?php

namespace App;

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
        return $this->revisions()->orderBy('metadata->wd_revision_id','desc')->first();
    }

    public function lastmajor()
    {
        return $this->revisions()->where('metadata->major', true)->where('metadata->wd_type', 'S')->orderBy('metadata->wd_revision_id', 'desc')->first();
    }

    public function sourcerevisions()
    {
        $json = $this->revisions()->where('metadata->wd_type','S')->get()->pluck('metadata');
        $sourcerevisionslist = array();
        foreach($json as $metadata) {
            $m = json_decode($metadata, true);
            $sourcerevisionslist[] = $m["wd_revision_id"];
        }
        return $sourcerevisionslist;
    }

}
