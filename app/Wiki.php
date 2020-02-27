<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Wiki extends Model
{
    public function pages()
    {
        return $this->hasMany('App\Page');
    }

    public function forums()
    {
        return $this->hasMany('App\Forum');
    }

    public function domains()
    {
        return $this->hasMany('App\Domain');
    }
}
