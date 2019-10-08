<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $guarded = [];

    public function page()
    {
        return $this->belongsTo('App\Page');
    }

    public function uploader()
    {
        return $this->belongsTo('App\User');
    }
}
