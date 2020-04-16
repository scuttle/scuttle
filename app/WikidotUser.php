<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WikidotUser extends Model
{
    protected $primaryKey = "wd_user_id";
    protected $guarded = [];

    public function pages()
    {
        return $this->hasMany('App\Page', 'wd_user_id');
    }

    public function revisions()
    {
        return $this->hasMany('App\Revision', 'wd_user_id');
    }

    public function posts()
    {
        return $this->hasMany('App\Post', 'wd_user_id');
    }

    public function votes()
    {
        return $this->hasMany('App\Vote', 'wd_user_id');
    }
}
