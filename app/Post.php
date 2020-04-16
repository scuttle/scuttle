<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $guarded = [];

    public function thread()
    {
        return $this->belongsTo('App\Thread');
    }

    public function children()
    {
        return $this->hasMany('App\Post','parent_id');
    }

    public function parent()
    {
        return $this->hasOne('App\Post','id','parent_id');
    }

    public function author()
    {
        if($this->wd_user_id != null) {
            return $this->belongsTo('App\WikidotUser', 'wd_user_id');
        }
        else { return $this->belongsTo('App\User'); }
    }
}
