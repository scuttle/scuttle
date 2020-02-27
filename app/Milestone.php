<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{
    public $guarded = [];

    public function page()
    {
       return $this->belongsTo('App\Page');
    }

    public function wikidot_user()
    {
        return $this->belongsTo('App\WikidotUser','wd_user_id','wd_user_id');
    }
}
