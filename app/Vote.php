<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    public $guarded = [];

    public function page()
    {
        return $this->belongsTo('App\Page');
    }

    public function user()
    {
        // We will need to change this up when we're taking non-WD votes.
        return $this->belongsTo('App\WikidotUser');
    }

}
