<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Vote extends Model
{
    use SoftDeletes;

    public $guarded = [];

    public function page()
    {
        return $this->belongsTo('App\Page');
    }

    public function user()
    {
        // We will need to change this up when we're taking non-WD votes.
        return $this->belongsTo('App\WikidotUser', 'wd_user_id');
    }

    public static function getStatus($reason)
    {
        return Cache::rememberForever('votes_status.'.$reason, function($reason) {
            return DB::table('votes_status')->where('status',$reason)->pluck('id')->first() ?? -1;
        });
    }

    public function deleteBecause($reason)
    {
        // We'll receive $reason which will correspond to a string in votes_status.
        $status = Cache::rememberForever('votes_status.'.$reason, function($reason) {
            return DB::table('votes_status')->where('status',$reason)->pluck('id')->first() ?? -1;
        });
        $this->status = $status;
        if($this->delete()) { return true; }
        else { return false; }
    }

}
