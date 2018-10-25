<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    public $primaryKey = 'domain';
    public $incrementing = false;
    public $keyType = 'string';

    public function wiki()
    {
        return $this->belongsTo('App\Wiki');
    }

    public static function search($needle)
    {
        $domain = Domain::where('domain',$needle)->first();
        return $domain->wiki;
    }
}
