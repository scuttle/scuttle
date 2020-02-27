<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.u';

    public function page()
    {
        return $this->belongsTo('App\Page');
    }

    public function posts()
    {
        return $this->hasMany('App\Post');
    }

    public function forum()
    {
        return $this->belongsTo('App\Forum');
    }

    public function creator()
    {
        if($this->wd_user_id != null) {
            return $this->belongsTo('App\WikidotUser', 'wd_user_id');
        }
        else { return $this->belongsTo('App\User'); }
    }
}
