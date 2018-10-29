<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Revision extends Model
{
    public $guarded = [];

    public function page()
    {
        return $this->belongsTo('App\Page');
    }

    public function parse($content)
    {
        $rules = array(
          'anchor',
          'bold',
          'italic',
          'underline'
        );


        foreach ($rules as $rule) {
            // Dynamic function given a rulename.
            $content = $this->$rule($content);
        }
        return $content;
    }

    public function anchor($content)
    {
        return preg_replace_callback(
            "/(\[\[# )([-_A-Za-z0-9.%]+?)(\]\])/i",
            function($match) {
                return "<a name='".$match[2]."'></a>";
            },
            $content
        );
    }

    public function bold($content)
    {
       return preg_replace_callback(
            "/\*\*([^\s\n](?:.*[^\s\n])?)\*\*/U",
            function($match) {
                return "<b>".$match[1]."</b>";
            },
            $content
        );
    }

    public function italic($content)
    {
        return preg_replace_callback(
            "/\/\/([^\s](?:.*[^\s])?)\/\//U",
            function($match) {
                return "<i>".$match[1]."</i>";
            },
            $content
        );
    }

    public function underline($content)
    {
        return preg_replace_callback(
            "/__([^\s](?:.*[^\s])?)__/U",
            function($match) {
                return "<u>".$match[1]."</u>";
            },
            $content
        );
    }
}
