<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $guarded = [];
    protected $dateFormat = 'Y-m-d H:i:s.u';

    public function thread()
    {
        return $this->belongsTo('App\Thread');
    }

    public function author()
    {
        if($this->wd_user_id != null) {
            return $this->belongsTo('App\WikidotUser', 'wd_user_id');
        }
        else { return $this->belongsTo('App\User'); }
    }
}
