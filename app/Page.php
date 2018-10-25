<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{

    public $guarded = [];
    public function revisions()
    {
        return $this->hasMany('App\Revision');
    }

    public function wiki()
    {
        return $this->belongsTo('App\Wiki');
    }
}
