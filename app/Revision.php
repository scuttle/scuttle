<?php

namespace App;

use cogpowered\FineDiff\Diff;
use cogpowered\FineDiff\Granularity\Word;
use Illuminate\Database\Eloquent\Model;

class Revision extends Model
{
    public $guarded = [];

    public function page()
    {
        return $this->belongsTo('App\Page');
    }

    public function diff()
    {
        $granularity = new Word;
        $diff = new Diff($granularity);
        $lastmajor = $this->page->revisions()
            ->where('metadata->major',true)
            ->orderBy('metadata->wd_revision_id','desc')
            ->get()->first();
        $opcodes = $diff->getOpcodes($lastmajor->content,$this->content);
        // If the opcodes are less than half the size of the new body, store the opcodes in lieu of the whole text.
        // Return the value that should go to metadata->major.
        if(strlen($opcodes) / strlen($this->content) > 0.5) {
            $this->content = $opcodes;
            return false;
        }
        else { // If the opcodes are more than half the size of the new body, leave it alone and call it a major revision.
            return true;
        }
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
