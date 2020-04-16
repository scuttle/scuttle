<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $guarded = [];

    public function pages()
    {
        return $this->belongsToMany('App\Page','page_tags');
    }

    public static function find_by_name(int $wiki_id, array $needle)
    {
        return Tag::where('wiki_id', $wiki_id)->whereIn('name', $needle)->get();
    }
}
