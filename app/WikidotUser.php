<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WikidotUser extends Model
{
    protected $primaryKey = "wd_user_id";
    protected $guarded = [];
}
