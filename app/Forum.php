<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Forum extends Model
{
    protected $guarded = [];

    public function wiki()
    {
        return $this->belongsTo('App\Wiki');
    }

    public function subforums()
    {
        return $this->hasMany('App\Forum', 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo('App\Forum', 'parent_id');
    }
}
